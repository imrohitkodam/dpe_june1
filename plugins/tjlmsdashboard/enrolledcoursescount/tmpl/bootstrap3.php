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
defined('_JEXEC') or die('Restricted access');
jimport('techjoomla.common');

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

$class['xsDeviceClass'] = $this->params->get('xsmall_device_col_class', 'col-xs-6');
$class['smDeviceClass'] = $this->params->get('small_device_col_class', 'col-sm-4');
$class['medDeviceClass'] = $this->params->get('medium_device_col_class', 'col-md-2');
$class['largeDeviceClass'] = $this->params->get('large_device_col_class', 'col-lg-2');
?>

<div class="dashbs3 enrolled-courses-count statbox text-truncate mb-10 pb-10 <?php echo implode(' ', $class); ?>" data-toggle="tooltip" title="<?php echo Text::_('PLG_TJLMSDASHBOARD_ENROLLED_COURSES_TOTALNUM'); ?>">
	<div class="statbox__label text-truncate">
		 <span><?php echo Text::_('PLG_TJLMSDASHBOARD_ENROLLED_COURSES_TOTALNUM'); ?></span>
	</div>
	<div class="statbox__count font-600">
		<?php if(!empty($totalEnrolledCourses)):

			$techjoomlacommon = new TechjoomlaCommon;
			$menuItemID       = $techjoomlacommon->getItemId('index.php?option=com_tjlms&view=courses');

			$urlcourses = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=courses&course_status=enrolledcourses&Itemid='. $menuItemID, false);
			?>

		<a href="<?php echo $urlcourses; ?>" id="testing">
			<?php echo $totalEnrolledCourses;?>
		</a>
		<?php else:?>
			<?php  echo "0";?>
		<?php endif;?>
	</div>
</div>
