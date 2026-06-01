<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activities Notifier LOGman plugin.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */

class PlgLogmanNotifierEmail extends ComLogmanActivityNotifierEmail
{
    /**
     * Determines if logins and logouts should be notified.
     *
     * @var bool
     */
    protected $_notify_userlog_actions;

    /**
     * A list of packages/actions for which activities will get notified.
     *
     * @var
     */
    protected $packages_actions;

    public function __construct(KObjectConfig $config)
    {
        $usergroups = KObjectConfig::unbox($config->usergroups);

        if (count($usergroups))
        {
            $manager = KObjectManager::getInstance();

            $query = $manager->getObject('lib:database.query.select');

            $query->table('users AS users')->join('user_usergroup_map AS xref', 'xref.user_id = users.id', 'INNER')
                  ->columns('users.email')->where('xref.group_id IN :groups')->bind(array('groups' => $usergroups));

            $emails = $manager->getObject('lib:database.adapter.mysqli')->select($query, KDatabase::FETCH_FIELD_LIST);

            if (!empty($emails)) {
                $config->bcc->append(array_diff($emails, KObjectConfig::unbox($config->bcc)));
            }
        }

        parent::__construct($config);

        $this->_notify_userlog_actions = (bool) $config->notify_userlog_actions;
        $this->_packages_actions        = KObjectConfig::unbox($config->packages_actions);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $translator = $this->getObject('translator');
        $site       = JFactory::getConfig()->get('sitename', 'sitename');

        $config->append(array(
            'subject'                => $translator->translate('PLG_LOGMAN_NOTIFIER_EMAIL_SUBJECT', array('site' => $site)),
            'notify_userlog_actions' => false,
            'notify_packages'        => array(),
            'template' => 'file://plugins/logman/notifier/html/email.html'
        ));

        parent::_initialize($config);
    }

    /**
     * Overridden to avoid sending login/logout notifications
     */
    public function notify(ComActivitiesActivityInterface $activity)
    {
        $result = parent::notify($activity);

        if ($this->_canNotify($activity) && !$result)
        {
            $app = JFactory::getApplication();

            if ($app->getCfg('debug'))
            {
                $app->enqueueMessage(JText::_('PLG_LOGMAN_NOTIFIER_ERROR'), 'notice');
            }
        }


        return $result;
    }

    protected function _canNotify(ComActivitiesActivityInterface $activity)
    {
        $result = parent::_canNotify($activity);

        $userlog_action = $activity->package == 'users' && in_array($activity->getActivityVerb(), array('login', 'logout'));

        // Check if we should ignore userlog actions.
        if ($result && !$this->_notify_userlog_actions && $userlog_action) {
            $result = false;
        }

        // Check if we should ignore page views actions.
        if ($result && ($activity->verb == 'read') && !$this->getConfig()->notify_page_views) {
            $result = false;
        }

        // Packages/actions check.
        if ($result && isset($this->_packages_actions))
        {
            $packages = $this->_packages_actions->packages;

            if (!in_array($activity->package, $packages)) {
                $result = false;
            }

            $actions = isset($this->_packages_actions->actions) ? $this->_packages_actions->actions : array();

            if ($result && $actions && !in_array(sprintf('%s.%s', $activity->name, $activity->verb), $actions)) {
                $result = false;
            }
        }

        return $result;
    }
}