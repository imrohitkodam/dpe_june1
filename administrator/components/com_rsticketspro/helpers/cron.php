<?php
/**
* @version 2.0.0
* @package RSTickets! Pro 2.0.0
* @copyright (C) 2010-2013 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die('Restricted access');

class RSTicketsProCron {
    
    protected $types = array();
    protected $logs = array();
    
    public function __construct($types = array()) {
        // Load language
        JFactory::getLanguage()->load('plg_system_rsticketsprocron', JPATH_ADMINISTRATOR);
        
        // Get cron types
        $this->types = $types;
    }
    
    public function test($id)
    {
        if (!$this->onCronTestFunctions())
        {
            return;
        }
        
        $mbox           = $this->_connect($id);
        $imap_errors    = $this->_getConnectionErrors();
        $app            = JFactory::getApplication();
        
        if (!$mbox)
        {
            $app->enqueueMessage(JText::_('RST_CRON_COULD_NOT_CONNECT'), 'error');

            if ($imap_errors)
            {
                foreach ($imap_errors as $imap_error)
                {
                    $app->enqueueMessage($imap_error, 'error');
                    $connectionData[] =   $imap_error; // DPE HACK
                }
            }
        }
        else
        {
            $app->enqueueMessage(JText::sprintf('RST_CRON_OK'));
            $app->enqueueMessage(JText::sprintf('RST_CRON_OK2'));
           // DPE HACK
            $connectionData[] = JText::sprintf('RST_CRON_OK').JText::sprintf('RST_CRON_OK2');

            $this->_disconnect($mbox);
        }

        return $connectionData;// DPE HACK
    }
    
    public function parse()
    {
        if (!$this->onCronTestFunctions(false))
        {
            return;
        }
        
        $db     = JFactory::getDbo();
        $query  = $db->getQuery(true);
        
        // Sanitize types
        $this->types = array_map('intval', $this->types);
        
        $query->clear()
            ->select('*')
            ->from($db->qn('#__rsticketspro_accounts'))
            ->where($db->qn('published').' = '.$db->q(1))
            ->where($db->qn('type').' IN ('.implode(',',$this->types).')')
            ->order($db->qn('ordering').' ASC');
        
        $db->setQuery($query);
        $accounts = $db->loadObjectList();
        
        if (empty($accounts))
        {
            return;
        }

        require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/rsticketspro.php';
        require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/ticket.php';
        
        $use_editor             = RSTicketsProHelper::getConfig('allow_rich_editor');
        $allow_ticket_reopening = RSTicketsProHelper::getConfig('allow_ticket_reopening');
        $now                    = JFactory::getDate();
        
        foreach ($accounts as $account)
        {
            if ($account->last_check + ($account->check_interval*60) > $now->toUnix())
            {
                continue;
            }
            
            $blacklist = array();
            if ($account->blacklist)
            {
                $blacklist = str_replace("\r\n", "\n", $account->blacklist);
                $blacklist = explode("\n", $blacklist);
            }
            
            $query->clear()
                ->update($db->qn('#__rsticketspro_accounts'))
                ->set($db->qn('last_check').' = '.$db->q($now->toUnix()))
                ->where($db->qn('id').' = '.$db->q($account->id));
            
            $db->setQuery($query);
            $db->execute();
            
            $mbox = $this->_connect($account->id);
            if (!$mbox)
            {
                continue;
            }
            
            $total = imap_num_msg($mbox);
            if ($total == 0)
            {
                $this->_disconnect($mbox);
                continue;
            }
            
            // little hack to prevent server from timing out
            if ($total > 10)
            {
                $total = 10;
            }
            
            $department = JTable::getInstance('Departments','RsticketsproTable');
            
            for ($mid = 1; $mid <= $total; ++$mid)
            {
                try
                {
                    // Get headers
                    $headers = $this->_decodeHeaders($mbox, $mid);
                    if (empty($headers))
                    {
                        throw new Exception("[FATAL ERROR] Could not read headers for message $mid.");
                    }

                    // Construct the array
                    $data = array(
                        'customer_id' => 0,
                        'email'       => $headers->from[0]->mailbox . '@' . $headers->from[0]->host
                    );


                // Hack for DPE : Get cc users

                if($headers->cc)
                {
                    foreach ($headers->cc as $headercc)
                    {
                        $ccUsersByEmail[] = $headercc->mailbox . '@' . $headercc->host;
                    }

                    $data['clusterusers'] = $ccUsersByEmail;
                }

                // Hack for DPE : Get cc users

                    if (!JMailHelper::isEmailAddress($data['email']))
                    {
                        throw new Exception("[FATAL ERROR] Message $mid: ".$data['email']." is not a valid email address.");
                    }

                    if (!empty($headers->fromaddress))
                    {
                        $data['name'] = trim(preg_replace('#(<.*>)#', '', $headers->fromaddress));
                    }
                    else
                    {
                        $data['name'] = $headers->from[0]->personal;
                    }
                    
                    if (isset($headers->subject))
                    {
                        $data['subject'] = $headers->subject;
                    }
                    elseif (isset($headers->Subject))
                    {
                        $data['subject'] = $headers->Subject;
                    }
                    else
                    {
                        $data['subject'] = '';
                    }
                    $data['department_id']  = $account->department_id;
                    $data['priority_id']    = $account->priority_id;
                    $data['status_id']      = RST_STATUS_OPEN;
                    $data['date']           = $now->toSql();
                    $data['last_reply']     = $data['date'];
                    $data['last_reply_customer'] = 1;
                    $data['agent']          = 'RSTickets! Pro Cron';
                    $data['referer']        = '';
                    $data['ip']             = '127.0.0.1';
                    $data['logged']         = 0;

                    if ($blacklist)
                    {
                        $found_blacklist = false;
                        foreach ($blacklist as $blacklisted_email)
                        {
                            if (strpos($blacklisted_email, '*') !== false)
                            {
                                // Wildcard found
                                $parts = explode('*', $blacklisted_email);
                                foreach ($parts as $b => $part)
                                {
                                    $parts[$b] = preg_quote($part, '/');
                                }
                                $pattern = '/'.implode('(.*)', $parts).'/i';
                                if (preg_match($pattern, $data['email'], $match))
                                {
                                    $found_blacklist = true;
                                    break;
                                }
                            }
                            else
                            {
                                // Regular address, see if it matches
                                if (strtolower($data['email']) == strtolower($blacklisted_email))
                                {
                                    $found_blacklist = true;
                                    break;
                                }
                            }
                        }

                        if ($found_blacklist)
                        {
                            throw new Exception("[FATAL ERROR] Message $mid: ".$data['email']." is blocked ('$blacklisted_email')");
                        }
                    }

                    $mail = new RSTicketsProMail($mbox, $mid);
                    if (empty($mail->structure))
                    {
                        throw new Exception("[FATAL ERROR] Could not read structure for message $mid.");
                    }

                    if (empty($mail->plainmsg))
                    {
                        $mail->plainmsg = html_entity_decode(strip_tags(str_replace(array('<br>', '<br />', '<br/>', '<BR>', '<BR />', '<BR/>', '</p>', '</P>'), "\n", $mail->htmlmsg)), ENT_COMPAT, 'utf-8');
                    }

                    if (empty($mail->htmlmsg))
                    {
                        $mail->htmlmsg = nl2br($mail->plainmsg);
                    }

                    $data['message'] = $use_editor ? $mail->htmlmsg : $mail->plainmsg;

                    // Strip quoted reply history — keep only the new content
                    $data['message'] = $this->_stripQuotedReply($data['message'], $use_editor);

                    // Check if this is a new ticket reply
                    $code = '';

                    if (preg_match('#\[([^]]+)\-([A-Z0-9]{10})\]#', $data['subject'], $matches))
                    {
                        $code = $matches[1].'-'.$matches[2];
                    }
                    else
                    {
                        // Fallback: search for existing open tickets with the same subject
                        // BUT: Only if it looks like a reply (Re: prefix or known CC user)
                        $isReplyPrefix = preg_match('/^(Re|Fwd|RE|FWD|re|fwd|Aw|Betr|Réf):\s+/i', $data['subject']);

                        // If it's from a known CC user, it might be a reply even without standard prefixes
                        $isKnownCC = false;
                        if (!$isReplyPrefix && !$isThreading)
                        {
                            // Quickly check if this email address is CC'd on ANY open ticket with this subject
                            $ccQuery = $db->getQuery(true);
                            $ccQuery->select($db->qn('t.id'))
                                ->from($db->qn('#__rsticketspro_tickets', 't'))
                                ->join('INNER', $db->qn('#__rsticket_integration_xref', 'eth') . ' ON ' . $db->qn('eth.ticket_id') . ' = ' . $db->qn('t.id'))
                                ->where($db->qn('t.subject') . ' = ' . $db->q($data['subject']))
                                ->where($db->qn('t.status_id') . ' IN (1, 3, 4)');
                            
                            $db->setQuery($ccQuery);
                            $matches = $db->loadColumn();
                            if ($matches)
                            {
                                foreach ($matches as $tid)
                                {
                                    $rsTktXrefTable = DPE::table('RsticketXref');
                                    $rsTktXrefTable->load(array('ticket_id' => $tid));
                                    
                                    $ccEmails     = new JRegistry($rsTktXrefTable->emails);
                                    $dpeccEmails  = new JRegistry($rsTktXrefTable->dpe_cc_emails);
                                    $userccEmails = new JRegistry($rsTktXrefTable->user_cc_emails);
                                    
                                    $allccEmails = array_merge(
                                        (array) $ccEmails->get('email', array()),
                                        (array) $dpeccEmails->get('email', array()),
                                        (array) $userccEmails->get('email', array())
                                    );
                                    
                                    if (in_array($data['email'], $allccEmails))
                                    {
                                        $isKnownCC = true;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($isReplyPrefix || $isKnownCC)
                        {
                            $cleanSubject = preg_replace('/^(Re|Fwd|RE|FWD|re|fwd):\s+/i', '', $data['subject']);
                            $cleanSubject = trim($cleanSubject);

                            $fQuery = $db->getQuery(true);
                            $fQuery->select($db->qn('t.id'))
                                ->select($db->qn('t.code'))
                                ->select($db->qn('t.customer_id'))
                                ->select($db->qn('t.alternative_email'))
                                ->from($db->qn('#__rsticketspro_tickets', 't'))
                                ->where('(' . $db->qn('t.subject') . ' = ' . $db->q($data['subject']) . ' OR ' . $db->qn('t.subject') . ' = ' . $db->q($cleanSubject) . ')')
                                ->where($db->qn('t.status_id') . ' IN (1, 3,4)') // Open or in-progress or On-hold(Awating client confirmation)
                                ->order($db->qn('t.date') . ' DESC');
                            
                            $db->setQuery($fQuery);
                            $candidates = $db->loadObjectList();

                            if ($candidates)
                            {
                                foreach ($candidates as $candidate)
                                {
                                    $isAuthorized = false;

                                    // Check if sender is the customer
                                    $uQuery = $db->getQuery(true);
                                    $uQuery->select($db->qn('email'))
                                        ->from($db->qn('#__users'))
                                        ->where($db->qn('id') . ' = ' . (int) $candidate->customer_id);
                                    $db->setQuery($uQuery);
                                    if ($db->loadResult() == $data['email'])
                                    {
                                        $isAuthorized = true;
                                    }

                                    // Check alternative email
                                    if (!$isAuthorized && !empty($candidate->alternative_email) && $candidate->alternative_email == $data['email'])
                                    {
                                        $isAuthorized = true;
                                    }

                                    // Check if sender is in CC list
                                    if (!$isAuthorized)
                                    {
                                        $rsTktXrefTable = DPE::table('RsticketXref');
                                        $rsTktXrefTable->load(array('ticket_id' => $candidate->id));

                                        $ccEmails     = new JRegistry($rsTktXrefTable->emails);
                                        $dpeccEmails  = new JRegistry($rsTktXrefTable->dpe_cc_emails);
                                        $userccEmails = new JRegistry($rsTktXrefTable->user_cc_emails);

                                        $ccEmailsArr     = $ccEmails->get('email', array());
                                        $dpeccEmailsArr  = $dpeccEmails->get('email', array());
                                        $userccEmailsArr = $userccEmails->get('email', array());

                                        $ccEmailsArr     = is_array($ccEmailsArr) ? $ccEmailsArr : (array) $ccEmailsArr;
                                        $dpeccEmailsArr  = is_array($dpeccEmailsArr) ? $dpeccEmailsArr : (array) $dpeccEmailsArr;
                                        $userccEmailsArr = is_array($userccEmailsArr) ? $userccEmailsArr : (array) $userccEmailsArr;

                                        $allccEmails = array_merge($ccEmailsArr, $dpeccEmailsArr, $userccEmailsArr);

                                        if (in_array($data['email'], $allccEmails))
                                        {
                                            $isAuthorized = true;
                                        }
                                    }

                                    if ($isAuthorized)
                                    {
                                        $code = $candidate->code;
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    if ($code)
                    {

                        $query->clear()
                            ->select($db->qn('id'))
                            ->select($db->qn('department_id'))
                            ->select($db->qn('customer_id'))
                            ->select($db->qn('staff_id'))
                            ->select($db->qn('status_id'))
                            ->select($db->qn('alternative_email'))
                            ->from($db->qn('#__rsticketspro_tickets'))
                            ->where($db->qn('code').' = '.$db->q($code));

                        $db->setQuery($query);
                        if ($ticket = $db->loadObject())
                        {
                            $this->addLog($account, "[OK] Found ticket [$code].", $data);

                            // Closed tickets cannot be replied to
                            if ($ticket->status_id == 2 && !$allow_ticket_reopening)
                            {
                                throw new RSTicketsProRejectionException("[WARNING] Ticket [$code] is marked as closed, cannot add a reply.");
                            }

                            $ticket_id = $ticket->id;

                            // Load department
                            $department->load($ticket->department_id);
                            $upload_extensions = str_replace("\r\n", "\n", $department->upload_extensions);
                            $upload_extensions = explode("\n", $upload_extensions);

                            // Check attachments
                            foreach ($mail->attachments as $i => $attachment)
                            {
                                // Check if this attachment is allowed
                                if (!RSTicketsProHelper::isAllowedExtension(RSTicketsProHelper::getExtension($attachment['filename']), $upload_extensions))
                                {
                                    $ext = RSTicketsProHelper::getExtension($attachment['filename']);
                                    $this->addLog($account, "[WARNING] Extension $ext is not allowed for department {$department->name}, dropping attachment {$attachment['filename']}.", $data);

                                    unset($mail->attachments[$i]);
                                    continue;
                                }

                                if ($department->upload_size > 0 && strlen($mail->attachments[$i]['contents']) > $department->upload_size*1048576)
                                {
                                    $size = number_format(strlen($mail->attachments[$i]['contents'])/1048576, 2, '.', '');
                                    $this->addLog($account, "[WARNING] Filesize $size mb exceeds department {$department->name} limit of {$department->upload_size} mb, dropping attachment {$attachment['filename']}.", $data);

                                    unset($mail->attachments[$i]);
                                    continue;
                                }

                                $mail->attachments[$i]['src'] = 'cron';
                            }

                            $data['ticket_id'] = $ticket_id;

                            $query->clear()
                                ->select($db->qn('id'))
                                ->from($db->qn('#__users'))
                                ->where($db->qn('email').' = '.$db->q($data['email']));

                            $db->setQuery($query);
                            $data['user_id'] = (int) $db->loadResult();


                            
                             // Try to check if this is an alternative email
                            if (!empty($ticket->alternative_email) && $ticket->alternative_email == $data['email'])
                            {
                                $query->clear()
                                    ->select($db->qn('id'))
                                    ->from($db->qn('#__users'))
                                    ->where($db->qn('id').' = '.$db->q($ticket->customer_id));

                                $db->setQuery($query);
                                $data['user_id'] = (int) $db->loadResult();
                            }
                            // DPE hack start to allow cc user reply if sender email available against ticket

                        $rsTktXrefTable = DPE::table('RsticketXref ');
                        $rsTktXrefTable->load(array('ticket_id' => $data['ticket_id']));

                        $ccEmails     = new JRegistry($rsTktXrefTable->emails);
                        $dpeccEmails  = new JRegistry($rsTktXrefTable->dpe_cc_emails);
                        $userccEmails = new JRegistry($rsTktXrefTable->user_cc_emails);

                        $ccEmails['email']     = empty($ccEmails['email']) ? (array) $ccEmails['email'] : $ccEmails['email'];
                        $dpeccEmails['email']  = empty($dpeccEmails['email']) ? (array) $dpeccEmails['email'] : $dpeccEmails['email'];
                        $userccEmails['email'] = empty($userccEmails['email']) ? (array) $userccEmails['email'] : $userccEmails['email'];

                        $allccEmails = array_merge($ccEmails['email'], $dpeccEmails['email'], $userccEmails['email']);

                        $isEmailValid = in_array($data['email'], $allccEmails);

                        // User not found - no reason to continue, this reply is not genuine
                        if (!$isEmailValid)
                        {
                            if (empty($data['user_id'])) {
                                $this->addLog($account, "[FATAL ERROR] Could not add reply from user with email {$data['email']} because it does not exist in our database, cannot continue.", $data);
                                // mark this message for removal
                                imap_delete($mbox, $mid);
                                continue;
                            }
                        }

                        // DPE hack end to allow cc user reply if sender email available against ticket
                            
                            // User not found - no reason to continue, this reply is not genuine
                            if (empty($data['user_id']))
                            {
                                // ... unless we accept all replies (mark them as the customer)
                                if ($account->accept_all_replies)
                                {
                                    $data['user_id'] = $ticket->customer_id;
                                }
                                else
                                {
                                    throw new RSTicketsProRejectionException("[FATAL ERROR] Could not add reply from user with email {$data['email']} because it does not exist in our database, cannot continue.");
                                }
                            }

                            // There's a special case where a staff member is actually the customer
                            $isStaff = RSTicketsProHelper::isStaff($data['user_id']);
                            if ($isStaff && $data['user_id'] == $ticket->customer_id)
                            {
                                $isStaff = false;
                            }

                            // Is staff - check permissions
                            // Must be able to answer a ticket, see unassigned tickets if this is unassigned and see other tickets if this is not his ticket
                            if ($isStaff)
                            {
                                $status_id   = 3; // Set to on-hold if a staff member replied
                                $permissions = RSTicketsProHelper::getPermissions($data['user_id']);

                                if (isset($permissions->answer_ticket) && !$permissions->answer_ticket)
                                {
                                    throw new RSTicketsProRejectionException("[FATAL ERROR] Could not add reply from staff member with email {$data['email']} because he does not have the permission to reply, cannot continue.");
                                }

                                if ($ticket->staff_id == 0 && isset($permissions->see_unassigned_tickets) && !$permissions->see_unassigned_tickets)
                                {
                                    throw new RSTicketsProRejectionException("[FATAL ERROR] Could not add reply from staff member with email {$data['email']} because he does not have the permission to see unassigned tickets, cannot continue.");
                                }

                                if ($ticket->staff_id > 0 && $ticket->staff_id != $data['user_id'] && isset($permissions->see_other_tickets) && !$permissions->see_other_tickets)
                                {
                                    throw new RSTicketsProRejectionException("[FATAL ERROR] Could not add reply from staff member with email {$data['email']} because he does not have the permission to see tickets assigned to other staff members, cannot continue.");
                                }
                            }
                            else
                            {
                                $status_id = 1; // Set to open if a customer replied

                                // Hack for DPE : Check cc users are form same cluster

                            $rsTktXrefTable = DPE::table('RsticketXref ');
                            $rsTktXrefTable->load(array('ticket_id' => $data['ticket_id']));
                            $rsTktXrefTable->agency_id;

                            JLoader::import("/components/com_cluster/libraries/cluster", JPATH_ADMINISTRATOR);
                            $cluster = ClusterCluster::getInstance($rsTktXrefTable->agency_id);
                
                            // Check email replied user is a member of cluster
                            $isValid = $cluster->isMember($data['user_id']);

                            // Hack for DPE : Check cc users are form same cluster  

                            // Hack for DPE start: Check cc user available againt ticket
                            /* If email replied user is not a member of cluster 
                            then check email available against ticket
                            */
                            if (!$isValid)
                            {
                                $ccEmails     = new JRegistry($rsTktXrefTable->emails);
                                $dpeccEmails  = new JRegistry($rsTktXrefTable->dpe_cc_emails);
                                $userccEmails = new JRegistry($rsTktXrefTable->user_cc_emails);

                                $ccEmails['email']     = empty($ccEmails['email']) ? (array) $ccEmails['email'] : $ccEmails['email'];
                                $dpeccEmails['email']  = empty($dpeccEmails['email']) ? (array) $dpeccEmails['email'] : $dpeccEmails['email'];
                                $userccEmails['email'] = empty($userccEmails['email']) ? (array) $userccEmails['email'] : $userccEmails['email'];

                                $allccEmails = array_merge($ccEmails['email'], $dpeccEmails['email'], $userccEmails['email']);

                                $isValid = in_array($data['email'], $allccEmails);
                            }
                            // Hack for DPE end: Check cc user available against ticket
                            //DPE hack  Check CC user is registered or not if yes accept the reply.

                                $query->clear()
                                    ->select($db->qn('id'))
                                    ->from($db->qn('#__users'))
                                    ->where($db->qn('id').' = '.$db->q($data['user_id']));
                                $db->setQuery($query);
                                $cc_userId = (int) $db->loadResult();    
                                

                                // User is not related to the ticket - not the customer
                                // ($cc_userId != $data['user_id']) &&
                                if (($data['user_id'] != $ticket->customer_id) && (!$account->accept_all_replies) && !$isValid)
                                {                           
                                    
                                    throw new RSTicketsProRejectionException("[FATAL ERROR] Could not add reply from customer with email {$data['email']} because he is not the ticket submitter, cannot continue.");
                                }
                            }

                            // Manually update status
                            if ($ticket->status_id == 2 && $allow_ticket_reopening)
                            {
                                $query->clear()
                                    ->update($db->qn('#__rsticketspro_tickets'))
                                    ->set($db->qn('status_id').' = '.$db->q($status_id))
                                    ->where($db->qn('id').' = '.$db->q($ticket_id));

                                $db->setQuery($query);
                                $db->execute();
                            }

                            // Uploads are not allowed
                            if (!$this->getCanUpload($department, $data['user_id']))
                            {
                                $for = $data['user_id'] ? 'all users' : 'unregistered users';
                                $this->addLog($account, "[WARNING] Dropping all attachments because department {$department->name} does not allow uploads for $for.", $data);
                                $mail->attachments = array();
                            }

                            if ($replyAbove = RSTicketsProHelper::getReplyAbove())
                            {
                                $replyAbove = str_replace(array('<p>', '</p>'), '', $replyAbove);
                                $offset = strpos($data['message'], $replyAbove); //Please reply above this line.
                                if ($offset !== false)
                                {
                                    $data['message'] = substr($data['message'], 0, $offset);
                                }
                            }

                            $this->addLog($account, "[OK] Adding reply to [$code] from {$data['email']}.", $data);

                            $data['files'] = $mail->attachments;
                            $ticket = new RSTicketsProTicketHelper();
                            $ticket->bind($data);
                            $ticket->saveMessage();
                            $message_id = $ticket->getMessageId();
                            $ticket_id = $ticket->getTicketId();

                            if ($message_id > 0)
                            {
                                $query->clear()
                                    ->select($db->qn('id'))
                                    ->select($db->qn('ticket_id'))
                                    ->select($db->qn('message'))
                                    ->from($db->qn('#__rsticketspro_ticket_messages'))
                                    ->where($db->qn('id').' = '.(int) $message_id);

                                $db->setQuery($query);
                                if ($message = $db->loadObject())
                                {
                                    $ticket_id   = $message->ticket_id;
                                    $pattern     = '#src="({rsticketspro_cron_inline_(.*?)})"#i';
                                    if (preg_match_all($pattern, $message->message, $matches))
                                    {
                                        for ($m = 0; $m < count($matches[0]); $m++)
                                        {
                                            $replace  = $matches[1][$m];
                                            $filename = $matches[2][$m];

                                            $with = JUri::root().'index.php?option=com_rsticketspro&task=viewinline&cid='.$ticket_id.'&filename='.htmlentities($filename, ENT_COMPAT, 'utf-8').'&lang=en';
                                            $message->message = str_replace($replace, $with, $message->message);
                                        }

                                        $query->clear()
                                            ->update($db->qn('#__rsticketspro_ticket_messages'))
                                            ->set($db->qn('message').' = '.$db->q($message->message))
                                            ->where($db->qn('id').' = '.$db->q($message->id));

                                        $db->setQuery($query);
                                        $db->execute();
                                    }
                                }
                            }
                            // mark this message for removal
                            imap_delete($mbox, $mid);
                            continue;
                        }
                    }

                    $department->load($account->department_id);
                    $upload_extensions = str_replace("\r\n", "\n", $department->upload_extensions);
                    $upload_extensions = explode("\n", $upload_extensions);

                    // Add new ticket
                    // Check if new ticket can be added

                    // Nobody
                    if (!$account->accept)
                    {
                        throw new RSTicketsProRejectionException("[FATAL ERROR] Creation of new tickets is not allowed, dropping message from {$data['email']}.");
                    }

                    // Everyone or only registered
                    $query->clear()
                        ->select($db->qn('id'))
                        ->from($db->qn('#__users'))
                        ->where($db->qn('email').' = '.$db->q($data['email']));

                    $db->setQuery($query);
                    $user_id = (int) $db->loadResult();

                    // Only registered
                    if ($account->accept == 1 && !$user_id)
                    {
                        throw new RSTicketsProRejectionException("[FATAL ERROR] {$data['email']} is not registered, cannot create a ticket to {$department->name}.");
                    }

                    // Uploads are not allowed
                    if (!$this->getCanUpload($department, $user_id))
                    {
                        $for = $user_id ? 'all users' : 'unregistered users';
                        $this->addLog($account, "[WARNING] Dropping all attachments because department {$department->name} does not allow uploads for $for.", $data);
                        $mail->attachments = array();
                    }

                    if ($user_id)
                    {
                        $data['customer_id'] = $user_id;
                        if (RSTicketsProHelper::isStaff($user_id))
                        {
                            $permissions = RSTicketsProHelper::getPermissions($user_id);
                            if (isset($permissions->add_ticket) && !$permissions->add_ticket)
                            {
                                throw new RSTicketsProRejectionException("[FATAL ERROR] Staff member with email {$data['email']} does not have the permission to add a new ticket to {$department->name}.");
                            }
                        }
                    }

                    // Check attachments
                    foreach ($mail->attachments as $i => $attachment)
                    {
                        // Check if this attachment is allowed
                        if (!RSTicketsProHelper::isAllowedExtension(RSTicketsProHelper::getExtension($attachment['filename']), $upload_extensions))
                        {
                            $ext = RSTicketsProHelper::getExtension($attachment['filename']);
                            $this->addLog($account, "[WARNING] Extension $ext is not allowed for department {$department->name}, dropping attachment {$attachment['filename']}.", $data);

                            unset($mail->attachments[$i]);
                            continue;
                        }

                        if ($department->upload_size > 0 && strlen($mail->attachments[$i]['contents']) > $department->upload_size*1048576)
                        {
                            $size = number_format(strlen($mail->attachments[$i]['contents'])/1048576, 2, '.', '');
                            $this->addLog($account, "[WARNING] Filesize $size mb exceeds department {$department->name} limit of {$department->upload_size} mb, dropping attachment {$attachment['filename']}.", $data);

                            unset($mail->attachments[$i]);
                            continue;
                        }

                        $mail->attachments[$i]['src'] = 'cron';
                    }

                    if (!empty($department->upload_ticket_required) && empty($mail->attachments))
                    {
                        throw new RSTicketsProRejectionException("[FATAL ERROR] Ticket has no attachments but department requires them, cannot create a ticket to {$department->name}.");
                    }

                    $this->addLog($account, "[OK] Adding a new ticket from {$data['email']} to {$department->name}.", $data);

                    $data['files'] = $mail->attachments;
                    $ticket = new RSTicketsProTicketHelper();
                    $ticket->bind($data);
                    if (!$ticket->saveTicket())
                    {
                        throw new Exception($ticket->getError());
                    }
                    $message_id = $ticket->getMessageId();
                    $ticket_id = $ticket->getTicketId();

                    if ($ticket_id > 0)
                    {
                        $query->clear()
                            ->select($db->qn('id'))
                            ->select($db->qn('message'))
                            ->from($db->qn('#__rsticketspro_ticket_messages'))
                            ->where($db->qn('ticket_id').' = '.(int) $ticket_id)
                            ->where($db->qn('id').' = '.(int) $message_id);

                        $db->setQuery($query);
                        if ($message = $db->loadObject())
                        {
                            $pattern     = '#src="({rsticketspro_cron_inline_(.*?)})"#i';
                            if (preg_match_all($pattern, $message->message, $matches))
                            {
                                for ($m = 0; $m < count($matches[0]); $m++)
                                {
                                    $replace  = $matches[1][$m];
                                    $filename = $matches[2][$m];

                                    $with = JUri::root().'index.php?option=com_rsticketspro&task=viewinline&cid='.$ticket_id.'&filename='.htmlentities($filename, ENT_COMPAT, 'utf-8').'&lang=en';
                                    $message->message = str_replace($replace, $with, $message->message);
                                }

                                $query->clear()
                                    ->update($db->qn('#__rsticketspro_ticket_messages'))
                                    ->set($db->qn('message').' = '.$db->q($message->message))
                                    ->where($db->qn('id').' = '.$db->q($message->id));

                                $db->setQuery($query);
                                $db->execute();
                            }
                        }
                    }
                }
                catch (RSTicketsProRejectionException $e)
                {
                    $this->addLog($account, $e->getMessage(), isset($data) ? $data : array());

                    // We need to send the rejected email
                    // Get email sending settings
                    $from           = RSTicketsProHelper::getConfig('email_address');
                    $fromname       = RSTicketsProHelper::getConfig('email_address_fullname');
                    $replyto        = RSTicketsProHelper::getConfig('email_address_reply_to');
                    $replytoname    = RSTicketsProHelper::getConfig('email_address_reply_to_name');
                    // Are we using global ?
                    if (RSTicketsProHelper::getConfig('email_use_global'))
                    {
                        $app            = JFactory::getApplication();
                        $from           = $app->get('mailfrom');
                        $fromname       = $app->get('fromname');
                        $replyto        = $app->get('replyto');
                        $replytoname    = $app->get('replytoname');
                    }
                    if (isset($department->email_use_global) && !$department->email_use_global)
                    {
                        $from           = $department->email_address;
                        $fromname       = $department->email_address_fullname;
                        $replyto        = !empty($department->email_address_reply_to) ? $department->email_address_reply_to : null;
                        $replytoname    = !empty($department->email_address_reply_to_name) ? $department->email_address_reply_to_name : null;
                    }

                    if ($email = RSTicketsProHelper::getEmail('reject_email'))
                    {
                        $replace = array('{live_site}', '{customer_name}', '{customer_email}', '{department}', '{subject}', '{reason}');
                        $with = array(JUri::root(), $data['name'], $data['email'], JText::_($department->name), $data['subject'], $e->getMessage());

                        $email_subject = str_replace($replace, $with, $email->subject);
                        $email_message = str_replace($replace, $with, $email->message);

                        if ($from !== $data['email'])
                        {
                            RSTicketsProHelper::sendMail($from, $fromname, $data['email'], $email_subject, $email_message, 1, null, null, null, $replyto, $replytoname);
                            $this->addLog($account, "[OK] Sending the rejection email to {$data['email']}.", $data);
                        }
                        else
                        {
                            $this->addLog($account, "[WARNING] Did not send rejection email to {$data['email']} because sender and receiver are the same.", $data);
                        }
                    }
                }
                catch (Exception $e)
                {
                    $this->addLog($account, $e->getMessage(), isset($data) ? $data : array());
                }

                // mark this message for removal
                imap_delete($mbox, $mid);
            }
            
            imap_expunge($mbox);
            $this->_disconnect($mbox);
        }

        $this->saveLog();
    }
    
    
    protected function onCronTestFunctions($show_message = true)
    {
        if (!function_exists('imap_open'))
        {
            if ($show_message)
            {
                JFactory::getApplication()->enqueueMessage(JText::_('RST_CRON_NO_IMAP'), 'warning');
            }

            return false;
        }

        if (!function_exists('iconv'))
        {
            if ($show_message)
            {
                JFactory::getApplication()->enqueueMessage(JText::_('RST_CRON_NO_ICONV'), 'warning');
            }

            return false;
        }

        return true;
    }
    
    protected function _getConnectionErrors()
    {
        $return = imap_errors();

        if (!is_array($return))
        {
            $return = array();
        }

        imap_alerts();
        
        return $return;
    }
    
    /**
     * Strip quoted reply history from an email body.
     * Removes "On [date] [name] wrote:" blocks and blockquotes so only
     * the new content is stored in the ticket message.
     */
    protected function _stripQuotedReply($body, $is_html = true)
    {
        if ($is_html)
        {
            // 1. Cut at common HTML quote markers (classes/ids)
            // We search for the first marker and cut everything from that point onwards.
            $html_markers = array(
                'class="gmail_quote"',
                'class="gmail_attr"',
                'id="divRplyFwdMsg"',
                'id="appendonsend"',
                '<blockquote'
            );

            foreach ($html_markers as $marker)
            {
                if (($pos = strpos($body, $marker)) !== false)
                {
                    // Find the opening tag '<' before this marker to cut cleanly
                    $start_of_tag = strrpos(substr($body, 0, $pos), '<');
                    if ($start_of_tag !== false)
                    {
                        $body = substr($body, 0, $start_of_tag);
                    }
                    else
                    {
                        $body = substr($body, 0, $pos);
                    }
                }
            }

            // 2. Cut at text-based reply headers that might be inside HTML tags
            $patterns = array(
                '/On\s+.*?\s+wrote:/i',
                '/-+Original Message-+/i',
                '/From:.*?\nSent:.*?\nTo:.*?\nSubject:.*?/si',
                '/________________________________/i'
            );

            foreach ($patterns as $pattern)
            {
                if (preg_match($pattern, $body, $matches, PREG_OFFSET_CAPTURE))
                {
                    $pos = $matches[0][1];
                    // Try to backtrack to the nearest line break or paragraph start
                    $p_start = strrpos(substr($body, 0, $pos), '<p');
                    $d_start = strrpos(substr($body, 0, $pos), '<div');
                    $br_start = strrpos(substr($body, 0, $pos), '<br');
                    
                    $backtrack = max($p_start, $d_start, $br_start);
                    
                    if ($backtrack !== false && ($pos - $backtrack) < 100)
                    {
                        $body = substr($body, 0, $backtrack);
                    }
                    else
                    {
                        $body = substr($body, 0, $pos);
                    }
                }
            }

            // Final cleanup of trailing breaks and whitespace
            $body = preg_replace('/(<br\s*\/?>|\s|<div><\/div>|<p><\/p>)*$/i', '', $body);
        }
        else
        {
            // Plain text logic
            $patterns = array(
                '/^On\s+.*?\s+wrote:\s*$/mi',
                '/^-+Original Message-+/i',
                '/^From:.*?\nSent:.*?\nTo:.*?\nSubject:.*?/si',
                '/^________________________________/m'
            );

            foreach ($patterns as $pattern)
            {
                if (preg_match($pattern, $body, $matches, PREG_OFFSET_CAPTURE))
                {
                    $body = substr($body, 0, $matches[0][1]);
                }
            }

            // Also strip lines starting with ">" (quoted lines)
            $lines = explode("\n", $body);
            $clean = array();
            foreach ($lines as $line)
            {
                if (strncmp(ltrim($line), '>', 1) !== 0)
                {
                    $clean[] = $line;
                }
            }
            $body = implode("\n", $clean);
        }

        return trim($body);
    }

    protected function _connect($account_id)
    {
        $account = JTable::getInstance('Crons','RsticketsproTable');
        $account->load($account_id);
        
        // {[server]:[port][flags]}
        $server = $account->server;
        $port   = $account->port;
        $flags  = '/' . $account->protocol;

        if ($account->security)
        {
            $flags .= '/'.$account->security;

            if (!$account->validate)
            {
                $flags .= '/novalidate-cert';
            }
        }
        else
        {
            $flags .= '/notls';
        }

        $connect = '{' . $server . ':' . $port . $flags . '}INBOX';
        
        $mbox = @imap_open($connect, $account->username, $account->password, OP_SILENT);
        return $mbox;
    }
    
    protected function _disconnect($mbox) {
        return imap_close($mbox);
    }
    
    protected function getCanUpload($department, $is_registered) {
        $upload = $department->upload;
        
        if ($upload == 0) {
            return false;
        } elseif ($upload == 1) {
            return true;
        } elseif ($upload == 2 && $is_registered) {
            return true;
        }
        
        return false;
    }
    
    protected function addLog($account, $desc, $data = array()) {
        $date = JFactory::getDate();
        $date->modify('+'.count($this->logs).' seconds');
        
        $this->logs[] = array(
            'date' => $date->toSql(),
            'account_id' => $account->id,
            'subject' => !empty($data['subject']) ? $data['subject'] : '',
            'description' => $desc
        );
    }
    
    protected function saveLog() {
        $db     = JFactory::getDbo();
        $query  = $db->getQuery(true);
        
        foreach ($this->logs as $log) {
            $query->clear()
                ->insert($db->qn('#__rsticketspro_accounts_log'))
                ->set($db->qn('date').' = '.$db->q($log['date']))
                ->set($db->qn('account_id').' = '.$db->q($log['account_id']))
                ->set($db->qn('subject').' = '.$db->q($log['subject']))
                ->set($db->qn('description').' = '.$db->q($log['description']));
            
            $db->setQuery($query);
            $db->execute();
        }
    }
    
    protected function _decodeHeaders($mbox, $mid)
    {
        $headers = imap_headerinfo($mbox, $mid);
        if (empty($headers))
        {
            return false;
        }
        
        foreach ($headers as $header => $value)
        {
            if (!is_array($value))
            {
                if ($objects = imap_mime_header_decode($value))
                {
                    $string = '';

                    foreach ($objects as $obj)
                    {
                        $obj->charset = strtoupper($obj->charset);

                        if ($obj->charset != 'DEFAULT' && $obj->charset != 'UTF-8')
                        {
                            $obj->text = iconv($obj->charset, 'UTF-8', $obj->text);
                        }

                        if (strlen($obj->text))
                        {
                            $string .= $obj->text;
                        }
                    }

                    if (strlen($string))
                    {
                        $headers->{$header} = $string;
                    }
                }
            }
        }
        
        return $headers;
    }

}


