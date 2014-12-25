<?php require_once dirname(__FILE__) . '/jbvalue.php';

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
    protected $_namespace        = 'jbzoo';

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
     * @param  int    $item_id
     * @param  string $element_id
     * @param  string $key
     * @return bool
     */
    public function remove($item_id, $element_id, $key = null)
    {
        $items = $this->getItems();

        if (!empty($items)) {
            if (!empty($key)) {
                return $this->removeVariant($key);
            }

            return $this->removeItem($item_id, $element_id);
        }

        return false;
    }

    /**
     * Remove item from cart by id.
     * Item_id-variant or item_id for basic.
     * @param  int $item_id - Item_id
     * @param null $element_id
     * @return bool
     */
    public function removeItem($item_id, $element_id)
    {
        $items = $this->getItems();

        if (!empty($items)) {
            foreach ($items as $key => $item) {
                if ($item['item_id'] == $item_id && $item['element_id'] == $element_id) {
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

        if ($this->inCartVariant($key)) {
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
    }

    /**
     * Recount all basket
     * @return array
     */
    public function recount()
    {
        $order   = $this->newOrder();
        $session = $this->_getSession();

        // items
        $items    = $order->getTotalForItems(true);
        $itemsRes = array();
        foreach ($items as $key => $itemSumm) {
            $itemsRes['Price-' . $key] = $itemSumm->data();
        }

        // shipping
        $shippingRes = array();
        if (isset($session['shipping'])) {
            foreach ($session['shipping'] as $elemId => $shipping) {
                if ($elemId == '_current') {
                    continue;
                }
                $element = $order->getShippingElement($elemId);
                $element->bindData($shipping);
                $shippingRes['Price-' . $elemId] = $element->getRate()->data();
            }
        }

        // result
        $result = array(
            'CartTableRow'  => $itemsRes,
            'Shipping'      => $shippingRes,
            'TotalCount'    => $order->getTotalCount(),
            'TotalPrice'    => $order->getTotalForItems()->data(),
            'ShippingPrice' => $order->getShippingPrice(false)->data(),
            'Total'         => $order->getTotalSum()->data(),
        );

        return $result;
    }

    /**
     * @param $shipping
     */
    public function setShipping($shipping)
    {
        if (!isset($shipping['_shipping_id'])) {
            return;
        }

        $id = $shipping['_shipping_id'];

        $session = $this->_getSession();

        $session['shipping']['_current'] = $id;
        $session['shipping'][$id]        = $shipping;

        $this->_setSession('shipping', $session['shipping']);
    }

    /**
     * @return array
     */
    public function getShipping()
    {
        $session = $this->_getSession();

        if (isset($session['shipping']) && isset($session['shipping']['_current']) && isset($session['shipping']['_current'])) {
            $cur = $session['shipping']['_current'];
            if (isset($session['shipping'][$cur])) {
                return $session['shipping'][$cur];
            }
        }

        return array('_shipping_id' => $this->_config->get('default_shipping'));
    }

    /**
     * @return array
     */
    public function getShippingList()
    {
        $session = $this->_getSession();

        return $session->get('shipping', array());
    }

    /**
     * Check if item in cart.
     * @param  string $id - item_id.
     * @param  string $element_id
     * @return bool
     */
    public function inCart($id, $element_id)
    {
        $items = $this->getItems();

        foreach ($items as $item) {
            if ($item['item_id'] == $id && $item['element_id'] == $element_id) {
                return true;
            }
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
