<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

class plgContentLogmanresources extends CMSPlugin
{
    public function onContentAfterDisplay($context, &$article, &$params, $page = 0)
    {
        if (!class_exists('Koowa')) return;

        if ($context == 'com_content.article')
        {
            $manager = KObjectManager::getInstance();

            $controller = $manager->getObject('com:activities.controller.resource');

            $model = $controller->getModel();

            $resource = $model->package('content')->name('article')->resource_id($article->id)->fetch();

            if ($resource->isNew())
            {
                $controller->add(array(
                    'package'     => 'content',
                    'name'        => 'article',
                    'resource_id' => $article->id,
                    'title'       => $article->title
                ));
            }
        }
    }

    /**
     * Overridden to only run if we have Nooku framework installed
     */
    public function update(&$args)
    {
        $return = null;

        if (class_exists('Koowa') && class_exists('KObjectManager') && (bool) ComponentHelper::getComponent('com_logman', true)->enabled)
        {
            try
            {
                $return = parent::update($args);
            }
            catch (Exception $e)
            {
                if (JDEBUG) {
                    Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
                }
            }
        }

        return $return;
    }
}