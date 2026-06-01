<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_latestnews_js
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;


$jmailAlertsPluginPath    = JPATH_SITE . '/components/com_jmailalerts/helpers/plugins.php';
$jmaIntegrationHelperPath = JPATH_SITE . '/plugins/system/plg_sys_jma_integration/plg_sys_jma_integration/plugins.php';

// Include plugin helper file
// Else condition is needed when JMA integration plugin is used on sites where JMA is not installed
if (JFile::exists($jmailAlertsPluginPath))
{
	include_once $jmailAlertsPluginPath;
}
elseif (JFile::exists($jmaIntegrationHelperPath))
{
	include_once $jmaIntegrationHelperPath;
}

/**
 * Latest news plugin for JMailAlerts Component.
 *
 * @since  2.5.1
 */
class PlgEmailalertsJma_Latestnews_Js extends JMailAlertsPlugin
{
	public $extension = 'com_content';

	/**
	 * Plugin trigger to get latest matching records
	 *
	 * @param   string  $id               Userid or email id for user whom email will be sent
	 * @param   string  $lastEmailDate    Timestamp when last email was sent to that user
	 * @param   array   $userParams       Array of user's alert preference considering data tags
	 * @param   int     $fetchOnlyLatest  Decide to send only fresh content or not
	 *
	 * @return  array
	 *
	 * @since  2.5.0
	 */
	public function onEmail_jma_latestnews_js($id, $lastEmailDate, $userParams, $fetchOnlyLatest)
	{
		// This function is just a dummy
		// Let's call parent function
		return $this->onEmailTrigger($id, $lastEmailDate, $userParams, $fetchOnlyLatest);
	}

