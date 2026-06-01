<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Download DOCman Logger
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanDocmanLoggerDownload extends ComLogmanActivityLogger
{
    protected $_docman_download_log = 'com:logman.docman.user.downloads';

    protected function _initialize(KObjectConfig $config)
    {
        $config->actions = array('after.render', 'after.redirect', 'after.compress');

        parent::_initialize($config);
    }

    public function log($action, KModelEntityInterface $object, KObjectIdentifierInterface $subject)
    {
        if ($action == 'after.compress')
        {
            // TODO Subject should be the whole command subject object and not only the identifier ... this way
            // I would be able to grab the controller request directly.
            $request = $this->getObject('request');

            if ($id = $request->getQuery()->id)
            {
                $documents = $this->getObject('com://admin/docman.model.documents')->id($id)->fetch();

                foreach ($documents as $document)
                {
                    $document->multidownload = $id;

                    parent::log($action, $document, $subject);
                }
            }
        }
        else
        {
            // Do not log streaming (with range headers) requests.
            if (!$this->_isStreaming() && $this->_canLog($object))
            {
                parent::log($action, $object, $subject);

                $this->_trackDownload($object);
            }
        }
    }

    public function getActivityData(KModelEntityInterface $object, KObjectIdentifierInterface $subject, $action = null)
    {
        $data = parent::getActivityData($object, $subject, $action);

        // Fix resource name and action.
        if ($data['name'] === 'download')
        {
            $data['name']   = 'document';
            $data['action'] = 'download';

            if ($object->multidownload) {
                $data['metadata'] = array('multidownload' => $object->multidownload);
            }
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

            if (in_array($object->id, $downloads)) {
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

            if (!in_array($object->id, $downloads)) $downloads[] = $object->id;

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
        return $command->getSubject()->getModel()->fetch();
    }

    protected function _getController($config = array())
    {
        if (JComponentHelper::isInstalled('com_leadman') && JComponentHelper::getComponent('com_leadman', true)->enabled) {
            $config['log_guests_activities'] = true;
        }

        return parent::_getController($config);
    }
}