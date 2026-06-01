<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;

$document           = Factory::getDocument();
$comparams          = ComponentHelper::getParams('com_tjlms');
$currency           = $comparams->get('currency', '');
$allow_paid_courses = $comparams->get('allow_paid_courses', '0');

$layoutToLoad       = $this->menuparams->get('layout_to_load', 'masonrypin');
$catModulePos       = $this->menuparams->get('cat_pos_alignment', 'left');

// Get selected layout of courses.
$setLayout = str_replace(':', '', strstr($this->menuparams->get('courses_layout'), ':'));

if ($setLayout == '' || $setLayout == 'default')
{
	$setLayout = 'course';
}

foreach ($this->course_cats as $ind => $cat)
{
	if (isset($course_cat) && !empty($course_cat))
	{
		if ($course_cat == $cat->value)
		{
			$catclass   = "class='catvisited'";
			$active_cat = $cat->text;
		}
	}
	elseif (isset($filter_menu_category))
	{
		if ($filter_menu_category == $cat->value)
		{
			$catclass   = "class='catvisited'";
			$active_cat = $cat->text;
		}
	}
}

$category_listHTML = $filters = $active_cat = $pinclass = $coursesContainerClass = '';
$tjlmsCoursesDiv = "col-sm-12";

$pinclass = '';

if ($layoutToLoad == 'fixedheight')
{
	$coursesContainerClass  = "row flex-row";
	$pinclass = $this->menuparams->get('xsmall_device_pin_class') . ' '.
				$this->menuparams->get('small_device_pin_class') . ' '.
				$this->menuparams->get('medium_device_pin_class') . ' '.
				$this->menuparams->get('large_device_pin_class') . ' ';
}

if ($layoutToLoad == 'fixedheight' || $layoutToLoad == 'masonrypin')
{
	$layout = new FileLayout($setLayout . 'pin');
}
else
{
	$coursesContainerClass  = "tjlms-courses-list";
	$layout = new FileLayout($setLayout . 'list');
}

/*Get the categories and filter moduels*/
$renderer	= $document->loadRenderer('module');

/*if modules published on tjlms_category are set to show*/
if ($catModulePos != 'none')
{
	$modules = ModuleHelper::getModules('tjlms_category');
	ob_start();

	foreach ($modules as $module)
	{
		$attribs['style'] = 'xhtml';
		$category_listHTML .=  $renderer->render($module, $attribs);
	}

	ob_get_clean();
	$tjlmsCoursesDiv = ($category_listHTML) ? 'col-sm-9' : 'col-sm-12';
}

$pageHeading = Text::_("COM_TJLMS_ALL_COURSES");

if ($this->courses_to_show == 'liked'):
	$pageHeading = Text::_("COM_TJLMS_YOUR_LIKED_COURSES");
elseif ($this->courses_to_show == 'enrolled'):
	$pageHeading = Text::_("COM_TJLMS_MY_ENROLLED_COURSES");
elseif ($this->courses_to_show == 'recommended'):
	$pageHeading = Text::_("COM_TJLMS_RECOMMENDED_COURSE");
endif;

