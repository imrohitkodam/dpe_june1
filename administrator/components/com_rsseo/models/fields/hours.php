<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license     GNU General Public License version 2 or later; see LICENSE
*/

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

class JFormFieldHours extends FormField
{
	public $type = 'Hours';
	
	protected function getLabel() {
		return '';
	}
	
	protected function getInput() {
		$html = array();
		
		$weekdays = array(
			'Monday' => Text::_('COM_RSSEO_MONDAY'),
			'Tuesday' => Text::_('COM_RSSEO_TUESDAY'),
			'Wednesday' => Text::_('COM_RSSEO_WEDNESDAY'),
			'Thursday' => Text::_('COM_RSSEO_THURSDAY'),
			'Friday' => Text::_('COM_RSSEO_FRIDAY'),
			'Saturday' => Text::_('COM_RSSEO_SATURDAY'),
			'Sunday' => Text::_('COM_RSSEO_SUNDAY')
		);
		
		$html[] = '<table class="table">';
		$html[] = '<thead>';
		$html[] = '<tr>';
		$html[] = '<th>'.Text::_('COM_RSSEO_WEEKDAY').'</th>';
		$html[] = '<th class="center"><span class="hasPopover" data-placement="top" data-content="'.Text::_('COM_RSSEO_HOURS_INFO').'">'.Text::_('COM_RSSEO_OPENS').'</span></th>';
		$html[] = '<th class="center"><span class="hasPopover" data-placement="top" data-content="'.Text::_('COM_RSSEO_HOURS_INFO').'">'.Text::_('COM_RSSEO_CLOSES').'</span></th>';
		$html[] = '</tr>';
		$html[] = '</thead>';
		
		foreach ($weekdays as $weekday => $name) {
			$checked = isset($this->value[$weekday]['enabled']) ? 'checked="checked"' : '';
			$opens	 = isset($this->value[$weekday]['opens']) ? $this->value[$weekday]['opens']: '';
			$closes	 = isset($this->value[$weekday]['closes']) ? $this->value[$weekday]['closes'] : '';
			
			$html[] = '<tr>';
			$html[] = '<td>';
			$html[] = '<input type="checkbox" '.$checked.' name="'.$this->name.'['.$weekday.'][enabled]" id="rsseo_'.$weekday.'" value="1" /> <label for="rsseo_'.$weekday.'" class="checkbox inline">'.$name.'</label>';
			$html[] = '</td>';
			$html[] = '<td class="center">';
			$html[] = '<input type="text" name="'.$this->name.'['.$weekday.'][opens]" class="input-mini center" value="'.$opens.'" onkeyup="javascript:this.value=this.value.replace(/[^0-9,:]/g, \'\');" />';
			$html[] = '</td>';
			$html[] = '<td class="center">';
			$html[] = '<input type="text" name="'.$this->name.'['.$weekday.'][closes]" class="input-mini center" value="'.$closes.'" onkeyup="javascript:this.value=this.value.replace(/[^0-9,:]/g, \'\');" />';
			$html[] = '</td>';
			$html[] = '</tr>';
		}
		
		$html[] = '</table>';
		
		return implode("\n", $html);
		
	}
}