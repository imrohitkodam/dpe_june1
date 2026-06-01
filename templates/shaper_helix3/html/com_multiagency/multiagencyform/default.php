<?php
/**
* @package    Com_Multiagency
*
* @author      Techjoomla <extensions@techjoomla.com>
* @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
* @license     GNU General Public License version 2 or later; see LICENSE.txt
*/

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');

if(JVERSION >= '3.0')
{Factory::getDocument()->getWebAssetManager()->usePreset('choicesjs')
->useScript('webcomponent.field-fancy-select');
}
HTMLHelper::_('bootstrap.renderModal');

// Load admin language file
$lang = Factory::getLanguage();
$lang->load('com_multiagency', JPATH_SITE);
$user    = Factory::getUser();
$UriRoot = Uri::root();

$canEdit    = $user->authorise('core.edit', 'com_multiagency');
Text::script('COM_MULTIAGENCY_DUPLICATE_MANAGER');
HTMLHelper::script( Uri::root().'media/com_multiagency/js/multiagency.js' );

$redirectUrl = 'index.php?option=com_dpe&view=schools';
$dpeUtility     = DPE::utilities();
$itemId         = $dpeUtility->getItemId($redirectUrl);
?>
<div class="multiagency-edit front-end-edit">
	<div class="page-header">
	<?php if (!$canEdit) : ?>
		<h2>
			<?php throw new Exception(Text::_('COM_MULTIAGENCY_ERROR_MESSAGE_NOT_AUTHORISED'), 403); ?>
		</h2>
	<?php else : ?>
	<?php if (!empty($this->item->id)): ?>
	<h2><?php echo Text::sprintf('COM_MULTIAGENCY_EDIT_ITEM_TITLE', Text::_('COM_MULTIAGENCY_ORGANISATION')); ?></h2>
	<?php else: ?>
	<h2><?php echo Text::sprintf('COM_MULTIAGENCY_ADD_ITEM', Text::_('COM_MULTIAGENCY_ORGANISATION')); ?></h2>
	<?php endif; ?>
	</div>
	<div class="col-12 col-sm-7 col-md-7">
		<div class="clearfix">&nbsp;</div>
		<form id="form-multiagency" action="<?php echo Route::_('index.php?option=com_multiagency&task=multiagency.save'); ?>" method="post" class="form-validate form-horizontal ucm-form-styling create-organization-form" enctype="multipart/form-data">
			<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
			<input type="hidden" name="jform[state]" value="<?php echo $this->item->state; ?>" />
			<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
			<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />
			<?php echo $this->form->getInput('created_by'); ?>
			<?php echo $this->form->getInput('modified_by'); ?>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('title'); ?>
					
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('title'); ?>
				</div>
			</div>

			<div class="">
					<?php echo $this->form->renderField('tags'); ?>
			</div>
			<div class="">
					<?php echo $this->form->renderField('lead_consultant_id'); ?>
			</div>
				
			<?php echo LayoutHelper::render('joomla.edit.params', $this); ?>
			<?php echo $this->form->renderField('manager'); ?>
			<?php echo $this->form->renderField('id'); ?>
			<?php echo $this->form->renderField('category_id'); ?>
			<div class="control-group">
				<div class="controls text-right float-end">
					<?php if ($this->canSave): ?>
					<button type="submit" class="validate btn btn-primary" onclick="return checkDuplicates();">
						<?php echo Text::_( 'JSUBMIT'); ?>
					</button>
					<?php endif; ?>
					<a class="btn btn-default" href="<?php echo Route::_($redirectUrl . '&Itemid=' . $itemId, false); ?>" title="<?php echo Text::_('JCANCEL'); ?>">
						<?php echo Text::_( 'JCANCEL'); ?>
					</a>
				</div>
			</div>
			<input type="hidden" name="option" value="com_multiagency" />
			<input type="hidden" name="task" value="multiagencyform.save" />
			<input type="hidden" name="redirectUrl" value="<?php echo Route::_($redirectUrl . '&Itemid=' . $itemId, false); ?>"/>
			<?php echo HTMLHelper::_( 'form.token'); ?>
			<?php echo HTMLHelper::_( 'jquery.token'); ?>
		</form>
	</div>
	<?php endif; ?>
</div>
<script type="text/javascript">
jQuery(document).ready(function() {
	var id = parseInt(<?php echo $this->item->id;?>);

	if (id)
	{
		jQuery('.extravalue').attr('readonly', true);
	}

	var UriRoot = "<?php echo $UriRoot; ?>";

	});
	var UriRoot = "<?php echo $UriRoot; ?>";
</script>
<script>
jQuery(document).ready(function()
{
	// To replace tag  remove item content to close button
	 jQuery('#jform_tags_chosen').hide();
     jQuery('.choices__button_joomla').text('x');
     jQuery('.choices__inner').on('change',function(){jQuery('.choices__button_joomla').text('x'); })
     jQuery('.choices__item.choices__item--selectable').click(function(){jQuery('.choices__button_joomla').text('x'); })
       jQuery(document).on('mouseenter', '.choices__item', function() {
        var $button = jQuery('.choices__button_joomla');

        $button.text('x');
      });

        jQuery(document).on('keydown', function(event) {
        if (event.key === 'Enter') {
           var $button = jQuery('.choices__button_joomla');
    		    $button.text('x');
	        }
        })


	/* It restrict the user for manual input in datepicker field */
	jQuery(document).delegate('#jform_com_fields_calendar', 'focusin', function(event) {
		event.preventDefault();
		jQuery(this).parent().siblings(':eq(0)').show();
	});

	jQuery(document).delegate('#jform_com_fields_calendar', 'keydown contextmenu', function() {
			return false;
	});
});
</script>

