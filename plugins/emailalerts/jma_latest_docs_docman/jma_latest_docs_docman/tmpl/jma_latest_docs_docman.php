<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_latest_docs_docman
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

<table class="jma_docs_dm product-table">
	<?php
	// Show table headings?
	if ($pluginParams->get('show_table_headings'))
	{
		?>
		<tr>
			<th class="jma_docs_dm_th">
				<?php echo JText::_("PLG_LATEST_DOCS_DOCMAN_DOC_TITLE"); ?>
			</th>

			<th class="jma_docs_dm_th">
				<?php echo JText::_("PLG_LATEST_DOCS_DOCMAN_LINKS"); ?>
			</th>

			<?php
			// Show date?
			if ($pluginParams->get('show_date'))
			{
				?>
				<th class="jma_docs_dm_th">
					<?php echo JText::_("PLG_LATEST_DOCS_DOCMAN_DATE"); ?>
				</th>
				<?php
			}
			?>
		</tr>
		<?php
	}

	$cat_array = array();

	foreach ($list as $row)
	{
		if (in_array($row->cat_id, $cat_array))
		{
			?>
			<tr>
				<td>
					<?php echo $row->title; ?>
				</td>

				<td>
					(
					<a href="<?php echo $row->link;?>"
						title="<?php echo $row->title; ?>">
						<?php echo JText::_('PLG_LATEST_DOCS_DOCMAN_VIEW'); ?>
					</a>
					|
					<a href="<?php echo $row->dwn_link;?>"
						title="<?php echo $row->title; ?>">
						<?php echo JText::_('PLG_LATEST_DOCS_DOCMAN_DOWNLOAD'); ?>
					</a>
					)
				</td>

				<?php
				// Show date?
				if ($pluginParams->get('show_date'))
				{
					?>
					<td>
						<?php
						// @echo JFactory::getDate($row->date)->format(JText::_('PLG_LATEST_DOCS_DOCMAN_DATE_FORMAT'));
						echo JHtml::date($row->date, JText::_('PLG_LATEST_DOCS_DOCMAN_DATE_FORMAT'));
						?>
					</td>
					<?php
				}
				?>
			</tr>
			<?php
		}
		else
		{
			array_push($cat_array, $row->cat_id);

			if ($pluginParams->get('show_category'))
			{
				?>
				<tr>
					<td class="jma_docs_dm_td">
						<?php echo $row->cat_title;?>
					</td>
				</tr>
				<?php
			}
			?>

			<tr>
				<td class="jma_docs_dm_title">
					<?php echo $row->title; ?>
				</td>

				<td class="jma_docs_dm_links">
					(
					<a href="<?php echo $row->link;?>"
						title="<?php echo $row->title; ?>">
						<?php echo JText::_('PLG_LATEST_DOCS_DOCMAN_VIEW'); ?>
					</a>
					|
					<a href="<?php echo $row->dwn_link;?>"
						title="<?php echo $row->title; ?>">
						<?php echo JText::_('PLG_LATEST_DOCS_DOCMAN_DOWNLOAD'); ?>
					</a>
					)
				</td>

				<?php
				// Show date?
				if ($pluginParams->get('show_date'))
				{
					?>
					<td>
						<?php
						// @echo JFactory::getDate($row->date)->format(JText::_('PLG_LATEST_DOCS_DOCMAN_DATE_FORMAT'));
						echo JHtml::date($row->date, JText::_('PLG_LATEST_DOCS_DOCMAN_DATE_FORMAT'));
						?>
					</td>
					<?php
				}
				?>
			</tr>
			<?php
		}
	}
	?>
</table>
