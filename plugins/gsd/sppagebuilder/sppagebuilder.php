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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 *  SP Page Builder Google Structured Data Plugin
 */
class plgGSDSPPageBuilder extends GSD\PluginBase
{
	/**
	 * The SP Page Builder item.
	 * 
	 * @var  object
	 */
	protected $item;
	
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
		$model = BaseDatabaseModel::getInstance('Page', 'SppagebuilderModel');
		$this->item  = $model->getItem();

		// Array data
		return [
			'id'    	  => $this->item->id,
			'headline'    => $this->item->title,
			'image'		  => $this->getImage(),
			'created_by'  => $this->item->created_by,
			'created'     => $this->item->created_on,
			'modified'    => $this->item->modified,
			'publish_up'  => $this->item->created_on
		];
	}
	
	/**
	 * Returns the first image found in the page's content.
	 * 
	 * @return  string
	 */
	private function getImage()
	{
		if (!is_string($this->item->text))
		{
			return;
		}

		if (!$content = json_decode($this->item->text, true))
		{
			return;
		}

		$img = '';

		$allowed_addons = ['text_block', 'image'];
	
		foreach ($content as $row)
		{
			if (!isset($row['columns']))
			{
				continue;
			}
			
			foreach ($row['columns'] as $column)
			{
				if (!isset($column['addons']))
				{
					continue;
				}
				
				foreach ($column['addons'] as $addon)
				{
					if (!isset($addon['name']))
					{
						continue;
					}

					if (!in_array($addon['name'], $allowed_addons))
					{
						continue;
					}
					
					if ($addon['name'] === 'text_block')
					{
						// Find image in text
						$img = \GSD\Helper::getFirstImageFromString($addon['settings']['text']);
					}
					
					if (!$img && $addon['name'] == 'image')
					{
						$img = isset($addon['settings']['image']['src']) ? $addon['settings']['image']['src'] : '';
					}
				}
			}
		}

		return $img;
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
			'publish_down',
			'ratingValue',
			'reviewCount',
			'alias',
			'introtext',
			'fulltext',
			'imagetext'
		];
		
		// Remove unsupported mapping options
		foreach ($remove_options as $option)
		{
			unset($options['GSD_INTEGRATION']['gsd.item.' . $option]);
		}
	}
}
