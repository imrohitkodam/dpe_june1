<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_JLike
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');
JLoader::import('components.com_jlike.models.pathuser', JPATH_SITE);

/**
 * JLike Model Paths
 *
 * @since  1.6
 */
class JLikeModelPathnodegraph extends JModelList
{
	protected $db;

	protected $loggedInUser;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JModelLegacy
	 * @since   12.2
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.path_id',
				'title','a.path_title',
				'description','a.path_description'
			);
		}

		$this->db = JFactory::getDbo();
		$this->loggedInUser = JFactory::getUser();

		parent::__construct($config);
	}

	/**
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  JDatabaseQuery  A JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   12.2
	 */
	protected function getListQuery()
	{
		$query = $this->db->getQuery(true);

		// Select the required fields from the table.
		$query->select('p.path_id, p.path_title, p.path_description,p.params,png.node,png.lft,png.rgt,png.this_compulsory');
		$query->from($this->db->quoteName('#__jlike_paths', 'p'));
		$query->join('LEFT', $this->db->quoteName('#__jlike_pathnode_graph', 'png') .
		' ON (' . $this->db->quoteName('png.path_id') . ' = ' . $this->db->quoteName('p.path_id') . ')');

		// Give only parent paths
		$onlyParent = $this->getState('filter.parent');

		if (!empty($onlyParent))
		{
			$query->where($this->db->quoteName('png.lft') . '=' . $this->db->quote(0));
		}

		// Filter by state
		$state = $this->getState('filter.state');

		if (!empty($state))
		{
			$query->where($this->db->quoteName('p.state') . '=' . $this->db->quote($state));
		}

		// Filter by path type
		$type = $this->getState('filter.path_type');

		if (!empty($type))
		{
			$query->where($this->db->quoteName('p.path_type') . '=' . $this->db->quote($type));
		}

		// Filter by path id
		$pathId = $this->getState('filter.path_id');

		if (!empty($pathId))
		{
			$query->where($this->db->quoteName('p.path_id') . '=' . (int) $pathId);
		}

		// Filter by path alias
		$alias = $this->getState('filter.alias');

		if (!empty($alias))
		{
			$query->where($this->db->quoteName('p.alias') . '=' . $this->db->quote($alias));
		}

		$nodeContent = $this->getState('filter.node_content');

		if (!empty($nodeContent))
		{
			$query->where($this->db->quoteName('png.node') . '=' . (int) $nodeContent);
		}

		// Filter By paths this_compulsory.
		$thisCompulsory = $this->getState('filter.this_compulsory');

		if ($thisCompulsory === "0" || $thisCompulsory)
		{
			$query->where($this->db->quoteName('png.this_compulsory') . '=' . (int) $thisCompulsory);
		}

		// Filter by path id
		$isPath = $this->getState('filter.isPath');

		if (!empty($isPath))
		{
			$query->where('png.isPath = ' . (int) $isPath);
		}

		return $query;
	}

	/**
	 * Method to get user subscribed path.
	 *
	 * @return Object|boolean Object on success, false on failure.
	 *
	 * @throws Exception
	 */
	public function getItems()
	{
		$items = parent::getItems();
		$pathUser_model = JModelLegacy::getInstance('PathUser', 'JLikeModel');
		$pathModel = JModelLegacy::getInstance('Path', 'JLikeModel');

		foreach ($items as $item)
		{
			$item->isSubscribedPath = $pathUser_model->getPathUserDetails($item->path_id, $this->loggedInUser->id);
			$item->isPathOfPaths = $pathModel->isPathOfPaths($item->path_id);
		}

		return $items;
	}
}