class RSTicketsProMail
{
    public $htmlmsg;
    public $plainmsg;
    public $charset;
    public $attachments;
    
    public $mbox;
    public $mid;
    public $structure;
    
    public $inline_ids = array();
    
    public function __construct($mbox, $mid) {
        $this->mbox = $mbox;
        $this->mid = $mid;
        
        $this->structure = imap_fetchstructure($this->mbox, $this->mid);        
        if (empty($this->structure))
            return false;
        
        $this->_getMessage();
        // first level
        $this->_getAttachments($this->structure);
        $this->_setInlineAttachments();
        
        if ($this->charset != 'UTF-8' && $this->charset != 'X-UNKNOWN') {
            $beforeplainmsg = $this->plainmsg;
            $beforehtmlmsg  = $this->htmlmsg;
            $this->plainmsg = iconv($this->charset, 'UTF-8//IGNORE', $this->plainmsg);
            $this->htmlmsg = iconv($this->charset, 'UTF-8//IGNORE', $this->htmlmsg);
            
            if (strlen($beforeplainmsg) > 0 && strlen($this->plainmsg) == 0)
                $this->plainmsg = $beforeplainmsg;
            unset($beforeplainmsg);
            if (strlen($beforehtmlmsg) > 0 && strlen($this->htmlmsg) == 0)
                $this->htmlmsg = $beforehtmlmsg;
            unset($beforehtmlmsg);
        }
    }
    
