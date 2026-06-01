<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.popover');

require_once JPATH_SITE . '/components/com_dpe/helpers/main.php';
require_once JPATH_SITE . '/components/com_jlike/models/contentform.php';
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);

$doc = Factory::getDocument();

// DPE hack
$doc->addStyleSheet(Uri::root() . 'media/system/css/modal.css');
// $doc->addScript(Uri::root() . 'media/system/js/mootools-core.js');
// $doc->addScript(Uri::root() . 'media/system/js/mootools-more.js');
$doc->addScript(Uri::root() . 'media/system/js/messages.min.js');
$doc->addScript(Uri::root() . 'media/system/js/modal.js');

jimport('joomla.html.pane');

$options['relative'] = true;
JHtml::stylesheet('com_tjlms/jlike.css', $options);

$jinput = Factory::getApplication()->input;
$jinput->set('tmpl', 'component');
$user = Factory::getUser();

$close = $jinput->get('close', '', 'INT');
$showusers = $jinput->get('showusers', 0, 'INT');

// Check elearning tool action for course lessons for DPE
if ($this->course_id)
{
	// Check elearning tool action for DPE
	if (!$this->olUser->authorise('core.manageall', 'com_cluster'))
	{
		JLoader::import('/components/com_subusers/includes/rbacl', JPATH_ADMINISTRATOR);

		if (!RBACL::check($this->olUser->id, 'com_cluster', 'core.viewShika', 'com_tjlms'))
		{
			$app = Factory::getApplication();

			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

			return;
		}
	}
}
// Code start for check the document is available only for assigned users, admin, document owner

$statusandscore = $this->tjlmsLessonHelper->getLessonScorebyAttemptsgrading($this->lesson->id, Factory::getUser()->id);
Factory::getDocument()->addScriptOptions('isDocumentCompleted', (!empty($statusandscore) && ($statusandscore->lesson_status == 'completed' || $statusandscore->lesson_status == 'passed') ? 1 : 0));

$lessonParams = array(
"element" => 'com_tjlms.lesson',
"element_id" => $this->lesson->id,
"url" => 'index.php?option=com_tjlms&view=lesson&lesson_id=' . $this->lesson->id,
"title" => $this->lesson->title
);
// Check the user is log in
if (!$this->user_id && !$user->guest)
{
	$lessonId = $jinput->get('lesson_id', 0, 'INT');
	$Itemid = $jinput->get('Itemid', 0, 'INT');

	if ($lessonId )
	{
		require_once JPATH_ROOT . '/libraries/techjoomla/common.php';
		$tjcommon = new TechjoomlaCommon();
		$app = Factory::getApplication();
		$lessonRedirectLink = 'index.php?option=com_dpe&view=myassignments';
		$menuItemId = $tjcommon->getItemId($lessonRedirectLink);
		$lessonURL = Uri::root().'index.php?option=com_tjlms&view=lesson&lesson_id=' . $lessonId . '&Itemid=' . $menuItemId;
		$app->redirect($lessonURL);
	}
	?>
		<div class="alert alert-danger">
			<span><?php echo Text::_('JERROR_ALERTNOAUTHOR');?></span>
		</div>
	<?php
	return;
}

// DPE - Hack - Start - To check the document is available only for assigned staff users

$superUser         = $this->olUser->authorise('core.admin');

$params = ComponentHelper::getParams('com_multiagency');
// $managerRole = $params->get('manager_role_id', '0', 'INT');
$memberRoleId = $params->get('member_role_id', '0', 'INT');
$schoolAdmin = $params->get('school_admin_role_id', '0', 'INT');
$dpeAdminRole = $params->get('multyagency_admin_role_id', '0', 'INT');
$dpeTrusteeRole = $params->get('organization_trustee_role_id', '0', 'INT');

// Is manager, dpeadmin and superuser
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

$result = RBACL::getRoleByUser($this->user_id, 'com_multiagency');
$assignedUsers = array();

// Allow this view to School Manger, Dpe Admin, School Admin and Superuser, and assigned staff members
if (!$superUser
	&& !in_array($dpeAdminRole, $result) && !$user->guest)
{
	$contentId = JlikeModelContentForm::getContentID($lessonParams);
	$dpeMainHelper = new DpeMainHelper;
	$assignedUsers = $dpeMainHelper->getAssignedUser((int) $contentId, $this->user_id);

	$clusterId     = $jinput->get('cluster_id', 0, 'INT');
	$canViewLesson = RBACL::check($this->olUser->id, 'com_cluster', 'core.manage.lessons', 'com_tjlms', $clusterId);

	// if (!$this->course_id && empty($assignedUsers) && !in_array($managerRole, $result)
	// && !in_array($schoolAdmin, $result) && !$canViewLesson)
	if (!$this->course_id && empty($assignedUsers) && !in_array($schoolAdmin, $result) && !$canViewLesson)
	{
		?>
		<div class="alert alert-danger">
			<span><?php echo Text::_('JERROR_ALERTNOAUTHOR');?></span>
		</div>
	<?php
		return;
	}
}

