<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */
defined('JPATH_PLATFORM') or die;

class JFormFieldKBConditions extends JFormField
{
	protected $type = 'KBConditions';
	
	protected $options 			 = array();
	protected $departments 		 = array();
	protected $priorities 		 = array();
	protected $statuses 		 = array();
	protected $customFieldValues = array();
	protected $customFields      = array();

	public function __construct($form = null)
	{
		parent::__construct($form);
		
		// prepare lists
		// condition types
		$this->options['types'] = array(
			JHtml::_('select.option', '', JText::_('RST_PLEASE_SELECT')),
			JHtml::_('select.option', 'department', JText::_('RST_DEPARTMENT')),
			JHtml::_('select.option', 'subject', JText::_('RST_TICKET_SUBJECT')),
			JHtml::_('select.option', 'message', JText::_('RST_TICKET_MESSAGE')),
			JHtml::_('select.option', 'priority', JText::_('RST_PRIORITY')),
			JHtml::_('select.option', 'status', JText::_('RST_TICKET_STATUS')),
			JHtml::_('select.option', 'custom_field', JText::_('RST_CUSTOM_FIELD'))
		);
		// conditions
		$this->options['conditions'] = array(
			JHtml::_('select.option', '', JText::_('RST_PLEASE_SELECT')),
			JHtml::_('select.option', 'eq', JText::_('RST_IS_EQUAL')),
			JHtml::_('select.option', 'neq', JText::_('RST_IS_NOT_EQUAL')),
			JHtml::_('select.option', 'like', JText::_('RST_IS_LIKE')),
			JHtml::_('select.option', 'notlike', JText::_('RST_IS_NOT_LIKE'))
		);
		// connectors
		$this->options['connectors'] = array(
			JHtml::_('select.option', 'AND', JText::_('RST_AND')),
			JHtml::_('select.option', 'OR', JText::_('RST_OR'))
		);
		
		// departments
		$this->departments = $this->getDepartments();
		// priorities
		$this->priorities = $this->getPriorities();
		// statuses
		$this->statuses = $this->getStatuses();
		// custom fields
		$this->customFields = $this->getCustomFields();
		// custom field values
		$this->customFieldValues = $this->getCustomFieldValues();
	}
	
	protected function getDepartments()
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		$query->select($db->qn('id'))
			  ->select($db->qn('name'))
			  ->from($db->qn('#__rsticketspro_departments'))
			  ->order($db->qn('ordering').' '.$db->escape('ASC'));
		
