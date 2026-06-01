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

trait Event
{
	/**
	 * Provides the payload for Event pages.
	 * 
	 * Producing Schema: Event
	 * 
	 * @return  mixed
	 */
	protected function viewEvent()
	{
		$this->thingRequestIDName = 'eventId';

        // Make sure we have a valid ID
		if (!$id = $this->getThingID())
		{
			return;
        }

		if (!class_exists('JBusinessUtil'))
		{
			return;
		}
		
		$model = BaseDatabaseModel::getInstance('Event', 'JBusinessDirectoryModel', ['ignore_request' => true]);
		if (!$this->item = $model->getItem($id))
		{
			return;
		}

		if ($videosTable = Table::getInstance('EventVideos', '\\Joomla\\CMS\\Table\\', []))
		{
			$this->item->videos = $videosTable->getEventVideos($id);
		}

		if ($attributesTable = Table::getInstance('EventAttributes', '\\Joomla\\CMS\\Table\\', []))
		{
			$this->item->customFields = $attributesTable->getEventAttributes($id);
		}

		$country = $this->getItemCountry($this->item->countryId);

		$shared_fields_values = $this->getSharedFieldValues();

		$payload = [
			'id' => $id,
			'alias' => $this->item->alias,
			'headline' => $this->item->name,
			// Since there isn't a specific image we can set, get the first image from the Pictures field
			'image' => isset($shared_fields_values['shared.picture_url_0']) ? $shared_fields_values['shared.picture_url_0'] : '',
			'imagetext' => Helper::getFirstImageFromString($this->item->short_description . $this->item->description),
			'introtext' => $this->item->short_description,
			'description' => empty($this->item->short_description) ? $this->item->description : $this->item->short_description,
			'fulltext' => $this->item->description,
			'created' => Functions::dateToUTC($this->item->created),
			'startDate' => Functions::dateToUTC($this->item->start_date . ' ' . $this->item->start_time),
			'endDate' => Functions::dateToUTC($this->item->end_date . ' ' . $this->item->end_time),
			'locationName' => $this->item->address,
			'locationAddress' => $this->item->address,
			'addressCountry' => $country ? $country->country_name : '',
			'addressLocality' => $this->item->address . ' ' . $this->item->street_number,
			'addressRegion' => $this->item->city . ', ' . $this->item->county,
			'postalCode' => $this->item->postalCode,
			'offerPrice' => $this->item->price,
			'metakey' => $this->item->meta_keywords,
			'metadesc' => $this->item->meta_description,
			'online_url' => $this->item->attendance_url
		];

		// Add shared fields values
		$payload = array_merge($payload, $shared_fields_values);

		// Add event fields values
		$payload = array_merge($payload, $this->getEventFieldValues());

		// Add event custom fields (Custom Attributes) values
		$payload = array_merge($payload, $this->getItemCustomFieldsValues('event'));

		return $payload;
	}

