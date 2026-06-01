<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;

$class['xsDeviceClass'] = $this->params->get('xsmall_device_col_class', 'col-xs-6');
$class['smDeviceClass'] = $this->params->get('small_device_col_class', 'col-sm-4');
$class['medDeviceClass'] = $this->params->get('medium_device_col_class', 'col-md-2');
$class['largeDeviceClass'] = $this->params->get('large_device_col_class', 'col-lg-2');
?>

<div class="dashbs3 total-time-spent statbox text-truncate mb-10 pb-10 <?php echo implode(' ', $class); ?>" data-toggle="tooltip" title="<?php echo Text::_('PLG_TJLMSDASHBOARD_TOTAL_TIME_SPENT_NUM'); ?>">
	<div class="statbox__label text-truncate">
		<span>
			<?php echo Text::_('PLG_TJLMSDASHBOARD_TOTAL_TIME_SPENT_NUM'); ?>
		</span>
	</div>
	<div class="statbox__count font-600">
	<?php
		$timetaken = (empty($totalSpentTime) ? '00 : 00 : 00' : $totalSpentTime);
		echo $timetaken;
	?>
	</div>
</div>
