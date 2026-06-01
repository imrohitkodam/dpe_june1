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

use GSD\Helper;
use NRFramework\Functions;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;

trait Offer
{
	/**
	 * The currency.
	 * 
	 * @var  object
	 */
	protected $currency;

	/**
	 * The currency symbol.
	 * 
	 * @var  string
	 */
	protected $currency_symbol;
	
	/**
	 * Provides the payload for Offer pages.
	 * 
	 * Producing Schema: Service
	 * 
	 * @return  mixed
	 */
	protected function viewOffer()
	{
		$this->thingRequestIDName = 'offerId';

        // Make sure we have a valid ID
		if (!$id = $this->getThingID())
		{
			return;
        }

		if (!class_exists('JBusinessUtil'))
		{
			return;
		}

		$model = BaseDatabaseModel::getInstance('Offer', 'JBusinessDirectoryModel', ['ignore_request' => true]);
		if (!$this->item = $model->getItem($id))
		{
			return;
		}

		if ($attributesTable = Table::getInstance('OfferAttributes', '\\Joomla\\CMS\\Table\\', []))
		{
			$this->item->customFields = $attributesTable->getOfferAttributes($id);
		}

		$country = $this->getItemCountry($this->item->countryId);
		
		// Get the currency
		$this->currency = JBusinessUtil::getCurrency($this->item->currencyId);
		$this->currency_symbol = $this->currency ? ($this->currency->currency_symbol === '#' ? $this->currency->currency_name : $this->currency->currency_symbol) : '';

		$shared_fields_values = $this->getSharedFieldValues();

		$lowestPrice = $this->item->specialPrice ? $this->item->specialPrice : $this->item->price;

		$payload = [
			'id' => $id,
			'alias' => $this->item->alias,
			'headline' => $this->item->subject,
			// Since there isn't a specific image we can set, get the first image from the Pictures field
			'image' => isset($shared_fields_values['shared.picture_url_0']) ? $shared_fields_values['shared.picture_url_0'] : '',
			'imagetext' => Helper::getFirstImageFromString($this->item->short_description . $this->item->description),
			'introtext' => $this->item->short_description,
			'description' => empty($this->item->short_description) ? $this->item->description : $this->item->short_description,
			'fulltext' => $this->item->description,
			// Set the provider name to the company name
			'provider_name' => $this->item->company->name,
			// Set the provider image as the company's logo
			'provider_image' => BD_PICTURES_PATH . $this->item->company->logoLocation,
			'provider_streetAddress' => $this->item->address,
			'provider_country' => $country ? $country->country_name : '',
			'provider_city' => $this->item->city,
			'provider_addressRegion' => $this->item->city . ', ' . $this->item->county,
			'provider_postalCode' => $this->item->postalCode,
			'offerPrice' => $lowestPrice,
			'priceRange' => ($this->item->specialPrice ? $this->currency_symbol . $this->item->specialPrice . '-' : '') . $this->currency_symbol . $this->item->price,
			'currency' => $this->currency_symbol,
			'metakey' => $this->item->meta_keywords,
			'metadesc' => $this->item->meta_description,
			'created' => Functions::dateToUTC($this->item->created)
		];

		// Add shared fields values
		$payload = array_merge($payload, $shared_fields_values);

		// Add offer fields values
		$payload = array_merge($payload, $this->getOfferFieldValues());

		// Add offer custom fields (Custom Attributes) values
		$payload = array_merge($payload, $this->getItemCustomFieldsValues('offer'));

		return $payload;
	}

