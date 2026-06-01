<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2022 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('Restricted access');

use GSD\MappingOptions;
use NRFramework\Functions;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

class Shared extends GSD\PluginBase
{
	/**
	 * Returns the schema allowed in the current page to be rendered.
	 * 
	 * @return  string
	 */
	protected function getCurrentPageAllowedContentType()
	{
		$allowed_schema = 'localbusiness';

		switch ($this->app->input->get('view')) {
			case 'event':
				$allowed_schema = 'event';
				break;
			case 'offer':
				$allowed_schema = 'service';
				break;
		}
		
		return $allowed_schema;
	}

	/**
	 * Returns all the event/offer categories.
	 * 
	 * @return  mixed
	 */
	protected function getItemCategories()
	{
		if (!isset($this->item->categories))
		{
			return;
		}
		
		if (!$categories = $this->item->categories)
		{
			return;
		}

		$cats = [];

		foreach ($categories as $key => $value)
		{
			if (!isset($value[1]))
			{
				continue;
			}
			
			$cats[] = $value[1];
		}

		return implode(', ', $cats);
	}

	/**
	 * Returns an items custom fields values
	 * 
	 * @param  string  $prefix
	 * 
	 * @return array
	 */
	protected function getItemCustomFieldsValues($prefix = '')
	{
		if (!isset($this->item->customFields))
		{
			return [];
		}
		
		if (!$customFields = $this->item->customFields)
		{
			return [];
		}

		$payload = [];

		foreach ($customFields as $key => $cf)
		{
			// skip custom field without a value
			if (!$cf->attributeValue)
			{
				continue;
			}

			if (in_array($cf->attributeTypeCode, ['select_box', 'checkbox', 'radio', 'multiselect']))
			{
				$value = $this->getArrayCustomFieldValue($cf->attributeValue, $cf->optionsIDS, $cf->options);
			}
			else
			{
				$value = $cf->attributeValue;
			}

			$payload[$prefix . '.cf.' . $cf->id] = $value;
		}
		
		return $payload;
	}

	/**
	 * Returns all item attachments.
	 * 
	 * @param    string  $prefix
	 * 
	 * @return   mixed
	 */
	protected function getItemAttachments($prefix = '')
	{
		if (!isset($this->item->attachments))
		{
			return [];
		}

		if (!is_array($this->item->attachments) || empty($this->item->attachments))
		{
			return [];
		}

		$urls = [];
		
		foreach ($this->item->attachments as $key => $value)
		{
			$urls[$prefix . 'attachment_' . $key] = BD_ATTACHMENT_PATH . $value->path;
		}

		return $urls;
	}

	/**
	 * Returns the country object of the given country ID.
	 * 
	 * @param   int     $countryId
	 * 
	 * @return  object
	 */
	protected function getItemCountry($countryId)
	{
		if (!$this->item->countryId)
		{
			return;
		}
		
		if (!$countryTable = Table::getInstance('Country', '\\Joomla\\CMS\\Table\\', []))
		{
			return;
		}

		return $countryTable->getCountry($this->item->countryId);
	}

	/**
	 * Find the value of a custom field with the following type:
	 * 
	 * - Select Box
	 * - Checkbox
	 * - Radio
	 * - MultiSelector
	 * 
	 * @param   string  $value
	 * @param   string  $keys
	 * @param   string  $values
	 * 
	 * @return  string
	 */
	protected function getArrayCustomFieldValue($value, $keys, $values)
	{
		// Create a new array combined by $keys, $values
		$keys = explode('|#', $keys);
		$values = explode('|#', $values);
		$keys = array_slice($keys, 0, count($values));
		
		$choices = array_combine($keys, $values);

		// Transform value to an array of values
		$value = explode(',', $value);

		$data = [];

		foreach ($choices as $key => $label)
		{
			if (!in_array($key, $value))
			{
				continue;
			}

			$data[] = $label;
		}
		
		return implode(', ', $data);
	}

	/**
	 * Returns a category's name given its ID.
	 * 
	 * @param   int    $id
	 * 
	 * @return  mixed
	 */
	protected function getItemCategoryByID($id)
	{
		if (!$categoryTable = Table::getInstance('Category', 'JBusinessTable', []))
		{
			return;
		}
		
		if (!$category = $categoryTable->getCategoryById($id))
		{
			return;
		}

		return $category->name;
	}

	/**
	 * Returns an items selected categories.
	 * 
	 * @param   array   $selctedCategories
	 * 
	 * @return  string
	 */
	protected function getItemSelectedCategories($selectedCategories)
	{
		$names = [];

		foreach ($selectedCategories as $key => $value)
		{
			$names[] = trim($value->name);
		}

		return implode(', ', $names);
	}

	/**
	 * Returns the shared field values.
	 * 
	 * @return  array
	 */
	protected function getSharedFieldValues()
	{
		$shared_fields = $this->getSharedFields();
		
		$prefix = 'shared.';
		
		$data = [];

		// Add all data directly from the item object
		foreach ($shared_fields as $key => $label)
		{
			// Only add directly existing properties
			if (!property_exists($this->item, $key))
			{
				continue;
			}
			
			$data[$prefix . $key] = $this->item->$key;
		}

		// Add pictures
		if (count($pictures = $this->item->pictures))
		{
			$pictures_urls = [];
			foreach ($pictures as $key => &$picture)
			{
				$picture = (array) $picture;
				$pictures_urls[] = BD_PICTURES_PATH . $picture['picture_path'];
			}
			$data[$prefix . 'pictures'] = implode(',', $pictures_urls);
		}

		// Add pictures data
		if (count($pictures = $this->item->pictures))
		{
			foreach ($pictures as $key => $picture)
			{
				$picture = (array) $picture;
				// Picture URL
				$data[$prefix . 'picture_url_' . $key] = BD_PICTURES_PATH . $picture['picture_path'];
				// Picture Title
				$data[$prefix . 'picture_title_' . $key] = $picture['picture_title'];
				// Picture Description
				$data[$prefix . 'picture_description_' . $key] = $picture['picture_info'];
			}
		}

		// Add videos
		if (isset($this->item->videos) && count($videos = $this->item->videos))
		{
			foreach ($videos as $key => $video)
			{
				$data[$prefix . 'video_' . $key] = $video->url;
			}
		}

		return $data;
	}

