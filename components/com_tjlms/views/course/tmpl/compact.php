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

//Plugin trigger to add the schema for courses
Factory::getApplication()->triggerEvent("onTjLmsCourseSchema", array($this->item));

$courseUrl =	$this->tjlmsFrontendHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id='.$course->id, false);

// Set metadata
$config = Factory::getConfig();
$siteName = $config->get('sitename');
$document->addCustomTag('<meta property="og:title" content="' . $this->escape($course->title) . '" />');
$document->addCustomTag('<meta property="og:image" content="' . $course->image . '" />');
$document->addCustomTag('<meta property="og:url" content="' . $courseUrl . '" />');
$document->addCustomTag('<meta property="og:description" content="' . nl2br($this->escape($course->short_desc)) . '" />');
$document->addCustomTag('<meta property="og:site_name" content="' . $siteName . '" />');
$document->addCustomTag('<meta property="og:type" content="course" />');

$document->addCustomTag('<meta property="twitter:title" content="' . $this->escape($course->title) . '" />');
$document->addCustomTag('<meta property="twitter:image" content="' . $course->image . '" />');
$document->addCustomTag('<meta property="twitter:url" content="' . $courseUrl . '" />');
$document->addCustomTag('<meta property="twitter:description" content="' . nl2br($this->escape($course->short_desc)) . '" />');
$document->addCustomTag('<meta property="twitter:site" content="' . $siteName . '" />');
?>
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

				<?php
				$coursePrerequiSite = $this->item->params->get('courseprerequisite');

				if (!empty($coursePrerequiSite['onBeforeEnrolCoursePrerequisite']['0']) && PluginHelper::isEnabled('tjlms', 'courseprerequisite'))
				{?>
				<div class="tabs">
					<div class="">
					<?php
					echo HTMLHelper::_('bootstrap.startTabSet', 'shikaTab', array('active' => 'contents'));
						echo HTMLHelper::_('bootstrap.addTab', 'shikaTab', 'contents', Text::_('COM_TJLMS_TOC_HEAD_CAPTION'));
				}?>

				<!--course TOC-->
				<div id="tjlms_course_toc" class="row-fluid tjlms_course_toc">
					<?php
						echo $this->loadTemplate('toc');
					?>
				</div><!--tjlms_course_toc ends-->

				<?php
				if (!empty($coursePrerequiSite['onBeforeEnrolCoursePrerequisite']['0']) && PluginHelper::isEnabled('tjlms', 'courseprerequisite'))
				{
					echo HTMLHelper::_('bootstrap.endTab');

					echo HTMLHelper::_('bootstrap.addTab', 'shikaTab', 'prerequisite', Text::_('COM_TJLMS_PREREQUISITE_HEAD_CAPTION'));
					echo $this->loadTemplate('prerequisite');

					echo HTMLHelper::_('bootstrap.endTab');
					?></div>
					<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
				</div>
				<?php }?>

				<?php if (!empty($this->onAftercourseContent))
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
