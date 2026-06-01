<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('Restricted access');

use NRFramework\Functions;
use NRFramework\Cache;
use GSD\Helper;
use GSD\MappingOptions;
use GSD\PluginBaseEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

/**
 *  JEvents Google Structured Data Plugin
 */
class plgGSDJEvents extends PluginBaseEvent
{

    /**
     *  Indicates the query string parameter name that is used by the front-end component
     *
     *  @var  string
     */
    protected $thingRequestIDName = 'evid';

    /**
     *  Get View Name
     *
     *  @return  string  Return the current executed view in the front-end
     */
    protected function getView()
    {
        $input = $this->app->input;

		if ($input->get('jevtask') == 'icalrepeat' || $input->get('jevtask') == 'icalrepeat.detail')
		{
			return 'event';
		}

        return $this->app->input->get('view');
	}

	/**
	 *  Get event's data
	 *
	 *  @return  array
	 */
	public function viewEvent()
	{
		// Thing id
		$id = $this->app->input->get($this->thingRequestIDName);

		$dataModel = new JEventsDataModel("JEventsAdminDBModel");

		list($year, $month, $day) = JEVHelper::getYMD();
		$event = $dataModel->getEventData($id, "icaldb", $year, $month, $day);
		$event = $event['row'];

		$custom_fields = $this->getAllCustomFields($id);

		$date_start = Functions::dateToUTC($event->_dtstart);
		$date_end   = Functions::dateToUTC($event->_dtend);

		// Array data
		$payload = [
			'id' 				  => $event->_ev_id,
			'headline'    		  => $event->_title,
			'fulltext'			  => $event->_text,
			'description' 		  => $event->_description,
			'imagetext'	   		  => Helper::getFirstImageFromString($event->text),
			'startDate'	  		  => $date_start,
			'endDate'	  		  => $date_end,
			'created_by'  		  => $event->_created_by,
			'created'     		  => $event->_created,
			'publish_up'  		  => $event->_publish_up,
			'publish_down'  	  => $date_end,
			'offerStartDate' 	  => $date_start,
			'performerName'		  => Helper::getSiteName()
		];

		if (!empty($custom_fields))
		{
			$this->attachCustomFields($custom_fields, $payload);
		}

		// If Location exists, apply it as a default value
		if ($cf_value = $this->customFieldExistsByName('jev_loc_id', $custom_fields))
		{
			$payload['locationName'] = $cf_value['value'];
		}

		// If Standard Image 1 exists, use it as the default image
		if ($cf_value = $this->customFieldExistsByName('jev_image0', $custom_fields))
		{
			$payload['image'] = $cf_value['value'];
		}

		return $payload;
	}

	/**
	 * Returns all custom fields + extra fields.
	 * 
	 * @return  array
	 */
	private function getAllCustomFields($id = null)
	{
		$active_custom_field_name = '';
		$custom_fields = [];
		if (\NRFramework\Extension::isInstalled('jevcustomfields', 'plugin', 'jevents') && \NRFramework\Extension::pluginIsEnabled('jevcustomfields', 'jevents'))
		{
			$active_custom_field_name = $this->getActiveCustomField();
			$custom_fields = $this->getCustomFields($id, $active_custom_field_name);
		}

		// Load the extra fields
		$extra_fields = $this->getExtraCustomFields($id);
		return array_merge($custom_fields, $extra_fields);
	}

	/**
	 * Append Custom Fields to payload
	 *
	 * @param	array	$fields
	 * @param	array	$payload
	 * @param   string 	$prefix
	 *
	 * @return	void
	 */
	private function attachCustomFields($fields, &$payload, $prefix = 'cf.')
	{
		if (!is_array($fields) || count($fields) == 0)
		{
			return;
		}

		foreach ($fields as $key => $field)
		{
			$field_path = $prefix . strtolower($field['name']);
			$value = $field['value'];

			if ($field['rawvalue'] && $field['value'] != $field['rawvalue'])
			{
				$value = $field['rawvalue'];
			}

			$payload[$field_path] = is_array($value) ? implode(', ', $value) : $value;
		}
	}

