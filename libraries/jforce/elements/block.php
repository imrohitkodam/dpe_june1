<?php
/**
 * @copyright	Copyright (C) 2014 JoomlaForceTeam. All rights reserved.
 * @license		GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

//require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';
//require_once '/modules/mod_socialpaneltabs/helpers/text.php';
require_once (dirname(__FILE__).'/../helpers/text.php');

class JFormFieldNN_Block extends JFormField
{
	public $type = 'Block';
	private $params = null;

	protected function getLabel()
	{
		return '';
	}

	protected function getInput()
	{
		
		JLoader::import( 'joomla.version' );
		$version = new JVersion();
		
		if (version_compare( $version->RELEASE, '2.5', '<=')) {
			return "";
		} else {
		
		$this->params = $this->element->attributes();

		//JHtml::stylesheet('nnframework/style.min.css', false, true);

		$title = $this->get('label');
		$description = $this->get('description');
		$class = $this->get('class');

		$start = $this->get('start', 0);
		$end = $this->get('end', 0);

		$html = array();

		if ($start || !$end)
		{
			$html[] = '</div>';
			if (!(strpos($class, 'alert') === false))
			{
				$html[] = '<div class="alert ' . $class . '">';
			}
			else
			{
				$html[] = '<div class="well well-small ' . $class . '">';
			}
			if ($title)
			{
				$title = JFText::html_entity_decoder(JText::_($title));
				$html[] = '<h4>' . $title . '</h4>';
			}
			if ($description)
			{
				// variables
				$v1 = JText::_($this->get('var1'));
				$v2 = JText::_($this->get('var2'));
				$v3 = JText::_($this->get('var3'));
				$v4 = JText::_($this->get('var4'));
				$v5 = JText::_($this->get('var5'));

				$description = JFText::html_entity_decoder(trim(JText::sprintf($description, $v1, $v2, $v3, $v4, $v5)));
				$description = str_replace('span style="font-family:monospace;"', 'span class="nn_code"', $description);
				$html[] = '<div>' . $description . '</div>';
			}
			$html[] = '<div><div>';
		}
		if (!$start && !$end)
		{
			$html[] = '</div>';
		}

		return '</div>' . implode('', $html);
		
		}
	}

	

	private function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}
