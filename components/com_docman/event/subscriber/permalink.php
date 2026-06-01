<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2020 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanEventSubscriberPermalink extends KEventSubscriberAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'token_validation' => true,
            'priority'         => KEvent::PRIORITY_HIGH
        ));

        parent::_initialize($config);
    }

    /**
     * Re-route DOCman permalink
     * 
     * @param KEventInterface $event
     * @return void
     */
    public function onAfterApplicationInitialise(KEventInterface $event)
    {
        $request = $this->getObject('request');

        $path   = trim(implode('/', $request->getUrl()->getPath(true)), '/');
        $folder = trim(implode('/', (array) $request->getSiteUrl()->getPath(true)), '/');

        if ($folder && strpos($path, $folder) === 0) {
            $path = trim(substr($path, strlen($folder)), '/');
        }

        $path = explode('/', $path);

        if (isset($path[0]) && $path[0] === 'index.php') {
            array_shift($path); // Remove index.php from begining of path
        }

        if (isset($path[0]) && $path[0] === 'doclink')
        {
            $token = array_pop($path);
            $slug  = array_pop($path);

            $config = $this->getConfig();

            if ($config->token_validation)
            {
                // Validate token
                $secret = JFactory::getConfig()->get('secret');
                $token  = $this->getObject('lib:http.token')->fromString($token);

                if (!$token->verify($secret) || $slug != $token->getSubject()) {
                    trigger_error('Invalid JWT token', E_USER_ERROR);
                }

                $permission = $token->getClaim('auth_policy');
            }
            else $permission = null;

            $response = $this->getObject('response', [
                'request' => $request,
                'user'    => $this->getObject('user')
            ]);



            $controller = $this->getObject('com://site/docman.controller.download', array(
                'behaviors' => array(
                    'permissible' => array(
                        'permission' => $permission
                        )
                    ),
                'request'  => $request,
                'response' => $response
            ));

            $controller->slug($slug)->render();

            $response->send();
        }
    }
}