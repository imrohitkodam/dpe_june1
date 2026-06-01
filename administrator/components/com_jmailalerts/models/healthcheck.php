<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Simulate model class
 *
 * @package  JMailAlerts
 *
 * @since    2.5.0
 */
class JMailalertsModelHealthCheck extends BaseDatabaseModel
{
	/**
	 * Get health check data
	 *
	 * @return  array
	 */
	public function healthcheck()
	{
		$data = array();

		$data['installed'] = $this->installedPlugins();
		$data['enable']    = $this->enabledPlugins();
		$data['plgname']   = $this->getPluginNames();
		$data['alerts']    = $this->createdAlerts();
		$data['published'] = $this->publishedAlerts();
		$data['defaults']  = $this->defaultAlerts();
		$data['synced']    = $this->syncedAlerts();

		return $data;
	}

	/**
	 * Get installed jmailalerts plugin count
	 *
	 * @return  array
	 */
	public function installedPlugins()
	{
		$this->_db->setQuery('SELECT COUNT(e.extension_id)  AS number FROM #__extensions AS e WHERE folder=\'emailalerts\' ');
		$installplg = $this->_db->loadResult();

		return $installplg;
	}

	/**
	 * Get enabled jmailalerts plugin count
	 *
	 *
	 * @return  array
	 */
	public function enabledPlugins()
	{
		$this->_db->setQuery('SELECT COUNT(e.extension_id) AS number FROM #__extensions AS e WHERE folder=\'emailalerts\' AND e.enabled = \'1\' ');
		$enableplg = $this->_db->loadResult();

		return $enableplg;
	}

	/**
	 * Get alerts count
	 *
	 * @return  array
	 */
	public function createdAlerts()
	{
		$this->_db->setQuery("SELECT COUNT(al.id) AS number FROM #__jma_alerts AS al ");
		$created = $this->_db->loadResult();

		return $created;
	}

	/**
	 * Get Plug names
	 *
	 * @return  obect|string
	 */
	public function getPluginNames()
	{
		$this->_db->setQuery('SELECT name, enabled, element FROM #__extensions WHERE folder=\'emailalerts\' ORDER BY element');
		$plugname = $this->_db->loadObjectList();

		return $plugname = (!empty($plugname)) ? $plugname : Text::_('NO_PLUGINS_ENABLED_OR_INSTALLED');
	}

	/**
	 * Get published alert count
	 *
	 *
	 * @return  array
	 */
	public function publishedAlerts()
	{
		$this->_db->setQuery("SELECT COUNT(al.id) AS number FROM #__jma_alerts AS al WHERE al.state = '1' ");
		$created = $this->_db->loadResult();

		return $created;
	}

	/**
	 * Get default alerts count
	 *
	 *
	 * @return  array
	 */
	public function defaultAlerts()
	{
		$this->_db->setQuery("SELECT al.id FROM #__jma_alerts AS al WHERE al.is_default = 1 ");
		$default = $this->_db->loadColumn();
		$default = (!empty($default['0'])) ? COUNT($default) : 0;

		return $default;
	}

	/**
	 * Get syned alerts count
	 *
	 *
	 * @return  array
	 */
	public function syncedAlerts()
	{
		$this->_db->setQuery("SELECT COUNT(DISTINCT(ea.alert_id)) AS number FROM #__jma_subscribers AS ea ");
		$synced	= $this->_db->loadResult();

		return $synced;
	}
}
