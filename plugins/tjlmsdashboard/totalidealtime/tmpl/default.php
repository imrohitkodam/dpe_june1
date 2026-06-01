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

<div class="statbox total-ideal-time <?php echo $plg_data->size; ?>" >
	<div class="statbox-overlay" >
		<div class="ideal-time-label" >
			<span>
				<strong>
					<?php echo Text::_('PLG_TJLMSDASHBOARD_TOTAL_IDEAL_TIME_NUM'); ?>
				</strong>
			</span>
		</div>
		<div class="ideal-time-value">
		<?php
			$idealTime = (empty($totalIdealTime) ? '00 : 00 : 00' : $totalIdealTime);
			echo $idealTime;
			?>
		</div>
	</div>
</div>

<style>
	.total-ideal-time .statbox-overlay
	{
		background:<?php echo $plg_data->background_color; ?> ;
		border-left: 6px solid <?php echo $plg_data->border_color;?>;
		color:<?php echo $plg_data->text_color;?>;
	}

	.total-ideal-time .ideal-time-value
	{
		/*text-align: left;*/
		font-size: x-large;
	}

	.total-ideal-time .ideal-time-label
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
