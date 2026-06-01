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

$prefix    = JText::_('RLEM_DOWNLOAD');
$extension = $item->name . ' v' . $item->version->version . ($item->version->is_pro ? 'PRO' : '');

$text        = '';
$title       = $prefix . ': ' . $extension;
$hidden_text = $title;
$icon        = 'download';
$class       = 'btn btn-sm btn-success rl-no-styling';
$url         = $item->downloadurl_pro ?: $item->downloadurl;
?>
<?php echo JLayoutHelper::render('link', compact('text', 'hidden_text', 'title', 'icon', 'class', 'url')); ?>
