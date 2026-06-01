<?php
/**
 * @package         Articles Anywhere
 * @version         17.2.10PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

namespace RegularLabs\Plugin\System\ArticlesAnywhere\DataGroups\Helpers;

defined('_JEXEC') or die;



class VideoVimeo extends VideoObject
{
    static $regex            = '(?:vimeo\.com/|player\.vimeo\.com/video/)(?<id>[0-9]+)(?<query>.*)';
    static $url_prefix       = 'https://vimeo.com/';
    static $url_prefix_embed = 'https://player.vimeo.com/video/';
    static $url_prefix_short = 'https://vimeo.com/';
}
