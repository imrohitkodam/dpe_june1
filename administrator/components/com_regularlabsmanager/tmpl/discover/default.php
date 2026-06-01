<?php
/**
 * @package         Regular Labs Extension Manager
 * @version         9.2.5
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Layout\LayoutHelper as JLayoutHelper;

if (JFactory::getApplication()->isClient('site'))
{
    die();
}
?>
<?php if (empty($this->items)) : ?>
    <div class="alert alert-danger">
        <?php echo JText::sprintf('JLIB_INSTALLER_ERROR_DOWNLOAD_SERVER_CONNECT', 'download.regularlabs.com'); ?>
    </div>
<?php else: ?>
    <form name="regularlabsmanagerForm" id="regularlabsmanagerForm"
          class="<?php echo ! empty($this->items->extensionmanager) ? 'has_extensionmanager' : ''; ?>">

        <?php echo JLayoutHelper::render('card.extensionmanager', ['items' => $this->items->extensionmanager]); ?>

        <div class="<?php echo ! empty($this->items->extensionmanager) ? 'disabled rl-cursor-not-allowed' : ''; ?>">
            <?php echo JLayoutHelper::render('card.no_access', ['items' => $this->items->no_access]); ?>
            <?php echo JLayoutHelper::render('card.broken', ['items' => $this->items->broken]); ?>
            <?php echo JLayoutHelper::render('card.updates_available', ['items' => $this->items->updates_available]); ?>
            <?php echo JLayoutHelper::render('card.installed', ['items' => $this->items->installed]); ?>
            <?php echo JLayoutHelper::render('card.not_installed', ['items' => $this->items->not_installed]); ?>
        </div>
        </div>
    </form>
<?php endif; ?>
