<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2023 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('Restricted access');

use GSD\Helper;
use GSD\MappingOptions;
use Joomla\CMS\Factory;
use Joomla\CMS\Date\Date;
use NRFramework\Functions;
use NRFramework\Cache;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

/**
 *  iCagenda Google Structured Data Plugin
 */
class plgGSDICagenda extends \GSD\PluginBaseEvent
{
	/**
	 * Model
	 * 
	 * @var  Class
	 */
	private $model;

	/**
	 * iCagenda config.
	 * 
	 * @var  object
	 */
	private $ic_config;

	/**
	 * Item
	 * 
	 * @var  Class
	 */
	private $item;
	
	/**
	 *  Get article's data
	 *
	 *  @return  array
	 */
	public function viewEvent()
	{	
		// Make sure we have a valid ID
		if (!$id = $this->getThingID())
		{
			return;
		}

		// Load current item via model
		$this->model = BaseDatabaseModel::getInstance('Event', 'iCagendaModel');

		$this->item = $this->model->getItem();

		$this->ic_config = $this->item->params;
		
		if (!is_object($this->item))
		{
			return;
		}

		$start_date = Functions::dateToUTC($this->item->startdate);
		$end_date = Functions::dateToUTC($this->item->enddate);

		// Array data
		$payload = [
			'id'   		  		  => $this->item->id,
			'alias'       		  => $this->item->alias,
			'headline'    		  => $this->item->title,
			'description' 		  => empty($this->item->shortdesc) ? $this->item->desc : $this->item->shortdesc,
			'introtext'   		  => $this->item->shortdesc,
			'fulltext'   	      => $this->item->desc,
			'image'       		  => $this->item->image,
			'imagetext'	   		  => Helper::getFirstImageFromString($this->item->shortdesc . $this->item->desc),
			'startDate'	  		  => $start_date,
			'endDate'	  		  => $end_date,
			'created_by'  		  => $this->item->created_by,
			'publish_up'  		  => $this->item->created,
			'publish_down'		  => $end_date,
			'offerStartDate' 	  => $start_date,
			'metadesc'	   		  => $this->item->metadesc,
			'locationName' 		  => $this->item->place,
			'locationAddress'	  => $this->item->address,
			'addressCountry'	  => $this->item->country,
			'addressLocality'	  => $this->item->city,
			'online_url'	 	  => $this->item->website
		];

		// Add Item Data Values
		$this->attachItemDataValues($payload);

		// Add Features Values
		$this->attachFeaturesValues($payload);

		// Add Custom Fields Values
		$this->attachCustomFieldsValues($payload);
		
		return $payload;
	}

	/**
	 * Adds all item data values to the payload
	 * 
	 * @param   array   $payload
	 * 
	 * @return  array
	 */
	private function attachItemDataValues(&$payload)
	{
		$data = $this->getItemData();

		foreach ($data as $key)
		{
			$value = isset($this->item->$key) ? $this->item->$key : '';
			
			if ($key === 'coordinates')
			{
				$value = $this->item->lat && $this->item->lng ? $this->item->lat . ',' . $this->item->lng : '';
			}
			
			// set value
			$payload['data.' . $key] = $value;
		}
	}

	/**
	 * Adds features values.
	 * 
	 * @param   array   $payload
	 * 
	 * @return  array
	 */
	private function attachFeaturesValues(&$payload)
	{
		// Get selected features from the event
		$selected_features = isset($this->item->version_features) ? $this->item->version_features : false;
		if (!$selected_features)
		{
			return;
		}
		
		if (!$features = $this->getFeatures($selected_features))
		{
			return;
		}

		$params_media = ComponentHelper::getParams('com_media');
		$image_path = $params_media->get('image_path', 'images');

		$FEATURES_ICONSIZE_EVENT = $this->ic_config->get('features_icon_size_event');
		$FEATURES_ICONROOT_EVENT = Uri::root() . $image_path . '/icagenda/feature_icons/' . $FEATURES_ICONSIZE_EVENT . '/';

		foreach ($features as $key => $value)
		{
			// Add Icon URL
			$payload['features.' . $key . '_url'] = !empty($value['icon']) ? $FEATURES_ICONROOT_EVENT . $value['icon'] : '';

			// Add Icon Alt
			$payload['features.' . $key . '_alt'] = $value['icon_alt'];
			
			// Add Feature Alias
			$payload['features.' . $key . '_alias'] = $value['alias'];
			
			// Add Feature Title
			$payload['features.' . $key . '_title'] = $value['title'];
		}
	}

