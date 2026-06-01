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
	<tr>
		<td colspan="3">
			<div id="rss_visualization" style="text-align: center; clear: both;"></div><br />
		</td>
	</tr>
	<tr>
		<td>
			<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'imggeneral', 'style' => 'display:none;'), true); ?>
			<span id="gageneral"></span>
		</td>
		<td>&nbsp;</td>
		<td valign="top">
			<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'imgnewreturning', 'style' => 'display:none;'), true); ?>
			<span id="ganewreturning"></span>
		</td>
	</tr>
	<tr>
		<td colspan="3">
			<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'imgvisits', 'style' => 'display:none;'), true); ?>
			<span id="gavisits"></span>
		</td>
	</tr>
	<tr>
		<td colspan="3">
			<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'imggeocountry', 'style' => 'display:none;'), true); ?>
			<span id="gageocountry"></span>
		</td>
	</tr>
	<tr>
		<td>
			<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'imgbrowsers', 'style' => 'display:none;'), true); ?>
			<span id="gabrowsers"></span>
		</td>
		<td>&nbsp;</td>
		<td valign="top">
			<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'imgmobiles', 'style' => 'display:none;'), true); ?>
			<span id="gamobiles"></span>
		</td>
	</tr>
</table>