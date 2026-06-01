<?php
/**
 * @version    SVN: <svn_id>
 * @package    Quick2cart
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;

define('GRADESCOES', "1");
define('GRADEHIGHEST', "2");
define('GRADEAVERAGE', "3");
define('GRADESUM', "4");

// Global icon constants.
if (JVERSION >= '3.0')
{
	define('LMS_DASHBORD_ICON_ORDERS', "icon-credit");
	define('LMS_DASHBORD_ICON_ITEMS', "icon-bars");
	define('LMS_DASHBORD_ICON_SALES', "icon-chart");
	define('LMS_DASHBORD_ICON_AVG_ORDER', "icon-credit");
	define('LMS_DASHBORD_ICON_ALL_SALES', "icon-chart");
	define('LMS_DASHBORD_ICON_USERS', "icon-users");
	define('LMS_DASHBORD_ICON_COURSE', "icon-book");
	define('LMS_DASHBORD_ICON_COURSE_COMPLETE', "icon-ok");
	define('LMS_DASHBORD_ICON_REVENUE', "icon-briefcase");
}
else
{
	define('LMS_DASHBORD_ICON_ORDERS', "icon-shopping-cart");
	define('LMS_DASHBORD_ICON_ITEMS', "icon-gift");
	define('LMS_DASHBORD_ICON_SALES', "icon-briefcase");
	define('LMS_DASHBORD_ICON_AVG_ORDER', "icon-th-large");
	define('LMS_DASHBORD_ICON_ALL_SALES', "icon-briefcase");
	define('LMS_DASHBORD_ICON_USERS', "icon-user");
	define('LMS_DASHBORD_ICON_COURSE', "icon-book");
	define('LMS_DASHBORD_ICON_COURSE_COMPLETE', "icon-ok");
	define('LMS_DASHBORD_ICON_REVENUE', "icon-briefcase");
}

define('LMS_COURSE_SCROLLTOLASTACCESSEDLESSON', "1");

HTMLHelper::script('media/com_tjlms/js/tjlms.min.js');
