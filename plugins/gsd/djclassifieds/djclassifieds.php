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

use NRFramework\Cache;
use GSD\Helper;
use GSD\MappingOptions;
use GSD\PluginBaseProduct;
use NRFramework\Functions;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 *  DJ Classifieds Google Structured Data Plugin
 */
class plgGSDDJClassifieds extends PluginBaseProduct
{
	/**
	 * The DJ Classifieds model used within the class
	 * 
	 * @var array
	 */
	private $model;

	/**
	 * The DJ Classifieds item
	 * 
	 * @var object
	 */
	private $item;
	
	/**
	 *  Get the post's data
	 *
	 *  @return  array
	 */
	public function viewItem()
	{
		$id = $this->getThingId();

		// load item
		$this->model = BaseDatabaseModel::getInstance('ModelItem', 'Djclassifieds');
		$this->item = $this->model->getItem();

		// payload
		$payload = [
			'id'           => $this->item->id,
			'alias'        => $this->item->alias,
			'headline'     => $this->item->name,
			'description'  => empty($this->item->intro_desc) ? $this->item->description : $this->item->intro_desc,
			'introtext'    => $this->item->intro_desc,
			'fulltext'     => $this->item->description,
			'image'        => $this->getProductImage(),
			'imagetext'	   => Helper::getFirstImageFromString($this->item->intro_desc . $this->item->description),
			'offerPrice'   => $this->item->price,
			'currency'     => $this->item->currency,
			'created'      => Functions::dateToUTC($this->item->date_start),
			'created_by'   => $this->item->user_id,
			'modified'     => Functions::dateToUTC($this->item->date_mod),
			'publish_up'   => Functions::dateToUTC($this->item->date_start),
			'publish_down' => Functions::dateToUTC($this->item->date_exp)
		];

		// Load custom fields
		$this->attachCustomFields($payload);

		// Attach the extra fields
		$this->attachExtraFields($payload);

		// Attach rating and review data
		$this->attachRatingAndReviewData($payload);

		return $payload;
	}

	/**
	 * Gets the product image
	 * 
	 * @return  string
	 */
	public function getProductImage()
	{
		$db	= Factory::getDBO();
		$query = $db->getQuery(true);

		$query->select($db->quoteName(['path', 'name', 'ext']))
			  ->from($db->quoteName('#__djcf_images'))
			  ->where($db->quoteName('item_id') . ' = ' . $db->quote($this->getThingId()))
			  ->order($db->quoteName('ordering') . ' ASC')
			  ->setLimit(1);
			  
		$db->setQuery($query);
		$images = $db->loadRow();

		if (!is_array($images) || count($images) == 0 || !isset($images[0]) || !isset($images[1]) || !isset($images[2]))
		{
			return;
		}

        return $images[0] . $images[1] . '.' . $images[2];
	}

	/**
	 * Gets the location name based on region
	 * 
	 * @param   integer  $region_id
	 * 
	 * @return  string
	 */
	private function getLocationName($region_id)
	{
		$location_name = '';
		
		$regions = $this->model->getRegions();

		foreach ($regions as $r)
		{
			if ($r->id == $region_id)
			{
				$location_name = $r->name;
				break;
			}
		}

		return $location_name;
	}

	/**
	 * Attach Custom Fields to the payload
	 *
	 * @param	array	$payload
	 * @param   string 	$prefix
	 *
	 * @return	void
	 */
	private function attachCustomFields(&$payload, $prefix = 'cf.')
	{
		// get the custom fields
		$fields = $this->model->getFields($this->item->cat_id);
		
		if (!is_array($fields) || count($fields) == 0)
		{
			return;
		}

		// Attach the custom fields
		foreach ($fields as $key => $field)
		{
			$field_id = $field->id;

			$field_path = $prefix . $field_id;
			$value = $field->value;

			$type = $field->type;

			if ($type == 'checkbox')
			{
				// example value: ;yes;no;
				$value = ltrim($value, ';');
				$value = rtrim($value, ';');
				$value = explode(';', $value);
			}
			else if ($type == 'date')
			{
				$value = $field->value_date;
			}
			else if ($type == 'date_from_to')
			{
				$value = $field->value_date . ' - ' . $field->value_date_to;
			}

			$payload[$field_path] = is_array($value) ? implode(', ', $value) : $value;
		}
	}

	/**
	 * Attach extra fields to the payload
	 * 
	 * @param   array   $payload
	 * 
	 * @return  void
	 */
	private function attachExtraFields(&$payload)
	{
		$extra_fields = $this->getExtraFields();

		if (!is_array($extra_fields) || count($extra_fields) == 0)
		{
			return;
		}

		foreach ($extra_fields as $key => $field)
		{
			$object_item_name = $field['name'];
			$value = isset($this->item->$object_item_name) ? $this->item->$object_item_name : '';
			
			if ($field['name'] == 'expiration_date')
			{
				$value = Functions::dateToUTC($this->item->date_exp);
			}
			else if ($field['name'] == 'location')
			{
				$value = $this->getLocationName($this->item->region_id);
			}
			else if ($field['name'] == 'latitude_longitude')
			{
				$value = $this->item->latitude . ',' . $this->item->longitude;
			}

			$payload[$field['name']] = $value;
		}
	}

