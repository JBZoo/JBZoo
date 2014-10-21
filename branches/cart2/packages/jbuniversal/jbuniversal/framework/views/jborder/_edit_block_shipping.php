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

$jbmoney = $this->app->jbmoney;

$statusList = $this->app->jbcartstatus->getList(JBCart::STATUS_SHIPPING, true);
$element    = $order->getShipping();
if ($element) {
    $curStatus = $element->getStatus();
}

?>
<div class="uk-panel uk-panel-box">

    <h3 class="uk-panel-title">
        <?php echo JText::_('JBZOO_ORDER_SHIPPING_TITLE'); ?>
    </h3>

    <?php echo $this->shipRender->renderAdminEdit(array('order' => $order)); ?>

    <?php if ($element) : ?>
        <dl class="uk-description-list-horizontal">
            <dt>Цена доставки</dt>
            <dd>
                <p><?php echo $jbmoney->toFormat($element->getRate(), 'EUR'); ?></p>
            </dd>

            <dt>Статус</dt>
            <dd>
                <p><?php echo $this->app->jbhtml->select($statusList, 'order[shipping][status]', '', $curStatus); ?></p>
            </dd>
        </dl>
    <?php endif; ?>

    <?php echo $this->shipFieldsRender->renderAdminEdit(array('order' => $order)); ?>

</div>