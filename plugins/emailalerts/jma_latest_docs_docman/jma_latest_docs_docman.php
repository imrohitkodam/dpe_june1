<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_latest_docs_docman
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

$jmailAlertsPluginPath    = JPATH_SITE . '/components/com_jmailalerts/helpers/plugins.php';
$jmaIntegrationHelperPath = JPATH_SITE . '/plugins/system/plg_sys_jma_integration/plg_sys_jma_integration/plugins.php';

// Include plugin helper file
// Else condition is needed when JMA integration plugin is used on sites where JMA is not installed
if (File::exists($jmailAlertsPluginPath))
{
	include_once $jmailAlertsPluginPath;
}
elseif (File::exists($jmaIntegrationHelperPath))
{
	include_once $jmaIntegrationHelperPath;
}

/**
 * Latest docs DOCman JMailAlerts Plugin
 *
 * @since  2.5.1
 */
class PlgEmailalertsJma_Latest_Docs_Docman extends JMailAlertsPlugin
{
	public $extension = 'com_docman';

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
	public function onEMail_jma_latest_docs_docman($id, $lastEmailDate, $userParams, $fetchOnlyLatest)
	{
		// This function is just a dummy
		// Let's call parent function
		return $this->onEmailTrigger($id, $lastEmailDate, $userParams, $fetchOnlyLatest);
	}

	/**
	 * Method to get the records based on user preferences.
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

		// If no userid/or no guest user return blank array for html and css
		if ($id == null)
		{
			return $list;
		}

		$db   = Factory::getDbo();
		$app  = Factory::getApplication();
		$user = Factory::getUser($id);

		// Get user preferences for this plugin parameters(shown in frontend)
		$no_of_docs = (int) $userParams['no_of_docs'];
		$catid      = isset($userParams['catid']) ? trim($userParams['catid']) : '';

		// Show parent category for subcategories?

		/*if ($this->params->get('show_parent_category'))
		{
			include_once(JPATH_ADMINISTRATOR."/components/com_docman/docman.class.php");
			global $_DOCMAN;
			$_DOCMAN=new dmMainFrame();
			equire_once($_DOCMAN->getPath('classes','utils'));
		}*/

		// Category filter
		$catCondition = '';

		if ($catid)
		{
			$ids = explode(',', $catid);
			$ids = ArrayHelper::toInteger($ids);

			// $catCondition = ' AND (c.id='.implode(' OR c.id=', $ids) . ')';
			$catCondition = ' AND (c.docman_category_id=' . implode(' OR c.docman_category_id=', $ids) . ')';
		}

		// For docman beta 3
		$query = "SELECT dm.docman_document_id AS id, dm.docman_category_id AS cid,
		 dm.title, dm.created_by AS owner, dm.created_on AS published_date, dm.slug,
		 c.title AS cat_title, c.slug AS cat_slug
		 FROM #__docman_documents AS dm
		 JOIN #__docman_categories c ON c.docman_category_id = dm.docman_category_id
		 WHERE c.enabled = 1
		 AND dm.enabled = 1";

		/*AND dm.created_by <> ".$id.*/

		$query .= " (" . $catid ? $catCondition : '' . ")";

		if ($fetchOnlyLatest)
		{
			$query .= " AND dm.created_on >=";
			$query .= $db->Quote($lastEmailDate);
		}

		// Add ACL check

		$latestGroups = array();
		$groups   = array_column($this->getGroupLevels($user->groups), 'docman_level_id');

		foreach ($groups as $key => $group)
		{
			$latestGroups[$key] = '-' . $group;
		}

		$acessLevel = $user->getAuthorisedViewLevels();
		$latestGroups = array_merge($latestGroups, $acessLevel);
		$latestGroups = implode(',', $latestGroups);
		$checkacc = ' AND c.access IN (' . $latestGroups . ')';
		$query   .= $checkacc;
		$query   .= " ORDER BY dm.created_on DESC";

		$db->setQuery($query);
		$newdocs = $db->loadObjectList();

		$replace = Uri::root();
		$Itemid  = $this->getItemId('index.php?option=com_docman');

		$i     = 0;

