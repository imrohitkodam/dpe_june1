<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_coursedisplay
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;

$layout = new FileLayout('coursepin', JPATH_ROOT . '/plugins/emailalerts/jma_coursedisplay/jma_coursedisplay/layouts');
?>

<h2 class="subTitle">
	<?php echo JText::_($pluginParams->get('plugintitle')); ?>
</h2>
<?php
if ($this->params->get('show_in_progress_courses') && !empty($list->inprogressCourseList))
{
	?>
	<h2 class="subTitle">
		<?php echo Text::_('PLG_JMA_CD_IN_PROGRESS_TITLE'); ?>
	</h2>

	<?php
		$data = $list->inprogressCourseList;
		echo $layout->render($data);
}

if ($this->params->get('show_recommended_courses') && !empty($list->recommendedCourseList))
{
	?>
	<h2 class="subTitle">
		<?php echo Text::_('PLG_JMA_CD_RECOMMENDED_TITLE'); ?>
	</h2>

	<?php
		$data = $list->recommendedCourseList;
		echo $layout->render($data);
}

if ($this->params->get('show_recently_added_courses') && !empty($list->recentlyAddedCourseList))
{
	?>
	<h2 class="subTitle">
		<?php echo Text::_('PLG_JMA_CD_RECENTLY_ADDED_TITLE'); ?>
	</h2>

	<?php
		$data = $list->recentlyAddedCourseList;
		echo $layout->render($data);
}

if ($this->params->get('show_most_popular_courses') && !empty($list->mostPopularCourseList))
{
	?>
	<h2 class="subTitle">
		<?php echo Text::_('PLG_JMA_CD_MOST_POPULAR_TITLE'); ?>
	</h2>
	<?php
		$data = $list->mostPopularCourseList;
		echo $layout->render($data);
}
