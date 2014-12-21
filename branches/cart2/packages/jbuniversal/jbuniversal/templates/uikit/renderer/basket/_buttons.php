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


$this->app->jbassets->less('jbassets:less/cart/buttons.less');
?>

<div class="jbcart-buttons clearfix">

    <input type="submit" name="create" value="<?php echo JText::_('JBZOO_CART_SUBMIT'); ?>"
           class="jbbutton green big" />

    <?php if ($view->payment) : ?>
        <input type="submit" name="create-pay" value="<?php echo JText::_('JBZOO_CART_SUBMIT_AND_PAY'); ?>"
               class="jbbutton green big" />
    <?php endif; ?>

</div>
