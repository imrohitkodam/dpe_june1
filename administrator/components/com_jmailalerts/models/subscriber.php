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
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;

/**
 * Subscriber model class.
 *
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 * @since       2.6.1
 */
class JmailalertsModelsubscriber extends AdminModel
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
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
		$config['event_after_delete'] = 'onAfterJmaAlertSubscriptionDelete';

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
	public function getTable($type = 'Subscriber', $prefix = 'JmailalertsTable', $config = array())
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
		$form = $this->loadForm('com_jmailalerts.subscriber', 'subscriber', array('control' => 'jform', 'load_data' => $loadData));

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
		$data = Factory::getApplication()->getUserState('com_jmailalerts.edit.subscriber.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
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
				$db->setQuery('SELECT MAX(ordering) FROM #__jma_subscribers');
				$max = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * Method Preview.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function preview()
	{
		$input = Factory::getApplication()->input;
		require_once JPATH_SITE . '/components/com_jmailalerts/models/emails.php';
		require_once JPATH_SITE . '/components/com_jmailalerts/helpers/emailhelper.php';
		$jmaEmailHelper    = new jmailalertsemailhelper;
		$jmaModelEmails    = new jmailalertsModelEmails;
		$today              = $input->get('select_date_box', '', 'STRING');
		$targetUserId       = $input->get('user_id', '', 'INT');
		$alertTypeId        = $input->get('alert_id', '', 'INT');
		$targetEmailId      = $input->get('email_id', '', 'STRING');
		$destinationEmailId = $input->get('send_mail_to_box', '', 'STRING');
		$flag               = $input->get('flag');

		// For registered user
		if ($targetUserId)
		{
			$query = "SELECT  u.id as user_id,u.name,u.email as email_id, u.params, a.template, a.email_subject, e.date,
			e.alert_id, a.template_css, e.plugins_subscribed_to, a.respect_last_email_date"
			. " FROM #__users AS u, #__jma_subscribers AS e , #__jma_alerts AS a"
			. " WHERE e.user_id = " . $targetUserId
			. " AND e.alert_id = " . $alertTypeId
			. " AND u.id = e.user_id"
			. " AND a.id = e.alert_id";
		}
		// Guest user
		else
		{
			$query = "SELECT  e.user_id as user_id,e.name,e.email_id as email_id,
			a.template, a.email_subject,
			e.date, e.alert_id ,a.template_css, e.plugins_subscribed_to, a.respect_last_email_date"
			. " FROM #__jma_subscribers AS e , #__jma_alerts AS a"
			. " WHERE e.email_id = '" . $targetEmailId
			. "' AND e.alert_id = " . $alertTypeId
			. " AND a.id = e.alert_id";
		}

		$this->_db->setQuery($query);
		$targetUserData = $this->_db->loadObjectList();

		$i = 0;

		foreach ($targetUserData as $data)
		{
			if ($data->date)
			{
				$data->date = ($today) ? $today : $data->date;
			}
			else
			{
				$data[$i]->date = ($today) ? $today : $data[$i]->date;
			}

			$i++;
		}

		if ($targetUserData)
		{
			$targetUserData[0]->email = $destinationEmailId;

			// Get template from alert type
			$query = "SELECT template FROM #__jma_alerts WHERE id =$alertTypeId ";
			$this->_db->setQuery($query);
			$msgBody = $this->_db->loadResult();

			$skipTags     = array('[SITENAME]', '[NAME]', '[SITELINK]', '[PREFRENCES]', '[mailuser]');
			$tmplTags     = $jmaModelEmails->get_tmpl_tags($msgBody, $skipTags);
			$rememberTags = $jmaModelEmails->get_original_tmpl_tags($msgBody, $skipTags);
			$response     = $jmaEmailHelper->getMailcontent($targetUserData[0], $flag, $tmplTags, $rememberTags);

			if ($response[1] == 3)
			{
				echo Text::_('COM_JMAILALERTS_NO_MAIL_CONTENT');

				return;
			}

			return $response;
		}
	}

	/**
	 * Method to save an subscription data.
	 *
	 * @param   array  $data  data
	 *
	 * @return  mixed  Id on success and false on failure
	 *
	 * @since    1.6
	 */
	public function save($data)
	{
		if (!empty($data))
		{
			$isNew = true;
			$table = $this->getTable();

			if ($data['id'] != 0)
			{
				$isNew = false;
			}

			// Bind data
			if (!$table->bind($data))
			{
				$this->setError($table->getError());

				return false;
			}

			if (parent::save($data))
			{
				$id = (int) $this->getState($this->getName() . '.id');
				$data['subscriptionId'] = $id;

				PluginHelper::importPlugin('jmailalert');
				Factory::getApplication()->triggerEvent('onAfterJmaAlertSubscriptionSave', array($data, $isNew));

				return $id;
			}
			else
			{
				return false;
			}
		}
	}
}