	/**
	 * Grabs all custom fields from the active custom field name
	 *
	 * @param   int     $ev_id
	 * @param   string  $name
	 *
	 * @return  array
	 */
	private function getCustomFields($ev_id = null, $name = '')
	{
		if (empty($name))
		{
			return [];
		}
		
		$hash = md5($this->_name . 'cf');

		if (Cache::has($hash))
		{
			return Cache::get($hash);
		}

        $fileBase = JPATH_PLUGINS . "/jevents/jevcustomfields/customfields/templates/";
		$xmlFile  = $fileBase . $name;

        if (!is_file($xmlFile))
        {
            return;
        }

        if (!$parsed_custom_fields = simplexml_load_file($xmlFile))
        {
            return;
        }

		$ds = DIRECTORY_SEPARATOR;

		$custom_fields = [];

        foreach ($parsed_custom_fields[0]->fields->fieldset as $field)
        {
			if (count((array)$field->field))
			{
				foreach ($field->field as $attributes)
				{
					$attrs = $attributes->attributes();

					$options = isset($attributes->option) ? $attributes->option : [];

					$type = (string) $attrs->type;
					$name = (string) $attrs->name;
					$label = (string) $attrs->label;
					$value = $this->getCustomFieldValue($ev_id, $name);

					if (!count($options) && empty($value) && $ev_id)
					{
						continue;
					}

					// If there is no custom field value (this happens if you set a JEvents custom field and
					// don't go to the Event to save it and update the custom field data)
					// We then get the default value for this custom field
					if (empty($value))
					{
						$value = isset($attrs->default) ? $attrs->default : '';
					}

					// If its an uploaded file, set the value as a URL that points to the file
					if ($type === 'jevcffile')
					{
						list($uploaded_filename, $initial_filename) = explode('|', $value) + ['', ''];
						$value = implode($ds, [Uri::root(), 'images', 'jevents', $uploaded_filename]);
					}
					// If its an uploaded image, set the value as a URL that points to the image itself
					else if ($type === 'jevcfimage')
					{
						$value = implode($ds, [Uri::root(), 'images', 'jevents', $value]);
					}

					// Get the values for the radio, checkboxes, select, etc...
					if (count($options))
					{
						$value = str_replace('-1,', '', $value);
						$values = explode(',', $value);
						$temp_values = [];
						foreach ($values as $val)
						{
							$val = (int) $val;
							$counter = 0;
							foreach ($options as $opt)
							{
								$atts = $opt->attributes();

								if ($val == $atts->value[0])
								{
									$temp_values[] = Text::_($options[$counter]);
									break;
								}
								$counter++;
							}
						}
						$value = implode(', ', $temp_values);
					}

					$custom_fields[] = [
						'name' => $name,
						'label' => $label,
						'value' => $value,
						'rawvalue' => $value
					];
				}
			}
		}
		return Cache::set($hash, $custom_fields);
	}

	/**
	 * Get the images names from the plugin
	 *
	 * @return  array
	 */
	private function getImagesNames()
	{
		$plugin  = PluginHelper::getPlugin('jevents', 'jevfiles');
		$params = new Registry($plugin->params);
		$total_images = $params->get('imnum', 0);

		$images = [];

		for ($i = 1; $i <= $total_images; $i++)
		{
			$images[] = Text::_('JEV_STANDARD_IMAGE_' . $i);
		}

		return $images;
	}

