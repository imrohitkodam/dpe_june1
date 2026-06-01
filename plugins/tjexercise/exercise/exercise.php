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

require_once JPATH_ADMINISTRATOR . '/components/com_tmt/helpers/formhelper.php';

/**
 * Content builder plugin for Joomla Content
 *
 * @since  1.0.0
 */
class PlgTjexerciseexercise extends FormHelper
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
	public function onGetSubFormat_tjexerciseContentInfo($config=array('exercise'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 			= array();
		$obj['name']	= $this->params->get('plugin_name', 'exercise');
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
	 * @param   int    $form_id      id of form
	 *
	 * @return  html
	 *
	 * @since 1.0.0
	 */
	public function onGetSubFormat_exerciseContentHTML($mod_id, $lesson_id, $lesson, $comp_params, $form_id)
	{
		$html = $this->getSubFormat_ContentHTML($mod_id, $lesson_id, $lesson, $comp_params, $form_id);

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
	public function onexerciserenderPluginHTML($config)
	{
		$input = Factory::getApplication()->input;
		$mode = $input->get('mode', '', 'STRING');
		$config['plgtask'] = 'exercise_updatedata';
		$config['plgtype'] = $this->_type;
		$config['plgname'] = $this->_name;

		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath('default');
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

	public function onAdditionalexerciseFormatCheck($lessonId, $mediaObj)
	{
		return parent::getAdditionalFormatCheck($lessonId, $mediaObj);
	}

	/**
	 * Function to get the id of the scorm table
	 *
	 * @param   INT  $lessonId   lessonid
	 * @param   INT  $mediaData  Media object
	 *
	 * @return  id of tjlms_scorm
	 *
	 * @since 1.0.0
	 */
	public function onGetAdditionalexerciseData($lessonId, $mediaData)
	{
		return parent::getAdditionalformdata($lessonId, $mediaData);
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
	public function OnAfterexerciseFormatUploaded($lessonFormatData)
	{
		$test_id = $this->OnAfterFormatUploaded($lessonFormatData);

		return $test_id;
	}

	/**
	 * Function is used to check if the lesson is passable
	 *
	 * @return boolean
	 *
	 * @since __DEPOLOY_VERSION__
	 */
	public function onisPassable_tjexercise()
	{
		return true;
	}
}
