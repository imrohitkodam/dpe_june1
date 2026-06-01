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

use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Layout\LayoutHelper as JLayoutHelper;

extract($displayData);

/**
 * @var   object $item
 */

$text        = '';
$title       = JText::_('JVISIT_WEBSITE') . ': ' . $item->name;
$hidden_text = $title;
$icon        = 'out-2';
$class       = 'btn btn-sm btn-light rl-no-styling';
$url         = 'https://regularlabs.com/' . $item->alias;
?>
<?php echo JLayoutHelper::render('link', compact('text', 'hidden_text', 'title', 'icon', 'class', 'url')); ?>
