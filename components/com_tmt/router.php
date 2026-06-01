<?php
/**
 * @version    SVN: <svn_id>
 * @package    TMT
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2012-2013 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Buildroute function
 *
 * @param   array  &$query  A named array
 *
 * @return    array
 */
/*function tmtBuildRoute(&$query)
{
	$segments = array();

	if (isset($query['task']))
	{
		$segments[] = implode('/', explode('.', $query['task']));
		unset($query['task']);
	}

	if (isset($query['id']))
	{
		$segments[] = $query['id'];
		unset($query['id']);
	}

	return $segments;
}

/**
 * Parseroute function
 *
 * @param   array  $segments  A named array
 *
 * @return   array
 *
 * Formats:
 * index.php?/tmt/task/id/Itemid
 * index.php?/tmt/id/Itemid
 */
/*function tmtParseRoute($segments)
{
	$vars = array();

	// View is always the first element of the array.
	$count = count($segments);

	if ($count)
	{
		$count--;
		$segment = array_pop($segments);

		if (is_numeric($segment))
		{
			$vars['id'] = $segment;
		}
		else
		{
			$count--;
			$vars['task'] = array_pop($segments) . '.' . $segment;
		}
	}

	if ($count)
	{
		$vars['task'] = implode('.', $segments);
	}

	return $vars;
}*/
