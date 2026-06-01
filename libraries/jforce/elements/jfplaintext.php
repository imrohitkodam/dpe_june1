<?php
/**
 * Element: PlainText
 * Displays plain text as element
 *
 * @package         NoNumber Framework
 * @version         14.7.5
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2014 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

//require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';

/**
 * PlainText Element
 */
class JFormFieldJfplaintext extends JFormField
{
	public $type = 'jfplaintext';
	private $params = null;

	protected function getLabel()
	{

		//JHtml::stylesheet('nnframework/style.min.css', false, true);

		$this->params = $this->element->attributes();
		$label = $this->prepareText($this->get('label'));

		$description = (trim($this->value) != '') ? trim($this->value) : $this->get('description');
		$description = $this->prepareText($description);

		$div = '';
		if (!$label && !$description)
		{
			return '';
		}

		if (!$label)
		{
			return '<label class="jflabel">' . $description . '</label>';
		}

		if (!$description)
		{
			return '<label class="jflabel">' . $label . '</label>';
		}

		$description = $this->prepareText($this->get('description'));

		return '<label class="hasTooltip" title="<b>' . $label . '</b><br>' . str_replace('"', '&quot;', $description) . '">'
		. $label . '</label>';
	}

	protected function getInput()
	{
		$text = $this->prepareText(trim($this->value));

		if (!$text)
		{
			return '';
		}

		return '<fieldset class="jf_plaintext">' . $text . '</fieldset>';
	}

	private function prepareText($str = '')
	{
		if ($str == '')
		{
			return '';
		}

		// variables
		$v1 = JText::_($this->get('var1'));
		$v2 = JText::_($this->get('var2'));
		$v3 = JText::_($this->get('var3'));
		$v4 = JText::_($this->get('var4'));
		$v5 = JText::_($this->get('var5'));
		$str = JText::sprintf(JText::_($str), $v1, $v2, $v3, $v4, $v5);
		//$str = trim(JFText::html_entity_decoder($str));
		//$str = str_replace('&quot;', '"', $str);
		//$str = str_replace('span style="font-family:monospace;"', 'span class="jfcode"', $str);

		return $str;
	}

	private function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}
