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
defined('_JEXEC') or die( 'Restricted access' );
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;

$input = Factory::getApplication()->input;
$lesson	= $input->get('lesson_id', '', 'INT');
$scorm	= $input->get('scorm', '', 'INT');
$scoid	= $input->get('scoid', '', 'INT');
$attempt =	$input->get('attempt', '', 'INT');
$mode =	$input->get('mode', '', 'STRING');

$userid =	Factory::getUser()->id;

$tjlmsTrackingHelper	=	new comtjlmstrackingHelper;
$scormhelper	=	new comtjlmsScormHelper;

$sco_data	=	$scormhelper->getSCOdata($scorm, $scoid);
$scormdata	=	$scormhelper->getScormData($scorm);

/*Get the scorm folder name from package*/
$ext = pathinfo($scormdata->package, PATHINFO_EXTENSION);
$scormFoldername = basename($scormdata->package, "." . $ext);
$result	= JURI::base() . 'media/com_tjlms/lessons/' . $lesson . '/scorm/' . $sco_data->launch;
if ($scormFoldername && Folder::exists(JPATH_SITE . '/media/com_tjlms/lessons/' . $lesson . '/'. $scormFoldername))
{
	$result	= JURI::base() . 'media/com_tjlms/lessons/' . $lesson . '/'. $scormFoldername .'/' . $sco_data->launch;
}

$version = $scormdata->version;

if ($mode !='preview')
{
	$scormhelper->scorm_insert_track($userid, $scorm, $scoid, $attempt, 'x.start.time', time());

	$trackObj = new stdClass;
	$trackObj->attempt = $attempt;
	$trackObj->lesson_status = 'started';
	$trackObj->score = '';

	$tjlmsTrackingHelper->update_lesson_track($lesson, $userid, $trackObj);

	if ($sco_data->scormtype == 'asset')
	{
		$element = ($version	!=	'SCORM_1.2') ? 'cmi.completion_status' : 'cmi.core.lesson_status';
		$value = 'completed';
		$res = $scormhelper->scorm_insert_track($userid, $scorm, $scoid, $attempt, $element, $value);
	}
}

if ($sco_data->scormtype == 'asset')
{
	// HTTP 302 Found => Moved Temporarily.
	header('Location: ' . $result);

	// Provide a short feedback in case of slow network connection.
	echo '<html><body><p>' . 'activitypleasewait' . '</p></body></html>';
	exit;
}
?>
<?php
/*

if (!empty($id)) {
	if (! $cm = get_coursemodule_from_id('scorm', $id)) {
		print_error('invalidcoursemodule');
	}
	if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
		print_error('coursemisconf');
	}
	if (! $scorm = $DB->get_record('scorm', array('id'=>$cm->instance))) {
		print_error('invalidcoursemodule');
	}
} else if (!empty($a)) {
	if (! $scorm = $DB->get_record('scorm', array('id'=>$a))) {
		print_error('coursemisconf');
	}
	if (! $course = $DB->get_record('course', array('id'=>$scorm->course))) {
		print_error('coursemisconf');
	}
	if (! $cm = get_coursemodule_from_instance('scorm', $scorm->id, $course->id)) {
		print_error('invalidcoursemodule');
	}
} else {
	print_error('missingparameter');
}
$PAGE->set_url('/mod/scorm/loadSCO.php', array('scoid'=>$scoid, 'id'=>$cm->id));

if (!isloggedin()) { // Prevent login page from being shown in iframe.
	Using simple html instead of exceptions here as shown inside iframe/object.
	echo html_writer::start_tag('html');
	echo html_writer::tag('head', '');
	echo html_writer::tag('body', get_string('loggedinnot'));
	echo html_writer::end_tag('html');
	exit;
}

require_login($course, false, $cm, false); // Call require_login anyway to set up globals correctly.

check if scorm closed
$timenow = time();
if ($scorm->timeclose !=0) {
	if ($scorm->timeopen > $timenow) {
		print_error('notopenyet', 'scorm', null, userdate($scorm->timeopen));
	} else if ($timenow > $scorm->timeclose) {
		print_error('expired', 'scorm', null, userdate($scorm->timeclose));
	}
}

$context = context_module::instance($cm->id);

if (!empty($scoid)) {

	Direct SCO request
	if ($sco = scorm_get_sco($scoid)) {
		if ($sco->launch == '') {
			Search for the next launchable sco
			if ($scoes = $DB->get_records_select(
					'scorm_scoes',
					'scorm = ? AND '.$DB->sql_isnotempty('scorm_scoes', 'launch', false, true).' AND id > ?',
					array($scorm->id, $sco->id),
					'id ASC')) {
				$sco = current($scoes);
			}
		}
	}
}
If no sco was found get the first of SCORM package
if (!isset($sco)) {
	$scoes = $DB->get_records_select(
		'scorm_scoes',
		'scorm = ? AND '.$DB->sql_isnotempty('scorm_scoes', 'launch', false, true),
		array($scorm->id),
		'id ASC'
	);
	$sco = current($scoes);
}

if ($sco->scormtype == 'asset') {
	$attempt = scorm_get_last_attempt($scorm->id, $USER->id);
	$element = (scorm_version_check($scorm->version, SCORM_13)) ? 'cmi.completion_status':'cmi.core.lesson_status';
	$value = 'completed';
	$result = scorm_insert_track($USER->id, $scorm->id, $sco->id, $attempt, $element, $value);
}
Forge SCO URL
$connector = '';
$version = substr($scorm->version, 0, 4);
if ((isset($sco->parameters) && (!empty($sco->parameters))) || ($version == 'AICC')) {
	if (stripos($sco->launch, '?') !== false) {
		$connector = '&';
	} else {
		$connector = '?';
	}
	if ((isset($sco->parameters) && (!empty($sco->parameters))) && ($sco->parameters[0] == '?')) {
		$sco->parameters = substr($sco->parameters, 1);
	}
}

if ($version == 'AICC') {
	require_once("$CFG->dirroot/mod/scorm/datamodels/aicclib.php");
	$aicc_sid = scorm_aicc_get_hacp_session($scorm->id);
	if (empty($aicc_sid)) {
		$aicc_sid = sesskey();
	}
	$sco_params = '';
	if (isset($sco->parameters) && (!empty($sco->parameters))) {
		$sco_params = '&'. $sco->parameters;
	}
	$launcher = $sco->launch.$connector.'aicc_sid='.$aicc_sid.'&aicc_url='.$CFG->wwwroot.'/mod/scorm/aicc.php'.$sco_params;
} else {
	if (isset($sco->parameters) && (!empty($sco->parameters))) {
		$launcher = $sco->launch.$connector.$sco->parameters;
	} else {
		$launcher = $sco->launch;
	}
}

if (scorm_external_link($sco->launch)) {
	TODO: does this happen?
	$result = $launcher;
} else if ($scorm->scormtype === SCORM_TYPE_EXTERNAL) {
	Remote learning activity
	$result = dirname($scorm->reference).'/'.$launcher;
} else if ($scorm->scormtype === SCORM_TYPE_IMSREPOSITORY) {
	Repository
	$result = $CFG->repositorywebroot.substr($scorm->reference, 1).'/'.$sco->launch;
} else if ($scorm->scormtype === SCORM_TYPE_LOCAL or $scorm->scormtype === SCORM_TYPE_LOCALSYNC) {
	note: do not convert this to use get_file_url() or moodle_url()
	SCORM does not work without slasharguments and moodle_url() encodes querystring vars
	$result = "$CFG->wwwroot/pluginfile.php/$context->id/mod_scorm/content/$scorm->revision/$launcher";
}

add_to_log($course->id, 'scorm', 'launch', 'view.php?id='.$cm->id, $result, $cm->id);

header('Content-Type: text/html; charset=UTF-8');

if ($sco->scormtype == 'asset') {
	HTTP 302 Found => Moved Temporarily.
	header('Location: ' . $result);
	Provide a short feedback in case of slow network connection.
	echo '<html><body><p>' . get_string('activitypleasewait', 'scorm'). '</p></body></html>';
	exit;
}
*/

