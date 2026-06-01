<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

?>

<?php foreach ($this->items as $i => $item) { ?>
	<tr>
		<td class="center small hidden-phone"><?php echo HTMLHelper::_('grid.id', $i, $item->session_id); ?></td>
		<td><?php echo HTMLHelper::_('date', $item->date, $this->config->global_dateformat); ?></td>
		<td><?php echo rsseoHelper::obfuscateIP($item->ip); ?></td>
		<td><a href="<?php echo Uri::root().rsseoHelper::getSef($item->page); ?>" target="_blank"><?php echo empty($item->page) ? Uri::root() : rsseoHelper::getSef($item->page); ?></a></td>
		<td><a href="javascript:void(0);" onclick="RSSeo.showModal('<?php echo Route::_('index.php?option=com_rsseo&view=statistics&layout=pageviews&tmpl=component&id='.$item->id,false); ?>')"><?php echo Text::_('COM_RSSEO_VIEW_PAGEVIEWS'); ?></a></td>
	</tr>
<?php } ?>