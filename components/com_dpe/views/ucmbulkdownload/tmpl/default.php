
<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2025 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\User\User;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Component\ComponentHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('bootstrap.renderModal');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('jquery.token');

$document = Factory::getDocument();
$document->addStylesheet('templates/shaper_helix3/css/custom.css');
$document->addScript(Uri::root() . 'media/system/js/messages.min.js');
$document->addScript(Uri::root() . 'media/com_dpe/js/tjucm.js');

$user      = Factory::getUser();
JLoader::import('ComtjlmsHelper', JPATH_SITE . '/components/com_tjlms/helpers');
$app              = Factory::getApplication();
$comtjlmsHelper   = new ComtjlmsHelper;
$ucmitemId           = $comtjlmsHelper->getitemid('index.php?option=com_dpe&view=ucmbulkdownload');
$submitReportLink = Route::_('index.php?option=com_dpe&view=ucmbulkdownload&Itemid=' . $ucmitemId, false);
$currentUrl = '/index.php?option=com_dpe&view=ucmbulkdownload&Itemid=' . $ucmitemId;
$dpeAdmin       = $user->authorise('core.manageall', 'com_cluster');
$menu = $app->getMenu();
$menuItem = $menu->getItem($ucmitemId);
$listOrder = $this->escape($this->state->get('list.ordering', 'id'));
$listDirn  = $this->escape($this->state->get('list.direction', 'DESC'));
$input = Factory::getApplication();
$clusterId = $input->input->get('cluster_id', '', 'INT');
$this->state->set('filter.cluster_id', $clusterId);
$base = Uri::root();

?>

<div id="system-message-container"></div>
<div class="container">
	<div class ="row">
	<form action="<?php echo Route::_('index.php?option=com_dpe&view=ucmbulkdownload');?>" method="post" name="adminForm" id="adminForm" class="">
		<!--Filter-->
		<div class="col-xs-12 dp-pagination-dropdown ticket-searchtool">
			<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this, 'options' => array('filterButton' => true, 'filtersHidden' => false)));?>
		</div>
		<div class="pull-right btn-add">
			<?php echo $this->getToolbar();?>
		</div>

		<div class="col-xs-12 table-responsive" id="no-more-tables">
			<table class="table table-striped table-bordered1 table-hover" id="rsticketsproList">
				<thead>

					<tr>
						<th width="1%" class="hidden-phone" style="<?php echo $displayToggel; ?>">
								<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
							</th>
						<th class="center">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPE_MY_TICKETS_ID', 'id', $listDirn, $listOrder);?>
						</th>
						<th class="center">
							<?php echo Text::_('COM_DPE_DOWNLOAD_BULK_REPORT_FILE_NAME');?>
						</th>
						<th class="center">
							<?php echo Text::_('COM_DPE_DOWNLOAD_BULK_REPORT_ORG_NAME');?>
						</th>
						<th class="center">
							<?php echo Text::_('COM_DPE_DOWNLOAD_BULK_REPORT_REQUEST_BY');?>
						</th>
						<th class="center">
							<?php echo Text::_('COM_DPE_DOWNLOAD_BULK_REPORT_REQUEST_DATE');?>
						</th>
						
						<th id="rst_head_delete" class="center" width="5%">
							<?php echo Text::_('COM_DPE_DOWNLOAD_BULK_REPORT_EXPIRYDATE'); ?>
						</th>
						<th id="rst_head_delete" class="center" width="5%">
							<?php echo Text::_('COM_DPE_DOWNLOAD_BULK_REPORT'); ?>
						</th>
						<th id="rst_head_delete" class="center" width="5%">
							<?php echo Text::_('COM_DPE_ACTION_ITEM'); ?>
						</th>	
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($this->items as $i => $item)
					{
						?>
						<tr class="row<?php echo $i % 2; ?>">
								<td>
									<input class="form-check-input valid form-control-success" autocomplete="off" type="checkbox" id="cb" name="cid[]" value="<?php echo $item->id;?>" o   nclick="Joomla.isChecked(this.checked);" aria-invalid="false">

									
								</td>




							<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_ID').$item->id; ?>">
								<?php echo (int) $item->id; ?>
							</td>
							<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_NAME'); ?>">
								<?php echo html_entity_decode($item->name_of_the_file, ENT_QUOTES, 'UTF-8'); ?>
							</td>
							<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_CLUSTER_IDS'); ?>">
								<?php echo htmlspecialchars($item->cluster_names, ENT_QUOTES, 'UTF-8'); ?>
							</td>
							<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_CLUSTER_IDS'); ?>">
								<?php echo htmlspecialchars($item->user_id, ENT_QUOTES, 'UTF-8'); ?>
							</td>
							<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_CREATED_DATE'); ?>">
								<?php echo Factory::getDate($item->created_at)->format('d-m-Y'); ?>
							</td>
							
							<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_DATE_RANGE'); ?>">
								<?php echo Factory::getDate($item->expiry)->format('d-m-Y'); ?>
							</td>
							
							<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_CREATED_DATE'); ?>">
								<a class="btn btn-info btn-small actionbutton"href="#" onclick="downloadUcmZip('<?php echo $item->download_url;?>')"style="background: #22b8f0; border: none; width: max-content;">
									<i class="fa fa-download" aria-hidden="true"></i>
									<?php echo Text::_('COM_DPE_DOWNLOAD_BULK_REPORT'); ?></a>
								</td>
								<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_CREATED_DATE'); ?>">
									<a class="btn btn-info actionbutton" href="javascript:void(0);" onclick="deleteBulkReport('<?php echo $item->id; ?>');"
										style="background: #22b8f0; border: none; width: max-content;">
										<i class="fa fa-trash" aria-hidden="true"></i>
										<?php echo Text::_('COM_DPE_DOWNLOAD_BULK_REPORT_DELETE');?>
									</a>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			<?php  if (count($this->items)==0)
					{
						?>
						<h4 style="background: #d3d3d3bf;padding: 12px;color: black;">
							<?php echo Text::_('COM_DPE_BULK_FILE_NO_FILE_PRESENT');?>
						</h4>
					
					<?php }?>
				<input type="hidden" name="task" value=""/>
				<input type="hidden" name="boxchecked" value="0"/>
				<input type="hidden" name="url" id="url" value="<?php echo $currentUrl;?>"/>
				<input type="hidden" name="Itemid" value="<?php echo $ucmitemId; ?>" />

				<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
				<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
				<?php echo HTMLHelper::_('form.token');?>

				<?php if ($this->pagination && $this->pagination->pagesTotal > 1) : ?>
					<div class="pager">
						<?php echo $this->pagination->getPagesLinks(); ?>
					</div>
				<?php endif; ?>


			</form>
		</div>
</div>

<script type="text/javascript">
	
	jQuery(document).ready(function()
	{
		jQuery('#dropdown-div').hide();
	})
	jQuery(document).ready(function ($) {
    var spColumnSelector = '#sp-title .sp-column';
    var $spColumn = $(spColumnSelector);
    var currentContent = $spColumn.html().trim(); // remove whitespace

    if (currentContent) {
        // Store the full HTML if not empty
        localStorage.setItem('spPageTitle', currentContent);
    } else {
        // It's empty, try restoring from localStorage
        var savedContent = localStorage.getItem('spPageTitle');
        if (savedContent) {
            $spColumn.html(savedContent);
            console.log('Restored page title from localStorage.');
        }

        //Do any additional actions here if needed
    }

    jQuery('.js-stools-container-filters').show();
});


</script>



