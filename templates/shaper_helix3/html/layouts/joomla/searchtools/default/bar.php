<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\Registry\Registry;


HTMLHelper::script('media/com_dpe/js/dpe.min.js');



$data = $displayData;

// Receive overridable options
$data['options'] = !empty($data['options']) ? $data['options'] : array();

if (is_array($data['options']))
{
	$data['options'] = new Registry($data['options']);
}

// Options
$filterButton = $data['options']->get('filterButton', true);
$searchButton = $data['options']->get('searchButton', true);

$filters = $data['view']->filterForm->getGroup('filter');
?>

<?php if (!empty($filters['filter_search'])) : ?>
	<?php if ($searchButton) : ?>
<!--
		<label for="filter_search" class="element-invisible">
			<?php if (isset($filters['filter_search']->label)) : ?>
				<?php echo Text::_($filters['filter_search']->label); ?>
			<?php else : ?>
				<?php echo Text::_('JSEARCH_FILTER'); ?>
			<?php endif; ?>
		</label>
-->
		<div class="btn-group me-2">
			<?php echo $filters['filter_search']->input; ?>
			<?php if ($filters['filter_search']->description) : ?>
				<?php JHtmlBootstrap::tooltip('#filter_search', array('title' => Text::_($filters['filter_search']->description))); ?>
			<?php endif; ?>
			<div class="btn-group float-none">
				<button type="submit" class="btn btn-primary hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', 'JSEARCH_FILTER_SUBMIT'); ?>" aria-label="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
					<span class="fa fa-search" aria-hidden="true"></span>
				</button>
				
			</div>	
		</div>
		
		<?php if ($filterButton) : ?>
			<div class="btn-group hidden-phone search-tool-filter">
				<button type="button" class="btn hasTooltip btn-primary js-stools-btn-filter" onclick="setNewTableHeight();" title="<?php echo HTMLHelper::_('tooltipText', 'JSEARCH_TOOLS_DESC'); ?>">
					<?php echo Text::_('JSEARCH_TOOLS');?> <span class="caret"></span>
				</button>
				<button type="button" class="btn hasTooltip js-stools-btn-clear" style="background-color: #efefef;" title="<?php echo HTMLHelper::_('tooltipText', 'JSEARCH_FILTER_CLEAR'); ?>">
					<?php echo Text::_('JSEARCH_FILTER_CLEAR');?>
				</button>
			</div>
		<?php endif; ?>
	<?php endif; ?>
<?php endif;
?>
<script>
	
	jQuery(document).ready(function() {
  // When the button is clicked
  jQuery(".js-stools-btn-filter").on("click", function() {
    // Check if the element with class js-stools-container-filters-visible is present
    if (jQuery(".js-stools-container-filters-visible").length > 0) {
      // Add your class to the desired element
      jQuery(".js-stools-btn-filter").addClass("btn-primary");
      jQuery('.js-stools-container-filters').css('display','');
    }
    else
    {
   		// jQuery(".js-stools-btn-filter").removeClass("btn-primary");	
   		 jQuery('.js-stools-container-filters').css('display','none');
    }
  });
});
</script>

<?php