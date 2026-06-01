<?php
/**
 * @package    TJ-Fields
 * @author     TechJoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

if (!key_exists('field', $displayData))
{
	return;
}

$field = $displayData['field'];

if ($field->value)
{
	foreach ($field->value as $userId)
	{
		$userData = Factory::getUser($userId);
		$assigneeUsers[] = $userData->name . '(' . $userData->email . ')';
	}

	if ($assigneeUsers)
	{
		echo implode('</br>', $assigneeUsers);
	}
}
