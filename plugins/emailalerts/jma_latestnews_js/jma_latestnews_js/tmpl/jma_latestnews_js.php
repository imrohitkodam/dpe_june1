<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_latestnews_js
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');
?>

<h2 class="subTitle" style="font-size: 18px; !important">
	<?php echo JText::_($pluginParams->get('plugintitle')); ?>
</h2>

<table class= "jma_latestnewsjs product-table">
	<tr>
		<!-- <td class="jma_latestnewsjs_th">
			<?php  //echo JText::_('TITLE_LN_JS'); ?>
		</td> -->

		<?php
		if ($pluginParams->get('show_author'))
		{
			?>
			<!-- <td class="jma_latestnewsjs_th">
				<?php //echo JText::_('JMA_LATESTNEWS_AUTHOR'); ?>
			</td> -->
			<?php
		}

		if ($pluginParams->get('show_date'))
		{
			?>
			<!-- <td class="jma_latestnewsjs_th date-ts">
				<?php //echo JText::_('JMA_LATESTNEWS_DATE'); ?>
			</td> -->
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
			<tr class="news-row">
				<td class="jma_latestnewsjs_td_60">
					<a href="<?php echo $row->link;?>"
						title="<?php echo $row->title; ?>" >
						<?php echo $row->title; ?>
					</a>
				</td>

				<?php
				if ($pluginParams->get('show_author'))
				{
					?>
					<td class="jma_latestnewsjs_td_20" >
						<?php echo $row->author; ?>
					</td>
					<?php
				}

				if ($pluginParams->get('show_date'))
				{
					?>
					<td class="jma_latestnewsjs_td_20">
						<?php echo JHtml::date($row->date, JText::_('JMA_LATESTNEWS_DATE_FORMAT')); ?>
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
						<br><br>
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
				<tr class="news-category-row">
					<td class="jma_latestnewsjs_th news-category" colspan="3"><?php echo $row->category; ?></td>
				</tr>
				<?php
			}
			?>

			<tr class="news-details-row">
				<td class="jma_latestnewsjs_td_60">
					<a href="<?php echo $row->link; ?>"
						title="<?php echo $row->title; ?>" >
						<?php echo $row->title; ?>
					</a>
				</td>

				<?php
				if ($pluginParams->get('show_author'))
				{
					?>
					<td class="jma_latestnewsjs_td_20">
						<?php echo $row->author; ?>
					</td>
					<?php
				}

				if ($pluginParams->get('show_date'))
				{
					?>
					<td class="jma_latestnewsjs_td_20">
						<?php echo JHtml::date($row->date, JText::_('JMA_LATESTNEWS_DATE_FORMAT')); ?>
					</td>
					<?php
				}
				?>
			</tr>

			<?php
			if ($pluginParams->get('show_introtext'))
			{
				?>
				<tr class="news-intro-text">
					<td colspan="3" class="jma_justify">
						<?php echo $row->intro; ?>
						<br><br>
					</td>
				</tr>
				<?php
			}
		}
	}
	?>
</table>
