<?php
/**
 * JBZoo App is universal Joomla CCK, application for YooTheme Zoo component
 *
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
 * Class JBCartElementShippingEmsPost
 */
class JBCartElementShippingEmsPost extends JBCartElementShipping
{
    /**
     * Url to make request
     * @var string
     */
    protected $_url = 'http://emspost.ru/api/rest?';

    const EMSPOST_CURRENCY = 'RUB';

    /**
     * Class constructor
     *
     * @param App    $app
     * @param string $type
     * @param string $group
     */
    public function __construct($app, $type, $group)
    {
        parent::__construct($app, $type, $group);

        $this->registerCallback('ajaxGetPrice');
    }

    /**
     * @param  float       $sum
     * @param  string      $currency
     * @param  JBCartOrder $order
     *
     * @return float
     */
    public function modify($sum, $currency, JBCartOrder $order)
    {
        $rate = $this->getRate();

        return $sum + $rate;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function hasValue($params = array())
    {
        return TRUE;
    }

    /**
     * @param  array $params
     *
     * @return mixed|string
     */
    public function renderSubmission($params = array())
    {
        if ($layout = $this->getLayout('submission.php')) {
            return self::renderLayout($layout, array(
                'params' => $params
            ));
        }

        return FALSE;
    }

    /**
     * Validates the submitted element
     *
     * @param  $value
     * @param  $params
     *
     * @return array
     */
    public function validateSubmission($value, $params)
    {
        $params = $value->getArrayCopy();
        $params = $this->mergeParams($params);
        $price  = $this->_getPrice($params);

        if ($country = $this->app->country->isoToName(strtoupper($params['to']))) {
            $to = $country;
        }

        return array(
            'value'  => $price,
            'fields' => array(
                'recipient' => $this->app->validator->create('string')->clean($to)
            ),
            'params' => $params
        );
    }

    /**
     * @return string
     */
    public function getDefaultParams()
    {
        $params = array(
            'method' => 'ems.calculate',
            'from'   => $this->_getDefaultCity(),
            'to'     => '',
            'type'   => 'att',
            'weight' => $this->getBasketWeight(),
        );

        return $params;
    }

    /**
     * @return int
     */
    public function getRate()
    {
        return $this->get('value', 0);
    }

    /**
     * Get array of parameters to push it into(data-params)
     *
     * @param  boolean $encode - Encode array or no
     *
     * @return string|array
     */
    public function getWidgetParams($encode = TRUE)
    {
        $params = array(
            'getPriceUrl'    => $this->app->jbrouter->elementOrder($this->identifier, 'ajaxGetPrice'),
            'shippingfields' => implode(':', $this->config->get('shippingfields', array()))
        );

        return $encode ? json_encode($params) : $params;
    }

    /**
     * @param $to - Russian city
     */
    public function ajaxGetPrice($to)
    {
        $params = json_decode($to, TRUE);
        $price  = $this->_getPrice($params);

        $this->app->jbajax->send(array(
            'price' => $this->_jbmoney->toFormat($price)
        ));
    }

    /**
     * Decoding the result of API call
     *
     * @param $responseBody
     *
     * @return array
     */
    public function processingData($responseBody)
    {
        return json_decode($responseBody, TRUE);
    }

    /**
     * Change name/value to value/name
     *
     * @param $city
     *
     * @return mixed
     */
    public function convertCity($city)
    {
        $result   = $city;
        $cities   = $this->_getLocations('cities');
        $converse = array_flip($cities);

        if (array_key_exists($city, $cities)) {
            $result = $cities[$city];

        } else if (array_key_exists($city, $converse)) {
            $result = $converse[$city];
        }

        return $result;
    }

    /**
     * City location of the store
     *
     * @return string
     */
    protected function _getDefaultCity()
    {
        $city = parent::_getDefaultCity();
        $city = $this->convertCity($city);

        return $city;
    }

    /**
     * Make request and get price form service
     *
     * @param  array $params
     *
     * @return int
     */
    protected function _getPrice($params = array())
    {
        $price  = 0;
        $params = $this->mergeParams($params);
        $url    = $this->getServicePath($params);

        $result = $this->_callService($url);
        $result = $result['rsp'];

        if ($result['stat'] === 'ok') {
            $price += $result['price'];
            $price = $this->_jbmoney->convert(self::EMSPOST_CURRENCY, $this->currency(), $price);
        }

        return $price;
    }

    /**
     * @param  string $type - cities, regions, countries, russia
     *
     * @return string
     */
    protected function _getLocations($type = 'countries')
    {
        $result    = $this->_getDefaultValue();
        $request   = $this->_url . 'method=ems.get.locations&type=' . $type . '&plain=true';
        $locations = $this->_callService($request);
        $locations = $locations['rsp'];

        if ($locations['stat'] === 'ok') {
            foreach ($locations['locations'] as $location) {
                $key = $this->clean($location['value']);

                $result[$key] = $location['name'];
            }
        }

        return $result;
    }

}
