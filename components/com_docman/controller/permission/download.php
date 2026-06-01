<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Download controller permissions
 */
class ComDocmanControllerPermissionDownload extends ComDocmanControllerPermissionAbstract
{
    public function canRead()
    {
        $item   = $this->getModel()->fetch();
        $result = $this->getObject('user')->authorise('com_docman.download', 'com_docman');

        if ($item->isPermissible()) {
            $result = $item->canPerform('download');
        }

        if (!$result) {
            $result = $this->_isPlayerRequest();
        }

        if (!$result) {
            $result = $item->created_by == $this->getUser()->getId();
        }

        return (bool) $result;
    }

    public function canRender()
    {
        return $this->canRead();
    }

    protected function _isPlayerRequest()
    {
        $result = false;

        $controller = $this->getMixer();
        $request    = $controller->getRequest();
        $query      = $request->getQuery();

        // Check query for player JWT

        if ($query->has('auth_player'))
        {
            $token = $this->getObject('lib:http.token')->fromString($query->auth_player);

            if ($token->verify(JFactory::getConfig()->get('secret')))
            {
                // Check subject

                if ($subject = $token->getSubject()) {
                    $result = $controller->getUser()->getEmail() == $subject;
                }

                // Entity check

                $entity = $controller->getModel()->fetch();

                if (!$result && !$entity->isNew() && ($entity->count() === 1) && ($uuid = $token->getClaim('entity'))) {
                    $result = $uuid == $entity->uuid;
                }
            }
        }

        return $result;
    }
}