	/**
	 * Offer fields of a J-BusinessDirectory item.
	 * 
	 * @return  array
	 */
	private function getOfferFields()
	{
		$fields = [
			// Select a business
			'company' => Text::_('LNG_SELECT_A_BUSINESS'),
			// Subject
			'subject' => Text::_('LNG_SUBJECT'),
			// Start Date
			'startDate' => Text::_('LNG_START_DATE'),
			// End Date
			'endDate' => Text::_('LNG_END_DATE'),
			// Publish start date
			'publish_start_date' => Text::_('LNG_PUBLISH_START_DATE'),
			// Publish end date
			'publish_end_date' => Text::_('LNG_PUBLISH_END_DATE'),
			// Publish start date time
			'publish_start_date_time' => Text::_('PLG_GSD_JBUSINESSDIRECTORY_START_DATE_TIME'),
			// Publish end date time
			'publish_end_date_time' => Text::_('PLG_GSD_JBUSINESSDIRECTORY_END_DATE_TIME'),
			// Time Zone
			'time_zone' => Text::_('LNG_TIME_ZONE'),
			// Publish start time
			'publish_start_time' => Text::_('LNG_PUBLISH_START_TIME'),
			// Publish end time
			'publish_end_time' => Text::_('LNG_PUBLISH_END_TIME'),
			// Price
			'price' => Text::_('LNG_PRICE'),
			// Price base
			'price_base' => Text::_('LNG_PRICE_BASE'),
			// Price base unit
			'price_base_unit' => Text::_('LNG_PRICE_BASE_UNIT'),
			// Special price
			'specialPrice' => Text::_('LNG_SPECIAL_PRICE'),
			// Special price base
			'special_price_base' => Text::_('LNG_SPECIAL_PRICE_BASE'),
			// Special price base unit
			'special_price_base_unit' => Text::_('LNG_SPECIAL_PRICE_BASE_UNIT'),
			// Currency
			'currency' => Text::_('LNG_CURRENCY'),
			// Price Text
			'price_text' => Text::_('LNG_PRICE_TEXT'),
			// Contact Email
			'contact_email' => Text::_('PLG_GSD_JBUSINESSDIRECTORY_OFFER_CONTACT_EMAIL'),
			// Offer Type
			'offer_type' => Text::_('LNG_TYPE'),
			// Coupons number
			'total_coupons' => Text::_('LNG_COUPONS_NUMBER'),
			// Category
			'categories' => Text::_('LNG_CATEGORY'),
			// Main Category
			'main_subcategory' => Text::_('LNG_MAIN_SUBCATEGORY'),
			// Meta Keywords
			'meta_keywords' => Text::_('LNG_META_KEYWORDS')
		];
		
		/**
		 * Attachments consists from a Repeater field
		 * which we do not know how many items can have per Event.
		 * 
		 * For this purpose, we assume 5 attachments details are enough and add them
		 * manually. If we later need more items, we can increase them.
		 */
		for ($i = 0; $i < 5; $i++)
		{
			$fields = array_merge($fields, [
				'attachment_' . $i => sprintf(Text::_('PLG_GSD_JBUSINESSDIRECTORY_ATTACHMENT'), $i + 1)
			]);
		}

		return $fields;
	}

	/**
	 * Returns the offer related field values.
	 * 
	 * @return  array
	 */
	private function getOfferFieldValues()
	{
		$payload = [
			'offer.company' => $this->item->company->name,
			'offer.subject' => $this->item->subject,
			'offer.startDate' => $this->item->startDate,
			'offer.endDate' => $this->item->endDate,
			'offer.publish_start_date' => $this->item->publish_start_date,
			'offer.publish_end_date' => $this->item->publish_end_date,
			'offer.publish_start_date_time' => Functions::dateToUTC($this->item->publish_start_date . ' ' . $this->item->publish_start_time),
			'offer.publish_end_date_time' => Functions::dateToUTC($this->item->publish_end_date . ' ' . $this->item->publish_end_time),
			'offer.price' => $this->currency_symbol . $this->item->price,
			'offer.price_base' => $this->item->price_base,
			'offer.price_base_unit' => $this->item->price_base_unit,
			'offer.specialPrice' => $this->item->specialPrice,
			'offer.special_price_base' => $this->item->special_price_base,
			'offer.special_price_base_unit' => $this->item->special_price_base_unit,
			'offer.currency' => $this->currency_symbol,
			'offer.price_text' => $this->item->price_text,
			'offer.contact_email' => $this->item->contact_email,
			'offer.offer_type' => $this->getOfferType(),
			'offer.total_coupons' => $this->item->total_coupons,
			'offer.categories' => $this->getItemCategories(),
			'offer.main_subcategory' => $this->getItemCategoryByID($this->item->main_subcategory),
			'offer.meta_keywords' => $this->item->meta_keywords
		];

		return array_merge($payload, $this->getItemAttachments('offer.'));
	}

	/**
	 * Return the offer name
	 * 
	 * @return  mixed
	 */
	private function getOfferType()
	{
		if (!$offerTable = Table::getInstance('OfferType', '\\Joomla\\CMS\\Table\\', []))
		{
			return;
		}
		
		if (!$this->item->offer_type)
		{
			return;
		}
		
		if (!$offer = $offerTable->getOfferType($this->item->offer_type))
		{
			return;
		}

		return $offer->name;
	}
}