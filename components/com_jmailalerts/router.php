<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

/**
 * Build route
 *
 * @param   array  &$query  A named array
 *
 * @return  array
 */
function jmailalertsBuildRoute(&$query)
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
 * JMA Parse route
 *
 * Formats:
 * index.php?/jmailalerts/task/id/Itemid
 * index.php?/jmailalerts/id/Itemid
 *
 * @param   array  $segments  A named array
 *
 * @return  array
 */
function jmailalertsParseRoute($segments)
{
	$vars = array();

	// View is always the first element of the array
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
}
