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
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of categories
 *
 * @since  2.5.1
 */
class JFormFieldCbfieldsgender extends \JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var string
	 * @since 1.6
	 */
	protected $type = 'Cbfieldsgender';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return  array  An array of JHtml options.
	 *
	 * @since   2.5.1
	 */
	protected function getOptions()
	{
		$cbFolder = JPATH_ADMINISTRATOR . '/components/com_comprofiler';

		if (!Folder::exists($cbFolder))
		{
			return array();
		}

		// Get the database object and a new query object.
		$db      = Factory::getDBO();
		$query   = $db->getQuery(true);

		// Build the query.
		$type = "'text','textarea','select','multiselect','checkbox','multicheckbox','radio'";
		$query = "SELECT name AS value, title AS text
		 FROM #__comprofiler_fields
		 WHERE `table` LIKE '#__comprofiler'
		 AND published = 1
		 AND type IN (" . $type . ")";

		// Set the query and load the options.
		$db->setQuery($query);

		try
		{
			$options = $db->loadObjectList();
		}
		catch (Exception $e)
		{
			// JError::raiseWarning(500, $db->getErrorMsg());

			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}

		if ($options)
		{
			foreach ($options as $i => $option)
			{
				$options[$i]->text = Text::_($option->text);
			}

			// Merge any additional options in the XML definition.
			$options = array_merge(parent::getOptions(), $options);

			return $options;
		}
		else
		{
			return Text::_('NO_GENDER_FIELDS_FOUND');
		}
	}
}
