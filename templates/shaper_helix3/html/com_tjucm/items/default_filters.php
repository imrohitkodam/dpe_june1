<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;


$tmpListColumn = $this->listcolumn;
reset($tmpListColumn);
$firstListColumn = key($tmpListColumn);
$user = Factory::getUser();

	$params              = ComponentHelper::getParams('com_dpe');
	$sarrRquestStatus = json_decode($params->get('sarrequestStatus'), true);
	$foiRquestStatus = json_decode($params->get('foirequestStatus'), true);
	$breachRquestStatus = json_decode($params->get('breachStatus'), true);

	Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
	$sarfieldTable = Table::getInstance('field', 'TjfieldsTable');
	$sarfieldTable->load(array('id'=>$sarrRquestStatus));

	$foifieldTable = Table::getInstance('field', 'TjfieldsTable');
	$foifieldTable->load(array('id'=>$foiRquestStatus));

	$breachfieldTable = Table::getInstance('field', 'TjfieldsTable');
	$breachfieldTable->load(array('id'=>$breachRquestStatus));


$sarrRquestStatusName = $sarfieldTable->name;
$foiRquestStatusName = $foifieldTable->name;
$breachRquestStatusName = $breachfieldTable->name;
Text::script('COM_TJUCM_DELETE_MESSAGE');
Text::script('COM_TJUCM_NO_ITEM_SELECTED');
 $document = Factory::getDocument();
 $document->addScript(Uri::root() . 'media/com_dpe/js/genericprocess.min.js');

 $mainframe         = Factory::getApplication();
 $menu              = $mainframe->getMenu();
 $jobTittleMenuItem = $menu->getItems('link', 'index.php?option=com_multiagency&view=users', true);
 $backLink          = Route::_('index.php?option=com_multiagency&view=users&filter[agencies]=&Itemid=' . $jobTittleMenuItem->id, false, 0);
 $isClient          = ($this->client == 'com_tjucm.role') ? '1' : '0';
 
 // Check if the current user client com_tjucm.role then execute the code
 if ($isClient) :
 $input        = Factory::getApplication()->input;
 $filters      = $input->get('filter', [], 'ARRAY');
 $filterAgencies = $filters['agencies'] ?? ''; // Shorter ternary syntax
 $filterTags   = $filters['tags'] ?? [];

// Get Joomla session and input
$session = Factory::getSession();

 $clusterID = $input->get('cluster') ?? ''; // Shorter ternary syntax
//  $filterTags   = $filters['tags'] ?? [];

if($clusterID !=''){
	$session->set('selectedCluster', $clusterID);

}

 if($filterAgencies != ''){
		// Store new cluster ID in session
		$session->set('selectedClusterInJobTittle', $filterAgencies);
 }else{
	$filterAgencies=$session->get('selectedClusterInJobTittle'); // Get Cluster ID
 }
 
 $url = 'index.php?option=com_multiagency&view=users&filter[agencies]=' . urlencode($filterAgencies);
 
 // Append tags dynamically if present
 if (!empty($filterTags) && is_array($filterTags)) {
	 foreach ($filterTags as $index => $tag) {
		 $url .= '&filter[tags][' . $index . ']=' . urlencode($tag);
	 }
 }
 
 $backLink = Route::_($url . '&Itemid=' . $jobTittleMenuItem->id);
 ?>
 <!-- Display the back button text -->
<div>
	 <a class="fs-16 font-600 cursor-pointer" href="<?php echo $backLink; ?>">
		 <i class="fa fa-arrow-left mr-10" aria-hidden="true"></i>
		 <?php echo Text::_('COM_DPE_BACK_BUTTON'); ?>
	 </a>
</div>
 <?php endif; ?>
 
