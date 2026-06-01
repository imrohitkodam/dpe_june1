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

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;

/**
 * Item Model for an JMA.
 *
 * @since  1.6
 */
class JmailalertsModelAlert extends AdminModel
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $text_prefix = 'COM_JMAILALERTS';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   3.2
	 */
	public function __construct($config = array())
	{
		$config['event_after_save'] = 'onAfterJmaAlertSave';
		$config['event_after_delete'] = 'onAfterJmaAlertDelete';

		parent::__construct($config);
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 */
	public function getTable($type = 'Alert', $prefix = 'JmailalertsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_jmailalerts.alert', 'alert', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_jmailalerts.edit.alert.data', array());

		if (empty($data))
		{
			$data = $this->getItem();

			// Support for 'multiple' field
			$data->allowed_freq = json_decode($data->allowed_freq);

			// Get the plug-ins names
			$plgNames = $this->getPluginNames();
			$data->plgins = $plgNames;

			// Get plguins descriptions from xml
			if (isset($plgNames))
			{
				$data->plg_des = $this->getPluginDescriptionFromXML($plgNames);
			}
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Do any procesing on fields here if needed
			$item->email_alert_plugin_names = $this->getPluginNames();

			if (isset($item->email_alert_plugin_names))
			{
				$item->plugin_description_array = $this->getPluginDescriptionFromXML($item->email_alert_plugin_names);
			}
		}

		return $item;
	}

	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param   Table  $table  A Table object.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function prepareTable($table)
	{
		if (empty($table->id))
		{
			// Set ordering to the last item if not set
			if (@$table->ordering === '')
			{
				$db = Factory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__jma_alerts');
				$max = $db->loadResult();

				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * Method to set a alert as default.
	 *
	 * @param   mixed  $id  Alert id
	 *
	 * @throws	Exception
	 * @return  boolean  True if successful.
	 */
	public function setDefault($id = 0)
	{
		$db		  = $this->getDbo();
		$ids   = implode(',', $id);

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
	 * @param   mixed  $id  Alert id
	 *
	 * @throws	Exception
	 * @return  boolean  True if successful.
	 */
	public function unsetDefault($id = 0)
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

	/**
	 * Method load_template to load the email template
	 * Model emogrifier use to add inline style
	 * default_template.php contain default template html
	 * mb_convert_encoding php character encoding function
	 * default_template.css default template css
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function loadTemplate()
	{
		require_once JPATH_SITE . '/components/com_jmailalerts/models/emogrifier.php';

		$path = JPATH_SITE . '/components/com_jmailalerts/emails/default_template.php';
		require_once $path;

		$data = array();

		// Condition to check if mbstring is enabled
		if (!function_exists('mb_convert_encoding'))
		{
			echo Text::_("MB_EXT");
			$emorgdata = $emails_config['message_body'];
		}
		else
		{
			$cssfile = JPATH_SITE . '/components/com_jmailalerts/emails/default_template.css';
			$cssdata = file_get_contents($cssfile);

			$emogr         = new TJEmogrifier($emails_config['message_body'], $cssdata);
			$emorgdata     = $emogr->emogrify();
			$emorgdatanew  = stripslashes($emorgdata);

			$data['template'] = $emorgdatanew;
			$data['css']      = $cssdata;

			$emorgdatanew1 = json_encode($data);
		}

		echo $emorgdatanew1;

		jexit();
	}

	/**
	 * Get plugin names from db
	 *
	 * @return  array
	 *
	 * @since   2.4.0
	 */
	public function getPluginNames()
	{
		// FIRST GET THE EMAIL-ALERTS RELATED PLUGINS FRM THE `jos_plugins` TABLE
		$this->_db->setQuery('SELECT element FROM #__extensions WHERE folder = \'emailalerts\'  AND enabled = 1');

		// Get the plugin names and store in an array
		$email_alert_plugins_array = $this->_db->loadColumn();

		return  $email_alert_plugins_array;
	}

	/**
	 * Get plugin desc from xml files
	 *
	 * @param   array  $plugin_array  The primary key related to the alerts which were deleted.
	 *
	 * @return  array
	 *
	 * @since   2.4.0
	 */
	public function getPluginDescriptionFromXML($plugin_array)
	{
		$plugin_description_array = array();

		$i = 0;

		if (count($plugin_array))
		{
			foreach ($plugin_array as $emailalert_plugin)
			{
				// Get the description of the plugin from the XML file
				$data = Installer::parseXMLInstallFile(JPATH_SITE . '/plugins/emailalerts/' . $emailalert_plugin . '/' . $emailalert_plugin . '.xml');

				$plugin_description_array[$i++] = $data['description'];
			}
		}

		return $plugin_description_array;
	}

	/**
	 * Delete #__jma_subscribers if alert is deleted
	 *
	 * @param   array  &$pks  The primary key related to the alerts which were deleted.
	 *
	 * @return  boolean
	 *
	 * @since   3.7.0
	 */
	public function delete(&$pks)
	{
		$subscriptionsData = array();
		$context           = Factory::getApplication()->input->get('option');
		$db                = Factory::getDbo();
		PluginHelper::importPlugin('jmailalert');

		// Get Subscription data for each alert ID
		foreach ($pks as $alertId)
		{
			$query = $db->getQuery(true);
			$query->select('*, title')
				->from($db->qn('#__jma_subscribers'))
				->join('INNER', $db->qn('#__jma_alerts') . 'ON (' . $db->qn('#__jma_alerts.id') . ' = ' . $db->qn('#__jma_subscribers.alert_id') . ')')
				->where($db->qn('#__jma_subscribers.alert_id') . ' = ' . (int) $alertId);

			$db->setQuery($query);
			$subscriptionsData[$alertId] = $db->loadAssocList();
		}

		$return = parent::delete($pks);

		if ($return)
		{
			// Now check to see if this articles was featured if so delete it from the #__content_frontpage table
			$db    = $this->getDbo();
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__jma_subscribers'))
				->where('alert_id IN (' . implode(',', $pks) . ')');
			$db->setQuery($query);
			$db->execute();

			foreach ($subscriptionsData as $subscriptions)
			{
				foreach ($subscriptions as $subscription)
				{
					Factory::getApplication()->triggerEvent('onAfterJmaAlertSubscriptionDelete', array($context, (object) $subscription));
				}
			}
		}

		return $return;
	}
}
