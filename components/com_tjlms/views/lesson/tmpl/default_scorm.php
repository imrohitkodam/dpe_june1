<?php
/**
 * @package InviteX
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
* @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
* @link     http://www.techjoomla.com
*/
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
jimport('joomla.html.pane');

?>
<script>
var site_root = "<?php echo JURI::base();?>";
var errorCode = "0";
function underscore(str) {
    str = String(str).replace(/.N/g,".");
    return str.replace(/\./g,"__");
}
jQuery(window).load(function () {

	var height = jQuery(".tjlms_lesson_screen", top.document).height();
	if(!height)
		height = jQuery(this).height();
	jQuery("object").css("height",height-50);
	jQuery("object").css("width",'100%');
	jQuery("#scorm_object").css("height",height-50);
	jQuery("#scorm_object").css("width",'100%');

	hideImage();
});
</script>

<?php
$input = Factory::getApplication()->input;
$ol_user=Factory::getUser();
$scorm_data	=	(object)$this->additionalReqData;
$canaccess=$flag=0;
$enrol_approval=0;
$canaccess=1;
$flag=1;
$enrol_approval=1;
$attemp_flag=0;

// Get plugin launch in param set in plugin
$plugin = PluginHelper::getPlugin('tjscorm', 'nativescorm');
$pluginParams = new Registry($plugin->params);
$launch_in = $pluginParams->get('launch_in', 'iframe');

if (!empty($this->sub_format))
{
	$config = array();
	$config['sourcefilename']	= $this->sourcefilename;
	$config['file']	= $this->source;
	$config['mid']	= $this->lesson->media_id;
	$config['lesson_id'] = $this->lesson_id;
	$config['attempt'] = $this->attempt;
	$config['current'] = 1;

	if (!empty($this->lastattempttracking_data))
	{
		$config['current'] = $this->lastattempttracking_data->current_position;
	}

	// Trigger all sub format  video plugins method that renders the video player
	PluginHelper::importPlugin('tjscorm', $this->pluginToTrigger);
	$result = Factory::getApplication()->triggerEvent('on' . $this->pluginToTrigger . 'renderPluginHTML', array($config));

	echo $result[0];
}

?>


<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">
	<form method='POST' name='adminForm' id='adminForm' action='' class="no-margin">

	<?php
		$scormhelper = $this->comtjlmsScormHelper;
		$scoid = $this->entrysco = $input->get('sco_id', $scorm_data->entry, 'INT');
		$version = strtolower($scorm_data->version);

		$scorm	=	$scorm_data->id;
		$userid	=	Factory::getUser()->id;

		$attemptsdonebyuser = $scormhelper->getScoTotalAttemptsdone($scoid, $userid);

		if ($attemptsdonebyuser == 0)
		{
			$attempt = 1;
		}
		else
		{
			$attemptcheck = $scormhelper->getScoAttempttobeLaunched($version, $scorm, $scoid, $userid, $attemptsdonebyuser, $this->allowedAttepmts);

			if ($attemptcheck > 0)
			{
				$attempt = $attemptcheck;
			}
			elseif ($attemptcheck == 0)
			{
				$attempt = $attemptsdonebyuser;
			}
		}

		$userscormdata	=	$scormhelper->getUserScodata($scorm,$scoid, $userid, $attempt);

		if (file_exists( JPATH_COMPONENT .'/libraries/scorm/datamodels/'.$version.'.js.php' )) {
			include(JPATH_COMPONENT .'/libraries/scorm/datamodels/'.$version.'.js.php');
		} else {

			include(JPATH_COMPONENT .'/libraries/scorm/datamodels/scorm_1.2.js.php');
		}

		$append_var	=	'scorm=' . $scorm . '&scoid=' . $this->entrysco . '&attempt='.$attempt;

		$course_entry_file = JURI::base().'index.php?option=com_tjlms&view=lesson&layout=loadsco&lesson_id='.$this->lesson_id.'&cid='. $this->course_id .'&mode='. $this->mode .'&'.$append_var;
	?>
		<div class="tjlms_toc_player" id="tjlms_toc_player">
			<?php if($this->fullscreen =! 1 ){ ?>
				<?php echo  $this->loadTemplate('toc'); ?>
			<?php } ?>

			<?php
			if ($launch_in != 'iframe')
			{
			?>
			<div id="tjlms_scom_player" class="tjlms_scom_player" >
					<object id="scorm_object" type="text/html" data="<?php echo $course_entry_file; ?>" ></object>
			</div>
	<?php
			}
			else
			{
		?>
				<iframe id="scorm_object" type="text/html" src="<?php echo $course_entry_file; ?>"></iframe>
		<?php } ?>
			<div class="clearfix"></div>
		</div><!--toc_player-->


		<input type="hidden" name="option" value="com_tjlms" />
		<input	type="hidden" name="task" value="enrol" />
		<input type="hidden"	name="controller" value="course" />
	</form>
</div>



