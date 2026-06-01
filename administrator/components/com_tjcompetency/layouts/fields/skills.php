<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string   $autocomplete    Autocomplete attribute for the field.
 * @var   boolean  $autofocus       Is autofocus enabled?
 * @var   string   $class           Classes for the input.
 * @var   string   $description     Description of the field.
 * @var   boolean  $disabled        Is this field disabled?
 * @var   string   $group           Group the field belongs to. <fields> section in form XML.
 * @var   boolean  $hidden          Is this field hidden in the form?
 * @var   string   $hint            Placeholder for the field.
 * @var   string   $id              DOM id of the field.
 * @var   string   $label           Label of the field.
 * @var   string   $labelclass      Classes to apply to the label.
 * @var   boolean  $multiple        Does this field support multiple values?
 * @var   string   $name            Name of the input field.
 * @var   string   $onchange        Onchange attribute for the field.
 * @var   string   $onclick         Onclick attribute for the field.
 * @var   string   $pattern         Pattern (Reg Ex) of value of the form field.
 * @var   boolean  $readonly        Is this field read only?
 * @var   boolean  $repeat          Allows extensions to duplicate elements.
 * @var   boolean  $required        Is this field required?
 * @var   integer  $size            Size attribute of the input.
 * @var   boolean  $spellcheck      Spellcheck state for the form field.
 * @var   string   $validate        Validation rules to apply.
 * @var   string   $value           Value attribute of the field.
 *
 * @var   string   $skillName       The skill name
 * @var   mixed    $groups          The filtering groups (null means no filtering)
 * @var   mixed    $excluded        The users to exclude from the list of users
 */

if (!$readonly)
{
	JHtml::_('script', 'jui/fielduser.min.js', array('version' => 'auto', 'relative' => true));
}

$uri = new JUri('index.php?option=com_tjcompetency&view=skills&layout=modal&tmpl=component&required=0&field={field-user-id}');

if ($required)
{
	$uri->setVar('required', 1);
}

// Invalidate the input value if no user selected
if ($this->escape($skillName) === JText::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTMAP_FORM_SELECT_SKILL'))
{
	$skillName = '';
}

$inputAttributes = array(
	'type' => 'text', 'id' => $id, 'class' => 'field-user-input-name', 'value' => $this->escape($skillName)
);

if ($class)
{
	$inputAttributes['class'] .= ' ' . $class;
}

if ($size)
{
	$inputAttributes['size'] = (int) $size;
}

if ($required)
{
	$inputAttributes['required'] = 'required';
}

if (!$readonly)
{
	$inputAttributes['placeholder'] = JText::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTMAP_FORM_SELECT_SKILL');
}

?>
<div class="field-user-wrapper"
	 data-url="<?php echo (string) $uri; ?>"
	 data-modal=".modal"
	 data-modal-width="100%"
	 data-modal-height="400px"
	 data-input=".field-user-input"
	 data-input-name=".field-user-input-name"
	 data-button-select=".button-select">
	<div class="input-append">
		<input <?php echo ArrayHelper::toString($inputAttributes); ?> readonly />
		<?php if (!$readonly) : ?>
			<button
				type="button"
				class="btn btn-primary button-select"
				title="<?php echo JText::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTMAP_FORM_SELECT_SKILL'); ?>"
				aria-label="<?php echo JText::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTMAP_FORM_SELECT_SKILL'); ?>"
				>
				<span class="icon-star" aria-hidden="true"></span>
			</button>
			<?php echo JHtml::_(
				'bootstrap.renderModal',
				'skillModal_' . $id,
				array(
					'title'       => JText::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTMAP_FORM_SELECT_SKILL'),
					'closeButton' => true,
					'footer'      => '<button type="button" class="btn" data-dismiss="modal">' . JText::_('JCANCEL') . '</button>',
				)
			); ?>
		<?php endif; ?>
	</div>
	<?php if (!$readonly) : ?>
		<input type="hidden" id="<?php echo $id; ?>_id" name="<?php echo $name; ?>" value="<?php echo (int) $value; ?>" class="field-user-input<?php echo $class ? ' ' . $class : ''; ?>" data-onchange="<?php echo $this->escape($onchange); ?>" />
	<?php endif; ?>
</div>
