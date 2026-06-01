<?php
/**
 * @package    Shika
 * @author     Techjoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2019. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Attempt report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelTagstime extends TjreportsModelReports
{
	protected $default_order = 'tag';

	protected $default_order_dir = 'ASC';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JModelLegacy
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$lang     = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		$this->columns = array(
			'tag'         => array('table_column' => 't.title', 'title' => 'Tag'),
			'course_time' => array('table_column' => 'course_time', 'title' => 'Online Time'),
			'event_time'  => array('table_column' => 'event_time', 'title' => 'Classroom Time'),
			'total_time'  => array('table_column' => '', 'title' => 'Total Time', 'disable_sorting' => true)
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return STRING Client
	 *
	 * @since   2.0
	 * */
	public function getPluginDetail()
	{
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('Datewise Time Spent per Tag  Report'));

		return $detail;
	}

	/**
	 * Create an array of filters
	 *
	 * @return    void
	 *
	 * @since    1.0
	 */
	public function displayFilters()
	{
		JLoader::import('components.com_tjlms.models.reports', JPATH_ADMINISTRATOR);

		$lmsparams = ComponentHelper::getParams('com_tjlms');

		// Allow filtering on all the tags that have courses or events assigned
		$query = "SELECT DISTINCT c.tag_id, t.title
		 FROM #__contentitem_tag_map AS c
		 LEFT JOIN #__tags AS t ON c.tag_id=t.id
		 WHERE c.type_alias = 'com_tjlms.course' OR c.type_alias = 'com_jticketing.event'
		 ORDER BY t.title";
		$this->_db->setQuery($query);
		$tags = $this->_db->loadObjectList();
		$tag_filter[] = JHTML::_('select.option', '', '- All Tags -');

		foreach ($tags as $tag)
		{
			$tag_filter[] = JHTML::_('select.option', $tag->tag_id, $tag->title);
		}

		$dispFilters[0] = array (
			'tag' => array (
				'search_type' => 'select', 'select_options' => $tag_filter, 'searchin' => 't.id'
			)
		);

		$dispFilters[1] = array (
			'date' => array (
				'search_type'    => 'date.range',
				'searchin'       => 'date',
				'date_from' => array (
					'attrib' => array (
						'placeholder' => 'YYYY-MM-DD',
						'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'
					)
				),
				'date_to' => array (
					'attrib' => array (
						'placeholder' => 'YYYY-MM-DD',
						'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'
					)
				)
			)
		);

		return $dispFilters;
	}

	/**
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  JDatabaseQuery  A JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		$db        = $this->_db;
		$query     = parent::getListQuery();
		$filters   = $this->getState('filters');
		$colToshow = (array) $this->getState('colToshow');

		$query->select('r.tag_id, SUM(r.course_time) AS course_time, SUM(r.event_time) AS event_time');
		$query->from($db->quoteName('#__tagreport_tag_time', 'r'));
		$query->group($db->quoteName('r.tag_id'));
		$query->select('t.title AS tag');
		$query->join('LEFT', $db->quoteName('#__tags', 't') . ' ON (' . $db->quoteName('t.id') . ' = ' . $db->quoteName('r.tag_id') . ')');

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getItems()
	{
		$items = parent::getItems();
		$colToshow = $this->getState('colToshow');

		$newItems = array();

		// Convert the seconds to h m s representation
		foreach ($items as $key => $value)
		{
			$hours                      = floor($value['course_time'] / 3600);
			$minutes                    = floor(($value['course_time'] / 60) % 60);
			$seconds                    = $value['course_time'] % 60;
			$items[$key]['course_time'] = "{$hours}h {$minutes}m {$seconds}s";

			$hours                      = floor($value['event_time'] / 3600);
			$minutes                    = floor(($value['event_time'] / 60) % 60);
			$seconds                    = $value['event_time'] % 60;
			$items[$key]['event_time'] = "{$hours}h {$minutes}m {$seconds}s";

			$total_time                = $value['event_time'] + $value['course_time'];
			$hours                     = floor($total_time / 3600);
			$minutes                   = floor(($total_time / 60) % 60);
			$seconds                   = $total_time % 60;
			$items[$key]['total_time'] = "{$hours}h {$minutes}m {$seconds}s";
		}

		$items = $this->sortCustomColumns($items);

		return $items;
	}
}