	/**
	 * Returns the reviews of the item.
	 * 
	 * @param   int    $status    The J-BusinessDirectory Item status
	 * 
	 * @return  array
	 */
	protected function getReviews($status = 1)
	{
		if (!$reviewTable = Table::getInstance('Review', '\\Joomla\\CMS\\Table\\', []))
		{
			return;
		}

		if (!$reviews = $reviewTable->getReviews($this->getThingID(), true, $status))
		{
			return [];
		}

		$data = [];

		foreach ($reviews as $key => $review)
		{
            $data[] = (object) [
                'author' => $review->name,
                'datePublished' => Functions::dateToUTC($review->creationDate, true),
                'description' => $review->description,
                'rating' => $review->rating
            ];
		}

		return $data;
	}

	/**
	 * Shared fields between J-BusinessDirectory items.
	 * 
	 * @return  array
	 */
	protected function getSharedFields()
	{
		$fields = [
			// Street Number
			'street_number' => Text::_('LNG_STREET_NUMBER'),
			// Address
			'address' => Text::_('LNG_ADDRESS'),
			// Area
			'area' => Text::_('LNG_AREA'),
			// Country
			'countryId' => Text::_('LNG_COUNTRY'),
			// County / Region
			'county' => Text::_('LNG_COUNTY'),
			// City
			'city' => Text::_('LNG_CITY'),
			// Province
			'province' => Text::_('LNG_PROVINCE'),
			// Postal Code
			'postalCode' => Text::_('LNG_POSTAL_CODE'),
			// Latitude
			'latitude' => Text::_('LNG_LATITUDE'),
			// Longitude
			'longitude' => Text::_('LNG_LONGITUDE'),
			// Meta Title
			'meta_title' => Text::_('LNG_META_TITLE'),
			// All pictures
			'pictures' => Text::_('LNG_PICTURES')
		];
		
		/**
		 * Pictures consists from a Repeater field
		 * which we do not know how many items can have per listing.
		 * 
		 * For this purpose, we assume 5 pictures are enough and add them
		 * manually. If we later need more items, we can increase them.
		 */
		for ($i = 0; $i < 5; $i++)
		{
			$fields = array_merge($fields, [
				// Picture URL #XX
				'picture_url_' . $i => sprintf(Text::_('PLG_GSD_JBUSINESSDIRECTORY_PICTURE_URL'), ($i + 1)),
				// Picture Title #XX
				'picture_title_' . $i => sprintf(Text::_('PLG_GSD_JBUSINESSDIRECTORY_PICTURE_TITLE'), ($i + 1)),
				// Picture Description #XX
				'picture_description_' . $i => sprintf(Text::_('PLG_GSD_JBUSINESSDIRECTORY_PICTURE_DESCRIPTION'), ($i + 1))
			]);
		}
		
		/**
		 * Videos consists from a Repeater field
		 * which we do not know how many items can have per listing.
		 * 
		 * For this purpose, we assume 5 videos are enough and add them
		 * manually. If we later need more items, we can increase them.
		 */
		for ($i = 0; $i < 5; $i++)
		{
			$fields = array_merge($fields, [
				// Video #XX
				'video_' . $i => sprintf(Text::_('PLG_GSD_JBUSINESSDIRECTORY_VIDEO'), ($i + 1))
			]);
		}

		return $fields;
	}

	/**
	 * Company custom fields of a J-BusinessDirectory item.
	 * 
	 * @param   int    $status    The J-BusinessDirectory Item status
	 * 
	 * @return  array
	 */
	protected function getItemCustomFields($status = 1)
	{
		if (!$attributeTable = Table::getInstance('Attribute', '\\Joomla\\CMS\\Table\\', []))
		{
			return;
		}

		if (!$attributes = $attributeTable->getAttributes($status))
		{
			return [];
		}

		/**
		 * Holds a list of attributes IDs that should not be displayed in the list
		 * as they do not hold any value.
		 */
		$skipped_atts = [
			// "Header" Custom Attribute
			'5'
		];

		$fields = [];

		foreach ($attributes as $key => $attribute)
		{
			// Skip custom fields that do not contain a value
			if (in_array($attribute->type, $skipped_atts))
			{
				continue;
			}
			
			$fields[$attribute->id] = $attribute->name;
		}

		return $fields;
	}

	/**
	 * Returns an app setting property value.
	 * 
	 * @param   string  $property
	 * 
	 * @return  mixed
	 */
	protected function getAppSettingPropertyValue($property)
	{
		if (!is_string($property) || empty($property))
		{
			return;
		}
		
        $appSettings = JBusinessUtil::getApplicationSettings();

		if (!property_exists($appSettings, $property))
		{
			return;
		}

		return $appSettings->$property;
	}
}