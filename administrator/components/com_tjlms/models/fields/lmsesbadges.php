<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\ListField;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of categories
 *
 * @since  1.0.0
 */
class JFormFieldLmsesbadges extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'lmsesbadges';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of HTMLHelper options.
	 *
	 * @since   11.4
	 */
	protected function getOptions()
	{
		$db = Factory::getDbo();
		$input = Factory::getApplication()->input;
		$query = $db->getQuery(true);
		$query->select('a.*');
		$query->from('`#__social_badges` AS a');
		$query->where('extension="com_tjlms"');
		$db->setQuery($query);
		$esbadges = $db->loadObjectList();

		$options = array();
		$prop = " class='inputbox' ";
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_SELECT'));

		foreach ($esbadges as $key => $obj)
		{
			$options[] = HTMLHelper::_('select.option', $obj->id, $obj->title);
		}

		if (!$this->value)
		{
			$this->value = $options[0]->value;
		}

		$dropdown = HTMLHelper::_('select.genericlist', $options, $this->name, $prop, 'value', 'text', $this->value);

		return $dropdown;
	}
}
