<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

JFormHelper::loadFieldClass('list');

use Joomla\CMS\Language\LanguageHelper;

class JFormFieldRsticketsprolang extends JFormFieldList
{
	protected function getOptions()
	{
		// Initialize variables.
		$options = array();

		$languages = LanguageHelper::getKnownLanguages();

		foreach ($languages as $language => $properties)
		{
			$options[] = JHtml::_('select.option', $language, $properties['name']);
		}

		reset($options);
		
		return $options;
	}
}