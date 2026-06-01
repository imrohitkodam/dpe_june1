<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_latestitems_flexi
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');
?>

<h2 class="subTitle">
	<?php echo JText::_($pluginParams->get('plugintitle')); ?>
</h2>

<table class="jma_latestitems_flexi product-table">
	<tr>
		<td class="jma_latestitems_flexi_th">
			<?php // @echo JText::_('TITLE_LN_FLEXI'); ?>
		</td >

		<?php
		if ($pluginParams->get('show_author'))
		{
			?>
			<td class="jma_latestitems_flexi_th">
				<?php echo JText::_('AUTHOR_LN_FLEXI'); ?>
			</td>
			<?php
		}
		?>

		<?php
		if ($pluginParams->get('show_date'))
		{
			?>
			<td class="jma_latestitems_flexi_th">
				<?php echo JText::_('DATE_LN_FLEXI'); ?>
			</td>
			<?php
		}
		?>
	</tr>

	<?php
	$cat_array = array();

	foreach ($list as $row)
	{
		if (in_array($row->catid, $cat_array))
		{
			?>
			<tr>
				<td class="jma_latestitems_flexi_td_60">
					<a href="<?php echo $row->link;?>"
						title="<?php echo $row->title; ?>">
						<?php echo $row->title; ?>
					</a>
				</td>

				<?php
				if ($pluginParams->get('show_author'))
				{
					?>
					<td class="jma_latestitems_flexi_td_20">
						<?php echo $row->author; ?>
					</td>
					<?php
				}
				?>

				<?php
				if ($pluginParams->get('show_date'))
				{
					?>
					<td class="jma_latestitems_flexi_td_20">
						<?php echo JHtml::date($row->date, "Y-m-d"); ?>
					</td>
					<?php
				}
				?>
				</tr>

				<?php
				if ($pluginParams->get('show_introtext'))
				{
					?>
					<tr>
						<td colspan="3" class="jma_justify">
							<?php echo $row->intro; ?>
						</td>
					</tr>
					<?php
				}
		}
		else
		{
			array_push($cat_array, $row->catid);

			if ($pluginParams->get('show_category'))
			{
				?>
				<tr>
					<td class="jma_latestitems_flexi_th">
						<?php echo $row->category; ?>
					</td>
				</tr>

				<?php
			}
			?>

			<tr>
				<td class="jma_latestitems_flexi_td_60">
					<a href="<?php echo $row->link;?>"
						title="<?php echo $row->title; ?>">
						<?php echo $row->title; ?>
					</a>
				</td>

				<?php
				if ($pluginParams->get('show_author'))
				{
					?>
					<td class="jma_latestitems_flexi_td_20">
						<?php echo $row->author; ?>
					</td>
					<?php
				}

				if ($pluginParams->get('show_date'))
				{
					?>
					<td class="jma_latestitems_flexi_td_20">
						<?php echo JHtml::date($row->date, "Y-m-d"); ?>
					</td>
					<?php
				}
				?>
			</tr>

			<?php
			if ($pluginParams->get('show_introtext'))
			{
				?>
				<tr>
					<td colspan="3" class="jma_justify">
						<?php echo $row->intro; ?>
					</td>
				</tr>

				<?php
			}
		}
	}
	?>
</table>
