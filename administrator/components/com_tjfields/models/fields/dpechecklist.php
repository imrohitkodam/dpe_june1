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
use Joomla\CMS\Table\Table;
jimport('joomla.form.formfield');
/**
 * Form Field class for the Joomla Platform.
 * Provides an input field for files
 *
 * @link   http://www.w3.org/TR/html-markup/input.file.html#input.file
 * @since  11.1
 */

class JFormFieldDpeChecklist extends JFormField
{
	//The field class must know its own type through the variable $type.
	protected $type = 'Dpechecklist';


	public function getInput() {


		$data = $this->getLayoutData();
		$id = $this->id;
		$input = \Joomla\CMS\Factory::getApplication()->input;
		$jform = $input->post->get('jform', array(), 'ARRAY');

		$fieldId = preg_replace('/^jform_/', '', $id);

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
		$tjFieldFieldTable->load(array('name' => $fieldId, 'state' => 1));
		$params = json_decode($tjFieldFieldTable->params);
		
		$enableChecklistScore = isset($params->enablechecklistscore) ? $params->enablechecklistscore : 0;
		$enableChecklistNa = isset($params->enablechecklistna) ? $params->enablechecklistna : 0;
		$enableNaNumeric = isset($params->enablenanumeric) ? $params->enablenanumeric : 0;

		$fieldName = str_replace("[",'', $this->name);
		$fieldName = str_replace("]",'',$fieldName);
		
		$html = '';

		if ($enableChecklistScore)
		{
			// Dynamic Scoring Logic
			$options = array();
			$tjfieldsJson = null;

			// Try to get tjfields from various sources
			if (isset($this->element['tjfields'])) {
				$tjfieldsJson = $this->element['tjfields'];
			} elseif (isset($this->tjfields)) {
				$tjfieldsJson = $this->tjfields;
			} elseif (isset($tjFieldFieldTable->params)) {
				// Fallback to params from loaded table if not in element
				$tjParams = json_decode($tjFieldFieldTable->params);
				if (isset($tjParams->tjfields)) {
					$tjfieldsJson = json_encode($tjParams->tjfields);
				}
			}

			if (!empty($tjfieldsJson))
			{
				$tjfields = json_decode($tjfieldsJson);
				if (!empty($tjfields))
				{
					foreach($tjfields as $i => $opt)
					{
						if (isset($opt->optionname) && isset($opt->optionvalue))
						{
							$val = isset($opt->numeric_value) ? $opt->numeric_value : 0;
							
							// Exception: If index is 3 (N/A) and numeric is disabled for it, use text value
							if ($i == 3 && !$enableNaNumeric) {
								$val = $opt->optionvalue;
							}

							$options[] = (object) array(
								'value' => $val,
								'text' => $opt->optionname,
								'score' => isset($opt->numeric_value) ? $opt->numeric_value : 0
							);
						}
					}
				}
			}

			// Render Dynamic Options
			$html .= '<div class="row-fluid"><div class="btn-group checklistBtnGrp" data-toggle="buttons">';

			foreach ($options as $i => $opt) {
				$checked = '';
				$activeClass = '';
				$btnClass = '';
				$idSuffix = '';
				$onClick = '';


				switch ($i) {
					case 0:
						$btnClass = 'dpe-danger';
						$idSuffix = 'danger';
						$onClick = "jQuery(this).addClass('danger'); jQuery('#warning{$fieldName}').removeClass('warning');jQuery('#info{$fieldName}').removeClass('info');jQuery('#na{$fieldName}').removeClass('na');";
						if ($this->value == $opt->value) { $checked = 'checked'; $activeClass = 'danger'; }
						break;
					case 1:
						$btnClass = 'dpe-warning';
						$idSuffix = 'warning';
						$onClick = "jQuery(this).addClass('warning'); jQuery('#danger{$fieldName}').removeClass('danger');jQuery('#info{$fieldName}').removeClass('info');jQuery('#na{$fieldName}').removeClass('na');";
						if ($this->value == $opt->value) { $checked = 'checked'; $activeClass = 'warning'; }
						break;
					case 2:
						$btnClass = 'dpe-info';
						$idSuffix = 'info';
						$onClick = "jQuery(this).addClass('info'); jQuery('#warning{$fieldName}').removeClass('warning');jQuery('#danger{$fieldName}').removeClass('danger');jQuery('#na{$fieldName}').removeClass('na');";
						if ($this->value == $opt->value) { $checked = 'checked'; $activeClass = 'info'; }
						break;
					case 3:
						if (!$enableChecklistNa)
						{
							continue 2;
						}
						$btnClass = 'dpe-na';
						$idSuffix = 'na';
						$onClick = "jQuery(this).addClass('na'); jQuery('#warning{$fieldName}').removeClass('warning');jQuery('#danger{$fieldName}').removeClass('danger');jQuery('#info{$fieldName}').removeClass('info');";
						if ($this->value == $opt->value) { $checked = 'checked'; $activeClass = 'na'; }
						break;
				}

				$html .= '<label class="btn checklistBtn ' . $btnClass . ' ' . $activeClass . '" id="' . $idSuffix . $fieldName . '" onclick="' . $onClick . '">';
				$html .= '<input type="radio" name="' . $this->name . '" value="' . $opt->value . '" autocomplete="off" ' . $checked;
				if (isset($opt->score)) {
					$html .= ' data-score="' . $opt->score . '"';
				}
				$html .= '> ' . $opt->text;
				$html .= '</label>';
			}
			$html .= '</div></div>';

			

				
		}
		else
		{
			// Previous Workflow (Hardcoded Text Values)
			$checkedDone = '';
			$checkedDoneClass = '';
			$checkedInprogress = '';
			$checkedInprogressClass = '';
			$checkedtodo = '';
			$checkedtodoClass = '';
			$checkedNa = '';
			$checkedNaClass = '';

			if ($this->value == 'done')
			{
				$checkedDone  = 'checked';
				$checkedDoneClass  = 'info';
			}

			if ($this->value == 'inprogress')
			{
				$checkedInprogress  = 'checked';
				$checkedInprogressClass  = 'warning';
			}

			if ($this->value == 'todo')
			{
				$checkedtodo  = 'checked';
				$checkedtodoClass  = 'danger';
			}

			if ($this->value == 'na')
			{
				$checkedNa = 'checked';
				$checkedNaClass = 'na';
			}

			$html .= '<div class="row-fluid">
	<div class="btn-group checklistBtnGrp" data-toggle="buttons" >
	  <label class="btn checklistBtn dpe-danger '.$checkedtodoClass.'" onclick="jQuery(this).addClass(\'danger\'); jQuery(\'#warning'.$fieldName.'\').removeClass(\'warning\');jQuery(\'#info'.$fieldName.' \').removeClass(\'info\');jQuery(\'#na'.$fieldName.'\').removeClass(\'na\');" id="danger'.$fieldName.'">
		<input type="radio" name="' . $this->name . '" value="todo"  autocomplete="off" ' . $checkedtodo . '> To Do
	  </label>
	  <label class="btn checklistBtn dpe-warning '.$checkedInprogressClass.'" id="warning'.$fieldName.'"  onclick="jQuery(this).addClass(\'warning\'); jQuery(\'#danger'.$fieldName.'\').removeClass(\'danger\');jQuery(\'#info'.$fieldName.'\').removeClass(\'info\');jQuery(\'#na'.$fieldName.'\').removeClass(\'na\');" >
		<input type="radio" name="' . $this->name . '" value="inprogress" autocomplete="off" ' . $checkedInprogress . '> In-progress
	  </label>
	  <label class="btn checklistBtn dpe-info '.$checkedDoneClass.'" id="info'.$fieldName.'"  onclick="jQuery(this).addClass(\'info\'); jQuery(\'#warning'.$fieldName.'\').removeClass(\'warning\');jQuery(\'#danger'.$fieldName.'\').removeClass(\'danger\');jQuery(\'#na'.$fieldName.'\').removeClass(\'na\');" >
		<input type="radio" name="' . $this->name . '" value="done" autocomplete="off" ' . $checkedDone . '> Done
	  </label>';

			if ($enableChecklistNa)
			{
				$html .= '<label class="btn checklistBtn dpe-na '.$checkedNaClass.'" id="na'.$fieldName.'"  onclick="jQuery(this).addClass(\'na\'); jQuery(\'#warning'.$fieldName.'\').removeClass(\'warning\');jQuery(\'#danger'.$fieldName.'\').removeClass(\'danger\');jQuery(\'#info'.$fieldName.'\').removeClass(\'info\');" >
		<input type="radio" name="' . $this->name . '" value="na" autocomplete="off" ' . $checkedNa . '> N/A
	  </label>';
			}

			$html .= '</div></div>';
		}


		return $html;
	}
}