	/**
	 * Attach ratings and reviews to the payload
	 * 
	 * @param   array  $payload
	 * 
	 * @return  void
	 */
	private function attachRatingAndReviewData(&$payload)
	{
		if (!class_exists('DJReviewsModelRating') || !class_exists('DJReviewsModelReviewsList'))
		{
			return;
		}
		
		$ratingModel = BaseDatabaseModel::getInstance('Rating', 'DJReviewsModel', ['ignore_request' => true]);
		$ratingItem = $ratingModel->getItem($this->getProductID());

		$ratingValue = $ratingItem->avg_rate;
		$reviewCount = $ratingItem->r_count;
		
		$payload['ratingValue'] = $ratingValue;
		$payload['reviewCount'] = $reviewCount;
		$payload['reviews'] = $this->getReviews();
	}

	/**
	 * Get the ID of the product from the djrevs_objects so we can fetch the reviews and ratings.
	 * 
	 * @return  string
	 */
	private function getProductID()
	{
		$hash  = md5('gsdDJID_' . $this->item->id);
        $cache = Cache::read($hash);

        if ($cache)
        {
            return $cache;
		}
		
		$db = Factory::getDbo();

		$query = $db->getQuery(true);

		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__djrevs_objects'));
		$query->where($db->quoteName('entry_id') . ' = ' . $this->item->id);

		$db->setQuery($query);

		$row = $db->loadAssoc();

		$id = isset($row['id']) ? $row['id'] : '';

        return Cache::set($hash, $id);
	}

    /**
     * Get the reviews of the item
     * 
     * @return  array
     */
    protected function getReviews()
    {
		$model = BaseDatabaseModel::getInstance('ReviewsList', 'DJReviewsModel');
		
		// We need to set the state of filter.item_id to the id of the product(taken from djrevs_objects)
		// in order to retrieve the reviews
		$state = $model->getState();
		$model->setState('filter.item_id', $this->getProductID());
		
		$reviewItems = $model->getItems();

        $data = [];

        foreach ($reviewItems as $review)
        {
            $data[] = [
                'author' => $review->user_name,
                'datePublished' => $review->created,
                'description' => $review->message,
                'rating' => $review->avg_rate
            ];
		}
		
		return $data;
    }

	/**
	 * Gets all custom fields
	 * 
	 * @return  array
	 */
	private function getCustomFields()
	{
		$hash = md5($this->_name . 'cf');

		if (Cache::has($hash))
		{
			return Cache::get($hash);
		}

		$db = Factory::getDbo();

		$query = $db->getQuery(true);

		// Get all DJ Classifieds Fields
		$query
			->select($db->quoteName(['id', 'label'], ['name', 'title']))
			->from($db->quoteName('#__djcf_fields'));

		$db->setQuery($query);

		$fields = $db->loadAssocList();
		
		return Cache::set($hash, $fields);
	}

	/**
	 * Get the following extra fields:
	 * - Website
	 * - Video link
	 * - Contact
	 * - Expiration date
	 * - Location
	 * - Address
	 * - Post code
	 * - Latitude
	 * - Longitude
	 * - Latitude and Longitude
	 * 
	 * @return  array
	 */
	private function getExtraFields()
	{
		// load language
		NRFramework\Functions::loadLanguage('com_djclassifieds');

		return [
			[
				'name'  => 'website',
				'title' => Text::_('COM_DJCLASSIFIEDS_WEBSITE')
			],
			[
				'name'  => 'video',
				'title' => Text::_('COM_DJCLASSIFIEDS_VIDEO')
			],
			[
				'name'  => 'contact',
				'title' => Text::_('COM_DJCLASSIFIEDS_CONTACT')
			],
			[
				'name'  => 'expiration_date',
				'title' => Text::_('COM_DJCLASSIFIEDS_EXPIRATION_DATE')
			],
			[
				'name'  => 'location',
				'title' => Text::_('COM_DJCLASSIFIEDS_LOCATION')
			],
			[
				'name'  => 'address',
				'title' => Text::_('COM_DJCLASSIFIEDS_ADDRESS')
			],
			[
				'name'  => 'post_code',
				'title' => Text::_('COM_DJCLASSIFIEDS_POSTCODE')
			],
			[
				'name'  => 'latitude',
				'title' => Text::_('COM_DJCLASSIFIEDS_LATITUDE')
			],
			[
				'name'  => 'longitude',
				'title' => Text::_('COM_DJCLASSIFIEDS_LONGITUDE')
			],
			[
				'name'  => 'latitude_longitude',
				'title' => Text::_('COM_DJCLASSIFIEDS_LATITUDE') . ',' . Text::_('COM_DJCLASSIFIEDS_LONGITUDE')
			]
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
		parent::onMapOptions($plugin, $options);

		if ($plugin != $this->_name)
        {
			return;
		}

		$integration_prefix = 'gsd.item.';

		// Add Integration fields
		$integration_fields = $this->getExtraFields();
		foreach ($integration_fields as $key => $value)
		{
			$options['GSD_INTEGRATION'][$integration_prefix . $value['name']] = $value['title'];
		}
		
		// Add Custom Fields
		if (!$custom_fields = $this->getCustomFields())
		{
			return;
		}
		
		$custom_fields_options = [];
	
		foreach ($custom_fields as $key => $field)
		{
			$custom_fields_options[$field['name']] = $field['title'];
		}

		MappingOptions::add($options, $custom_fields_options);

		$remove_options = [
			'sku',
			'brand',
			'offerAvailability'
		];

		// Remove unsupported mapping options
		foreach ($remove_options as $option)
		{
			unset($options['GSD_INTEGRATION']['gsd.item.' . $option]);
		}
	}
}
