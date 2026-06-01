<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Multilanguage;

jimport('joomla.application.categories');
/**
 * Tjlms Categories helper.
 *
 * @since  1.0.0
 */
class TjlmsModelCategories extends ListModel
{
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	public $context = 'com_tjlms.categories';

	/**
	 * The category context (allows other extensions to derived from this model).
	 *
	 * @var		string
	 */
	protected $extension = 'com_tjlms';

	public $menu_category = null;

	public $active_category = null;

	private $items = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function populateState($ordering = 'ordering', $direction = 'ASC')
	{
		parent::populateState('a.id', 'acs');

		// Initialise variables.
		$app = Factory::getApplication('site');
		$input = Factory::getApplication()->input;

		// Get from menu settings, if not set then get mainframe limit.
		$limit = 0;

		$this->setState('list.limit', $limit);
		$limitstart = $input->getInt('limitstart', 0);

		if ($limit == 0)
		{
			$this->setState('list.start', 0);
		}
		else
		{
			$this->setState('list.start', $limitstart);
		}
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.0.0
	 */
	protected function getListQuery()
	{
		$db     = Factory::getDbo();
		$user   = Factory::getUser();
		$groups = implode(',', $user->getAuthorisedViewLevels());
		$app  = Factory::getApplication();

		$query = $db->getQuery(true)
			->select('a.id, a.title, a.level, a.language, a.parent_id, a.lft, a.rgt')
			->from('#__categories AS a')
			->where('a.parent_id > 0');

		if ($this->menu_category)
		{
			$table = Table::getInstance('Category', 'JTable');
			$table->load($this->menu_category);
			$query->where('a.lft >= ' . (int) $table->lft);
			$query->where('a.rgt <= ' . (int) $table->rgt);
		}

		// Filter on extension.
		$query->where('extension = ' . $db->quote($this->extension));

		// Filter on user access level
		$query->where('a.access IN (' . $groups . ')');
		$query->where('a.published = 1');

		$checkIsClient = version_compare(JVERSION, '3.7.0', 'le') ? $app->isSite() : $app->isClient('site');

		if ($checkIsClient && Multilanguage::isEnabled())
		{
			$query->where('a.language in (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
		}

		$query->order('a.lft');

		return $query;
	}

	/**
	 * redefine the function and add some properties to make the styling more easy
	 *
	 * @return mixed An array of data items on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

		$newItems = array();

		$db = $this->getDbo();
		$table = Table::getInstance('Category', 'JTable');

		if ($this->active_category)
		{
			$table->load($this->active_category);
		}

		$leastLevel = 1;

		foreach ($items as $index => $cat)
		{
			$query = $db->getQuery(true)
			->select('count(id)')
			->from('#__categories')
			->where('parent_id=' . (int) $cat->id)
			->where('published= 1');
			$db->setQuery($query);
			$cat->haschlid = $db->loadResult();

			$cat->open = 0;

			if ($table->id)
			{
				if ($cat->lft < (int) $table->lft && $cat->rgt > (int) $table->rgt)
				{
					$cat->open = 1;
				}
			}

			$newItems[$cat->id] = $cat;
		}

		return $newItems;
	}
}
