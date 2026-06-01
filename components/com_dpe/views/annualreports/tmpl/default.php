
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
$user      = Factory::getUser();
$listOrder = $this->escape($this->state->get('list.ordering', 'a.id'));
$listDirn  = $this->escape($this->state->get('list.direction', 'DESC'));
$dpeAdmin       = $user->authorise('core.manageall', 'com_cluster');
?>
<div id="system-message-container"></div>

<div class ="row">
	<form action="<?php echo Route::_('index.php?option=com_dpe&view=annualreports');?>" method="post" name="adminForm" id="adminForm" class="">
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
						<th class="center">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPE_MY_TICKETS_ID', 'o.id', $listDirn, $listOrder);?>
						</th>
						<th class="center">
							<?php echo Text::_('COM_MULTIAGENCY_ORGANISATION');?>
						</th>
						<th class="center">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPE_ANNUAL_REPORT_CREAE_DATE', 'o.start_date', $listDirn, $listOrder);?>
						</th>
						<th class="center">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPE_ANNUAL_REPORT_REQUESTED_BY', 'o.created_by', $listDirn, $listOrder);?>
						</th>
						<th class="center">
						<?php echo Text::_('COM_DPE_ANNUAL_REPORT_DATE_RANGE');?>

						</th>
						
						<th id="rst_head_delete" class="center" width="5%">
							<?php echo Text::_('COM_DPE_REPORT_STATUS'); ?>
						</th>
						<th id="rst_head_delete" class="center" width="15%">
							<?php echo Text::_('COM_DPE_ACTION_ITEM'); ?>
						</th>	
					</tr>
				</thead>
				<tbody>
					<?php
					if (count($this->items)==0)
						{
							?>
							<tr class=""><td>
							<?php echo Text::_('COM_DPE_NO_MATCHING_RESULTS');?>
						</td></tr>
						<?php }
		
					foreach ($this->items as $i => $item)
					{
						?>
						<tr class="row<?php echo $i % 2; ?>">
							<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_ID'); ?>">
								<?php echo (int) $item->id; ?>
							</td>
							<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_CLUSTER_IDS'); ?>">
								<?php echo htmlspecialchars($item->cluster_ids, ENT_QUOTES, 'UTF-8'); ?>
							</td>
							<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_CREATED_DATE'); ?>">
								<?php echo Factory::getDate($item->created_date)->format('d-m-Y'); ?>
							</td>
							<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_NAME'); ?>">
								<?php echo htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8'); ?>
							</td>
							<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_DATE_RANGE'); ?>">
								<?php echo Factory::getDate($item->start_date)->format('d-m-Y') . ' - ' . Factory::getDate($item->end_date)->format('d-m-Y'); ?>
							</td>
							
							<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_CREATED_DATE'); ?>">
								<?php echo str_replace('_', ' ', htmlspecialchars($item->report_status, ENT_QUOTES, 'UTF-8'))  ; ?>
							</td>
							<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_CREATED_DATE'); ?>">
								<a class="btn btn-info actionbutton" href="<?php echo Route::_('index.php?option=com_dpe&view=annualreport'.'?id='.$item->id);?>" 
									     style="background: #22b8f0; border: none; width: max-content; display: inline-block; margin-right: 5px;">

									<i class="fas fa-edit"></i>
									<?php echo ($dpeAdmin)?Text::_('COM_DPE_DOCUMENT_VIEW_EDIT'):Text::_('COM_DPE_DOCUMENT_EDIT');?>
								</a>
								<a class="btn btn-info actionbutton" href="javascript:void(0);" onclick="deleteAnnualReport('<?php echo $item->id; ?>');"
     								style="background: #22b8f0; border: none; width: max-content; display: inline-block;">
										<i class="fa fa-trash" aria-hidden="true"></i>
										<?php echo Text::_('COM_DPE_ANNUAL_REPORT_DELETE');?>
									</a>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>

			<input type="hidden" name="task" value=""/>
			<input type="hidden" name="boxchecked" value="0"/>
			<input type="hidden" name="url" id="url" value="<?php echo $currentUrl;?>"/>

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

<script type="text/javascript">
	jQuery(document).ready(function ($) {
    var spColumnSelector = '#sp-title .sp-column';
    var $spColumn = $(spColumnSelector);
    var currentContent = $spColumn.html().trim(); // remove whitespace

    if (currentContent) {
        // Store the full HTML if not empty
        localStorage.setItem('spPageTitleannual', currentContent);
    } else {
        // It's empty, try restoring from localStorage
        var savedContent = localStorage.getItem('spPageTitleannual');
        if (savedContent) {
            $spColumn.html(savedContent);
            console.log('Restored page title from localStorage.');
        }

        //Do any additional actions here if needed
    }
});


function deleteAnnualReport(id) {
    if (!id || id.length < 1) {
        return false;
    }

    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=annualreports.deleteAnnualReport",
        type: 'POST',
        data: { 'id': id },
        headers: { 'X-CSRF-Token': Joomla.getOptions('csrf.token', '') },
        success: function (response) {
            try {
				
				var response = JSON.parse(response); // Not needed – response is already an object
                if (response.success) {
                    messageDisplay(response.data.msg, 'success');
                    jQuery('table tr').each(function () {
                        let row = jQuery(this);
                        let firstTd = row.find('td').first();
                        if (firstTd.text().trim() === id) {
                            row.remove(); // Remove the matching row
                        }
                    });
                } else {
                    messageDisplay(response.message || 'Something went wrong', 'error');
                }
            } catch (e) {
                console.error('Error handling response:', e);
                messageDisplay('Unexpected response from server.', 'error');
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error:', error);
            messageDisplay('Failed to delete report. Server error.', 'error');
        }
    });

    return true;
}

function messageDisplay(msg, type){
    jQuery('<div id="system-message-container"></div>').appendTo('#system-message-container');
    Joomla.renderMessages({[type] : [msg]}); 
    jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
    setTimeout(function() {
     jQuery('joomla-alert').fadeOut('slow', function() {
        $(this).remove();
    });
 }, 10000); 
}
</script>




