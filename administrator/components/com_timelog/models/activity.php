<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\MVC\Model\AdminModel;

JLoader::import('components.com_timelog.includes.timelog', JPATH_ADMINISTRATOR);

/**
 * Timelog model.
 *
 * @since  __DEPLOY_VERSION__
 */

class TimelogModelActivity extends AdminModel
{
	/**
	 * @var     string    The prefix to use with controller messages.
	 * @since   __DEPLOY_VERSION__
	 */
	protected $text_prefix = 'COM_TIMELOG';

	/**
	 * @var   string  Alias to manage history control
	 * @since   __DEPLOY_VERSION__
	 */
	public $typeAlias = 'com_timelog.activity';

	/**
	 * @var null  Item data
	 * @since  __DEPLOY_VERSION__
	 */
	protected $item = null;

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \Form|boolean  A Form object on success, false on failure
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Get the form.
		// Check admin and load admin form in case of admin venue form
		if ($app->isClient('administrator'))
		{
			$form = $this->loadForm('com_timelog.activity', 'activity', array('control' => 'jform', 'load_data' => $loadData));
		}
		else
		{
			$form = $this->loadForm('com_timelog.activityform', 'activityform', array('control' => 'jform', 'load_data' => $loadData));
		}

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	$data  The data for the form.
	 *
	 * @since	__DEPLOY_VERSION__
	 */
	protected function loadFormData()
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Check admin and load admin form in case of admin activity form
		if ($app->isClient('administrator'))
		{
			// Check the session for previously entered form data.
			$data = $app->getUserState('com_timelog.edit.activity.data', array());
		}
		else
		{
			$data = $app->getUserState('com_timelog.edit.activityform.data', array());
		}

		if (empty($data))
		{
			if ($this->item === null)
			{
				$this->item = $this->getItem();
			}

			$data = $this->item;

			// Support for multiple or not foreign key field: activity_type_id
			$array = array();

			foreach ((array) $data->activity_type_id as $value)
			{
				if (!is_array($value))
				{
					$array[] = $value;
				}
			}

			if (!empty($array))
			{
				$data->activity_type_id = $array;
			}
		}

		return $data;
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return    Table    A database object
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getTable($type = 'Activity', $prefix = 'TimelogTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_timelog/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since  __DEPLOY_VERSION__
	 */
	public function save($data)
	{
		$id    = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('activity.id');
		$state = (!empty($data['state'])) ? 1 : 0;
		$user  = Factory::getUser();

		// DPE Hack - Check if you have access timelog for a activity
		JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

		if ((!$data['client_id']) || !($data['license_id']))
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);

			return;
		}

		$slaSlaActivity = SlaSlaActivity::getInstance($data['client_id']);

		if (empty($slaSlaActivity->id))
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);

			return;
		}

		if (property_exists($slaSlaActivity, 'cluster_id'))
		{
			$clusterId = $slaSlaActivity->cluster_id;
		}

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			if (!$clusterId)
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}

			// DPE hack to check permission
			$canCreateTimelog = RBACL::check($user->id, 'com_cluster', 'core.create.logs', 'com_timelog', $clusterId);

			if (!$canCreateTimelog)
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}
		}

		/*
		if ($id)
		{
			Check the user can edit this item
			$authorised = $user->authorise('core.edit', 'com_timelog') || $authorised = $user->authorise('core.edit.own', 'com_timelog');
		}
		else
		{
			Check the user can create new items in this section
			$authorised = $user->authorise('core.create', 'com_timelog');
		}

		if ($authorised !== true)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
		*/

		$timelog = TimelogActivity::getInstance($id);

		// Bind the data.
		if (!$timelog->bind($data))
		{
			$this->setError($timelog->getError());

			return false;
		}

		$result = $timelog->save();

		// Store the data.
		if (!$result)
		{
			$this->setError($timelog->getError());

			return false;
		}

		// Code - start - To insert xref table entries for timelog media

		if (!empty($data['new_media_ids']))
		{
			// Create TJMediaXref class object
			$modelMediaXref = TJMediaXref::getInstance();

			foreach ($data['new_media_ids'] as $key => $mediaId)
			{
				if ($mediaId)
				{
					$mediaData['id'] = '';
					$mediaData['client_id'] = $timelog->id;
					$mediaData['media_id'] = $mediaId;
					$mediaData['client'] = 'com_timelog.activity';
					$modelMediaXref->bind($mediaData);
					$modelMediaXref->save();
				}
			}
		}

		// Code - end

		$this->setState('activity.id', $timelog->id);

		return true;
	}
}
