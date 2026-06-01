<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

if ($this->show_thumbs && ($this->category->thumb || $this->parent_category->thumb)) {
	$category_thumb = '<span class="rst-kb-category-thumb">' . JHtml::_('image', 'components/com_rsticketspro/assets/thumbs/small/' . ($this->category->thumb ? $this->category->thumb : $this->parent_category->thumb), $this->category->name, array(), false) . '</span>';
} else {
	$category_thumb = '';
}

if (!empty($this->category->name))
{
	?>
	<h1 class="rst-kb-heading"><?php echo $category_thumb . $this->escape($this->category->name); ?></h1>
	<?php
}
else
{
	?>
	<h1 class="rst-kb-heading"><?php echo $category_thumb . $this->escape($this->params->get('page_heading', $this->params->get('page_title'))); ?></h1>
	<?php
}

if ($this->params->def('show_description', 1) && $this->category->description)
{
	?>
	<div class="rst-kb-lead-description"><?php echo $this->category->description; ?></div>
	<?php
}

if (count($this->categories))
{
	if (!$this->cid && $this->params->get('split_to_tabs', 0)) {
		$kb_tabs	= new RsticketsproAdapterNavpills('rst-kb-tabs');
		$top_cats	= array();
		
		foreach ($this->categories as $i => $cat) {
			$top_cats[]	= $this->model->getCategories(array('inherited' => false, 'id' => $cat->id));
			$kb_tabs->addTitle($cat->name, 'rst_kb_' . preg_replace('/[^a-zA-Z0-9]+/', '', strtolower($cat->name)));
		}
		
		if (count($top_cats)) {
			foreach ($top_cats as $top_cat) {
				$parts		= array_chunk($top_cat, 3);
				$kb_rows	= '';
				
				if (count($parts)) {
					foreach ($parts as $part) {
						$kb_rows .= '<div class="rst-kb-row ' . RsticketsproAdapterGrid::row() . '">';
					
						foreach ($part as $category) {
							$this->category = $category;
							$kb_rows .= $this->loadTemplate('card');
						}
						$kb_rows .= '</div>';
					}
					$kb_tabs->addContent($kb_rows);
				} else {
					$kb_tabs->addContent('<div class="alert alert-info"><span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only">' . JText::_('INFO') . '</span> ' . JText::_('RST_NO_KB_SUBCATEGORIES') . '</div>');
				}
			}
			$kb_tabs->render();
		} else {
		?>
		<div class="alert alert-info">
			<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?php echo JText::_('RST_NO_KB_CATEGORIES'); ?>
		</div>
		<?php
		}
	
	} else {
		$parts = array_chunk($this->categories, 3);
		foreach ($parts as $part) {
		?>
		<div class="rst-kb-row <?php echo RsticketsproAdapterGrid::row(); ?>">
		<?php	
		foreach ($part as $category) {
			$this->category = $category;
			echo $this->loadTemplate('card');
		}
		?>
		</div>
	<?php
		}
	}
}
?>

