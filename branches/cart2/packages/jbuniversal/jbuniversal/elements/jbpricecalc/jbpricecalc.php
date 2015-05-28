<?php
/**
 * JBZoo App is universal Joomla CCK, application for YooTheme Zoo component
 * @package     jbzoo
 * @version     2.x Pro
 * @author      JBZoo App http://jbzoo.com
 * @copyright   Copyright (C) JBZoo.com,  All rights reserved.
 * @license     http://jbzoo.com/license-pro.php JBZoo Licence
 * @coder       Alexander Oganov <t_tapak@yahoo.com>
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

App::getInstance('zoo')->loader->register('ElementJBPrice', 'elements:jbprice/jbprice.php');

/**
 * Class ElementJBPriceAdvance
 * The Price element for JBZoo
 * @since 2.2
 */
class ElementJBPriceCalc extends ElementJBPrice
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->isOverlay = true;
    }

    /**
     * Get variant from $this->data() by values
     * MODE: OVERLAY
     * @param  array $values
     * @return array
     */
    public function getVariantByValues($values = array())
    {
        $data = (array)$this->get('values', array());

        if (empty($values) || empty($data)) {
            return (array)$values;
        }

        $variations = array();
        foreach ($data as $i => $value) {
            foreach ($value as $identifier => $fields) {
                if (isset($values[$identifier])) {

                    $diff = array_diff_assoc($fields, $values[$identifier]);
                    if (empty($diff)) {
                        $variations[$i] = $this->get('variations.' . $i, array());
                    }
                }
            }
        }

        return (array)$variations;
    }

    /**
     * @param array $template
     * @param array  $values
     */
    public function ajaxChangeVariant($template = array('default'), $values = array())
    {
        $list = $this->getVariantByValues($values);

        $keys = array_keys($list);
        $key  = (int)end($keys);
        $data = array();

        $this->setDefault($key);

        if(count($template)) {
            foreach ($template as $tpl)
            {
                $this->setTemplate($tpl);

                if ($this->_list instanceof JBCartVariantList) {
                    $this->_list->clear();
                }

                $this->getList($list, array(
                    'default'  => $key,
                    'values'   => $values,
                    'template' => $tpl,
                    'currency' => !empty($currency) ? $currency : $this->currency()
                ));

                $data = array_merge_recursive((array)$data, (array)$this->_list->renderVariant());
            }
        }

        $this->app->jbajax->send($data);
    }

    /**
     * Ajax add to cart method
     * @param string $template
     * @param int    $quantity
     * @param array  $values
     */
    public function ajaxAddToCart($template = 'default', $quantity = 1, $values = array())
    {
        $jbAjax = $this->app->jbajax;

        //Get variant by selected values
        $list = $this->getVariantByValues($values);

        $cart = JBCart::getInstance();
        $keys = array_keys($list);
        $key  = (int)end($keys);

        //Set the default option, which we have received, not saved. For correct calculation.
        $this->setTemplate($template)->setDefault($key);

        $this->getList($list, array(
            'values'   => $values,
            'quantity' => $quantity,
            'currency' => !empty($currency) ? $currency : $this->currency()
        ));
        $session_key = $this->_list->getSessionKey();

        $data = $cart->getItem($session_key);
        if (!empty($data)) {
            $quantity += $data['quantity'];
        }

        //Check balance
        if ($this->inStock($quantity, $key)) {
            $cart
                ->addItem($this->_list->getCartData())
                ->updateItem($cart->getItem($session_key));

            $jbAjax->send(array(), true);

        } else {
            $jbAjax->send(array('message' => JText::_('JBZOO_JBPRICE_ITEM_NO_QUANTITY')), false);
        }

        $jbAjax->send(array('added' => 0, 'message' => JText::_('JBZOO_JBPRICE_NOT_AVAILABLE_MESSAGE')));
    }

    /**
     * @param string $template Template to render
     * @param string $layout   Current price layout
     * @param string $hash     Hash string for communication between the elements in/out modal window
     * @return string
     */
    public function ajaxModalWindow($template = 'default', $layout = 'default', $hash)
    {
        $this->setTemplate($template)->setLayout($layout);
        $this->cache = false;

        $this->getParameters();
        $this->getConfigs();

        $html = $this->render(array(
            'template'       => $template,
            'layout'         => 'modal',
            '_layout'        => $layout,
            'modal_template' => null
        ));

        return parent::renderLayout($this->getLayout('_modal.php'), array(
            'html' => $html
        ));
    }

    /**
     * Remove from cart method
     * @param string $key - Session key
     * @return mixed
     */
    public function ajaxRemoveFromCart($key = null)
    {
        if (!(int)$this->config->get('remove_variant', 0)) {
            $key = null;
        }
        $item_id = $this->getItem()->id;
        $result  = JBCart::getInstance()->remove($item_id, $this->identifier, $key);

        $this->app->jbajax->send(array('removed' => $result));
    }

    /**
     * Get all options for element.
     * Used in element like select, color, radio etc.
     * @param string $id
     * @return array
     */
    public function findOptions($id)
    {
        if (empty($id)) {
            return array();
        }

        return $this->get('selected.' . $id, array());
    }

    /**
     * @param $identifier
     * @return array|void
     */
    public function elementOptions($identifier)
    {
        $modifiers = (int)$this->config->get('show_modifiers', 0);
        $options   = (array)$this->findOptions($identifier);

        if (!$modifiers && count($options)) {
            return array_combine($options, $options);
        }

        return $this->addModifiers($options);
    }

    /**
     * Adds modifier value of each option
     * @param array $options
     * @return array
     */
    public function addModifiers($options = array())
    {
        if (empty($options)) {
            return $options;
        }
        $result = array();

        foreach ($options as $key => $option) {
            $total = JBCart::val();
            $parts = explode('__', $key);
            if ($value = $this->getData($parts[1] . '._value.value')) {
                $total->set($value);
            }

            $result[$option] = $option . ' <em>' . $total->html($this->currency()) . '</em>';
        }

        return $result;
    }

    /**
     * Bind and validate data
     * @param array $data
     */
    public function bindData($data = array())
    {
        if (null !== $this->_item) {
            $hashes = array();

            if (array_key_exists('variations', $data)) {
                $list = $this->build($data['variations']);
                unset($data['variations']);

                // generate hashes
                $values = (array)$this->get('values', array());
                if (count($values)) {
                    $hashes = array_map(create_function('$data', ' return md5(serialize($data));'), $values);
                }

                /** @type JBCartVariant $variant */
                foreach ($list as $key => $variant) {
                    /** @type JBCartElementPrice $element */
                    if (($variant->isBasic()) || ($variant->count('simple') === 1 && !in_array($variant->hash(), $hashes, true))) {

                        //add variant hash to array based on simple elements values
                        $hashes[$key] = $variant->hash();
                    }
                }

                //leave only unique hashes. The array keys are the keys of valid variants.
                $hashes = array_unique($hashes);

                //get valid variants
                $list = array_intersect_key($list, $hashes);

                //generate array values and selected
                if (count($list)) {
                    foreach ($list as $key => $variant) {
                        $variant->setId($key)->bindData();

                        $this->bindVariant($variant);
                        $variant->clear();
                    }
                }
            }

            if (!empty($data)) {
                $result = $this->_item->elements->get($this->identifier);

                foreach ($data as $_id => $unknown) {
                    $result[$_id] = is_string($unknown) ? JString::trim($unknown) : $unknown;
                }
                $this->_item->elements->set($this->identifier, $result);
            }
        }
    }

    /**
     * @param JBCartVariant $variant
     * @return $this
     */
    public function bindVariant(JBCartVariant $variant)
    {
        if ($this->_item !== null) {
            $simple = $variant->simple();
            $data   = $this->data();

            $values     = (array)$data->get('values', array());
            $selected   = (array)$data->get('selected', array());
            $variations = (array)$data->get('variations', array());

            $variations[$variant->getId()] = $variant->data();

            if (!$variant->isBasic()) {
                $values[$variant->getId()] = array_filter(array_map(create_function('$element',
                    'return JString::strlen($element->getValue(true)) > 0 ? (array)$element->data() : null;'), $simple
                ));

                $_selected = array_filter(array_map(create_function('$element', 'return JString::strlen($element->getValue(true)) > 0
                ? JString::trim($element->getValue(true) . \'__\' . $element->variant) : null;'), $simple)
                );

                if ($_selected) {
                    foreach ($_selected as $key => $value) {
                        $val                    = explode('__', $value);
                        $selected[$key][$value] = $val[0];
                    }
                }
            }
            $variations = array_values($variations);
            $values     = array_values($values);

            $data->set('variations', $variations);
            $data->set('selected', $selected);
            $data->set('values', $variant->isBasic() ? array(self::BASIC_VARIANT => array()) : $values);

            $this->_item->elements->set($this->identifier, (array)$data);
        }

        return $this;
    }

    /**
     * Get element search data for sku table
     * @return array
     */
    public function getIndexData()
    {
        $variations = (array)$this->get('variations', array());

        $data = array();
        if (count($variations)) {
            $defKey = $this->defaultKey();

            if ($this->_list instanceof JBCartVariantList) {
                $this->_list->clear();
            }
            $list  = $this->getList();

            // Build list of variants
            $_variants = $this->build($variations);

            // Add default variant to list
            if ($defKey !== self::BASIC_VARIANT) {
                $list->add(array($defKey => $_variants[$defKey]));
            }

            $list->current()->setId(-1);
            $data = array_merge($data, $this->getVariantData($list->current()));
            $list->current()->setId($defKey);

            $first = $list->first();
            // Get basic variant index
            $data = array_merge($data, $this->getVariantData($first));

            $list->add($_variants);
            /** @type JBCartVariant $variant */
            foreach ($list->all() as $variant) {
                if (!$variant->isBasic()) {
                    $data = array_merge($data, $this->getVariantData($variant));
                }
            }
        }

        return $data;
    }

    /**
     * @param JBCartVariant $variant
     * @return array
     */
    public function getVariantData(JBCartVariant $variant)
    {
        $vars = $this->app->jbvars;
        $data   = array();

        if ($variant->isBasic()) {
            $elements = $variant->core();

        } elseif ($variant->is(-1)) {
            $elements = $variant->all();

        } else {
            $elements = $variant->simple();
        }

        if (count($elements)) {
            /**@type JBCartElementPrice $element */
            foreach ($elements as $paramId => $element) {
                $value = $element->getSearchData();
                $id    = $element->identifier;

                $date = $string = $num = null;
                if ($value instanceof JBCartValue) {
                    $value->convert('eur');
                    $string = $value->data(true);
                    $num    = $value->val();
                } else {
                    $value  = JString::trim((string)$value);
                    $string = $value;
                    $num    = $this->_helper->isNumeric($value) ? $vars->number($value) : null;
                    $date   = $this->isDate($value);
                }

                if (($string !== '' && $string !== null) || (is_float($num) || is_int($num)) || ($date !== '' && $date !== null)) {
                    $key = $this->_item->id . '__' . $this->identifier . '__' . $variant->getId() . '__' . $id;

                    $data[$key] = array(
                        'item_id'    => $this->_item->id,
                        'element_id' => $this->identifier,
                        'param_id'   => $id,
                        'value_s'    => $string,
                        'value_n'    => $num,
                        'value_d'    => $date,
                        'variant'    => $variant->getId()
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Load assets
     * @return $this
     */
    public function loadEditAssets()
    {
        parent::loadEditAssets();

        if ((int)$this->config->get('mode', 1)) {
            $this->app->jbassets->js('jbassets:js/admin/validator/calc.js');
        }

        return $this;
    }


    /**
     * @return $this|void
     */
    public function loadAssets()
    {
        $this->app->jbassets->less('elements:jbpricecalc/assets/less/jbpricecalc.less');

        return parent::loadAssets();
    }
}
