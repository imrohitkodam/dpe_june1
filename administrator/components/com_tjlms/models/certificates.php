<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Tjlms
 * @author     Parth Lawate <contact@techjoomla.com>
 * @copyright  2016 Parth Lawate
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Utilities\ArrayHelper;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Certificates records.
 *
 * @since       1.6
 * @deprecated  1.3.32 Use TJCertificate certificates models instead
 */
class TjlmsModelCertificates extends ListModel
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
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'ordering', 'a.ordering',
				'state', 'a.state',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by',
				'title', 'a.title',
				'body', 'a.body',
				'access', 'a.access',
				'created_date', 'a.created_date',
				'modified_date', 'a.modified_date',
			);
		}

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
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_tjlms');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.title', 'asc');
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return   string A store id.
	 *
	 * @since    1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   JDatabaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		$user = Factory::getUser();
		$tjlmsParams = ComponentHelper::getParams('com_tjlms');
		$showUserOrUsername = $tjlmsParams->get('show_user_or_username', 'name');

		$query = $this->_db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'DISTINCT a.*'
			)
		);
		$query->from($this->_db->qn('#__tjlms_certificate_template', 'a'));

		// Join over the users for the checked out user
		$query->select($this->_db->qn('uc.name', 'editor'));
		$query->join('LEFT', $this->_db->qn('#__users', 'uc') . ' ON (' . $this->_db->qn('uc.id') . '=' . $this->_db->qn('a.checked_out') . ')');

		// Join over the user field 'created_by'
		if ($showUserOrUsername)
		{
			$query->select('IF(created_by.username IS NULL,"' . Text::_('COM_TJLMS_BLOCKED_USER') . '",created_by.username) AS created_by');
		}
		else
		{
			$query->select('IF(created_by.name IS NULL,"' . Text::_('COM_TJLMS_BLOCKED_USER') . '",created_by.name) AS created_by');
		}

		$query->join('LEFT', $this->_db->qn('#__users', 'created_by') .
		'ON(' . $this->_db->qn('created_by.id') . ' = ' . $this->_db->qn('a.created_by') . ')');

		// Join over the user field 'modified_by'
		$query->select($this->_db->qn('modified_by.name', 'modified_by'));
		$query->join('LEFT', $this->_db->qn('#__users', 'modified_by') .
		' ON(' . $this->_db->qn('modified_by.id') . ' = ' . $this->_db->qn('a.modified_by') . ')');

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where($this->_db->qn('a.state') . ' = ' . (int) $published);
		}
		else
		{
			$query->where($this->_db->qn('a.state') . ' IN (0, 1)');
		}

		$certCreated = $this->getState('filter.created_by');

		if ($certCreated)
		{
			$query->where($this->_db->qn('a.created_by') . ' = ' . (int) $certCreated);
		}

		// Here a.access is a indicate certificate is private or public
		$query->where('(' . $this->_db->quoteName('a.access') . ' = 1 OR ' .
		$this->_db->quoteName('a.created_by') . ' = ' . $this->_db->quote((int) $user->id) . ')');

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($this->_db->qn('a.id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $this->_db->q('%' . $this->_db->escape($search, true) . '%');
				$query->where('(' . $this->_db->qn('a.title') . ' LIKE ' . $search . ')');
			}
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Get an array of data items
	 *
	 * @return mixed Array of data items on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

		foreach ($items as $oneItem)
		{
			$oneItem->access = Text::_('COM_TJLMS_CERTIFICATES_ACCESS_OPTION_' . strtoupper($oneItem->access));
		}

		return $items;
	}

	/**
	 * Get an array of certificates ids which is used for courses
	 *
	 * @param   ARRAY  $certIds  array of certificate ids.
	 *
	 * @return Array of certificate ids.
	 */
	public function getCourseCertificates($certIds)
	{
		ArrayHelper::toInteger($certIds);

		try
		{
			$query = $this->_db->getQuery('true');
			$query->select('DISTINCT c.certificate_id');
			$query->from($this->_db->qn('#__tjlms_courses', 'c'));
			$query->where($this->_db->qn('c.state') . ' = 1');
			$query->where($this->_db->qn('c.certificate_id') . ' IN(' . implode(",", $certIds) . ')');
			$this->_db->setQuery($query);

			return $this->_db->loadColumn();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Replace the old certificate tags with new tags
	 *
	 * @param   ARRAY  $oldTags  array of old certificate tags.
	 * @param   ARRAY  $newTags  array of new certificate tags
	 *
	 * @return Array of certificate ids.
	 */
	public function updateTags($oldTags, $newTags)
	{
		$certificates = $this->getItems();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

		foreach ($certificates as $certificate)
		{
			$certBody = str_replace($oldTags, $newTags, $certificate->body);
			$certTemplateTable = Table::getInstance('certificatetemplate', 'TjlmsTable', array('dbo', $this->_db));
			$certTemplateTable->load(array('id' => $certificate->id));
			$certTemplateTable->body = $certBody;
			$certTemplateTable->store();
		}
	}
}
