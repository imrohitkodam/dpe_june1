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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

/**
 * Box API plugin
 *
 * @since  1.0.0
 */
class PlgTjdocumentBoxapi2 extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * @var object
	 */
	protected $setting;

	/**
	 * Plugin that supports uploading and tracking the PPTs PDFs documents of Box API
	 *
	 * @param   string   &$subject  The context of the content being passed to the plugin.
	 * @param   integer  $config    Optional page number. Unused. Defaults to zero.
	 *
	 * @since 1.0.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->initializeSetting();
	}

	/**
	 * Plugin that supports uploading and tracking the PPTs PDFs documents of Box API
	 *
	 * @return  object.
	 *
	 * @since 1.0.0
	 */
	public function initializeSetting()
	{
		$this->setting       = new stdClass;
		$this->setting->client_id     = $this->params->get('boxapi2_client_id', '', 'STRING');
		$this->setting->client_secret = $this->params->get('boxapi2_client_secret', '', 'STRING');
		$this->setting->enterpriseID  = $this->params->get('boxapi2_enterpriseID', '', 'STRING');
		$this->setting->publicKeyID    = $this->params->get('boxapi2_publicKeyID', '', 'STRING');
		$this->setting->passphrase    = $this->params->get('boxapi2_passphrase', '', 'STRING');
		$this->setting->privatekey    = $this->params->get('boxapi2_privatekey', '', 'STRING');
		$this->setting->boxjson       = $this->params->get('boxapi2_boxjson', '', 'STRING');

		return $this->setting;
	}

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
	public function onGetSubFormat_tjdocumentContentInfo($config = array('boxapi2'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 			   = array();
		$obj['name']	   = Text::_($this->params->get('plugin_name', 'PLG_TJDOCUMENT_BOXAPI2_PLUGIN_NAME'));
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
	public function onGetSubFormat_boxapi2ContentHTML($mod_id , $lesson_id, $lesson, $comp_params)
	{
		$result = array();
		$plugin_name = $this->_name;

		if (empty($this->setting->client_id) || empty($this->setting->client_secret))
		{
			return "<div class='alert alert-error'>" . Text::_("PLG_BOXAPI2_NOTCONFIGURED_MSG") . "</div>";
		}

		$ip = gethostbyname('www.google.com');

		if ($ip == 'www.google.com')
		{
			return "<div class='alert alert-error'>" . Text::_("PLG_BOXAPI2_NO_NET_CONNECTION") . "</div>";
		}

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
	public function onUpload_filesOnboxapi2($lesson_id, $filename = '', $filepath = '')
	{
		if (empty($this->setting->client_id) || empty($this->setting->client_secret))
		{
			return false;
		}

		JLoader::import('plugins.tjdocument.boxapi2.classes.boxapi', JPATH_SITE);
		$boxapi = new BoxApi($this->setting);
		$access_token = $boxapi->createJWTToken();

		$docresult = array();
		$error = '';
		$res   = 0;

		if (!$access_token)
		{
			$error = $boxapi->getError();
		}
		else
		{
			ini_set('max_execution_time', 300);
			$boxapi->access_token = $access_token;
			$upload_result = $boxapi->sendFileToBox($filename, $filepath);

			if ($upload_result)
			{
				$res = 1;
				$docresult['document_id'] = $upload_result;
			}
			else
			{
				$error = implode('<br>', $boxapi->getErrors());
			}
		}

		$docresult['res'] = $res;

		// If no success and no error, set explicitly
		if (!$res && !$error)
		{
			$docresult['error'] = 'Something went wrong.';
		}
		elseif ($error)
		{
			$docresult['error'] = $error;
		}

		return $docresult;
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
	public function onAdditionalboxapi2FormatCheck($lessonId, $mediaObj)
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
	// public function onboxapi2renderPluginHTML($data)
	public function onBoxapi2renderPluginHTML($data)
	{
		$error = $url_to_use = '';
		$document_id = $data['document_id'];
		$olUserId = Factory::getUser()->get('id');
		$document = Factory::getDocument();

		// &expiring_embed_link=false
		// &fields=expiring_embed_link

		if (!$document_id)
		{
			$error  = Text::_('PLG_TJDOCUMENT_BOXAPI2_DOCUMENT_ID_IS_MISSING');
		}
		else
		{
			JLoader::import('plugins.tjdocument.boxapi2.classes.boxapi', JPATH_SITE);
			$boxapi = new BoxApi($this->setting);
			$access_token = $boxapi->createJWTToken();

			$pluginUrl    = Uri::root() . 'index.php?option=com_tjlms&task=callSysPlgin&plgType=' . $this->_type . '&plgName=' . $this->_name;
			$input 		  = Factory::getApplication()->input;
			$data['mode'] = $input->get('mode', '', 'STRING');

			if (!$olUserId)
			{
				$data['mode'] = 'preview';
			}

			$data['time_sync_url']  = $pluginUrl . '&plgtask=updateData&mode=' . $data['mode'];
			$data['debug'] = Jfactory::getConfig()->get('debug', false);
			unset($data['source']);

			if (!$access_token)
			{
				$error  = $boxapi->getError();
			}

			$lang     = Factory::getLanguage();
			$langCode = $lang->getTag();

			if (!in_array($langCode, $boxapi->supportedLanguage))
			{
				$langCode = 'en-US';
			}

			// Polyfill.io only loads a Promise polyfill if your browser needs one
			$document->addScript('https://cdn.polyfill.io/v2/polyfill.min.js?features=Promise');
			$document->addScript('https://cdn01.boxcdn.net/platform/preview/1.8.0/' . $langCode . '/preview.js');
			$document->addStyleSheet('https://cdn01.boxcdn.net/platform/preview/1.8.0/' . $langCode . '/preview.css');
			
			$document->addStyleSheet(Juri::root(true) . '/plugins/' . $this->_type . '/' . $this->_name . '/assets/css/boxapi2.css?v=1.2');
		}

		$document->addScript(Juri::root(true) . '/plugins/' . $this->_type . '/' . $this->_name . '/assets/js/boxapi2.js?v=1.1');

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

			/* $trackingid = $comtjlmstrackingHelper->update_lesson_track(
			 * 	$lesson_id, $oluser_id, $attempt, $score, $status, $u_id, $total_content, $cur_pos, $time_spent);*/

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

	/**
	 * Ajax function to be called via Ajax
	 *
	 * @return MIX JSON Result
	 *
	 * @since 1.0.0
	 */
	public function onAjaxBoxapi2()
	{
		$input = Factory::getApplication()->input;
		$result = array('success' => false, 'error' => '');

		JLoader::import('plugins.tjdocument.boxapi2.classes.migration', JPATH_SITE);
		$migrate = new BoxApiMigration;

		$token = $input->get('token', '');

		if ($token)
		{
			$migrate->token = $token;
		}

		$canMigrate = $migrate->canMigrate();

		if ($canMigrate)
		{
			$subTask = $input->get('subtask', '');
			$lastMediaId = $input->get('last_id', '');

			if ($subTask == 'gettotal')
			{
				$result['total']   = $migrate->getTotalDocsToMigrate();
				$result['success'] = true;
				$result['token']   = $migrate->token;
			}
			else
			{
				$media_id = $migrate->migrateMedia($lastMediaId);

				if ($media_id)
				{
					$result['lastId'] = $media_id;
				}

				$errors = $migrate->getErrors();

				if (!$errors)
				{
					$result['success'] = true;
				}
				else
				{
					$result['error'] = implode('<br>', $errors);
				}
			}
		}
		else
		{
			$result['error'] = Text::_('JERROR_ALERTNOAUTHOR');
		}

		// $result['data'] = $migrate;

		echo json_encode($result);
		exit;
	}
}
