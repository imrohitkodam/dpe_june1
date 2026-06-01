<?php

/**
 * JCH Optimize - Performs several front-end optimizations for fast downloads
 *
 * @package   jchoptimize/joomla-platform
 * @author    Samuel Marshall <samuel@jch-optimize.net>
 * @copyright Copyright (c) 2020 Samuel Marshall / JCH Optimize
 * @license   GNU/GPLv3, or later. See LICENSE file
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */
//$start = microtime(true);


defined('_JEXEC') or die('Restricted access');

$DIR = dirname(dirname(dirname(dirname(__FILE__))));

if (file_exists($DIR . '/defines.php'))
{
        include_once $DIR . '/defines.php';
}

if (!defined('_JDEFINES'))
{
        define('JPATH_BASE', $DIR);
        require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_BASE . '/includes/framework.php';

require_once JPATH_PLUGINS . '/system/jch_optimize/autoload.php';
