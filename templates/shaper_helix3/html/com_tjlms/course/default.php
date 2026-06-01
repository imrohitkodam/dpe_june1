<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.renderModal', 'a.tjmodal');
HTMLHelper::_('jquery.token');

$app          = Factory::getApplication();
$document     = Factory::getDocument();
$renderer     = $document->loadRenderer('module');
$currency     = $this->tjlmsparams->get('currency', '', 'STRING');
$allowCreator = $this->tjlmsparams->get('allow_creator', 0, 'INT');
$course       = $this->item;
$showbuy      = $planExpire = 1;
$renew        = 0;
$user         = Factory::getUser();

// Check elearning tool action for DPE
if (!$user->authorise('core.manageall', 'com_cluster'))
{
	JLoader::import('/components/com_subusers/includes/rbacl', JPATH_ADMINISTRATOR);

	if (!RBACL::check($user->id, 'com_cluster', 'core.viewShika', 'com_tjlms'))
	{
		$app = Factory::getApplication();

		$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

		return;
	}
}

// DPE Hack added for notification manager

// Get Current url for notification manager widget
$extraParams = Uri::getInstance()->toString(array('query'));
$extraParams = str_replace('?', '&', $extraParams);
$urlId = '';
$input = $app->input;

if ($input->getInt('id'))
{
	$urlId = '&id=' . $input->getInt('id');
}

$currentUrl =  'index.php?option=' . $input->get('option') . '&view=' . $input->get('view') . $urlId . $extraParams .'&Itemid=' . $input->get('Itemid');
?>
<!-- override for refresh -->
<script type='text/javascript'>

(function()
{
  if( window.localStorage )
  {
    if( !localStorage.getItem('firstLoad') )
    {
      localStorage['firstLoad'] = true;
      window.location.reload();
    }
    else
      localStorage.removeItem('firstLoad');
  }
})();
</script>
<!-- override for refresh -->

<div itemscope itemtype="https://schema.org/Course">
	<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?> tjBs3">
		<div id="com_tjlms_course_content" class="com_tjlms_content com_tjlms_course_content">

		<!-- Course details -->
		<div class="row">
			<?php
				$renderer = $document->loadRenderer('module');
				$modules  = ModuleHelper::getModules('tjlms_course_blocks');
				$attribs  = array();

				if ($modules)
				{
					ob_start();

					foreach ($modules as $module)
					{
						$attribs['style'] = 'xhtml';
						$course_blocksHTML .= $renderer->render($module, $attribs);
					}

					ob_get_clean();

					$courseMainClass   = 'col-xs-12 col-sm-12';
					$courseblocksClass = '';

					if (!empty($course_blocksHTML))
					{
						$courseMainClass .= " col-md-8 col-lg-8 partition-line";
						$courseblocksClass = ' col-xs-12 col-sm-12 col-md-4 col-lg-4 pl-30 sidebar-module';
					}
				}
			?>
			<div class="<?php echo $courseMainClass; ?>">
				<!--Course image and desc -->

				<?php 
					echo $this->loadTemplate('header');
				?>
				<!--Course image and desc ends -->
				<div class="tabs">
				<div class="">
				<?php
				echo HTMLHelper::_('bootstrap.startTabSet', 'shikaTab', array('active' => 'contents'));
					echo HTMLHelper::_('bootstrap.addTab', 'shikaTab', 'contents', Text::_('COM_TJLMS_TOC_HEAD_CAPTION'));
				?>

				<!--course TOC-->
				<div id="tjlms_course_toc" class="row-fluid tjlms_course_toc">
					<?php
						echo $this->loadTemplate('toc');
					?>
				</div><!--tjlms_course_toc ends-->

				<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
				<?php
					$coursePrerequiSite = $this->item->params->get('courseprerequisite');

					if (!empty($coursePrerequiSite['onBeforeEnrolCoursePrerequisite'])?count($coursePrerequiSite['onBeforeEnrolCoursePrerequisite']):0 && PluginHelper::isEnabled('tjlms', 'courseprerequisite'))
					{
						echo HTMLHelper::_('bootstrap.addTab', 'shikaTab', 'prerequisite', Text::_('COM_TJLMS_PREREQUISITE_HEAD_CAPTION'));
						echo $this->loadTemplate('prerequisite');
						echo HTMLHelper::_('bootstrap.endTab');
					} ?>
					</div>
					<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
				</div>

				<?php
				if (!empty($this->onAftercourseContent))
				{
					?>
					<hr class="hr hr-condensed">
					<div class="courseComment">
						<?php
							echo $this->onAftercourseContent;
						?>
					</div>
					<hr class="hidden visible-xs visible-sm">
					<?php
				}
				?>
			</div><!--span8 rightdiv_class ends-->
			<!--Get the left panel - modules one-->
			<?php
			if (!empty($courseblocksClass))
			{
				?>
				<div class="<?php echo $courseblocksClass; ?>">
				<?php

					if ($this->item->allowEnroll)
					{
						$courseData = array();
						$courseData['id'] = $this->item->id;
						$courseData['title'] = $this->item->title;
						$courseData['checkPrerequisiteCourseStatus'] = $this->checkPrerequisiteCourseStatus;
						echo LayoutHelper::render('course.enroll', $courseData);
					}

					if ($this->checkPrerequisiteCourseStatus)
					{
						echo $this->loadTemplate('setgoal');
					}

					if (($this->item->type == 1 && !$allowCreator) || ($this->item->type == 1 && $allowCreator && $course->created_by != $user->id))
					{
						echo $this->loadTemplate('subplans');
					}

					echo $course_blocksHTML;
					?>
				</div>
				<?php
			}	?>
		</div>
	</div>
</div>
</div>

<!-- DPE Hack added for notification manager -->
<input type="hidden" name="url" id="url" value="<?php echo $currentUrl;?>"/>
<input type="hidden" name="element" id="element" value="com_tjlms.course"/>
<input type="hidden" name="element_id" id="element_id" value="<?php echo $this->item->id;?>"/>
<input type="hidden" name="cluster_id" id="cluster_id" value=""/>
