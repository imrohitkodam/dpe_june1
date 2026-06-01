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

use Joomla\CMS\Helper\TagsHelper;
use GSD\Helper;
use GSD\MappingOptions;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

/**
 *  DPCalendar Google Structured Data Plugin
 */
class plgGSDDPCalendar extends \GSD\PluginBaseEvent
{
	/**
	 * Model
	 * 
	 * @var  Object
	 */
	private $model;

	/**
	 * The event.
	 * 
	 * @var  Object
	 */
	private $item;
	
	/**
	 * Get event's data
	 *
	 * @return  array
	 */
	public function viewEvent()
	{	
		// Make sure we have a valid ID
		if (!$id = $this->getThingID())
		{
			return;
		}

		// Load current item via model
		$this->model = BaseDatabaseModel::getInstance('Event', 'DPCalendarModel');

		$dpcalendarhelper = class_exists('DPCalendar\Helper\DPCalendarHelper') ? '\DPCalendar\Helper\DPCalendarHelper' : '\DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper';

		$this->item = $this->model->getItem();
		
		if (!is_object($this->item))
		{
			return;
		}

		$this->item->tags = new TagsHelper();
		$this->item->tags->getItemTags('com_dpcalendar.event', $this->item->id);

		$locationName = count($this->item->locations) ? $this->item->locations[0]->title : null;
		$locationCountry = count($this->item->locations) && isset($this->item->locations[0]->country_code_value) ? $this->item->locations[0]->country_code_value : null;

		$payload = [
			'id'   		  		  => $this->item->id,
			'alias'       		  => $this->item->alias,
			'headline'    		  => $this->item->title,
			'description' 		  => empty($this->item->introText) ? $this->item->description : $this->item->introText,
			'introtext'   		  => $this->item->introText,
			'fulltext'   	      => $this->item->description,
			'image_intro'  		  => Uri::root() . $this->item->images->image_intro,
			'image_full'   		  => Uri::root() . $this->item->images->image_full,
			'image'        		  => Uri::root() . ($this->item->images->image_intro ?: $this->item->images->image_full),
			'imagetext'	   		  => Helper::getFirstImageFromString($this->item->introText . $this->item->description),
			'startDate'	  		  => $this->item->start_date,
			'endDate'	  		  => $this->item->end_date,
			'created_by'  		  => $this->item->created_by,
			'publish_up'  		  => $this->item->created,
			'publish_down'		  => $this->item->end_date,
			'offerStartDate' 	  => $this->item->start_date,
			'offerPrice'    	  => $this->getPrice(),
			'locationName' 		  => $locationName,
			'locationAddress'	  => $locationName,
			'addressCountry'	  => $locationCountry,
			'postalCode'	  	  => count($this->item->locations) ? $this->item->locations[0]->zip : null,
			'metakey'			  => $this->item->metadata->get('metakey'),
			'metadesc'			  => $this->item->metadata->get('metadesc'),
			'offercurrency'  	  => $dpcalendarhelper::getComponentParameter('currency', 'USD')
		];

		// Add Item Data Values
		$this->attachItemData($payload);

		return $payload;
	}

	/**
	 * Return the price.
	 * 
	 * @return  mixed
	 */
	private function getPrice()
	{
		// Compatibility Start
		if (isset($this->item->price))
		{
			$prices = $this->item->price;

			if (!$prices)
			{
				return;
			}
	
			// One price only
			if (count($prices->value) === 1)
			{
				return reset($prices->value);
			}
			
			// Multiple prices
			$minPrice = min($prices->value);
			$maxPrice = max($prices->value);
	
			return [$minPrice, $maxPrice];
		}
		// Compatibility End


		if (!isset($this->item->prices))
		{
			return;
		}

		$prices = json_decode(json_encode($this->item->prices), true);
		if (!$prices)
		{
			return;
		}

		// One price only
		if (count($prices) === 1)
		{
			$prices = reset($prices);
			return $prices['value'];
		}

		// Multiple prices
		$minPrice = min(array_column($prices, 'value'));
		$maxPrice = max(array_column($prices, 'value'));

		return [$minPrice, $maxPrice];
	}

