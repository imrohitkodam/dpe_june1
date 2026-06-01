<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of js fields
 *
 * @since  2.5.1
 */
class JFormFieldJsprofiletype extends \JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var string
	 * @since 1.6
	 */
	protected $type = 'Jsprofiletype';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return  array  An array of JHtml options.
	 *
	 * @since   2.5.1
	 */
	protected function getOptions()
	{
		// Get the database object and a new query object.
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);

		$query = "SELECT id AS value, name AS text FROM `#__community_profiles` WHERE published = 1";
		$db->setQuery($query);
		$options = $db->loadObjectList();

		if ($options)
		{
			foreach ($options as $i => $option)
			{
				$options[$i]->text = Text::_($option->text);
			}

			// Merge any additional options in the XML definition.
			return array_merge(parent::getOptions(), $options);
		}
		else
		{
			return Text::_('NO_JSPROFILETYPE_FOUND');
		}
	}
}