<div id="filter-progress-bar" class="row">
	<div class="col-lg-8 col-12 <?php echo $isClient ? 'mt-4':'';?>">
		<div class="btn-group pull-left mb-2 me-2">
			<div class="pull-left">
				<input type="text" name="filter_search" id="filter_search" class="rounded-0"
				title="<?php echo empty($firstListColumn) ? Text::_('JSEARCH_FILTER') :
				Text::sprintf('COM_TJUCM_ITEMS_SEARCH_TITLE', $this->listcolumn[$firstListColumn]->label); ?>"
				value="<?php echo $this->escape($this->state->get($this->client . '.filter.search')); ?>"
				placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>"/>
			</div>
			<div class="pull-left btn-group">
				<button class="btn btn-primary rounded-0" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><span class="icon-search"></span></button>
				<button class="btn btn-default qtc-hasTooltip rounded-0" style="background-color: #efefef;" id="clear-search-button" onclick="document.getElementById('filter_search').value='';this.form.submit();" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><?php echo Text::_('JSEARCH_FILTER_CLEAR');?></button>
			</div>
		</div>

		<?php
		$db = Factory::getDbo();

		// Check if com_cluster component is installed
		if (ComponentHelper::getComponent('com_cluster', true)->enabled)
		{
			JLoader::import('components.com_tjfields.tables.field', JPATH_ADMINISTRATOR);
			$fieldTable = Table::getInstance('Field', 'TjfieldsTable', array('dbo', $db));
			$fieldTable->load(array('client' => $this->client, 'type' => 'cluster'));

			if ($fieldTable->id)
			{
				FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
				$cluster           = FormHelper::loadFieldType('cluster', false);
				$this->clusterList = $cluster->getOptionsExternally();

				?>
				<div class="btn-group mr-10 md-w-200px pull-left mb-2">
					<?php
						echo HTMLHelper::_('select.genericlist', $this->clusterList, "cluster", 'class="input-medium" size="1" onchange="this.form.submit();"', "value", "text",$this->state->get($this->client . '.filter.cluster_id', '', 'INT'));
					?>
				</div>
				<?php
			}
		}

		// Get the item category filter
		JLoader::import('components.com_tjfields.tables.field', JPATH_ADMINISTRATOR);
		$fieldTable = Table::getInstance('Field', 'TjfieldsTable', array('dbo', $db));
		$fieldTable->load(array('client' => $this->client, 'type' => 'itemcategory', 'state' => '1'));

		if ($fieldTable->id)
		{
			$selectCategory = new stdClass;
			$selectCategory->value = '';
			$selectCategory->text = Text::_("COM_TJUCM_FILTER_SELECT_CATEGORY_LABEL");

			$categoryOptions = HTMLHelper::_('category.options', $this->client, $config = array('filter.published' => array(1)));
			$categoryOptions = array_merge(array($selectCategory), $categoryOptions);
			?>
			<div class="btn-group pull-right hidden-xs">
				<?php
				echo HTMLHelper::_(
					'select.genericlist', $categoryOptions, "itemcategory", 'class="input-medium"
					size="1" onchange="this.form.submit();"', "value", "text",
					$this->state->get($this->client . '.filter.category_id', '', 'INT')
				);
				?>
			</div>
			<?php
		}?>
		<!--filter for calculations-->
		<?php 
		$numericoptions = array();
		foreach ($this->listcolumn as $colorconfigValue) 
		{ 
			$numericRanges = json_decode(json_decode($colorconfigValue->params)->colorcombination);
			$isFilterable  = $colorconfigValue->filterable;
			($isFilterable && $colorconfigValue->type =='numericcalculation') ? $canFilter = 1 : '';

			if ($numericRanges && ($isFilterable == '1'))
			{
				foreach($numericRanges as $key => $numericRange)
				{	
					$numericoptions[$key] = array('value'=>$numericRange->min .'-'. $numericRange->max,'text'=>$numericRange->value);
				}
				array_unshift( $numericoptions,array('value'=>'0','text'=> Text::sprintf('COM_TJUCM_SELECT_NUMERICCALCULATIO_FILTER', $colorconfigValue->label)));
			}	
		}

		if ($canFilter)
			{  ?>
				<div class="btn-group pull-left mr-10 mb-5 md-w-200px">
					<fieldset id="filter-bar">
						<div class="filter-select">
							<?php
							echo HTMLHelper::_('select.genericlist', $numericoptions, "numeirccalculation", 'class="input-medium" size="1" onchange="this.form.submit();"', "value", "text", $this->state->get($this->client . '.filter.numeirccalculation', '', 'STRING'));
							?>
						</div>
					</fieldset>
				</div>
				<?php
			}
			?>
			<!--calculation filter end-->

<?php		// Load filter fields
JLoader::import('components.com_tjfields.models.options', JPATH_ADMINISTRATOR);
JLoader::import('components.com_tjfields.models.fields', JPATH_ADMINISTRATOR);
$fieldsModel = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
$fieldsModel->setState('filter.client', $this->client);
$fieldsModel->setState('filter.filterable', 1);
$fields = $fieldsModel->getItems();

