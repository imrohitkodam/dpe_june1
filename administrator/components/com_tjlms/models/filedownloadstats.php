<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Class supporting a list of filedownloadstats records.
 *
 * @since  1.4.0
 */
class TjlmsModelFileDownloadStats extends ListModel
{
	public $columnsWithDirectSorting;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.4.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.`id`',
				'course_id', 'a.`course_id`',
				'downloads', 'a.`downloads`',
				'file_id', 'a.`file_id`',
				'user_id', 'a.`user_id`',
				'title', 'b.org_filename'
			);
		}

		$this->columnsWithDirectSorting = array('b.org_filename');

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * This method should only be called once per instantiation and is designed
	 * to be called on the first call to the getState() method unless the model
	 * configuration flag to ignore the request is set.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.4.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$input         = Factory::getApplication()->input;
		$this->context .= $input->get('layout');

		// List state information.
		parent::populateState('id', 'ASC');

		$context = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $context);

		if (JVERSION < '4.0.0')
		{
			// Split context into component and optional section
			$parts = FieldsHelper::extract($context);

			if ($parts)
			{
				$this->setState('filter.component', $parts[0]);
				$this->setState('filter.section', $parts[1]);
			}
		}
	}

	/**
	 * Method to get a \JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  \JDatabaseQuery  A \JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.4.0
	 */
	protected function getListQuery()
	{
		$input = Factory::getApplication()->input;

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'DISTINCT a.*'
			)
		);

		$query->select("COUNT('a.file_id') as download_count");
		$query->select($db->qn("b.org_filename", "title"));

		$query->from('`#__tjlms_file_download_stats` AS a');
		$query->join('left', $db->qn('#__tjlms_media', 'b') .
		' ON (' . $db->qn('a.file_id') . ' = ' . $db->qn('b.id') . ')');
		$query->join('left', $db->qn('#__users', 'u') .
		' ON (' . $db->qn('a.user_id') . ' = ' . $db->qn('u.id') . ')');

		// Filter by search in title
		$search = $this->getState('filter.search');

		if ($this->context == 'com_tjlms.filedownloadstatsmodal')
		{
			$fileId = $input->get('fileId');

			$query->where($db->qn('a.file_id') . ' = ' . (int) $fileId);

			$search = $db->q('%' . $db->escape($search, true) . '%');
			$query->where('( u.name LIKE ' . $search . ')');

			// Group by the id.
			$query->group($db->qn('a.id'));
		}
		else
		{
			if (!empty($search))
			{
				if (stripos($search, 'id:') === 0)
				{
					$query->where('a.id = ' . (int) substr($search, 3));
				}
				else
				{
					$search = $db->q('%' . $db->escape($search, true) . '%');
					$query->where('( a.id LIKE ' . $search . '  OR  b.org_filename LIKE ' . $search . ')');
				}
			}

			// Group by the file id.
			$query->group($db->qn('a.file_id'));
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'name');
		$orderDirn = $this->state->get('list.direction', 'ASC');

		if ($orderCol && $orderDirn)
		{
			if (in_array($orderCol, $this->columnsWithDirectSorting))
			{
				$query->order($db->escape($orderCol . ' ' . $orderDirn));
			}
		}

		return $query;
	}

	/**
	 * Delete the file download stats entries.
	 *
	 * @param   ARRAY  $cid  array of download stats id
	 *
	 * @return  Boolean  true on success and false for failed condition.
	 *
	 * @since   1.4.0
	 */
	public function delete($cid)
	{
		try
		{
			ArrayHelper::toInteger($cid);
			$fileIds = array();

			foreach ($cid as $eachId)
			{
				$query = $this->_db->getQuery(true);

				$query->select($this->_db->qn('file_id'));
				$query->from($this->_db->qn('#__tjlms_file_download_stats'));
				$query->where($this->_db->qn('id') . ' = ' . (int) $eachId);
				$this->_db->setQuery($query);
				$fileIds[] = $this->_db->loadresult();
			}

			if (is_array($fileIds) && !empty($fileIds))
			{
				$groupToDelet = implode(',', $fileIds);
				$query = $this->_db->getQuery(true);

				// Delete all files as selected
				$query->delete($this->_db->qn('#__tjlms_file_download_stats'));
				$query->where($this->_db->qn('file_id') . ' IN ( ' . $groupToDelet . ' )');

				$this->_db->setQuery($query);

				if (!$this->_db->execute())
				{
					echo $this->_db->getErrorMsg();

					return false;
				}
			}

			return true;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}
}
