<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activity Controller
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanControllerRoute extends KControllerModel
{
    /**
     * Performs checks before adding a new route
     *
     * @param KControllerContextInterface $context The context object
     *
     * @return bool True if route may be safely created, false otherwise
     */
    protected function _beforeAdd(KControllerContextInterface $context)
    {
        $result = true;

        $data = $context->getRequest()->getData();

        // Set path and page if not set yet

        $data->path = isset($data->path) ? $data->path : $this->getPath();
        $data->page = isset($data->page) ? $data->page : (($page = $this->getPage()) ? $page->id : null);

        if ($data->path && $data->page)
        {
            $route = $this->getModel()
                          ->row($data->row)
                          ->name($data->name)
                          ->package($data->package)
                          ->page($data->page)
                          ->sort('created_on')
                          ->direction('desc')
                          ->fetch();

            if (!$route->isNew())
            {
                // Double check that this is not an obsolete path

                /*if ($url = $route->url)
                {
                    if ($data->path != $url->toString(KHttpUrl::PATH))
                    {
                        // Redirect to the most current version of the route

                        $this->redirect(array('url' => $url));
                    }
                }*/

                if ($this->_minimizePath($data->path) == $route->path) {
                    $result = false; // The route already exists, do not create one
                }
            }
            else
            {
                // Avoid adding routes that do not exist. Joomla re-directs to the default menu item if it
                // cannot resolve the one available in the path

                $page = $this->getPage($data->page);

                $path = trim($this->_minimizePath($data->path), '/');

                if (!$page || strpos($path, $page->route) !== 0)
                {
                    $this->redirect(); // Attempt to re-direct

                    $result = false; // Do not add this route
                }
            }
        }
        else $result = false;

        // Minimize path before adding new route
        $data->path = $this->_minimizePath($data->path);

        return $result;
    }

    protected function _beforeRedirect(KControllerContextInterface $context)
    {
        $result = true;

        if (!$context->param->url)
        {
            $path = $this->getPath(false) ?: '/';

            $route = $this->getObject($this->getModel()->getIdentifier())->path($path)->fetch();

            if (!$route->isNew() && ($url = $route->url)) {
                $context->param->url = $url;
            } else {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Redirect action
     *
     * Performs a permanent redirect
     *
     * @param KControllerContextInterface $context The context object
     */
    protected function _actionRedirect(KControllerContextInterface $context)
    {
        $response = $this->getObject('response', array(
            'request' => $this->getObject('request'),
            'user'    => $this->getObject('user')
        ));

        $response->setStatus(KHttpResponse::MOVED_PERMANENTLY)->setRedirect($context->param->url->toString())->send();
    }

    /**
     * Page getter
     *
     * @param int|null $id  The page ID or null to get the active one
     *
     * @return mixed The current page, null if there isn't any
     */
    public function getPage($id = null)
    {
        $menu = JFactory::getApplication()->getMenu();

        if ($id) {
            $page = $menu->getItem($id);
        } else {
            $page = $menu->getActive();
        }

        return $page;
    }

    /**
     * Path getter
     *
     * @param bool $full Return full path (Folder and prefix if available) when true
     *
     * @return string The current request path
     */
    public function getPath($full = true)
    {
        $path = $this->getObject('request')->getUrl()->toString(KHttpUrl::PATH);

        if (!$full) {
           $path = $this->_minimizePath($path);
        }

        return $path;
    }

    /**
     * Returns a minimized path by stripping folder and prefix from the path if present
     *
     * @param string $path The path to minimize
     *
     * @return string The minimized path
     */
    protected function _minimizePath($path)
    {
        if ($path)
        {
            $folder = $this->getObject('request')->getSiteUrl()->toString(KHttpUrl::PATH);
    
            if ($folder) {
                $path = str_replace($folder, '', $path);
            }
    
            if (strpos($path, '/index.php') === 0) {
                $path = str_replace('/index.php', '', $path);
            }
        }

        return $path;
    }
}