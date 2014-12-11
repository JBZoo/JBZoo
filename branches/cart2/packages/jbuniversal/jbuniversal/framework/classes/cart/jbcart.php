<?php

require_once dirname(__FILE__) . '/jbvalue.php';

/**
 * Class JBCart
 */
class JBCart
{
    const DEFAULT_POSITION = 'list';

    const NOTIFY_ORDER_CREATE  = 'order_create';
    const NOTIFY_ORDER_EDIT    = 'order_edit';
    const NOTIFY_ORDER_STATUS  = 'order_status';
    const NOTIFY_ORDER_PAYMENT = 'order_payment';

    const MODIFIER_ORDER = 'modifier_order';
    const MODIFIER_ITEM  = 'modifier_item';

    const STATUS_ORDER    = 'order';
    const STATUS_PAYMENT  = 'payment';
    const STATUS_SHIPPING = 'shipping';

    const CONFIG_NOTIFICATION      = 'notification';
    const CONFIG_MODIFIERS         = 'modifier';
    const CONFIG_VALIDATORS        = 'validator';
    const CONFIG_PAYMENTS          = 'payment';
    const CONFIG_SHIPPINGS         = 'shipping';
    const CONFIG_STATUS_EVENTS     = 'status_events';
    const CONFIG_CURRENCIES        = 'currency';
    const CONFIG_STATUSES          = 'status';
    const CONFIG_EMAIL_TMPL        = 'email_tmpl';
    const CONFIG_SHIPPINGFIELDS    = 'shippingfield';
    const CONFIG_FIELDS            = 'field';
    const CONFIG_FIELDS_TMPL       = 'field_tmpl';
    const CONFIG_PRICE             = 'price';
    const CONFIG_PRICE_TMPL        = 'price_tmpl';
    const CONFIG_PRICE_TMPL_FILTER = 'price_tmpl_filter';

    const ELEMENT_TYPE_DEFAULT       = 'element';
    const ELEMENT_TYPE_CURRENCY      = 'currency';
    const ELEMENT_TYPE_SHIPPING      = 'shipping';
    const ELEMENT_TYPE_SHIPPINGFIELD = 'shippingfield';
    const ELEMENT_TYPE_MODIFIERITEM  = 'modifieritem';
    const ELEMENT_TYPE_MODIFIERPRICE = 'modifierprice';
    const ELEMENT_TYPE_MODIFIERS     = 'modifier';
    const ELEMENT_TYPE_NOTIFICATION  = 'notification';
    const ELEMENT_TYPE_ORDER         = 'order';
    const ELEMENT_TYPE_EMAIL         = 'email';
    const ELEMENT_TYPE_PAYMENT       = 'payment';
    const ELEMENT_TYPE_PRICE         = 'price';
    const ELEMENT_TYPE_STATUS        = 'status';
    const ELEMENT_TYPE_VALIDATOR     = 'validator';

    /**
     * @var string
     */
    protected $_sessionNamespace = 'jbcart';

    /**
     * @var string
     */
    protected $_namespace = 'jbzoo';

    /**
     * @var App
     */
    public $app = null;

    /**
     * @var JSONData
     */
    protected $_config = null;

    /**
     * @var JBMoneyHelper
     */
    protected $_jbmoney;