// We expect a SCO: select which API are we looking for.

$LMS_api = (($version	==	'SCORM_1.2') || empty($version)) ? 'API' : 'API_1484_11';


?>

		<script type="text/javascript">
			doredirect();
		//<![CDATA[

		var myApiHandle = null;
		var myFindAPITries = 0;

		function myGetAPIHandle() {
		   myFindAPITries = 0;
		   if (myApiHandle == null) {
			  myApiHandle = myGetAPI();
		   }
		   return myApiHandle;
		}

		function myFindAPI(win) {

		   while ((win.<?php echo $LMS_api; ?> == null) && (win.parent != null) && (win.parent != win)) {

			  myFindAPITries++;
			  // Note: 7 is an arbitrary number, but should be more than sufficient
			  if (myFindAPITries > 10) {
				 return null;
			  }
			 win	=	win.parent;
		   }
		   return win.<?php echo $LMS_api; ?>;
		}

		// hun for the API - needs to be loaded before we can launch the package
		function myGetAPI() {
		   var theAPI = myFindAPI(window);

		   if ((theAPI == null) && (window.opener != null) && (typeof(window.opener) != "undefined")) {
			  theAPI = myFindAPI(window.opener);
		   }
		   if (theAPI == null) {
			  return null;
		   }
		   return theAPI;
		}

	   function doredirect() {
		   <?php if($this->jDebug == 1){ ?>
			 console.log(myGetAPIHandle());
			<?php } ?>

			if (myGetAPIHandle() != null) {
			location = "<?php echo $result ?>";
			}
			else {
				document.body.innerHTML = "<p> " +
				                           <?php echo 'activityloading';?> +
				                           "<span id='countdown'>" +
				                           <?php echo '2' ?> +
				                           "</span>" +
				                            <?php echo 'numseconds';?> +
				                            "&nbsp; <p>";
				var e = document.getElementById("countdown");
				var cSeconds = parseInt(e.innerHTML);
				var timer = setInterval(function() {
												if( cSeconds && myGetAPIHandle() == null ) {
													e.innerHTML = --cSeconds;
												} else {
													clearInterval(timer);
													document.body.innerHTML = "<p><?php echo 'activitypleasewait';?></p>";
												  location = "<?php echo $result ?>";
												  //jQuery('#scorm_object').attr("data", "<?php echo $result ?>");
												}
											}, 1000);
			}
		}

		//]]>
		</script>