    protected function _setInlineAttachments() {
        if (!count($this->inline_ids)) return;
        foreach ($this->inline_ids as $id => $filename)
            $this->htmlmsg = preg_replace('#src="cid:'.preg_quote($id).'"#i', 'src="{rsticketspro_cron_inline_'.$filename.'}"', $this->htmlmsg);
    }
    
    protected function _getAttachments($structure, $level='') {
        if (!isset($structure->parts)) return;
        if (!count($structure->parts)) return;
        
        $parts = count($structure->parts);
        for ($i=0; $i<$parts; $i++) {
            // loop
            if (!empty($structure->parts[$i]->parts)) {
                $nextlevel = $level.($i+1).'.';
                $this->_getAttachments($structure->parts[$i], $nextlevel);
            }
            
            $is_attachment = false;
            
            $new_attachment = array(
                'filename' => '',
                'name' => '',
                'contents' => ''
            );
            
            if ($structure->parts[$i]->ifdparameters)
                foreach ($structure->parts[$i]->dparameters as $object)
                    if (strtolower($object->attribute) == 'filename') {
                        $is_attachment = true;
                        $new_attachment['filename'] = $object->value;
                    }
            
            if ($structure->parts[$i]->ifparameters)
                foreach ($structure->parts[$i]->parameters as $object)
                    if (strtolower($object->attribute) == 'name') {
                        $is_attachment = true;
                        $new_attachment['filename'] = $object->value;
                    }
            
            // IMAGE 
            if ($structure->parts[$i]->type == 5) {
                $is_attachment = true;
                $ext = 'jpg';
                if ($structure->parts[$i]->ifsubtype)
                    $ext = strtolower($structure->parts[$i]->subtype);
                $new_attachment['filename'] = uniqid('image').'.'.$ext;
            }
            
            if ($is_attachment)
            {
                $new_attachment['contents'] = imap_fetchbody($this->mbox, $this->mid, $level.($i+1));
                
                // 3 = BASE64
                if ($structure->parts[$i]->encoding == 3)
                {
                    $new_attachment['contents'] = base64_decode($new_attachment['contents']);
                }

                // 4 = QUOTED-PRINTABLE
                elseif ($structure->parts[$i]->encoding == 4)
                {
                    $new_attachment['contents'] = quoted_printable_decode($new_attachment['contents']);
                }

                if ($objects = imap_mime_header_decode($new_attachment['filename']))
                {
                    $new_attachment_filename = '';

                    foreach ($objects as $obj)
                    {
                        $obj->charset = strtoupper($obj->charset);

                        if ($obj->charset != 'DEFAULT' && $obj->charset != 'UTF-8')
                        {
                            $obj->text = iconv($obj->charset, 'UTF-8', $obj->text);
                        }

                        if (strlen($obj->text))
                        {
                            $new_attachment_filename .= $obj->text;
                        }
                    }

                    if (strlen($new_attachment_filename))
                    {
                        $new_attachment['filename'] = $new_attachment_filename;
                    }
                }
                
                $this->attachments[] = $new_attachment;
                
                if (isset($structure->parts[$i]->id))
                    $this->inline_ids[trim($structure->parts[$i]->id, '<>')] = @$new_attachment['filename'];
            }
        }
    }

