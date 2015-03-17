<?php
/**
 * JBZoo App is universal Joomla CCK, application for YooTheme Zoo component
 * @package     jbzoo
 * @version     2.x Pro
 * @author      JBZoo App http://jbzoo.com
 * @copyright   Copyright (C) JBZoo.com,  All rights reserved.
 * @license     http://jbzoo.com/license-pro.php JBZoo Licence
 * @coder       Denis Smetannikov <denis@jbzoo.com>
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Class JBCartElementShippingRussianpost
 */
class JBCartElementShippingRussianpost extends JBCartElementShipping
{
    const CURRENCY  = 'rub';
    const CACHE_TTL = 1440;

    /**
     * @var string
     */
    public $_url = 'http://www.russianpost.ru/autotarif/Autotarif.aspx';


    protected $_currency = 'rub';

    /**
     * Validates the submitted element
     * @param  $value
     * @param  $params
     * @return array
     */
    public function validateSubmission($value, $params)
    {
        $this->bindData($value);
        $value->set('rate', $this->getRate()->data(true));

        return $value;
    }

    /**
     * @return int
     */
    public function getRate()
    {
        $resp = $this->app->jbhttp->request($this->_url, array(
            'countryCode'  => '643', // Russian code
            'viewPost'     => $this->get('viewPost', 23),
            'typePost'     => $this->get('typePost', 1),
            'postOfficeId' => $this->get('postOfficeId'),
            'weight'       => $this->_order->getTotalWeight() * 1000, // weight in gramm
            'value1'       => ceil($this->_order->getTotalForItems()->plain()),
        ), array(
            'cache'     => true,
            'cache_ttl' => self::CACHE_TTL,
        ));

        if ($resp) {
            preg_match('/<span id="TarifValue">([0-9\,\-]+)<\/span>/i', $resp, $result);
            if (isset($result[1])) {
                $summ = $this->_order->val($result[1], self::CURRENCY);
                return $summ;
            }
        }

        return $this->_order->val(0, self::CURRENCY);
    }

    /**
     * @return array
     */
    protected function _getViewpostList()
    {
        return array(
            ''   => '-&nbsp;' . JText::_('JBZOO_ELEMENT_SHIPPING_RUSSIANPOST_TYPE') . '&nbsp;-',
            '23' => JText::_('JBZOO_ELEMENT_SHIPPING_RUSSIANPOST_PARCEL'),
            '18' => JText::_('JBZOO_ELEMENT_SHIPPING_RUSSIANPOST_CARD'),
            '13' => JText::_('JBZOO_ELEMENT_SHIPPING_RUSSIANPOST_LETTER'),
            '26' => JText::_('JBZOO_ELEMENT_SHIPPING_RUSSIANPOST_RICH_PARCEL'),
            '36' => JText::_('JBZOO_ELEMENT_SHIPPING_RUSSIANPOST_RICH_PACKAGE'),
            '16' => JText::_('JBZOO_ELEMENT_SHIPPING_RUSSIANPOST_RICH_LETTER')
        );
    }

    /**
     * @return array
     */
    protected function _getTypePostList()
    {
        return array(
            ''  => '-&nbsp;' . JText::_('JBZOO_ELEMENT_SHIPPING_RUSSIANPOST_TYPE') . '&nbsp;-',
            '1' => JText::_('JBZOO_ELEMENT_SHIPPING_RUSSIANPOST_GROUND'),
            '2' => JText::_('JBZOO_ELEMENT_SHIPPING_RUSSIANPOST_AIR'),
            '3' => JText::_('JBZOO_ELEMENT_SHIPPING_RUSSIANPOST_COMBINE'),
            '4' => JText::_('JBZOO_SHIPPING_RUSSIANPOST_FAST')
        );
    }

    /**
     * @return $this
     */
    public function loadAssets()
    {
        $this->app->jbassets->js('cart-elements:shipping/russianpost/assets/js/russianpost.js');
        $this->app->jbassets->chosen();
    }
}