// DPE - Hack - End

// If invalid url, throw error
if ($this->inValidUrl == 1)
{
	?>
		<div class="alert alert-danger">
			<span><?php echo Text::_('COM_TJLMS_LESSON_INVALID_URL');?></span>
		</div>
	<?php
	return;
}

// Redirect to lesson list view if course not exist for selected lesson
if (!$this->course_id)
{ 
	$app = Factory::getApplication();
	$menu = $app->getMenu();
	$active = $menu->getActive();
	$lessonRedirectLink = 'index.php?option=com_dpe&view=myassignments';
	$menuItem = $menu->getItems( 'link', $lessonRedirectLink , true );

	$emailFlag = (empty($active)) ? true : false;

	if(!$active->id)
	{	
		$active = new stdClass; // PHP8.1 test
		$active->id = $app->input->get('Itemid', '0', 'INT');
	}

	// OR condition for :If staff user hit the link from email then user need to redirect on myassignments view


	if ($active->id == $menuItem->id || (in_array($memberRoleId, $result) || in_array($dpeTrusteeRole, $result)))
	{
		$this->returnUrl = $this->tjlmshelperObj->tjlmsRoute($lessonRedirectLink . '&Itemid=' . $menuItem->id,false);
	}
	else
	{
		$lessonRedirectLink = 'index.php?option=com_tjlms&view=managelessons';
		$menu = $app->getMenu();
		$menuItem = $menu->getItems( 'link', $lessonRedirectLink , true );
		$this->returnUrl = $this->tjlmshelperObj->tjlmsRoute($lessonRedirectLink . '&Itemid=' . $menuItem->id,false);
	}

	// DPE - Hack - Start - Check User Associated With Cluster or Not
	$plgSystemDpeTjlmsCluster = PluginHelper::getPlugin('system', 'dpe_tjlms_cluster');

	// Check user not manageall cluster permission & not a members of cluster
	if (!empty($plgSystemDpeTjlmsCluster) && !$this->olUser->authorise('core.manageall', 'com_cluster') && !$user->guest)
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
		$table = Table::getInstance('TjlmsClusterXref', 'DpeTable');
		$table->load(array('lesson_id' => $this->lesson->id));

		// Check logged-in user associated with passed cluster_id
		JLoader::import("/components/com_cluster/libraries/cluster", JPATH_ADMINISTRATOR);

		// Check logged-in user is a member of cluster
		$cluster = ClusterCluster::getInstance($table->cluster_id);

		$isValid = $cluster->isMember($this->olUser->id);

			// Check user cluster permission
			if (!$isValid || (!RBACL::check($this->olUser->id, 'com_cluster', 'core.manage.lessons', 'com_tjlms', $table->cluster_id) && empty($assignedUsers)))
			{
			?>
				<div class="alert alert-danger">
					<span><?php echo Text::_('JERROR_ALERTNOAUTHOR');?></span>
				</div>
			<?php
				return;
			}

			// DPE hack to access only active licence org assigned document
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters         = $clusterUserModel->getUsersClusters($user->id);

			foreach ($clusters as $cluster)
			{
				$clusterIds[] = $cluster->cluster_id;
			}

			// Check lesson cluster is available in user's active licence org array
			if (!in_array($table->cluster_id, $clusterIds))
			{
				?>
				<div class="alert alert-danger">
					<span><?php echo Text::_('JERROR_ALERTNOAUTHOR');?></span>
				</div>
			<?php
				return;
			}
		PluginHelper::importPlugin('content');
		$jLikeInteractions = Factory::getApplication()->triggerEvent('onGetLessonInteractions', array($this->lesson_id));
	}

	// DPE - Hack - End
}

// Get lesson data
$lesson_data = $this->lesson;

//Get the Cluster Id from lession Id.
JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_dpe/tables');
$tjFieldFieldTable = Table::getInstance('tjlmsclusterxref', 'DpeTable');
$data = $tjFieldFieldTable->load(array('lesson_id' => $lesson_data->id)); 
// If invalid url, throw error
if ($this->usercanAccess['access'] == 0)
{
	?>
		<div class="alert alert-danger">
			<span><?php echo $this->usercanAccess['msg'];	?></span>
			<span><?php echo Text::sprintf('COM_TJLMS_LESSON_CLICK_COURSE_LINK', $this->returnUrl);?></span>
		</div>
	<?php
	return;
}

$lesson_url = $this->tjlmshelperObj->tjlmsRoute("index.php?option=com_tjlms&view=lesson&lesson_id=" . $lesson_data->id . "&tmpl=component&lessonscreen=1",false);


