<?php
/**
 * @package     addTodo
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Date\Date;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('bootstrap.renderModal');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('jquery.token');

HTMLHelper::_('script', 'media/com_jlike/js/jlike.js');
HTMLHelper::script('plugins/system/dpeaddtodo/addtodoservice.js');
HTMLHelper::script('plugins/system/dpeaddtodo/addtodotmpl.js');
HTMLHelper::script('plugins/system/dpeaddtodo/addtodosubmit.js');
HTMLHelper::_('script', 'media/system/js/fields/calendar.min.js');
HTMLHelper::_('script', 'media/com_jlike/vendors/loader/js/loadingoverlay.min.js');
HTMLHelper::_('script', 'media/com_dpe/js/dpepreloader.js');
Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjreports/tables');

$app = Factory::getApplication();
$doc = Factory::getDocument();
// $doc->addScript(Uri::root() . 'media/system/js/mootools-core.js');
// $doc->addScript(Uri::root() . 'media/system/js/mootools-more.js');
$doc->getWebAssetManager()->useAsset('script', 'messages');
JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

Text::script('COM_JLIKE_DUE_DATE_VALIDATION_MESSAGE');
Text::script('PLG_ADDTODO_TITLE_VALIDATION_MESSAGE');

Text::script('PLG_CONTENT_JLIKE_MULTIAGENCY_FIELD_COURSE_EMPLOYEE_TODO');
Text::script('PLG_CONTENT_JLIKE_MULTIAGENCY_FIELD_ORGANISATION_NAME');
Text::script('PLG_CONTENT_JLIKE_MULTIAGENCY_FIELD_COURSE_NAME');
Text::script('plg_tjreports_addtodo_course_status');
$doc = Factory::getDocument();
$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');

// Check for Can view recommendatoin form
if (ComponentHelper::getComponent('com_subusers', true)->enabled)
{
	JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

	if (!$this->user->authorise('core.manageall', 'com_cluster'))
	{ 

		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters         = $clusterUserModel->getUsersClusters($this->user->id);	
		$canAccessTodoForm   = null;
		$staffAccessTodoForm = null;
		$adminOrgs           = array();
		$staffOrgs           = array();

		foreach ($clusters as $cluster)
		{
			if (!$canAccessTodoForm)
			{
				$canAccessTodoForm   = RBACL::check($this->user->id, 'com_cluster', 'core.manageNotificationManager', 'com_jlike', $cluster->cluster_id);
				$staffAccessTodoForm = RBACL::check($this->user->id, 'com_cluster', 'core.own.manageNotifications', 'com_jlike', $cluster->cluster_id);

				if ($canAccessTodoForm)
				{
					$adminOrgs[]  = $cluster->cluster_id;
				}

				if ($staffAccessTodoForm)
				{
					$staffOrgs[] = $cluster->cluster_id;
				}
			}
		}

		if (!$canAccessTodoForm && !$staffOrgs)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->setHeader('status', 403, true);

			return;
		}
	}
}

$tmpl       = $app->input->getString('tmpl', '');
$popupClass = '';
$columnClass = '';

// Check template component set or not.
if (!empty($tmpl))
{
	$doc = Factory::getDocument();
	$doc->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
	$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
	$popupClass = 'notification-add-form';
	$columnClass = 'col-sm-12';
	$cancelButton = "";
}
else
{
	$columnClass = 'col-sm-6';
	$cancelButton = "Joomla.submitbutton('recommendation.cancel')";
}

$urlData  = new Registry($this->item->params);
$pageLink = $urlData['current_page_link'];
?>
<div id="system-message-container"></div>
<div class="clearfix"></div>
<form action="" class="form-validate form-horizontal add-todos ucm-form-styling recommendation-form <?php echo htmlspecialchars($popupClass, ENT_QUOTES, 'UTF-8');?>"
    method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
	<div class="timelog-add-form activity-edit front-end-edit p-3 jlike-timelog">
	<?php if (!empty($popupClass))
	{
	?>
		<button type="button" data-refresh="" class="close closepopup">&times;</button>
	<?php
}
	?>
	<h2 class="activity-header fs-20 mb-20 pb-3">
		<?php
			echo Text::_('COM_JLIKE_ADD_RECOMMENDATION') 
		?>
	</h2>
</div>
	<div class="container-fluid">
		<div class="row d-flex flex-wrap">
			<div class="form-group" style="display:none;">
			<!-- 	<label class="col-sm-6 col-xs-12"><?php echo $this->form->getLabel('id'); ?></label>-->
				<!-- <div class="col-sm-6 col-xs-12"> <?php echo $this->form->getInput('id'); ?> </div> -->
			</div>
			<div class="col-sm-6">
				<div class="col-xs-12" id = "selectfilters">
				</div>
				<div class="col-xs-12" id = "filters">
				</div>
				<div class="col-xs-12 alluser">
				</div>
				<div class="col-xs-12">
					<?php
						echo $this->form->renderField('title');
					?>
				</div>
				<div class="col-xs-12">
					<?php
						echo $this->form->renderField('sender_msg');
					?>
				</div>
			
				<div class="col-xs-12">
					
					<div id="course_title" hidden="hidden">
					</div>
				</div>
			</div>
			<div class="col-sm-6">
				
				<div class="col-xs-12">
					<?php
						echo $this->form->renderField('due_date');
					?>
				</div>
				<div class="col-xs-12">
					<?php
						echo $this->form->renderField('reminder', null, null, ['class' => 'reminder-field']);
					?>
				</div>
			</div>
				<br>
				<div class="preloader-wrap" style= "display: none;">
					<div class="percentage" id="precent" ></div>
						<div class="newloader">
							 <div class="trackbar">
								<div class="loadbar"></div>
								</div>
							<div class="glow"></div>
						</div><br><br>
					<div class="getdata" style="margin-left: 42% !important;" id="getdata"><?php echo Text::_('COM_DPE_LOADER_SAVING_DATA'); ?></div>
				</div>
			<div class="control-group popup-action-btn mr-20">
				<div class="controls pull-right">

				
						<button  type="button" class="btn btn-primary addTodoReport"><?php echo Text::_('JSUBMIT'); ?></button>
					

					<button type="button " class="btn btn-default btn closepopup"  onclick="<?php echo htmlspecialchars($cancelButton, ENT_QUOTES, 'UTF-8');?>">
							<span><?php echo Text::_('JCANCEL'); ?></span>
					</button>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" id="assigned_user_id" value="<?php echo htmlspecialchars(($this->item)?$this->item->get('assigned_to'):'', ENT_QUOTES, 'UTF-8'); ?>" />
	<input type="hidden" name="jform[id]" id="id" value="0" />
	<input type="hidden" name="option" value="com_dpe"/>
	<input type="hidden" name="task" value="users.todoSave"/>
	<input type="hidden" name="jform[element]" id="element" value="com_jlike.generic_todo"/>
	<input type="hidden" name="jform[element_id]" id="element_id" value="1"/>
	<input type="hidden" name="jform[url]" id="url" value="<?php echo htmlspecialchars(Uri::getInstance()->toString(), ENT_QUOTES, 'UTF-8');?>"/>
	<input type="hidden" name="jform[content_id]" id="content_id" value="<?php echo htmlspecialchars(($this->item)?$this->item->get('content_id'):'', ENT_QUOTES, 'UTF-8'); ?>"/>
	<input type="hidden" name="jform[current_page_link]" id="current_page_link" value=""/>
	<input type='hidden'name="jform[course_id]", id="course_id" value=""/>
	<input type='hidden'name="jform[contentId]", id="contentId" value=""/>
	<input type='hidden'name="jform[clusters]", id="jform_clusters" value=""/>
	<input type='hidden' id="jform_all_cluster_users" name="jform[all_cluster_users]" value = "" >
	<input type='hidden' id="rootUrl" name="rootUrl" value = "<?php echo uri::root();?>" >
	<?php $duedate = new Date('now');?>
  <input type='hidden' id="duedate" name="duedate" value = "<?php echo $duedate->format(Text::_('DATE_FORMAT_CALENDAR_DATETIME'));?>" >
  <input type='hidden'name="jform[is_todo_specific]", id="is_todo_specific" value="1"/>


	<?php echo HTMLHelper::_('form.token'); ?>
</form>
<script type="text/javascript">

var todoId      = <?php echo json_encode($this->item->id); ?>;
var root_url = jQuery('#rootUrl').val();

var tmpl = <?php echo json_encode($tmpl);?>;

if (tmpl)
{
	addTodo.init();
}

jQuery('.addTodoReport').click(function(event){
 event.stopPropagation();

 if(jQuery('#jform_title').val() > 1)
 {
  	jQuery('.addTodoReport').prop('disabled',true);
 }

addTodo.addTodos(); 

})

</script>
<script>
		
jQuery(document).ready(function() {

  /*var inputElement = jQuery('#jform_due_date');

  var currentDate = new Date();

  var formattedDate = currentDate.getDate() + '-' + (currentDate.getMonth() + 1) + '-' + currentDate.getFullYear() + ' ' + currentDate.getHours() + ':' + currentDate.getMinutes();
  inputElement.val(formattedDate);
  
  jQuery('#jform_due_date_btn').click(function(){
	  setTimeout(function(){	var inputElement = jQuery('#jform_due_date');

  var currentDate = new Date();

  var formattedDate = currentDate.getDate() + '-' + (currentDate.getMonth() + 1) + '-' + currentDate.getFullYear() + ' ' + currentDate.getHours() + ':' + currentDate.getMinutes();

  inputElement.val(formattedDate);
},1000)

  

  })*/
 jQuery('#system-message-container').on("click", function() { 
                // Find the closest "system-message-container" div and clear its content
                jQuery("#system-message-container").empty();
            });
});



</script>
