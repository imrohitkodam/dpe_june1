<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    Advanced_Search
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Class supporting a list of Advsearch indexer fields.
 *
 * @since  3.3
 */
class AdvsearchModelsearchindexerfields extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'fields.id',
				'field_name', 'fields.field_name',
				'mapping_label', 'fields.mapping_label',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method Build an SQL query to load the list data.
	 *
	 * @return   JDatabaseQuery
	 *
	 * @since    1.0.0
	 */
	protected function getListQuery()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select(array('fields.*'));
		$query->from($db->quoteName('#__advanced_search_indexer', 'indexer'));

		$query->JOIN('INNER', $db->quoteName('#__advanced_search_indexer_fields', 'fields') . ' ON (fields.indexer_id = indexer.id)');

		$client = $this->getState('field_name');

		if (!empty($client))
		{
			$query->where($db->quoteName('indexer.name') . '=' . $db->quote($client));
		}

		$db->setQuery($query);

		return $query;
	}
}
