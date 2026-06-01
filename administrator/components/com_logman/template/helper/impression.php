<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activity Template Helper
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanTemplateHelperImpression extends KTemplateHelperAbstract
{
    public function impression($config = array())
    {
        $config = new KObjectConfig($config);

        $impression = $config->row;

        return sprintf('<a href="%s">%s</a>', $this->route(array('url' => $impression->url)), $impression->url);
    }

    public function referrer($config = array())
    {
        $config = new KObjectConfig($config);

        $impression = $config->row;

        return sprintf('<a href="%s">%s</a>', $impression->referrer, $impression->referrer);
    }

    public function route($config = array())
    {
        $config = new KObjectConfig($config);

        $path = $this->getObject('request')->getSiteUrl()->toString(KHttpUrl::PATH);

        return sprintf('%s%s', $path, $config->url);
    }
}