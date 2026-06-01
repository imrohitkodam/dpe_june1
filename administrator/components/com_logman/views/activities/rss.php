<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Rss View
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanViewActivitiesRss extends KViewRss
{
    protected function _fetchData(KViewContext $context)
    {
        $context->data->sitename = JFactory::getApplication()->getCfg('sitename');
        $context->data->base_url = JURI::base();
        $context->data->language = JFactory::getLanguage()->getTag();

        parent::_fetchData($context);
    }
}