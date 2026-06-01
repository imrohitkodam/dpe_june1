<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
use Joomla\Data\DataObject;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);


/**
 * Methods supporting a list of Subusers records.
 *
 * @since  1.6
 */
class DpeModelRoplist extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.6
	 */
	public function __construct($config = array())
	{
		$this->common  = '';

		parent::__construct($config);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   string  $fieldId  Date to be checked
	 * @param   string  $value    Date to be checked
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since   1.6
	 */
	public function getProgressData($fieldId, $value)
	{
		// Table Object
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
		$tjFieldFieldTable->load(array('name' => 'com_tjucm_rop_status'));

		$db       = $this->getDbo();

		// Get UCM IDs whoes field value is as per required business function
		$subQuery = $db->getQuery(true);
		$subQuery->select('fv.content_id');
		$subQuery->from($db->quoteName('#__tjfields_fields_value', 'fv'));
		$subQuery->where($db->quoteName('fv.client') . ' = ' . $db->q('com_tjucm.rop'));
		$subQuery->join('INNER', $db->quoteName('#__tj_ucm_data', 'tju') . ' ON ' . $db->quoteName('tju.id') . ' = ' . $db->quoteName('fv.content_id'));
		$subQuery->where($db->quoteName('tju.draft') . ' = ' . (int) 0);
		$subQuery->where($db->quoteName('fv.field_id') . ' = ' . (int) $fieldId);
		$subQuery->where($db->quoteName('fv.value') . ' = ' . $db->q($value));

		$query    = $db->getQuery(true);

		$query->select('sum(case when fv.value = "Complete" then 1 else 0 end) Completed,
			sum(case when fv.value = "In Progress" then 1 else 0 end) inprogress');

		$query->from($db->quoteName('#__tjfields_fields_value', 'fv'));
		$query->where($db->quoteName('fv.client') . ' = ' . $db->q('com_tjucm.rop'));

		$query->join('INNER', $db->quoteName('#__tj_ucm_data', 'tju') . ' ON ' . $db->quoteName('tju.id') . ' = ' . $db->quoteName('fv.content_id'));

		$query->where($db->quoteName('tju.draft') . ' = ' . (int) 0);
		$query->where($db->quoteName('fv.field_id') . ' = ' . (int) $tjFieldFieldTable->id);
		$query->where($db->quoteName('tju.id') . ' IN (' . $subQuery . ' ) ');

		$clusterIds       = array();
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$user             = Factory::getUser();
		$clusterIds       = array();
		$clusters         = $clusterUserModel->getUsersClusters($user->id);
		$input            = Factory::getApplication()->input;

		// Get UCM type ID for RBACL Check
		$ucmTable         = Table::getInstance('type', 'TjucmTable');
		$ucmTable->load(array('unique_identifier' => 'com_tjucm.rop'));


		foreach ($clusters as $cluster)
		{
			// Check user have permission to manage all clusters
			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				// Check user having permission to add staff
				if (RBACL::check($user->id, 'com_cluster', 'core.viewitemlist.' . $ucmTable->id, 'com_tjucm', $cluster->cluster_id))
				{
					$clusterIds[] = $cluster->cluster_id;
				}
			}
		}

		$clusterId     = $this->getState('filter.cluster_id');
		$filterProcess = $input->get('filter_process', '', STRING);

		if (!$user->authorise('core.manageall', 'com_cluster') && !$clusterId)
		{
			$query->where($db->quoteName('tju.cluster_id') . " IN ('" . implode("','", $clusterIds) . "')");
		}

		if ($clusterId)
		{
			$query->where($db->quoteName('tju.cluster_id') . ' = ' . (int) $clusterId);
		}
		elseif ($filterProcess == 'generic')
		{
			$dpeParam         = ComponentHelper::getParams('com_dpe');

			if ($dpeParam->get('cluster_id', '0', 'INT') > 0)
			{
				$query->where($db->quoteName('tju.cluster_id') . ' = ' . (int) $dpeParam->get('cluster_id', '0', 'INT'));
			}
		}

		$db->setQuery($query);

		$result = $db->loadObject();

		return $result;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   INT  $fieldId  fieldId
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since   1.6
	 */
	public function getOrgnisationCount($fieldId)
	{
		// UCM Table Object
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
		$tjFieldFieldTable->load(array('name' => 'com_tjucm_rop_status'));
		$user              = Factory::getUser();

		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusterIds       = array();
		$clusters         = $clusterUserModel->getUsersClusters($user->id);
		$ucmTable         = Table::getInstance('type', 'TjucmTable');
		$ucmTable->load(array('unique_identifier' => 'com_tjucm.rop'));

		foreach ($clusters as $cluster)
		{
			// Check user have permission to manage all clusters
			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				// Check user having permission to add staff
				if (RBACL::check($user->id, 'com_cluster', 'core.viewitemlist.' . $ucmTable->id, 'com_tjucm', $cluster->cluster_id))
				{
					$clusterIds[] = $cluster->cluster_id;
				}
			}
		}

		if (!$fieldId)
		{
			return;
		}

		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select('DISTINCT tju.cluster_id');
		$query->from($db->quoteName('#__tjfields_fields_value', 'fv'));
		$query->join('INNER', $db->quoteName('#__tj_ucm_data', 'tju') . ' ON ' . $db->quoteName('tju.id') . ' = ' . $db->quoteName('fv.content_id'));
		$query->join('LEFT', $db->quoteName('#__tj_clusters', 'tjc') . ' ON ' . $db->quoteName('tjc.id') . ' = ' . $db->quoteName('tju.cluster_id'));

		$query->where($db->quoteName('fv.field_id') . ' = ' . (int) $fieldId);
		$query->where($db->quoteName('tjc.state') . ' = ' . (int) 1);

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			$query->where($db->quoteName('tju.cluster_id') . " IN ('" . implode("','", $clusterIds) . "')");
		}

		$db->setQuery($query);

		$clusters   = $db->loadColumn();

		return $clusters;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since   1.6
	 */
	public function getlistData()
	{
		$ropbusinessFieldOptions = array();

		$clusterId = $this->getState('filter.cluster_id');

		// While creating a business field need to add same class name
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models');
		$tjfieldsModelFields  = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel', array('ignore_request' => true));
		$tjfieldsModelFields->setState('filter.client', 'com_tjucm.rop');
		$tjfieldsModelFields->setState('filter.validation_class', 'business-function');
		$ropbusinessFieldData = $tjfieldsModelFields->getItems();

		if (!empty($ropbusinessFieldData))
		{
			$businessFunctionFieldId = $this->businessFunctionFieldId = $ropbusinessFieldData[0]->id;

			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models');
			$tjfieldsModelOptions = BaseDatabaseModel::getInstance('Options', 'TjfieldsModel', array('ignore_request' => true));

			$tjfieldsModelOptions->setState('filter.field_id', $businessFunctionFieldId);
			$ropbusinessFieldOptions = $tjfieldsModelOptions->getItems();
		}

		foreach ($ropbusinessFieldOptions as $key => $ropbusinessFieldOption)
		{

			$ropbusinessFieldOptions[$key]->progressData = $this->getProgressData($ropbusinessFieldOption->field_id, $ropbusinessFieldOption->value);

			if (!$clusterId)
			{
				if ($ropbusinessFieldOptions[$key]->field_id)
				{
					$ropbusinessFieldOptions[$key]->cluster_ids_cnt = count($this->getOrgnisationCount($ropbusinessFieldOption->field_id));
				}
			}
		}
		//~ print_r($ropbusinessFieldOptions);
		//~ die;

		return $ropbusinessFieldOptions;
	}
}
