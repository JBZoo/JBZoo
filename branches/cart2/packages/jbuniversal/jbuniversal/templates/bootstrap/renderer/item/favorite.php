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


$jbtemplate = $this->app->zoo->getApplication()->jbtemplate;
$rowClass   = $jbtemplate->getRowClass();
?>

<?php if ($this->checkPosition('title')) : ?>
    <h2 class="item-title"><?php echo $this->renderPosition('title'); ?></h2>
<?php endif; ?>

<div class="<?php echo $rowClass; ?> last">
    <div class="<?php echo $jbtemplate->gridClass(4); ?>">
        <?php if ($this->checkPosition('image')) : ?>
            <div class="item-image">
                <?php echo $this->renderPosition('image'); ?>
            </div>
        <?php endif; ?>

        <?php if ($this->checkPosition('price')) : ?>
            <div class="item-price">
                <?php echo $this->renderPosition('price', array('style' => 'jbblock')); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="<?php echo $jbtemplate->gridClass(8); ?>">
        <?php if ($this->checkPosition('text')) : ?>
            <div class="item-text">
                <?php echo $this->renderPosition('text', array(
                    'labelTag' => 'h4',
                    'style'    => 'block',
                )); ?>
            </div>
        <?php endif; ?>

        <?php if ($this->checkPosition('properties')) : ?>
            <table class="table table-hover item-properties">
                <?php echo $this->renderPosition('properties', array('style' => 'jbtable')); ?>
            </table>
        <?php endif; ?>
    </div>
</div>