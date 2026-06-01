<?php
/**
 * @package LMS shika
 * @subpackage  mod_lms_filter
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.techjoomla.com
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Uri\Uri;

$options['relative'] = true;
HTMLHelper::_('stylesheet', 'mod_lms_filter/style.css', $options);
HTMLHelper::_('script', 'mod_lms_filter/script.js', $options);

$layout = Factory::getApplication()->input->get('layout', '', 'STRING');
$filterClass = '';
$input = Factory::getApplication()->input;
$courses_to_show = $input->get('courses_to_show', 'all', 'STRING');

?>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">
	<form name="adminForm" id="adminForm" class="form-validate" method="post">

		<div id="filter-bar">
			<?php if ($params->get('search',1) == 1) : ?>

				<?php $filterClass = ($mod_filter->search) ? 'filterActive' : ''; ?>

				<div class="filter_search d-inline-block valign-top mb-10 col-xxs-12">

					 <div class="input-group <?php echo $filterClass;?>" data-id="filter-search">
						<input type="text" name="filter_search" id="filter_search"
						placeholder="<?php echo Text::_('MOD_LMS_FILTER_FILTER_SEARCH_DESC_COURSES'); ?>"
						value="<?php echo htmlspecialchars($mod_filter->search, ENT_COMPAT, 'UTF-8'); ?>"
						class="hasTooltip form-control" title="<?php echo Text::_('JSEARCH_FILTER'); ?>"/>

						<span class="d-table-cell valign-middle">
							<button type="submit" class="btn hasTooltip"
							title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
								<i class="fa fa-search"></i>
							</button>
						</span>
					</div><!-- /input-group -->

				</div>
			<?php endif; ?>

				<?php if ($params->get('course_type',1) == 1) : ?>
				<?php
					$typeoptions   = array();
					$typeoptions[] = HTMLHelper::_('select.option','-1',Text::_('MOD_LMS_FILTER_ALL_COURSE_TYPE'));
					$typeoptions[] = HTMLHelper::_('select.option','0',Text::_('MOD_LMS_FILTER_ALL_COURSE_TYPE_FREE'));
					$typeoptions[] = HTMLHelper::_('select.option','1',Text::_('MOD_LMS_FILTER_ALL_COURSE_TYPE_PAID'));

					$filterClass = ($mod_filter->course_type != -1) ? 'filterActive' : '';
				?>

<!--
				<div data-id="filter-type" class="d-inline-block valign-top mb-10 <?php echo $filterClass; ?>">
						<?php
						// echo HTMLHelper::_('select.genericlist', $typeoptions, "course_type", 'class="input-small"  size="1" onchange="this.form.submit();" name="course_type"',"value", "text",$mod_filter->course_type);
						?>
				</div>
-->
			<?php endif; ?>

			<?php
			if ($params->get('category',1) == 1): ?>
				<?php
					$options   = array();
					$options[] = HTMLHelper::_('select.option', '-1', Text::_('MOD_LMS_FILTER_SELECT_CATEGORY'));

					foreach($cats as $cat)
					{
						$options[] = HTMLHelper::_('select.option', $cat->value, $cat->text);
					}

					$filterClass = ($mod_filter->category_filter != 0) ? 'filterActive' : '';
				?>
				<div data-id="filter-category" class="d-inline-block valign-top mb-10 <?php echo $filterClass; ?>">
					<?php

					echo HTMLHelper::_('select.genericlist', $options, "category_filter", 'class="" size="1"
					onchange="this.form.submit();" name="category_filter"',"value", "text",$mod_filter->category_filter);
					?>
				</div>
			<?php endif; ?>

			<?php

			$resetClass="col-xxs-12";

			if ($params->get('creator', 0) == 1): ?>
				<?php

					$resetClass="col-xxs-6";

					$courseCreatorsOption   = array();
					$courseCreatorsOption[] = HTMLHelper::_('select.option', '0', Text::_('MOD_LMS_FILTER_SELECT_COURSE_CREATOR'));
					$filterClass = '';
					$componentParams = ComponentHelper::getParams('com_tjlms');
					$param = $componentParams->get('show_user_or_username', 'name');

					foreach($courseCreators as $courseCreator)
					{
						$courseCreatorsOption[] = HTMLHelper::_('select.option', $courseCreator->created_by, $courseCreator->$param);
					}

					$filterClass = ($mod_filter->creator_filter != 0) ? 'filterActive' : '';
				?>
				<div data-id="filter-author" class="d-inline-block valign-top mb-10 <?php echo $resetClass;?> <?php echo $filterClass; ?>">
						<?php
							echo HTMLHelper::_('select.genericlist', $courseCreatorsOption, "creator_filter", 'class="input-medium" "size="1"
									onchange="this.form.submit();"',"value", "text",$mod_filter->creator_filter);
						?>
				</div>
			<?php endif; ?>

			<?php
			if ($params->get('course_status', 0) == 1):
					$resetClass="col-xxs-6";
					$courseStatusOptions   = array();
					if ($courses_to_show == 'enrolled')
					{
						$courseStatusOptions[] = HTMLHelper::_('select.option','0',Text::_('MOD_LMS_FILTER_ENROLL_COURSE_STATUS'));
					}
					else
					{
						$courseStatusOptions[] = HTMLHelper::_('select.option','0',Text::_('MOD_LMS_FILTER_ALL_COURSE_STATUS'));
						$courseStatusOptions[] = HTMLHelper::_('select.option','enrolledcourses',Text::_('MOD_LMS_FILTER_ENROLL_COURSE_STATUS'));
					}

					$courseStatusOptions[] = HTMLHelper::_('select.option','completedcourses',Text::_('MOD_LMS_FILTER_COMPLETE_COURSE_STATUS'));
					$courseStatusOptions[] = HTMLHelper::_('select.option','incompletedcourses',Text::_('MOD_LMS_FILTER_INCOMPLETE_COURSE_STATUS'));

					$filterClass = ($mod_filter->course_status != '') ? 'filterActive' : '';
				?>

				<div data-id="filter-course-status" class="d-inline-block valign-top mb-10 <?php echo $resetClass;?> <?php echo $filterClass; ?>">
						<?php
						echo HTMLHelper::_('select.genericlist', $courseStatusOptions, "course_status", 'class="input-medium"  size="1"
							onchange="this.form.submit();" name="course_status"',"value", "text",$mod_filter->course_status);
						?>
				</div>
			<?php endif; ?>

				<div class="filter_search d-inline-block valign-top mb-10 text-center <?php echo $resetClass;?>">
					<button type="button" class="btn hasTooltip btn-primary"
									title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"
									onclick="tjlmsfilter.reset();this.form.submit();">
						<i class="fa fa-close"></i>
					</button>
				</div>


				<?php if ($params->get('pagination', 1) == 1):
					JLoader::import('joomla.application.component.model');
					JLoader::import('courses', JPATH_SITE . '/components/com_foo/models');
					$coursesModel = BaseDatabaseModel::getInstance('courses', 'TjlmsModel');
					$coursesModel->pagination = $coursesModel->getPagination();
				?>
				<div class="d-inline-block valign-top pull-right">
						<div class="hidden-xs btn-group pull-right">
							<label for="limit" class="element-invisible">
								<?php echo Text::_('COM_TJLMS_SEARCH_SEARCHLIMIT_DESC'); ?>
							</label>
							<?php echo $coursesModel->pagination->getLimitBox(); ?>
						</div>
				</div>
			<?php endif; ?>
		</div>

		<div class="clearfix"></div>
		<input type="hidden" name="lms_current_url" value="<?php echo Uri::getInstance()->toString();?>"/>
		<input type="hidden" name="option" value="com_tjlms" />
		<input type="hidden" name="view" value="courses" />
		<input type="hidden" name="layout" value="<?php echo htmlentities($layout); ?>" />
		<input type="hidden" name="task" value="submit_filter" />
		<input type="hidden" name="controller" value="" />
		<input type="hidden" name="limitstart" value="0">
	</form>
</div>
<script>
tjlmsfilter.init();
</script>
