<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var LoginGuardViewCaptive $this */
/** @var LoginGuardModelCaptive $model */

$model           = $this->getModel();
$allowRememberMe = ComponentHelper::getParams('com_loginguard')->get('allow_rememberme', 1) == 1;
$isJ4            = version_compare(JVERSION, '3.999.999', 'gt');

?>
<div class="loginguard-captive card card-body">
	<div class="alert alert-info">
		<h3 id="loginguard-title" class="font-600">
			<?php if (!empty($this->renderOptions['help_url'])): ?>
				<span class="pull-right float-end">
			<a href="<?= $this->renderOptions['help_url'] ?>"
					class="btn btn-sm btn-secondary"
					target="_blank"
			>
				<span class="icon icon-question-sign"></span>
			</a>
			</span>
			<?php endif;?>
			<?php if (!empty($this->title)): ?>
				<?= $this->title ?> <small class="font-600"> &ndash;
			<?php endif; ?>
			<?php if (!$this->allowEntryBatching): ?>
				<?= $this->escape($this->record->title) ?>
			<?php else: ?>
				<?= $this->escape($this->getModel()->translateMethodName($this->record->method)) ?>
			<?php endif; ?>
			<?php if (!empty($this->title)): ?>
			</small>
			<?php endif; ?>
		</h3>

		<?php if ($this->renderOptions['pre_message']): ?>
			<div class="loginguard-captive-pre-message text-muted">
				<?= $this->renderOptions['pre_message'] ?>
			</div>
		<?php endif; ?>
	</div>
    

	<form action="<?= Route::_('index.php?option=com_loginguard&task=captive.validate&record_id=' . ((int) $this->record->id)) ?>"
			id="loginguard-captive-form"
			method="post"
			class="form-horizontal ucm-form-styling"
	>
		<?= HTMLHelper::_('form.token') ?>

	    <div id="loginguard-captive-form-method-fields">
		    <?php if ($this->renderOptions['field_type'] == 'custom'): ?>
			    <?= $this->renderOptions['html']; ?>
		    <?php else:
                $js = <<< JS
; // Fix broken third party Javascript...
window.addEventListener("DOMContentLoaded", function() {
    document.getElementById('loginGuardCode').focus();
});

JS;
		        $this->document->addScriptDeclaration($js);

            ?>
                <div class="<?= $isJ4 ? 'row mb-3' : 'control-group form-group'?>">
					<?php if ($this->renderOptions['label']): ?>
                    <label for="loginGuardCode" class="<?= $isJ4 ? 'col-sm-3 col-form-label' : 'col-sm-4' ?>">
	                    <?= $this->renderOptions['label'] ?>
                    </label>
					<?php endif; ?>
					<div class="<?= $isJ4 ? 'col-sm-9' : 'col-sm-5' ?> <?= $this->renderOptions['label'] ? '' : 'offset-sm-3' ?>">
						<input type="<?= $this->renderOptions['input_type'] ?>"
							   name="code"
							   value=""
							<?php if (!empty($this->renderOptions['placeholder'])): ?>
								placeholder="<?= $this->renderOptions['placeholder'] ?>"
							<?php endif; ?>
							   id="loginGuardCode"
							   class="form-control input-large"
						>
					</div>
                </div>

		    <?php endif;?>

		    <?php if (!empty($this->browserId) && $allowRememberMe): ?>
				<div id="loginguard-captive-form-remember-me"
						class="<?= $isJ4 ? 'row mb-3' : 'control-group form-group' ?>">
					<label for="loginguard-rememberme-yes" class="<?= $isJ4 ? 'col-sm-3 col-form-label' : 'col-sm-4' ?>">
					    <?= Text::_('JGLOBAL_REMEMBER_ME') ?>
					</label>
					<div class="<?= $isJ4 ? 'col-sm-9' : 'col-sm-8' ?>">
						<div class="loginguard-toggle" id="loginguard-rememberme-container">
							<input id="loginguard-rememberme-yes" type="radio" name="rememberme" value="1" checked />
							<label for="loginguard-rememberme-yes" class="green"><?= Text::_('JYES') ?></label>
							<input id="loginguard-rememberme-no" type="radio" name="rememberme" value="0" />
							<label for="loginguard-rememberme-no" class="red"><?= Text::_('JNO') ?></label>
						</div>
					</div>
				</div>
		    <?php endif;?>
        </div>

        <div id="loginguard-captive-form-standard-buttons" class="<?= $isJ4 ? 'row mb-3' : 'control-group form-group'?>">
			<div class="<?= $isJ4 ? 'col-sm-9 offset-sm-3' : 'col-sm-6 col-sm-offset-3' ?>">
				<div class="pull-right">
					<button class="btn btn-primary me-3"
							id="loginguard-captive-button-submit"
							style="<?= $this->renderOptions['hide_submit'] ? 'display: none' : '' ?>"
							type="submit">
						<span class="icon icon-rightarrow icon-arrow-right" aria-hidden="true"></span>
						<?= Text::_('COM_LOGINGUARD_LBL_VALIDATE'); ?>
					</button>

					<?php if ($this->isAdmin): ?>
						<a href="<?= Route::_('index.php?option=com_login&task=logout&' . Session::getFormToken() . '=1') ?>"
						class="btn btn-danger"
						id="loginguard-captive-button-logout">
							<span class="icon icon-lock" aria-hidden="true"></span>
							<?= Text::_('COM_LOGINGUARD_LBL_LOGOUT'); ?>
						</a>
					<?php else: ?>
						<a href="<?= Route::_('index.php?option=com_users&task=user.logout&' . Session::getFormToken() . '=1') ?>"
						class="btn btn-danger" id="loginguard-captive-button-logout">
							<span class="icon icon-lock" aria-hidden="true"></span>
							<?= Text::_('COM_LOGINGUARD_LBL_LOGOUT'); ?>
						</a>
					<?php endif; ?>
					<?php if (count($this->records) > 1): ?>
						<div id="loginguard-captive-form-choose-another" <?= $isJ4 ? 'class="my-3"' : 'style="margin-top: 1em"' ?>>
							<a href="<?= Route::_('index.php?option=com_loginguard&view=captive&task=select') ?>">
								<?= Text::_('COM_LOGINGUARD_LBL_USEDIFFERENTMETHOD'); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>
			</div>
        </div>
    </form>

	<?php if ($this->renderOptions['post_message']): ?>
        <div class="loginguard-captive-post-message">
	        <?= $this->renderOptions['post_message'] ?>
        </div>
	<?php endif; ?>

</div>