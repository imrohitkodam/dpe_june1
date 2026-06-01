<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RSTicketsProFieldHelper
{
	protected static function isCode($value)
	{
		if (strpos(strtolower($value), '//<code>') !== false)
		{
			return eval($value);
		}

		return $value;
	}

	protected static function generateDummyForm()
	{
		static $form;

		if (is_null($form))
		{
			$form = new JForm('rsticketsprodummyform');
		}

		return $form;
	}
	
	public static function showCustomField($field, $selected = array(), $editable = true, $department_id = 0)
	{
		if (empty($field) || empty($field->type))
		{
			return false;
		}

		if (isset($field->value))
		{
			switch ($field->type)
			{
				case 'freetext':
				case 'textbox':
				case 'textarea':
				case 'calendar':
				case 'calendartime':
				case 'radio':
					$selected[$field->name] = $field->value;
					break;

				case 'select':
				case 'multipleselect':
				case 'checkbox':
					$selected[$field->name] = self::explode($field->value);
					break;
			}
		}

		$template = '%s';
		$name = $department_id ? 'rst_custom_fields[department_'.$department_id.']['.$field->name.']' : 'rst_custom_fields['.$field->name.']';

		// Create the XML
		$xml = new SimpleXMLElement('<field></field>');
		$xml->addAttribute('label', htmlspecialchars($field->label, ENT_COMPAT, 'utf-8'));
		$xml->addAttribute('description', htmlspecialchars($field->description, ENT_COMPAT, 'utf-8'));

		if ($field->required)
		{
			$xml->addAttribute('required', 'true');
		}

		if ($department_id)
		{
			$xml->addAttribute('showon', 'jform[department_id]:' . $department_id);
		}

		if (!$editable)
		{
			$template = '<div class="rst_custom_field_label">' . JText::_($field->label) . '</div><div class="rst_custom_field_value">%s</div>';
		}

		$form = self::generateDummyForm();

		switch ($field->type)
		{
			case 'freetext':
				$jfield = JFormHelper::loadFieldType('rsticketsprohtml');
				$jfield->setForm($form);
				$jfield->setup($xml, self::isCode($field->values));

				return $jfield->renderField(array('class' => 'rst_custom_field'));
				break;

			case 'textbox':
				$value = isset($selected[$field->name]) ? $selected[$field->name] : self::isCode($field->values);

				if ($editable)
				{
					$xml->addAttribute('name', $name);
					$xml->addAttribute('class', 'rst_textbox');

					$jfield = JFormHelper::loadFieldType('text');
					$jfield->setForm($form);
					$jfield->setup($xml, $value);
					$html = $jfield->renderField(array('class' => 'rst_custom_field'));

					if ($field->additional)
					{
						$html = str_replace('<input', '<input ' . $field->additional, $html);
					}

					return $html;
				}
				else
				{
					return static::sprintf($template, RSTicketsProHelper::htmlEscape($value));
				}
				break;

			case 'textarea':
				$value = isset($selected[$field->name]) ? $selected[$field->name] : self::isCode($field->values);

				if ($editable)
				{
					$xml->addAttribute('name', $name);
					$xml->addAttribute('class', 'rst_textarea');

					$jfield = JFormHelper::loadFieldType('textarea');
					$jfield->setForm($form);
					$jfield->setup($xml, $value);
					$html = $jfield->renderField(array('class' => 'rst_custom_field'));

					if ($field->additional)
					{
						$html = str_replace('<textarea', '<textarea ' . $field->additional, $html);
					}

					return $html;
				}
				else
				{
					return static::sprintf($template, nl2br(RSTicketsProHelper::htmlEscape($value)));
				}
				break;

			case 'select':
				self::getOptions($field, $xml, $selected, $values);

				if ($editable)
				{
					$xml->addAttribute('name', $name . '[]');
					$xml->addAttribute('class', 'rst_select');

					$jfield = JFormHelper::loadFieldType('list');
					$jfield->setForm($form);
					$jfield->setup($xml, $values);
					$html = $jfield->renderField(array('class' => 'rst_custom_field'));

					if ($field->additional)
					{
						$html = str_replace('<select', '<select ' . $field->additional, $html);
					}

					return $html;
				}
				else
				{
					return static::sprintf($template, nl2br(RSTicketsProHelper::htmlEscape(implode("\n", $values))));
				}
				break;

			case 'multipleselect':
				self::getOptions($field, $xml, $selected, $values);

				if ($editable)
				{
					$xml->addAttribute('name', $name);
					$xml->addAttribute('class', 'rst_select');
					$xml->addAttribute('multiple', 'multiple');

					$jfield = JFormHelper::loadFieldType('list');
					$jfield->setForm($form);
					$jfield->setup($xml, $values);
					$html = $jfield->renderField(array('class' => 'rst_custom_field'));

					if ($field->additional)
					{
						$html = str_replace('<select', '<select ' . $field->additional, $html);
					}

					return $html;
				}
				else
				{
					return static::sprintf($template, nl2br(RSTicketsProHelper::htmlEscape(implode("\n", $values))));
				}
				break;

			case 'checkbox':
				self::getOptions($field, $xml, $selected, $values);

				if ($editable)
				{
					$xml->addAttribute('name', $name);

					$jfield = JFormHelper::loadFieldType('checkboxes');
					$jfield->setForm($form);
					$jfield->setup($xml, $values);
					$html = $jfield->renderField(array('class' => 'rst_custom_field'));

					if ($field->additional)
					{
						$html = str_replace('<input', '<input ' . $field->additional, $html);
					}

					return $html;
				}
				else
				{
					return static::sprintf($template, nl2br(RSTicketsProHelper::htmlEscape(implode("\n", $values))));
				}

				break;

			case 'radio':
				self::getOptions($field, $xml, $selected, $values);

				$value = isset($values[0]) ? $values[0] : null;

				if ($editable)
				{
					$xml->addAttribute('name', $name);

					if (version_compare(JVERSION, '4.0', '>='))
					{
						$jfield = JFormHelper::loadFieldType('radiobasic');
					}
					else
					{
						$jfield = JFormHelper::loadFieldType('radio');
					}
					$jfield->setForm($form);
					$jfield->setup($xml, $value);
					$html = $jfield->renderField(array('class' => 'rst_custom_field'));

					if ($field->additional)
					{
						$html = str_replace('<input', '<input ' . $field->additional, $html);
					}

					return $html;
				}
				else
				{
					return static::sprintf($template, RSTicketsProHelper::htmlEscape($value));
				}
				break;

			case 'calendar':
			case 'calendartime':
				$value = isset($selected[$field->name]) ? $selected[$field->name] : self::isCode($field->values);

				if ($editable)
				{
					$xml->addAttribute('name', $name);

					if ($field->type === 'calendartime')
					{
						$originalFormat = RSTicketsProHelper::getConfig('date_format');
						$format = self::getCalendarFormat($originalFormat);
						$xml->addAttribute('showtime', 'true');
						$xml->addAttribute('filter', 'user_utc');
					}
					else
					{
						$originalFormat = RSTicketsProHelper::getConfig('date_format_notime');
						$format = self::getCalendarFormat($originalFormat);
						$xml->addAttribute('filter', 'none');
					}

					$xml->addAttribute('format', $format);
					
					if ($field->additional)
					{
						$attributes = self::parseAttributes($field->additional);

						foreach ($attributes as $attribute => $val)
						{
							$xml->addAttribute($attribute, $val);
						}
					}

					$jfield = JFormHelper::loadFieldType('calendar');
					$jfield->setForm($form);
					if (is_callable(array($jfield, 'setDatabase')))
					{
						$jfield->setDatabase(JFactory::getDbo());
					}

					// Sanity check for value
					if ($value)
					{
						try
						{
							if ($field->type === 'calendar')
							{
								$offset = null;
							}
							else
							{
								$offset = JFactory::getUser()->getParam('timezone', JFactory::getApplication()->get('offset'));
							}

							$value = JFactory::getDate($value, $offset)->format($originalFormat);
						}
						catch (Exception $e)
						{
							$value = null;
						}
					}

					$jfield->setup($xml, $value);
					return $jfield->renderField(array('class' => 'rst_custom_field'));
				}
				else
				{
					return static::sprintf($template, RSTicketsProHelper::htmlEscape($value));
				}
				break;
		}

		return false;
	}

	protected static function sprintf($template, $value)
	{
		return str_replace('%s', $value, $template);
	}

	protected static function parseAttributes($string)
	{
		$parsed = array();

		// Let's grab all the key/value pairs using a regular expression
		if (preg_match_all('/([\w:-]+)[\s]?(=[\s]?"([^"]*)")?/i', $string, $attr))
		{
			$numPairs = count($attr[1]);
			for ($i = 0; $i < $numPairs; $i++)
			{
				$parsed[$attr[1][$i]] = $attr[3][$i];
			}
		}

		return $parsed;
	}

	protected static function getOptions($field, $xml, $selected, &$values)
	{
		$values = array();
		$field->values = self::explode(self::isCode($field->values));
		if ($field->values)
		{
			foreach ($field->values as $string)
			{
				$disabled = false;
				if (strpos($string, '[d]') !== false)
				{
					$string = str_replace('[d]', '', $string);
					$disabled = true;
				}

				// <optgroup>
				if (strpos($string, '[g]') !== false)
				{
					$string = str_replace('[g]', '', $string);
					$option = $xml->addChild('option', htmlspecialchars($string, ENT_COMPAT, 'utf-8'));
					$option->addAttribute('value', '<OPTGROUP>');
					continue;
				}
				if (strpos($string, '[/g]') !== false)
				{
					$string = str_replace('[/g]', '', $string);
					$option = $xml->addChild('option', htmlspecialchars($string, ENT_COMPAT, 'utf-8'));
					$option->addAttribute('value', '</OPTGROUP>');
					continue;
				}

				$checked = false;
				if (strpos($string, '[c]') !== false)
				{
					$string = str_replace('[c]', '', $string);

					if (!isset($selected[$field->name]))
					{
						$checked = true;
					}
				}

				if (strpos($string, '|') !== false)
				{
					list($value, $text) = explode('|', $string, 2);
				}
				else
				{
					$value = $text = $string;
				}

				if (isset($selected[$field->name]) && in_array($value, (array) $selected[$field->name]))
				{
					$checked = true;
				}

				$option = $xml->addChild('option', htmlspecialchars($text, ENT_COMPAT, 'utf-8'));
				$option->addAttribute('value', $value);
				if ($disabled)
				{
					$option->addAttribute('disabled', 'disabled');
				}
				if ($checked)
				{
					$values[] = $value;
				}
			}
		}
	}

	protected static function getCalendarFormat($format)
	{
		/*
		%a 	abbreviated weekday name D
		%A 	full weekday name l
		%b 	abbreviated month name M
		%B 	full month name F
		%C 	century number
		%d 	the day of the month ( 00 .. 31 ) d
		%e 	the day of the month ( 0 .. 31 ) j
		%H 	hour ( 00 .. 23 ) H
		%I 	hour ( 01 .. 12 ) h
		%j 	day of the year ( 000 .. 366 ) z
		%k 	hour ( 0 .. 23 ) G
		%l 	hour ( 1 .. 12 ) g
		%m 	month ( 01 .. 12 ) m
		%M 	minute ( 00 .. 59 ) i
		%n 	a newline character \n
		%p 	``PM'' or ``AM'' A
		%P 	``pm'' or ``am'' a
		%S 	second ( 00 .. 59 ) s
		%s 	number of seconds since Epoch (since Jan 01 1970 00:00:00 UTC) U
		%t 	a tab character \t
		%U, %W, %V 	the week number W
		%u 	the day of the week ( 1 .. 7, 1 = MON ) N
		%w 	the day of the week ( 0 .. 6, 0 = SUN ) w
		%y 	year without the century ( 00 .. 99 ) y
		%Y 	year including the century ( ex. 1979 ) Y
		%% 	a literal % character %
		*/

		$php = array('%', 'D', 'l', 'M', 'F', 'd', 'j', 'H', 'h', 'z', 'G', 'g', 'm', 'i', "\n", 'A', 'a', 's', 'U', "\t", 'W', 'N', 'w', 'y', 'Y');
		$js  = array('%%', '%a', '%A', '%b', '%B', '%d', '%e', '%H', '%I', '%j', '%k', '%l', '%m', '%M', '%n', '%p', '%P', '%S', '%s', '%t', '%U', '%u', '%w', '%y', '%Y');

		return str_replace($php, $js, $format);
	}
	
	protected static function explode($string)
	{
		$string = str_replace(array("\r\n", "\r"), "\n", $string);
		return explode("\n", $string);
	}
}