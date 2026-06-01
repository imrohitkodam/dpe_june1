<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

/**
 * Interface for AutoTweet plugins.  All extension plugins must implement this inteface.
 *
 * @since       1.0
 */
interface IAutotweetPlugin
{
    /**
     * Get data for article/entry. Returns an array. Must be implemented by concrete plugin class.
     *
     * @param string $id       Param
     * @param string $typeinfo Param
     *
     * @return array (
     *               'title'	=> 'Title of the article, event, forum post, ...'
     *               'text'	=> 'Text for the message.'
     *               'hashtags'	=> 'hashtags (They are inserted automatically by AutoTweet for channels supporting hashtags.'
     *               'url'		=> 'The complete routed URL of the article. (not shortened)'
     *               'image_url'	=> 'URL for the image to show (if there is an image)'
     *               'introtext'	=> 'Intoduction text.'
     *               'fulltext'	=> 'Enhanced text for the description (e. g. Facebook). Not available for all channels.'
     *               'catids'	=> 'array with category, container, etc. ids for the article, post...'
     *               'author'	=> 'id of creator or editor of the article'
     *               'event'	=> array (
     *               'location'		=> 'Location of the event'
     *               'street'		=> 'Street is optional'
     *               'city'			=> 'Must be a valid City for Facebook'
     *               'privacy'		=> 'OPEN, CLOSED or SECRET'
     *               'start_time'	=> 'yyyy-mm-dd hh:ss'
     *               'end_time'	=> 'yyyy-mm-dd hh:ss'
     *               );
     *               'is_valid'	=> 'true, when post has a valid databse entry'
     *               );
     */
    public function getData($id, $typeinfo);

    /**
     * getExtendedData - To return the data array from the native object.
     *
     * @param string $id             param
     * @param string $typeinfo       param
     * @param string &$native_object Param
     *
     * @return array
     */
    // Public function getExtendedData($id, $typeinfo, &$native_object); -- optional

    /**
     * Returns publish mode for plugin (default is true, so this works also for plugin without autopublish option).
     *
     * @return bool true, if autopublishing is enabled for plugin
     */
    public function isAutopublish();

    /**
     * Returns url mode for plugin.
     *
     * @return int urlmode (0 =  no url, 1 = show at the beginning, 2 = show at the end of message)
     */
    public function getShowUrlMode();
}
