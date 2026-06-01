<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');
?>

<h1 class="rst-heading"><?php echo JText::sprintf('RST_KB_RESULTS_FOR', $this->escape($this->word)); ?></h1>

<?php
if (empty($this->items))
{
	?>
	<div class="alert alert-info">
		<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?php echo JText::_('INFO'); ?></span>
		<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
	</div>
	<?php
}
else
{
?>
	<table class="table table-striped table-bordered table-hover">
	<?php
	if ($this->params->get('show_headings', 1))
	{
		?>
		<thead>
			<tr>
				<th nowrap="nowrap" style="width: 1%;"><?php echo JText::_('#'); ?></th>
				<th><?php echo JText::_('RST_KB_ARTICLE_NAME'); ?></th>
				<th><?php echo JText::_('RST_KB_CATEGORY_NAME'); ?></th>
			</tr>
		</thead>
		<?php
	}
	?>
	<tbody>
		<?php
		foreach ($this->items as $i => $item)
		{
			?>
			<tr>
			<?php if ($this->params->get('show_numbering', 1)) { ?>
				<td nowrap="nowrap" style="width: 1%;">
					<?php echo $this->pagination->getRowOffset($i); ?>
				</td>
			<? } ?>
				<td>
					<a href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=article&cid='.RSTicketsProHelper::KbSEF($item)); ?>">
						<?php echo ($this->show_thumbs && $item->thumb) != '' ? JHtml::_('image', 'components/com_rsticketspro/assets/thumbs/articles/' . $item->thumb, '', array('class' => 'rst-kb-article-thumb'), false) : ''; ?>
						<?php echo $item->name != '' ? $item->name : JText::_('RST_NO_TITLE'); ?>
					</a>
					<?php
					if ($this->isHot($item->hits))
					{
						?>
						<em class="rst-hot"><?php echo JText::_('RST_HOT'); ?></em>
						<?php
					}
					?>
				</td>
				<td>
					<?php
					if ($item->category_id)
					{
						$category = (object) array('id' => $item->category_id, 'name' => $item->category_name);
						?>
						<a href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=knowledgebase&cid='.RSTicketsProHelper::KbSEF($category)); ?>"><?php echo $this->escape($item->category_name); ?></a>
						<?php
					}
					else
					{
						echo JText::_('RST_KB_NO_CATEGORY');
					}
					?>
				</td>
			</tr>
			<?php
		}
		?>
	</tbody>
	</table>
	<?php
	if ($this->params->get('show_pagination', 1))
	{
		?>
		<div class="pagination<?php echo $this->escape($this->params->get('pageclass_sfx')); ?>">
			<?php echo $this->pagination->getPagesLinks(); ?>
		</div>
		<?php
	}
}