	/**
	 * Function to get latest matching records
	 *
	 * @param   string  $id               Userid or email id for user whom email will be sent
	 * @param   string  $lastEmailDate    Timestamp when last email was sent to that user
	 * @param   array   $userParams       Array of user's alert preference considering data tags
	 * @param   int     $fetchOnlyLatest  Decide to send only fresh content or not
	 *
	 * @return  array
	 *
	 * @since  2.5.0
	 */
	public function getList($id, $lastEmailDate, $userParams, $fetchOnlyLatest)
	{
		$list = array();

		// Init vars
		$replace       = JUri::root();
		$app           = JFactory::getApplication();
		$db            = JFactory::getDbo();
		$date          = JFactory::getDate();
		$nullDate      = $db->getNullDate();
		$now           = $date->toSql();

		// Load com_content router, and settings
		require_once JPATH_SITE . '/components/com_content/src/Helper/RouteHelper.php';
		$contentConfig = ComponentHelper::getParams('com_content');
		$access        = !$contentConfig->get('show_noauth');

		// Get user and userid
		if ($id === 0)
		{
			$user    = JFactory::getUser($id);
			$userId  = 0;
		}
		else
		{
			$user   = JFactory::getUser($id);
			$userId = (int) $user->get('id');
		}

		// Get plugin params -> admin params (not shown in frontend).
		$allowedCategoriesMethod = $this->params->get('allowed_categories_method', 'all_selected_categories');
		$show_front              = $this->params->get('show_front', 1);
		$ordering                = $this->params->get('ordering');
		$introtext_count         = (int) $this->params->get('introtext_count', 200);
		$show_introtext          = (int) $this->params->get('show_introtext', 0);
		$show_date               = (int) $this->params->get('show_date', 0);
		$show_author             = (int) $this->params->get('show_author', 0);
		$show_author_alias       = (int) $this->params->get('show_author_alias', 0);
		$show_category           = (int) $this->params->get('show_category', 0);

		// Get plguin params -> legacy/user params,
		// ** To be used as default when plugin is used with EB or InviteX like components
		$userCategoriesMethod = $this->params->get('user_categories_method', 'only_selected_categories');
		$catid                = $this->params->get('catid', '');
		$count         		  = (int) $this->params->get('count');
		$author               = (int) $this->params->get('author_id', '');

		// Now let's check if user has set his own preferences in $userParams for leagcy/user params

		// [1] Check if user has set count in user preferences
		if ($count)
		{
			$count =  $count;
		}
		else
		{
			$count = 5;
		}

		// [2] Check if user's category selection option is set in user preferences
		if (isset($userParams['user_categories_method']))
		{
			$userCategoriesMethod = $userParams['user_categories_method'];
		}

		// [3] Check if user has set author in user preferences
		if (isset($userParams['author_id']))
		{
			$author = $userParams['author_id'];
		}

		// [4.1] Check if user wants to subscribe to only the categories that he has selected
		if ($userCategoriesMethod == 'only_selected_categories')
		{
			// Check if user had choosen some categories
			if (isset($userParams['catid']))
			{
				// $catid = trim($userParams['catid']);
				$catid = $this->params->get('category');
			}
		}

		// [4.2.A] If admin has set allowed categories are to be auto selected as per user's ACL settings
		if ($allowedCategoriesMethod == 'as_per_acl_categories')
		{
			// If user has chosen 'All categories' option, get all categories as per his ACL, no matter if he has chosen any categories or not
			if ($userCategoriesMethod == 'all_categories')
			{
				$catid = $this->getUserCategories($user);
			}
		}
		// [4.2.B] If admin has set allowed categories are to be manually selected
		else
		{
			// If user has chosen 'All categories' option, get all categories from the 'list of categories, set by admin'
			// We will further trim down that list by applygin user ACL on that
			if ($userCategoriesMethod == 'all_categories')
			{
				// Get allowed categories set by the admin in admin params
				$cats = $this->params->get('category');

				if (is_array($cats))
				{
					$cats = implode(',', $cats);
				}
				
				$catid = $this->getUserCategories($user, $cats);

				if (is_array($catid))
				{
					$catid = implode(',', $catid);
				}
			}
		}

		// Published and date filter
		$where = ' a.state = 1';
		//. ' AND ( a.publish_up = ' . $db->Quote($nullDate) . ' OR a.publish_up <= ' . $db->Quote($now) . ' )'
		//. ' AND ( a.publish_down = ' . $db->Quote($nullDate) . ' OR a.publish_down >= ' . $db->Quote($now) . ' )';
//
		// Author Filter
		/*switch ($author)
		{
			case 'by_me':
				if ($userId)
				{
					$where .= ' AND (created_by = ' . (int) $userId . ' OR modified_by = ' . (int) $userId . ')';
				}
				break;

			case 'not_me':
				$where .= ' AND (created_by <> ' . (int) $userId . ' AND modified_by <> ' . (int) $userId . ')';
				break;
		}*/

		// *IMP - Ordering
		

		// Ordering - [2] - Then sort by user set preferences
		switch ($ordering)
		{
			case 'm_dsc':
			$where_ordering .= ' a.modified DESC';
			break;

			case 'p_dsc':
			$where_ordering .= ' a.publish_up DESC ';
			break;

			case 'c_dsc':
			default:
			$where_ordering .= ' a.created DESC ';
			break;
		}

		// Ordering - [1] - If we are showing category, First sort results by category name
		if ($show_category)
		{
			$where_ordering .= ' , category ASC ';
		}
		else
		{
			$where_ordering .= ' ';
		}
		
		// Category filter
		if ($catid)
		{
			if (is_array($catid))
			{
				$catCondition = ' AND (cc.id=' . implode(' OR cc.id=', $catid) . ')';
			}
			else
			{
				$ids = explode(',', $catid);
				$ids = ArrayHelper::toInteger($ids);
				$catCondition = ' AND (cc.id=' . implode(' OR cc.id=', $ids) . ')';
			}
		}

		// Introtext filter
		$intro = '';

		if ($show_introtext)
		{
			$intro = " a.introtext AS intro, ";
		}

		$currentDateTime = Factory::getDate();

		// Format the date and time according to your preference
		$currentDateTime = $currentDateTime->format('Y-m-d H:i:s');

		// ACL filter for artcles
		$groups   = implode(',', $user->getAuthorisedViewLevels());
		$checkacc = ' a.access IN (' . $groups . ')';

		// Get content items/articles
		$query = 'SELECT ' . $intro . ' a.id, a.catid, a.title, a.created,a.modified, a.alias, a.created_by_alias, a.language, u.name, u.username, cc.access, cc.title as category, ' .
		' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug, ' .
		' CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(":", cc.id, cc.alias) ELSE cc.id END as catslug ' .
		' FROM #__content AS a ' .
		' LEFT JOIN #__users AS u ON u.id=a.created_by ' .
		($show_front == '0' ? ' LEFT JOIN #__content_frontpage AS f ON f.content_id = a.id' : '') .
		' INNER JOIN #__categories AS cc ON cc.id = a.catid' .
		' WHERE ' . $where .
		($access ? ' AND ' . $checkacc : '') .
		($catid ? $catCondition : '') .
		// ($show_front == '0' ? ' AND f.content_id IS null ' : '') .
		' AND cc.published = 1 AND a.publish_up <= '. $db->Quote($currentDateTime);

		// Get only the fresh content
		if ($fetchOnlyLatest)
		{
			// Depending on ordering, change where clause
			switch ($ordering)
			{
				case 'm_dsc':
				$query .= " AND a.modified >= " . $db->Quote($lastEmailDate);
				break;

				case 'p_dsc':
				$query .= " AND a.publish_up >= " . $db->Quote($lastEmailDate);
				break;

				case 'c_dsc':
				default:
				$query .= " AND a.created >= " . $db->Quote($lastEmailDate);
				break;
			}
		}


		  // Sort by
		$query .= ' ORDER BY '  . $where_ordering .' limit 0,'. $count;

          // Use user's preferred value for count
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		$splitArrays = [];

			// Loop through each object in the original array
		foreach ($rows as $key => $object) {
			    // Get the catid property value of the current object
			$catid = $object->catid;
			$uniqueCatid[] = $object->catid;

			    // If this is the first object with this catid, create a new array
			if (!isset($splitArrays[$catid])) {
				$splitArrays[$catid] = [];
			}

			    // Add the current object to the array for this catid
			$splitArrays[$catid][] = $object;


		}

		$uniqueCategory = array_unique($uniqueCatid);
		foreach($uniqueCategory as $key => $uniqId)
		{
			usort($splitArrays[$uniqId], function($a, $b) {
				if ($this->params->get('ordering') == 'm_dsc'){

					return [$b->modified]
					<=>
					[$a->modified];
				}
				else{

					return [$b->created]
					<=>
					[$a->created];

				}

			});
		}
		$rows = array_reduce($splitArrays, function($result, $item) {
			return array_merge($result, $item);
		}, array());

		// No output.
		if (!$rows)
		{
			return $list;
		}

		$i = 0;

		// If email is previewed from backend, do not generate sef urls as SEF won't work in backend
		if ($app->isClient('administrator'))
		{
			foreach ($rows as $row)
			{ 
				$list[$i] = new stdclass;
				
				// Hack for dpe can go in Core
				$newUrl = ContentHelperRoute::getArticleRoute($row->id.':'.$row->alias, $row->catid);

				$list[$i]->link = Route::link("site", $newUrl);
				$baseUrl = rtrim(Uri::root(), "/");

				$list[$i]->link = $baseUrl . $list[$i]->link;

				$list[$i]->title = htmlspecialchars($row->title);

				if ($show_author_alias && $row->created_by_alias)
				{
					$list[$i]->author = htmlspecialchars($row->created_by_alias);
				}
				else
				{
					$list[$i]->author = htmlspecialchars($row->name);
				}

				$list[$i]->date     = ($this->params->get('ordering') == 'm_dsc')?htmlspecialchars($row->modified):htmlspecialchars($row->created);
				$list[$i]->catid    = htmlspecialchars($row->catid);
				$list[$i]->category = htmlspecialchars($row->category);

				if ($show_introtext)
				{
					$list[$i]->intro = substr(strip_tags($row->intro), 0, $introtext_count) . "...";
				}

				$i++;
			}
		}
		// If email is previewed/generated from frontend, generate sef urls.
		else
		{

			foreach ($rows as $row)
			{
				$list[$i] = new stdclass;

				// Hack for dpe can go in Core
				$newUrl = ContentHelperRoute::getArticleRoute($row->id.':'.$row->alias, $row->catid);
				$list[$i]->link = Route::link("site", $newUrl);
                                // Get the base URL
				$baseUrl = rtrim(Uri::root(), "/");
              // Construct the complete article link
				$list[$i]->link = $baseUrl . $list[$i]->link;

				$list[$i]->title = htmlspecialchars($row->title);

				if ($show_author_alias && $row->created_by_alias)
				{
					$list[$i]->author = htmlspecialchars($row->created_by_alias);
				}
				else
				{
					$list[$i]->author = htmlspecialchars($row->name);
				}

				$list[$i]->date     = ($this->params->get('ordering') == 'm_dsc')?htmlspecialchars($row->modified):htmlspecialchars($row->created);
				$list[$i]->catid    = htmlspecialchars($row->catid);
				$list[$i]->category = htmlspecialchars($row->category);

				if ($show_introtext)
				{
					$list[$i]->intro = substr(strip_tags($row->intro), 0, $introtext_count) . "...";
				}

				$i++;
			}
			
		}

		return $list;
	}

	/**
	 * Method to get categories as per user ACL
	 *
	 * @param   object  $user           The userid or email of the user to whom email is being sent.
	 * @param   string  $catListFilter  Comma separated list of category ids allowed.
	 *
	 * @return  string
	 *
	 * @since   2.5.6
	 */
	protected function getUserCategories($user, $catListFilter = '')
	{
		$db   = JFactory::getDbo();
		$cats = '';

		$catSql = "SELECT id
		 FROM #__categories
		 WHERE published=1
		 AND extension='com_content'";

		 if ($catListFilter)
		 {
		 	$catSql .= "AND id IN (" . $catListFilter . ")";
		 }

		 $groups  = implode(',', $user->getAuthorisedViewLevels());
		 $catSql .= ' AND access IN (' . $groups . ')';

		// Run query, get categories
		 $db->setQuery($catSql);
		 $cats = $db->loadColumn();

		 if (is_array($cats))
		 {
		 	$cats = implode(',', $cats);
		 }

		 return $cats;
		}
	}
