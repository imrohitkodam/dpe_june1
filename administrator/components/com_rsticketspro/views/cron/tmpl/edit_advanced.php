<?php
/**
* @version 2.0.0
* @package RSTickets! Pro 2.0.0
* @copyright (C) 2010-2013 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die('Restricted access');

// set description if required
if (isset($this->fieldset->description) && !empty($this->fieldset->description)) { ?>
	<?php echo JText::_($this->fieldset->description); ?>
<?php } ?>
<?php
$this->field->startFieldset('');
foreach ($this->fields as $field) {
	$this->field->showField($field->hidden ? '' : $field->label, $field->input);
}
$this->field->endFieldset();