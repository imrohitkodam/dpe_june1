<?php
/**
 * @version     admin/autoloader.php 2020-05-28 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

function classLoader($class)
{
	if (stripos($class, 'Watchfulli') !== 0)
	{
		return false;
	}

	if ($class == "Watchfulli")
	{
		require_once WATCHFULLI_PATH . "/classes/watchfulli.php";

		return true;
	}

	$shortClassName = str_replace('Watchfulli', '', $class);
	$subDir         = '';
	foreach (['extensions'] as $item)
	{
		if (strpos($shortClassName, ucfirst($item)) !== 0)
		{
			continue;
		}

		$subDir         = $item . '/';
		$shortClassName = str_replace(ucfirst($item), '', $shortClassName);
		break;
	}

	$path = WATCHFULLI_PATH . '/classes/' . $subDir . strtolower($shortClassName) . '.php';

	if (!file_exists($path))
	{
		return false;
	}

	require_once $path;

	return true;
}
