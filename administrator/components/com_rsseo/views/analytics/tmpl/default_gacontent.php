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
		<td>
			<?php echo HTMLHelper::image('com_rsseo/loader.gif', '', array('id' => 'imgcontent', 'style' => 'display:none;'), true); ?>
			<span id="gacontent"></span>
		</td>
	</tr>
</table>