	/**
	 * Adds all item data values to the payload
	 * 
	 * @param   array   $payload
	 * 
	 * @return  array
	 */
	private function attachItemData(&$payload)
	{
		$payload['data.url'] = $this->item->url;
		$payload['data.color'] = $this->item->color;
		$payload['data.intro_image_url'] = Uri::root() . $this->item->images->image_intro;
		$payload['data.intro_image_alt'] = $this->item->images->image_intro_alt;
		$payload['data.intro_image_caption'] = $this->item->images->image_intro_caption;
		$payload['data.full_image_url'] = Uri::root() . $this->item->images->image_full;
		$payload['data.full_image_alt'] = $this->item->images->image_full_alt;
		$payload['data.full_image_caption'] = $this->item->images->image_full_caption;
		$payload['data.capacity'] = $this->item->capacity;
		$payload['data.capacity_used'] = $this->item->capacity_used;
		$payload['data.max_tickets'] = $this->item->max_tickets;
		$payload['data.booking_opening_date'] = $this->item->booking_opening_date;
		$payload['data.booking_closing_date'] = $this->item->booking_closing_date;
		$payload['data.booking_cancel_closing_date'] = $this->item->booking_cancel_closing_date;
		$payload['data.booking_information'] = $this->item->booking_information;
		$payload['data.rights'] = $this->item->metadata->get('rights');
		$payload['data.xreference'] = $this->item->xreference;

		// Locations
		if ($locations = $this->item->locations)
		{
			$location1 = isset($locations[0]) ? $locations[0] : false;
			if ($location1)
			{
				$payload['data.location1_label'] = $location1->title;
				$payload['data.location1_coords'] = $location1->xreference;
				$payload['data.location1_province'] = $location1->province;
				$payload['data.location1_city'] = $location1->city;
				$payload['data.location1_zip'] = $location1->zip;
				$payload['data.location1_street'] = $location1->street;
				$payload['data.location1_number'] = $location1->number;
			}

			$location2 = isset($locations[0]) ? $locations[0] : false;
			if ($location2)
			{
				$payload['data.location2_label'] = $location2->title;
				$payload['data.location2_coords'] = $location2->xreference;
				$payload['data.location2_province'] = $location2->province;
				$payload['data.location2_city'] = $location2->city;
				$payload['data.location2_zip'] = $location2->zip;
				$payload['data.location2_street'] = $location2->street;
				$payload['data.location2_number'] = $location2->number;
			}

			$location3 = isset($locations[0]) ? $locations[0] : false;
			if ($location3)
			{
				$payload['data.location3_label'] = $location3->title;
				$payload['data.location3_coords'] = $location3->xreference;
				$payload['data.location3_province'] = $location3->province;
				$payload['data.location3_city'] = $location3->city;
				$payload['data.location3_zip'] = $location3->zip;
				$payload['data.location3_street'] = $location3->street;
				$payload['data.location3_number'] = $location3->number;
			}
		}

		// Price
		// Compatibility Start
		$prices = isset($this->item->price) ? $this->item->price : null;
		if ($prices)
		{
			if ($prices && isset($prices->value))
			{
				$prices = $prices->value;
	
				if (isset($prices[0]))
				{
					$payload['data.price1'] = $prices[0];
				}
				if (isset($prices[1]))
				{
					$payload['data.price2'] = $prices[1];
				}
				if (isset($prices[2]))
				{
					$payload['data.price3'] = $prices[2];
				}
			}
		}
		// Compatibility End
		else
		{
			$prices = isset($this->item->prices) ? $this->item->prices : null;
			if ($prices)
			{
				$prices = array_values(json_decode(json_encode($prices), true));
				if (isset($prices[0]))
				{
					$payload['data.price1'] = $prices[0]['value'];
				}
				if (isset($prices[1]))
				{
					$payload['data.price2'] = $prices[1]['value'];
				}
				if (isset($prices[2]))
				{
					$payload['data.price3'] = $prices[2]['value'];
				}
			}
		}

		// Early Bird
		// Compatibility Start
		$earlyBird = isset($this->item->earlybird) ? $this->item->earlybird : null;
		if ($earlyBird)
		{
			$dates = $this->item->earlybird->date;
			
			if (isset($earlybird[0]))
			{
				$payload['data.earlybird1_value'] = $earlybird[0];
				$payload['data.earlybird1_date'] = $dates[0];
			}
			if (isset($earlybird[1]))
			{
				$payload['data.earlybird2_value'] = $earlybird[1];
				$payload['data.earlybird2_date'] = $dates[1];
			}
			if (isset($earlybird[2]))
			{
				$payload['data.earlybird3_value'] = $earlybird[2];
				$payload['data.earlybird3_date'] = $dates[2];
			}
		}
		// Compatibility End
		else
		{
			$earlyBird = isset($this->item->earlybird_discount) ? $this->item->earlybird_discount : null;
			if ($earlyBird)
			{
				$earlyBird = array_values(json_decode(json_encode($earlyBird), true));

				if (isset($earlyBird[0]))
				{
					$payload['data.earlybird1_value'] = $earlyBird[0]['value'];
					$payload['data.earlybird1_date'] = $earlyBird[0]['date'];
				}
				if (isset($earlyBird[1]))
				{
					$payload['data.earlybird2_value'] = $earlyBird[1]['value'];
					$payload['data.earlybird2_date'] = $earlyBird[1]['date'];
				}
				if (isset($earlyBird[2]))
				{
					$payload['data.earlybird3_value'] = $earlyBird[2]['value'];
					$payload['data.earlybird3_date'] = $earlyBird[2]['date'];
				}
			}
		}

		// Booking Options
		if ($booking_options = $this->item->booking_options)
		{
			if (isset($booking_options->booking_options0))
			{
				$payload['data.option1_price'] = $booking_options->booking_options0->price;
			}

			if (isset($booking_options->booking_options1))
			{
				$payload['data.option2_price'] = $booking_options->booking_options1->price;
			}

			if (isset($booking_options->booking_options2))
			{
				$payload['data.option3_price'] = $booking_options->booking_options2->price;
			}
		}

		// Tags
		if ($tags = $this->item->tags)
		{
			if ($tags->itemTags)
			{
				if (isset($tags->itemTags[0]))
				{
					$payload['data.tag1'] = $tags->itemTags[0]->title;
				}

				if (isset($tags->itemTags[1]))
				{
					$payload['data.tag2'] = $tags->itemTags[1]->title;
				}

				if (isset($tags->itemTags[2]))
				{
					$payload['data.tag3'] = $tags->itemTags[2]->title;
				}
			}
		}

		/**
		 * Add Custom Fields
		 */
        if ($fields = FieldsHelper::getFields('com_dpcalendar.event', $this->item, true))
		{
			foreach ($fields as $field)
			{
				$payload['cf.' . $field->name] = $field->value;
			}
		}
	}

