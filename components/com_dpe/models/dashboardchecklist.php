<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Data\DataObject;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Subusers records.
 *
 * @since  1.6
 */
class DpeModelDashboardchecklist extends ListModel
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
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since    1.6
	 */
	protected function populateState($ordering = "a.id", $direction = "DESC")
	{
		$app  = Factory::getApplication();
		$user = Factory::getUser();
		$db = Factory::getDbo();

		// DPE hack  set tag 
		$tags = $app->getUserStateFromRequest($this->context.'.filter.tags', 'filter_tags', '', 'string');


		if($tags)
		{
			$this->setState('filter.tags', $tags);
		}else
		{
			$this->setState('filter.tags','');
		}
		
	}
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   string  $client     Date to be checked
	 * @param   string  $contentId  Date to be checked
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since   1.6
	 */
	public function getBarData($client, $contentId)
	{
		$db    = $this->getDbo();
		$query    = $db->getQuery(true);
		$subquery    = $db->getQuery(true);
		$params = ComponentHelper::getParams('com_dpe');
		$checklistSchool = $params->get('checklistSchool', '0', 'INT');

		$subquery->select('count(*)')
			->from($db->quoteName('#__tjfields_fields'))
			->where($db->quoteName('client') . ' = ' . $db->q($client))
			->where($db->quoteName('type') . ' != "cluster"')
			->where($db->quoteName('state') . ' = ' . $db->q('1'));

		$query->select('sum(case when value = "todo" then 1 else 0 end) todo,
			sum(case when value = "done" then 1 else 0 end) done,
			sum(case when value = "inprogress" then 1 else 0 end) inprogress,(' . $subquery . ') as total')
			->from($db->quoteName('#__tjfields_fields_value'))
			->where($db->quoteName('client') . ' = ' . $db->q($client));

		if ($contentId)
		{
			$query->where($db->quoteName('content_id') . ' = ' . $db->q($contentId));
		}

		$db->setQuery($query);

		$result = $db->loadObject();

		return $result;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   INT  $id  Date to be checked
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since   1.6
	 */
	public function isChecklist($id)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select('t.id')
			->from($db->quoteName('#__tj_ucm_data', 'd'))
			->join('LEFT', $db->quoteName('#__tj_ucm_types', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('d.type_id'));
		$query->where($db->quoteName('t.params') . ' LIKE "%dpe_checklist=1%"')
			->where($db->quoteName('t.state') . ' = ' . $db->q('1'))
			->where($db->quoteName('d.id') . ' = ' . $db->q($id));

		$db->setQuery($query);

		$result = $db->loadObject();

		return $result;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   INT  $client    Date to be checked
	 * @param   INT  $schoolId  Date to be checked
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since   1.6
	 */
	public function checkAllowCount($client, $schoolId)
	{
		$params = ComponentHelper::getParams('com_dpe');

		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select('*')
			->from($db->quoteName('#__tj_ucm_data'));

		if ($client)
		{
			$query->where($db->quoteName('client') . ' = ' . $db->q($client));
		}

		$query->where($db->quoteName('cluster_id') . ' = ' . $db->q($schoolId));

		$db->setQuery($query);

		$result = $db->loadObject();

		return $result;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since   1.6
	 */
	public function getlistData($clusterId)
	{
		$db    = $this->getDbo();
		$subQuery = $db->getQuery(true);
		$query    = $db->getQuery(true);
		$clusterId = ($clusterId)?$clusterId:$this->getState('filter.cluster_id');
		$params = ComponentHelper::getParams('com_dpe');
		$checklistSchool = $params->get('checklistSchool', '0', 'INT');


		$subQuery->select('ucm.id')
			->from($db->quoteName('#__tj_ucm_data', 'ucm'))
			->where($db->quoteName('ucm.cluster_id') . ' = ' . $db->q($clusterId));

		$query->select(array('t.id as type_id', 't.title', 'd.modified_date', 'd.client', 'd.id', 't.unique_identifier'))
			->from($db->quoteName('#__tj_ucm_types', 't'))
			->join('LEFT', $db->quoteName('#__tj_ucm_data', 'd') . ' ON ' . $db->quoteName('d.type_id') . ' = ' . $db->quoteName('t.id')
			. ' AND d.id IN (' . $subQuery . ')')
			->where($db->quoteName('t.params') . ' LIKE "%dpe_checklist=1%"')
			->where($db->quoteName('t.state') . ' = ' . $db->q('1'))
			->group($db->quoteName('t.id'))->order('t.ordering ASC');

		$db->setQuery($query);
		$result = $db->loadObjectList();

		return $result;
	}



	
}
