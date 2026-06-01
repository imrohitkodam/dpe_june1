<?php
/**
 * @package     Com_Shika
 * @subpackage  mod_lms_course_progress
 * @copyright   Copyright (C) 2009-2017 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.techjoomla.com
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ModuleHelper;

if (JVERSION >= '3.0')
{
	HTMLHelper::_('bootstrap.renderModal');
}
else
{
	HTMLHelper::_('behavior.modal');
}

$options['relative'] = true;
HTMLHelper::stylesheet('mod_lms_course_blocks/style.css', $options);
?>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?> ">
<?php
if ($params->get('progress', 1)) :
	require ModuleHelper::getLayoutPath('mod_lms_course_blocks', 'default_' . 'progress');
endif;

if ($params->get('info', 1)) :
	require ModuleHelper::getLayoutPath('mod_lms_course_blocks', 'default_' . 'info');
endif;

if ($params->get('assign_user', 1) && $showassign == 1) :
	require ModuleHelper::getLayoutPath('mod_lms_course_blocks', 'default_' . 'assign_user');
endif;

if ($params->get('taught_by', 1)) :
	require ModuleHelper::getLayoutPath('mod_lms_course_blocks', 'default_' . 'taught_by');
endif;

if ($params->get('recommend', 1) && $showrecommend == 1) :
	require ModuleHelper::getLayoutPath('mod_lms_course_blocks', 'default_' . 'recommend');
endif;

if ($params->get('group_info', 1)) :
	require ModuleHelper::getLayoutPath('mod_lms_course_blocks', 'default_' . 'group_info');
endif;

if ($params->get('enrolled', 1)) :
	require ModuleHelper::getLayoutPath('mod_lms_course_blocks', 'default_' . 'enrolled');
endif;

if ($params->get('fields', 1)) :
	require ModuleHelper::getLayoutPath('mod_lms_course_blocks', 'default_' . 'fields');
endif;
?>
</div>

