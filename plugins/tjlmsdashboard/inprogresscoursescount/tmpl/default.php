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


<div class="statbox inprogress-count <?php echo $plg_data->size; ?>" >
	<div class="statbox-overlay" >
		<div class="enrolled-courses-label" >
			 <span><strong><?php echo Text::_('PLG_TJLMSDASHBOARD_INPROGRESS_COURSES_TOTALNUM'); ?></strong></span>
		</div>
		<div class="enrolled-courses-value">
			<?php echo !empty($inprogressCourses) ? $inprogressCourses : "0"; ?>
		</div>
	</div>
</div>
<style>
	.inprogress-count .statbox-overlay
	{
		background:<?php echo $plg_data->background_color; ?> ;
		border-left: 6px solid <?php echo $plg_data->border_color;?>;
		color:<?php echo $plg_data->text_color;?>;"
	}

	.inprogress-count .enrolled-courses-value
	{
		/*text-align: left;*/
		font-size: x-large;
	}

	.inprogress-count .enrolled-courses-label
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
