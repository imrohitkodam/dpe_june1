<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

$tjlmsparams	= ComponentHelper::getParams('com_tjlms');
?>

<script>

var lesson_characters_allowed	=	<?php echo $tjlmsparams->get('allow_char_desc', '150', 'INT');?>;
var numeric_value_validation_msg	=	"<?php echo Text::_('COM_TJLMS_NO_NEGATIVE_NUMBER'); ?>";
var allowed_paid_course	=	<?php echo $tjlmsparams->get('allow_paid_courses', '0', 'INT');?>;
var provide_valid_scorm_package_msg =  "<?php echo Text::_('COM_TJLMS_VALID_SCORM_PACKAGE'); ?>";
var lesson_upload_size = <?php echo $tjlmsparams->get('lesson_upload_size', 10, 'INT');?>;
var nonvalid_extension = "<?php echo Text::_('COM_TJLMS_UPLOAD_EXTENSION_ERROR');?>"
var filesize_exceeded = "<?php echo Text::sprintf('COM_TJLMS_UPLOAD_SIZE_ERROR', $tjlmsparams->get('lesson_upload_size', 10, 'INT') . ' MB');?>";
var alert_confirm_proceed_deleting="<?php	echo JTEXT::_('COM_TJLMS_SURE_DELETE')	?>";
var	delete_success_msg = "<?php	echo JTEXT::_('COM_TJLMS_DELETE_SUCCESS')	?>";
var	lesson_delete_success_msg = "<?php	echo JTEXT::_('COM_TJLMS_LESSON_DELETE_SUCCESS')	?>";
var	quiz_delete_success_msg = "<?php	echo JTEXT::_('COM_TJLMS_QUIZ_DELETE_SUCCESS')	?>";
var	save_lesson_details_firstmsg = "<?php	echo JTEXT::_('COM_TJLMS_SAVE_LESSON_DETAILS_BEFORE_FORMAT');?>"
var	format_not_uploaded_error = "<?php	echo JTEXT::_('COM_TJLMS_FORMAT_NOT_UPLOADED_ERROR');?>";
var delete_lesson_msg	=	"<?php	echo JTEXT::_('COM_TJLMS_SURE_DELETE')	?>";
var file_not_selected_error	= "<?php	echo JTEXT::_('COM_TJLMS_FILE_NOT_SECLTED_ERROR')	?>";
var video_url_or_embedcode_blank	= "<?php echo Text::_('COM_TJLMS_VIDEO_SOURCE_NOT_PROVIDED_ERROR'); ?>";
var allow_associate_files = <?php echo $tjlmsparams->get('allow_associate_files', 0, 'INT');?>;
var form_date_validation_failed = "<?php echo Text::_('COM_TJLMS_DATE_RANGE_VALIDATION'); ?>";
</script>