	/**
	 * Retrieves useful item data
	 * 
	 * @return  array
	 */
	private function getItemData()
	{
		$data = [
			'url',
			'color',
			'intro_image_url',
			'intro_image_alt',
			'intro_image_caption',
			'full_image_url',
			'full_image_alt',
			'full_image_caption',
			'capacity',
			'capacity_used',
			'max_tickets',
			'booking_opening_date',
			'booking_closing_date',
			'booking_cancel_closing_date',
			'rights',
			'xreference',
			// Location
			'location1_label',
			'location1_coords',
			'location1_province',
			'location1_city',
			'location1_zip',
			'location1_street',
			'location1_number',
			'location2_label',
			'location2_coords',
			'location2_province',
			'location2_city',
			'location2_zip',
			'location2_street',
			'location2_number',
			'location3_label',
			'location3_coords',
			'location3_province',
			'location3_city',
			'location3_zip',
			'location3_street',
			'location3_number',
			// Price
			'price1',
			'price2',
			'price3',
			// Early Bird
			'earlybird1_value',
			'earlybird1_date',
			'earlybird2_value',
			'earlybird2_date',
			'earlybird3_value',
			'earlybird3_date',
			// Booking Information
			'booking_information',
			// Booking Options
			'option1_price',
			'option2_price',
			'option3_price',
			// Tags
			'tag1',
			'tag2',
			'tag3'
		];

		return $data;
	}

	/**
	 * Listening to the onAfterRender Joomla event
	 *
	 * @return void
	 */
	public function onAfterRender()
	{
        // Make sure we are on the right context
        if ($this->app->isClient('administrator') || 
            !$this->passContext() || 
            $this->getView() != 'event' ||
            !$this->params->get('remove_dpcalendar_product_schema', true))
		{
            return;
        }

        // Remove the Event Structured Data item added by DPCalendar
        \GSD\SchemaCleaner::remove('Event');
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
			'performerType',
			'performerName',
			'performerURL',
			'organizerType',
			'organizerName',
			'organizerURL',
			'offerinventorylevel',
			'addressLocality',
			'addressRegion'
		];

		// Remove unsupported mapping options
		foreach ($remove_options as $option)
		{
			unset($options['GSD_INTEGRATION']['gsd.item.' . $option]);
		}

		// Add custom item data
		if (!$custom_item_data = $this->getItemData())
		{
			return;
		}
		
		$custom_item_data_options = [];
		foreach ($custom_item_data as $key)
		{
			$custom_item_data_options[$key] = Text::_('PLG_GSD_DPCALENDAR_' . strtoupper($key));
		}
		MappingOptions::add($options, $custom_item_data_options, 'GSD_INTEGRATION', 'gsd.item.data.');

		/**
		 * Add Custom Fields
		 */
        if ($custom_fields = FieldsHelper::getFields('com_dpcalendar.event', null, true))
		{
			$custom_fields_data = [];
			foreach ($custom_fields as $field)
			{
				$custom_fields_data[$field->name] = Text::_('PLG_GSD_DPCALENDAR_CUSTOM_FIELD') . ': ' . $field->title;
			}
			MappingOptions::add($options, $custom_fields_data, 'GSD_CUSTOM_FIELDS', 'gsd.item.cf.');
		}
	}
}