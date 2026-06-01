<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::script(Uri::root().'media/com_timelog/js/timelog.js');

$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');

$document = Factory::getDocument();
$document->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
$document->addStyleSheet('templates/shaper_helix3/css/custom.css');

$app          = Factory::getApplication()->input;
$appendUrl    = '';
$licenseId    = $app->getInt('licence_id', 0);
$licenseTitle = '';
$tmpl         = $app->getString('tmpl');
$state        = $app->getInt('state');

if (!empty($licenseId))
{
	$appendUrl = '&licence_id=' . $licenseId . '&state=' . $state;

	// Include SLA to find SLA Type
	JLoader::register('MultiagencyFrontendHelpers', JPATH_SITE . '/components/com_multiagency/helpers/multiagency.php');
	$multiAgencyHelper = new MultiagencyFrontendHelpers;
	$agencyInfo = $multiAgencyHelper->getMultiagencyByLicense($licenseId);

	if (!empty($agencyInfo))
	{
		$licenseTitle = $agencyInfo->title . ' (' . $agencyInfo->sla_title . ')';
	}
}

// DPE - Hack  - Start
JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
$params = DPE::config();
$dateFormat = (String) $params->get('dateFormat');
// DPE - Hack  - End
?>

<form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post"
	name="adminForm" id="adminForm">
	<div class="row ml-0 mr-0">
		<div class="modal-header mb-20">
			<!-- Show remove icon only on popup -->
			
			<h3 class="m-0"><?php
			if ($licenseTitle !='')
			{
				echo  ucfirst($this->escape($licenseTitle)) .' - ';
			}
			echo Text::_('COM_TIMELOG') ;
			?></h3>
			<?php if ($tmpl == 'component') { ?>
			<button type="button" class="close" onclick="timeLog.closePopup();">&times;</button>
			<?php } ?>
		</div>
	<div class="col-xs-12">
		<div id="filter-progress-bar" class="row">
			<div class="col-xs-12 col-sm-12 marginb10 timelog-activities-search">
				<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this,'options' => array('filtersHidden' => false)));?>
			</div>
			<div class="col-12 my-3 mb-2 text-end">
				<!-- Show add button only on popup -->
				<?php if ($this->canCreateTimelog && $tmpl == 'component') : ?>
					<a href="<?php echo Route::_('index.php?option=com_timelog&tmpl=component&task=dpeactivityform.edit&id=0'. $appendUrl ); ?>"
					   class="btn btn-success btn-small"><i
							class="icon-plus"></i>
						<?php echo Text::_('COM_TIMELOG_ADD_ITEM'); ?></a>
				<?php endif; ?>
             </div>
		</div>
	</div>
	<div class="col-xs-12">
