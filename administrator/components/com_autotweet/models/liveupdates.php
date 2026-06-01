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

defined('_JEXEC') || exit;

/**
 * AutotweetModelLiveUpdates - The updates provisioning Model.
 *
 * @since       1.0
 */
class AutotweetModelLiveUpdates extends XTF0FUtilsUpdate
{
    public const CONFIG_AUTOUPDATE_KEY = 'live-udpate';

    public const CONFIG_AUTOUPDATE_NOTIFY = 2;

    public const CONFIG_AUTOUPDATE_STABLE = 0;

    /**
     * Public constructor. Initialises the protected members as well.
     *
     * @param array $config Param
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $dlid = EParameter::getComponentParam(CAUTOTWEETNG, 'update_dlid', '');
        $this->extraQuery = '';

        // If I have a valid Download ID I will need to use a non-blank extra_query in Joomla! 3.2+
        if (preg_match('/^([0-9]{1,}:)?[0-9a-f]{32}$/i', $dlid)) {
            $this->extraQuery = 'dlid='.$dlid.'&amp;dummy=my.zip';
        }

        $this->updateSiteName = VersionHelper::getFlavourName();
        $this->updateSite = VersionHelper::getUpdatesSite();
    }

    /**
     * Refreshes the Joomla! update sites for this extension as needed.
     */
    public function refreshUpdateSite()
    {
        if (empty($this->extension_id)) {
            return;
        }

        // Clean the component update sites
        $this->cleanComponentUpdateSites();

        // Load the Package
        $this->loadPackageExtension();
        parent::refreshUpdateSite();
    }

    public function cleanComponentUpdateSites()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select($db->qn('update_site_id'))
            ->from($db->qn('#__update_sites_extensions'))
            ->where($db->qn('extension_id').' = '.$db->q($this->extension_id))
            ->order('update_site_id');
        $db->setQuery($query);
        $updateSiteIds = $db->loadColumn(0);

        if (empty($updateSiteIds)) {
            return;
        }

        // Delete all component update sites
        $cond = implode(',', $updateSiteIds);

        $query = $db->getQuery(true)
            ->delete($db->qn('#__update_sites_extensions'))
            ->where($db->qn('update_site_id').' IN ('.$cond.')');
        $db->setQuery($query);
        $db->execute();