	/**
	 * Get the images values
	 *
	 * @param   int    $ev_id
	 *
	 * @return  array
	 */
	private function getImagesValues($ev_id)
	{
		$db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select('rawdata')
            ->from($db->quoteName('#__jevents_vevent'))
            ->where($db->quoteName('ev_id') . '= ' . $db->q($ev_id));

		$db->setQuery($query);

		if (!$result = $db->loadResult())
		{
			return;
		}
		
		$result = unserialize($result);

		$result = array_filter($result, function ($key) {
			return strpos($key, 'custom_upload_image') === 0;
		}, ARRAY_FILTER_USE_KEY);

		$result = array_values($result);
		$result = array_filter($result, function ($input) {return $input & 1;}, ARRAY_FILTER_USE_KEY);
		$result = array_values($result);

		$index = 0;
		foreach ($result as $img)
		{
			$result[$index] = Uri::root() . 'images/jevents/' . $img;

			$index++;
		}

		return $result;
	}

	/**
	 * Grab Location, Contact and Extra Info fields
	 *
	 * @param   int    $ev_id
	 *
	 * @return  array
	 */
	private function getExtraCustomFields($ev_id = null)
	{
		$extra_fields = [];

		$extra_fields_data = [];
		// If we are in Event view, fetch the values of the images
		// and the extra fields values
		if (!empty($ev_id))
		{
			$extra_fields_data = $this->getExtraCustomFieldsData($ev_id);
		}

		// Add Locations
		$extra_fields[] = [
			'name' => 'jev_loc_id',
			'label' => Text::_('JEV_EVENT_ADRESSE'),
			'value' => isset($extra_fields_data[0]->location) ? $extra_fields_data[0]->location : '',
			'rawvalue' => isset($extra_fields_data[0]->location) ? $extra_fields_data[0]->location : ''
		];

		// Add Contact
		$extra_fields[] = [
			'name' => 'jev_contact_details',
			'label' => Text::_('JEV_EVENT_CONTACT'),
			'value' => isset($extra_fields_data[0]->contact) ? $extra_fields_data[0]->contact : '',
			'rawvalue' => isset($extra_fields_data[0]->contact) ? $extra_fields_data[0]->contact : ''
		];

		// Add Extra Info
		$extra_fields[] = [
			'name' => 'jev_extra_info',
			'label' => Text::_('JEV_EVENT_EXTRA'),
			'value' => isset($extra_fields_data[0]->extra_info) ? $extra_fields_data[0]->extra_info : '',
			'rawvalue' => isset($extra_fields_data[0]->extra_info) ? $extra_fields_data[0]->extra_info : ''
		];

		// Images
		if (\NRFramework\Extension::isInstalled('jevfiles', 'plugin', 'jevents') && \NRFramework\Extension::pluginIsEnabled('jevfiles', 'jevents'))
		{
			$images_values = !empty($ev_id) ? $this->getImagesValues($ev_id) : [];
			$images_names = $this->getImagesNames();
			$id = 0;
			foreach ($images_names as $img)
			{
				$extra_fields[] = [
					'name' => 'jev_image' . $id,
					'label' => $img,
					'value' => isset($images_values[$id]) ? $images_values[$id] : '',
					'rawvalue' => isset($images_values[$id]) ? $images_values[$id] : ''
				];

				$id++;
			}
		}

		$custom_fields = [];

		// Attach them to custom fields
		foreach ($extra_fields as $field)
		{
			$custom_fields[] = [
				'name' => $field['name'],
				'label' => $field['label'],
				'value' => $field['value'],
				'rawvalue' => $field['value']
			];
		}

		return $custom_fields;
	}

	/**
	 * Get extra custom fields data (locations, contact and extra info)
	 *
	 * @param   int    $ev_id
	 *
	 * @return  array
	 */
	private function getExtraCustomFieldsData($ev_id)
	{
		$db = Factory::getDbo();

		$select = 'd.location as location';
		$join = '';

		if (\NRFramework\Extension::isInstalled('jevlocations', 'plugin', 'jevents') && \NRFramework\Extension::pluginIsEnabled('jevlocations', 'jevents'))
		{
			$select = "CONCAT(l.country, ' ', l.state, ' ', l.city, ' ', l.postcode, ' ', l.street) as location";
			$join = "LEFT JOIN " . $db->quoteName('#__jev_locations') . " as l ON d.location = l.loc_id";
		}

		$query = "SELECT
					d.contact as contact,
					d.extra_info as extra_info,
					" . $select . "
				  FROM
				  	" . $db->quoteName('#__jevents_vevdetail') . " as d
				  " . $join . "
				  WHERE d.evdet_id = " . $db->q($ev_id);

		$db->setQuery($query);

		$result = $db->loadObjectList();

		return $result;

	}

