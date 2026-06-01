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

use Joomla\CMS\Layout\LayoutHelper as JLayoutHelper;

extract($displayData);

/**
 * @var   object $item
 */
?>

<div class="card mb-4">
    <h4 class="card-header">
        <?php echo $item->name; ?>
    </h4>

    <div class="card-body">
        <?php echo JLayoutHelper::render('alerts', ['messages' => $item->messages]); ?>
    </div>
</div>
