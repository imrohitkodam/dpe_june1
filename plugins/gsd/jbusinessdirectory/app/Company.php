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

trait Company
{
	/**
	 * Provides the payload for Company pages.
	 * 
	 * Producing Schema: Local Business
	 * 
	 * @return  mixed
	 */
	protected function viewCompanies()
	{
		// Make sure we have a valid ID
		if (!$id = $this->getThingID())
		{
			return;
		}

		if (!class_exists('JBusinessUtil'))
		{
			return;
		}

		$model = BaseDatabaseModel::getInstance('ManageCompany', 'JBusinessDirectoryModel', ['ignore_request' => true]);
		if (!$this->item = $model->getItem($id))
		{
			return;
		}

		// Get reviews for this business
		$reviews = $this->getReviews(1);

		$country = $this->getItemCountry($this->item->countryId);

		// Get the currency
		$currency = JBusinessUtil::getCurrency($this->getAppSettingPropertyValue('currency_id'));

		$price = $this->getPackageProperty('price');

        // Prepare Data
        $payload = [
			'id' => $id,
			'alias' => $this->item->alias,
			'headline' => $this->item->name,
			'image' => BD_PICTURES_PATH . $this->item->logoLocation,
			'imagetext' => Helper::getFirstImageFromString($this->item->short_description . $this->item->description),
			'introtext' => $this->item->short_description,
			'description' => empty($this->item->short_description) ? $this->item->description : $this->item->short_description,
			'fulltext' => $this->item->description,
			'created' => Functions::dateToUTC($this->item->creationDate),
			'created_by' => $this->item->userId,
			'modified' => Functions::dateToUTC($this->item->modified),
			'streetAddress' => $this->item->address,
			'addressCountry' => $country ? $country->country_name : '',
			'postalCode' => $this->item->postalCode,
			'addressRegion' => $this->item->city . ', ' . $this->item->county,
			'addressLocality' => $this->item->address . ' ' . $this->item->street_number,
			'geo' => $this->item->latitude . ',' . $this->item->longitude,
			'telephone' => $this->item->phone,
			'offerPrice' => $price,
			'priceRange' => $price ? ($currency->currency_symbol === '#' ? $currency->currency_name : $currency->currency_symbol . $price) : '',
			'reviews' => $reviews,
			'reviewCount' => is_array($reviews) ? count($reviews) : 0,
			'ratingValue' => JBusinessUtil::getReviewsAverageScore($reviews),
			'metadesc' => $this->item->meta_description,
			'openinghours' => $this->getCompanyOpeningHours()
        ];

		// Add shared fields values
		$payload = array_merge($payload, $this->getSharedFieldValues());

		// Add business fields values
		$payload = array_merge($payload, $this->getCompanyFieldValues());
		
		// Add business custom fields (Custom Attributes) values
		$payload = array_merge($payload, $this->getItemCustomFieldsValues('company'));

		return $payload;
    }

