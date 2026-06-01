<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Download FILEman Logger
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanFilemanLoggerDownload extends PlgLogmanFilemanLoggerNode
{
    protected $_docman_download_log = 'com:logman.fileman.user.downloads';

    protected function _initialize(KObjectConfig $config)
    {
        $config->actions = array('after.render');

        parent::_initialize($config);
    }

    public function log($action, KModelEntityInterface $object, KObjectIdentifierInterface $subject)
    {
        // Do not log streaming (with range headers) requests.
        // Do not log streaming (with range headers) requests.
        if (!$this->_isStreaming() && $this->_canLog($object))
        {
            parent::log($action, $object, $subject);

            $this->_trackDownload($object);
        }
    }

    protected function _downloadLogExists($action, KModelEntityInterface $object, KObjectIdentifierInterface $subject)
    {
        $result = false;

        // TODO: Ideally I should be grabbing this from the controller implementing loggable,
        // TODO: same for the ::getAddress call below. A logger should perhaps have access to
        // TODO: loggable so that we may grab the behavior subject, aka the controller
        $user = $this->getObject('user');

        if ($user->isAuthentic())
        {
            $ip = $this->getObject('request')->getAddress();

            $model = $this->getObject('com:logman.model.activities');

            $data = $this->getActivityData($object, $subject, $action);

            if ($model->ip($ip)->user($user->getId())->package('fileman')->action('download')->row($data['row'])->count()) {
                $result = true;
            }
        }

        return $result;
    }

    public function getActivityData(KModelEntityInterface $object, KObjectIdentifierInterface $subject, $action = null)
    {
        $data = parent::getActivityData($object, $subject, $action);

        // Fix resource action.
        if ($data['name'] === 'file') {
            $data['action'] = 'download';
        }

        return $data;
    }

    /**
     * Checks if the current request is a streaming request
     *
     * The main difference with the Framework check is that here we also consider that a bytes=0- range
     * is not a streaming request since this is effectively the same as requesting the whole resource. Some
     * clients make use of such range headers to determine streaming capabilities while partially downloading
     * the file.
     *
     * @return bool True if the request is a streaming request, false otherwise
     */
    protected function _isStreaming()
    {
        $request = $this->getObject('request');

        $result = $request->isStreaming();

        if ($result)
        {
            // Check for Range bytes=0- range header
            $range = str_replace(' ', '', $request->getHeaders()->get('range'));

            if (stripos($range,'bytes=0-') === 0)  {
                $result = false;
            }
        }

        return $result;
    }

    protected function _canLog(KModelEntityInterface $object)
    {
        $result = true;

        // TODO: Ideally I should have access to loggable so that I grab the user from context
        $user = $this->getObject('user');

        if ($user->isAuthentic())
        {
            $downloads = $user->get($this->_docman_download_log, array());

            if (in_array($object->path, $downloads)) {
                $result = false;
            }
        }

        return $result;
    }

    protected function _trackDownload(KModelEntityInterface $object)
    {
        // TODO: Ideally I should have access to loggable so that I grab the user from context
        $user = $this->getObject('user');

        if ($user->isAuthentic())
        {
            $downloads = $user->get($this->_docman_download_log, array());

            if (!in_array($object->path, $downloads)) $downloads[] = $object->path;

            $user->set($this->_docman_download_log, $downloads);
        }
    }

    public function getActivityStatus(KModelEntityInterface $object, $action = null)
    {
        return (parent::getActivityStatus($object, $action) != KModelEntityInterface::STATUS_FAILED) ? 'downloaded' : null;
    }

    public function getActivityObject(KCommandInterface $command)
    {
        // Return the document being downloaded.
        return $command->result;
    }
}