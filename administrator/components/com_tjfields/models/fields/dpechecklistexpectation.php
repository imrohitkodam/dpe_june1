<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

defined('JPATH_PLATFORM') or die;
use Joomla\CMS\Language\Text;

jimport('joomla.form.formfield');
/**
 * Form Field class for the Joomla Platform.
 * Provides an input field for files
 *
 * @link   http://www.w3.org/TR/html-markup/input.file.html#input.file
 * @since  11.1
 */

class JFormFieldDpeChecklistExpectation extends JFormField
{
	//The field class must know its own type through the variable $type.
	protected $type = 'DpechecklistExpectation';


	public function getInput() {

		$data = $this->getLayoutData();
		$danger = $dangerClass = $warning = $warningClass = $info = $infoClass = $message = $messageClass = '';

		if ($data['field']->value == 'Not Applicable')
		{
			$danger  = 'checked';
			$dangerClass  = 'danger';
		}

		if ($data['field']->value == 'Not Meeting Expectation')
		{
			$warning  = 'checked';
			$warningClass  = 'warning';
		}

		if ($data['field']->value == 'Partially Meeting Expectation')
		{
			$info  = 'checked';
			$infoClass  = 'info';
		}

		if ($data['field']->value == 'Fully Meeting Expectation')
		{
			$message  = 'checked';
			$messageClass  = 'custom-message';
		}

		$fieldNmm = str_replace("[",'',$data['field']->name);
		$fieldNm = str_replace("]",'',$fieldNmm);
		$html = '
<div class="btn-group" data-toggle="buttons" >
  <label class="btn checklistBtn dpe-danger '.$dangerClass.'" onclick="jQuery(this).addClass(\'danger\'); jQuery(\'#message'.$fieldNm.'\').removeClass(\'custom-message\'); jQuery(\'#warning'.$fieldNm.'\').removeClass(\'warning\');jQuery(\'#info'.$fieldNm.' \').removeClass(\'info\');" id="danger'.$fieldNm.'">
    <input type="radio" name="' . $this->name . '" value="'.Text::_('COM_TJFIELDS_CHECKLIST_EXPECTATION_NOT_APPLICABLE_VALUE').'"  autocomplete="off" ' . $danger . '>Not Applicable
  </label>
  <label class="btn checklistBtn dpe-warning '.$warningClass.'" id="warning'.$fieldNm.'"  onclick="jQuery(this).addClass(\'warning\'); jQuery(\'#message'.$fieldNm.'\').removeClass(\'custom-message\'); jQuery(\'#danger'.$fieldNm.'\').removeClass(\'danger\');jQuery(\'#info'.$fieldNm.'\').removeClass(\'info\');" >
    <input type="radio" name="' . $this->name . '" value="'.Text::_('COM_TJFIELDS_CHECKLIST_EXPECTATION_NOT_MEETING_EXPECTATION_VALUE').'" autocomplete="off" ' . $warning . '>Not Meeting Expectation
  </label>
  <label class="btn checklistBtn dpe-info '.$infoClass.'" id="info'.$fieldNm.'"  onclick="jQuery(this).addClass(\'info\'); jQuery(\'#message'.$fieldNm.'\').removeClass(\'custom-message\'); jQuery(\'#warning'.$fieldNm.'\').removeClass(\'warning\');jQuery(\'#danger'.$fieldNm.'\').removeClass(\'danger\');" >
    <input type="radio" name="' . $this->name . '" value="'.Text::_('COM_TJFIELDS_CHECKLIST_EXPECTATION_PARTIALLY_MEETING_EXPECTATION_VALUE').'" autocomplete="off" ' . $info . '>Partially Meeting Expectation
  </label>
  <label class="btn checklistBtn dpe-message '.$messageClass.'" id="message'.$fieldNm.'"  onclick="jQuery(this).addClass(\'custom-message\'); jQuery(\'#info'.$fieldNm.'\').removeClass(\'info\'); jQuery(\'#warning'.$fieldNm.'\').removeClass(\'warning\');jQuery(\'#danger'.$fieldNm.'\').removeClass(\'danger\');" >
    <input type="radio" name="' . $this->name . '" value="'.Text::_('COM_TJFIELDS_CHECKLIST_EXPECTATION_FULLY_MEETING_EXPECTATION_VALUE').'" autocomplete="off" ' . $message . '>Fully Meeting Expectation</label>
</div>';
		return $html;
	}
}
