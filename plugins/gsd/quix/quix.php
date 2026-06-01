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
 *  Quix Page Builder Google Structured Data Plugin
 */
class plgGSDQuix extends GSD\PluginBase
{
	/**
	 *  Get page's data
	 *
	 *  @return  array
	 */
	public function viewPage()
	{
		// Load current item via model
		$model = BaseDatabaseModel::getInstance('Page', 'QuixModel', ['ignore_request' => true]);
		$item = $model->getData($this->getThingID());

		$metadata = json_decode($item->metadata);

		// Array data
		return [
			'id'    	  => $item->id,
			'headline'    => $item->title,
			'created_by'  => $item->created_by,
			'created'     => $item->created,
			'modified'    => $item->modified,
			'publish_up'  => $item->created,
        	'metakey'     => isset($metadata->focus_keywords) ? $metadata->metadata : null,
			'metadesc'    => isset($metadata->desc) ? $metadata->desc : null,
			'image'	      => isset($metadata->image_intro) && !empty($metadata->image_intro) ? 'images/' . ltrim($metadata->image_intro, '/') : null
		];
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
			'imagetext',
			'created_by',
			'description',
		];
		
		// Remove unsupported mapping options
		foreach ($remove_options as $option)
		{
			unset($options['GSD_INTEGRATION']['gsd.item.' . $option]);
		}
	}
}
