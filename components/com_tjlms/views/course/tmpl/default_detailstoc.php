<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\CMS\Language\Text;
/*layout for Image & text ads only (ie. title & img & decrip)
this will be the default layout for the module/zone
*/
?>

<table class="table table-bordered tjlms_course_toc_listing no-margin no-padding unstyled_list tjlms-table" width="100%">

<?php if($this->lesson_count == 0) { ?>
	<tr class="tjlms_lesson_<?php echo $module_data->id?>">
		<td><div class="alert alert-warning"><?php	echo Text::_('TJLMS_NO_LESSON_PRESENT');	?></div></td></tr>

<?php }
	else
	{ ?>

		<?php $modules_data = $this->module_data; ?>

		<?php foreach ($modules_data as $module_data){ ?>

			<?php if($this->modules_present > 1) { ?>

				<tr id="modlist_<?php	echo	$module_data->id;	?>"  class="tjlms_module">
					<td class="tjlms_section_title">
						<div class="tjlms_section_title_container">
							<span><?php echo $module_data->name;	?></span>
							<div class="collapse-icon-containner">
								<b class="collapse-icon"></b>
							</div>
						</div>
					</td>
				</tr>

			<?php } ?>

	<?php if (!$module_data->lessons) { 	?>

				<tr class="tjlms_lesson_<?php echo $module_data->id?>"><td colspan=4><div class="alert alert-warning"><?php	echo Text::_('TJLMS_NO_LESSON_PRESENT');	?></div></td></tr>

		<?php }	else { ?>

				<?php $lessondetails_link =	'index.php?option=com_tjlms&view=lesson&layout=details&tmpl=component'; ?>
				<?php $report_link =	'index.php?option=com_tjlms&view=reports&layout=attempts&tmpl=component'; ?>


				<?php foreach($module_data->lessons as $m_lesson) { ?>

					<?php
						$lessondetails_link .= "&lesson_id=".$m_lesson->id;

						// Check id uploaded scorm is multi scorm
						$multi_scorm	=	0;
						if(isset($m_lesson->scorm_toc_tree) && !empty($m_lesson->scorm_toc_tree)){
							$multi_scorm	=	1;
						}
					?>

						<tr id="lessonlist_<?php	echo	$m_lesson->id;	?>" class="tjlms_lesson tjlms_lesson_<?php echo $module_data->id?>">

								<!--<div class="tjlms_lesson-info row-fluid">-->
								<td>
									<?php
										$this->lesson_data	=	$m_lesson;

										$html_lesson='';
										$show_additional = 0;
										$layout = $this->tjlmshelperObj->getViewpath('com_tjlms','lesson','details');
										ob_start();
										include($layout);
										$html_lesson.= ob_get_contents();
										ob_end_clean();
										echo $html_lesson;
									?>

								</td>
							</tr>

				<?php } ?>
			<?php } ?>
	<?php } ?>
<?php } ?>
</table>

