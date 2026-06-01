<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjtextmedia_joomlacontent', JPATH_ADMINISTRATOR);

JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

/**
 * Content builder plugin for Joomla Content
 *
 * @since  1.0.0
 */
class PlgTjtextmediaJoomlacontent extends CMSPlugin
{
	/**
	 * Function to get Sub Format options when creating / editing lesson format
	 * the name of function should follow standard getSubFormat_<plugin_type>ContentInfo
	 *
	 * @param   ARRAY  $config  config specifying allowed plugins
	 *
	 * @return  object.
	 *
	 * @since 1.0.0
	 */
	public function onGetSubFormat_tjtextmediaContentInfo($config=array('joomlacontent'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 			= array();
		$obj['name']	= $this->params->get('plugin_name', 'Article as Lesson');
		$obj['id']		= $this->_name;
		$obj['assessment'] = $this->params->get('assessment', '0');

		return $obj;
	}

	/**
	 * Function to get Sub Format HTML when creating / editing lesson format
	 * the name of function should follow standard getSubFormat_<plugin_name>ContentHTML
	 *
	 * @param   INT    $mod_id       id of the module to which lesson belongs
	 * @param   INT    $lesson_id    id of the lesson
	 * @param   MIXED  $lesson       Object of lesson
	 * @param   ARRAY  $comp_params  Params of component
	 *
	 * @return  html
	 *
	 * @since 1.0.0
	 */
	public function onGetSubFormat_joomlacontentContentHTML($mod_id , $lesson_id, $lesson, $comp_params)
	{
		$result = array();
		$plugin_name = $this->_name;
		ob_start();

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('COUNT(id)');
		$query->from($db->quoteName('#__content'));
		$query->where($db->quoteName('state') . " = 1") OR ($db->quoteName('state') . " = 0");
		$db->setQuery($query);
		$articleCnt = $db->loadResult();

		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, 'creator');
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function to get needed data for this API
	 *
	 * @param   MIXED  $data  array
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	public function getData($data)
	{
		return true;
	}

	/**
	 * Function to render the document
	 *
	 * @param   ARRAY  $config  Data to display
	 *
	 * @return  complete html along with script is return.
	 *
	 * @since 1.0.0
	 */
	public function onjoomlacontentrenderPluginHTML($config)
	{
		$input = Factory::getApplication()->input;
		$mode = $input->get('mode', '', 'STRING');
		$config['plgtask'] = 'joomlaContent_updatedata';
		$config['plgtype'] = $this->_type;
		$config['plgname'] = $this->_name;

		// Load the layout & push variables
		ob_start();
		$layout = $layout = PluginHelper::getLayoutPath($this->_type, $this->_name, 'default');
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function to check if the scorm tables has been uploaded while adding lesson
	 *
	 * @param   INT  $lessonId  lessonId
	 * @param   OBJ  $mediaObj  media object
	 *
	 * @return  media object of format and subformat
	 *
	 * @since 1.0.0
	 */

	public function onAdditionaljoomlacontentFormatCheck($lessonId, $mediaObj)
	{
		return $mediaObj;
	}

	/**
	 * function used to save time spent for joomla content
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 * */
	public function onjoomlaContent_updatedata()
	{
		header('Content-type: application/json');
		$input = Factory::getApplication()->input;

		$post = $input->post;
		$lesson_id = $post->getDeta('lesson_id', '', 'INT');
		$user_id = Factory::getUser()->id;

		$trackObj = new stdClass;

		$trackObj->current_position = $post->get('current_position', '', 'INT');
		$trackObj->total_content = $post->get('total_content', '', 'INT');
		$trackObj->time_spent = $post->get('time_spent', '', 'INT');

		$trackObj->attempt = $post->get('attempt', '', 'INT');
		$trackObj->score = 0;
		$trackObj->lesson_status = $post->get('lesson_status', '', 'STRING');
		require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';

		$comtjlmstrackingHelper = new comtjlmstrackingHelper;
		$trackingid = $comtjlmstrackingHelper->update_lesson_track($lesson_id, $user_id, $trackObj);

		$trackingid = json_encode($trackingid);
		jexit();
	}

	/**
	 * Function to get HTML to be shown insted on LAUNCH button
	 *
	 * @param   OBJ  $lesson  lesson object
	 *
	 * @return  complete html along with script is return.
	 *
	 * @since 1.0.0
	 */
	public function onGetjoomlacontentLaunchButtonHtml($lesson)
	{
		require_once JPATH_SITE . '/components/com_tjlms/helpers/media.php';
		$tjlmsmediaHelper = new TjlmsmediaHelper;
		$mediaDetails = $tjlmsmediaHelper->getMediaParams($lesson->media_id);
		$mediaParams = json_decode($mediaDetails->params, true);
		$errorMsg = '';

		if (isset($mediaParams['contentid']))
		{
			$errorMsg = $this->articleIsPublished($mediaParams['contentid']);
		}

		if (is_string($errorMsg))
		{
			$span = '<i rel="popover" class="icon-lock" ></i><span class="lesson_attempt_action">' . Text::_("PLG_TJTEXTMEDIA_JC_LAUNCH") . '</span>';

			$return['html'] = '<button rel="popover" data-original-content=" ' . $errorMsg . '" class="btn btn-small btn-disabled">' . $span . ' </button>';

			$return['supress_lms_launch'] = 1;

			return $return;
		}
	}

	/**
	 * Functions check article and it's category is published or not and return article ID on success
	 *
	 * @param   INT  $contentId  content id
	 *
	 * @return  file
	 *
	 * @since 1.0.0
	 */
	public function articleIsPublished($contentId)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery('true');
		$query->select('c.id, c.publish_down, c.publish_up');
		$query->from('#__content as c');
		$query->join('INNER', $db->quoteName('#__categories', 'ca') . ' ON (' . $db->quoteName('c.catid') . ' = ' . $db->quoteName('ca.id') . ')');
		$query->where('c.state = 1');
		$query->where('ca.published = 1');
		$query->where('c.id = ' . $contentId);
		$db->setQuery($query);

		$result = $db->loadObject();
		$now = Factory::getDate()->toSql();

		if (empty($result) || $result->publish_up > $now)
		{
			return $msg = Text::sprintf("PLG_TJTEXTMEDIA_JC_ACCESS_DENIED");
		}
		elseif ($result->publish_down < $now && $result->publish_down != 0)
		{
			return $msg = Text::sprintf("PLG_TJTEXTMEDIA_JC_ARTICLE_EXPIRED");
		}

		return 0;
	}

	/**
	 * Functions fetches all article ids used as a lesson
	 *
	 * @return  Array
	 *
	 * @since 1.0.0
	 */
	public function getLessonArticles()
	{
		static $articleids;

		if (!isset($articleids))
		{
			$db = Factory::getDBO();
			$query = $db->getQuery('true');
			$query->select('m.params');
			$query->from('#__tjlms_media as m');
			$query->where('m.sub_format = ' . $db->quote('joomlacontent.url'));
			$db->setQuery($query);
			$resultSet = $db->loadObjectList();

			foreach ($resultSet as $result)
			{
				$jcResult = json_decode($result->params, true);

				if (isset($jcResult['contentid']) && (int) $jcResult['contentid'])
				{
					$articleids[$jcResult['contentid']] = (int) $jcResult['contentid'];
				}
			}
		}

		return $articleids;
	}

	/**
	 * Functions check if article id(s) are used as lesson
	 *
	 * @param   MIX  $contentId           Content Id(s)
	 * @param   MIX  &$lesson_articleIds  Lesson Articles Ids
	 *
	 * @return  boolean
	 *
	 * @since 1.0.0
	 */
	public function isLessonArticle($contentId, &$lesson_articleIds = false)
	{
		$articleids = $this->getLessonArticles();

		if (!empty($articleids) && !empty($contentId))
		{
			if (is_array($contentId))
			{
				$lesson_articleIds  = array_intersect($articleids, $contentId);

				if (!empty($lesson_articleIds))
				{
					return true;
				}
			}
			else
			{
				if (in_array($contentId, $articleids))
				{
					$lesson_articleIds = $contentId;

					return true;
				}
			}
		}

		return false;
	}
}
