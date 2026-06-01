<?php
/**
 * @package     LMS_Shika
 * @subpackage  mod_lms_categorylist
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.techjoomla.com
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$options['relative'] = true;
JHtml::_('stylesheet', 'mod_lms_categorylist/style.css', $options);
JHtml::_('script', 'mod_lms_categorylist/script.js', $options);

//$categories = $modlmscategorylistHelper->getCategories($show_courses_from_cat);

$leastLevel = 1;
foreach ($categories as $index => $cat)
{
	$leastLevel = ($leastLevel < $cat->level) ? $leastLevel : $cat->level;
}

$courses_to_show = $app->input->get('courses_to_show', 'all', 'STRING');
$coursesUrl = 'index.php?option=com_tjlms&view=courses&courses_to_show=' . $courses_to_show;

?>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?> tjlms-categories panel panel-default br-0 tjBs3 mt-3">
	<div class="panel-heading border">
		<span class="panel-heading__title">
			<i class="fa fa-th fa-lg"></i>
			<span class="course_block_title"><?php echo Text::_("MOD_LMS_CATEGORYLIST_FILTER_CAMP_CAT"); ?></span>
		</span>
	</div>
	<ul class="list-unstyled list-group ml-0">

	<?php if (!$show_courses_from_cat) :?>

		<li class="list-group-item d-block" data-level='1'>
			<?php
			$allCoursesUrl = $comtjlmsHelper->tjlmsRoute($coursesUrl . '&course_cat=-1');
			?>

			<a href="<?php echo $allCoursesUrl; ?>" class="<?php echo ($activeCat) ? 'text-muted' : ''; ?>">
				<span>
					<?php
						$pageHeading = Text::_("MOD_LMS_CATEGORYLIST_ALL_COURSES");

						if ($courses_to_show == 'liked'):
							$pageHeading = Text::_("MOD_LMS_CATEGORYLIST_MY_LIKED_COURSES");
						endif;
						if ($courses_to_show == 'enrolled'):
							$pageHeading = Text::_("MOD_LMS_CATEGORYLIST_MY_ENROLLED_COURSES");
						endif;
						if ($courses_to_show == 'recommended'):
							$pageHeading = Text::_("MOD_LMS_CATEGORYLIST_MY_RECOMMENDED_COURSES");
						endif;

						echo $pageHeading;
					?>
				</span>
			</a>
		</li>

	<?php endif;?>
		<?php foreach ($categories as $ind => $cat): ?>

		<?php

			$cat_url = $comtjlmsHelper->tjlmsRoute($coursesUrl . '&course_cat=' . $ind);

			$li_class = ($cat->haschlid > 0) ? " has-children" : "";

			if ($cat->level == $leastLevel)
			{
				$li_class .= " d-block";
				$li_class .= ($cat->open) ? " open  d-block" : "";
			}
			else
			{
				$li_class .= ($activeCat == $ind || $show_courses_from_cat == $ind) ? " d-block" : "";
				$li_class .= ($cat->open) ? " open  d-block" : "";
				$li_class .= (isset($categories[$cat->parent_id]) && $categories[$cat->parent_id]->open) ? " d-block" : " ";
			}
		?>

		<li class="list-group-item <?php echo $li_class;?>" data-id="<?php echo $cat->id;?>" data-level="<?php echo $cat->level;?>" data-parent="<?php echo $cat->parent_id;?>">

			<?php
			//~ $cat_url = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&task=courses.setModelVariables&courses_to_show=all&course_cat=' . $ind);?>

			<a href="<?php echo $cat_url ?>" class="<?php echo ($activeCat == $ind) ? 'd-block' : 'text-muted'; ?>">
				<span>

					<?php
						$repeat = ($cat->level - 1 >= 0) ? $cat->level - 1 : 0;
						$cat->title = str_repeat('- ', $repeat) . $cat->title;
					?>

					<?php echo $cat->title; ?>
				</span>
			</a>

			<?php if ($cat->haschlid > 0): ?>
				<i class="fa fa-chevron-right" aria-hidden="true" onclick="showChildCats(this)"></i>
			<?php endif;?>
		</li>

		<?php endforeach;?>
	</ul>
</div>