foreach ($fields as $field)
{


	$options = array();

	if ($field->type != "assignee")
	{
		$tjFieldsOptionsModel = BaseDatabaseModel::getInstance('Options', 'TjfieldsModel', array('ignore_request' => true));
		$tjFieldsOptionsModel->setState('filter.field_id', $field->id);
		$options = $tjFieldsOptionsModel->getItems();
	}
	else
	{
				// Load options dynamically for assignee type field filter

		FormHelper::addFieldPath(JPATH_SITE . '/components/com_dpe/models/fields/');
		$assignee = FormHelper::loadFieldType('assigneeFilter', false);
		$options  = $assignee->getOptionsExternally();
	}

	if (!empty($options))
	{
		$defaultOption = new stdclass;
		$defaultOption->value = "";
		$defaultOption->options = Text::_("JSELECT") . ' ' . ucfirst($field->label);

		$options = array_merge(array($defaultOption), $options);
		?>
		<div class="btn-group pull-left pe-2 mb-2 md-w-200px">
			<?php


	if($field->name == $sarrRquestStatusName || $field->name == $foiRquestStatusName || $field->name == $breachRquestStatusName )	{
	echo HTMLHelper::_(
				'select.genericlist', $options, $field->name.'[]', 'class="input-medium multistatus" multiple="multiple" 
				size="1" onchange="this.form.submit();"', "value", "options",
				$this->state->get('filter.field.' . $field->name.'[]')
			);

	}
	else
	{
			echo HTMLHelper::_(
				'select.genericlist', $options, $field->name, 'class="input-medium"
				size="1" onchange="this.form.submit();"', "value", "options",
				$this->state->get('filter.field.' . $field->name)
			);
	}
			?>
		</div>
		<?php
	}
}

				$params     			  = ComponentHelper::getParams('com_multiagency');
				$multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group'); 
				$orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');

