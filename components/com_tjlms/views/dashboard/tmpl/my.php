<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
HTMLHelper::_('bootstrap.renderModal', 'a.tjmodal');


$options['relative'] = true;
HTMLHelper::stylesheet('com_tjlms/tjdashboard.css', $options);

?>

<?php if (!$this->userid):	?>

	<div class="alert alert-warning">
		<?php echo Text::_('COM_TJLMS_LOGIN_MESSAGE'); ?>
	</div>

	<?php return false;	?>

<?php endif; ?>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?> tj-dashboard com_tjlms_content tjBs3">

	<div class="container-fluid">
		<div class="row">
			<div class="tj-dashboard__title"><h2><?php echo Text::_('COM_TJLMS_DASHBOARD'); ?></h2></div>
		</div>
	</div>
	<div class="container-fluid statbox-items">
		<div class="row">

			<?php $checkSpanCnt = 0;?>

			<?php foreach ($this->dashboardData as $index => $eachBlock):	?>
				<?php if (isset($eachBlock->html[0])):	?>
					<?php echo $eachBlock->html[0];?>
				<?php endif;	?>
			<?php endforeach;	?>

		</div>
	</div>
</div>
