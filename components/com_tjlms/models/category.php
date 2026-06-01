<?php
/**
 * @package    LMS_Shika
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Categories\Categories;
jimport('joomla.application.component.model');

/**
 * Model for courses
 *
 * @since  1.0
 */

class TjlmsModelCategory extends ListModel
{
	private $catParent = null;

	private $items = null;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   2.2
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id',
				'a.id',
				'title',
				'a.title',
				'state',
				'a.state'
			);
		}

		parent::__construct($config);
	}

	/**
	 * getcoursesByCats.
	 *
	 * @param   INTEGER  $cat_id  An optional associative array of configuration settings.
	 *
	 * @return  categories
	 *
	 * @since   2.2
	 */
	public function getcoursesByCats($cat_id)
	{
		try
		{
			$input = Factory::getApplication()->input;
			$catWhere = $this->getWhereCategory($cat_id);
			$db = $this->getDbo();
			$input = Factory::getApplication()->input;
			$query = $db->getQuery(true);
			$query->select('c.*');
			$query->from($db->qn('#__categories', 'c'));
			$query->where("c.extension = 'com_tjlms'");

			if ($catWhere)
			{
				foreach ($catWhere as $cw)
				{
					$query->where($cw);
				}
			}

			// Added to fix the subcategory ordering issue
			$query->order($db->qn('c.lft'));
			$db->setQuery($query);
			$categories = $db->loadobjectList();

			foreach ($categories  as $index => $category)
			{
				if ($category->id != $cat_id)
				{
					$query = $db->getQuery(true);
					$query->select('a.*');
					$query->from($db->qn('#__tjlms_courses', 'a'));
					$query->JOIN('INNER', '`#__categories` AS c ON c.id=a.catid');
					$query->where($db->qn('a.catid') . '=' . $db->q((int) $category->id));
					$query->where("a.state = 1");
					$query->order($db->escape('a.ordering') . ' ASC');
					$db->setQuery($query);

					$courses = $db->loadobjectList();
					$categories[$index]->courses = $courses;
				}
				else
				{
					unset($categories[$index]);
					$categories['main'] = $category;
				}
			}

			return $categories;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Function to get where conditions with categories
	 *
	 * @param   int  $categoryId  category id
	 *
	 * @return  array where conditions
	 *
	 * @since   1.0
	 */
	public function getWhereCategory($categoryId)
	{
		$db = Factory::getDBO();
		$where = '';

		$cat_tbl = Table::getInstance('Category', 'JTable');
		$cat_tbl->load((int) $categoryId);
		$rgt = $cat_tbl->rgt;
		$lft = $cat_tbl->lft;
		$baselevel = (int) $cat_tbl->level;
		$where[] = 'c.lft >= ' . (int) $lft;
		$where[] = 'c.rgt <= ' . (int) $rgt;
		$where[] = 'c.published = 1';

		return $where;
	}

	/**
	 * Function to check id cat is in plublished state
	 *
	 * @param   int  $categoryId  category id
	 *
	 * @return  array where conditions
	 *
	 * @since   1.0
	 */
	public function checkifCatPresent($categoryId)
	{
		$cat_tbl = Table::getInstance('Category', 'JTable');
		$cat_tbl->load((int) $categoryId);

		if ($cat_tbl->published == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * redefine the function and add some properties to make the styling more easy
	 *
	 * @return mixed An array of data items on success, false on failure.
	 */
	public function getItems()
	{
		// Import Joomla Categories library
		jimport('joomla.application.categories');

		$categories = Categories::getInstance('Tjlms');

		$this->catParent = $categories->get('root');

		if (is_object($this->catParent))
		{
			// Get child categories
			$this->items = $this->catParent->getChildren(true);
		}
		else
		{
			$this->items = false;
		}

		return $this->items;
	}
}