// Hide the Tags filter for non-Super Admin users
if ($user->authorise('core.manageall', 'com_cluster') || $user->authorise('core.admin'))
{
	FormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');
	$dpeTags = FormHelper::loadFieldType('Dpetags', false);
	$dpeTag  = $dpeTags->getOptions();
	JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
	$dpeModel = DPE::model('school', array('ignore_request' => true));

	// Show the  respected tags in the filters 
	if (in_array($multiagencyTrusteeRoleId, $user->groups))
	{
		
		$tags = $dpeModel->getAgencyTags($multiagencyTrusteeRoleId); 
		$dpeTag = $tags;
	}
	elseif (in_array($orgAdminRoleId, $user->groups)) {
		
		$tags = $dpeModel->getAgencyTags($orgAdminRoleId); 
		$dpeTag = $tags;
	}
	?>
	<div class="btn-group pull-left pr-10 mb-3 md-w-200px">
		<fieldset id="filter-bar">
			<div class="filter-select fltrt">
				<select name="filter_tags[]" id = "tags" data-placeholder="<?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?>" class="inputbox" multiple="multiple" onchange="this.form.submit()">
					<option value=""><?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?> </option>
					<?php echo HTMLHelper::_('select.options', $dpeTag, 'value', 'text', $this->state->get('filter.tags'));?>
				</select>
			</div>
		</fieldset>
		</div><?php } ?>
		

	</div>
	<div class="col-lg-4 col-12 <?php echo $isClient ? 'mt-4':'';?>">
		<div class="btn-group pull-right pl-10 hidden-xs">
			<?php echo $this->pagination->getLimitBox(); ?>
		</div>
		<div class="pull-right pl-10 mb-10 additem">
					<a class="btn btn-primary btn-small"
						href="javascript:void(0);"
						onclick="tjucm.itmes.deleteMultipleItems(this);"
						id="ucm-delete"
						data-target-form="adminForm"
						title="<?php echo TEXT::_('COM_TJUCM_RECORD_DELETE_BTN_TEXT_TITLE');?>" >
						<i class="fa fa-trash"></i><?php echo ' ' . TEXT::_('COM_TJUCM_RECORD_DELETE_BTN_TEXT')?>
					</a>
				</div>
		<div class="pull-right pl-10 mb-10 additem">

			<?php
			// This can be removed later
			$tjUcmFrontendHelper = new TjucmHelpersTjucm;
			$itemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=itemform&client=' . $this->client);
			$masterUcmLink = Route::_('index.php?option=com_tjucm&view=itemform&Itemid=' . $itemId, false);

			if ($user->authorise('core.manageall', 'com_cluster') && $this->client == 'com_tjucm.role')
				{
					$ModalitemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=items&client=' . $this->client);
					$modalUrl = Route::_("index.php?option=com_tjucm&view=items&layout=jobtitle_modal&tmpl=component&Itemid=" . $ModalitemId,false);
					?>
					<a href="javascript:void(0);" 
					onclick="openJobTitleModal('<?php echo $modalUrl; ?>')" 
					class="btn btn-primary btn-small">
					<i class="icon-plus"></i><?php echo Text::_('COM_DPE_ADD_JOB_TITLE_BUTTON'); ?>
					</a>

				<?php
				}
			if ($this->allowedToAdd)
				{?>
					<a href="<?php echo $masterUcmLink ; ?>" class="btn btn-primary btn-small">
						<i class="icon-plus"></i><?php echo Text::_('COM_TJUCM_ADD_ITEM'); ?>
					</a>
					<?php
				}
				?>
			</div>

			<div class="pull-right pl-10 mb-10">
				<?php
				if ($this->canImport)
					{?>
						<a href="#" id="import-items" class="btn btn-primary btn-small">
							<i class="fa fa-upload"></i> <?php echo Text::_('COM_TJUCM_IMPORT_ITEM'); ?>
						</a>
						<?php
					}
					if ($user->authorise('core.manageall', 'com_cluster') || $orgAdminRoleId)
					{   $dpeAdmin = $user->authorise('core.manageall', 'com_cluster');
					?>
					<a href="#" id="export-items" onclick="exportItems('<?php echo $dpeAdmin;?>');"class="btn btn-primary btn-small">
							<i class="fa fa-download"></i> <?php echo Text::_('COM_TJUCM_EXPORT_ITEM_PDF'); ?>
						</a>
					<?php }?>
				</div>
			<div id="jobtitle-modal-wrapper"></div>
				
			</div>
		</div>
		<script>
			//DPE HACK 
			jQuery(document).ready(function(){
			var ischecked= jQuery('#recordProcessCheck').is(':checked');
			if(!ischecked)
			{
				          	    jQuery('.page-link').each(function () {
                var originalHref = jQuery(this).attr('href');
                var clusterId = jQuery('#cluster').val();
                if (originalHref) {
                    // Choose correct query parameter separator
                    var separator = originalHref.includes('?') ? '&' : '?';
                    // Avoid duplicating parameters
                        var updatedHref = originalHref + separator + 'cluster='+clusterId;
                        jQuery(this).attr('href', updatedHref);
                }
            });
			}
          })

			//DPE HACK
			jQuery(document).ready(function() {
				jQuery('#tags').change(function(e) {

			// check tag list is empty or not and set tag filter 
			var selectData = jQuery("#tags").chosen().val();       
			if ((selectData == null) || (selectData == ""))
			{	
				jQuery("#tags").val(jQuery("#tags option:first").val());
			}
		}); 

			// check dpeadmin or not 
			var isDpeAdmin = "<?php echo $user->authorise('core.manageall', 'com_cluster'); ?>";
			if (!isDpeAdmin)
			{
				jQuery('#filter_tags_chosen').hide();
			}

		// set filters according to the custer and tags 
		jQuery('#tags').on('change', function() {
			jQuery("#cluster").val(jQuery("#cluster option:first").val());
		});
		jQuery('#cluster').on('change', function() {	
			jQuery("#tags").val(jQuery("#tags option:first").val());
		});
	})

				jQuery(document).ready(function() {
				jQuery('.multistatus').change(function(e) {

			// check tag list is empty or not and set status filter 
			var selectData = jQuery(".multistatus").chosen().val(); 
			if ((selectData == null) || (selectData == ""))
			{	
				jQuery(".multistatus").val(jQuery(".multistatus option:first").val());
			}else
			{
				    jQuery(".multistatus option:first").remove();

			}
		}); 

	})
</script>
<script type="text/javascript">
	jQuery(document).ready(function(){

		if (!jQuery('#import-items').length )
		{
			jQuery('.dpecustomdelete').css('margin-left', '177px');
		}
	})
</script>