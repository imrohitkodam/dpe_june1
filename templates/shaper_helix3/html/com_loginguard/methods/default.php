<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var LoginGuardViewMethods $this */

?>
<div id="loginguard-methods-list" class="loginguard-methods-list">
	<div id="loginguard-methods-reset-container" class="m-0">
		<div id="loginguard-methods-reset-message" class="font-600">
            <?= Text::sprintf('COM_LOGINGUARD_LBL_LIST_STATUS', Text::_('COM_LOGINGUARD_LBL_LIST_STATUS_' . ($this->tfaActive ? 'ON' : 'OFF'))) ?>
        </div>
		<?php if ($this->tfaActive): ?>
		<div>
			<a href="<?= Route::_('index.php?option=com_loginguard&task=methods.disable&' . Session::getFormToken() . '=1' . ($this->returnURL ? '&returnurl=' . $this->escape(urlencode($this->returnURL)) : '') . '&user_id=' . $this->user->id) ?>"
			   class="btn btn-danger btn-sm">
				<?= Text::_('COM_LOGINGUARD_LBL_LIST_REMOVEALL'); ?>
			</a>
		</div>
		<?php endif; ?>
	</div>

	<div class="clearfix"></div>

	<?php if (!$this->isAdmin): ?>
	<div class="alert alert-info">		
		<h5 id="loginguard-methods-list-head">
			<?= Text::_('COM_LOGINGUARD_HEAD_LIST_PAGE'); ?>
		</h5>
		<div id="loginguard-methods-list-instructions">
			<p>
				<span class="icon icon-info"></span>
				<?= Text::_('COM_LOGINGUARD_LBL_LIST_INSTRUCTIONS'); ?>
			</p>
		</div>
	</div>	
	<?php endif; ?>
		

	<?php $this->setLayout('list'); echo $this->loadTemplate(); ?>
</div>