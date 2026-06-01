<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Notifiable Controller Behavior.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */

class ComLogmanControllerBehaviorNotifiable extends KControllerBehaviorAbstract
{
    /**
     * A queue of notifier objects.
     *
     * @var KObjectQueue
     */
    protected $_notifiers;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_notifiers = $this->getObject('lib:object.queue');

        foreach ($config->notifiers as $notifier => $config)
        {
            $url = $this->getObject('request')->getUrl();

            if (is_numeric($notifier))
            {
                $notifier = $config;
                $config = array('url' => $url);
            }
            else $config->append(array('url' => $url));

            if (!$notifier instanceof ComLogmanActivityNotifierInterface) {
                $notifier = $this->getObject($notifier, KObjectConfig::unbox($config));
            }

            $this->addNotifier($notifier);
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('notifiers' => array()));
        parent::_initialize($config);
    }

    /**
     * Adds a notifier to the queue.
     *
     * @param ComLogmanActivityNotifierInterface $notifier The notifier.
     * @return KControllerInterface
     */
    public function addNotifier(ComLogmanActivityNotifierInterface $notifier)
    {
        $this->_notifiers->enqueue($notifier, self::PRIORITY_NORMAL);
        return $this->getMixer();
    }

    protected function _afterAdd(KControllerContextInterface $context)
    {
        $activity = $context->result;

        if ($activity instanceof ComActivitiesActivityInterface)
        {
            foreach ($this->_notifiers as $notifier) {
                $notifier->notify($activity);
            }
        }
    }
}