	/**
	 * Company fields of a J-BusinessDirectory item.
	 * 
	 * @return  array
	 */
	private function getCompanyFields()
	{
		$fields = [
			// Package Name
			'package_name' => Text::_('PLG_GSD_JBUSINESSDIRECTORY_PACKAGE_NAME'),
			// Package Description
			'package_description' => Text::_('PLG_GSD_JBUSINESSDIRECTORY_PACKAGE_DESCRIPTION'),
			// Package Price
			'package_price' => Text::_('PLG_GSD_JBUSINESSDIRECTORY_PACKAGE_PRICE'),
			// Package Special Price
			'package_special_price' => Text::_('PLG_GSD_JBUSINESSDIRECTORY_PACKAGE_SPECIAL_PRICE'),
			// Package Renewal Price
			'package_renewal_price' => Text::_('PLG_GSD_JBUSINESSDIRECTORY_PACKAGE_RENEWAL_PRICE'),
			// Telephone
			'telephone' => Text::_('LNG_TELEPHONE'),
			// Business Name
			'name' => Text::_('LNG_COMPANY_NAME'),
			// Website
			'website' => Text::_('LNG_WEBSITE'),
			// Business Type
			'business_type' => Text::_('LNG_COMPANY_TYPE'),
			// Establishment Year
			'establishment_year' => Text::_('LNG_ESTABLISHMENT_YEAR'),
			// Employees
			'employees' => Text::_('LNG_EMPLOYEES'),
			// Keywords
			'keywords' => Text::_('LNG_KEYWORDS'),
			// Slogan
			'slogan' => Text::_('LNG_COMPANY_SLOGAN'),
			// Categories
			'selectedCategories' => Text::_('LNG_CATEGORIES'),
			// Main Category
			'mainSubcategory' => Text::_('LNG_MAIN_SUBCATEGORY'),
			// Activity Radius
			'activity_radius' => Text::_('LNG_ACTIVITY_RADIUS'),
			// Business Contact Information - Email
			'email' => 'PLG_GSD_JBUSINESSDIRECTORY_BUSINESS_CONTACT_INFORMATION_EMAIL',
			// Company Editors
			'companyEditors' => 'LNG_COMPANY_EDITORS',
			// Business Cover Image
			'business_cover_image' => Text::_('LNG_BUSINESS_COVER_IMAGE'),
			// Business Opening Hours Notes
			'business_opening_hours_notes' => Text::_('PLG_GSD_JBUSINESSDIRECTORY_BUSINESS_OPENING_HOURS_NOTES'),
			// Business Custom Tab Name
			'business_custom_tab_name' => Text::_('PLG_GSD_JBUSINESSDIRECTORY_BUSINESS_CUSTOM_TAB_NAME'),
			// Business Custom Tab Content
			'business_custom_tab_content' => Text::_('PLG_GSD_JBUSINESSDIRECTORY_BUSINESS_CUSTOM_TAB_CONTENT')
		];

		/**
		 * Business Contact Person consists from a Repeater field
		 * which we do not know how many items can have per Business listing.
		 * 
		 * For this purpose, we assume 5 person details are enough and add them
		 * manually. If we later need more items, we can increase them.
		 */
		for ($i = 0; $i < 5; $i++)
		{
			$fields = array_merge($fields, [
				// Business Contact Person - Contact #XX - Department
				'contact_department_' . $i => sprintf(Text::_('PLG_GSD_JBUSINESSDIRECTORY_BUSINESS_CONTACT_PERSON_DEPARTMENT'), ($i + 1)),
				// Business Contact Person - Contact #XX - Name
				'contact_name_' . $i => sprintf(Text::_('PLG_GSD_JBUSINESSDIRECTORY_BUSINESS_CONTACT_PERSON_NAME'), ($i + 1)),
				// Business Contact Person - Contact #XX - Telephone
				'contact_phone_' . $i => sprintf(Text::_('PLG_GSD_JBUSINESSDIRECTORY_BUSINESS_CONTACT_PERSON_TELEPHONE'), ($i + 1)),
				// Business Contact Person - Contact #XX - Email
				'contact_email_' . $i => sprintf(Text::_('PLG_GSD_JBUSINESSDIRECTORY_BUSINESS_CONTACT_PERSON_EMAIL'), ($i + 1))
			]);
		}

		return $fields;
	}

	/**
	 * Returns the company related field values.
	 * 
	 * @return  array
	 */
	private function getCompanyFieldValues()
	{
		$payload = [
			'company.package_name' => $this->getPackageProperty('name'),
			'company.package_description' => $this->getPackageProperty('description'),
			'company.package_price' => $this->getPackageProperty('price'),
			'company.package_special_price' => $this->getPackageProperty('special_price'),
			'company.package_renewal_price' => $this->getPackageProperty('renewal_price'),
			'company.telephone' => $this->item->phone,
			'company.name' => $this->item->name,
			'company.website' => $this->item->website,
			'company.business_type' => $this->getCompanyTypesNames(),
			'company.establishment_year' => $this->item->establishment_year,
			'company.employees' => $this->item->employees,
			'company.keywords' => $this->item->keywords,
			'company.slogan' => $this->item->slogan,
			'company.selectedCategories' => $this->getItemSelectedCategories($this->item->selectedCategories),
			'company.mainCategory' => $this->getItemCategoryByID($this->item->mainSubcategory),
			'company.activity_radius' => $this->item->activity_radius,
			'company.email' => $this->item->email,
			'company.companyEditors' => $this->getCompanyEditors(),
			'company.business_cover_image' => BD_PICTURES_PATH . $this->item->business_cover_image,
			'company.business_opening_hours_notes' => $this->item->notes_hours,
			'company.business_custom_tab_name' => $this->item->custom_tab_name,
			'company.business_custom_tab_content' => $this->item->custom_tab_content
		];
		
		return array_merge($payload, $this->getBusinessContacts($this->item->contacts));
	}