<div class="rst-kb-section<?php echo ' ' . $this->category_class; ?>-filtering">
	<form action="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=knowledgebase'.($this->cid ? '&cid='.RSTicketsProHelper::KbSEF($this->category) : '')); ?>" method="post" name="adminForm" id="adminForm">
		<?php
		if ($this->params->get('filter', 1) || $this->is_filter_active || $this->params->get('show_pagination_limit', 1))
		{
			?>
			<fieldset class="com-rsticketspro-kb-filter">
				<?php
				if ($this->params->get('filter', 1) || $this->is_filter_active)
				{
					?>
					<div class="btn-group pull-left float-left">
						<label class="filter-search-lbl element-invisible" for="filter-search">
							<?php echo JText::_('RST_FILTER').'&#160;'; ?>
						</label>
						<input type="text" class="form-control" name="search" id="filter-search" value="<?php echo $this->escape($this->filter_word); ?>" title="<?php echo JText::_('RST_FILTER'); ?>" placeholder="<?php echo JText::_('RST_FILTER'); ?>" />
						<button type="submit" class="btn btn-primary"><?php echo JText::_('RST_SEARCH'); ?></button>
						<?php
						if (strlen($this->filter_word))
						{
							?>
							<button type="button" class="btn btn-danger" onclick="document.getElementById('filter-search').value=''; this.form.submit();"><?php echo JText::_('RST_CLEAR'); ?></button>
							<?php
						}
						?>
					</div>
					<?php
				}

				if ($this->params->get('show_pagination_limit', 1))
				{
					?>
					<div class="btn-group pull-right float-right">
						<label for="limit" class="element-invisible">
							<?php echo JText::_('JGLOBAL_DISPLAY_NUM'); ?>
						</label>
						<?php echo $this->pagination->getLimitBox(); ?>
					</div>
					<?php
				}
				?>
				<div class="clearfix"></div>
			</fieldset>
			<?php
		}
		?>

		<?php
		if ($this->params->get('tag_sorting', 0) && $this->cat_tag_sorting) {
			$cat_id	= (!$this->cid && $this->params->get('split_to_tabs', 0))  ? $this->params->get('top_category_id', 0) : $this->cid;
			$tags	= RSTicketsProHelper::getTagsList($cat_id);
			
			if (count($tags)) {
		?>
		<div class="rst-kb-tag-sorting">
			<input type="hidden" name="tag" id="filter-tag" value="<?php echo $this->escape($this->filter_tag); ?>" title="<?php echo JText::_('RST_FILTER'); ?>" />
			<ul class="nav nav-pills">
				<li class="nav-item">
					<button class="nav-link<?php echo !$this->filter_tag ? ' active' : ''; ?>" onclick="document.getElementById('filter-tag').value=''; this.form.submit();"><?php echo JText::_('RST_FILTER_ALL'); ?></button>
				</li>
				
				<?php
				foreach ($tags as $tag) {
					$tag_lang = preg_replace('/[^a-zA-Z0-9\s-]/', '', $tag->tag);
					$tag_lang = str_replace(array(' ', '-'), '_', $tag_lang);
					$tag_lang = 'RST_TAG_' . strtoupper($tag_lang);
				?>
				<li class="nav-item">
					<button class="nav-link<?php echo ($this->filter_tag == $tag->tag) ? ' active' : ''; ?>" onclick="document.getElementById('filter-tag').value='<?php echo $tag->tag; ?>'; this.form.submit();"><?php echo (JFactory::getLanguage()->hasKey($tag_lang) ? JText::_($tag_lang) : $tag->tag); ?></button>
				</li>
				<?php } ?>
			</ul>
		</div>
		<?php
			}
		}
		?>

		<?php
		if (count($this->items)) {
			if ($this->category_layout == 'list') {
		?>
		<table class="table table-striped table-bordered table-hover">
			<?php
			if ($this->params->get('show_headings', 1))
			{
				?>
				<thead>
					<tr>
						<th nowrap="nowrap" style="width: 1%;"><?php echo JText::_('#'); ?></th>
						<th><?php echo JHtml::_('grid.sort', 'RST_KB_ARTICLE_NAME', 'name', $this->sortOrder, $this->sortColumn); ?></th>
						<?php
						if ($this->params->get('show_hits', 0))
						{
							?>
							<th nowrap="nowrap" style="width: 1%;"><?php echo JHtml::_('grid.sort', 'RST_KB_ARTICLE_HITS', 'hits', $this->sortOrder, $this->sortColumn); ?></th>
							<?php
						}
						?>
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
				<?php } ?>
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
					<?php
					if ($this->params->get('show_hits', 0))
					{
						?>
						<td nowrap="nowrap" style="width: 1%;">
							<span class="badge badge-info"><?php echo $item->hits; ?></span>
						</td>
						<?php
					}
					?>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php
		} else {
			$items_rows = array_chunk($this->items, $this->category_columns);
		?>
		<div class="rst-kb-column-results">
		<?php foreach ($items_rows as $items_row) { ?>
			
			<div class="<?php echo RsticketsproAdapterGrid::row(); ?>">
			<?php foreach ($items_row as $item) { ?>
				<div class="<?php echo RsticketsproAdapterGrid::column(12 / $this->category_columns); ?>">
					<div class="rst-kb-article-item">
						<a href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=article&cid='.RSTicketsProHelper::KbSEF($item)); ?>">
							<?php echo ($this->show_thumbs && $item->thumb) != '' ? JHtml::_('image', 'components/com_rsticketspro/assets/thumbs/articles/' . $item->thumb, '', array('class' => 'rst-kb-item-thumb ' . (version_compare(JVERSION, '4.0', '>=') ? 'img-' : '') . 'thumbnail'), false) : ''; ?>
							<h4 class="rst-kb-item-title"><?php echo $item->name != '' ? $item->name : JText::_('RST_NO_TITLE'); ?></h4>
						</a>
					</div>
				</div>
			<?php } ?>
			</div>
		<?php
			}
		?>
		</div>
		<?php } ?>
		<?php
			if ($this->params->get('show_pagination', 1))
			{
				?>
				<div class="pagination<?php echo $this->escape($this->params->get('pageclass_sfx')); ?>">
					<?php echo $this->pagination->getPagesLinks(); ?>
				</div>
				<?php
			}
		} else {
		?>
		<div class="alert alert-info">
			<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?php echo JText::_('INFO'); ?></span>
			<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
		<?php } ?>

		<?php echo JHtml::_('form.token'); ?>
		<input type="hidden" name="option" value="com_rsticketspro" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="cid" value="<?php echo $this->cid; ?>" />
		<input type="hidden" name="filter_order" value="<?php echo $this->sortColumn; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $this->sortOrder; ?>" />
	</form>
</div>