        $query = $db->getQuery(true)
            ->delete($db->qn('#__update_sites'))
            ->where($db->qn('update_site_id').' IN ('.$cond.')');
        $db->setQuery($query);
        $db->execute();
    }

    /**
     * autoupdate.
     */
    public function autoupdate()
    {
        $return = [
            'message' => '',
        ];

        // First of all let's check if there are any updates
        $updateInfo = (object) $this->getUpdates(true);

        // There are no updates, there's no point in continuing
        if (!$updateInfo->hasUpdate) {
            return [
                'message' => [
                    'No available updates found.',
                ],
            ];
        }

        $return['message'][] = 'Update detected, version: '.$updateInfo->version;

        // Ok, an update is found, what should I do?
        $autoupdate = self::CONFIG_AUTOUPDATE_NOTIFY;

        // Let's notifiy the user
        if (self::CONFIG_AUTOUPDATE_NOTIFY === (int) $autoupdate) {
            $email = EParameter::getComponentParam(CAUTOTWEETNG, 'notificationEmail');

            if (empty($email)) {
                $return['message'][] = "There isn't an email for notifications, no notification will be sent.";
            } else {
                // Ok, I can send it out, but before let's check if the user set any frequency limit
                $numfreq = EParameter::getComponentParam(CAUTOTWEETNG, 'notificationFreq', 1);
                $freqtime = EParameter::getComponentParam(CAUTOTWEETNG, 'notificationTime', 'day');

                $lastSend = $this->getLastSend();
                $shouldSend = false;

                if (!$numfreq) {
                    $shouldSend = true;
                } else {
                    $check = strtotime('-'.$numfreq.' '.$freqtime);

                    if ($lastSend < $check) {
                        $shouldSend = true;
                    } else {
                        $return['message'][] = "Frequency limit hit, I won't send any email";
                    }
                }

                if ($shouldSend) {
                    if ($this->sendNotificationEmail($updateInfo->version, $email)) {
                        $return['message'][] = 'E-mail(s) correctly sent';
                    } else {
                        $return['message'][] = 'An error occurred while sending e-mail(s). Please double check your settings';
                    }

                    $this->setLastSend();
                }
            }
        }

        return $return;
    }

    /**
     * sendNotificationEmail.
     *
     * @param string $version Param
     * @param string $email   Param
     */
    private function sendNotificationEmail($version, $email)
    {
        $email_subject = <<<'ENDSUBJECT'
THIS EMAIL IS SENT FROM YOUR SITE "[SITENAME]" - Update available
ENDSUBJECT;

        $email_body = <<<'ENDBODY'
================================================================================
UPDATE INFORMATION
================================================================================

Your site has determined that there is an updated version of [EXTENSION] available for download.

New version number: [VERSION]

This email is sent to you by your site to remind you of this fact. This email IS NOT sent by the authors of [EXTENSION]. It is sent automatically by your own site, [SITENAME]. The authors of the software will never contact you about available updates.

================================================================================
WHY AM I RECEIVING THIS EMAIL?
================================================================================

This email has been automatically sent by a CLI script you, or the person who built or manages your site, has installed and explicitly activated. This script looks for updated versions of the software and sends an email notification to all Super Users. You will receive several similar emails from your site, up to 6 times per day, until you either update the software or disable these emails.

To disable these emails, please contact your site administrator.

If you do not understand what this means, please do not contact the authors of the software. They are NOT sending you this email and they cannot help you. Instead, please contact the person who built or manages your site.

================================================================================
WHO SENT ME THIS EMAIL?
================================================================================

This email is sent to you by your own site, [SITENAME]
ENDBODY;

        $jconfig = \Joomla\CMS\Factory::getConfig();
        $sitename = $jconfig->get('sitename');

        $substitutions = [
            '[VERSION]' => $version,
            '[SITENAME]' => $sitename,
            '[EXTENSION]' => '"'.VersionHelper::getFlavourName().'"',
        ];

        $email_subject = str_replace(array_keys($substitutions), array_values($substitutions), $email_subject);
        $email_body = str_replace(array_keys($substitutions), array_values($substitutions), $email_body);

        $mailer = \Joomla\CMS\Factory::getMailer();

        $mailfrom = $jconfig->get('mailfrom');
        $fromname = $jconfig->get('fromname');

        $mailer->setSender(
            [
                $mailfrom,
                $fromname,
            ]
        );

        $mailer->addRecipient($email);
        $mailer->setSubject($email_subject);
        $mailer->setBody($email_body);

        return $mailer->Send();
    }

    /**
     * getLastSend.
     */
    private function getLastSend()
    {
        return XTF0FModel::getTmpInstance('Automators', 'AutoTweetModel')
            ->lastRun(self::CONFIG_AUTOUPDATE_KEY)
            ->toUnix();
    }

    /**
     * setLastSend.
     */
    private function setLastSend()
    {
        $now = \Joomla\CMS\Factory::getDate();
        XTF0FModel::getTmpInstance('Automators', 'AutoTweetModel')->lastRunCheck(self::CONFIG_AUTOUPDATE_KEY, 0, $now);
    }

    private function loadPackageExtension()
    {
        // Find the extension ID
        $db = XTF0FPlatform::getInstance()->getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->qn('#__extensions'))
            ->where($db->qn('type').' = '.$db->q('package'))
            ->where($db->qn('element').' = '.$db->q('pkg_autotweet'));
        $db->setQuery($query);
        $extension = $db->loadObject();

        if (is_object($extension)) {
            $this->extension_id = $extension->extension_id;
            $data = json_decode($extension->manifest_cache, true);

            if (isset($data['version'])) {
                $this->version = $data['version'];
            }
        }
    }
}
