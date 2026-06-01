<?php
/**
 * @package	RSTicketsPro!
 * @copyright	(c) 2013 - 2018 RSJoomla!
 * @link		https://www.rsjoomla.com
 * @license	GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

JLoader::register('PrivacyPlugin', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/plugin.php');
JLoader::register('PrivacyRemovalStatus', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/removal/status.php');


/**
 * RSTickets! Pro Privacy Plugin.
 */
class PlgPrivacyRsticketspro extends PrivacyPlugin
{
	const EXTENSION = 'plg_privacy_rsticketspro';

	/**
	 * Can the plugin run?
	 *
	 * @access public
	 *
	 * @return bool
	 */
	public function canRun()
	{
		return file_exists(JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/rsticketspro.php');
	}

    /**
     * Performs validation to determine if the data associated with a remove information request can be processed
     *
     * This event will not allow a super user account to be removed
     *
     * @param   PrivacyTableRequest  $request  The request record being processed
     * @param   JUser                $user     The user account associated with this request if available
     *
     * @return  PrivacyRemovalStatus
     *
     * @since   3.9.0
     */
    public function onPrivacyCanRemoveData(PrivacyTableRequest $request, JUser $user = null)
    {
        $status = new PrivacyRemovalStatus;

        if (!$user)
        {
            return $status;
        }

        if ($user->authorise('core.admin'))
        {
            $status->canRemove = false;
            $status->reason    = JText::_('PLG_PRIVACY_RSTICKETSPRO_ERROR_CANNOT_REMOVE_SUPER_USER');
        }

        return $status;
    }

	/**
	 * Function that retrieves the information for the RSTickets! Pro Component Capabilities
	 * @return array
	 *
	 */
	public function onPrivacyCollectAdminCapabilities()
	{
		if (!$this->canRun())
		{
			return array();
		}

		$capabilities = array(
			JText::_('PLG_PRIVACY_RSTICKETSPRO_CAPABILITIES_GENERAL') => array(
				JText::_('PLG_PRIVACY_RSTICKETSPRO_CAPABILITIES_TICKETS'),
				JText::_('PLG_PRIVACY_RSTICKETSPRO_CAPABILITIES_MESSAGES'),
				JText::_('PLG_PRIVACY_RSTICKETSPRO_CAPABILITIES_RECAPTCHA')
			)
		);

		return $capabilities;
	}
	/**
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  PrivacyExportDomain[]
	 *
	 * @since   3.9.0
	 */
	public function onPrivacyExportRequest(PrivacyTableRequest $request, JUser $user = null)
	{
		if (!$this->canRun())
		{
			return array();
		}

		if (!$user)
		{
			return array();
		}

        require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/rsticketspro.php';

		/** @var JTableUser $userTable */
		$userTable = JUser::getTable();
		$userTable->load($user->id);

		return $this->createUserTickets($userTable);
	}

	/**
	 * Removes the data associated with a remove information request
	 *
	 * This event will pseudoanonymise the user account
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  void
	 *
	 * @since   3.9.0
	 */
	public function onPrivacyRemoveData(PrivacyTableRequest $request, JUser $user = null)
	{
		if (!$this->canRun())
		{
			return;
		}

		// This plugin only processes data for registered user accounts
		if (!$user)
		{
			return;
		}
		
		// Load the language for the RSTicketsPro!
		JFactory::getLanguage()->load('com_rsticketspro', JPATH_ADMINISTRATOR);
        // Anonymise data
        require_once JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/rsticketspro.php';
		RSTicketsProHelper::anonymise($user->id, 0);
	}


    /**
     * Create the domain for the tickets
     *
     * @param   JTableUser  $user  The JTableUser object to process
     *
     * @return  PrivacyExportDomain[]
     *
     * @since   3.9.0
     */
    private function createUserTickets(JTableUser $user)
    {
		$domains = array();

        $domain_tickets 		= $this->createDomain('user_rsticketspro_tickets');
        $domain_files 			= $this->createDomain('user_rsticketspro_tickets_files');
        $domain_history 		= $this->createDomain('user_rsticketspro_tickets_history');
        $domain_messages 		= $this->createDomain('user_rsticketspro_tickets_messages');
		$domain_notes 			= $this->createDomain('user_rsticketspro_tickets_notes');
		$domain_custom_fields 	= $this->createDomain('user_rsticketspro_tickets_custom_fields');

        // Get the database object
        $db 	= &$this->db;
        $query	= $db->getQuery(true);

		$query
			->select($db->qn('t.id'))
			->select($db->qn('d.name')) // dep name
			->select('IF(' . $db->qn('t.staff_id') . ' = '.$db->q(0).', ' . $db->q(JText::_('RST_UNASSIGNED')) . ', (SELECT '.$db->qn('#__users.name').' FROM '.$db->qn('#__users').' WHERE '.$db->qn('#__users.id').' = '.$db->qn('t.staff_id').')) AS ' . $db->qn('staff_member'))
			->select($db->qn('t.code'))
			->select($db->qn('t.subject'))
			->select($db->qn('s.name')) // status
			->select($db->qn('p.name')) // priority name
			->select($db->qn('t.date'))
			->select($db->qn('t.last_reply'))
			->select($db->qn('t.closed'))
			->select($db->qn('t.flagged'))
			->select($db->qn('t.agent'))
			->select($db->qn('t.referer'))
			->select($db->qn('t.ip'))
			->select($db->qn('t.logged'))
			->select($db->qn('t.feedback'))
			->select($db->qn('t.followup_sent'))
			->select($db->qn('t.has_files'))
			->select($db->qn('t.time_spent'))
			->from($db->qn('#__rsticketspro_tickets', 't'))
			->where($db->qn('t.customer_id') . ' = ' . $db->q($user->id))
			->join('left', $db->qn('#__rsticketspro_departments', 'd').' ON '.$db->qn('t.department_id').' = '.$db->qn('d.id'))
			->join('left',$db->qn('#__rsticketspro_statuses', 's').' ON '.$db->qn('t.status_id').' = '.$db->qn('s.id'))
			->join('left',$db->qn('#__rsticketspro_priorities', 'p').' ON '.$db->qn('t.priority_id').' = '.$db->qn('p.id'));

        if ($items = $db->setQuery($query)->loadAssocList())
        {
            foreach ($items as $item)
            {
				// adding the tickets
				$domain_tickets->addItem($this->createItemFromArray($item, $item['id']));
            }
        }

        // ticket custom fields
        $query	= $db->getQuery(true);
        $query
            ->select($db->qn('c.ticket_id'))
            ->select($db->qn('f.name'))
            ->select($db->qn('c.value'))
            ->from($db->qn('#__rsticketspro_custom_fields_values', 'c'))
            ->join('left', $db->qn('#__rsticketspro_custom_fields', 'f').' ON '.$db->qn('c.custom_field_id').' = '.$db->qn('f.id'))
            ->join('left', $db->qn('#__rsticketspro_tickets', 't').' ON '.$db->qn('c.ticket_id').' = '.$db->qn('t.id'))
            ->where($db->qn('t.customer_id') . ' = ' .  $db->q($user->id));

        if ($cfields = $db->setQuery($query)->loadAssocList()) {
            foreach ($cfields as $cfield) {
                $domain_custom_fields->addItem($this->createItemFromArray($cfield));
            }
        }

        // handle the files
        $query	= $db->getQuery(true);
        $query
            ->select($db->qn('f.ticket_id'))
            ->select($db->qn('filename'))
            ->from($db->qn('#__rsticketspro_ticket_files', 'f'))
            ->leftJoin( $db->qn('#__rsticketspro_ticket_messages', 'm') . ' ON (' . $db->qn('f.ticket_message_id') .' = ' . $db->qn('m.id') . ')')
            ->where($db->qn('m.user_id') . ' = ' . $db->q($user->id));

        if ($files = $db->setQuery($query)->loadAssocList()) {
            foreach ($files as $file) {
                $domain_files->addItem($this->createItemFromArray($file));
            }
        }


        // ticket messages
        $query	= $db->getQuery(true);
        $query
            ->select($db->qn('ticket_id'))
            ->select($db->qn('message'))
            ->select($db->qn('date'))
            ->select($db->qn('html'))
            ->from($db->qn('#__rsticketspro_ticket_messages', 'm'))
            ->where($db->qn('m.user_id') . ' = ' . $db->q($user->id));

        if ($messages = $db->setQuery($query)->loadAssocList()) {
            foreach ($messages as $message) {
                $domain_messages->addItem($this->createItemFromArray($message));
            }
        }

        // ticket history
        $query	= $db->getQuery(true);
        $query
            ->select($db->qn('ticket_id'))
            ->select($db->qn('ip'))
            ->select($db->qn('date'))
            ->select($db->qn('type'))
            ->from($db->qn('#__rsticketspro_ticket_history'))
            ->where($db->qn('user_id') . ' = ' . $db->q($user->id));

        if ($actions = $db->setQuery($query)->loadAssocList()) {
            foreach ($actions as $action) {
                $domain_history->addItem($this->createItemFromArray($action));
            }
        }

        // ticket notes
        $query	= $db->getQuery(true);
        $query
            ->select($db->qn('ticket_id'))
            ->select($db->qn('text'))
            ->select($db->qn('date'))
            ->from($db->qn('#__rsticketspro_ticket_notes'))
            ->where($db->qn('user_id') . ' = ' . $db->q($user->id));

        if ($notes = $db->setQuery($query)->loadAssocList()) {
            foreach ($notes as $note) {
                $domain_notes->addItem($this->createItemFromArray($note));
            }
        }

		// searches
		$domain_searches = $this->createDomain('user_rsticketspro_searches');

		$query	= $db->getQuery(true);
		$query
			->select($db->qn('name'))
			->select($db->qn('params'))
			->select($db->qn('default'))
			->select($db->qn('published'))
			->select($db->qn('ordering'))
			->from($db->qn('#__rsticketspro_searches'))
			->where($db->qn('user_id') . ' = ' . $db->q($user->id));

		if ($searches = $db->setQuery($query)->loadAssocList()) {
			foreach ($searches as $search) {
				if (!empty($search['params'])) {
					$search['params'] = @unserialize(base64_decode($search['params']));
				}
				$domain_searches->addItem($this->createItemFromArray($search));
			}
		}

        // Signature
        $domain_signature = $this->createDomain('user_rsticketspro_signature');

        $query	= $db->getQuery(true);
        $query
            ->select($db->qn('signature'))
            ->from($db->qn('#__rsticketspro_staff'))
            ->where($db->qn('user_id') . ' = ' . $db->q($user->id));

        if ($signature = $db->setQuery($query)->loadAssoc()) {
            $domain_signature->addItem($this->createItemFromArray($signature));
        }

		$domains[] = $domain_tickets;
        $domains[] = $domain_messages;
		$domains[] = $domain_files;
        $domains[] = $domain_custom_fields;
		$domains[] = $domain_history;
		$domains[] = $domain_notes;
		$domains[] = $domain_searches;
		$domains[] = $domain_signature;

        return $domains;
    }
}