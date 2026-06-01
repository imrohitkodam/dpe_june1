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
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\File;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjexternaltool_ltiresource', JPATH_ADMINISTRATOR);

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjexternaltoolLtiresource extends CMSPlugin
{
	/**
	 * Function that is triggered from TP to update the score
	 *
	 * @return  void.
	 *
	 * @since 1.0.0
	 */
	public function ltiresourcelistener()
	{
		$rawbody = file_get_contents("php://input");
		$xml = simplexml_load_string($rawbody);

		if (!$xml)
		{
			return;
		}

		$file_contents['OUTPUT'] = "\n" . "================OUTPUT======================";
		$file_contents["LTI SENT"] = $rawbody;

		$body = $xml->imsx_POXBody;

		foreach ($body->children() as $child)
		{
			$messagetype = $child->getName();
		}

		$message_id = $xml->imsx_POXHeader->imsx_POXRequestHeaderInfo->imsx_messageIdentifier;

		switch ($messagetype)
		{
			case 'readResultRequest':

				$lesson_track_id = $xml->imsx_POXBody->readResultRequest->resultRecord->sourcedGUID->sourcedId;

				if ($this->ltiVerifySourcedid($lesson_track_id))
				{
					$grade = $this->ltiReadGrade($lesson_track_id);

					$responsexml = $this->ltiGetResponsexml(
							'success',  // Empty grade is also 'success'.
							'Result read',
							$message_id,
							'readResultResponse'
					);

					$node = $responsexml->imsx_POXBody->readResultResponse;
					$node = $node->addChild('result')->addChild('resultScore');
					$node->addChild('language', 'en');
					$node->addChild('textString', isset($grade) ? $grade : '');

					$file_contents["WHAT WE SENT"] = $responsexml->asXML();
				}
				else
				{
					$file_contents["WHAT WE SENT"] = " The Track id is not present in the table";
				}

			break;

			case 'replaceResultRequest':
				try
				{
					$lesson_track_id  = $xml->imsx_POXBody->replaceResultRequest->resultRecord->sourcedGUID->sourcedId;

					$score = $xml->imsx_POXBody->replaceResultRequest->resultRecord->result->resultScore->textString;
					$score = (string) $score;

					if (!is_numeric($score))
					{
						throw new Exception('Score must be numeric');
					}

					$grade = floatval($score);

					if ( $grade < 0.0 || $grade > 1.0 )
					{
						throw new Exception('Score not between 0.0 and 1.0');
					}
				}
				catch (Exception $e)
				{
					$responsexml = $this->ltiGetResponsexml(
						'failure',
						$e->getMessage(),
						uniqid(),
						'replaceResultResponse');

					$file_contents["WHAT WE SENT"] = $responsexml->asXML();
					break;
				}

				$gradestatus = $this->ltiUpdateGrade($lesson_track_id, $grade);

				$responsexml = $this->ltiGetResponsexml(
						$gradestatus ? 'success' : 'failure',
						'Grade replace response',
						$message_id,
						'replaceResultResponse'
				);

				$file_contents["WHAT WE SENT"] = $responsexml->asXML();

			break;

			case 'deleteResultRequest':
				$lesson_track_id = $xml->imsx_POXBody->replaceResultRequest->resultRecord->sourcedGUID->sourcedId;

				$gradestatus = $this->ltiDeleteGrade($lesson_track_id);

				$responsexml = $this->ltiGetResponsexml(
						$gradestatus ? 'success' : 'failure',
						'Grade delete request',
						$parsed->messageid,
						'deleteResultResponse'
				);

				$file_contents["WHAT WE SENT"] = $responsexml->asXML();

			break;

			default:
				/* Fire an event if we get a web service request which we don't support directly.
				This will allow others to extend the LTI services, which I expect to be a common
				use case, at least until the spec matures.*/

				$responsexml = ltiGetResponsexml(
					'unsupported',
					'unsupported',
					$message_id,
					$messagetype
				);

				$file_contents["WHAT WE SENT"] = $responsexml->asXML();

			break;
		}

		// Echo the XML output created.. This is the ACTUAL response to be sent to LTI
		echo $file_contents["WHAT WE SENT"];

		$log[] = "================================";
		$log[] = "OUTPUT FOR : " . $lesson_track_id;
		$log[] = $rawbody;

		file_put_contents(JPATH_SITE . '/plugins/tjexternaltool/ltiresource/tjltilogs.txt', implode("\n", $log), FILE_APPEND);

		jexit();
	}

	/**
	 * Function to check if the track entry is present
	 *
	 * @param   INT  $track_id  id of the lesson track table
	 *
	 * @return  xml
	 *
	 * @since 1.0.0
	 */
	public function ltiVerifySourcedid($track_id)
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$table = Table::getInstance('Lessontrack', 'TjlmsTable', array('dbo', Factory::getDBo()));
		$table->load($track_id);

		return $table->id;
	}

	/**
	 * Function to read the track entry of LTI is asking us to do it
	 *
	 * @param   INT  $track_id  id of the lesson track table
	 *
	 * @return  xml
	 *
	 * @since 1.0.0
	 */
	public function ltiReadGrade($track_id)
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$table = Table::getInstance('Lessontrack', 'TjlmsTable', array('dbo', Factory::getDBo()));
		$table->load($track_id);

		return $table->score;
	}

	/**
	 * Function to udate the score of the track entry of LTI is asking us to do it
	 *
	 * @param   INT  $track_id  id of the lesson track table
	 * @param   INT  $score     Score to be updated
	 *
	 * @return  xml
	 *
	 * @since 1.0.0
	 */
	public function ltiUpdateGrade($track_id, $score)
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$table = Table::getInstance('Lessontrack', 'TjlmsTable', array('dbo', Factory::getDBo()));
		$table->load($track_id);

		// Set updating fields data
		$table->score = $score * 100;
		$table->lesson_status = 'completed';

		$lessn_row = Table::getInstance('Lesson', 'TjlmsTable', array('dbo', Factory::getDBo()));
		$lessn_row->load($table->lesson_id);

		if ($lessn_row->total_marks)
		{
			$table->score = $score * $lessn_row->total_marks;
		}

		if ($lessn_row->passing_marks)
		{
			$table->lesson_status = 'passed';

			if ($table->score < $lessn_row->passing_marks)
			{
				$table->lesson_status = 'failed';
			}
		}

		return $table->store($table);
	}

	/**
	 * Function to delete the track entry of LTI is asking us to do it
	 *
	 * @param   INT  $track_id  id of the lesson track table
	 *
	 * @return  xml
	 *
	 * @since 1.0.0
	 */
	public function ltiDeleteGrade($track_id)
	{
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$table = Table::getInstance('Lessontrack', 'TjlmsTable', array('dbo', Factory::getDBo()));

		return $table->delete($track_id);
	}

	/**
	 * Function to crate the response in XML format to sent back to LTI
	 *
	 * @param   INT    $codemajor    codemajor
	 * @param   INT    $description  description
	 * @param   MIXED  $messageref   messageref
	 * @param   ARRAY  $messagetype  messagetype
	 *
	 * @return  xml
	 *
	 * @since 1.0.0
	 */
	public function ltiGetResponsexml($codemajor, $description, $messageref, $messagetype)
	{
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><imsx_POXEnvelopeResponse />');
		$xml->addAttribute('xmlns', 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0');

		$headerinfo = $xml->addChild('imsx_POXHeader')->addChild('imsx_POXResponseHeaderInfo');

		$headerinfo->addChild('imsx_version', 'V1.0');
		$headerinfo->addChild('imsx_messageIdentifier', (string) mt_rand());

		$statusinfo = $headerinfo->addChild('imsx_statusInfo');
		$statusinfo->addchild('imsx_codeMajor', $codemajor);
		$statusinfo->addChild('imsx_severity', 'status');
		$statusinfo->addChild('imsx_description', $description);
		$statusinfo->addChild('imsx_messageRefIdentifier', $messageref);
		$incomingtype = str_replace('Response', 'Request', $messagetype);
		$statusinfo->addChild('imsx_operationRefIdentifier', $incomingtype);

		$xml->addChild('imsx_POXBody')->addChild($messagetype);

		return $xml;
	}

	/*function ltiParseGradeReadmessage($xml)
	{
		$node = $xml->imsx_POXBody->readResultRequest->resultRecord->sourcedGUID->sourcedId;
		$resultjson = json_decode((string) $node);

		$parsed = new stdClass;
		$parsed->instanceid = $resultjson->data->instanceid;
		$parsed->userid = $resultjson->data->userid;
		$parsed->launchid = $resultjson->data->launchid;
		$parsed->typeid = $resultjson->data->typeid;
		$parsed->sourcedidhash = $resultjson->hash;

		$parsed->messageid = lti_parse_message_id($xml);

		return $parsed;
	}*/

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
	public function GetSubFormat_tjexternaltoolContentInfo($config = array('ltiresource'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 			= array();
		$obj['name']	= $this->params->get('plugin_name', 'Ltiresource');
		$obj['id']		= $this->_name;

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
	public function GetSubFormat_ltiresourceContentHTML($mod_id, $lesson_id, $lesson, $comp_params)
	{
		$result = array();
		$plugin_name = $this->_name;

		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath('creator');
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
	public function additionalltiresourceFormatCheck($lessonId, $mediaObj)
	{
		return $mediaObj;
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
	public function ltiresourcerenderPluginHTML($config)
	{
		$input = Factory::getApplication()->input;
		$user = Factory::getUser();

		/* Update tjlms_lesson_tracking */
		$trackObj   = new stdClass;
		$trackObj->lesson_id		= $config['lesson_id'];
		$trackObj->attempt			= $config['attempt'];
		$trackObj->lesson_status	= 'incomplete';
		$trackObj->total_content	= 0;
		$trackObj->time_spent		= 0;
		$lesson_track_id			= $this->updateData($user->id, $trackObj);

		$launch_base_url = trim($config['file']);

		$lti_uri = Uri::getInstance($launch_base_url);
		$launch_base_url = $lti_uri->getScheme() . '://' . $lti_uri->getHost() . $lti_uri->getPath();
		$urlParams = $lti_uri->getQuery(true);

		$launch_details = json_decode($config['params']);
		$secret = trim($launch_details->secret);
		$key = trim($launch_details->key);
		$lesson = $config['lesson_data'];
		$course = $config['course'];

		$date = Factory::getDate();

		$course_url = JURI::root() . 'index.php?option=com_tjlms&view=course&id=' . $lesson->course_id;

		/*Launch Params*/
		$launch_params = array(
			"user_id" => md5($user->id),
			"roles" => "Learner",
			"resource_link_id" => $lesson->id,
			"resource_link_title" => $lesson->name,
			"resource_link_description" => $lesson->short_desc,
			"lis_person_name_full" => $user->name,
			"lis_person_contact_email_primary" => $user->email,
			"lis_result_sourcedid" => $lesson_track_id,
			"launch_presentation_locale" => "en-GB",
			"launch_presentation_document_target" => $launch_details->launchin,
			"launch_presentation_css_url" => '',
			"launch_presentation_width" => '',
			"launch_presentation_height" => '',
			"launch_presentation_return_url" => $course_url,
			"context_id" => $lesson->course_id,
			"context_type" => 'CourseSection',
			"context_title" => $course->title,
			"context_label" => $course->alias,
			"tool_consumer_instance_guid" => "tjlms",
			"tool_consumer_info_version" => "1.0.3",
			"tool_consumer_info_product_family_code" => $this->params->get('tool_consumer_info_product_family_code'),
			"tool_consumer_instance_name" => "Joomla config site name",
			"tool_consumer_instance_description" => "Site meta description form joomla config",
			"tool_consumer_instance_url" => JURI::root(),
			"tool_consumer_instance_contact_email" => "",
			"custom_canvas_enrollment_state" => "active",
			"custom_canvas_user_id" => $user->id,
			"custom_canvas_user_login_id" => $user->id,
			"custom_canvas_api_domain" => $this->params->get('custom_canvas_api_domain'),
			"custom_canvas_assignment_id" => $lesson->id,
			"custom_canvas_assignment_points_possible" => "100",
			"custom_canvas_assignment_title" => $lesson->name,
			"ext_outcome_data_values_accepted" => 'url,text',
			"ext_ims_lis_basic_outcome_url" => JURI::root() . "index.php?option=com_tjlms&task=listener&type=tjexternaltool.ltiresource",
			"custom_canvas_course_id" => $lesson->course_id,
			"lti_message_type" => 'basic-lti-launch-request',
			"lti_version" => "LTI-1p0",
			"lis_outcome_service_url" => JURI::root() . "index.php?option=com_tjlms&task=listener&type=tjexternaltool.ltiresource",
		);

		// OAuth Core 1.0 spec: http://oauth.net/core/1.0/
		$launch_params["oauth_consumer_key"] = $oauth_params["oauth_consumer_key"] = trim($launch_details->key);
		$launch_params["oauth_signature_method"]  = $oauth_params["oauth_signature_method"] = "HMAC-SHA1";

		$date = Factory::getDate();
		$launch_params["oauth_timestamp"]  = $oauth_params["oauth_timestamp"] = $date->toUnix();

		$launch_params["oauth_version"] = $oauth_params["oauth_version"] = "1.0";
		$launch_params["oauth_callback"] = $oauth_params["oauth_callback"] = "about:blank";

		jimport('joomla.oauth1.client');
		$launch_params["oauth_nonce"] = JOAuth1Client::generateNonce();

		foreach ($urlParams as $key => $value)
		{
			$launch_params[$key] = $value;
		}

		$file_contents[] = "==========INPUT FOR :" . $lesson_track_id . "======";
		$file_contents[] = 'WHAT WE SENT ===>';

		foreach ($launch_params as $key => $value)
		{
			$file_contents[$key] = $value;
		}

		file_put_contents(JPATH_SITE . '/plugins/tjexternaltool/ltiresource/tjltilogs.txt', print_r($file_contents, true) . ' ', FILE_APPEND);

		// In OAuth, request parameters must be sorted by name
		$launch_data_keys = array_keys($launch_params);
		sort($launch_data_keys);
		$launch_data = array();

		foreach ($launch_data_keys as $key)
		{
			array_push($launch_data, $key . "=" . rawurlencode($launch_params[$key]));
		}

		$base_string = "POST&" . urlencode($launch_base_url) . "&" . rawurlencode(implode("&", $launch_data));
		$secret = urlencode($secret) . "&";

		$signature = base64_encode(hash_hmac("sha1", $base_string, $secret, true));

		foreach ($urlParams as $key => $value)
		{
			unset($launch_params[$key]);
		}

		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath('default');
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Used to prepare the base string to create oauth signature
	 * NOT USED IN THIS BUILD
	 *
	 * @param   mixed  $method          Method (POST/GET)
	 * @param   mixed  $url             URL
	 * @param   mixed  $signing_params  Params to be sent to create signature.
	 *
	 * @return  string  $data encoded in a way compatible with OAuth.
	 *
	 * @since   13.1
	 */
	public function prepare_base_string($method, $url, $signing_params)
	{
		$base = array($method, $url, $signing_params);

		return implode('&', $this->safeEncode($base));
	}

	/**
	 * Encodes the string or array passed in a way compatible with OAuth.
	 * If an array is passed each array value will will be encoded.
	 *
	 * @param   mixed  $data  The scalar or array to encode.
	 *
	 * @return  string  $data encoded in a way compatible with OAuth.
	 *
	 * @since   13.1
	 */
	public function safeEncode($data)
	{
		if (is_array($data))
		{
			return array_map(array($this, 'safeEncode'), $data);
		}
		elseif (is_scalar($data))
		{
			return str_ireplace(
				array('+', '%7E'),
				array(' ', '~'),
				rawurlencode($data)
				);
		}
		else
		{
			return '';
		}
	}

	/**
	 * Function to get needed data for this API
	 *
	 * @param   INT    $uid   user_id
	 * @param   ARRAY  $data  lesson track data
	 *
	 * @return  id from tjlms_lesson_tracking
	 *
	 * @since 1.0.0
	 */
	public function updateData($uid, $data)
	{
		$db = Factory::getDBO();
		$input = Factory::getApplication()->input;

		$mode = $input->get('mode', '', 'STRING');

		$trackingid = '';

		if ($mode != 'preview')
		{
			$post = $input->post;
			$lesson_id = $data->lesson_id;
			$oluser_id = $uid;

			$trackObj = new stdClass;
			$trackObj->attempt = $data->attempt;
			$trackObj->score = 0;
			$trackObj->total_content = '';
			$trackObj->current_position = '';
			$trackObj->time_spent = '';

			$lesson_status	=	$data->lesson_status;

			if (!empty($lesson_status))
			{
				$trackObj->lesson_status = $lesson_status;
			}

			if (!empty($data->current_position))
			{
				$current_position = $data->current_position;
				$trackObj->current_position = round($current_position, 2);
			}

			if (!empty($data->total_content))
			{
				$total_content = $data->total_content;
				$trackObj->total_content = round($total_content, 2);
			}

			if (!empty($data->time_spent))
			{
				$time_spent = $data->time_spent;
				$trackObj->time_spent = round($time_spent, 2);
			}

			if (!empty($data->score))
			{
				$score = $data->score;
				$trackObj->score = $score;
			}

			require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';
			$comtjlmstrackingHelper = new comtjlmstrackingHelper;

			$trackingid = $comtjlmstrackingHelper->update_lesson_track($lesson_id, $oluser_id, $trackObj);
		}

		return $trackingid;
	}

	/**
	 * function used to save LTI score
	 *
	 * @return id of tjlms_lesson_track
	 *
	 * @since 1.0.0
	 * */
	public function onScoreUpdate()
	{
		$app = Factory::getApplication();
		$app->setHeader('Content-Type', 'application/xml');

		$input	= Factory::getApplication()->input;
		$lid	= $input->get('lid', '', 'STRING');
		$uid	= $input->get('uid', '', 'STRING');

		$fscore	= $input->get('result_resultscore_textstring', '', 'STRING');

		if ($lid && $fscore && $uid)
		{
			// Get keys against lesson
			$db   = Factory::getDBO();

			$query = $db->getQuery(true);
			$query->select('id');
			$query->from('#__users');
			$query->where('MD5(id) = ' . $db->quote($uid));
			$db->setQuery($query);
			$uid = $db->loadResult();

			$query = $db->getQuery(true);
			$query->select('attempt');
			$query->select('lesson_id');
			$query->from('#__tjlms_lesson_track');
			$query->where('MD5(lesson_id) = ' . $db->quote($lid));
			$query->where('user_id = ' . $uid);
			$db->setQuery($query);
			$track_data = $db->loadObjectList();

			/*
			$query->select('m.source');
			$query->from('#__tjlms_lessons as l');
			$query->join('INNER', '#__tjlms_media as m  ON (l.media_id = m.id)');
			$query->where('l.id = ' . $lid);
			$db->setQuery($query);
			$source = $db->loadResult();

			$launch_details = json_decode($source);
			$key = $launch_details->key;
			$secret = $launch_details->secret;

			require_once  JPATH_SITE . '/plugins/tjexternaltool/ltiresource/lib/OAuth.php';
			require_once  JPATH_SITE . '/plugins/tjexternaltool/ltiresource/lib/OAuthBody.php';

			/* For my application, We only allow application/xml*/

			/*
  			$request_headers = OAuthUtil::get_headers();
			$hdr = $request_headers['Content-Type'];

			if ( ! isset($hdr) )
			{
				$hdr = $request_headers['Content-type'];
			}

			if ($hdr != 'application/xml' )
			{
				header('Content-Type: text/plain');
				die("Must be content type xml");
			}

			$oauth_consumer_key = $key;
			$oauth_consumer_secret = $secret;
			*/

			$response = '<?xml version="1.0" encoding="UTF-8"?>
			<imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/lis/oms1p0/pox">
				<imsx_POXHeader>
					<imsx_POXResponseHeaderInfo>
						<imsx_version>V1.0</imsx_version>
						<imsx_messageIdentifier>%s</imsx_messageIdentifier>
						<imsx_statusInfo>
							<imsx_codeMajor>%s</imsx_codeMajor>
							<imsx_severity>status</imsx_severity>
							<imsx_description>%s</imsx_description>
							<imsx_messageRefIdentifier>%s</imsx_messageRefIdentifier>
						</imsx_statusInfo>
					</imsx_POXResponseHeaderInfo>
				</imsx_POXHeader>
				<imsx_POXBody>%s
				</imsx_POXBody>
			</imsx_POXEnvelopeResponse>';

			/*
			try
			{
				$body = handleOAuthBodyPOST($oauth_consumer_key, $oauth_consumer_secret);

				$xml = new SimpleXMLElement($body);
				$imsx_header = $xml->imsx_POXHeader->children();
				$parms = $imsx_header->children();
				$message_ref = (string) $parms->imsx_messageIdentifier;
				$imsx_body = $xml->imsx_POXBody->children();
				$operation = $imsx_body->getName();
				$parms = $imsx_body->children();
			}
			catch (Exception $e)
			{
				echo sprintf($response, uniqid(), 'failure', $e->getMessage(), 'zzz', "");
				exit();
			}

			$sourcedid = $parms->resultRecord->sourcedGUID->sourcedId;
			$header_key = getOAuthKeyFromHeaders();

			if ( $header_key != $oauth_consumer_key )
			{
				echo sprintf($response, uniqid(), 'failure', "B64=$oauth_consumer_key HDR=$header_key", $message_ref, "");
				exit();
			}

			$top_tag = str_replace("Request", "Response", $operation);

			if ( $operation == "replaceResultRequest" )
			{
				$score  = (string) $parms->resultRecord->result->resultScore->textString;
				$fscore = (float) $score;

				if ( ! is_numeric($score) )
				{
					echo sprintf($response, uniqid(), 'failure', "Score must be numeric", $message_ref, "");
					exit();
				}

				$fscore = (float) $score;

				if ( $fscore < 0.0 || $fscore > 1.0 )
				{
					echo sprintf($response, uniqid(), 'failure', "Score not between 0.0 and 1.0", $message_ref, "");
					exit();
				}
				*/
				// echo sprintf($response, uniqid(), 'success', "Score for $sourcedid is now $score", $fscore);
			/*}
			elseif ( $operation == "readResultRequest" )
			{*/
				$body = '
				<readResultResponse>
				  <result>
					<resultScore>
					  <language>en</language>
					  <textString>%s</textString>
					</resultScore>
				  </result>
				</readResultResponse>';
				$body = sprintf($body, $fscore);
				echo sprintf($response, uniqid(),  'success', "Score read successfully", $fscore, $body);
			/*
			 }
			elseif ( $operation == "deleteResultRequest" )
			{
				unset($_SESSION['tc_outcome']);
				echo sprintf($response, uniqid(), 'success', "Score deleted", 'zzz', "\n<" . $top_tag . "/>");
			}
			else
			{
				echo sprintf($response, uniqid(), 'unsupported', "Operation not supported - $operation", $message_ref, "");
			}
			*/

			/* Update tjlms_lesson_tracking */
			$trackObj   = new stdClass;
			$trackObj->plgtype 			= $this->_type;
			$trackObj->plgname 			= $this->_name;
			$trackObj->plgtask			= 'updateData';
			$trackObj->lesson_id		= $track_data[0]->lesson_id;
			$trackObj->attempt			= $track_data[0]->attempt;
			$trackObj->lesson_status	= 'complete';
			$trackObj->score			= $fscore;

			$lesson_track_id 			= $this->updateData($uid, $trackObj);
		}

		jexit();
	}

	/**
	 * Internal use functions
	 *
	 * @param   STRING  $layout  layout
	 *
	 * @return  string  layout
	 *
	 * @since 1.0.0
	 */
	public function buildLayoutPath($layout)
	{
		$app = Factory::getApplication();
		$core_file 	= dirname(__FILE__) . '/' . $this->_name . '/tmpl/' . $layout . '.php';
		$override = JPATH_BASE . '/templates/' . $app->getTemplate() . '/html/plugins/' . $this->_type . '/' . $this->_name . '/' . $layout . '.php';

		if (File::exists($override))
		{
			return $override;
		}
		else
		{
			return $core_file;
		}
	}

	/**
	 * Function is used to check if the lesson is passable
	 *
	 * @return boolean
	 *
	 * @since 1.3.39
	 */
	public function onisPassable_tjexternaltool()
	{
		return true;
	}
}