    /**
     * @return JBCart
     */
    public static function getInstance()
    {
        static $instance;

        if (is_null($instance)) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Class constructor
     * Constructor
     */
    private function __construct()
    {
        $this->app = App::getInstance('zoo');

        $this->_config  = JBModelConfig::model()->getGroup('cart.config');
        $this->_jbmoney = $this->app->jbmoney;
    }

    /**
     * Create price value object
     * @param int   $value
     * @param null  $currency
     * @param array $rates
     * @return array|int|JBCartValue
     */
    static public function val($value = 0, $currency = null, $rates = array())
    {
        if ($value instanceof JBCartValue) {
            return $value;
        }

        if (is_string($currency)) {
            $value = array($value, $currency);
        }

        return new JBCartValue($value, $rates);
    }

    /**
     * Get new order object
     * @return JBCartOrder
     */
    public function newOrder()
    {
        $order = new JBCartOrder();

        $order->id         = 0;
        $order->created    = $this->app->jbdate->toMySql();
        $order->created_by = (int)JFactory::getUser()->id;

        return $order;
    }

    /**
     * Get payment success status
     * @return string
     */
    public function getPaymentSuccess()
    {
        return $this->_config->get('default_payment_status_success', 'success');
    }

    /**
     * Get default status from cart configurations
     * @param string $type
     * @return JBCartElementStatus
     */
    public function getDefaultStatus($type = JBCart::STATUS_ORDER)
    {
        $statusCode = null;
        if ($type == JBCart::STATUS_ORDER) {
            $statusCode = $this->_config->get('default_order_status');

        } else if ($type == JBCart::STATUS_PAYMENT) {
            $statusCode = $this->_config->get('default_payment_status');

        } else if ($type == JBCart::STATUS_PAYMENT) {
            $statusCode = $this->_config->get('default_shipping_status');
        }

        if ($statusCode) {
            $status = $this->app->jbcartstatus->getByCode($statusCode, $type);
            if ($status) {
                return $status;
            }
        }

        $undefined = $this->app->jbcartstatus->getUndefined();

        return $undefined;
    }

    /**
     * Get all items from session
     * @param bool $assoc
     * @return mixed
     */
    public function getItems($assoc = true)
    {
        $session = $this->_getSession();
        $items   = $session->get('items', array());
        $result  = array();

        if (!empty($items)) {
            foreach ($items as $key => $item) {
                $result[$key] = $item;
            }
        }

        return $assoc === true ? $result : $this->app->data->create($result);
    }

    /**
     * @param $data
     * @internal param array $list
     */
    public function addItem($data)
    {
        $items = $this->getItems(false);

        $key = $data->get('key');
        if ($items->has($key)) {
            $items[$key]['quantity'] += $data->get('quantity');
        }

        if (!isset($items[$key])) {
            $items[$key] = (array)$data;
        }

        $this->_setSession('items', (array)$items);
    }

    /**
     * Get the weight of all items in basket.
     * @return int
     */
    public function getWeight()
    {
        $items  = $this->getItems();
        $weight = 0;

        foreach ($items as $item) {
            if (!empty($item['params'])) {

                $params = $this->app->data->create($item['params']);
                $temp   = (float)$item['quantity'] * (float)$params['weight'];

                $weight += $temp;
            }
        }

        return $weight;
    }

    /**
     * Get
     * @return mixed
     */
    public function getProperties()
    {
        $items = $this->getItems();

        $properties['height'] = $properties['width'] = $properties['length'] = 0;
        foreach ($items as $item) {
            if (!empty($item['params'])) {

                $params = $this->app->data->create($item['params']);

                $height = (float)$params->get('height', 0);
                $width  = (float)$params->get('width', 0);
                $length = (float)$params->get('length', 0);

                $properties['height'] += $item['quantity'] * $height;
                $properties['width'] += $item['quantity'] * $width;
                $properties['length'] += $item['quantity'] * $length;
            }
        }

        return $properties;
    }

    /**
     * Remove all variations if key is null.
     * $key = {item_id}-{variant_index}.
     * Priority on $key.
     * @param  int    $id
     * @param  string $key
     * @return bool
     */
    public function remove($id, $key = null)
    {
        $items = $this->getItems();

        if (!empty($items)) {

            if (!empty($key)) {
                return $this->removeVariant($key);
            }

            return $this->removeItem($id);
        }

        return false;
    }

    /**
     * Remove item from cart by id.
     * Item_id-variant or item_id for basic.
     * @param  int $id - Item_id
     * @return bool
     */
    public function removeItem($id)
    {
        $items = $this->getItems();

        if (!empty($items)) {
            foreach ($items as $key => $item) {
                if ($item['item_id'] === $id) {
                    unset($items[$key]);
                }
            }

            $this->_setSession('items', $items);

            return true;
        }

        return false;
    }

    /**
     * Remove item's variant from cart by $key.
     * Item_id-variant or item_id for basic.
     * @param string $key - Item_id + index of variant.
     * @return bool
     */
    public function removeVariant($key)
    {
        $items = $this->getItems();

        if (isset($items[$key])) {

            unset($items[$key]);
            $this->_setSession('items', $items);

            return true;
        }

        return false;
    }

    /**
     * Remove all items in cart
     */
    public function removeItems()
    {
        $this->app->jbsession->set('items', array(), $this->_sessionNamespace);
    }

    /**
     * Change item quantity from basket
     * @param $key
     * @param $quantity
     */
    public function changeQuantity($key, $quantity)
    {
        $items = $this->getItems();

        if ($this->inCart($key)) {
            $items[$key]['quantity'] = (float)$quantity;

            $this->_setSession('items', $items);
        }
    }

    /**
     * Is in stock item
     * @param $quantity
     * @param $key
     * @return bool
     */
    public function inStock($quantity, $key = null)
    {
        return true;
        $item_id    = null;
        $no         = null;
        $element_id = null;

        list($item_id, $no, $element_id) = explode('_', $key);
        $item = $this->app->table->item->get($item_id);

        if (!$element_id) {

            $element = $item->getElement($no);
            $data    = (array)$element->getBasicData();

        } else {

            $element = $item->getElement($element_id);
            $data    = $element->getVariations($no);
        }

        $data  = $this->app->data->create($data);
        $value = $data->find('_balance.value');

        if (!empty($data)) {

            if (isset($value) && $value == 0) {
                return false;

            } else if ($value == -1 || $value >= $quantity) {
                return true;

            } else if (!isset($value)) {
                return true;

            } else {
                return false;
            }
        }

        return false;

    }

    /**
     * Recount all basket
     * @return array
     */
    public function recount()
    {
        $itemsPrice = array();

        $count = 0;
        $total = JBCart::val(0);
        $items = $this->getItems();

        foreach ($items as $key => $item) {

            $itemsPrice[$key] = array();

            $itemTotal = JBCart::val($item['total']);

            $count += $item['quantity'];
            $itemTotal->multiply($item['quantity']);
            $total->add($itemTotal);

            $itemsPrice[$key]['Subtotal'] = $itemTotal->html();
        }

        $result = array(
            'items'      => $itemsPrice,
            'TotalCount' => $count,
            'TotalPrice' => $total->html(),
            'Total'      => $total->html(),
        );

        return $result;
    }

    /**
     * Check if item in cart.
     * @param  string $id - item_id.
     * @return bool
     */
    public function inCart($id)
    {
        $items = $this->getItems();

        if (isset($items[$id])) {
            return true;
        }

        return false;
    }

    /**
     * Check if item or item variation in cart by $key.
     * @param  string $key - {Item_id}-{variant} or {item_id} for basic.
     * @return bool
     */
    public function inCartVariant($key)
    {
        $items = $this->getItems();

        if (isset($items[$key])) {
            return true;
        }

        return false;
    }

    /**
     * Get session
     * @return JSONData
     */
    protected function _getSession()
    {
        $session = JFactory::getSession();
        $result  = $session->get($this->_sessionNamespace, array(), $this->_namespace);
        $result  = $this->app->data->create($result);

        return $result;
    }

    /**
     * Set session
     * @param string $key
     * @param mixed  $value
     */
    protected function _setSession($key, $value)
    {
        $session      = JFactory::getSession();
        $result       = $session->get($this->_sessionNamespace, array(), $this->_namespace);
        $result[$key] = $value;

        $session->set($this->_sessionNamespace, $result, $this->_namespace);
    }

}
