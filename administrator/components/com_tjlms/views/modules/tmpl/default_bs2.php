<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
jimport('joomla.html.pane');
HTMLHelper::_('behavior.formvalidation');
HTMLHelper::_('behavior.modal');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

include_once JPATH_COMPONENT.'/js_defines.php';

$options['relative'] = true;
HTMLHelper::_('script', 'com_tjlms/tjlmsAdmin.min.js', $options);
$input = Factory::getApplication()->input;
$course_id	= $this->course_id;

?>

<script>
	tjlmsAdmin.modules.init();

	jQuery(document).ready(function(){


			/* Change calendar id*/
			var i = 0;

			jQuery('.lesson_basic_form').each(function()
			{
				if(i != 0)
				{
					var tjlmsformid	= jQuery(this).attr('id');
					var formid	= tjlmsformid.replace("lesson-basic-form_", "");

					if (!formid)
					{
						var formid	= tjlmsformid.replace("lesson-add-form_", "");
					}

					var newCalendarId = "jform_start_date"+formid;
					var newCalendarBtnId = "jform_start_date_img"+formid;

					//jQuery(this '#jform_start_date').val()
					jQuery(this).find('#jform_start_date').attr("id",newCalendarId);
					jQuery(this).find('#jform_start_date_img').attr("id",newCalendarBtnId);

					/* Change Label Id for Error Message*/
					jQuery(this).find('label[for="jform_start_date"]').attr("for",newCalendarId);

					/* Start date calendar script load*/
					loadScriptForcalendar(newCalendarId, newCalendarBtnId);

					var newCalendarId = "jform_end_date"+formid;
					var newCalendarBtnId = "jform_end_date_img"+formid;

					//jQuery(this '#jform_start_date').val()
					jQuery(this).find('#jform_end_date').attr("id",newCalendarId);
					jQuery(this).find('#jform_end_date_img').attr("id",newCalendarBtnId);

					/* Change Label Id for Error Message*/
					jQuery(this).find('label[for="jform_end_date"]').attr("for",newCalendarId);

					/* Start date calendar script load*/
					loadScriptForcalendar(newCalendarId, newCalendarBtnId);
				}

				i++;
			});


	});

	function loadScriptForcalendar(newCalendarId, newCalendarBtnId)
	{
		window.addEvent('load', function() {Calendar.setup({
					// Id of the input field
					inputField: newCalendarId,
					// Format of the input field
					ifFormat: "%Y-%m-%d %H:%M:%S",
					// Trigger for the calendar (button ID)
					button: newCalendarBtnId,
					// Alignment (defaults to "Bl")
					align: "Tl",
					singleClick: true,
					firstDay: 0
					});
				});
	}
</script>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?>">
	<?php
		ob_start();
		include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
		$layoutOutput = ob_get_contents();
		ob_end_clean();
		echo $layoutOutput;
	?> <!--// JHtmlsidebar for menu ends-->


		<div class="modal hide fade" id="tjlmsModal">
		  <div class="modal-body">

		  </div>
			<div class="modal-footer">
				<a class="btn btn-primary" data-dismiss="modal">Close</a>
			</div>
		</div>


		<div class="curriculum-container tjBs3">
			<?php if (empty($this->CourseInfo)){ ?>

			<div class="alert alert-danger">
				<span><?php echo Text::_('COM_TJLMS_COURSE_INVALID_URL');?></span>
			</div>

		<?php }else { ?>
			<div class="help-bolck">
				<div class="media">
					<div class="pull-left" >
						<img class="media-object" src="<?php echo $this->CourseInfo->image; ?>">
					</div>
					<div class="media-body">
						<a title="<?php echo Text::_('COM_TJLMS_COURSE_EDIT_LINK'); ?>" href="index.php?option=com_tjlms&view=course&layout=edit&id=<?php echo $this->CourseInfo->id; ?>">
							<strong><?php echo $this->CourseInfo->title; ?></strong>
						</a>
						<div class="media">
							<span ><?php echo $this->CourseInfo->short_desc; ?></span>
						</div>
					</div>
				</div>
			</div>

			<div style="clear:both"></div>

			<?php if(empty($this->moduleData)){ ?>

				<div class="alert alert-info">
					<?php echo Text::_("COM_TJLMS_TRAININGMATERIAL_MESSGE");?>
				</div>

			<?php } ?>

			<!-- Show warning massage if passable lesson is not added in course start -->
			<?php if ($this->CourseInfo->certificate_term == 2) 
			{
				if (!$this->passableLessons)
				{ ?>

					<div class="alert alert-warning">
					<?php echo Text::_("COM_TJLMS_ADD_PASSABLE_TRAININGMATERIAL_MESSGE");?>
					</div>
			<?php
				}
			} ?>
			<!-- Show warning massage if passable lesson is not added in course end -->

			<?php
				if ($this->enrolled_users)
				{
					$toolsLink = JRoute::_("index.php?option=com_tjlms&view=tools&course_id=" . $this->CourseInfo->id);
				?>
					<div class="alert alert-info"><?php echo Text::sprintf('COM_TJLMS_CALCULATE_COURSE_PROGRESS_NOTICE', $this->enrolled_users, $toolsLink); ?></div>
				<?php 
					}
			?>

			<!--UL containing all modules-->
			<ul id="course-modules" class="courseModules curriculum-ul">

				<?php
					echo $this->loadTemplate('lessons');
				?>
			</ul><!--UI for Modules ends-->

			<div class="add-module-div" data-js-id="edit-module">
				<img alt="Add module" src="<?php echo JUri::root(true).'/media/com_tjlms/images/default/icons/add-module.png'; ?>"  title="<?php echo Text::_('COM_TJLMS_ADD_MULTIPLE_LESSONS');	?>"/>
				<span><?php	echo Text::_('COM_TJLMS_ADD_MODULE');	?></span>
			</div>

			<div class="module-edit-form" data-js-id="create-module-form" id="add_module_form_<?php echo $this->course_id;?>">
				<?php
					$moduleHtml='';
					$modId = 0;
					$modName = '';
					$modState	= 1;
					$modDescription='';
					$modImage='';
					$courseId	= $this->course_id;
					$tjlmshelperObj	=	new comtjlmsHelper();
					$layout = $tjlmshelperObj->getViewpath('com_tjlms','modules','module','ADMIN','ADMIN');
					ob_start();
					include($layout);
					$moduleHtml.= ob_get_contents();
					ob_end_clean();
					echo $moduleHtml;
				?>
			</div>
				<input type="hidden" name="option" value="com_tjlms" />
				<input type="hidden" id="course_id" name="course_id" value="<?php echo $course_id; ?>" />
				<input type="hidden" id="task" name="task" value="" />
				<input type="hidden" name="view" value="modules" />
				<input type="hidden" name="controller" value="modules" />
				<input type="hidden" name="controller" value="modules" />
		<?php } ?>
		</div>
	</div>
</div>
