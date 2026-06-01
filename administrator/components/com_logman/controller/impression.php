<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Impression Controller
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanControllerImpression extends KControllerModel
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->addCommandCallback('after.add', '_addActivity');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'toolbars'  => array('menubar'),
            'behaviors' => array(
                'restrictable' => array('redirect_url' => 'index.php?option=com_logman&view=config')
            )
        ));

        parent::_initialize($config);
    }

    protected function _addActivity(KControllerContextInterface $context)
    {
        $impression = $context->result;

        $controller = $this->getObject('com://admin/logman.controller.activity', [
            'log_guests_activities' => $this->getConfig()->log_guests_activities
        ]);

        // Create the activity entity

        $metadata = array();

        if ($impression->title) {
            $metadata['url'] = $impression->url;
        }

        $activity = $controller->add(array(
            'type'        => 'com',
            'application' => 'site',
            'package'     => $impression->package,
            'name'        => $impression->name,
            'action'      => 'read',
            'row'         => $impression->row,
            'title'       => $impression->title ?: $impression->url,
            'status'      => 'read',
            'created_on'  => $impression->created_on,
            'created_by'  => $impression->created_by,
            'metadata'    => $metadata
        ));

        if ($activity && !$activity->isNew())
        {
            // Create the relation entry

            $this->getObject('com://admin/logman.controller.activities_impression')
                 ->add(array('logman_activity_id' => $activity->id, 'logman_impression_id' => $impression->id));
        }
    }

    protected function _beforeAdd(KControllerContextInterface $context)
    {
        $request = $this->getObject('request');
        $data    = $context->getRequest()->getData();

        if ($referrer = $request->getReferrer(true))
        {
            $referrer       = $referrer->toString(KHttpUrl::PATH);
            $data->internal = 1;
        }
        else
        {
            $referrer       = $request->getReferrer(false);
            $data->internal = 0;
        }

        $data->referrer = isset($data->referrer) ? $data->referrer : $referrer;

        $path = $request->getSiteUrl()->toString(KHttpUrl::PATH);

        $data->url      = isset($data->url) ? $data->url : str_replace($path, '', $request->getUrl()->toString(KHttpUrl::PATH));
        $data->ip       = isset($data->ip) ? $data->ip : $request->getAddress();

        $headers = $request->getHeaders();

        if ($headers->has('user-agent')) {
            $data->metadata = array('headers' => array('user-agent' => $headers->get('user-agent')));
        }


        if ($secret = JFactory::getConfig()->get('secret')) {
            $data->session_hash = md5(sprintf('%s.%s', $this->getUser()->getSession()->getId(), $secret));
        }
    }
}