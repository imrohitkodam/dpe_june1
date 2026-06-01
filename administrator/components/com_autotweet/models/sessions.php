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
 * AutotweetModelSessions.
 *
 * @since       1.0
 */
class AutotweetModelSessions extends XTF0FModel
{
    /**
     * buildQuery.
     *
     * @param bool $overrideLimits Param
     *
     * @return XTF0FQuery
     */
    public function buildQuery($overrideLimits = false)
    {
        $db = $this->getDbo();

        $query = XTF0FQueryAbstract::getNew($db)->select('*')->from($db->quoteName('#__autotweet_sessions'));

        $fltOAuthKey = $this->getState('oauth_key', null, 'string');
        $query->where($db->qn('oauth_key').' = '.$db->q($fltOAuthKey));

        $fltOAuthType = $this->getState('oauth_type', null, 'string');
        $query->where($db->qn('oauth_type').' = '.$db->q($fltOAuthType));

        $expired = $this->getExpiredDate();

        // Valid session
        $query->where(
            '('.$db->qn('created').' > '.$db->q($expired).' OR '.
            $db->qn('modified').' > '.$db->q($expired).')'
        );

        return $query;
    }

    /**
     * purge.
     */
    public function purge()
    {
        $db = $this->getDbo();
        $query = XTF0FQueryAbstract::getNew($db)->delete($db->quoteName('#__autotweet_sessions'));
        $expired = $this->getExpiredDate();
        $query->where(
            '('.$db->qn('created').' < '.$db->q($expired).' AND ('.
                $db->qn('modified').' = '.$query->nullDate().' OR '.
                $db->qn('modified').' IS NULL))'
            .' OR '.
            '('.$db->qn('modified').' < '.$db->q($expired).' AND ('.
                $db->qn('modified').' <> '.$query->nullDate().' OR '.
                $db->qn('modified').' IS NULL))'
        );
        $db->setQuery($query);
        $db->execute();
    }

    /**
     * getExpiredDate.
     *
     * @return string
     */
    private function getExpiredDate()
    {
        // 24hs * 60min
        $lifetime = \Joomla\CMS\Factory::getConfig()->get('mapis_session_lifetime', 259200);

        $expiration = time() - $lifetime * 60;
        $expired = \Joomla\CMS\Factory::getDate($expiration)->toSql();

        return $expired;
    }
}
