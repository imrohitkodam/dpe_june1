<?php
/**
 * @package     LMS_Shika
 * @subpackage  mod_lms_taglessons
 * @copyright   Copyright (C) 2014 - 2023 Techjoomla. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.techjoomla.com
 */
// No direct access.
defined('_JEXEC') or die;

// Include the helper file
require_once dirname(__FILE__) . '/helper.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ModuleHelper;

$displayLimit   = $params->get('displayLimit');

if ($displayLimit <= 0)
{
	$displayLimit = 4;
}

$currentDate    = Factory::getDate()->toSql();
$nullDate       = "-";

$ModLmsTagLessonsHelper = new ModLmsTagLessonsHelper;
$lessonsData            = $ModLmsTagLessonsHelper->getLessons($params);

require ModuleHelper::getLayoutPath('mod_lms_taglessons', $params->get('layout', 'default'));
