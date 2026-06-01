<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_groups_js
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');
?>

<h2 class="subTitle">
	<?php echo JText::_($pluginParams->get('plugintitle'));?>
</h2>

<table class="jma_groupjs product-table">
	<tr>
		<th class = "jma_groupjs_th"><?php echo JText::_("PLG_JMA_G_TITLE"); ?></th>
		<th class = "jma_groupjs_th"><?php echo JText::_("PLG_JMA_G_MEMBER_COUNT"); ?></th>
		<th class = "jma_groupjs_th"><?php echo JText::_("PLG_JMA_G_CREATED_DATE"); ?></th>
	</tr>

	<?php
	foreach ($list as $row)
	{
		?>
		<tr>
			<td>
				<a href="<?php echo $row->link; ?>"
					title="<?php echo $row->title; ?>">
					<?php echo $row->title; ?>
				</a>
			</td>
			<td><?php echo $row->membercount; ?> </td>
			<td><?php echo $row->date; ?> </td>
		</tr>
		<?php
	}
	?>
</table>
