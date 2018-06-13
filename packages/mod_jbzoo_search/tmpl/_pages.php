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
 */

// no direct access
defined('_JEXEC') or die('Restricted access');


$pagesHTML = $modHelper->renderPages();
?>

<?php if ((int)$params->get('pages_show', 1)) : ?>
    <div class="jbfilter-row jbfilter-limit">
        <label for="jbfilter-id-limit" class="jbfilter-label">
            <?php echo JText::_('JBZOO_PAGES'); ?>
        </label>

        <div class="jbfilter-element">
            <?php echo $pagesHTML; ?>
        </div>
        <?php echo JBZOO_CLR; ?>
    </div>
<?php else :
    echo $pagesHTML;
endif;
