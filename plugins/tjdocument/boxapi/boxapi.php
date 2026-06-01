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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;

if (!defined('DS'))
{
	define('DS', '/');
}

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjdocument_boxapi', JPATH_ADMINISTRATOR);

require_once JPATH_SITE . DS . 'plugins' . DS . 'tjdocument' . DS . 'boxapi' . DS . 'boxapi' . DS . 'lib' . DS . 'box-view-api.php';
require_once JPATH_SITE . DS . 'plugins' . DS . 'tjdocument' . DS . 'boxapi' . DS . 'boxapi' . DS . 'lib' . DS . 'box-view-document.php';

$document = Factory::getDocument();

$document->addStyleSheet(JURI::root(true) . '/plugins/tjdocument/boxapi/boxapi/assets/css/crocodoc.viewer.css');

$document->addScript(JURI::root(true) . '/plugins/tjdocument/boxapi/boxapi/assets/js/crocodoc.viewer.min.js');
$document->addScript(JURI::root(true) . '/plugins/tjdocument/boxapi/boxapi/assets/js/track.js');

// $document->addScript(JURI::root(true) . '/plugins/tjdocument/boxapi/boxapi/assets/js/realtime.js');

$document->addStyleSheet(JURI::root(true) . '/plugins/tjdocument/boxapi/boxapi/assets/css/pop.css');
$document->addStyleSheet(JURI::root(true) . '/plugins/tjdocument/boxapi/boxapi/assets/css/fade.css');
$document->addStyleSheet(JURI::root(true) . '/plugins/tjdocument/boxapi/boxapi/assets/css/slide.css');
$document->addStyleSheet(JURI::root(true) . '/plugins/tjdocument/boxapi/boxapi/assets/css/spin.css');
$document->addStyleSheet(JURI::root(true) . '/plugins/tjdocument/boxapi/boxapi/assets/css/pageflip.css');
$document->addStyleSheet(JURI::root(true) . '/plugins/tjdocument/boxapi/boxapi/assets/css/carousel.css');
$document->addStyleSheet(JURI::root(true) . '/plugins/tjdocument/boxapi/boxapi/assets/css/toolbar.css');


/**
 * Box API plugin
 *
 * @since  1.0.0
 */
