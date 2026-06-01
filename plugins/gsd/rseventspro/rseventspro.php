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

use GSD\MappingOptions;
use GSD\Helper;
use NRFramework\Functions;

/**
 *  RSEvents!Pro Google Structured Data Plugin
 *  
 */
class plgGSDRSEventsPro extends GSD\PluginBaseEvent
{
	/**
	 * RSEvents!Pro Event ID
	 */
	protected $id;

	/**
     *  Validate context to decide whether the plugin should run or not.
     *
     *  @return   bool
     */
    protected function passContext()
    {
		$layout = $this->app->input->get('layout');
		$category = $this->app->input->get('category');

        return 	Helper::getComponentAlias() == $this->_name &&
				$layout == 'show' &&
				!$category;
    }

    /**
	 *  Get event's data
	 *
	 *  @return  array
	 */
	public function viewRseventspro()
	{
		// Make sure we have a valid ID
		if (!$this->id = $this->getThingID())
		{
			return;
		}

		require_once JPATH_SITE.'/components/com_rseventspro/helpers/rseventspro.php';
		$eventHelper = new \RSEvent($this->id);
		$event = $eventHelper->getEvent();
		
		
		$date_start = Functions::dateToUTC($event->start);
		$date_end   = Functions::dateToUTC($event->end);
		$created 	= Functions::dateToUTC($event->created);
		
		$owner 		 = $eventHelper->getOwner();
		$tickets 	 = $eventHelper->getTickets();
		$speakers	 = $this->getEventSpeakers();
		$currency 	 = rseventsproHelper::getConfig('payment_currency');
		$event_files = $this->getEventFiles();

		$rseventspro_model = Joomla\CMS\MVC\Model\BaseModel::getInstance('Rseventspro','RseventsproModel');
		$location  	= $rseventspro_model->getLocation();

        // Array data
		$data = [
			'id'           		=> $event->id,
			'headline'     		=> $event->name,
			'image'				=> Helper::getSiteURL() . '/components/com_rseventspro/assets/images/events/' . $event->icon,
			'imagetext'			=> Helper::getFirstImageFromString($event->description),
			'description'  		=> empty($event->small_description) ? $event->description : $event->small_description,
            'introtext'    		=> $event->small_description,
			'fulltext'     		=> $event->description,
			'created'      		=> $created,
			'publish_up'		=> $date_start,
			'publish_down'		=> $date_end,
			'startDate'	   		=> $date_start,
			'endDate'	   		=> $date_end,
			'locationName' 		=> $location->name,
			'locationAddress' 	=> $location->address,
			'offerCurrency'   	=> $currency,
			'created_by'	  	=> $owner,
			'organizerName'	  	=> $owner,
			'organizerURL'	  	=> $event->URL,
			'metakey' 		  	=> $event->metakeywords,
			'metadesc' 		  	=> $event->metadescription,
		];

		if(!empty($tickets))
		{
			$data['offerPrice']			  = Helper::formatPrice($tickets[0]->price);
			$data['offerInventoryLevel']  = $tickets[0]->seats;
		}

		if(!empty($speakers))
		{
			$data['performerName'] = $speakers[0]->name;
			if(property_exists($speakers[0], 'url'))
			{
				$data['performerURL'] = $speakers[0]->url;
			}
		}

		if (!empty($event_files))
		{
			$data['rsevents_file'] = $event_files[0]->name;
		}

        return $data;
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
			'alias',
			'modified',
			'ratingValue',
			'ratingCount',
			'performerType',
			'organizerType',
			'addressCountry',
			'addressRegion'
		];

		// Remove unsupported mapping options
		foreach ($remove_options as $option)
		{
			unset($options['GSD_INTEGRATION']['gsd.item.' . $option]);
		}
		
		// Add an option for the Event's first associated file
		$file_option = [
			'rsevents_file' => Joomla\CMS\Language\Text::_('PLG_GSD_RSEVENTSPRO_FILE_OPTION'),
		];
		MappingOptions::add($options, $file_option, 'GSD_INTEGRATION', 'gsd.item.');
	}

	/**
	 * Helper method to get RSEvents speakers
	 *
	 * @return array|null  List of speakers
	 *
	 */
	public function getEventSpeakers() {
		$db =  Joomla\CMS\Factory::getDbo();
		$query = $db->getQuery(true);
		
		
		$query->clear()
			->select($db->qn('id'))
			->from($db->qn('#__rseventspro_taxonomy'))
			->where($db->qn('type').' = '.$db->q('speaker'))
			->where($db->qn('ide').' = '.$this->id);
		
		$db->setQuery($query);
		$speakerIds = $db->loadColumn();
		
		if (empty($speakerIds))
		{
			return null;
		}

		$query->clear()
			->select($db->qn('id'))
			->select($db->qn('name'))
			->select($db->qn('url'))
			->from($db->qn('#__rseventspro_speakers'))
			->where($db->qn('published').' = 1')
			->where($db->quoteName('id') . ' IN (' . implode(',', $speakerIds) . ')')
			->order($db->qn('name').' ASC');
		$db->setQuery($query);
		return $db->loadObjectList();
	}

	/**
	 * Method to get RSEvent files
	 *
	 * @return array  List of files
	 *
	 */
	public function getEventFiles() {
		$db =  Joomla\CMS\Factory::getDbo();
		$query = $db->getQuery(true);
		
		$query->clear()
			->select($db->qn('id'))
			->select($db->qn('name'))
			->select($db->qn('location'))
			->from($db->qn('#__rseventspro_files'))
			->where($db->qn('ide').' = '.$this->id);
		
		$db->setQuery($query);
		return $db->loadObjectList();
	}
}