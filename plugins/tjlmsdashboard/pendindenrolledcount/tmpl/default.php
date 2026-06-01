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
?>

<div class="statbox pending-enrollment-count <?php echo $plg_data->size; ?>" >
	<div class="statbox-overlay" >
		<div class="pending-enrollment-label" >
			 <span><strong><?php echo Text::_('PLG_TJLMSDASHBOARD_PENDING_ENROLLED_COURSES_TOTALNUM'); ?></strong></span>
		</div>
		<div class="pending-enrollment-value">
			<?php echo !empty($totalpendingEnrollment) ? $totalpendingEnrollment : "0"; ?>
		</div>
	</div>
</div>

<style>
	.pending-enrollment-count .statbox-overlay
	{
		background:<?php echo $plg_data->background_color; ?> ;
		border-left: 6px solid <?php echo $plg_data->border_color;?>;
		color:<?php echo $plg_data->text_color;?>;"
	}

	.pending-enrollment-count .pending-enrollment-value
	{
		/*text-align: left;*/
		font-size: x-large;
	}

	.enrolled-courses-count .pending-enrollment-label
	{
		/*text-align: left;*/
	}

	.tj-dashboard .statbox .statbox-overlay
	{
		-webkit-border-radius:2px;
		border-radius:2px;
		padding:10px 4px 10px 10px;
	}

</style>
