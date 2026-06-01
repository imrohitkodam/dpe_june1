<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

class plgEventbookingZoomapp extends CMSPlugin implements SubscriberInterface
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
			'onEditEvent'           => 'onEditEvent',
			'onAfterSaveEvent'      => 'onAfterSaveEvent',
			'onAfterPaymentSuccess' => 'onAfterPaymentSuccess',
		];
	}

	/**
	 * Render settings form
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onEditEvent(Event $eventObj): void
	{
		/* @var EventbookingTableEvent $row */
		[$row] = array_values($eventObj->getArguments());

		if (!$this->canRun($row))
		{
			return;
		}

		ob_start();
		$this->drawSettingForm($row);

		$result = [
			'title' => Text::_('PLG_EVENTBOOKING_ZOOM_SETTINGS'),
			'form'  => ob_get_clean(),
		];

		$this->addResult($eventObj, $result);
	}

	/**
	 * Store setting into database
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onAfterSaveEvent(Event $eventObj): void
	{
		/**
		 * @var EventbookingTableEvent $row
		 * @var array                  $data
		 * @var bool                   $isNew
		 */
		[$row, $data, $isNew] = array_values($eventObj->getArguments());

		if (!$this->canRun($row))
		{
			return;
		}

		$params = new Registry($row->params);
		$params->set('zoom_meeting_id', $data['zoom_meeting_id']);
		$params->set('zoom_webinar_id', $data['zoom_webinar_id']);

		$row->params = $params->toString();

		$row->store();
	}

	/**
	 * Add registrants to selected Joomla groups when payment for registration completed
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onAfterPaymentSuccess(Event $eventObj): void
	{
		/* @var EventbookingTableRegistrant $row */
		[$row] = array_values($eventObj->getArguments());

		$params = new Registry($row->params);

		if ($params->get('zoom_integration_processed'))
		{
			return;
		}

		$config = EventbookingHelper::getConfig();

		if ($config->multiple_booking)
		{
			// Get list of Event IDs from shopping cart
			$db    = $this->db;
			$query = $db->getQuery(true)
				->select('event_id')
				->from('#__eb_registrants')
				->where("(id = $row->id OR cart_id = $row->id)")
				->order('id');
			$db->setQuery($query);
			$eventIds = $db->loadColumn();
		}
		else
		{
			$eventIds = [$row->event_id];
		}

		foreach ($eventIds as $eventId)
		{
			$event = EventbookingHelperDatabase::getEvent($eventId);

			$eventParams = new Registry($event->params);

			if ($meetingId = $eventParams->get('zoom_meeting_id'))
			{
				$meetingId = $this->normalizeId($meetingId);
				$this->addRegistrantToMeeting($row, $meetingId);
			}

			if ($webinarId = $eventParams->get('zoom_webinar_id'))
			{
				$webinarId = $this->normalizeId($webinarId);
				$this->addRegistrantToWebinar($row, $webinarId);
			}
		}
	}

	/**
	 * Add registrant to a meeting
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   string                       $meetingId
	 *
	 * @return void
	 */
	private function addRegistrantToMeeting($row, $meetingId): void
	{
		$data = [
			'email'      => $row->email,
			'first_name' => $row->first_name,
			'last_name'  => $row->last_name,
			'address'    => $row->address,
			'city'       => $row->city,
			'country'    => EventbookingHelper::getCountryCode($row->country),
			'zip'        => $row->zip,
			'state'      => $row->state,
			'phone'      => $row->phone,
			'org'        => $row->organization,
			'comments'   => $row->comment,
		];

		$url = sprintf('https://api.zoom.us/v2/meetings/%s/registrants', $meetingId);
		$this->sendRequestAndStoreResponseData($row, $url, $data);
	}

	/**
	 * Add registrant to a meeting
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   string                       $webibarId
	 *
	 * @return void
	 */
	private function addRegistrantToWebinar($row, $webibarId): void
	{
		$data = [
			'email'      => $row->email,
			'first_name' => $row->first_name,
			'last_name'  => $row->last_name,
			'address'    => $row->address,
			'city'       => $row->city,
			'country'    => EventbookingHelper::getCountryCode($row->country),
			'zip'        => $row->zip,
			'state'      => $row->state,
			'phone'      => $row->phone,
			'org'        => $row->organization,
			'comments'   => $row->comment,
		];

		$url = sprintf('https://api.zoom.us/v2/webinars/%s/registrants', $webibarId);

		$this->sendRequestAndStoreResponseData($row, $url, $data);
	}

	/**
	 * @param   EventbookingTableRegistrant  $row
	 * @param   string                       $url
	 * @param   array                        $data
	 *
	 * @return void
	 */
	private function sendRequestAndStoreResponseData($row, $url, $data): void
	{
		try
		{
			$accessToken = $this->getAccessToken();

			if (!$accessToken)
			{
				EventbookingHelper::logData(__DIR__ . '/zoomapp_response.txt', [], 'No access token received');

				return;
			}

			$http     = HttpFactory::getHttp();
			$response = $http->post(
				$url,
				json_encode($data),
				['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $accessToken]
			);

			if ($response->code == 201)
			{
				$responseData = json_decode($response->body, true);
				$params       = new Registry($row->params);

				foreach ($responseData as $key => $value)
				{
					$params->set('zoom_' . $key, $value);
				}

				$row->params = $params->toString();
				$row->store();
			}
			else
			{
				EventbookingHelper::logData(__DIR__ . '/zoomapp_response.txt', ['code' => $response->code, 'body' => $response->body]);
			}
		}
		catch (Exception $e)
		{
			// Do nothing for now

			EventbookingHelper::logData(__DIR__ . '/zoomapp_response.txt', [], $e->getMessage());
		}
	}

	/**
	 * Display form allows users to change setting for this subscription plan
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return void
	 */
	private function drawSettingForm($row): void
	{
		$params = new Registry($row->params);

		require PluginHelper::getLayoutPath($this->_type, $this->_name, 'form');
	}

	/**
	 * Method to check to see whether the plugin should run
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return bool
	 */
	private function canRun($row): bool
	{
		if ($this->app->isClient('site') && !$this->params->get('show_on_frontend'))
		{
			return false;
		}

		return true;
	}


	/**
	 * Method uses to normalize Meeting ID and Webinar ID
	 *
	 * @param   string  $id
	 *
	 * @return string
	 */
	private function normalizeId($id): string
	{
		return str_replace(' ', '', $id);
	}

	/**
	 * Get access token
	 *
	 * @return string
	 *
	 * @throws RuntimeException
	 */
	private function getAccessToken()
	{
		$vendor = 'zoom';

		// First, try to get the token from database. To be safe, the token is only considered valid if it is valid to use in the next 2 minutes
		$expireAtToCheck = time() + 7200;

		$rowToken = EventbookingHelperOauth::getAccessToken($vendor);

		if ($rowToken && $rowToken->expire_at > $expireAtToCheck)
		{
			return $rowToken->token;
		}

		// There is no valid token from database, call Zoom API to get the access token

		// Initialize expire the time which the token is valid
		$expireAt = time();

		$http    = HttpFactory::getHttp();
		$headers = [
			'Authorization' => 'Basic ' . base64_encode($this->params->get('client_id', '') . ':' . $this->params->get('client_secret', '')),
			'Content-Type'  => 'application/x-www-form-urlencoded',
			'Host'          => 'zoom.us',
		];

		$response = $http->post(
			'https://zoom.us/oauth/token',
			['grant_type' => 'account_credentials', 'account_id' => $this->params->get('account_id')],
			$headers
		);

		$result = json_decode($response->body);

		if ($response->code != 200)
		{
			EventbookingHelper::logData(__DIR__ . '/zoomapp_response.txt', [], 'Getting Access Token Error:' . $response->body);

			return '';
		}

		$token = $result->access_token;

		$expireAt += (int) $result->expires_in;

		// Store the token into database
		EventbookingHelperOauth::storeToken($vendor, $token, $expireAt);

		return $token;
	}
}