<?php
if (!empty($this->items))
{
?>
	<table class="table table-striped" id="activityList">
		<thead>
				<tr>
				<th class=''>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TIMELOG_ACTIVITIES_CLIENT_ID', 'a.client_id', $listDirn, $listOrder); ?>
				</th>
				<th class=''>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TIMELOG_ACTIVITIES_LOG_DATE', 'a.created_date', $listDirn, $listOrder); ?>
				</th>
				<th class=''>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TIMELOG_ACTIVITIES_LOG_USER', 'created_by.name', $listDirn, $listOrder); ?>
				</th>
<!--
				<th class=''>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TIMELOG_ACTIVITIES_ACTIVITY_TYPE_ID', 'a.activity_type_id', $listDirn, $listOrder); ?>
				</th>
-->
				<th class=''>
				<?php echo Text::_('COM_TIMELOG_ACTIVITIES_ACTIVITY_NOTE'); ?>
				</th>
				<th class=''>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TIMELOG_ACTIVITIES_LOG_TIME', 'a.spent_time', $listDirn, $listOrder); ?>
				</th>

				<th class=''>
				<?php echo Text::_('COM_TIMELOG_ACTIVITIES_MEDIA'); ?>
				</th>

				<?php if ($this->canDelete): ?>
					<th class="center">
				<?php echo Text::_('COM_TIMELOG_ACTIVITIES_ACTIONS'); ?>
				</th>
				<?php endif; ?>

		</tr>
		</thead>
		<tbody>
		<?php foreach ($this->items as $i => $item) : ?>
			<tr class="row<?php echo $i % 2; ?>">
				<td>
					<?php if ($this->canCreateTimelog) : ?>
					<a href="<?php echo Route::_('index.php?option=com_timelog&tmpl=component&task=dpeactivityform.edit&id='.(int) $item->id . $appendUrl); ?>">
					<?php echo $this->escape($item->activity_title); ?></a>
					<?php else : ?>
						<?php echo $this->escape($item->activity_title); ?>
					<?php endif; ?>
				</td>
				<td>
					<?php echo HTMLHelper::_('date', $this->escape($item->created_date), $dateFormat, false);?>
				</td>
				<td>
					<?php echo $item->created_by; ?>
				</td>
<!--
				<td>
					<?php echo $this->escape($item->title); ?>
				</td>
-->
				<td>
					<?php echo (strlen($this->escape($item->activity_note)) > 100 ) ? substr($this->escape(strip_tags($item->activity_note)), 0, 100) . '...' : $this->escape(strip_tags($item->activity_note)); ?>
				</td>
				<td>
					<?php echo $item->spent_time; ?>
				</td>

				<td>
					<ul class="list-inline">
						<?php
						$i = 1;
						if (!empty($item->mediaFiles))
						{
							foreach ($item->mediaFiles as $attachment)
							{
								$downloadAttachmentLink = Uri::root() . 'index.php?option=com_timelog&task=activity.downloadAttachment&' .
								Session::getFormToken() . '=1' . '&mediaId=' . $attachment->media_id . '&activityId=' . $attachment->client_id;
							?>
								<li>
									<span><i class="icon-download" aria-hidden="true"></i></span>
									<a
										href="<?php echo Route::_($downloadAttachmentLink);?>"
										target=""
										title="<?php echo $this->escape(strip_tags($attachment->title));?>">
										<?php echo Text::sprintf('COM_TIMELOG_ACTIVITY_ATTACHMENT', $i);?>
									</a>
								</li>
							<?php
								$i++;
							}
						}?>
						</ul>
				</td>

				<?php if ($this->canDelete): ?>
					<td class="center">
							<a href="<?php echo Route::_('index.php?option=com_timelog&tmpl=component&task=dpeactivityform.remove&id=' . $item->id . $appendUrl); ?>" class="btn btn-mini delete-button" type="button"><i class="icon-trash" ></i></a>
					</td>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
		}
		else
		{
			?>
			<div class="clearfix">&nbsp;</div>
			<div class="alert alert-info"><?php echo Text::_("COM_TIMELOG_NO_RECORDS_FOUND");?></div>
			<?php
		}
		?>

	<div class="col-xs-12">
		<div class="pull-right">
			<?php  echo $this->pagination->getPagesLinks(); ?>
		</div>
	</div>


	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="boxchecked" value="0"/>
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
	<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</div>
</form>

<?php if($this->canDelete) : ?>
<script type="text/javascript">

	jQuery(document).ready(function () {
		jQuery('.delete-button').click(deleteItem);
	});

	function deleteItem() {

		if (!confirm("<?php echo Text::_('COM_TIMELOG_DELETE_MESSAGE'); ?>")) {
			return false;
		}
	}
</script>
<script>
jQuery(document).ready(function()
{
	/* It restrict the user for manual input in datepicker field */
	jQuery(document).delegate('.calendar-textfield-class', 'focusin', function(event) {
		event.preventDefault();
		jQuery(this).parent().siblings(':eq(0)').show();
	});

	jQuery(document).delegate('.calendar-textfield-class', 'keydown contextmenu', function() {
			return false;
	});
});
</script>
<?php endif; ?>
