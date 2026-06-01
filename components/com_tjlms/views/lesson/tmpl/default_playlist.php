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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
?>
<div class="tjlms-lesson__playlist"  id="tjlms-lesson__playlist">
	<?php $modules_data = $this->module_data; ?>

	<?php foreach ($modules_data as $module_data):?>

		<?php if ($this->modules_present > 1 && !empty($module_data->lessons)): ?>

			<div class="panel panel-default border-0 mb-0" id="modlist_<?php	echo $module_data->id;?>">
								<div class="cursor-pointer panel-heading collapsed" data-jstoggle="collapse" data-target="#collapse_<?php echo $module_data->id;?>" aria-expanded="false">

					<h5 class="panel-title accordion-toggle">
						<a class="d-inline-block">
							<i class="fa fa-book" aria-hidden="true"></i>
							<span><?php echo $this->escape($module_data->name);	?></span>
							<?php
							if ($module_data->completedLessonsCount == count($module_data->lessons))
							{
								?>
									<i class="fa fa-check-circle pull-right"></i>
								<?php
							}
							?>
						</a>
					</h5>
				</div>

				<div id="collapse_<?php	echo $module_data->id;?>" class="panel-collapse collapse">
					<div class="panel-body p-0">

		<?php endif; ?>

		<?php $report_link ='index.php?option=com_tjlms&view=reports&layout=attempts&tmpl=component'; ?>

		<?php foreach($module_data->lessons as $m_index => $m_lesson): ?>

				<?php
					$icon = Uri::root(true) . '/media/com_tjlms/images/default/icons/';
					$multi_scorm	=	0;
					$hovertext = $m_lesson->format;
					$hovertitle = " title='" . Text::_('COM_TJLMS_LAUNCH_LESSON_TOOLTIP') . "'";
					$lessonTitleClass = 'col-xs-9';

					if ($hovertext == 'tmtQuiz'):
						$hovertext = 'quiz';
						$hovertitle=" title='" . Text::_("COM_TJLMS_LAUNCH_QUIZ_TOOLTIP") . "'";

					endif;

					// Check id uploaded scorm is multi scorm
					if(isset($m_lesson->scorm_toc_tree) && !empty($m_lesson->scorm_toc_tree)):
						$multi_scorm	=	1;
					endif;

					if ($multi_scorm != 1)
					{

						/*LANUCH button
						 * hovertitle  = POpover content
						 * disabled = if user has no access disable this
						 * active_btn_class = styling
						 * onclick =  Javscript
						 * lock_icon = for prerequisites
						 * */
						$disabled = $lock_icon = $launchButton = '';
						$active_btn_class = 'btn-small btn-primary tjlms-btn-flat';

						$lesson_url = $this->tjlmshelperObj->tjlmsRoute("index.php?option=com_tjlms&view=lesson&lesson_id=" . $m_lesson->id . "&tmpl=component&cid=" .$this->course_id, false);
						$lessonType = $m_lesson->free_lesson ? $m_lesson->free_lesson : 0;

						$onclick=	"open_lessonforattempt('" . addslashes(htmlspecialchars($lesson_url)) . "','" . $this->launch_lesson_full_screen ."', '" . $this->course_id . "', '" . $lessonType . "');";

						$usercanAccess = $this->model->canUserLaunch($m_lesson->id, $this->user_id);

						if ($usercanAccess['access'] == 1)
						{
							$hovertitle = '';
							$active_btn_class = 'btn-small btn-primary';

							if ($m_lesson->format != "tmtQuiz")
							{
								$plg_type = 'tj' . $m_lesson->format;
								$format_subformat = !empty($m_lesson->sub_format) ? explode('.', $m_lesson->sub_format) : '';
								$plg_name = isset($format_subformat[0])?$format_subformat[0]:'';

								PluginHelper::importPlugin($plg_type);
								$launchButtonArray = Factory::getApplication()->triggerEvent('onGet' . $plg_name . 'LaunchButtonHtml', array($m_lesson));

								if (!$plg_name)
								{
									$hovertitle	=	" rel='popover' data-original-content='" . htmlentities(Text::_('COM_TJLMS_PLUGIN_DISABLED'), ENT_QUOTES) . " ' ";
									$onclick="";
									$active_btn_class = 'btn-small btn-disabled bg-grey';
									$lock_icon="<i class='fa fa-lock' aria-hidden='true'></i>";
								}
								elseif (!empty($launchButtonArray) && !empty($launchButtonArray[0]))
								{
									$launchButton = $launchButtonArray[0];
								}
							}
						}
						else
						{
							$hovertitle	=	" rel='popover' data-original-content='" . htmlentities($usercanAccess['msg'],ENT_QUOTES) . " ' ";

							$onclick="";
							$active_btn_class = 'btn-small btn-disabled bg-grey';
							$lock_icon="<i class='fa fa-lock' aria-hidden='true'></i>";
						}

						if ($launchButton && isset($launchButton['html']))
						{

							echo $launchButton['html'];

							if ($launchButton['supress_lms_launch'] == 0):
								$tjlms_launch = 1;
							endif;
						}
						else
						{

							$tjlms_launch = 1;
						}
						?>

						<?php $completionClass = 'label-default';?>
						<?php if ($m_lesson->userStatus['status'] == 'completed' || $m_lesson->userStatus['status'] == 'passed' ): ?>
							<?php $completionClass = 'label-success';?>
						<?php endif;?>

						<?php if ($m_lesson->userStatus['status'] == 'incomplete'): ?>
							<?php $completionClass = 'label-warning';?>
						<?php endif;?>

						<?php if ($m_lesson->userStatus['status'] == 'failed'): ?>
							<?php $completionClass = 'label-danger';?>
						<?php endif;?>


					<div id="<?php echo $m_lesson->alias; ?>" class="tjlms-lesson__playlist__lesson <?php echo ($m_lesson->id == $this->lesson_id) ? ' active alert-success ' : '' ?> d-table p-10 pb-10">
						<div class="d-table-row">
							<div class="d-table-cell tjlms_toc__lesson-title valign-middle">
								<div>
									<img class="d-inline-block" alt="<?php echo $m_lesson->format; ?>" title="<?php echo ucfirst($hovertext); ?>" src="<?php echo Uri::root(true).'/media/com_tjlms/images/default/icons/'.$m_lesson->format.'.png';?>"/>

									<?php	echo htmlentities(ucfirst($m_lesson->title));?>
								</div>
								<?php if ($this->user_id): ?>
									<div class="label <?php echo $completionClass;?> ml-10">
										<?php echo Text::_("COM_TJLMS_LESSON_STATUS_" . strtoupper($m_lesson->userStatus['status'])); ?>
									</div>
								<?php endif; ?>
							</div>
							<div class="pull-right pt-5 pr-5">
								<?php
									if ($m_lesson->ideal_time)
									{
										echo $this->escape(Tjlms::Utilities()->secToHours($m_lesson->ideal_time * 60, false));
									}
								?>
							</div>
					<?php if ($tjlms_launch == 1): ?>

							<div class="d-table-cell text-right valign-middle">

								<button <?php echo $hovertitle; ?> class="br-0 btn <?php echo $active_btn_class; ?>" onclick="<?php echo $onclick?>">
									<?php echo $lock_icon; ?>
									<?php $lauchicon = "fa-angle-right";?>
									<span class="<?php echo ($lock_icon) ? 'hidden' : 'fa ' . $lauchicon;?> " aria-hidden="true"></span>

								</button>

							</div>

					<?php endif; ?>

						<!--tjlms_toc__lesson-title-->
						</div>
					</div>
				<?php } ?>
			<?php endforeach;?>


<?php	if ($this->modules_present > 1 && !empty($module_data->lessons)): ?>
				</div>
			</div>
		</div>
<?php endif; ?>

<?php endforeach;?>
</div>


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
			return '<button type="button" id="close" class="close" onclick="popup_close(this);">&times;</button><div class="tjlms-toc-popover"><div class="tjlms-toc-content">'+jQuery(this).attr('data-original-content')+'</div></div>';
		}
	});
});

function popup_close(btn)
{
	var div = jQuery(btn).closest('.popover').hide();
}
</script>