	/**
	 * Adds all custom fields values to the payload.
	 * 
	 * @param   array  $payload
	 * 
	 * @return  void
	 */
	private function attachCustomFieldsValues(&$payload)
	{
		$item_customfields = $this->item->version_customfields ? $this->item->version_customfields : false;
		if (!$item_customfields)
		{
			return;
		}

		if (!$item_customfields = @json_decode($item_customfields, true))
		{
			return;
		}
		
		foreach ($item_customfields as $slug => $value)
		{
			$payload['cf.' . $slug] = $value;
		}
	}

	/**
	 * Returns all created custom fields.
	 * 
	 * @return  array
	 */
	private function getCustomFields()
	{
        $hash = 'gsd_icagenda_get_customfields';
        if (Cache::has($hash))
        {
            return Cache::get($hash);
        }
		
		$cf = \iCutilities\Customfields\Customfields::getCustomfields(2);
		
        return Cache::set($hash, $cf);
	}

	/**
	 * Retrieves item data.
	 * 
	 * @return  array
	 */
	private function getItemData()
	{
		$data = [
			'place',
			'coordinates',
			'website',
			'email',
			'phone',
			'file'
		];

		return $data;
	}

	/**
	 * Retrieves features.
	 * 
	 * @param   string  $ids   Comma-separated list of feature IDs
	 * 
	 * @return  array
	 */
	private function getFeatures($ids = null)
	{
        $hash = 'gsd_icagenda_get_features';
        if (Cache::has($hash))
        {
            return Cache::get($hash);
        }

		$db = Factory::getDbo();

		$query = $db->getQuery(true);
		$query->select('*')
			->from($db->qn('#__icagenda_feature'));

		if ($ids && is_string($ids))
		{
			$query->where($db->qn('id') . ' IN (' . $ids . ')');
		}
		
		$db->setQuery($query);

		$data = $db->loadAssocList();

        return Cache::set($hash, $data);
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
		parent::onMapOptions($plugin, $options);

		if ($plugin != $this->_name)
		{
			return;
		}

		// Remove undeeded default mapping options values
		$remove_options = [
			'metakey',
			'metadesc',
			'performerType',
			'performerName',
			'performerURL',
			'organizerType',
			'organizerName',
			'organizerURL',
			'offercurrency',
			'offerinventorylevel',
			'addressRegion'
		];

		// Remove unsupported mapping options
		foreach ($remove_options as $option)
		{
			unset($options['GSD_INTEGRATION']['gsd.item.' . $option]);
		}

		// Add Item Data options
		if ($item_data = $this->getItemData())
		{
			$item_data_options = [];
			foreach ($item_data as $key)
			{
				$item_data_options[$key] = Text::_('PLG_GSD_ICAGENDA_OPTION_' . strtoupper($key));
			}

			MappingOptions::add($options, $item_data_options, 'GSD_INTEGRATION', 'gsd.item.data.');
		}

		// Add data for first 3 features selected
		$features_options = [];
		for ($i = 0; $i < 3; $i++)
		{
			$index = $i + 1;
			
			// Icon URL
			$features_options[$i . '_url'] = Text::sprintf('PLG_GSD_ICAGENDA_FEATURE_ITEM', $index, Text::_('PLG_GSD_ICAGENDA_ICON_URL'));

			// Icon Alt
			$features_options[$i . '_alt'] = Text::sprintf('PLG_GSD_ICAGENDA_FEATURE_ITEM', $index, Text::_('PLG_GSD_ICAGENDA_ICON_ALT'));

			// Feature Alias
			$features_options[$i . '_alias'] = Text::sprintf('PLG_GSD_ICAGENDA_FEATURE_ITEM', $index, Text::_('PLG_GSD_ICAGENDA_FEATURE_ALIAS'));

			// Feature Title
			$features_options[$i . '_title'] = Text::sprintf('PLG_GSD_ICAGENDA_FEATURE_ITEM', $index, Text::_('PLG_GSD_ICAGENDA_FEATURE_TITLE'));
		}
		MappingOptions::add($options, $features_options, 'PLG_GSD_ICAGENDA_FEATURES', 'gsd.item.features.');

		// Add custom fields
		if ($cf = $this->getCustomFields())
		{
			$cf_options = [];
			foreach ($cf as $field)
			{
				$cf_options[$field->slug] = $field->title;
			}

			MappingOptions::add($options, $cf_options, 'GSD_CUSTOM_FIELDS', 'gsd.item.cf.');
		}
	}
}
