<?php
/**
 * @package     Shika
 * @subpackage  mod_lms_filter
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

$options['relative'] = true;
HTMLHelper::_('stylesheet', 'mod_lms_filter/style.css', $options);
HTMLHelper::_('script', 'mod_lms_filter/script.js', $options);

$app             = Factory::getApplication();
$layout          = $app->input->get('layout', '', 'STRING');
$filterClass     = '';
$input           = $app->input;
$courses_to_show = $input->get('courses_to_show', 'all', 'STRING');

?>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">
	<form name="adminForm" id="adminForm" class="form-validate" method="post" action="">

		<div id="filter-bar">
			<?php if ($params->get('search',1) == 1) : ?>

				<?php $filterClass = ($mod_filter->search) ? 'filterActive' : ''; ?>

				<div class="filter_search d-inline-block valign-top mb-10 col-xxs-12">

					 <div class="input-group <?php echo $filterClass;?>" data-id="filter-search">
						<input type="text" name="filter_search" id="filter_search"
						placeholder="<?php echo Text::_('MOD_LMS_FILTER_FILTER_SEARCH_DESC_COURSES'); ?>"
						value="<?php echo htmlspecialchars($mod_filter->search, ENT_COMPAT, 'UTF-8'); ?>"
						class="hasTooltip d-inline-block form-control" title="<?php echo Text::_('JSEARCH_FILTER'); ?>"/>

						<span class="d-table-cell valign-middle">
							<button type="submit" class="btn hasTooltip d-inline-block"
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

					$filterClass   = ($mod_filter->course_type != -1) ? 'filterActive' : '';
				?>

				<div data-id="filter-type" class="d-inline-block valign-top mb-10 <?php echo $filterClass; ?>">
						<?php
						echo HTMLHelper::_('select.genericlist', $typeoptions, "course_type", 'class="input-small"  size="1"
							onchange="this.form.submit();" name="course_type"',"value", "text",$mod_filter->course_type);
						?>
				</div>
			<?php endif; ?>

			<?php
			if ($params->get('category',1) == 1): ?>
				<?php
					$options   = array();
					$options[] = HTMLHelper::_('select.option', '0', Text::_('MOD_LMS_FILTER_SELECT_CATEGORY'));

					foreach($cats as $cat)
					{
						$options[] = HTMLHelper::_('select.option', $cat->value, $cat->text);
					}

					$filterClass = ($mod_filter->category_filter != 0) ? 'filterActive' : '';
				?>
				<div data-id="filter-category" class="d-inline-block valign-top mb-10 <?php echo $filterClass; ?>">
					<?php

					echo HTMLHelper::_('select.genericlist', $options, "course_cat", 'class="" size="1"
					onchange="this.form.submit();" name="category_filter"',"value", "text",$mod_filter->category_filter);
					?>
				</div>
			<?php endif; ?>

			<?php

			$resetClass="col-xxs-12";

			if ($params->get('creator', 0) == 1): ?>
				<?php

					$resetClass = "col-xxs-6";

					$courseCreatorsOption   = array();
					$courseCreatorsOption[] = HTMLHelper::_('select.option', '0', Text::_('MOD_LMS_FILTER_SELECT_COURSE_CREATOR'));
					$courseCreatorsOption   = array_merge($courseCreatorsOption, $courseCreators);

					$filterClass            = ($mod_filter->creator_filter != 0) ? 'filterActive' : '';
				?>
				<div data-id="filter-author" class="d-inline-block valign-top mb-10 <?php echo $resetClass;?> <?php echo $filterClass; ?>">
						<?php
							echo HTMLHelper::_('select.genericlist', $courseCreatorsOption, "creator_filter", 'class="input-medium" "size="1"
									onchange="this.form.submit();"', "value", "text", $mod_filter->creator_filter);
						?>
				</div>
			<?php endif; ?>

			<?php
			if ($params->get('course_status', 0) == 1):
					$resetClass          ="col-xxs-6";
					$courseStatusOptions = array();
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

					$filterClass           = ($mod_filter->course_status != '') ? 'filterActive' : '';
				?>

				<div data-id="filter-course-status" class="d-inline-block valign-top mb-10 <?php echo $resetClass;?> <?php echo $filterClass; ?>">
						<?php
						echo HTMLHelper::_('select.genericlist', $courseStatusOptions, "course_status", 'class="input-medium"  size="1"
							onchange="this.form.submit();" name="course_status"',"value", "text",$mod_filter->course_status);
						?>
				</div>
			<?php endif; ?>

			<?php
			if ($params->get('filter_tag', 0) == 1): ?>
				<?php
					$options   = array();
					$options[] = HTMLHelper::_('select.option','0',Text::_('MOD_LMS_FILTER_SELECT_TAG'));

					foreach ($tags as $tag)
					{
						$options[] = HTMLHelper::_('select.option', $tag->id, $tag->title);
					}

					$filterClass = ($mod_filter->filter_tag != 0) ? 'filterActive' : '';
				?>
				<div data-id="filter-tag" class="d-inline-block valign-top mb-10 <?php echo $filterClass; ?>">
					<?php
					echo HTMLHelper::_('select.genericlist', $options, "filter_tag", 'class="" size="1"
					onchange="this.form.submit();" name="filter_tag"',"value", "text",$mod_filter->course_tag_filter);
					?>
				</div>
			<?php endif; ?>

				<div class="filter_search d-inline-block valign-top mb-10 text-center <?php echo $resetClass;?>">
					<button type="button" class="btn d-inline-block hasTooltip btn-primary"
									title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"
									onclick="tjlmsfilter.reset();this.form.submit();">
						<i class="fa fa-close"></i>
					</button>
				</div>

				<?php if ($params->get('pagination', 1) == 1):
					JLoader::import('joomla.application.component.model');
					JLoader::import('courses', JPATH_SITE . '/components/com_tjlms/models');
					$coursesModel             = BaseDatabaseModel::getInstance('courses', 'TjlmsModel');
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
	</form>
</div>
<script>
tjlmsfilter.init();
</script>
