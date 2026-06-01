<?php
/**
 * @version    SVN: <svn_id>
 * @package    JTicketing
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
defined('_JEXEC') or die('Restricted access');
jimport('joomla.plugin.plugin');
use Joomla\CMS\Factory;
$lang = Factory::getLanguage();
$lang->load('plg_community_addfields', JPATH_ADMINISTRATOR);
$lang->load('com_jticketing', JPATH_SITE);
use Joomla\CMS\Language\Text;

if (file_exists(JPATH_ROOT . '/media/techjoomla_strapper/tjstrapper.php'))
{
	require_once JPATH_ROOT . '/media/techjoomla_strapper/tjstrapper.php';
	TjStrapper::loadTjAssets('com_jticketing');
}

/**
 * Model for buy for creating order and other
 *
 * @package     JTicketing
 * @subpackage  component
 * @since       1.0
 */
class PlgCommunityaddFields extends CApplications
{
	/**
	 * function to validate Integration
	 *
	 * @return  boolean  true or false
	 *
	 * @since   1.0
	 */
	public function validateIntegration()
	{
		$com_params  = JComponentHelper::getParams('com_jticketing');
		$integration = $com_params->get('integration');

		if ($integration != 1)
		{
			return false;
		}

		return true;
	}

	/**
	 * This functions is called when jomsocial event is updated
	 *
	 * @param   string  $subject  subject
	 * @param   string  $config   config
	 *
	 * @since   1.0
	 */
	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadJTclasses();
	}

	/**
	 * This is called when jomsocial event creation form showed
	 *
	 * @param   STRING  $form_name  form_name
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onFormDisplay($form_name)
	{
		$app = JFactory::getApplication();
		$site = $app->isSite();
		$this->loadJTclasses();

		if ($form_name == 'createEvent')
		{
			if ($site)
			{
				$document   = JFactory::getDocument();
				$document->addStyleSheet(JUri::root(true) . '/media/com_jticketing/css/jticketing.css');
				$document->addStyleSheet(JUri::root(true) . '/plugins/community/addfields/css/addfields.css');
				JHtml::script(Juri::root() . 'media/com_jticketing/integrations/js/integrations.js');
				$seatCountMsg = Text::_('COM_JTICKETING_JOMSOCIAL_EVENT_TICKET_TYPES_SAVE_ERROR');
				$document->addScriptDeclaration('var seatCountMsg= "' . $seatCountMsg . '";');
				$eventDateMsg = Text::_('COM_JTICKETING_JOMSOCIAL_DATE_VALIDATION');
				$document->addScriptDeclaration('var eventDateMsg= "' . $eventDateMsg . '";');
			}

			if (!$this->validateIntegration())
			{
				return false;
			}

			$html = $this->getCustomFields();
			$obj = new CFormElement;
			$obj->position = 'after';
			$obj->html = '';
			$elements = array();

			foreach ($html as $singleHtml)
			{
				$obj->html .= $singleHtml;
			}

			$elements[] = $obj;

			return $elements;
		}
	}

	/**
	 * This is called when jomsocial event is stored
	 *
	 * @param   object  $event  event object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onEventCreate($event)
	{
		if (!$this->validateIntegration())
		{
			return false;
		}

		$this->loadJTclasses();
		$jteventHelper = new jteventHelper;

		$jteventHelper->saveEvent($event->id, '1');
	}

	/**
	 * This is called when jomsocial event is updated
	 *
	 * @param   object  $event  event object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onEventUpdate($event)
	{
		if (!$this->validateIntegration())
		{
			return false;
		}

		$this->loadJTclasses();

		$jteventHelper = new jteventHelper;

		$jteventHelper->saveEvent($event->id, '1');
	}

	/**
	 * This function updates jomsocial table
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function loadJTclasses()
	{
		require_once JPATH_SITE . '/components/com_jticketing/includes/jticketing.php';
		require_once JPATH_SITE . '/components/com_tjvendors/includes/tjvendors.php';

		// Load all required helpers.
		$jticketingmainhelperPath = JPATH_ROOT . '/components/com_jticketing/helpers/main.php';

		if (!class_exists('jticketingmainhelper'))
		{
			JLoader::register('jticketingmainhelper', $jticketingmainhelperPath);
			JLoader::load('jticketingmainhelper');
		}

		$jticketingfrontendhelper = JPATH_ROOT . '/components/com_jticketing/helpers/frontendhelper.php';

		if (!class_exists('jticketingfrontendhelper'))
		{
			JLoader::register('jticketingfrontendhelper', $jticketingfrontendhelper);
			JLoader::load('jticketingfrontendhelper');
		}

		$jteventHelperPath = JPATH_ROOT . '/components/com_jticketing/helpers/event.php';

		if (!class_exists('jteventHelper'))
		{
			JLoader::register('jteventHelper', $jteventHelperPath);
			JLoader::load('jteventHelper');
		}
	}

	/**
	 * Gets the custom fields
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function getCustomFields()
	{
		$input                    = Factory::getApplication()->input->get;
		$event_id                 = $input->get('eventid', '', 'GET');
		$lang                     = Factory::getLanguage();
		$extension                = 'com_jticketing';
		$base_dir                 = JPATH_ADMINISTRATOR;
		$lang->load($extension, $base_dir);
		$this->loadJTclasses();
		$jticketingfrontendhelper = new jticketingfrontendhelper;
		$attendeeGlobalFields     = array();
		$attendeeCoreFields       = JT::model('attendeecorefields', array('ignore_request' => true));
		$attendeeCoreFields->setState('filter.state', 1);
		$attendeeGlobalFields     = $attendeeCoreFields->getItems();
		$com_params               = JT::config();
		$attendeeCheckoutConfig   = $com_params->get('collect_attendee_info_checkout');
		$accessLevel              = $com_params->get('show_access_level');
		$document                 = Factory::getDocument();
		$document->addStyleSheet(JUri::root(true) . '/media/com_jticketing/css/jticketing.css');

		if (!$accessLevel)
		{
		?>
		<style>
		.subform-repeatable-wrapper .form-group:last-child{
		display: none;
		}
		#tickettypes-lbl ,#attendeefields-lbl{
		display: none;
		}
		</style>
		<?php
		}

		$userId     = Factory::getUser()->id;
		JLoader::register('TJVendors', JPATH_SITE . "/components/com_tjvendors/includes/tjvendors.php");
		$vendor     = TJVendors::vendor()->loadByUserId($userId, JT::getIntegration());
		$vendor_id  = $vendor->getId();
		$emailCheck = $vendor->getPaymentConfig() ? true : false;
		$params     = JT::config();
		$handle_transactions = $params->get('handle_transactions');
		$adaptivePayment     = $params->get('gateways');
		$customFields        = array();

		if ($emailCheck == "true" && ($handle_transactions == 1 || in_array('adaptive_paypal', $adaptivePayment)))
		{
			$link = 'index.php?option=com_tjvendors&view=vendor&layout=profile&client=com_jticketing';

			$warningMessage = '<div id="tjcover"><div class="alert alert-warning">' . Text::_('COM_JTICKETING_PAYMENT_DETAILS_ERROR_MSG1') . '
				<a href="' . JRoute::_($link . '&vendor_id=' . $vendor_id, false) . '" target="_blank">
			' . Text::_('COM_JTICKETING_VENDOR_FORM_LINK') . '</a>' . Text::_('COM_JTICKETING_PAYMENT_DETAILS_ERROR_MSG2') . '</div></div>';
			$customFields['warning_message'] = $warningMessage;
		}

		$customFields['ticket_title'] = '<div id="customFields"><legend>' . Text::_('COM_JTICKETING_JSEVENT_TICKET_TYPES') . '</legend>';
		$customTicketFields = JT::event($event_id,'com_community')->getCustomFieldTypes('ticketFields');
		$customFields['ticketFields'] = '<div class="jticketing-wrapper tjBs3">
			<div class="jticketing_params_container">
				<div>' . $customTicketFields . '</div>
			</div>
		</div></div>';

		if ($attendeeCheckoutConfig == 1)
		{
			$customAttendeeFields = JT::event($event_id,'com_community')->getCustomFieldTypes('attendeeFields');
			$customFields['attendee_title'] = '<legend>' . Text::_('COM_JTICKETING_JSEVENT_ATTENDEE_FIELDS') . '</legend>';
			$customFields['attendeeFields'] = '<div id="customFields"><div class="jticketing-wrapper tjBs3">
		<div class="jticketing_params_container">
					<div>' . $customAttendeeFields . '</div>
				</div>
			</div></div>';
		}

		return $customFields;
	}
}
