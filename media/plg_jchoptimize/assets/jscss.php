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

if(!defined('_JEXEC'))
{
	define('_JEXEC', 1);
}

defined('_JEXEC') or die('Restricted access');

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/plugins/system/jch_optimize/bootstrap.php';

JchOptimize\Core\Output::getCombinedFile();