if ($this->course_id)
{
	$lesson_url .= "&cid=" .$this->course_id;
}

$lesson_url = $this->tjlmshelperObj->tjlmsRoute($lesson_url ,false);

$params = ComponentHelper::getParams('com_tjlms');

// Jlike toolbar position
$show_toolbar_at_top = $params->get('tjlms_toolbar_option','1');

$toolbarClass = "fixed-top";


if ($show_toolbar_at_top == 0)
{
	$toolbarClass = "fixed-bottom";
}

$toolbar_content_class = 'lesson-right-panel';

Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jlike/tables');
$jlikeModelContent = Table::getInstance('content', 'JlikeTable', array());
$jlikeModelContent->load(array('element_id' => $this->lesson_id, 'element' => 'com_tjlms.lesson'));
$lessonInteractionFlag = (empty($jlikeModelContent->params)) ? false : true;

if(!$user->guest)
{
// Get jlike toolbar
$jlike_toolbar_file = $this->tjlmshelperObj->getViewpath('com_tjlms', 'lesson','jlike_toolbar');
ob_start();
include($jlike_toolbar_file);
$toolbar_html = ob_get_contents();
ob_end_clean();

// Get jlike toolbar content
$jlike_toolbar_content_file = $this->tjlmshelperObj->getViewpath('com_tjlms', 'lesson','jlike_toolbar_content');
ob_start();
include($jlike_toolbar_content_file);
$toolbar_content_html = ob_get_contents();
ob_end_clean();
}

if ($this->mode == 'preview') {
	
	HTMLHelper::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.css');
	HTMLHelper::_('bootstrap.framework');

}

?>

<!-- Container div-->
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?> com_tjlms_content tjBs3" id="<?php echo $this->mode;?>">
	<div class="container-fluid">
		<div class="row tjlms-lesson" data-js-attr="tjlms-lesson">

		<?php if($this->mode != 'preview' && !$user->guest): ?>

			<div class="<?php echo $toolbarClass;?>">
				<?php echo $toolbar_html; ?>
			</div>

		<?php elseif($this->mode == 'preview' && $close !== 0): ?>
			<div class="navbar-fixed-top" id="admin-close-button">
				<button type="button" class="close" onclick="tjLmsCommon.closePopup();"; data-dismiss="modal" aria-hidden="true"><i class="fa fa-close"></i></button>
			</div>
		<?php endif; ?>

		<?php $playList = 0 ; ?>

		<?php if ($this->showPlaylist == 1 && $this->mode != 'preview') : ?>
				<?php $playList = 1 ; ?>
		<?php endif; ?>

		<div class="tjlms-lesson__playlist-container hidden-xs col-sm-3 p-0 <?php echo (!$playList) ? 'hidden' : '';?>"  data-js-attr="lesson-playlist">

			<?php if ($playList): ?>

					<?php echo $this->loadTemplate('playlist'); ?>

			<?php endif; ?>

		</div>
		<div class="tjlms_lesson__player tjlms-lesson-player col-xs-12 <?php echo ($playList) ? 'col-sm-9' : 'col-sm-12';?>" data-js-attr="lesson-player">

		<?php if($this->askforinput	== 1): ?>

			<div id="resumeWindow" class="center text-center mt-10">
				<div class="well" id="askforattempt">

					<span class="help-block"><?php echo Text::_('COM_TJLMS_INCOMPLETE_LAST_ATTEMPT_MSG'); ?>
						<?php

						if($lesson_data->format!='scorm' && $lesson_data->format!='tjscorm' && $lesson_data->format!='textmedia' && $lesson_data->format!='externaltool')
						{
							$lang_constant_toshow	=	"COM_TJLMS_INCOMPLETE_LAST_ATTEMPT_STATUS_".$lesson_data->format;
							 echo Text::sprintf($lang_constant_toshow, $this->lastattempttracking_data->currentPositionFormat, $lesson_data->title, $this->lastattempttracking_data->totalContentFormat);
						}

						?>
					</span>

					<div class="clearfix"></div>

					<div class="container-fluid">
						<div class="row">
							<div class="col-xs-6 col-sm-6 text-right">
								<input type="button" name="new" value="<?php echo Text::_('COM_TJLMS_NEW_ATTEMPT') ?>" class="btn btn-default btn-medium" onclick="askforaction('start','<?php echo $lesson_data->id; ?>','<?php echo $lesson_url?>','<?php echo $this->attempt; ?>','<?php echo $lesson_data->format; ?>');">
							</div>
							<div class="col-xs-6 col-sm-6 text-left">
								<input type="button" id="old" name="old" value="<?php echo Text::_('COM_TJLMS_CONTINUE_OLD') ?>" class="btn btn-default btn-medium" onclick="askforaction('resume','<?php echo $lesson_data->id; ?>','<?php echo $lesson_url?>','<?php echo $this->attempt; ?>','<?php echo $lesson_data->format; ?>');">
							</div>
						</div>
					</div>
				</div><!--askforattempt ENDS-->
			</div><!-- resumeWindow ENDS -->

		<!-- If resume window... return from here-->
			<?php else: ?>
				<?php echo $this->loadTemplate(strtolower($lesson_data->format));?>
		<?php endif; ?>
			</div>

			<div class="tjlms-lesson__toolbar-content col-xs-12 col-sm-4 p-15  bg-gray assigneddoc-popup <?php echo $showusers ? '':'display-none'; ?>" data-js-attr="lesson-toolbar-content">
				<!-- If toolbar content position is at the bottom-->
				<?php if($this->mode != 'preview' && $lesson_data->format != 'tmtQuiz'): ?>
						<?php echo $toolbar_content_html; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<input type="hidden" id="cluster_id" name="cluster_id" value="<?php echo $tjFieldFieldTable->cluster_id ;?>"><input type="hidden" id="lession_id"value="<?php echo $tjFieldFieldTable->lesson_id;?>">