	/**
	 * Get all Business Contacts of a business listing.
	 * 
	 * @param   array  $contacts
	 * 
	 * @return  mixed
	 */
	private function getBusinessContacts($contacts = [])
	{
		if (!$contacts || !is_array($contacts) || !count($contacts))
		{
			return;
		}

		$payload = [];

		for ($i = 0; $i < count($contacts); $i++)
		{
			$payload['company.contact_department_' . $i] = $contacts[$i]->contact_department;
			$payload['company.contact_name_' . $i] = $contacts[$i]->contact_name;
			$payload['company.contact_phone_' . $i] = $contacts[$i]->contact_phone;
			$payload['company.contact_email_' . $i] = $contacts[$i]->contact_email;
		}
		
		return $payload;
	}

	/**
	 * Returns the company Opening Hours
	 * 
	 * @return  object
	 */
	private function getCompanyOpeningHours()
	{
		$opening_hours = new stdClass();
		$opening_hours->option = 2;

		$companyModel = BaseDatabaseModel::getInstance('Company', 'JBusinessDirectoryModel', ['ignore_request' => true]);
		$working_days = $companyModel->getWorkingDays($this->item->id);

		foreach ($working_days as $key => $value)
		{
			$lc_day = strtolower($value->name);

			$oh_item_value = new stdClass();
			$oh_item_value->enabled = $value->workHours['status'] === '1';
			$oh_item_value->start = $value->workHours['start_time'];
			$oh_item_value->end = $value->workHours['end_time'];

			// Add break hours
			if (isset($value->breakHours))
			{
				$oh_item_value->start1 = isset($value->breakHours['start_time'][0]) ? $value->breakHours['start_time'][0] : false;
				$oh_item_value->end1 = isset($value->breakHours['end_time'][0]) ? $value->breakHours['end_time'][0] : false;
			}
			
			$opening_hours->$lc_day = $oh_item_value;
		}

		return $opening_hours;
	}

	/**
	 * Returns the company editors names.
	 * 
	 * @return  mixed
	 */
	private function getCompanyEditors()
	{
		if (!$this->item->editors || !is_array($this->item->editors) || !count($this->item->editors))
		{
			return;
		}

		$names = [];

		foreach ($this->item->editors as $key => $value)
		{
			$names[] = trim($value->name);
		}
		
		return implode(', ', $names);
	}

	/**
	 * Finds and returns all company types names
	 * 
	 * @return  mixed
	 */
	private function getCompanyTypesNames()
	{
		if (!$this->item->typeId)
		{
			return;
		}

		if (!$typesTable = Table::getInstance('CompanyTypes', '\\Joomla\\CMS\\Table\\', []))
		{
			return;
		}

		$names = [];

		foreach ($this->item->typeId as $id)
		{
			if (!$name = $typesTable->getCompanyType($id))
			{
				continue;
			}
			
			$names[] = trim($name->name);
		}

		return implode(', ', $names);
	}

	/**
	 * Returns the company.
	 * 
	 * @var   	int     $id
	 * 
	 * @return  object
	 */
	private function getCompany($id)
	{
		if (!$table = Table::getInstance('Company', '\\Joomla\\CMS\\Table\\', []))
		{
			return;
		}

		return $table->getCompany($id);
	}

	/**
	 * Returns a property value of the item's package.
	 * 
	 * @param   string  $prop
	 * 
	 * @return  mixed
	 */
	private function getPackageProperty($prop = null)
	{
		if (!$prop)
		{
			return;
		}

		if (!isset($this->item->package))
		{
			return;
		}

		if (!property_exists($this->item->package, $prop))
		{
			return;
		}

		return $this->item->package->$prop;
	}
}