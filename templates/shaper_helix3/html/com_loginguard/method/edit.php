<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var  LoginGuardViewMethod  $this */

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

$cancelURL = Route::_('index.php?option=com_loginguard&task=methods.display&user_id=' . $this->user->id);

if (!empty($this->returnURL))
{
	$cancelURL = $this->escape(base64_decode($this->returnURL));
}

$recordId = (int) $this->record->id ?? 0;
$method   = $this->record->method ?? $this->getModel()->getState('method');
$userId   = (int) $this->user->id ?? 0;
$isJ4     = version_compare(JVERSION, '3.999.999', 'gt');
?>
<div class="card card-body">
	<?php if (!empty($this->title)): ?>
		<?php if (!empty($this->renderOptions['help_url'])): ?>
			<span class="pull-right float-end">
				<a href="<?= $this->renderOptions['help_url'] ?>"
				class="btn btn-sm btn-small btn-default btn-inverse btn-dark"
				target="_blank"
				>
					<span class="icon icon-question-sign"></span>
				</a>
			</span>
		<?php endif;?>
		<h3 id="loginguard-method-edit-head" class="font-600">
			<?= Text::_($this->title) ?>
		</h3>
		<hr class="my-10">
	<?php endif; ?>
	<form action="<?= Route::_(sprintf("index.php?option=com_loginguard&task=method.save&id=%d&method=%s&user_id=%d", $recordId, $method, $userId)) ?>"
		  class="form form-horizontal ucm-form-styling" id="loginguard-method-edit" method="post">
		<?= HTMLHelper::_('form.token') ?>
		<?php if (!empty($this->returnURL)): ?>
		<input type="hidden" name="returnurl" value="<?= $this->escape($this->returnURL) ?>">
		<?php endif; ?>

		<?php if (!empty($this->renderOptions['hidden_data'])): ?>
		<?php foreach ($this->renderOptions['hidden_data'] as $key => $value): ?>
		<input type="hidden" name="<?= $this->escape($key) ?>" value="<?= $this->escape($value) ?>">
		<?php endforeach; ?>
		<?php endif; ?>

		

		<div class="<?= $isJ4 ? 'row mb-3' : 'control-group form-group row'?>">
			<label class="<?= $isJ4 ? 'col-sm-6 col-form-label' : 'col-sm-4' ?> hasTooltip"
				for="loginguard-method-edit-title"
				title="<?= $this->escape(Text::_('COM_LOGINGUARD_LBL_EDIT_FIELD_TITLE_DESC')) ?>">
				<?= Text::_('COM_LOGINGUARD_LBL_EDIT_FIELD_TITLE'); ?>
			</label>
			<div class="<?= $isJ4 ? 'col-sm-9' : 'col-sm-5' ?>">
				<input type="text"
						class="<?= $isJ4 ? 'form-control' : '' ?>"
						id="loginguard-method-edit-title"
						name="title"
						value="<?= $this->escape($this->record->title) ?>"
						placeholder="<?= Text::_('COM_LOGINGUARD_LBL_EDIT_FIELD_TITLE_DESC') ?>">
			</div>
		</div>

		<div class="<?= $isJ4 ? 'row mb-3' : 'control-group form-group' ?>">
			<div class="<?= $isJ4 ? 'col-sm-9 offset-sm-3' : 'col-sm-5 col-sm-offset-4' ?>">
				<?php if ($isJ4): ?>
					<div class="form-check">
						<input class="form-check-input" type="checkbox" id="loginguard-is-default-method" <?= $this->record->default ? 'checked="checked"' : ''; ?> name="default">
						<label class="form-check-label" for="loginguard-is-default-method">
							<?= Text::_('COM_LOGINGUARD_LBL_EDIT_FIELD_DEFAULT'); ?>
						</label>
					</div>
				<?php else: ?>
					<label class="control-label hasTooltip w-100"
					title="<?= $this->escape(Text::_('COM_LOGINGUARD_LBL_EDIT_FIELD_DEFAULT_DESC')); ?>">
						<input type="checkbox" <?= $this->record->default ? 'checked="checked"' : ''; ?> name="default" class="mt-0">
						<?= Text::_('COM_LOGINGUARD_LBL_EDIT_FIELD_DEFAULT'); ?>
					</label>
				<?php endif; ?>
			</div>
		</div>

		<?php if (!empty($this->renderOptions['pre_message'])): ?>
		<div class="loginguard-method-edit-pre-message text-muted mt-4 mb-3">
			<?= $this->renderOptions['pre_message'] ?>
		</div>
		<?php endif; ?>

		<?php if (!empty($this->renderOptions['tabular_data'])): ?>
		<div class="loginguard-method-edit-tabular-container">
			<?php if (!empty($this->renderOptions['table_heading'])): ?>
			<h4 class="<?= $isJ4 ? 'h3 border-bottom mb-3' : '' ?>">
				<?= $this->renderOptions['table_heading'] ?>
			</h4>
			<?php endif; ?>
			<table class="table table-striped">
				<tbody>
				<?php foreach ($this->renderOptions['tabular_data'] as $cell1 => $cell2): ?>
				<tr>
					<td>
						<?= $cell1 ?>
					</td>
					<td>
						<?= $cell2 ?>
					</td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>

		<?php if ($this->renderOptions['field_type'] == 'custom'): ?>
			<?= $this->renderOptions['html']; ?>
		<?php else: ?>
		<div class="<?= $isJ4 ? 'row mb-3' : 'control-group form-group'?>">
			<?php if ($this->renderOptions['label']): ?>
			<label class="<?= $isJ4 ? 'col-sm-3 col-form-label' : ' col-sm-4' ?> hasTooltip" for="loginguard-method-edit-code">
				<?= $this->renderOptions['label']; ?>
			</label>
			<?php endif; ?>
			<div class="<?= $isJ4 ? 'col-sm-9' : 'col-sm-5' ?>" <?= $this->renderOptions['label'] ? '' : 'offset-sm-3' ?>>
				<input type="<?= $this->renderOptions['input_type']; ?>"
					   class="" id="loginguard-method-code"
					   name="code"
					   value="<?= $this->escape($this->renderOptions['input_value']) ?>"
					   placeholder="<?= $this->escape($this->renderOptions['placeholder']) ?>">
			</div>
		</div>
		<?php endif; ?>

		<div class="<?= $isJ4 ? 'row mb-3' : 'control-group form-group'?>">
			<div class="<?= $isJ4 ? 'col-sm-9 offset-sm-3' : 'col-sm-6 col-sm-offset-3' ?>">
				<div class="pull-right">
					<?php if ($this->renderOptions['show_submit'] || $this->isEditExisting): ?>
					<button type="submit" class="btn btn-primary me-3"
						<?= $this->renderOptions['submit_onclick'] ? "onclick=\"{$this->renderOptions['submit_onclick']}\"" : '' ?>>
						<span class="icon icon-ok"></span>
						<?= Text::_('COM_LOGINGUARD_LBL_EDIT_SUBMIT'); ?>
					</button>
					<?php endif; ?>

					<a href="<?= $cancelURL ?>"
					class="btn btn-small btn-sm btn-danger">
						<span class="icon icon-cancel-2"></span>
						<?= Text::_('COM_LOGINGUARD_LBL_EDIT_CANCEL'); ?>
					</a>
				</div>
			</div>
		</div>

		<?php if (!empty($this->renderOptions['post_message'])): ?>
			<div class="loginguard-method-edit-post-message text-muted">
				<?= $this->renderOptions['post_message'] ?>
			</div>
		<?php endif; ?>
	</form>
</div>