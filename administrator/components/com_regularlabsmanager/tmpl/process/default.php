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

$extensions = JFactory::getApplication()->getSession()->get('rlem-results', []);

$failed  = [];
$success = [];

foreach ($extensions as $item)
{
    if ($item->has_error)
    {
        $failed[] = $item;
        continue;
    }

    $success[] = $item;
}
?>
<h2>
    <?php echo JText::_('RLEM_OVERVIEW'); ?>
</h2>

<?php if (count($failed)): ?>

    <div class="card mb-4 border-2 border-danger">

        <h3 class="card-header bg-danger text-white rounded-0 align-items-center">
            <span class="icon-warning text-white me-2" aria-hidden="true"></span>
            <?php echo JText::_('RLEM_FAILED'); ?>
        </h3>

        <div class="card-body rl-bg-danger-light">
            <?php echo JLayoutHelper::render('button.retry'); ?>

            <?php foreach ($failed as $item): ?>
                <?php echo JLayoutHelper::render('card.result', compact('item')); ?>
            <?php endforeach; ?>
        </div>

    </div>
<?php endif; ?>

<?php foreach ($success as $item): ?>
    <?php echo JLayoutHelper::render('card.result', compact('item')); ?>
<?php endforeach; ?>
