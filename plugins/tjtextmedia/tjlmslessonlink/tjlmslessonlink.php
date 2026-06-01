<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJTEXTMEDIA,lessonlink
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

$lang = Factory::getLanguage();
$lang->load('plg_tjtextmedia_tjlmslessonlink', JPATH_ADMINISTRATOR);

// Add Table Path
Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

/**
 * Content builder plugin for link as lesson
 *
 * @since  1.0.0
 */
class PlgTjtextmediaTjlmsLessonLink extends CMSPlugin
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
	public function onGetSubFormat_tjtextmediaContentInfo($config=array('tjlmslessonlink'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 			= array();
		$obj['name']	= $this->params->get('plugin_name', 'Link as Lesson');
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
	public function onGetSubFormat_tjlmslessonlinkContentHTML($mod_id , $lesson_id, $lesson, $comp_params)
	{
		$result = array();
		$plugin_name = $this->_name;
		ob_start();
		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, 'creator');
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
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
	public function ontjlmslessonlinkrenderPluginHTML($config)
	{
		$input = Factory::getApplication()->input;
		$mode  = $input->get('mode', '', 'STRING');
		$config['plgtask'] = 'tjlmslessonlink_updatedata';
		$config['plgtype'] = $this->_type;
		$config['plgname'] = $this->_name;

		JLoader::import('components.com_tjlms.models.lesson', JPATH_SITE);
		$lessonModel = BaseDatabaseModel::getInstance('lesson', 'TjlmsModel', array('ignore_request' => true));

		// Later we will refactor below function
		$lessonInfo = $lessonModel->getlessondata($config['lesson_id']);

		JLoader::register('TjlmsmediaHelper', JPATH_SITE . '/components/com_tjlms/helpers/media.php');

		$tjlmsmediaHelper = new TjlmsmediaHelper;
		$mediaDetails     = $tjlmsmediaHelper->getMediaParams($lessonInfo->media_id);
		$mediaParams      = json_decode($mediaDetails->params, true);

		// Load the layout & push variables
		ob_start();
		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, 'default');
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

	public function onAdditionaltjlmslessonlinkFormatCheck($lessonId, $mediaObj)
	{
		return $mediaObj;
	}

	/**
	 * function used to save time spent for link as lesson
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 * */
	public function ontjlmslessonlink_updatedata()
	{
		header('Content-type: application/json');
		$input     = Factory::getApplication()->input;
		$post      = $input->post;
		$lesson_id = $post->get('lesson_id', '', 'INT');
		$user_id   = Factory::getUser()->id;

		$trackObj                   = new stdClass;
		$trackObj->current_position = $post->get('current_position', '', 'INT');
		$trackObj->total_content    = $post->get('total_content', '', 'INT');
		$trackObj->time_spent       = $post->get('time_spent', '', 'INT');
		$trackObj->attempt          = $post->get('attempt', '', 'INT');
		$trackObj->score            = 0;
		$trackObj->lesson_status    = $post->get('lesson_status', '', 'STRING');

		JLoader::register('comtjlmstrackingHelper', JPATH_SITE . '/components/com_tjlms/helpers/tracking.php');

		$comtjlmstrackingHelper = new comtjlmstrackingHelper;
		$trackingid             = $comtjlmstrackingHelper->update_lesson_track($lesson_id, $user_id, $trackObj);

		$trackingid = json_encode($trackingid);
		echo $trackingid;
		jexit();
	}
}
