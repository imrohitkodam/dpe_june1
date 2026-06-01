<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2020 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanEventSubscriberDoclink extends KEventSubscriberAbstract
{
    /**
     * Re-route old Document alias
     * 
     * @param KEventInterface $event
     * @return void
     */
    public function onException(KEventInterface $event)
    {
        $request = $this->getObject('request');

        $menu = \Joomla\CMS\Factory::getApplication()->getMenu()->getActive();

        if ($menu && $menu->component == 'com_docman')
        {
            $app = JFactory::getApplication();
            $is_download = 'file' == $request->query->slug;

            if ($app->getCfg('sef'))
            {
                $path = trim(implode('/', $request->getUrl()->getPath(true)), '/');
                $path = explode('/', $path);

                if (isset($path[0]) && $path[0] === 'index.php') {
                    array_shift($path); // Remove index.php from begining of path
                }

                if ($is_download)
                {
                    // Document file e.g. DOClink
                    array_pop($path);
                    $slug = array_pop($path);
                    $alias = substr($slug, strpos($slug, '-') + 1);
                    list($id) = explode('-', $slug);
                }
                else
                {
                    // Detail page
                    $slug  = array_pop($path);
                    $alias = substr($slug, strpos($slug, '-') + 1);
                    list($id) = explode('-', $slug);
                }
            }
            else
            {
                $slug = $request->query->alias;
                $alias = substr($slug, strpos($slug, '-') + 1);

                list($id) = explode('-', $slug);
            }

            // Check if alias no longer exists and fetch the current alias
            if ((int) $id && !$this->getObject('com://admin/docman.model.documents')->slug($alias)->count())
            {
                $document = $this->getObject('com://admin/docman.model.documents')->id($id)->fetch();

                // Build the updated URL of the document
                if ($app->getCfg('sef'))
                {
                    $path[] = "{$id}-{$document->slug}";

                    if ($is_download) {
                        $path[] = 'file';
                    }

                    $request->getUrl()->setPath('/' . implode('/', $path));
                }
                else
                {
                    $query = $request->getUrl()->getQuery(true);
                    $query['alias'] = "{$id}-{$document->slug}";
                    $request->getUrl()->setQuery($query);
                }

                $response = $this->getObject('response');
                $response->setRedirect($request->getUrl());
                $response->send();
            }
        }
    }
}