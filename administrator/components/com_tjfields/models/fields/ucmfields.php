<?php
/**
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of users
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldUcmFields extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	__DEPLOY_VERSION__
	 */
	protected $type = 'ucmfields';

	/**
	 * Fiedd to decide if options are being loaded externally and from xml
	 *
	 * @var		integer
	 * @since	__DEPLOY_VERSION__
	 */
	protected $loadExternally = 0;

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$app = Factory::getApplication()->input;
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('name, title');
		$query->from($db->quoteName('#__tjfields_fields'));
		$query->where($db->quoteName('client') . ' = ' . $db->q($app->get('client')));
		$query->where($db->quoteName('state') . ' = 1');
		$db->setQuery($query);
		$fields = $db->loadObjectList();

		$options = array();

		foreach ($fields as $field)
		{
			$options[] = HTMLHelper::_('select.option', $field->name, $field->title);
		}

		return $options;
	}

	/**
	 * Method to get a list of options for a list input externally and not from xml.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getOptionsExternally()
	{
		$this->loadExternally = 1;

		return $this->getOptions();
	}
}
