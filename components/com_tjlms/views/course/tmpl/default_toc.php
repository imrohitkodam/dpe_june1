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

/*layout for Image & text ads only (ie. title & img & decrip)
this will be the default layout for the module/zone
*/
jimport('joomla.html.html');
jimport('joomla.utilities.string');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
?>

<div class="tjlms-course-toc pt-10 p-20">
	<?php
	$coursePrerequiSite = $this->item->params->get('courseprerequisite');

		if (empty($coursePrerequiSite['onBeforeEnrolCoursePrerequisite']['0']))
		{ ?>
 		 <h4> <?php echo Text::_("COM_TJLMS_TOC_HEAD_CAPTION");?></h4>
	 		<hr class="tjlms-hr-dark mt-10">
	 	<?php }

if ($this->lesson_count == 0)
{
?>
	<div class="alert alert-warning">
		<?php echo Text::_('TJLMS_NO_LESSON_PRESENT'); ?>
	</div>
<?php
}
?>

<?php $modules_data = $this->item->toc; ?>

<div class="panel-group" id="accordion">

<?php foreach ($modules_data as $module_data)
{
	if ($this->modules_present > 1 && !empty($module_data->lessons))
	{
	?>
	<div class="panel panel-default border-0" id="modlist_<?php	echo $module_data->id;?>">
		<div class="cursor-pointer panel-heading collapsed border-0" data-jstoggle="collapse"
		data-target="#collapse_<?php echo $module_data->id;?>" aria-expanded="false">
			<h5 class="panel-title accordion-toggle">
				<a class="d-inline-block">
					<i class="fa fa-book" aria-hidden="true"></i>
					<span><?php echo $module_data->name; ?></span>
					<?php
					if ($module_data->completedLessonsCount == count($module_data->lessons))
					{
						?>
							<small class="pull-right">
							<i class="fa fa-check-circle"></i>
							<span class="hasTooltip" title="<?php echo HTMLHelper::_('date', $module_data->moduleLastaccessedon, Text::_('DATE_FORMAT_LC6')); ?>">
								<?php echo HTMLHelper::_('date.relative', $module_data->moduleLastaccessedon); ?>
							</span>
							</small>
						<?php
					}
					?>
				</a>
			</h5>
		</div>
		<div id="collapse_<?php	echo $module_data->id;?>" class="panel-collapse collapse">
			<div class="panel-body lessons-module">
	<?php
	}

	$report_link = 'index.php?option=com_tjlms&view=reports&layout=attempts&tmpl=component&course_id=' . $this->item->id;

	foreach ($module_data->lessons as $m_index => $m_lesson)
	{
		$multiscorm       =	0;
		$hovertitle       = " title='" . Text::_('COM_TJLMS_LAUNCH_LESSON_TOOLTIP') . "'";
		$lessonTitleClass = 'col-xs-9';

		// Check id uploaded scorm is multi scorm
		if (isset($m_lesson->scorm_toc_tree) && !empty($m_lesson->scorm_toc_tree))
		{
			$multiscorm = 1;
		}

		if ($multiscorm != 1)
		{
			/*LANUCH button
			 * hovertitle  = POpover content
			 * disabled = if user has no access disable this
			 * active_btn_class = styling
			 * onclick =  Javscript
			 * lock_icon = for prerequisites
			 * */
			$disabled = $lockIcon = $launchButton = '';
			$activeBtnClass = 'btn-small btn-primary tjlms-btn-flat';

			$lessonUrl = "index.php?option=com_tjlms&view=lesson&lesson_id=" . $m_lesson->id . "&tmpl=component&cid=" . $this->course_id;

			$lessonUrl  = $this->tjlmshelperObj->tjlmsRoute($lessonUrl, false);

			$lessonType = $m_lesson->free_lesson ? $m_lesson->free_lesson : 0;
			$courseId   = $this->canAutoEnroll ? $this->course_id : 0;
			$onclick    = "open_lessonforattempt('" . addslashes(htmlspecialchars($lessonUrl)) . "','"
			. $this->launch_lesson_full_screen . "' , '" . $courseId . "', '" . $lessonType . "');";

			$usercanAccess = $this->lessonModel->canUserLaunchFromCourse($this->item, $m_lesson, $this->oluser_id);

			if ($usercanAccess['access'] == 1)
			{
				$hovertitle     = '';
				$activeBtnClass = 'btn-small btn-primary pull-right';
				$btnTitle       = Text::_("COM_TJLMS_LAUNCH");

				if ($m_lesson->format != "tmtQuiz")
				{
					$plg_type         = 'tj' . $m_lesson->format;
					$format_subformat = !empty($m_lesson->sub_format) ? explode('.', $m_lesson->sub_format) : '';
					$plg_name         = isset($format_subformat[0])?$format_subformat[0]:'';

					PluginHelper::importPlugin($plg_type);
					$launchButtonArray = Factory::getApplication()->triggerEvent('onGet' . $plg_name . 'LaunchButtonHtml', array($m_lesson));

					if (!$plg_name)
					{
						$hovertitle     = " rel='popover' data-original-content='" . htmlentities(Text::_('COM_TJLMS_PLUGIN_DISABLED'), ENT_QUOTES) . " ' ";
						$onclick        = "";
						$activeBtnClass = 'btn-small btn-disabled bg-lightgrey pull-right';
						$btnTitle       = Text::_("COM_TJLMS_LAUNCH");

						// When the launch btn is disabled
						$lock_img = Uri::root(true) . '/media/com_tjlms/images/default/icons/lock.png';
						$lockIcon = "<img src='$lock_img' class='d-inline' alt='lock icon'/></i>";
					}
					elseif (!empty($launchButtonArray) && !empty($launchButtonArray[0]))
					{
						$launchButton = $launchButtonArray[0];
					}
					else
					{
						// When it is enabled
						$lockIcon = "<i class='fa fa-unlock' aria-hidden='true'></i>";

						// When the launch btn is enabled
						$lock_img  = Uri::root(true) . '/media/com_tjlms/images/default/icons/unlock.png';
						$lockIcon1 = "<img src='$lock_img' class='d-inline' alt='unlock icon'/></i>";
					}
				}
			}
			else
			{
				$hovertitle = $usercanAccess['msg'] ?  " rel='popover' data-original-content='" . htmlentities($usercanAccess['msg'], ENT_QUOTES) . " ' " : "";

				$onclick        = "";
				$activeBtnClass = 'btn-small btn-disabled bg-lightgrey pull-right';
				$btnTitle       = Text::_("COM_TJLMS_LAUNCH");
				$lockIcon       = "<i class='fa fa-lock mr-5' aria-hidden='true'></i>";

				// When the launch btn is enabled
				$lock_img  = Uri::root(true) . '/media/com_tjlms/images/default/icons/lock.png';
				$lockIcon1 = "<img src='$lock_img' class='d-inline' alt='lock icon'/></i>";
			}
		}

		if (!empty($m_lesson->userStatus['viewed']))
		{
			$lessonTitleClass = 'col-xs-12';

			$attempts_done_by_available = $m_lesson->userStatus['attemptsDone'];

			if ($m_lesson->no_of_attempts > 0)
			{
				$attempts_done_by_available .= " / " . $m_lesson->no_of_attempts;
			}
			else
			{
				$attempts_done_by_available .= " / " . Text::_("COM_TJLMS_LABEL_UNLIMITED_ATTEMPTS");
				$m_lesson->no_of_attempts = Text::_('COM_TJLMS_LABEL_UNLIMITED_ATTEMPTS');
			}

			if ($m_lesson->userStatus['attemptsDone'] > 0)
			{
				$reportLink = $this->tjlmshelperObj->tjlmsRoute($report_link . '&lesson_id=' . $m_lesson->id, false);

				$popover_con = "<div>Completed attempts:" . $m_lesson->userStatus['completedAttempts']
				. "</div><div>Total attempt:" . $m_lesson->userStatus['attemptsDone'] . "</div>";

				$statusattpt = "<a class='tjmodal attempt_report' href='" . $reportLink . "' bpl='popover' data-placement='right'
				data-original-content='" . htmlentities($popover_con, ENT_QUOTES) . "'>" . $attempts_done_by_available . " </a>";
			}
			else
			{
				$statusattpt = $attempts_done_by_available;
			}

			$completionClass = 'label-default';

			if ($m_lesson->userStatus['status'] == 'completed' || $m_lesson->userStatus['status'] == 'passed')
			{
				$completionClass = 'label-success';
			}
			elseif ($m_lesson->userStatus['status'] == 'incomplete')
			{
				$completionClass = 'label-warning';
			}
			elseif ($m_lesson->userStatus['status'] == 'failed')
			{
				$completionClass = 'label-danger';
			}
		}
	?>

	<div id="<?php echo $m_lesson->alias; ?>" class="lessons-module_inner pb-10 pt-10">
		<div class="row">
	  <?php
		if ($launchButton && isset($launchButton['html']))
		{
			echo $launchButton['html'];

			if ($launchButton['supress_lms_launch'] == 0)
			{
				$tjlms_launch = 1;
			}
		}
		else
		{
			$tjlms_launch = 1;
		}
		?>

	<div class="tjlms_toc__lesson-title <?php echo $lessonTitleClass; ?>">
		<img class="d-inline" alt="<?php echo $m_lesson->format; ?>"
		title="<?php echo ucfirst($m_lesson->format); ?>"
		src="<?php echo Uri::root(true) . '/media/com_tjlms/images/default/icons/' . $m_lesson->format . '.png';?>"/>
		<span class="pl-10">
			<span class="d-inline fs-15"><?php echo ucfirst($m_lesson->title);?></span>
		</span>
		<?php if ($this->oluser_id) : ?>
			<span class="label <?php echo $m_lesson->userStatus['status'] == 'not_started' ? 'label-default' : $completionClass; ?> ml-10">
				<?php echo Text::_("COM_TJLMS_LESSON_STATUS_" . strtoupper($m_lesson->userStatus['status'])); ?>
			</span>
		<?php endif; ?>
	</div>

	<?php
	if ($tjlms_launch == 1 && empty($m_lesson->userStatus['viewed']))
	{
		?>
		<div class="col-xs-3">
			<button <?php echo $hovertitle; ?>
			class="br-0 btn <?php echo $activeBtnClass; ?>"
			onclick="<?php echo $onclick?>"><?php echo $lockIcon; ?>
				<span class="lesson_attempt_action hidden-xs hidden-sm">
					<?php echo $btnTitle; ?>
				</span>
				<span class="glyphicon glyphicon-play hidden-md hidden-lg"></span>
			</button>
		</div>
		<?php
	}
	?>
		<!--tjlms_toc__lesson-title-->
	</div>

	<?php
	if (!$m_lesson->userStatus['viewed'] && $m_lesson->description)
	{
	?>
	<div class="row lesson_statusinfo">
		<div class="long_desc">

			<?php
				if (strlen($m_lesson->description) > 75 )
				{
					echo nl2br($this->tjlmshelperObj->html_substr(htmlentities($m_lesson->description), 0, 75)) . '<a href="javascript:" class="r-more">...More</a>';
				}
				else
				{
					echo nl2br(htmlentities($m_lesson->description));
				}
			?>
		</div>
		<div class="long_desc_extend" style="display:none;">
		<?php
			echo nl2br(htmlentities($m_lesson->description)) . '<a href="javascript:" class="r-less">...Less</a>';
		?>
		</div>
	</div><!--media-content-->
	<?php
	}
	?>

	<?php if (!empty($m_lesson->userStatus['viewed']))
	{
		$btnTitle = Text::_("COM_TJLMS_START_OVER");
		?>
		<div class="row mt-10">
			<div class="col-xs-9 small lesson_statusinfo">
				<div>
					<span class="border-r pr-10 mr-10"><b><?php echo Text::_("COM_TJLMS_USER_STARTED_LESSON_ON");?></b>&nbsp;
						<?php echo $m_lesson->userStatus['startedOn']; ?>
					</span>

					<span class="hidden-xs"><b><?php echo Text::_("COM_TJLMS_USER_LAST_ACCESSED_LESSON_ON");?></b>&nbsp;
						<?php echo $m_lesson->userStatus['lastAccessedOn']; ?>
					</span>
				</div>
				<div>
					<span class="border-r pr-10 mr-10"><b><?php echo Text::_("COM_TJLMS_USER_TOTAL_TIME_ON_LESSON");?></b>
						&nbsp;<?php echo $m_lesson->userStatus['totalTimeSpent']; ?>
					</span>
					<?php if ($m_lesson->format != 'feedback')
					{
					?>
						<span class="hidden-xs border-r pr-10 mr-10"><b><?php echo Text::_("COM_TJLMS_TOC_HEAD_SCORE");?></b>
							<?php echo $m_lesson->userStatus['score']; ?>
						</span>
					<?php
					}
					?>
					<br class="visible-xs">
					<span><b><?php echo Text::_("ATTEMPTS");?></b>
						&nbsp;<?php echo $statusattpt;?>
					</span>
				</div>
			</div>

		<?php
		if ($tjlms_launch == 1)
		{
		?>
		<div class="col-xs-3">
			<button <?php echo $hovertitle; ?>
			class="br-0 btn <?php echo $activeBtnClass; ?>"
			onclick="<?php echo $onclick?>">
				<?php echo $lockIcon; ?>
				<span class="lesson_attempt_action hidden-sm hidden-xs">
					<?php echo $btnTitle; ?>
				</span>
				<span class="glyphicon glyphicon-play glyphicon glyphicon-play hidden-md hidden-lg"></span>
			</button>
		</div>
		<?php
		}
		?>
	</div>
	<?php
	}
	?>
	</div>
	<?php
	}
	if ($this->modules_present > 1 && !empty($module_data->lessons))
	{
	?>
				</div>
			</div>
		</div>
	<?php
	}
}
?>

</div>

</div>

<script>
jQuery(document).ready(function() {
	/*var moduleId = <?php echo $this->openModuleId; ?>;*/
	var width = jQuery(window).width();
	var height = jQuery(window).height();
	jQuery('a.attempt_report').attr('rel','{handler: "iframe", size: {x: '+(width-(width*0.10))+', y: '+(height-(height*0.10))+'}}');

	/*if (moduleId){
		toggleModuleAccordion(moduleId);
	}*/
});
</script>
<script>
	jQuery(window).on('load', function () {

	jQuery('[rel="popover"]').on('click', function (e) {
		jQuery('[rel="popover"]').not(this).popover('hide');
	});

	jQuery('[rel="popover"]').popover({
		html: true,
		trigger: 'click',
		//container: this,
		placement: 'left',
		content: function () {
			return '<button type="button" id="close" class="close" onclick="popup_close(this);">&times;</button><div class="tjlms-toc-popover"><div class="tjlms-toc-content font-500">'+jQuery(this).attr('data-original-content')+'</div></div>';
		}
	});
});

function popup_close(btn)
{
	var div = jQuery(btn).closest('.popover').hide();
}
</script>
