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

$class['xsDeviceClass'] = $this->params->get('xsmall_device_col_class', 'col-xs-12');
$class['smDeviceClass'] = $this->params->get('small_device_col_class', 'col-sm-12');
$class['medDeviceClass'] = $this->params->get('medium_device_col_class', 'col-md-6');
$class['largeDeviceClass'] = $this->params->get('large_device_col_class', 'col-lg-6');
?>

<div class="tjlmsdashboard-discussions <?php echo implode(" ", $class); ?>">
	<div class="panel panel-default br-0">
		<div class="panel-heading">
			<span class="panel-heading__title">
				<span class="glyphicon glyphicon-stats" aria-hidden="true"></span>
				<span><?php echo Text::_('PLG_TJLMSDASHBOARD_GROUP_DISCUSSION_TITLE'); ?></span>
			</span>
		</div>
		<div class="panel-body p-10">

		<?php if (empty($groupDiscussion)): ?>
				<div class="alert alert-warning">
					<?php echo Text::_('PLG_TJLMSDASHBOARD_GROUP_DISCUSSION_NO_GROUPS'); ?>
				</div>
		<?php else: ?>
		<?php
			foreach ($groupDiscussion as $ind => $gd)
			{
				if($gd->title)
				{
			?>
			<div class="container-fluid">
				<div class="row">
					<?php if ($ind != 0): ?>
						<hr class="hr">
					<?php endif;?>

					<a href="<?php echo $gd->discussion_url; ?>" target="_blank"><?php echo $gd->title; ?></a>
				</div>
			</div>

			<?php
				}
			}
		?>
		<?php endif; ?>
		</div>
	</div>
</div>

