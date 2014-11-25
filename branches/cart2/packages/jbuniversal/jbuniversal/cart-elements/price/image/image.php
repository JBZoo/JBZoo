<?php
/**
 * JBZoo App is universal Joomla CCK, application for YooTheme Zoo component
 *
 * @package     jbzoo
 * @version     2.x Pro
 * @author      JBZoo App http://jbzoo.com
 * @copyright   Copyright (C) JBZoo.com,  All rights reserved.
 * @license     http://jbzoo.com/license-pro.php JBZoo Licence
 * @coder       Alexander Oganov <t_tapak@yahoo.com>
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Class JBCartElementPriceImage
 */
class JBCartElementPriceImage extends JBCartElementPrice
{
    /**
     * @return mixed|null|string
     */
    public function edit()
    {
        if ($layout = $this->getLayout('edit.php')) {
            return self::renderLayout($layout);
        }

        return null;
    }

    /**
     * @param array $params
     *
     * @return array|mixed|null|string
     */
    public function render($params = array())
    {
        $unique = $this->unique($params);

        if ($layout = $this->getLayout()) {
            return self::renderLayout($layout, array(
                'params'  => $params,
                'element' => $unique
            ));
        }

        return null;
    }

    /**
     * @param $params
     *
     * @return string
     */
    public function unique($params)
    {
        $image  = $params->get('image');
        $unique = $this->getJBPrice()->layout() . '_' . $this->getJBPrice()->getItem()->id;

        if (empty($image)) {
            return $unique;
        }

        return $unique . '_' . $image;
    }

    /**
     * @param $image
     * @param $params
     *
     * @return JSONData|string
     */
    public function getImage($image, $params = array())
    {
        if (empty($image)) {
            return $image;
        }

        $jbImage = $this->app->jbimage;
        if (is_array($image)) {
            $image = $image['value'];
        }

        if (empty($params)) {
            $params = $this->getRenderParams();
        }

        $width  = $params->get('width');
        $height = $params->get('height');

        $img = new stdClass();

        $url = $jbImage->getUrl($image);
        if ($width || $height) {
            $url = $jbImage->resize($image, $width, $height)->url;
        }

        $width_pop  = $params->get('width_popup');
        $height_pop = $params->get('height_popup');

        $img->image = $url;
        if ($width_pop || $height_pop) {
            $url = $jbImage->resize($image, $width_pop, $height_pop)->url;
        }
        $img->pop_up = $url;

        return !empty($img) ? $img : null;
    }

    /**
     * Get params for widget
     * @return array
     */
    public function interfaceParams()
    {
        $params = $this->getRenderParams();
        $path   = $this->getValue();

        return array(
            'related' => $this->unique($params),
            'image'   => $this->getImage($path, $params)
        );
    }

    /**
     * Returns data when variant changes
     * @return null
     */
    public function renderAjax()
    {
        $path = $this->getValue();

        return $this->getImage($path);
    }
    
}
