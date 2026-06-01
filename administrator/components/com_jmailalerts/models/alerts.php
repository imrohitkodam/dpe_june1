<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of Jmailalerts records.
 *
 * @since  1.6
 */
class JmailalertsModelalerts extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   3.2
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'ordering', 'a.ordering',
				'state', 'a.state',
				'title', 'a.title',
				'description', 'a.description',
				'allow_users_select_plugins', 'a.allow_users_select_plugins',
				'respect_last_email_date', 'a.respect_last_email_date',
				'is_default', 'a.is_default',
				'allowed_freq', 'a.allowed_freq',
				'default_freq', 'a.default_freq',
				'email_subject', 'a.email_subject',
				'template', 'a.template',
				'template_css', 'a.template_css',
				'batch_size', 'a.batch_size',
				'enable_batch', 'a.enable_batch',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState($ordering = 'id', $direction = 'desc')
	{
		// Load the filter state
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Load the filter state
		$defaultAlert = $this->getUserStateFromRequest($this->context . '.filter.defaultalerttype', 'defaultalerttype');
		$this->setState('filter.defaultalerttype', $defaultAlert);

		// Split context into component and optional section

		/*
		$parts = FieldsHelper::extract($search);

		if ($parts)
		{
			$this->setstate('filter.component', $parts[0]);
			$this->setstate('filter.section', $parts[1]);
		}
		*/

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on the model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  An identifier string to generate the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Method to get a \JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  \JDatabaseQuery  A \JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db		   = $this->getDbo();
		$query	 = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.*'
			)
		);
		$query->from('`#__jma_alerts` AS a');

		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->select('freq.name AS frequencyname,freq.id freqid');
		$query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
		$query->join('LEFT', '#__jma_frequencies AS freq ON freq.id=a.default_freq');

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where('a.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.state IN (0, 1))');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where(' a.title LIKE ' . $search);
			}
		}

		// Filtering through is_default
		$filterIsDefault = $this->state->get("filter.is_default");

		if ($filterIsDefault != '')
		{
			$query->where("a.is_default = '" . $db->escape($filterIsDefault) . "'");
		}

		// Add the list ordering clause.
		$orderCol	  = $this->state->get('list.ordering');
		$orderDirn	 = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Get plugin names
	 *
	 * @param   string  $template template string.
	 *
	 * @return  array|string  Plugin name as array of error msg
	 *
	 * @since    1.6
	 */
	public function getPluginNames($template)
	{
		$this->_db->setQuery('SELECT name, element,params FROM #__extensions WHERE folder=\'emailalerts\' AND enabled = 1');

		// Return the plugin data array
		$plugcompair = $this->_db->loadObjectList();

		foreach ($plugcompair as $plg)
		{
			if (strstr($template, '[' . $plg->element . ']'))
			{
				$plugname[] = $plg->element;
			}
		}

		if (isset($plugname[0]))
		{
			return implode(', ', $plugname);
		}
		else
		{
			return Text::_('COM_JMAILALERTS_NO_PLUGINS_ENABLED_OR_INSTALLED');
		}
	}

	/**
	 * Method to set a alert as default.
	 *
	 * @param   int|array  $id  The primary key ID for the alert
	 *
	 * @return  boolean  True if successful.
	 *
	 * @throws  Exception
	 *
	 * @since    1.6
	 */
	public function setDefault($id = array())
	{
		$db	 = $this->getDbo();
		$ids = implode(',', $id);
		$db->setQuery(
			'UPDATE #__jma_alerts' .
			' SET is_default = \'1\'' .
			' WHERE id IN( ' . $ids . ')'
		);
		$db->execute();

		// Clean the cache.
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to unset a alert as default.
	 *
	 * @param   int|array  $id  The primary key ID for the alert
	 *
	 * @return  boolean  True if successful.
	 *
	 * @throws	Exception
	 *
	 * @since    1.6
	 */
	public function unsetDefault($id = array())
	{
		$db  = $this->getDbo();
		$ids = implode(',', $id);
		$db->setQuery(
			'UPDATE #__jma_alerts' .
			' SET is_default = \'0\'' .
			' WHERE id IN (' . $ids . ')'
		);
		$db->execute();

		// Clean the cache.
		$this->cleanCache();

		return true;
	}
}