	/**
	 * Event fields of a J-BusinessDirectory item.
	 * 
	 * @return  array
	 */
	private function getEventFields()
	{
		$fields = [
			// Hosted By
			'company' => Text::_('LNG_HOSTED_BY'),
			// Attendance Mode
			'attendance_mode' => Text::_('LNG_ATTENDANCE_MODE'),
			// Attendance URL
			'attendance_url' => Text::_('LNG_ATTENDANCE_URL'),
			// Type
			'type' => Text::_('LNG_TYPE'),
			// Price
			'price' => Text::_('LNG_PRICE'),
			// Currency
			'currency' => Text::_('LNG_CURRENCY'),
			// Minimum Age
			'min_age' => Text::_('LNG_MIN_AGE'),
			// Maximum Age
			'max_age' => Text::_('LNG_MAX_AGE'),
			// Category
			'categories' => Text::_('LNG_CATEGORY'),
			// Main Category
			'main_subcategory' => Text::_('LNG_MAIN_SUBCATEGORY'),
			// Start Date
			'start_date' => Text::_('LNG_START_DATE'),
			// Start Datetime
			'start_date_time' => Text::_('NR_START_PUBLISHING'),
			// End Date
			'end_date' => Text::_('LNG_END_DATE'),
			// End Datetime
			'end_date_time' => Text::_('NR_FINISH_PUBLISHING'),
			// Start Time
			'start_time' => Text::_('LNG_START_TIME'),
			// End Time
			'end_time' => Text::_('LNG_END_TIME'),
			// Time Zone
			'time_zone' => Text::_('LNG_TIME_ZONE'),
			// Doors Open Time
			'doors_open_time' => Text::_('LNG_DOORS_OPEN_TIME'),
			// Participating Companies
			'associated_companies' => Text::_('LNG_ASSOCIATED_COMPANIES'),
			// Event Contact Telephone
			'contact_phone' => Text::_('PLG_GSD_JBUSINESSDIRECTORY_EVENT_CONTACT_TELEPHONE'),
			// Event Contact Email
			'contact_email' => Text::_('PLG_GSD_JBUSINESSDIRECTORY_EVENT_CONTACT_EMAIL'),
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
	 * Returns the event related field values.
	 * 
	 * @return  array
	 */
	private function getEventFieldValues()
	{
		// Get the company
		$company = $this->getCompany($this->item->company_id);

		// Get the currency
		$currency = JBusinessUtil::getCurrency($this->item->currency_id);
		$currency_symbol = $currency->currency_symbol === '#' ? $currency->currency_name : $currency->currency_symbol;

		$payload = [
			'event.company' => $company ? $company->name : '',
			'event.attendance_mode' => $this->getAttendanceMode($this->item->attendance_mode),
			'event.attendance_url' => $this->item->attendance_url,
			'event.type' => $this->item->eventType,
			'event.price' => $currency_symbol . $this->item->price,
			'event.currency' => $currency_symbol,
			'event.min_age' => $this->item->min_age,
			'event.max_age' => $this->item->max_age,
			'event.categories' => $this->getItemCategories(),
			'event.main_subcategory' => $this->getItemCategoryByID($this->item->main_subcategory),
			'event.start_date' => $this->item->start_date,
			'event.start_date_time' => Functions::dateToUTC($this->item->start_date . ' ' . $this->item->start_time),
			'event.end_date' => $this->item->end_date,
			'event.end_date_time' => Functions::dateToUTC($this->item->end_date . ' ' . $this->item->end_time),
			'event.start_time' => $this->item->start_time,
			'event.end_time' => $this->item->end_time,
			'event.time_zone' => $this->item->time_zone,
			'event.doors_open_time' => $this->item->doors_open_time,
			'event.associated_companies' => $this->getEventAssociatedCompanies(),
			'event.contact_phone' => $this->item->contact_phone,
			'event.contact_email' => $this->item->contact_email,
			'event.meta_keywords' => $this->item->meta_keywords
		];

		return array_merge($payload, $this->getItemAttachments('event.'));
	}

	/**
	 * Returns all event associated companies.
	 * 
	 * @return  mixed
	 */
	private function getEventAssociatedCompanies()
	{
		if (!$eventAssociatedCompanies = Table::getInstance('EventAssociatedCompanies', '\\Joomla\\CMS\\Table\\', []))
		{
			return;
		}
		
		// Ensure this event is tied to a company
		if (!$eventAssociatedCompanies->getAssociatedCompaniesByEvent($this->item->id))
		{
			return;
		}
		
		if (!$associated_companies = $eventAssociatedCompanies->getAssociatedCompanyOptions($this->item->id))
		{
			return;
		}
		
		$labels = [];
		
		foreach ($associated_companies as $key => $value)
		{
			if (!isset($value->name))
			{
				continue;
			}
			
			$labels[] = $value->name;
		}
		
		return implode(', ', $labels);
	}

	/**
	 * Returns the attendance mode label.
	 * 
	 * @param   string  $value
	 * 
	 * @return  mixed
	 */
	private function getAttendanceMode($value)
	{
		$mode = 'Offline';
		
		foreach(JBusinessUtil::getAttendanceModes() as $md)
		{
			if ($md->value !== $value)
			{
				continue;
			}
			
			$mode = $md->text;
		}

		switch (strtolower($mode)) {
			case 'live':
				$mode = 'Offline';
				break;
			case 'virtual':
				$mode = 'Online';
				break;
			case 'mixed':
				$mode = 'Mixed';
				break;
		}

		return 'https://schema.org/' . $mode . 'EventAttendanceMode';
	}
}