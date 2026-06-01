<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2021 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('Restricted access');

use GSD\MappingOptions;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 *  Gridbox Google Structured Data Plugin
 */
class plgGSDGridbox extends GSD\PluginBaseArticle
{
	/**
	 * Gridbox page item
	 * 
	 * @var  object
	 */
	private $item;
	
	/**
	 *  Get page's data
	 *
	 *  @return  array
	 */
	public function viewPage()
	{
		// Skip in case there's no page ID. SP Page Builder-based 404 Error Pages has id 0.
		if (!$this->getThingID())
		{
			return;
		}

		// Load current item via model
		$model = BaseDatabaseModel::getInstance('gridbox', 'GridboxModel');
		$this->item  = $model->getItem();

		return [
			'id'    	   => $this->item->id,
			'alias'		   => $this->item->page_alias,
			'headline'     => $this->item->title,
			'created_by'   => $this->getFirstAuthor(),
			'intro_text'   => $this->item->intro_text,
			'image'		   => $this->item->intro_image,
        	'metakey'	   => $this->item->meta_keywords,
			'metadesc'	   => $this->item->meta_description,
			'created'      => $this->item->created,
			'modified'     => $this->item->saved_time,
			'publish_up'   => $this->item->created,
			'publish_down' => $this->item->end_publishing
		];
	}

	/**
	 * A Gridbox page can have multiple authors.
	 * If the page has authors, retrieve the first author from the list so we can use it
	 * in our structured data. The author ID is not a valid user ID so we also need to 
	 * fetch the real user ID.
	 * 
	 * @return  mixed
	 */
	private function getFirstAuthor()
	{
		if (empty($this->item->authors))
		{
			return;
		}

		$first_author_id = $this->item->authors[0]->id;

		// Find and return the real user ID from Author ID
		$db = $this->db;

		$query = $db->getQuery(true)
			->select('user_id')
			->from($db->quoteName('#__gridbox_authors'))
			->where($db->quoteName('id') . ' = ' . $db->quote($first_author_id));

		$db->setQuery($query);

		return $db->loadObject()->user_id;
	}

    /**
	 * The MapOptions Backend Event. Triggered by the mappingoptions fields to help each integration add its own map options.
	 *  
	 * @param	string	$plugin
	 * @param	array	$options
	 *
	 * @return	void
	 */
    public function onMapOptions($plugin, &$options)
    {
		if ($plugin != $this->_name)
        {
			return;
		}
		
		$remove_options = [
			'ratingValue',
			'reviewCount',
			'fulltext',
			'imagetext'
		];
		
		// Remove unsupported mapping options
		foreach ($remove_options as $key => $option)
		{
			unset($options['GSD_INTEGRATION']['gsd.item.' . $option]);
		}
	}
}
