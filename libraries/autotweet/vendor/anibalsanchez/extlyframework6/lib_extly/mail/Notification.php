<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

// No direct access
defined('_JEXEC') || exit('Restricted access');

/**
 * Utility class for mail notification to admin users.
 *
 * @since       3.0
 */
abstract class Notification
{
    /**
     * mailToAdmin.
     *
     * @param string $emailSubject Subject
     * @param string $emailBody    Body
     *
     * @since   3.0
     */
    public static function mailToAdmin($emailSubject, $emailBody)
    {
        $config = JFactory::getConfig();

        // Get all admin users - Limit 16 Users
        $query = 'SELECT name, email, sendEmail, id FROM #__users'.' WHERE sendEmail=1 AND block=0 LIMIT 16';

        $db = JFactory::getDBO();
        $db->setQuery($query);
        $rows = $db->loadObjectList();

        // Send mail to all users with users creating permissions and receiving system emails
        foreach ($rows as $row) {
            $usercreator = JFactory::getUser($id = $row->id);

            if ($usercreator->authorise('core.manage', 'com_users')) {
                $return = JFactory::getMailer()->sendMail($config->get('mailfrom'), $config->get('fromname'), $row->email, $emailSubject, $emailBody, true);
            }
        }
    }
}
