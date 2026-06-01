<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.

defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\PluginHelper;

jimport('techjoomla.tjnotifications.tjnotifications');
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Language\Text;

JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);
/**
 * Item Model for an Sla activity.
 *
 * @since  1.0.0
 */
class SlaModelSlaActivity extends AdminModel
{
	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_sla.slaactivity', 'slaactivity', array('control' => 'jform', 'load_data' => $loadData));

		return empty($form) ? false : $form;
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
	public function getTable($type = 'SlaActivities', $prefix = 'SlaTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sla/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return   void
	 *
	 * @since    1.0.0
	 */

	protected function populateState()
	{
		$jinput = Factory::getApplication()->input;
		$id = ($jinput->get('id'))?$jinput->get('id'):$jinput->get('id');
		(!$jinput->get('ticketId'))?$this->setState('slaactivity.id', $id):'';// DPE Hack cant go core.
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	$data  The data for the form.
	 *
	 * @since	1.0.0
	 */
	protected function loadFormData()
	{
		$data = $this->getItem();

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  \CMSObject|boolean  Object on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			if (!empty($item->id))
			{
				JLoader::import('components.com_jlike.tables.todos', JPATH_ADMINISTRATOR);
				$todoTable = Table::getInstance('Todos', 'JlikeTable');
				$todoTable->load(array('parent_id' => $item->todo_id));

				if (property_exists($todoTable, 'assigned_to'))
				{
					$item->cluster_user = $todoTable->assigned_to;
				}

				if (property_exists($todoTable, 'due_date'))
				{
					$item->due_date     = $todoTable->due_date;
				}

				$todoStatusTable = Table::getInstance('Todos', 'JlikeTable');
				$todoStatusTable->load(array('id' => $item->todo_id));

				if (property_exists($todoStatusTable, 'status'))
				{
					$item->todo_status  = $todoStatusTable->status;
				}

				JLoader::import('components.com_cluster.tables.clusters', JPATH_ADMINISTRATOR);
				$clusterTable = Table::getInstance('Clusters', 'ClusterTable');
				$clusterTable->load(array('id' => $item->cluster_id));

				if (property_exists($clusterTable, 'name'))
				{
					$item->organisation  = $clusterTable->name;
				}

				JLoader::import('components.com_sla.tables.slaactivitytypes', JPATH_ADMINISTRATOR);
				$slaActivityTypesTable = Table::getInstance('SlaActivityTypes', 'SlaTable');
				$slaActivityTypesTable->load(array('id' => $item->sla_activity_type_id));

				if (property_exists($slaActivityTypesTable, 'title'))
				{
					$item->activityTypesTitle  = $slaActivityTypesTable->title;
				}

				// Hack needed for DPE
				JLoader::import('contentform', JPATH_SITE . '/components/com_jlike/models');

				$contentData = array();

				$contentData['element']    = 'com_sla.slaactivity';
				$contentData['url']        = 'index.php?option=com_sla&view=slaactivity&layout=edit&id='
. $item->id . '&licence_id=' . $item->license_id;
				$contentData['element_id'] = $item->id;
				$contentId                 = JlikeModelContentForm::getContentID($contentData);

				// Get Lead Consultant Todo
				JLoader::import('components.com_jlike.tables.todos', JPATH_ADMINISTRATOR);
				$leadConsultantTodo = Table::getInstance('Todos', 'JlikeTable');
				$leadConsultantTodo->load(array('id' => $item->todo_id));

				if (property_exists($leadConsultantTodo, 'assigned_to'))
				{
					$item->lead_consultant_id = $leadConsultantTodo->assigned_to;
				}

				/*
				if ($item->cluster_user && $contentId)
				{
					Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jlike/tables');
					$jlikeTodoTable = Table::getInstance('Todos', 'JlikeTable');
					$jlikeTodoTable->load(array('content_id' => $contentId, 'assigned_to' => $item->cluster_user));

					if (property_exists($jlikeTodoTable, 'parent_id'))
					{
						$leadConsultantTodo = Table::getInstance('Todos', 'JlikeTable');
						$leadConsultantTodo->load(array('id' => $jlikeTodoTable->parent_id));

						if (property_exists($leadConsultantTodo, 'assigned_to'))
						{
							$item->lead_consultant_id = $leadConsultantTodo->assigned_to;
						}
					}
				}
				*/
			}
		}

		return $item;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function save($data)
	{
		$pk = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('slaactivity.id');

		$isNew = $pk ? false : true;

		// Get null data time
		$db = Factory::getDbo();
		$nullDate = $db->getNullDate();

		// Get SLA details
		$slaClusterXrefTable = SlaFactory::table("slaclusterxrefs");
		$slaClusterXrefTable->load(array('license_id' => $data['license_id']));

		$todoData                = array();
		$todoData['id']          = $data['todo_id'];
		$todoData['assigned_to'] = $data['lead_consultant_id'];
		$todoData['start_date']  = (!empty($data['start_date'])) ? $data['start_date'] : $nullDate;
		$todoData['due_date']    = (!empty($data['due_date'])) ? $data['due_date'] : $nullDate;
		$todoData['title']       = $data['activity_name'];
		$todoData['sender_msg']  = $data['activity_desc'];
		$todoData['ideal_time']  = $data['ideal_time'];

		$slaServiceObj = SlaSlaService::getInstance($service->id);
		$jlikeTodoId   = $slaServiceObj->saveTodo($todoData);

		// Save SLA activities
		$slaSlaActivity                       = SlaSlaActivity::getInstance($pk);
		$slaSlaActivity->id                   = $pk;
		$slaSlaActivity->sla_activity_type_id = $data['sla_activity_type_id'];
		$slaSlaActivity->sla_id               = $slaClusterXrefTable->sla_id;
		$slaSlaActivity->cluster_id           = $slaClusterXrefTable->cluster_id;
		$slaSlaActivity->license_id           = $data['license_id'];
		$slaSlaActivity->todo_id              = $jlikeTodoId;
		$slaSlaActivity->created_on           = $data['created_on'];


		$licenceTable = Multiagency::table('licence');
		$licenceTable->load(array('id' => $data['license_id']));

		// If licence is in upcoming state then add activity in upcoming state
		if ($licenceTable->state == 3)
		{
			$slaSlaActivity->state = 3;
		}

		// Store the data.
		if ($slaSlaActivity->save())
		{
			$this->setState('slaactivity.id', $slaActivity->id);

			// Add/Update record in #_jlike_content #_jlike_todos only when user set assigned_to
			$slaSlaActivity->slaActivityData = $data;
			PluginHelper::importPlugin('system');
			Factory::getApplication()->triggerEvent('onAfterSlaActivitySave', array($slaSlaActivity, $isNew));

			return $slaSlaActivity;
		}
		else
		{
			$this->setError($slaSlaActivity->getError());

			return false;
		}
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   array  &$id  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   1.6
	 */
	public function delete(&$id)
	{
		$id = (!empty($id)) ? $id : (int) $this->getState('slaactivity.id');

		// DPE Hack - Check if you have access timelog for a activity
		$slaSlaActivity = SlaSlaActivity::getInstance($id);
		$user           = Factory::getUser();

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
			$canDeleteActivity = RBACL::check($user->id, 'com_cluster', 'core.delete.activity', 'com_sla', $clusterId);

			if (!$canDeleteActivity)
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}
		}

		$table = $this->getTable();

		if ($table->delete($id) === true)
		{
			return $id;
		}
		else
		{
			return false;
		}

		return true;
	}
}
