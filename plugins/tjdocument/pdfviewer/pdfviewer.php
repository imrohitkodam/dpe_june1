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
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

/**
 * tjDocument PDF Viewer plugin
 *
 * @since  1.0.0
 */
class PlgTjdocumentPdfviewer extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

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
	public function onGetSubFormat_tjdocumentContentInfo($config = array('pdfviewer'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 			   = array();
		$obj['name']	   = $this->params->get('plugin_name', 'PDF Viewer');
		$obj['id']		   = $this->_name;
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
	public function onGetSubFormat_pdfviewerContentHTML($mod_id , $lesson_id, $lesson, $comp_params)
	{
		$result = array();
		$plugin_name = $this->_name;

		// Load the layout & push variables
		ob_start();
		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, 'creator');
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function to upload a file on cloud
	 *
	 * @param   INT     $lesson_id  lessonid
	 * @param   STRING  $filename   file name
	 *
	 * @param   STRING  $filepath   file path
	 *
	 * @return  param name i.e. document_id and its vale $document_id  that need to store in Media table params
	 *
	 * @since 1.0.0
	 */
	public function onUpload_filesOnpdfviewer($lesson_id, $filename = '', $filepath = '')
	{
		return true;
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
	public function onAdditionalpdfviewerFormatCheck($lessonId, $mediaObj)
	{
		return $mediaObj;
	}

	/**
	 * Function to render the document
	 *
	 * @param   ARRAY  $data  Data to display
	 *
	 * @return  complete html along with script is return.
	 *
	 * @since 1.0.0
	 */
	public function onPdfviewerrenderPluginHTML($data)
	{
		$error = $url_to_use = '';

		$pluginUrl    = Uri::root() . 'index.php?option=com_tjlms&task=callSysPlgin&plgType=' . $this->_type . '&plgName=' . $this->_name;
		$input 		  = Factory::getApplication()->input;
		$data['mode'] = $input->get('mode', '', 'STRING');
		$data['time_sync_url']  = $pluginUrl . '&plgtask=updateData&mode=' . $data['mode'];

		$olUserId = Factory::getUser()->get('id');

		if (!$olUserId)
		{
			$data['mode'] = 'preview';
		}

		$data['debug'] = Jfactory::getConfig()->get('debug', false);

		ob_start();
		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name);
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * update the appempt data
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function onupdateData()
	{
		header('Content-type: application/json');
		$post      = Factory::getApplication()->input->post;
		$oluser_id = Factory::getUser()->get('id');

		$mode = $post->get('mode', '', 'STRING');

		if ($mode != 'preview' && $oluser_id)
		{
			$lesson_id = $post->get('lesson_id', '', 'INT');

			$trackObj = new stdClass;
			$trackObj->current_position = $post->get('current_position', '', 'INT');
			$trackObj->total_content    = $post->get('total_content', '', 'INT');
			$trackObj->time_spent       = $post->get('total_time', '', 'FLOAT');

			$trackObj->attempt          = $post->get('attempt', '', 'INT');
			$trackObj->score            = 0;
			$trackObj->lesson_status    = 'incomplete';

			if ($trackObj->current_position == $trackObj->total_content)
			{
				$trackObj->lesson_status = 'completed';
			}

			require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';

			$comtjlmstrackingHelper = new comtjlmstrackingHelper;
			$trackingid = $comtjlmstrackingHelper->update_lesson_track($lesson_id, $oluser_id, $trackObj);
			$trackingid = json_encode($trackingid);
			echo $trackingid;
		}
		else
		{
			echo 1;
		}

		jexit();
	}
}
