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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjscormcloud', JPATH_ADMINISTRATOR);

require_once JPATH_SITE . '/plugins/tjtincanlrs/tjscormcloud/tjscormcloud/lib/ScormEngineService.php';


/**
 * Tj Scorm Cloud plugin
 *
 * @since  1.0.0
 */
class PlgTjtincanlrsTjscormcloud extends CMSPlugin
{
	/**
	 * Plugin that supports uploading and tracking the Tincan pakcge on Scorm Cloud
	 *
	 * @param   string   &$subject  The context of the content being passed to the plugin.
	 * @param   integer  $config    Optional page number. Unused. Defaults to zero.
	 *
	 * @return  void.
	 */
	public function plgTjtincanlrsTjscormcloud(&$subject, $config)
	{
		parent::__construct($subject, $config);

		require_once JPATH_SITE . '/plugins/tjtincanlrs/tjscormcloud/tjscormcloud/lib/ScormEngineUtilities.php';

		$this->user = Factory::getUser();

		$this->appId = trim($this->params->get('appId'));
		$this->secretKey = trim($this->params->get('secretKey'));
		$this->serviceUrl = 'http://cloud.scorm.com/EngineWebServices/';
		$this->origin = ScormEngineUtilities::getCanonicalOriginString('citelco', 'theweb', '2.0');
	}

