<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class plgEventBookingMap extends CMSPlugin implements SubscriberInterface
{
	use RADEventResult;

	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    \Joomla\Database\DatabaseDriver
	 */
	protected $db;

	/**
	 * @return array
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onEventDisplay' => 'onEventDisplay',
		];
	}

	/**
	 * Constructor.
	 *
	 * @param $subject
	 * @param $config
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->app->getLanguage()->load('plg_eventbooking_map', JPATH_ADMINISTRATOR);
	}

	/**
	 * Display event location in a map
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onEventDisplay(Event $eventObj): void
	{
		/* @var EventbookingTableEvent $row */
		[$row] = array_values($eventObj->getArguments());

		$db    = $this->db;
		$query = $db->getQuery(true);
		$query->select('a.*')
			->from('#__eb_locations AS a')
			->innerJoin('#__eb_events AS b ON a.id = b.location_id')
			->where('b.id = ' . (int) $row->id);

		if ($fieldSuffix = EventbookingHelper::getFieldSuffix())
		{
			EventbookingHelperDatabase::getMultilingualFields($query, ['a.name', 'a.alias', 'a.description'], $fieldSuffix);
		}

		$db->setQuery($query);
		$location = $db->loadObject();

		$print = $this->app->getInput()->getInt('print', 0);

		if (
			$location === null
			|| empty($location->address)
			|| ($location->lat == 0 && $location->long == 0)
			|| $print
		)
		{
			return;
		}

		ob_start();

		HTMLHelper::_('behavior.core');

		$config = EventbookingHelper::getConfig();

		if ($config->get('map_provider', 'googlemap') == 'googlemap')
		{
			$this->drawMap($location);
		}
		else
		{
			$this->drawOpenStreetMap($location);
		}

		$form = ob_get_clean();

		$result = [
			'title'    => Text::_('PLG_EB_MAP'),
			'form'     => $form,
			'name'     => $this->_name,
			'position' => $this->params->get('output_position', 'after_register_buttons'),
		];

		$this->addResult($eventObj, $result);
	}

	/**
	 * Display event location in a map
	 *
	 * @param   stdClass  $location
	 *
	 * @return void
	 */
	private function drawMap($location): void
	{
		$config           = EventbookingHelper::getConfig();
		$rootUri          = Uri::root(true);
		$zoomLevel        = (int) $config->zoom_level ?: 14;
		$disableZoom      = $this->params->get('disable_zoom', 1) == 1 ? 'false' : 'true';
		$mapHeight        = $this->params->def('map_height', 500);
		$getDirectionLink = 'https://maps.google.com/maps?daddr=' . str_replace(' ', '+', $location->address);
		$getDirectText    = Text::_('EB_GET_DIRECTION');

		$bubbleText = <<<HTML
            <ul class="bubble">
                <li class="location_name"><h4>$location->name</h4></li>
                <li class="address">$location->address</li>
                <li class="address getdirection"><a href="$getDirectionLink" target="_blank">$getDirectText</a></li>
            </ul>
</ul>
HTML;

		$this->app->getDocument()
			->addScript('https://maps.googleapis.com/maps/api/js?key=' . $config->get('map_api_key', '') . '&v=quarterly')
			->addScript($rootUri . '/media/com_eventbooking/js/plg-eventbooking-map-googlemap.min.js')
			->addScriptOptions('mapZoomLevel', $zoomLevel)
			->addScriptOptions('mapLocation', $location)
			->addScriptOptions('scrollwheel', (bool) $disableZoom)
			->addScriptOptions('bubbleText', $bubbleText);
		?>
		<div id="mapform">
			<div id="map_canvas" style="width: 100%; height: <?php
			echo $mapHeight; ?>px"></div>
		</div>
		<?php
	}

	/**
	 * Display location on openstreetmap
	 *
	 * @param   EventbookingTableLocation  $location
	 *
	 * @return void
	 */
	private function drawOpenStreetMap($location): void
	{
		$rootUri   = Uri::root(true);
		$config    = EventbookingHelper::getConfig();
		$zoomLevel = (int) $config->zoom_level ?: 14;
		$mapHeight = $this->params->def('map_height', 500);

		$popupContent           = [];
		$popupContent[]         = '<h4 class="eb-location-name">' . $location->name . '</h4>';
		$popupContent[]         = '<p class="eb-location-address">' . $location->address . '</p>';
		$location->popupContent = implode('', $popupContent);

		$this->app->getDocument()
			->addScript($rootUri . '/media/com_eventbooking/assets/js/leaflet/leaflet.js')
			->addScript($rootUri . '/media/com_eventbooking/js/plg-eventbooking-map-openstreetmap.min.js')
			->addStyleSheet($rootUri . '/media/com_eventbooking/assets/js/leaflet/leaflet.css')
			->addScriptOptions('mapZoomLevel', $zoomLevel)
			->addScriptOptions('mapLocation', $location);
		?>
		<div id="mapform">
			<div id="map_canvas" style="width: 100%; height: <?php
			echo $mapHeight; ?>px"></div>
		</div>
		<?php
	}
}
