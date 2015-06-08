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


$align  = $this->app->jbitem->getMediaAlign($item, $layout);
$tabsId = $this->app->jbstring->getId('tabs');
?>

<div class="well clearfix">
    <?php if ($this->checkPosition('title')) : ?>
        <h1 class="item-title"><?php echo $this->renderPosition('title'); ?></h1>
    <?php endif; ?>

    <div class="row">
        <?php if ($this->checkPosition('image')) : ?>
            <div class="col-md-6">
                <div class="item-image">
                    <?php echo $this->renderPosition('image'); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($this->checkPosition('meta')) : ?>
            <div class="col-md-6">
                <div class="item-metadata">
                    <ul class="uk-list">
                        <?php echo $this->renderPosition('meta', array('style' => 'list')); ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="item-tabs">
    <ul id="<?php echo $tabsId; ?>" class="nav nav-tabs">
        <?php if ($this->checkPosition('text')) : ?>
            <li class="active">
                <a href="#item-desc" id="desc-tab" data-toggle="tab">
                    <?php echo JText::_('JBZOO_ITEM_TAB_DESCRIPTION'); ?>
                </a>
            </li>
        <?php endif; ?>

        <?php if ($this->checkPosition('properties')) : ?>
            <li>
                <a href="#item-prop" id="prop-tab" data-toggle="tab">
                    <?php echo JText::_('JBZOO_ITEM_TAB_PROPS'); ?>
                </a>
            </li>
        <?php endif; ?>

        <?php if ($this->checkPosition('gallery')) : ?>
            <li>
                <a href="#item-gallery" role="tab" id="gallery-tab" data-toggle="tab">
                    <?php echo JText::_('JBZOO_ITEM_TAB_GALLERY'); ?>
                </a>
            </li>
        <?php endif; ?>
    </ul>
    <div id="<?php echo $tabsId; ?>Content" class="tab-content">
        <?php if ($this->checkPosition('text')) : ?>
            <div class="tab-pane fade active in" id="item-desc">
                <div class="item-text">
                    <?php echo $this->renderPosition('text', array('style' => 'block')); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($this->checkPosition('properties')) : ?>
            <div class="tab-pane fade" id="item-prop">
                <ul class="list-properies">
                    <?php echo $this->renderPosition('properties', array('style' => 'list')); ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($this->checkPosition('gallery')) : ?>
            <div class="tab-pane fade" id="item-gallery">
                <?php echo $this->renderPosition('gallery'); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
