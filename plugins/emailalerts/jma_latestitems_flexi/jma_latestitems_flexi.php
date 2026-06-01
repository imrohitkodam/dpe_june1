<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_latestitems_flexi
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

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
 * Latest items flexi content plugin for JMailAlerts Component.
 *
 * @since  2.5.1
 */
class PlgEmailalertsJma_Latestitems_Flexi extends JMailAlertsPlugin
{
	public $extension = 'com_flexicontent';

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
	public function onEmail_jma_latestitems_flexi($id, $lastEmailDate, $userParams, $fetchOnlyLatest)
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

		// If no userid or no guest user, return blank array for HTML and CSS.
		if ($id === null)
		{
			return $list;
		}

		require_once JPATH_SITE . '/components/com_flexicontent/helpers/route.php';

		$app    = JFactory::getApplication();
		$db     = JFactory::getDbo();
		$user   = JFactory::getUser($id);
		$userId = (int) $user->get('id');

		// Get user preferences for this plugin parameters(shown in frontend)
		$count = (int) $userParams['count'];
		$catid = trim($userParams['catid']);

		// $secid = '';
		// $aid   = $user->get('aid');

		// Get plugin parameters(not shown in frontend)
		$ordering   = $this->params->get('ordering');
		$show_front = $this->params->get('show_front', 0);

		$introtext_count   = (int) $this->params->get('introtext_count', 150);
		$show_introtext    = (int) $this->params->get('show_introtext', 0);
		$show_date         = (int) $this->params->get('show_date', 0);
		$show_author       = (int) $this->params->get('show_author', 0);
		$show_author_alias = (int) $this->params->get('show_author_alias', 0);
		$show_category     = (int) $this->params->get('show_author', 0);

		$contentConfig = JComponentHelper::getParams('com_flexicontent');
		$access        = !$contentConfig->get('show_noauth');

		$nullDate = $db->getNullDate();
		$date     = JFactory::getDate();
		$now      = $date->toSQL();

		$replace = JUri::root();

		// Date filter
		$where = 'a.state = 1' . ' AND ( a.publish_up = ' . $db->Quote($nullDate)
		. ' OR a.publish_up <= ' . $db->Quote($now) . ' )' . ' AND ( a.publish_down = '
		. $db->Quote($nullDate) . ' OR a.publish_down >= ' . $db->Quote($now) . ' )';

		// Author Filter
		switch ($userParams['user_id'])
		{
			case 'by_me':
				$where .= ' AND (created_by = ' . (int) $userId . ' OR modified_by = ' . (int) $userId . ')';
				break;
			case 'not_me':
				$where .= ' AND (created_by <> ' . (int) $userId . ' AND modified_by <> ' . (int) $userId . ')';
				break;
		}

		// Ordering
		switch ($ordering)
		{
			case 'm_dsc':
				$ordering = 'a.modified DESC, a.created DESC';
				break;
			case 'c_dsc':
			default:
				$ordering = 'a.created DESC';
				break;
		}

		// Category filter
		$catCondition = '';

		if ($catid)
		{
			$ids = explode(',', $catid);
			$ids = ArrayHelper::toInteger($ids);

			$catCondition = 'AND (cc.id=' . implode(' OR cc.id=', $ids) . ')';
		}

		// Section filter

		/*if ($secid)
		{
			$ids = explode(',', $secid);
			$ids = ArrayHelper::toInteger($ids);

			$secCondition = ' AND (s.id=' . implode(' OR s.id=', $ids) . ')';
		}*/

		// Introtext filter
		$intro = '';

		if ($show_introtext)
		{
			$intro = "a.introtext AS intro,";
		}

		$rows = null;

		// The categories are selected
		if ($catid)
		{
			$groups   = implode(',', $user->getAuthorisedViewLevels());
			$checkacc = 'a.access IN (' . $groups . ')';
			$query    = 'SELECT ' . $intro . '  a.id, a.catid, a.title, a.created, a.created_by_alias,
			 u.name, u.username, cc.access, cc.title
			 as category FROM #__content AS a LEFT JOIN #__users AS u ON u.id=a.created_by
			 INNER JOIN #__categories AS cc ON cc.id = a.catid
			 WHERE ' . $where . ($access ? ' AND ' . $checkacc : '') . ($catid ? $catCondition : '') . '
			 AND cc.published = 1';

			// Get only fresh content
			if ($fetchOnlyLatest)
			{
				$query .= " AND a.created >= ";
				$query .= $db->Quote($lastEmailDate);
			}

			$query .= ' ORDER BY ' . $ordering;

			// Use user's preferred value for count
			$db->setQuery($query, 0, $count);
			$rows = $db->loadObjectList();
		}

		if ($rows)
		{
			// Call plugin function to sort output by category
			$rows = $this->multi_d_sort($rows, 'catid', 0);
			$i    = 0;

			// If email is previewed from backend, do not generate sef urls as it won't work
			if ($app->isClient('administrator'))
			{
				foreach ($rows as $row)
				{
					$list[$i]        = new stdclass;
					$list[$i]->link  = JRoute::_($replace . FlexicontentHelperRoute::getItemRoute($row->id, $row->catid), false);
					$list[$i]->title = htmlspecialchars($row->title);

					if ($show_author_alias && $row->created_by_alias)
					{
						$list[$i]->author = htmlspecialchars($row->created_by_alias);
					}
					else
					{
						$list[$i]->author = htmlspecialchars($row->name);
					}

					$list[$i]->date     = htmlspecialchars($row->created);
					$list[$i]->catid    = htmlspecialchars($row->catid);
					$list[$i]->category = htmlspecialchars($row->category);

					if ($show_introtext)
					{
						$list[$i]->intro = substr(strip_tags($row->intro), 0, $introtext_count) . " ...";
					}

					$i++;
				}
			}
			// If email is previewed/generated from frontend, generate sef urls
			else
			{
				foreach ($rows as $row)
				{
					$list[$i]        = new stdclass;

					// Links will generate sef urls
					$list[$i]->link  = JUri::root() . substr(JRoute::_(FlexiContentHelperRoute::getItemRoute($row->id, $row->catid)), strlen(JUri::base(true)) + 1);
					$list[$i]->title = htmlspecialchars($row->title);

					if ($show_author_alias && $row->created_by_alias)
					{
						$list[$i]->author = htmlspecialchars($row->created_by_alias);
					}
					else
					{
						$list[$i]->author = htmlspecialchars($row->name);
					}

					$list[$i]->date     = htmlspecialchars($row->created);
					$list[$i]->catid    = htmlspecialchars($row->catid);
					$list[$i]->category = htmlspecialchars($row->category);

					if ($show_introtext)
					{
						$list[$i]->intro = substr(strip_tags($row->intro), 0, $introtext_count) . " ...";
					}

					$i++;
				}
			}
		}

		return $list;
	}
}
