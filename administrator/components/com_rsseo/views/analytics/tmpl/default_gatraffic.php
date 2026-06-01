<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
?>
<table cellpadding="0" cellspacing="0" width="100%">
	<tr style="text-align: center;">
		<td align="right" style="width:45%;">
			<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'imgsourceschart', 'style' => 'display:none;'), true); ?>
			<span id="gasourceschart"></span>
		</td>
		<td align="left"><div id="rss_pie" style="clear: both;"></div></td>
	</tr>
	<tr>
		<td colspan="2">
			<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'imgsources', 'style' => 'display:none;'), true); ?>
			<span id="gasources"></span>
		</td>
	</tr>
</table>