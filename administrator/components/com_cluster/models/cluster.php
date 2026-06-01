<?php
/**
 * @package    Cluster
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Object\CMSObject;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

/**
 * Item Model for an Cluster.
 *
 * @since  1.0.0
 */
class ClusterModelCluster extends AdminModel
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
		$form = $this->loadForm('com_cluster.cluster', 'cluster', array('control' => 'jform', 'load_data' => $loadData));

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
	public function getTable($type = 'Clusters', $prefix = 'ClusterTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');

		return Table::getInstance($type, $prefix, $config);
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
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_cluster.edit.cluster.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data
	 *
	 * @return boolean
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function save($data)
	{
		$pk   = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('cluster.id');

		$cluster = ClusterCluster::getInstance($pk);

		// Bind the data.
		if (!$cluster->bind($data))
		{
			$this->setError($cluster->getError());

			return false;
		}

		$result = $cluster->save();

		// Store the data.
		if (!$result)
		{
			$this->setError($cluster->getError());

			return false;
		}

		$this->setState('cluster.id', $cluster->id);

		return true;
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
		$id = ($jinput->get('id')) ? $jinput->get('id') : $jinput->get('id');
		$this->setState('cluster.id', $id);
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  \CMSObject|boolean  Object on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);

		// @TODO This will not required once we decide the rendering mechanisam
		if (property_exists((string)$item, 'params'))
		{
			$registry = new Registry($item->params);
			$item->params = $registry->toString();
		}

		return $item;
	}

	/**
	 * Method to get Cluster details by Client
	 *
	 * @param   string  $client    Cluster client
	 *
	 * @param   int     $clientId  Cluster client id
	 *
	 * @return  ClusterCluster  The Cluster object
	 *
	 * @since 1.0.0
	 */
	public static function getClusterByClient($client, $clientId)
	{
		// DPE hack can go in core
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->from($db->qn('#__tj_clusters'))
			->where($db->qn('state') . ' = ' . (int) 1);

			if ($client)
			{
				$query->where($db->qn('client') . ' = ' . $db->q($client));
			}

			if ($clientId)
            {
				$query->where($db->qn('client_id') . ' = ' . $db->q($clientId));
			}
		$query->setLimit(1);	
		
		$db->setQuery($query);

		return ClusterCluster::getInstance($db->loadResult());
	}
}
