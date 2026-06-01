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
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjscorm_nativescorm', JPATH_ADMINISTRATOR);

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjscormNativescorm extends CMSPlugin
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
	public function onGetSubFormat_tjscormContentInfo($config = array('nativescorm'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 			= array();
		$obj['name']	= $this->params->get('plugin_name', 'Native scorm');
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
	public function onGetSubFormat_nativescormContentHTML($mod_id, $lesson_id, $lesson, $comp_params)
	{
		$result = array();
		$plugin_name = $this->_name;

		/*Get setting from scorm table*/
		$scormLesson = $this->getscormDataforLesson($lesson_id);

		// Load the layout & push variables
		ob_start();
		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, 'creator');
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function to upload a file on server
	 * This is blank as we do not upload file on jwplayer
	 *
	 * @param   INTERGER  $lesson_id  lesson id
	 * @param   STRING    $filename   file name
	 * @param   STRING    $filepath   file path
	 *
	 * @return  true
	 *
	 * @since 1.0.0
	 */
	public function onUpload_filesOnnativescorm($lesson_id, $filename = '', $filepath = '')
	{
		$app = Factory::getApplication();
		jimport('joomla.filesystem.folder');

		/*extract scorm to media/comtjlms/lessons/SCORMZIPNAME folder*/
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$renamedto = basename($filename, "." . $ext);

		$extract_dir = JPATH_SITE . '/media/com_tjlms/lessons/' . $lesson_id . '/scorm';

		if ($renamedto)
		{
			$extract_dir = JPATH_SITE . '/media/com_tjlms/lessons/' . $lesson_id . '/' . $renamedto;
		}

		if (Folder::exists($extract_dir))
		{
			Folder::delete($extract_dir);
		}

		jimport('techjoomla.common');
		$SocialLibraryObject = new TechjoomlaCommon;

		if (!$SocialLibraryObject->extractCourse($extract_dir, $filepath))
		{
			return false;
		}

		// Create htaccess file in each lessons folder to allow direct files access of the folder
		$htaccessFile	=	JPATH_SITE . '/media/com_tjlms/lessons/' . $lesson_id . '/.htaccess';

		if (!File::exists($htaccessFile))
		{
			$content = "allow from all";
			$result = File::write($htaccessFile, $content);

			if (!$result)
			{
				$app->enqueueMessage('Could not create file - ' . $htaccessFile, 'warning');
			}
		}

		return true;
	}

	/**
	 * Function to upload a file on server
	 * This is blank as we do not upload file on jwplayer
	 *
	 * @param   STRING  $lessonFormatData  file name
	 *
	 * @return  true
	 *
	 * @since 1.0.0
	 */
	public function onparsenativescormFormat($lessonFormatData)
	{
		$db          = Factory::getDBO();
		$format_data = new stdClass;
		$format_data->lesson_id = $lessonFormatData['id'];
		$format_data->storage = 'local';
		$format_data->scormtype	= 'native';

		if ($lessonFormatData['source'])
		{
			$format_data->package = $lessonFormatData['source'];
			$scormLesson = $this->getscormDataforLesson($format_data->lesson_id);

			if (!empty($scormLesson))
			{
				$format_data->id = $scormLesson->id;
				$db->updateObject('#__tjlms_scorm', $format_data, 'id');
				$scorm_id	=	$format_data->id;
			}
			else
			{
				$format_data->version = '';
				$format_data->grademethod = '0';
				$format_data->passing_score = '0';
				$format_data->entry = '0';
				$format_data->launch = '0';
				$db->insertObject('#__tjlms_scorm', $format_data);
				$scorm_id	=	$db->insertid();
			}

			$lib_path = JPATH_SITE . '/plugins/' . $this->_type . '/' . $this->_name . '/' . $this->_name . '/lib/scormlib.php';

			if (File::exists($lib_path))
			{
				require_once $lib_path;
				$tjlmsscormlib = new tjlmsscormlib;

				/*extract scorm to media/comtjlms/lessons/SCORMZIPNAME folder*/
				$ext = pathinfo($format_data->package, PATHINFO_EXTENSION);
				$scormFoldername = basename($format_data->package, "." . $ext);

				$scorm_data = new stdClass;
				$scorm_data->lesson_id = $format_data->lesson_id;
				$scorm_data->id = $scorm_id;
				$ret = $tjlmsscormlib->scorm_parse($scorm_data,  $scormFoldername);

				return true;
			}
		}

		return false;
	}

	/**
	 * Function to upload a file on server
	 * This is blank as we do not upload file on jwplayer
	 *
	 * @param   STRING  $lessonFormatData  file name
	 *
	 * @return  true
	 *
	 * @since 1.0.0
	 */
	/*public function onAfternativescormFormatUploaded($lessonFormatData)
	{
		$db          = Factory::getDBO();
		$format_data = new stdClass;
		$format_data->lesson_id = $lessonFormatData['id'];
		$format_data->storage = 'local';
		$format_data->scormtype	= 'native';

		$subformatdata = $lessonFormatData['nativescorm'];

		if ($subformatdata['uploded_lesson_file'])
		{
			$format_data->package = $subformatdata['uploded_lesson_file'];
		}

		if (isset($subformatdata['passing_score']) && $subformatdata['passing_score'] != '')
		{
			$format_data->passing_score = $subformatdata['passing_score'];
		}

		if (isset($subformatdata['grademethod']) && $subformatdata['grademethod'] != '')
		{
			$format_data->grademethod = $subformatdata['grademethod'];
		}

		$scormLesson = $this->getscormDataforLesson($format_data->lesson_id);

		if (!empty($scormLesson))
		{
			$format_data->id = $scormLesson->id;
			$db->updateObject('#__tjlms_scorm', $format_data, 'id');
			$scorm_id	=	$format_data->id;
		}
		else
		{
			$db->insertObject('#__tjlms_scorm', $format_data);
			$scorm_id	=	$db->insertid();
		}

		return true;
	}*/

	/**
	 * Function to get the id of the scorm table
	 *
	 * @param   INT  $lessonid  lessonid
	 *
	 * @return  id of tjlms_scorm
	 *
	 * @since 1.0.0
	 */
	public function onGetAdditionalnativescormData($lessonid)
	{
		return $this->getscormDataforLesson($lessonid);
	}

	/**
	 * Function to get the id of the scorm table
	 *
	 * @param   INT  $lessonid  lessonid
	 *
	 * @return  id of tjlms_scorm
	 *
	 * @since 1.0.0
	 */
	public function getscormDataforLesson($lessonid)
	{
		$db = Factory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_scorm'));
		$query->where($db->quoteName('lesson_id') . ' = ' . (int) $lessonid);
		$query->order('id DESC');
		$db->setQuery($query);

		return $db->loadObject();
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
	public function onAdditionalnativescormFormatCheck($lessonId, $mediaObj)
	{
		if ($this->getscormDataforLesson($lessonId))
		{
			return $mediaObj;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Function to render the video
	 *
	 * @param   ARRAY  $config  data to be used to play video
	 *
	 * @return  complete html along with script is return.
	 *
	 * @since 1.0.0
	 */
	public function onnativescormrenderPluginHTML($config)
	{
		$input = Factory::getApplication()->input;
		$mode = $input->get('mode', '', 'STRING');
		$file_ext = pathinfo($config['sourcefilename'], PATHINFO_EXTENSION);
		$scriptfile = JURI::root(true) . '/plugins/tjscorm/nativescorm/assets/js/nativescorm.js';

		// YOUR CODE TO RENDER HTML
		$html = '
		<script type="text/javascript">
		var plugdataObject = {
			plgtype: "' . $this->_type . '",
			plgname: "' . $this->_name . '",
			plgtask:"updateData",
			lesson_id: ' . $config['lesson_id'] . ',
			attempt: ' . $config['attempt'] . ',
			file_id : "' . $config['file'] . '",
			type : "' . $file_ext . '",
			seekTo : ' . $config['current'] . ',
			mode:  "' . $mode . '"
		};
		</script>
		<script src="' . $scriptfile . '"></script>';

		return $html;
	}

	/**
	 * Function is used to check if the lesson is passable
	 *
	 * @return boolean
	 *
	 * @since 1.3.39
	 */
	public function onisPassable_tjscorm()
	{
		return true;
	}
}
