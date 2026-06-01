<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Abstract Activity Notifier Class.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
abstract class ComLogmanActivityNotifierAbstract extends KObject implements ComLogmanActivityNotifierInterface
{
    /**
     * The activity renderer.
     *
     * @var mixed
     */
    protected $_renderer;

    /**
     * Site URL for rendering activity messages.
     *
     * @var KHttpUrl
     */
    protected $_url;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_renderer = $config->renderer;

        if ($url = $config->url) {
            $this->setUrl($url);
        }

        if ($activity = $config->activity) {
            $this->setActivity($activity);
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('renderer' => 'com://admin/logman.template.helper.activity'));
        parent::_initialize($config);
    }

    /**
     * Sends a notification about an activity.
     *
     * @param ComActivitiesActivityInterface $activity The activity to notify about.
     *
     * @return bool True if the notification was successfully sent, false otherwise.
     */
    abstract public function notify(ComActivitiesActivityInterface $activity);

    /**
     * Renders an activity message.
     *
     * @param ComActivitiesActivityInterface $activity The activity.
     * @param bool                           $html     Whether to render the activity an HTML or plain text activity
     *                                                 message.
     *
     * @return string The activity message.
     */
    protected function _renderActivity(ComActivitiesActivityInterface $activity, $html = true)
    {
        if (!$this->_renderer instanceof ComActivitiesActivityRendererInterface)
        {
            $renderer = $this->getObject($this->_renderer);

            $renderer->getTemplate()->registerFunction('url', array($this, 'getUrl'));

            $this->_renderer = $renderer;
        }

        return $this->_renderer->render($activity, array(
            'html' => $html,
            'fqr'  => $this->getUrl() ? true : false
        ));
    }

    /**
     * Site URL setter.
     *
     * @param $url The site URL.
     */
    public function setUrl(KHttpUrl $url)
    {
        // Only keep the authority segment.
        $this->_url = $this->getObject('lib:http.url', array('url' => $url->toString(KHttpUrl::AUTHORITY)));
    }

    /**
     * Site URL getter.
     *
     * @return KHttpUrl|null The site URL, null if no URL is set.
     */
    public function getUrl()
    {
        return $this->_url;
    }
}