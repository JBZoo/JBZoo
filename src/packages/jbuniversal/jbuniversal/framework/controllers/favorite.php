<?php
/**
 * JBZoo Application
 *
 * This file is part of the JBZoo CCK package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Application
 * @license    GPL-2.0
 * @copyright  Copyright (C) JBZoo.com, All rights reserved.
 * @link       https://github.com/JBZoo/JBZoo
 * @author     Denis Smetannikov <denis@jbzoo.com>
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Class FavoriteJBUniversalController
 */
class FavoriteJBUniversalController extends JBUniversalController
{

    /**
     * Favorite list of curret user
     * @throws AppException
     */
    function favorite()
    {
        // init
        $this->app->jbdebug->mark('favorite::init');

        $this->app->jbdoc->noindex();

        $type   = $this->_jbrequest->get('type');
        $appId  = $this->_jbrequest->get('app_id');
        $itemId = $this->_jbrequest->get('Itemid');

        if (!$appId) {
            $appId = 0;
        }

        if (!JFactory::getUser()->id) {
            $this->app->jbnotify->notice(JText::_('JBZOO_FAVORITE_NOTAUTH_NOTICE'));
        }

        // get items
        $searchModel = JBModelFilter::model();
        $items       = $this->app->jbfavorite->getAllItems();

        $items        = $searchModel->getZooItemsByIds(array_keys($items));
        $this->items  = $items;
        $this->params = $this->_params;
        $this->appId  = $appId;
        $this->itemId = $itemId;

        if (!$this->template = $this->application->getTemplate()) {
            throw new AppException('No template selected');
        }

        // set renderer
        $this->renderer = $this->app->renderer->create('item')->addPath(
            array(
                $this->app->path->path('component.site:'),
                $this->template->getPath()
            )
        );

        $this->app->jbdebug->mark('favorite::renderInit');

        // display view
        $this->getView('favorite')->addTemplatePath($this->template->getPath())->setLayout('favorite')->display();

        $this->app->jbdebug->mark('favorite::display');
    }

    /**
     * Clear action
     */
    public function remove()
    {
        $itemId = (int)$this->_jbrequest->get('item_id');
        $item   = $this->app->table->item->get($itemId);

        $this->app->jbfavorite->toggleState($item);

        $this->app->jbajax->send();
    }

    /**
     * Remove all action
     */
    public function removeAll()
    {
        $this->app->jbfavorite->removeItems();
        $this->app->jbajax->send();
    }

}