		$db->setQuery($query);
		return $db->loadObjectList();
	}
	
	protected function getPriorities()
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		$query->select($db->qn('id'))
			  ->select($db->qn('name'))
			  ->from($db->qn('#__rsticketspro_priorities'))
			  ->order($db->qn('ordering').' '.$db->escape('ASC'));
		
		$db->setQuery($query);
		return $db->loadObjectList();
	}
	
	protected function getStatuses()
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		$query->select($db->qn('id'))
			  ->select($db->qn('name'))
			  ->from($db->qn('#__rsticketspro_statuses'))
			  ->order($db->qn('ordering').' '.$db->escape('ASC'));
		
		$db->setQuery($query);
		return $db->loadObjectList();
	}
	
	protected function getCustomFieldValues()
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		$query->select($db->qn('values'))
			  ->select($db->qn('id'))
			  ->select($db->qn('type'))
			  ->from($db->qn('#__rsticketspro_custom_fields'));
		
		$db->setQuery($query);
		$customFieldValues = $db->loadObjectList('id');
		
		foreach ($customFieldValues as $id => $properties)
		{
			$list = array();
			
			$values = str_replace(array("\r\n", "\r"), "\n", $properties->values);
			$values = explode("\n", $values);
		
			foreach ($values as $value)
			{
				$list[] = JHtml::_('select.option', $value, $value);
			}
			
			$customFieldValues[$id] = $list;
		}
		
		return $customFieldValues;
	}
	
	protected function getCustomFields()
	{
		$db 	= JFactory::getDbo();
		$query 	= $db->getQuery(true);
		
		$query->select($db->qn('id'))
			  ->select($db->qn('department_id'))
			  ->select($db->qn('name'))
			  ->select($db->qn('type'))
			  ->select($db->qn('values'))
			  ->from($db->qn('#__rsticketspro_custom_fields'))
			  ->order($db->qn('ordering').' '.$db->escape('ASC'));
		
		$db->setQuery($query);
		$customFields = $db->loadObjectList();
		
		$list = array();
		foreach ($this->departments as $department)
		{
			$optgroup = new stdClass();
			$optgroup->value = '<OPTGROUP>';
			$optgroup->text = $department->name;
			$list[] = $optgroup;
			
			foreach ($customFields as $customField)
			{
				if ($customField->department_id != $department->id)
				{
					continue;
				}
				
				$list[] = JHtml::_('select.option', $customField->id, $customField->name);
			}
			
			$optgroup = new stdClass();
			$optgroup->value = '</OPTGROUP>';
			$optgroup->text = '';
			$list[] = $optgroup;
		}
		
		return $list;
	}
	
	protected function escape($string)
	{
		return htmlentities($string, ENT_COMPAT, 'utf-8');
	}
	
	protected function getFormControlName($name)
	{
		return $this->formControl.'['.$name.']';
	}
	
	protected function getInput()
	{
		$conditions = array();
		if ($this->value)
		{
			$conditions = unserialize($this->value);
		}
		if (!$conditions)
		{
			$conditions = array();
		}
		
		$html = '<p><button type="button" class="btn btn-success" id="addConditionLink"><span class="icon icon-plus"></span></button></p>';
		$html .= '<div class="clr"></div>';
		$html .= '<div id="rst_conditions">';
		
		$hidden_attribs = 'disabled="disabled" style="display: none;"';
		
		foreach ($conditions as $i => $condition) {
			$select_type = JHtml::_('select.genericlist', $this->options['types'], $this->getFormControlName('select_type').'[]', null, 'value', 'text', $condition->type, 'select_type'.$i);
			$select_custom_field_value = '';
			if ($condition->type == 'custom_field') {
				$select_custom_field_value = JHtml::_('select.genericlist', $this->customFields, $this->getFormControlName('select_custom_field_value').'[]', null, 'value', 'text', $condition->custom_field, 'select_custom_field_value'.$i);
			}
			$select_condition = JHtml::_('select.genericlist', $this->options['conditions'], $this->getFormControlName('select_condition').'[]', null, 'value', 'text', $condition->condition, 'select_condition'.$i);
			$select_connector = JHtml::_('select.genericlist', $this->options['connectors'], $this->getFormControlName('select_connector').'[]', null, 'value', 'text', $condition->connector, 'select_connector'.$i);
			$select_value 	  = '';
			
			$is_like = $condition->condition == 'like' || $condition->condition == 'notlike';
			
			switch ($condition->type)
			{
				case 'department':
					$select_value  = trim(JHtml::_('select.genericlist', $this->departments, $this->getFormControlName('select_value').'[]', ($is_like ? $hidden_attribs : ''), 'id', 'name', $condition->value, 'select_value'.$i));
					$select_value .= '<input type="text" name="'.$this->getFormControlName('select_value').'[]" value="'.$this->escape($condition->value).'" '.(!$is_like ? $hidden_attribs : '').' />';
				break;
				
				case 'priority':
					$select_value  = trim(JHtml::_('select.genericlist', $this->priorities, $this->getFormControlName('select_value').'[]', ($is_like ? $hidden_attribs : ''), 'id', 'name', $condition->value, 'select_value'.$i));
					$select_value .= '<input type="text" name="'.$this->getFormControlName('select_value').'[]" value="'.$this->escape($condition->value).'" '.(!$is_like ? $hidden_attribs : '').' />';
				break;
				
				case 'status':
					$select_value  = trim(JHtml::_('select.genericlist', $this->statuses, $this->getFormControlName('select_value').'[]', ($is_like ? $hidden_attribs : ''), 'id', 'name', $condition->value, 'select_value'.$i));
					$select_value .= '<input type="text" name="'.$this->getFormControlName('select_value').'[]" value="'.$this->escape($condition->value).'" '.(!$is_like ? $hidden_attribs : '').' />';
				break;
				
				case 'subject':
					$select_value = '<input type="text" name="'.$this->getFormControlName('select_value').'[]" value="'.$this->escape($condition->value).'" />';
				break;
				
				case 'message':
					$select_value = '<textarea name="'.$this->getFormControlName('select_value').'[]">'.$this->escape($condition->value).'</textarea>';
				break;
				
				case 'custom_field':
					$values = isset($this->customFieldValues[$condition->custom_field]) ? $this->customFieldValues[$condition->custom_field] : array();
					$select_value  = trim(JHtml::_('select.genericlist', $values, $this->getFormControlName('select_value').'[]', ($is_like ? $hidden_attribs : ''), 'value', 'text', $condition->value, 'select_value'.$i));
					$select_value .= '<input type="text" name="'.$this->getFormControlName('select_value').'[]" value="'.$this->escape($condition->value).'" '.(!$is_like ? $hidden_attribs : '').' />';
				break;
			}
			$html .= '<p><span class="rst_condition_if">'.JText::_('RST_IF').'</span> '.$select_type.'<span>&nbsp;</span><span class="responseSpan2">'.$select_custom_field_value.'</span><span>&nbsp;</span>'.$select_condition.'<span>&nbsp;</span><span class="responseSpan">'.$select_value.'</span><span>&nbsp;</span>'.$select_connector.'<span>&nbsp;</span><span>&nbsp;</span><a href="javascript: void(0);" class="btn btn-danger deleteConditionLink"><span class="icon icon-minus"></span></a></p>';
		}
		
		$html .= '</div>';
		return $html;
	}
}