<!-- DPE HACK-->
<input type="hidden" id="hidetool"value="todolist">
<!--DPE Hack END-->
<?php $launchLessonFullScreen = ($emailFlag == true) ? 'window' : $params->get('launch_full_screen');?>
<script>
	
	let guest = <?php echo $user->guest; ?>;
	
	if(guest){
    // Disable right-click context menu
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });
}

<?php
	Text::script('COM_TJLMS_LESSON_CONFIRM_BOX');
?>

	var confirmMsg             = "<?php echo ($this->course_id) ? (Text::_('COM_TJLMS_LESSON_CONFIRM_BOX')) : (Text::_('COM_TJLMS_DOCUMENT_CONFIRM_BOX')); ?>";
	var launchMode             = "<?php echo $this->mode?>";
	var launchLessonFullScreen = "<?php echo $this->launch_lesson_full_screen;?>";
	var courseDetailsURL       = "<?php echo $this->returnUrl?>";
	var returnUrl              = "<?php echo $this->returnUrl;?>";
	var showLessonPlaylist     = "<?php echo $playList;?>";
	var openModuleId           = "<?php echo $this->openModuleId;?>";
	tjlms.lesson.init(openModuleId);
<?php
	if ($showusers)
	{
	?>
		jQuery(window).load(function()
		{
			if (jQuery(this).hasClass('active'))
			{
				jQuery(this).removeClass('active');
				tjlms.lesson.hideRightPanel();
				tjlms.lesson.toggleLessonPanels();

				return;
			}

			jQuery('.toolbar_buttons').not("[data-ref='jliketoolbar-menu']").removeClass('active');
			jQuery(this).addClass('active');
			tjlms.lesson.showRightPanel();
			jQuery('.toolbar-content').hide();
			var refDiv = jQuery(this).data('ref');
			jQuery('#' + refDiv).show();

			/* if comment box is opened, add comment should be opened*/
			if (refDiv == 'comments') {
				jQuery("#divaddcomment a.jlike_comment_msg").trigger('click');
			}

			tjlms.lesson.toggleLessonPanels();
			jQuery("#assignment").show();
		});
	<?php
	}
	?>
<?php
	if(!$this->course_id && (strpos($jLikeInteraction->content, '<form') !== false))
	{
	?>
		jQuery(window).on('load',function() {
			jQuery(".toolbar-content").hide();
			jQuery(".tjlms_lesson__player").addClass('col-sm-8');
			setTimeout(function(){jQuery(".tjlms-lesson__toolbar-content").show();
			jQuery("#interaction").show();},500);
			
		});

	<?php
	}
?>

jQuery(document).on('click', '#interactionRead', function() {
    var read = jQuery(this);

    if (read.is(':checked')) {
        jQuery('#JlikeInteractionModal').modal({
            keyboard: false,
            backdrop: 'static',
        });
        jQuery('#JlikeInteractionModal').modal('show');
        jQuery('.modal-backdrop').remove();
        jQuery('#interactionUsed').prop('disabled', false);
    } else {
        jQuery('#interactionUsed').prop('disabled', true);
        jQuery('#interactionUsed').prop('checked', false);
        jQuery('#interactionRead').prop('checked', false);

        jQuery('.usedActions').addClass('hide');
        jQuery(".success-msg").html('');
        interactionUsedSave(read.val());
        jQuery("[data-js-id='used-description']").val('');
    }
});

jQuery(document).ready(function(){

	if(jQuery('#lession_id').val().length < 1)
	{
		jQuery('#notificationwidget').hide();
		jQuery('#notificationwidget').removeClass('d-inline-block');
	}
	
	
})
</script>