		if ($newdocs)
		{
			// If simulating
			if ($app->isClient("administrator"))
			{
				foreach ($newdocs as $newdoc)
				{
					$list[$i] = new stdclass;
					$list[$i]->link = Route::_(
						$replace .
						"index.php?option=com_docman&view=document&category_slug=" . $newdoc->cat_slug .
						"&alias=" . $newdoc->id . "-" . $newdoc->slug .
						"&Itemid=" . $Itemid,
						false
					);

					$list[$i]->dwn_link = Route::_(
						$replace .
						"index.php?option=com_docman&view=download&category_slug=" . $newdoc->cat_slug .
						"&alias=" . $newdoc->id . "-" . $newdoc->slug .
						"&Itemid=" . $Itemid,
						false
					);

					$list[$i]->title  = htmlspecialchars($newdoc->title);
					$list[$i]->cat_id = $newdoc->cid;

					// Show parent category for subcategories?

					/*if ($this->params->get('show_parent_category'))
					{
						$ancestors = DOCMAN_Cats::getAncestors($newdoc->gid);
						$ancestors = array_reverse($ancestors);

						if (count($ancestors) > 1)
						{
							$catpath = "";
							$k       = 0;

							foreach ($ancestors as $ancestor)
							{
								if ($k < count($ancestors) - 1)
								{
									$catpath .= $ancestor->name . " > ";
								}
								else
								{
									$catpath .= $ancestor->name;
								}

								$k++;
							}

							$list[$i]->cat_title = htmlspecialchars($catpath);
						}
						else
						{
							$list[$i]->cat_title = htmlspecialchars($newdoc->cat_title);
						}
					}
					else
					{
						$list[$i]->cat_title = htmlspecialchars($newdoc->cat_title);
					}*/

					$list[$i]->cat_title = htmlspecialchars($newdoc->cat_title);
					$list[$i]->date      = $newdoc->published_date;

					$i++;
				}
			}
			else
			{
				foreach ($newdocs as $newdoc)
				{
					$list[$i] = new stdclass;

					$list[$i]->link = Uri::root() . substr(
						Route::_(
							"index.php?option=com_docman&view=document&category_slug=" . $newdoc->cat_slug .
							"&alias=" . $newdoc->id . "-" . $newdoc->slug .
							"&Itemid=" . $Itemid,
							false
						),
						strlen(Uri::base(true)) + 1
					);

					$list[$i]->dwn_link = Uri::root() . substr(
						Route::_(
							"index.php?option=com_docman&view=download&category_slug=" . $newdoc->cat_slug .
							"&alias=" . $newdoc->id . "-" . $newdoc->slug .
							"&Itemid=" . $Itemid,
							false
						),
						strlen(Uri::base(true)) + 1
					);

					$list[$i]->title  = htmlspecialchars($newdoc->title);
					$list[$i]->cat_id = $newdoc->cid;

					// Show parent category for subcategories?

					/*if ($this->params->get('show_parent_category'))
					{
						if (count($ancestors) > 1)
						{
							$catpath = "";
							$k       = 0;

							foreach ($ancestors as $ancestor)
							{
								if ($k < count($ancestors) - 1)
								{
									$catpath .= $ancestor->name . " > ";
								}
								else
								{
									$catpath .= $ancestor->name;
								}

								$k++;
							}

							$list[$i]->cat_title = htmlspecialchars($catpath);
						}
						else
						{
							$list[$i]->cat_title = htmlspecialchars($newdoc->cat_title);
						}
					}
					else
					{
						$list[$i]->cat_title = htmlspecialchars($newdoc->cat_title);
					}*/

					$list[$i]->cat_title = htmlspecialchars($newdoc->cat_title);
					$list[$i]->date      = $newdoc->published_date;

					$i++;
				}
			}
		}

		if ($list)
		{
			// Sort by publish date
			$list = $this->multi_d_sort($list, 'date', 1);

			// Apply user prefered count
			$list = array_slice($list, 0, $no_of_docs);

			// Sort by cat_id
			$list = $this->multi_d_sort($list, 'cat_id', 0);
		}

		return $list;
	}

	/**
	 * Method to get the docman level id of the groups 
	 * 
	 * @param   array  $groups  groups id  in which user present
	 *
	 * @return  array
	 *
	 * @since  2.5.0
	 */
	public function getGroupLevels($groups)
	{
		if (empty($groups))
		{
			return false;
		}

		$result = array();
		$groups = (array) $groups;
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true);
		$query->select($db->quoteName(array('docman_level_id')));
		$query->from($db->quoteName('#__docman_levels'));

		if ($groups)
		{
			$query->where($db->quoteName('groups') . ' IN (' . (implode(',', $groups)) . ')');
		}

		$db->setQuery($query);
		$results = $db->loadObjectList();

	return $results;
	}
}
