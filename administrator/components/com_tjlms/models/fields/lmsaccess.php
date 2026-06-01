<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

namespace Joomla\CMS\Form\Field;

defined('JPATH_BASE') or die;

use Joomla\CMS\Form\Field\ListField;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of categories
 *
 * @since  1.0
 */
class JFormFieldLmsaccess extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.0
	 */
	protected $type = 'Lmsaccess';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   11.4
	 */
	protected function getOptions()
	{
		$db = Factory::getDbo();
		$input = Factory::getApplication()->input;
		$query = $db->getQuery(true);
		$query->select('a.*');
		$query->from('`#__viewlevels` AS a');
		$db->setQuery($query);
		$res = $db->loadObjectList();

		$options   = array();
		$prop = " class='inputbox' ";

		foreach ($res as $key => $obj)
		{
			$options[] = JHTML::_('select.option', $obj->id, $obj->title);
		}

		// Merge any additional options in the XML definition.
		$options = array_merge(parent::getOptions(), $options);

		return $options;
	}
}