    protected function _getMessage() {      
        $this->htmlmsg = $this->plainmsg = $this->charset = '';
        $this->attachments = array();

        // BODY
        // not multipart
        if (empty($this->structure->parts))
            $this->_getPart($this->structure, 0);
        else
            // multipart: iterate through each part
            foreach ($this->structure->parts as $partno0 => $p)
                $this->_getPart($p, $partno0+1);
    }
    
    protected function _getPart($p, $partno) {
        // $partno = '1', '2', '2.1', '2.1.3', etc if multipart, 0 if not multipart

        // DECODE DATA
        if ($partno)
            $data = imap_fetchbody($this->mbox, $this->mid, $partno);
        else
            $data = imap_body($this->mbox, $this->mid);
            
        // Any part may be encoded, even plain text messages, so check everything.
        if ($p->encoding == 4)
            $data = quoted_printable_decode($data);
        elseif ($p->encoding == 3)
            $data = base64_decode($data);
        // no need to decode 7-bit, 8-bit, or binary

        // PARAMETERS
        // get all parameters, like charset, filenames of attachments, etc.
        $params = array();
        if (!empty($p->parameters))
            foreach ($p->parameters as $x)
                $params[ strtolower( $x->attribute ) ] = $x->value;
        if (!empty($p->dparameters))
            foreach ($p->dparameters as $x)
                $params[ strtolower( $x->attribute ) ] = $x->value;

        // TEXT
        if ($p->type == 0 && $data)
        {           
            // Messages may be split in different parts because of inline attachments,
            // so append parts together with blank row.
            if (strtolower($p->subtype)=='plain') {
                $this->plainmsg .= trim($data) ."\n\n";
            } elseif ($p->ifdisposition && strtolower($p->disposition) == 'attachment') {
                // do nothing for now
            } else {
                if (preg_match("#<body[^>]*>(.*?)<\/body>#is", $data, $matches))
                    $data = $matches[1];
                $this->htmlmsg .= $this->_closeTags($data) .'<br /><br />';
            }
            $this->charset = $params['charset'];  // assume all parts are same charset
        }

        // EMBEDDED MESSAGE
        // Many bounce notifications embed the original message as type 2,
        // but AOL uses type 1 (multipart), which is not handled here.
        // There are no PHP functions to parse embedded messages,
        // so this just appends the raw source to the main message.
        elseif ($p->type == 2 && $data)
            $this->plainmsg .= trim($data) ."\n\n";

        // SUBPART RECURSION
        if (!empty($p->parts))
            foreach ($p->parts as $partno0 => $p2)
                $this->_getPart($p2, $partno.'.'.($partno0+1));  // 1.2, 1.2.1, etc.
    }
    
    protected function _closeTags($html) {
        #put all opened tags into an array
        preg_match_all('#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
        $openedtags = $result[1];   #put all closed tags into an array
        preg_match_all('#</([a-z]+)>#iU', $html, $result);
        $closedtags = $result[1];
        $len_opened = count($openedtags);
        # all tags are closed
        if (count($closedtags) == $len_opened) {
            return $html;
        }
        $openedtags = array_reverse($openedtags);
        # close tags
        for ($i=0; $i < $len_opened; $i++) {
            if (!in_array($openedtags[$i], $closedtags)){
                $html .= '</'.$openedtags[$i].'>';
            } else {
                unset($closedtags[array_search($openedtags[$i], $closedtags)]);
            }
        }
        return $html;
    }
}

class RSTicketsProRejectionException extends Exception {}