?>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?> com_tjlms_content tjBs3 container-fluid">

		<div class="row">
			<div class="col-xs-12">
				<?php $colon = ($active_cat) ? ' : ' : '';?>
				<h2><?php echo (!empty($defaultclass))? $pageHeading: $pageHeading . $colon .$active_cat; ?></h2>
			</div>
		</div>

		<hr class="row visible-xs hr hr-condensed mt-0 mb-0" />

		<div class="row visible-xs pt-5 pb-5 mb-5 bg-eee" data-identifier="tjlms-filters-menu">
			<div class="col-xs-6">
				<span class="label label-info br-0">
				<?php echo ($this->itemsCount == 1) ? Text::sprintf("COM_TJLMS_TOTAL_COURSES_LABEL", $this->itemsCount): Text::sprintf("COM_TJLMS_TOTAL_COURSES_LABEL_N", $this->itemsCount)?>
				</span>
			</div>
			<div class="col-xs-6 text-right">
				<span onclick="tjlmsfilter.toggle(this);">
					<?php echo Text::_("COM_TJLMS_COURSES_FILER");?>
					<i class="fa fa-angle-right" aria-hidden="true"></i>
				</span>
			</div>
		</div>

		<div class="row tjlms-filters">
			<div class="col-xs-12 p-0">
				<?php
					echo $this->loadTemplate("filters");
				?>
			</div>
		</div>

		<div class="row">
			<?php if($category_listHTML && $catModulePos == 'left')
				{ ?>
					<div class="col-sm-3 hidden-xs">
						<!-- for category list-->
						<?php echo $category_listHTML; ?>
					</div>
		<?php   } ?>

			<div class="<?php echo $tjlmsCoursesDiv;?> tjlms-courses">

				<?php
					$cnt = count($courses = $this->items);

					if (empty($courses))
					{
						echo '<div class="alert alert-info">';

						if (!$this->ol_user->id)
						{
								echo Text::_("COM_TJLMS_NO_DATA_FOR_GUEST");
						}
						else if($this->ifuseradmin)
						{
								echo Text::_("COM_TJLMS_NO_COURSE");
						}
						else if($this->ol_user->id)
						{
								echo Text::_("COM_TJLMS_NO_COURES_FOR_USERACCESS");
						}
						echo '</div>';
					}
				?>
				<div id="tjlms_pin_container" class="<?php echo $coursesContainerClass;?>">
					<?php
						if (!empty($courses))
						{
							foreach($courses as $data)
							{
								$checkPrerequisiteCourseStatus = true;

								if (PluginHelper::isEnabled('tjlms', 'courseprerequisite'))
								{
									PluginHelper::importPlugin('tjlms');
									$checkPrerequisiteCourseStatus = Factory::getApplication()->triggerEvent('onCheckPrerequisiteCourseStatus', array($data->id, $this->ol_user->id));
									$checkPrerequisiteCourseStatus = $checkPrerequisiteCourseStatus[0];
								}

								$data = (array)$data;
								$data['pinclass'] = $pinclass;
								$data['checkPrerequisiteCourseStatus'] = $checkPrerequisiteCourseStatus;
								echo $layout->render($data);
							}
						}
					?>
				</div>
					<?php
				/*}
				else
				{ ?>
					<div id="tjlms_courses_list">
						<?php echo $this->loadTemplate('list');?>
					</div>
				<?php
				}*/
				?>
			</div>

	<?php if ($category_listHTML && $catModulePos == 'right')
		  {
		?>
				<div class="col-sm-3 hidden-xs">
					<!-- for category list-->
					<?php echo $category_listHTML; ?>
				</div>
	<?php } ?>
		</div>

		<input type="hidden" name="option" value="com_tjlms" />
		<input type="hidden" name="view" value="courses" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="controller" value="" />
		<input type="hidden" name="filter_order" value="<?php echo $this->state->get('list.ordering'); ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $this->state->get('list.direction'); ?>" />

		<div class="pager">
			<?php echo $this->pagination->getPagesLinks(); ?>
		</div>
</div>

<!-- setup pin layout script-->
<?php

if ($layoutToLoad == 'masonrypin')
{
	$random_container = 'tjlms_pin_container';
	HTMLHelper::_('script', 'media/com_tjlms/vendors/masonry.pkgd.min.js');

	// Get pin width
	$pin_width = $this->menuparams->get('pin_width', '220');

	// Get pin padding
	$pin_padding = $this->menuparams->get('pin_padding', '3');

	// Calulate columnWidth (columnWidth = pin_width+pin_padding)
	$columnWidth = $pin_width + $pin_padding;
?>

<style type="text/css">
@media only screen and (min-width: 481px){
.tjlmspin { width: <?php echo $pin_width . 'px'; ?> !important; }
}
</style>

<script type="text/javascript">
if(jQuery(window).width() > 767)
{
	tjlms.courses.init('tjlms_pin_container', <?php echo (int) $columnWidth; ?>)
}
</script>

<?php
}