class PlgTjdocumentBoxapi extends CMSPlugin
{
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
		$this->apiKey = $this->params->get('appkey', '', 'STRING');
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
	public function onGetSubFormat_tjdocumentContentInfo($config = array('boxapi'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 			= array();
		$obj['name']	= $this->params->get('plugin_name', 'Box API Old (Depreciated)');
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
	public function onGetSubFormat_boxapiContentHTML($mod_id , $lesson_id, $lesson, $comp_params)
	{
		$result = array();
		$plugin_name = $this->_name;

		if (empty($this->apiKey))
		{
			return "<div class='alert alert-error'>" . Text::_("PLG_BOXAPI_NOTCONFIGURED_MSG") . "</div>";
		}

		$ip = gethostbyname('www.google.com');

		if ($ip == 'www.google.com')
		{
			return "<div class='alert alert-error'>" . Text::_("PLG_BOXAPI_NO_NET_CONNECTION") . "</div>";
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
	public function onUploadFilesOnboxapi($lesson_id, $filename = '', $filepath = '')
	{
		if (empty($this->apiKey))
		{
			return false;
		}

		// Ensure we have access to cURL.
		if (!$this->curlInstalled())
		{
			return array("res" => 0, "error" => 'cURL extension not found.');
		}

		try
		{
			$box     = new Box_View_API($this->apiKey);
			$doc = new Box_View_Document(array('name' => $filename, 'file_path' => $filepath));

			// Call box api to upload the file
			$upload_result = $box->upload($doc);
		}

		catch (Exception $e)
		{
			if ($e->getCode() == 401)
			{
				return array("res" => 0, "error" => Text::_("PLG_BOXAPI_WRONG_KEY_ERROR_MESSAGE"));
			}
		}

		if ($upload_result)
		{
			return array("res" => 1, "document_id" => $upload_result->id);
		}

		return $upload_result;
	}

	/**
	 * Checks whether or not PHP has the cURL extension enabled.
	 *
	 * @return bool
	 *   Returns TRUE if cURL if is enabled.
	 */
	private function curlInstalled()
	{
		return in_array('curl', get_loaded_extensions()) && function_exists('curl_version');
	}

	/**
	 * Create a session for viewing a document
	 *
	 * @param   INT  $document_id  document id provided by Box API
	 *
	 * @return  session data
	 *
	 * @since 1.0.0
	 */
	public function getSessionForDocument($document_id)
	{
		$doc = new Box_View_Document(array('id' => $document_id));

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
			// Return a error message for the viewer
			return false;
		}

		// As we got the status as 'done' we can proceed to get the session for viewing the doc
		$getSession = $box->view($doc);

		return $getSession;
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
	public function onAdditionalboxapiFormatCheck($lessonId, $mediaObj)
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
	public function onboxapirenderPluginHTML($data)
	{
		$html             = $realtime_url  = '';
		$document_id      = $data['document_id'];
		$checksessionUrl  = '';
		$getSession       = '';
		$loadingImagePath = Uri::root() . 'components/com_tjlms/assets/images/ajax.gif';

		$SelectedLayout      = $this->params->get('doc_layout', '', 'STRING');
		$layoutOptionForUser = $this->params->get('doc_layout_ft_option', '', 'STRING');

		// Check if session already present. So need to create a new seesion.
		$checksessionUrl = $this->checkSessionForDocument($data['lesson_id']);

		$input = Factory::getApplication()->input;
		$mode = $input->get('mode', '', 'STRING');

		$pluginUrl = 'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjdocument';

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

				if (isset($checksessionUrl->realtime_url))
				{
					$realtime_url = $checksessionUrl->realtime_url;
				}
			}
			else
			{
				$url_to_use   = $getSession->urls->assets;

				if (isset($getSession->urls->realtime_url))
				{
					$realtime_url = $getSession->urls->realtime_url;
				}
			}

			$html = '

					<div id="viewer_container" class="viewer_container"  >

						<!-- DIV WHICH RENDER DOCUMENT-->
						<div class="viewer" ></div>';
			$app = Factory::getApplication();
			$params = ComponentHelper::getParams('com_tjlms');
			$toolbar_position = $params->get('tjlms_toolbar_option', '1', 'INT');

			if ($toolbar_position == 0)
			{
				$html .= '<!--TOOLBAR FOR THE DOCUMENT-->
						<div class="viewer_toolbar row-fluid viewer_toolbar_jlike" style="display:none">';
			}
			else
			{
				$html .= '<!--TOOLBAR FOR THE DOCUMENT-->
						<div class="viewer_toolbar row-fluid" style="display:none">';
			}

			if ($layoutOptionForUser == 1)
			{
				$html .= '
						<div class="viewer_controls view-left viewer_layouts_container">
							<select  class="viewer_layouts transparent-button" onchange="setMode(this.value)" >
								<option value="plain">Normal</option>
								<option value="pop">Pop</option>
								<option value="fade">Fade</option>
								<option value="spin">Spin</option>
								<option value="slide">Slide</option>
								<option value="carousel">Carousel</option>
								<option value="book">Book</option>
							</select>
						</div>
							';
			}

			$html .= '
							<div class="viewer_controls view-center viewer_nav_conatainer">
								<button class="doc-prev prevpagebtn btn btn-small" onclick="previouspage()"><i class="fa fa-angle-left "></i></button>
									<button class="pagedetails transparent-button" onclick="enable_gotopage()">
										<span class="currentpage blackcolor" ></span>
										<span class="totalPagesSpan blackcolor" ></span>
									</button>
									<input type="text"  class="input-small viewer_gotopage" style="display:none"  onblur="gotopage()">
								<button class="doc-next nextpagebtn btn btn-small" onclick="nextpage()"><i class="fa fa-angle-right"></i></button>
							</div>
							<div class="viewer_controls  view-right viewer_zoom_conatainer">
								<button class="doc-zoom transparent-button" onclick="zoomin()"><i class="fa fa-search-plus"></i></button>
								<button class="doc-zoom transparent-button" onclick="zoomout()"><i class="fa fa-search-minus"></i></button>
							</div>
						</div><!--TOOLBAR ENDS-->


					</div><!--VIEWER CONTAINER ENDS-->



					<script type="text/javascript">

						jQuery(window).load(function () {

								var player_height = jQuery(".tjlms_lesson_screen", top.document).height();
								if(!player_height)
									player_height = jQuery(this).height();

								jQuery(".viewer_container").height(player_height-100);
								jQuery(".viewer_layouts").val("' . $SelectedLayout . '");
						});

						setTimeout(hideImage, 5000);

						jQuery(document).keypress(function(e) {
							if(e.which == 13) {
								gotopage();
							}
						});

						var url = "' . $url_to_use . '";

						var doc_object = [];
						doc_object["current_position"] = ' . $data['current'] . ';
						doc_object["total_time"] = 0;
						doc_object["user_id"] = ' . $data['user_id'] . ';
						doc_object["lesson_id"] = ' . $data['lesson_id'] . ';

						/* Function to allow user to enter the number of the page he wants to visit */
						function enable_gotopage()
						{
							jQuery(".pagedetails").hide();
							jQuery(".viewer_gotopage").show();
							jQuery(".viewer_gotopage").css("display","inline-block");
							jQuery(".viewer_gotopage").focus();
						}

						/* Function to go to page directly */
						function gotopage()
						{
							jQuery(".pagedetails").show();
							jQuery(".viewer_gotopage").hide();
							var pagenumberToVisit = jQuery(".viewer_gotopage").val();

							if (pagenumberToVisit)
							viewer.scrollTo(pagenumberToVisit);
						}

						/* Function to go next page */
						function nextpage()
						{
							viewer.scrollTo(Crocodoc.SCROLL_NEXT);
						}

						/* Function to go to previous directly */
						function previouspage()
						{
							viewer.scrollTo(Crocodoc.SCROLL_PREVIOUS);
						}

						/* Function to zoom in */
						function zoomin()
						{
							viewer.zoom(Crocodoc.ZOOM_IN);
						}

						/* Function to zoom out */
						function zoomout()
						{
							viewer.zoom(Crocodoc.ZOOM_OUT);
						}

						/* Function to set mode as per preference */
						function setMode(mode)
						{
							jQuery(".controls button").removeClass("selected");
							jQuery(".controls button." + mode).addClass("selected");

							switch (mode)
							{
								case "pop":
									viewer.setLayout(Crocodoc.LAYOUT_PRESENTATION);
									viewer.zoom(Crocodoc.ZOOM_AUTO);
									jQuery("body").removeClass().addClass("crocodoc-presentation-pop");
									break;

								case "fade":
									viewer.setLayout(Crocodoc.LAYOUT_PRESENTATION);
									viewer.zoom(Crocodoc.ZOOM_AUTO);
									jQuery("body").removeClass().addClass("crocodoc-presentation-fade");
									break;

								case "spin":
									viewer.setLayout(Crocodoc.LAYOUT_PRESENTATION);
									viewer.zoom(Crocodoc.ZOOM_AUTO);
									jQuery("body").removeClass().addClass("crocodoc-presentation-spin");
									break;

								case "slide":
									viewer.setLayout(Crocodoc.LAYOUT_PRESENTATION);
									viewer.zoom(Crocodoc.ZOOM_AUTO);
									jQuery("body").removeClass().addClass("crocodoc-presentation-slide");
									break;

								case "carousel":
									viewer.setLayout(Crocodoc.LAYOUT_PRESENTATION);
									viewer.zoom(Crocodoc.ZOOM_AUTO);
									viewer.zoom(Crocodoc.ZOOM_OUT);
									jQuery("body").removeClass().addClass("crocodoc-presentation-carousel");
									break;

								case "book":
									viewer.setLayout(Crocodoc.LAYOUT_PRESENTATION_TWO_PAGE);
									viewer.zoom(Crocodoc.ZOOM_AUTO);
									viewer.zoom(Crocodoc.ZOOM_OUT);
									jQuery("body").removeClass().addClass("crocodoc-pageflip");
									break;
								case "plain":
									viewer.setLayout(Crocodoc.LAYOUT_VERTICAL);
									viewer.zoom(Crocodoc.ZOOM_AUTO);
									jQuery("body").removeClass();
									break;
							}
						}


						var viewer = Crocodoc.createViewer(".viewer", {
							url: url,
							plugins: {
								// config for the analytics plugin
								analytics: {
									ontrack: function (page, seconds) {

										/* Get the time spent on the page */
										doc_object["total_time"] = seconds;

										jQuery.ajax({
												type: "POST",
												url: "' . Uri::root() . $pluginUrl . '&plgName=' . $this->_name . '&plgtask=updateData&mode=' . $mode . '",
												data: {
													user_id: doc_object["user_id"],
													lesson_id: doc_object["lesson_id"],
													current_position: doc_object["current_position"],
													total_time: doc_object["total_time"],
													total_content: doc_object["total_content"],
													attempt : ' . $data['attempt'] . '
												},
												dataType: "JSON",
												beforeSend: function() {
													/*loadingImage();*/
													jQuery(".viewer_controls .doc-prev").addClass("viewer-btn-disabled");
													jQuery(".viewer_controls .doc-next").addClass("viewer-btn-disabled");
												},
												success: function(data) {
													/*hideImage();*/
													jQuery(".viewer_toolbar").show();
													jQuery(".viewer_controls .doc-next").removeClass("viewer-btn-disabled");
													jQuery(".viewer_controls .doc-prev").removeClass("viewer-btn-disabled");
												}
											});
									}
								}
							}
						});


						viewer.on("ready", function (event) {
							hideImage();
							jQuery(".viewer_toolbar").show();
							jQuery(".currentpage").html(' . $data['current'] . ');

							var wheight = jQuery("#main_doc_container").height();
							var wwidth = jQuery("#main_doc_container").width();

							/*jQuery(".crocodoc-doc").height(wheight);
							jQuery(".crocodoc-doc").width(wwidth);*/

							/*showloading(0);*/


							/* Get total number of pages of the document */
							doc_object["total_content"] = event.data.numPages;

							/* Set total pages in toolbar */
							jQuery(".totalPagesSpan").html("/" +event.data.numPages);

							/* if the navigated page number is one disable prev button*/
							if(' . $data['current'] . ' == 1)
							{
								jQuery(".viewer_controls .doc-prev").addClass("viewer-btn-disabled");
							}

							/* if number of pages ==1 disable both prev and next button*/
							if(event.data.numPages == 1)
							{
								jQuery(".viewer_controls .doc-prev").addClass("viewer-btn-disabled");
								jQuery(".viewer_controls .doc-next").addClass("viewer-btn-disabled");
							}


							/* Page change on next and previous keyboard buttons */
							jQuery(window).on("keydown", function (ev)
							{
								if (ev.keyCode === 37) {
									viewer.scrollTo(Crocodoc.SCROLL_PREVIOUS);
								} else if (ev.keyCode === 39) {
									viewer.scrollTo(Crocodoc.SCROLL_NEXT);
								} else {
									return;
								}
								ev.preventDefault();
							});

							/*
							if ("' . $SelectedLayout . '" !== "plain")
							{
								jQuery(window).bind("mousewheel", function(e){
									if(e.originalEvent.wheelDelta /120 > 0) {
										viewer.zoom(Crocodoc.ZOOM_IN);
									}
									else{
										viewer.zoom(Crocodoc.ZOOM_OUT);
									}
								});
							}*/

							/* Id totla number of pages is 1 .. then save the status as completetd */
							if (event.data.numPages == 1)
							{
								doc_object["current_position"] = 1;
								setInterval(function(){
									jQuery.ajax({
										type: "POST",
										url: "' . Uri::root() . $pluginUrl . '&plgName=' . $this->_name . '&plgtask=updateData&mode=' . $mode . '",
										data: {
											user_id: doc_object["user_id"],
											lesson_id: doc_object["lesson_id"],
											current_position: doc_object["current_position"],
											total_time: 5,
											total_content: doc_object["total_content"],
											attempt : ' . $data['attempt'] . '
										},
										dataType: "JSON",
										success: function(data) {

										}
								});
								},5000)
							}


							setMode("' . $SelectedLayout . '");


							/* On continuing old attempt scroll directly to last visited page */
							viewer.scrollTo(' . $data['current'] . ');

							/* Save data of the user. time spent and current position */
							viewer.on("pagefocus", function (ev) {

								jQuery(".currentpage").html(ev.data.page);
								doc_object["current_position"] = ev.data.page;

								jQuery(".viewer_controls .doc-next").removeClass("viewer-btn-disabled");
								jQuery(".viewer_controls .doc-prev").removeClass("viewer-btn-disabled");

								if(ev.data.page == ev.data.numPages)
								{
									jQuery(".viewer_controls .doc-next").addClass("viewer-btn-disabled");
								}

								if(ev.data.page == 1)
								{
									jQuery(".viewer_controls .doc-prev").addClass("viewer-btn-disabled");
								}

							});
						});

						/* Load the viewer */
						viewer.load();

					</script>

					<style>
						.viewer {
							height: 100%;
						}

					</style>
			';
		}

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
		$input = Factory::getApplication()->input;

		$mode = $input->get('mode', '', 'STRING');

		if ($mode != 'preview')
		{
			$post             = $input->post;
			$lesson_id        = $post->get('lesson_id', '', 'INT');
			$oluser_id        = $post->get('user_id', '', 'INT');

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
	 * Check session ID for a document whether expired or not
	 *
	 * @param   INT  $lesson_id  saved file name
	 *
	 * @return boolean
	 *
	 * @since 1.0.0
	 */
	public function checkSessionForDocument($lesson_id)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('m.params');
		$query->from('#__tjlms_media as m');
		$query->join('LEFT', '#__tjlms_lessons as l ON l.media_id=m.id');
		$query->where('l.id=' . $lesson_id);
		$db->setQuery($query);
		$params = $db->loadresult();

		$jsonDecodedParams = json_decode($params);

		if (isset($jsonDecodedParams->expire_at))
		{
			$expire_at = $jsonDecodedParams->expire_at;

			// Convert the time in Y-m-d H:i:s format
			$expire_at = date("Y-m-d H:i:s", strtotime($expire_at));

			// Get current time to compare
			$current_time = date('Y-m-d H:i:s');

			$date = new DateTime($expire_at);
			$now  = new DateTime;

			// Compare the two timings
			$time_diff = $date->diff($now)->invert;

			// If session is present and not expired return the params
			if ($time_diff == 1)
			{
				return $jsonDecodedParams;
			}
		}

		return false;
	}

	/**
	 * Store session ID for a document in tjlms_media table
	 *
	 * @param   String  $sessionData  original file name
	 * @param   INT     $lesson_id    saved file name*
	 *
	 * @return boolean
	 *
	 * @since 1.0.0
	 */
	public function storeSessionData($sessionData, $lesson_id)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('m.id, m.params');
		$query->from('#__tjlms_media as m');
		$query->join('LEFT', '#__tjlms_lessons as l ON l.media_id=m.id');
		$query->where('l.id=' . $lesson_id);
		$db->setQuery($query);
		$mediaData = $db->loadObject();

		$jsonDecodedParams               = json_decode($mediaData->params);
		$jsonDecodedParams->session_id   = $sessionData->id;
		$jsonDecodedParams->expire_at    = $sessionData->expires_at;
		$jsonDecodedParams->assets_url   = $sessionData->urls->assets;
		$jsonDecodedParams->realtime_url = $sessionData->urls->realtime;

		$jsonEncodedParam = json_encode($jsonDecodedParams);

		$object         = new stdClass;
		$object->params = $jsonEncodedParam;
		$object->id     = $mediaData->id;

		if (!$db->updateObject('#__tjlms_media', $object, 'id'))
		{
			echo $db->stderr();

			return false;
		}

		return true;
	}
}
