<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Route Model Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanModelEntityRoute extends KModelEntityRow
{
    /**
     * URL getter
     *
     * Computes the latest URL for the current route object. The route menu item is
     * used to generate the URL in a first attempt. If a URL isn't found for the menu item
     * the system will attempt to build a URL for the resource by using any available menu
     * item
     *
     * @return ComKoowaDispatcherRouterRoute|null The URL object if found, null otherwise
     */
    public function getPropertyUrl()
    {
        $activity = $this->getObject('com://admin/logman.model.activities')->create([
            'row'     => $this->row,
            'package' => $this->package,
            'name'    => $this->name,
            'context' => 'site'
        ]);

        $url = $activity->getActivityObject()->getUrl();

        if ($url)
        {
            // Try filtering by page

            $activity->page = $this->page;

            $url = $activity->getActivityObject()->getUrl() ?: $url;
        }

        return $url;
    }
}