	/**
	 * Function to get Sub Format options when creating / editing lesson format
	 *
	 * @param   ARRAY  $config  config specifying allowed plugins
	 *
	 * @return  object.
	 *
	 * @since 1.0.0
	 */
	public function getSubFormat_ContentInfo($config = array('tjscormcloud'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj = array();
		$obj['name'] = $this->params->get('plugin_name', 'Scorm-cloud API');
		$obj['id'] = $this->_name;

		return $obj;
	}

	/**
	 * Function to get Sub Format HTML when creating / editing lesson format
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
	public function getSubFormat_tjscormcloudContentHTML($mod_id, $lesson_id, $lesson, $comp_params)
	{
		if (empty($this->appId) || empty($this->secretKey))
		{
			return "<div class='alert alert-error'>" . Text::_("PLG_SCORM_CLOUD_NOTCONFIGURED_MSG") . "</div>";
		}

		$result = array();
		$plg_name = $this->_name;
		$source = (isset($lesson->format_details['source'])) ? $lesson->format_details['source'] : '';

		$html = '
			<div class="control-label">' . Text::_("COM_TJLMS_UPLOAD_FORMAT") . '</div>

			<div  class="controls">
			<input type="hidden" id="lesson_format' . $plg_name . 'tincanlrs_source" name="lesson_format[' . $plg_name . '][tincanlrs_source]" value="upload"/>
			<div>
				<div class="fileupload fileupload-new pull-left" data-provides="fileupload">
					<div class="input-append">
						<div class="uneditable-input span4">
							<span class="fileupload-preview">
								' . Text::sprintf('COM_TJLMS_UPLOAD_FILE_WITH_EXTENSION', 'zip', $comp_params->get('lesson_upload_size', '0', 'INT')) . '
							</span>
						</div>
						<span class="btn btn-file">
							<span class="fileupload-new">' . Text::_("COM_TJLMS_BROWSE") . '</span>
							<input type="file"
								id="tincanlrs_upload"
								name="lesson_format[' . $plg_name . '][tincanlrs]"
								onchange="validate_file(this,\'' . $mod_id . '\',\'' . $plg_name . '\')" />
						</span>
					</div>
				</div>
				<div style="clear:both"></div>
			</div>
			<input type="hidden" class="valid_extensions" value="zip"/>
			</div>';

		return $html;
	}

	/**
	 * Function to upload a file on cloud
	 *
	 * @param   String  $filename               name of the file to be transfered
	 * @param   String  $absoluteFilePathToZip  Absolute Path of the file
	 *
	 * @return  param name i.e. scormcloud_id and its vale $scormcloud_id  that need to store in Media table params
	 *
	 * @since 1.0.0
	 */
	public function upload_filesOntjscormcloud($filename = '', $absoluteFilePathToZip = '')
	{
		if (!empty($this->appId) && !empty($this->secretKey))
		{
			$ScormService = new ScormEngineService($this->serviceUrl, $this->appId, $this->secretKey, $this->origin);
			$courseService = $ScormService->getCourseService();
			$scormcloud_id = uniqid();
			$res = $courseService->ImportCourse($scormcloud_id, $absoluteFilePathToZip);

			/* Res will be in this form if Import is successfull
			 *Array
				(
					[0] => ImportResult Object
						(
							[_title:ImportResult:private] => Tin Can Golf Example
							[_wasSuccessful:ImportResult:private] => 1
							[_message:ImportResult:private] => Import Successful
							[_parserWarnings:ImportResult:private] => Array
								(
								)

						)

				)*/

			// If if Import is successfull, return scormcloud_id
			if ($res)
			{
				return array("scormcloud_id" => $scormcloud_id);
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Create a session for viewing a document
	 *
	 * @param   VAR  $document_id  param that we jave stored in params column against media
	 *
	 * @return session object
	 *
	 * @since 1.0.0
	 */
	public function getSessionForDocument($document_id)
	{
		$doc = new Box_View_Document(array('id' => $document_id ));

		$api_key = $this->params->get('appkey', '', 'STRING');

		if (empty($api_key))
		{
			return false;
		}

		$box     = new Box_View_API($api_key);

		// Check if the status of the file is 'Done'
		$checkStatusForDoc = $box->getMetaData($doc);

		if ($checkStatusForDoc->status !== 'done')
		{
			return false;
		}

		// As we got the status as 'done' we can proceed to get the session for viewing the doc
		$getSession = $box->view($doc);

		return $getSession;
	}

	/**
	 * Function to render the document
	 *
	 * @param   ARRAY  $data  array containing document id lesson id etc.
	 *
	 * @return  $html		complete html along with script is return.
	 *
	 * @since 1.0.0
	 */
	public function renderPluginHTML($data)
	{
		$html             = '';
		$document_id      = $data['document_id'];
		$checksessionUrl  = '';
		$getSession       = '';
		$loadingImagePath = Uri::root() . 'components/com_tjlms/assets/images/ajax.gif';

		$SelectedLayout      = $this->params->get('doc_layout', '', 'STRING');
		$layoutOptionForUser = $this->params->get('doc_layout_ft_option', '', 'STRING');

		// Check if session already present. So need to create a new seesion.
		$checksessionUrl = $this->checkSessionForDocument($data['lesson_id']);

		if (empty($checksessionUrl))
		{
			// Create a session for viewing a document
			$getSession = $this->getSessionForDocument($document_id);

			// Store session ID and expire at in tjlms_media table
			$storeSessionData = $this->storeSessionData($getSession, $data['lesson_id']);
		}

		// Check if there was a error while creating seesion.
		if (!$getSession && empty($checksessionUrl))
		{
			$html = '<div class="alert alert-danger">' . Text::_('PLG_BOX_FILE_NOT_YE_AVAILABLE_TO_VIEW') . '</div>';
		}
		else
		{
			if (!empty($checksessionUrl))
			{
				$url_to_use   = $checksessionUrl->assets_url;
				$realtime_url = $checksessionUrl->realtime_url;
			}
			else
			{
				$url_to_use   = $getSession->urls->assets;
				$realtime_url = $getSession->urls->realtime_url;
			}
		}

		return $html;
	}

	/**
	 * update the appemt data
	 *
	 * @return  id of the tracking row
	 *
	 * @since   1.0.0
	 */
	public function updateData()
	{
		header('Content-type: application/json');
		$input = Factory::getApplication()->input;

		$post             = $input->post;
		$lesson_id        = $post->get('lesson_id', '', 'INT');
		$cur_pos = $post->get('current_position', '', 'INT');
		$total_content    = $post->get('total_content', '', 'INT');
		$time_spent       = $post->get('total_time', '', 'FLOAT');
		$u_id          = $post->get('user_id', '', 'INT');
		$attempt          = $post->get('attempt', '', 'INT');
		$score            = 0;
		$l_status    = 'incomplete';

		if ($current_position == $total_content)
		{
			$lesson_status = 'completed';
		}

		require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';

		$comtjlmstrackingHelper = new comtjlmstrackingHelper;
		$trackingid = $comtjlmstrackingHelper->update_lesson_track($lesson_id, $attempt, $score, $l_status, $u_id, $total_content, $cur_pos, $time_spent);
		$trackingid = json_encode($trackingid);
		echo $trackingid;
		jexit();
	}
}