	/**
	 * Get custom feld value
	 *
	 * @param   int     $ev_id
	 * @param   string  $field_name
	 *
	 * @return  string
	 */
	private function getCustomFieldValue($ev_id, $field_name)
	{
		if (empty($ev_id))
		{
			return '';
		}

		$db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select('value')
            ->from($db->quoteName('#__jev_customfields'))
            ->where($db->quoteName('evdet_id') . '= ' . $db->q($ev_id))
            ->where($db->quoteName('name') . '= ' . $db->q($field_name));

		$db->setQuery($query);

		$result = $db->loadResult();

		return $result;
	}

	/**
	 * Get the active custom field file name
	 *
	 * @return  string
	 */
	private function getActiveCustomField()
	{
		$db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select('params')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . "= 'jevcustomfields'");

		$db->setQuery($query);

		$result = $db->loadResult();
		$result = json_decode($result);

		return isset($result->template) ? $result->template : '';
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

		// Load JEvents Language Files
		\NRFramework\Functions::loadLanguage('com_jevents', JPATH_SITE);
		Factory::getLanguage()->load('plg_jevents_jevfiles', JPATH_ADMINISTRATOR);

		$custom_fields = $this->getAllCustomFields();

		if (count($custom_fields) > 0)
		{
			$custom_fields_options = [];

			foreach ($custom_fields as $key => $field)
			{
				$custom_fields_options[$field['name']] = $field['label'];
			}

			MappingOptions::add($options, $custom_fields_options);
		}

		$remove_options = [
			'alias',
			'introtext',
			'metakey',
			'metadesc',
			'offerprice',
			'offercurrency',
			'offerinventorylevel',
			'locationaddress',
			'addressCountry',
			'addressLocality',
			'addressRegion',
			'postalCode',
			'organizerType',
			'organizerName',
			'organizerURL',
			'performerType',
			'performerName',
			'performerURL'
		];

		// If Location setting does not exist, remove it from the mapping options
		if (!$this->customFieldExistsByName('jev_loc_id', $custom_fields))
		{
			$remove_options[] = 'locationname';
		}

		// If Standard Image does not exist, remove it from the mapping options
		if (!$this->customFieldExistsByName('jev_image0', $custom_fields))
		{
			$remove_options[] = 'image';
		}

		// Remove unsupported mapping options
		foreach ($remove_options as $key => $option)
		{
			unset($options['GSD_INTEGRATION']['gsd.item.' . $option]);
		}
	}

	/**
	 * Checks whether a custom field exists by its name.
	 * 
	 * @param   string   $name
	 * @param   array    $custom_fields
	 * 
	 * @return  mixed
	 */
	private function customFieldExistsByName($name = '', $custom_fields = [])
	{
		if (empty($name) || empty($custom_fields))
		{
			return false;
		}

		foreach ($custom_fields as $key => $cf)
		{
			if ($name !== $cf['name'])
			{
				continue;
			}

			return $cf;
		}

		return false;
	}

	/**
	 * Listening to the onAfterRender Joomla event
	 *
	 * @return void
	 */
	public function onAfterRender()
	{
		if ($this->app->isClient('administrator') ||
            !$this->passContext() ||
            $this->getView() != 'event' ||
            !$this->params->get('removemicrodata', true))
		{
			return;
		}

        // Remove JEvents Event microdata
        \GSD\SchemaCleaner::remove('Event');
	}
}