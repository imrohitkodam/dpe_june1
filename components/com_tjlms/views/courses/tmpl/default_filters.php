<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

$app         = Factory::getApplication();
$input       = $app->input;
$layout      = $input->get('layout', '', 'STRING');
$filterClass = '';

$courses_to_show = $input->get('courses_to_show', 'all', 'STRING');
?>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?> pt-20">
	<form name="adminForm" id="adminForm" class="form-validate custom-form-style" method="post">
		<div class="col-xs-12 col-sm-9 pd-0">
			<button type="button" class="btn hasTooltip btn-primary hidden-md hidden-lg mr-5 pull-right mb-5 d-inline-block" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>" onclick="tjlmsfilter.reset();this.form.submit();">
				<i class="fa fa-close mr-5"></i>
			</button>
			<div id="filter-bar">
				<?php if ($this->menuparams->get('search') == 1) : ?>
					<?php $filterClass = ($this->filterstates->search) ? 'filterActive' : ''; ?>

					<div class="filter_search d-inline-block valign-top mb-10 col-xxs-12 pr-5">

						 <div class="input-group <?php echo $filterClass;?> pull-left" data-id="filter-search">
							<input type="text" name="filter_search" id="filter_search"
							placeholder="<?php echo Text::_('COM_TJLMS_FILTER_FILTER_SEARCH_DESC_COURSES'); ?>"
							value="<?php echo htmlspecialchars($this->filterstates->search, ENT_COMPAT, 'UTF-8'); ?>"
							class="form-control hasTooltip d-inline-block" title="<?php echo Text::_('JSEARCH_FILTER'); ?>"/>

							<span class="d-table-cell valign-middle filter-search-btn">
								<button type="submit" class="btn hasTooltip btn-custom-small d-inline-block"
								title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
									<i class="fa fa-search"></i>
								</button>
							</span>
						</div><!-- /input-group -->
					</div>
				<?php endif; ?>

				<?php
				if ($this->menuparams->get('showfilters') != 'none')
				{
					$typeoptions   = array();
					$typeoptions[] = HTMLHelper::_('select.option', '-1', Text::_('COM_TJLMS_FILTER_ALL_COURSE_TYPE'));
					$typeoptions[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_FILTER_ALL_COURSE_TYPE_FREE'));
					$typeoptions[] = HTMLHelper::_('select.option', '1', Text::_('COM_TJLMS_FILTER_ALL_COURSE_TYPE_PAID'));

					$courseTypeGenericlist = HTMLHelper::_('select.genericlist', $typeoptions, "course_type", 'class="form-control input-small"  size="1"
								onchange="this.form.submit();" name="course_type"', "value",
								"text", $this->filterstates->course_type
							);

					$options   = array();
					$options[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_FILTER_SELECT_CATEGORY'));

					usort($this->course_cats, function($a, $b) {return strcmp($a->text, $b->text);});

					foreach ($this->course_cats as $cat)
					{
						$options[] = HTMLHelper::_('select.option', $cat->value, $cat->text);
					}

					$courseCategoryGenericlist = HTMLHelper::_('select.genericlist', $options, "course_cat", 'class="form-control" size="1"
						onchange="this.form.submit();" name="category_filter"', "value", "text", $this->filterstates->category_filter
							);

					$courseCreatorsOption   = array();
					$courseCreatorsOption[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_FILTER_SELECT_COURSE_CREATOR'));
					$courseCreatorsOption = array_merge($courseCreatorsOption, $this->courseCreators);

					$courseCreatorGenericlist = HTMLHelper::_('select.genericlist', $courseCreatorsOption, "creator_filter", 'class="form-control input-medium" size="1" onchange="this.form.submit();"',
						"value", "text", $this->filterstates->creator_filter
						);

					$courseStatusOptions = array();

					if ($courses_to_show == 'enrolled')
					{
						$courseStatusOptions[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_FILTER_ENROLL_COURSE_STATUS'));
					}
					else
					{
						$courseStatusOptions[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_FILTER_ALL_COURSE_STATUS'));
						$courseStatusOptions[] = HTMLHelper::_('select.option', 'enrolledcourses', Text::_('COM_TJLMS_FILTER_ENROLL_COURSE_STATUS'));
					}

					$courseStatusOptions[] = HTMLHelper::_('select.option', 'completedcourses', Text::_('COM_TJLMS_FILTER_COMPLETE_COURSE_STATUS'));
					$courseStatusOptions[] = HTMLHelper::_('select.option', 'incompletedcourses', Text::_('COM_TJLMS_FILTER_INCOMPLETE_COURSE_STATUS'));

					$courseStatusGenericlist = HTMLHelper::_('select.genericlist', $courseStatusOptions, "course_status", 'class="form-control input-medium"
						size="1"
						onchange="this.form.submit();" name="course_status"', "value", "text", $this->filterstates->course_status
								);

					$options   = array();
					$options[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_FILTER_SELECT_TAG'));

					usort($this->tags, function($a, $b) {return strcmp($a->title, $b->title);});

					foreach ($this->tags as $tag)
					{
						$options[] = HTMLHelper::_('select.option', $tag->id, $tag->title);
					}

					$courseTagsGenericlist = HTMLHelper::_('select.genericlist', $options, "filter_tag", 'class="form-control input-medium" size="1"
						onchange="this.form.submit();" name="filter_tag"', "value", "text", $this->filterstates->course_tag_filter
							);

					if ($this->menuparams->get('course_type') == 'basic')
					{
						$filterClass = ($this->filterstates->course_type != -1) ? 'filterActive' : '';
						?>
						<div data-id="filter-type" class="d-inline-block valign-top mb-10 col-xxs-12 <?php echo $filterClass; ?> px-5">
							<?php
								echo $courseTypeGenericlist;
							?>
						</div>
						<?php
					}

					if ($this->menuparams->get('category') == 'basic')
					{
						$filterClass = ($this->filterstates->category_filter != 0) ? 'filterActive' : '';
						?>
						<div data-id="filter-category" class="d-inline-block valign-top mb-10 col-xxs-12 <?php echo $filterClass; ?> ">
							<?php
								echo $courseCategoryGenericlist;
							?>
						</div>
						<?php
					}

					if ($this->menuparams->get('creator') == 'basic')
					{
						$filterClass = ($this->filterstates->creator_filter != 0) ? 'filterActive' : '';
						$resetClass = "col-xxs-12";
						?>
						<div data-id="filter-author" class="d-inline-block valign-top mb-10 <?php echo $resetClass;?>
						<?php echo $filterClass; ?> px-5">
							<?php
								echo $courseCreatorGenericlist;
								?>
						</div>
						<?php
					}

				?>
				<?php
				if ($this->menuparams->get('filter_tag') == 'basic' && $this->tags)
				{
					$filterClass = ($this->filterstates->course_tag_filter != 0) ? 'filterActive' : '';
					?>
					<div data-id="filter-tag" class="d-inline-block valign-top mb-10 <?php echo $filterClass; ?>">
						<?php
							echo $courseTagsGenericlist;
						?>
					</div>
					<?php
				}

				if ($this->menuparams->get('filter_customfields') == 'basic')
				{
					foreach ($this->courseFields as $key => $courseField)
					{
						$filterClass = ($this->filterstates->course_fields[$courseField['name']] != 0) ? 'filterActive' : '';
						$selectName = "course_fields[" . $courseField['name'] . "]";
						?>
						<div data-id="filter-<?php echo $courseField['name'] ?>" class="d-inline-block valign-top mb-10 col-xxs-12 <?php echo $filterClass; ?> px-5">
							<?php

							echo HTMLHelper::_('select.genericlist', $courseField['options'], $selectName, 'class="course-fields form-control input-medium" size="1"
							onchange="this.form.submit();" name=' . $selectName, "value", "text", $this->filterstates->course_fields[$courseField['name']]
								);
							?>
						</div>
						<?php
					}
				}

				if ($this->menuparams->get('course_status') == 'basic')
				{
					$filterClass = ($this->filterstates->course_status != '') ? 'filterActive' : '';
					$resetClass = "col-xxs-12";
					?>
					<div data-id="filter-course-status" class="d-inline-block valign-top mb-10 <?php echo $resetClass;?> <?php echo $filterClass; ?> ">
						<?php
							echo $courseStatusGenericlist;
						?>
					</div>
					<?php
				}

				?>

				<div class="courses__filter hide" id="displayFilterText">
				<?php
					if ($this->menuparams->get('category') === 'advanced')
					{
						$filterClass = ($this->filterstates->category_filter != 0) ? 'filterActive' : '';
							?>
						<div data-id="filter-category" class="d-inline-block valign-top mb-10 col-xxs-12 <?php echo $filterClass; ?> px-5">
							<?php
								echo $courseCategoryGenericlist;
							?>
						</div>
						<?php
					}

					if ($this->menuparams->get('filter_tag') == 'advanced' && $this->tags)
					{
						$filterClass = ($this->filterstates->course_tag_filter != 0) ? 'filterActive' : '';
						?>
							<div data-id="filter-tag" class="d-inline-block valign-top mb-10 <?php echo $filterClass; ?>">
								<?php
									echo $courseTagsGenericlist;
								?>
							</div>
						<?php
					}

					if ($this->menuparams->get('course_status') === 'advanced')
					{
						$filterClass = ($this->filterstates->course_status != '') ? 'filterActive' : '';
						$resetClass = "col-xxs-12";
						?>
						<div data-id="filter-course-status" class="d-inline-block valign-top mb-10 <?php echo $resetClass;?> <?php echo $filterClass; ?> px-5">
							<?php
								echo $courseStatusGenericlist;
							?>
						</div>
						<?php
					}

					if ($this->menuparams->get('course_type') === 'advanced')
					{
						$filterClass = ($this->filterstates->course_type != -1) ? 'filterActive' : '';
						?>
							<div data-id="filter-type" class="d-inline-block valign-top mb-10 col-xxs-12 <?php echo $filterClass; ?> px-5">
								<?php
									echo $courseTypeGenericlist;
								?>
							</div>
						<?php
					}

					if ($this->menuparams->get('creator') === 'advanced')
					{
						$filterClass = ($this->filterstates->creator_filter != 0) ? 'filterActive' : '';
						$resetClass = "col-xxs-12";
						?>
						<div data-id="filter-author" class="d-inline-block valign-top mb-10 <?php echo $resetClass;?>
						<?php echo $filterClass; ?> px-5">
							<?php
								echo $courseCreatorGenericlist;
								?>
						</div>
						<?php
					}

					if ($this->menuparams->get('filter_customfields') === 'advanced')
					{
						foreach ($this->courseFields as $key => $courseField)
						{
							$filterClass = ($this->filterstates->course_fields[$courseField['name']] != 0) ? 'filterActive' : '';
							$selectName = "course_fields[" . $courseField['name'] . "]";
							?>
							<div data-id="filter-<?php echo $courseField['name'] ?>" class="d-inline-block valign-top mb-10 col-xxs-12 <?php echo $filterClass; ?> px-5">
								<?php

								echo HTMLHelper::_('select.genericlist', $courseField['options'], $selectName, 'class="course-fields form-control input-medium" size="1"
								onchange="this.form.submit();" name=' . $selectName, "value", "text", $this->filterstates->course_fields[$courseField['name']]
									);
								?>
							</div>
							<?php
						}
					}
					?>
				</div>
				<?php
				}
				?>
			</div>
		</div>

		<div class="col-xs-12 col-sm-3 text-right">
			<?php
				if ($this->menuparams->get('showfilters') === 'advanced' || $this->menuparams->get('showfilters') === 'both') : ?>
					<div class="d-inline-block valign-top mb-10 text-center pr-5 hidden-xs hidden-sm">
						<a class="" id="displayFilter" href="javascript:void(0)" onclick="tjlmsfilter.toggleDiv('displayFilterText');" title="<?php echo Text::_('COM_TJLMS_FILTER_COURSE')?>">
							<i class="fa fa-filter" aria-hidden="true"></i>
						</a>
					</div>
			<?php endif;?>
			<div class="filter_search d-inline-block valign-top mb-10 text-center <?php echo $resetClass;?> pr-5 hidden-xs hidden-sm">
				<button type="button" class="btn hasTooltip btn-primary d-inline-block" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"
					onclick="tjlmsfilter.reset();this.form.submit();">
					<i class="fa fa-close"></i>
				</button>
			</div>

			<?php if ($this->menuparams->get('pagination', 1) == 1):
				?>
				<div class="d-inline-block valign-top">
					<div class="hidden-xs btn-group">
						<?php echo $this->pagination->getLimitBox(); ?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<div class="clearfix"></div>
	</form>
</div>
<script type="text/javascript">
	tjlmsfilter.init();
</script>
