<?php
/**
 * Modal layout to add Job title with tags
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Uri\Uri;


$app   = Factory::getApplication();
$document = Factory::getDocument();

$document->addScript(Uri::root() . 'media/com_dpe/js/tjucm.js');

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$document->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
$document->addStyleSheet('templates/shaper_helix3/css/custom.css');


$doc = Factory::getDocument();
$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
HTMLHelper::_('formbehavior.chosen', '#jform_tags');

// Build tags server-side
FormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');
$dpeTags = FormHelper::loadFieldType('Dpetags', false);
$dpeTag  = $dpeTags ? $dpeTags->getOptions() : array();

$user = Factory::getUser();
$params = ComponentHelper::getParams('com_multiagency');
$multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group');
$orgAdminRoleId = (int) $params->get('multiagency_school_admin_group', '0', 'INT');

JLoader::import('/components/com_dpe/includes/dpe', JPATH_SITE);
if (class_exists('DPE'))
{
    $dpeModel = DPE::model('school', array('ignore_request' => true));
    if ($dpeModel)
    {
        if (in_array($multiagencyTrusteeRoleId, $user->groups))
        {
            $tags = $dpeModel->getAgencyTags($multiagencyTrusteeRoleId);
            if (!empty($tags)) { $dpeTag = $tags; }
        }
        elseif (in_array($orgAdminRoleId, $user->groups))
        {
            $tags = $dpeModel->getAgencyTags($orgAdminRoleId);
            if (!empty($tags)) { $dpeTag = $tags; }
        }
    }
}

// Render options
$optionsHtml = '';
foreach ($dpeTag as $opt)
{
    if (is_object($opt))
    {
        $val  = isset($opt->value) ? $opt->value : (isset($opt->id) ? $opt->id : '');
        $text = isset($opt->text) ? $opt->text : (isset($opt->title) ? $opt->title : $val);
    }
    else
    {
        $val  = isset($opt['value']) ? $opt['value'] : (isset($opt['id']) ? $opt['id'] : '');
        $text = isset($opt['text']) ? $opt['text'] : (isset($opt['title']) ? $opt['title'] : $val);
    }
    if ($val !== '')
    {
        $optionsHtml .= '<option value="' . htmlspecialchars((string) $val, ENT_COMPAT, 'UTF-8') . '">' . htmlspecialchars((string) $text, ENT_COMPAT, 'UTF-8') . '</option>';
    }
}
$token = JSession::getFormToken();
?>

<div class="jobtitle-modal-body">
    <div class="iframe-modal-header">
        <h4 class="modal-title"><?php echo Text::_('COM_DPE_ADD_JOB_TITLE_BUTTON'); ?></h4>
        <button type="button" class="close absolute" aria-label="Close" onclick="closeParentModal()"><span aria-hidden="true">&times;</span></button>
        
    </div>
    <hr class="modal-sep" />

    <div id="item-form">
	<div class="overlay" id="tjucm_loader" style="display:none;">
		<div class="loader"></div>
	</div>
</div>
    <div id="modal-message-container"></div>

    <form id="jobtitle-modal-form" method="post" action="">
        <div class="row-fields">
            <div class="field-col">
                <label for="jform_tags" class="control-label"><?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?></label>
                <select name="jform[tags][]" id="jform_tags" class="inputbox" multiple="multiple">
                    <option value=""><?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?></option>
                    <?php echo HTMLHelper::_('select.options', $dpeTag, 'value', 'text'); ?>
                </select>
            </div>
            <div class="field-col">
                <label for="jform_com_tjucm_role_name" class="control-label"><?php echo Text::_('COM_USERS_SCHOOL_JOBTITLE_LABEL'); ?></label>
                <input type="text" name="jform[com_tjucm_role_name]" id="jform_com_tjucm_role_name" value="" class="form-control required" placeholder="Job title" required="" aria-invalid="false">
            </div>
        </div>

        <?php echo HTMLHelper::_('form.token'); ?>

        <div class="actions actions-right mt-3">
            <span id="tjucm_job_loader" style="display:none;margin-right:8px;">Loading...</span>
            <a class="btn btn-default btn-popup-cancel" onclick="closeParentModal()"><?php echo Text::_('JCANCEL'); ?></a>
            <button type="button" id="quick-add-submit" onclick="saveJobtitleWithTags('<?php echo $token; ?>')" class="btn btn-primary ml-2"><?php echo Text::_('JSUBMIT'); ?></button>
        </div>
    </form>
</div>



