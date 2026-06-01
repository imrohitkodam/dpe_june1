<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;




HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('bootstrap.renderModal');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('jquery.token');
HTMLHelper::_('script', 'media/com_dpe/js/dpeecharts.min.js');
HTMLHelper::_('script', 'media/com_dpe/js/matviewreport.js');
HTMLHelper::_('script', 'media/system/js/messages.min.js');
HTMLHelper::script('media/com_tjcertificate/vendors/html2canvas/js/html2canvas.js');
HTMLHelper::script('media/com_tjcertificate/vendors/loader/js/loadingoverlay.min.js');
$document = Factory::getDocument();
$document->addStylesheet('templates/shaper_helix3/css/custom.css');

$user      = Factory::getUser();

?>
<!-- <script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script> -->
<style type="text/css">
.fa-calendar{display:inline-flex;align-items:center;justify-content:center;color:#fff;border-radius:50%;font-size:16px;text-align:center;transition:background-color .3s ease,transform .2s ease;cursor:pointer}
.fa-calendar:hover{background-color:#0056b3;transform:scale(1.1)}
#main{border:1px solid #ddd;border-radius:8px;padding:10px;background-color:#fff;margin:20px 0}
.matviewbox{background-color:#f8f9fa;padding:20px;border-radius:8px;margin:20px 0}
@media print{.card.shadow-sm.mb-4,#notificationlistwidget,#notificationwidget,#timelogwidget{display:none!important}}
</style>
<div id="system-message-container"></div>
	<div class="overlay" id="loader-overlay">
		<div class="loader"></div>
	</div>
<div class="card shadow-sm mb-4">
  <div class="card-body">

    <!-- Header -->
    <h4 class="mt-2 mb-4 fw-bold text-primary" style="color: rgb(52 184 240) !important;">
      <?php echo Text::_('COM_DPE_MATVIEW_REPORT_HEADER'); ?>
    </h4>

    <!-- Report Filters Form -->
<form id="reportForm" name="reportForm">
     <div class="row g-3">

        <!-- Cluster / Organization Filter -->
        <div class="col-md-3">
          <div class="mb-3">
            <?php echo $this->form->renderField('cluster_id'); ?>
          </div>
        </div>

        <!-- Tags Filter -->
        <?php
        $params    = ComponentHelper::getParams('com_multiagency');
        $multiagency_trustee_group = (int) $params->get('multiagency_trustee_group');
        $isTrustee 				   = in_array($multiagency_trustee_group, $user->groups);
        $orgAdminRoleId            = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
        $orgAdminRoleId 		   = in_array($orgAdminRoleId, $user->groups);

        if ($user->authorise('core.manageall', 'com_cluster') || $isTrustee || $orgAdminRoleId)
        {
          FormHelper::addFieldPath(JPATH_SITE . '/components/com_tjucm/models/fields/');
          $dpeTags = FormHelper::loadFieldType('dpetags', false);
          $dpeTag  = $dpeTags->getOptions();

          JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
          $dpeModel = DPE::model('school', ['ignore_request' => true]);

          $trusteeTags = ($isTrustee) ? $dpeModel->getAgencyTags($multiagency_trustee_group) : $dpeModel->getAgencyTags($orgAdminRoleId);
          ?>
          <div class="col-md-2" style="margin-top: 9px;">
            <div class="mb-3">
              <label for="filter_tags" class="form-label">
                <?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?>
              </label>
              <select name="filter_tags[]" id="filter_tags"
                      data-placeholder="<?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?>"
                      class="form-select chosen-select" multiple="multiple">
                <?php
                if ($user->authorise('core.manageall', 'com_cluster'))
                {
                  echo HTMLHelper::_('select.options', $dpeTag, 'value', 'text', $this->items->tags);
                }
                else
                {
                  echo HTMLHelper::_('select.options', $trusteeTags, 'value', 'text', $this->items->tags);
                }
                ?>
              </select>
            </div>
          </div>
        <?php } ?>

        <!-- Date Filters -->
        <div class="col-md-2">
          <?php echo $this->form->renderField('start_date'); ?>
        </div>
        <div class="col-md-2">
          <?php echo $this->form->renderField('end_date'); ?>
        </div>
        <div class="col-md-1 d-flex align-items-center">
          <h2 class="fs-5 fw-normal mt-0 ms-3 mb-0">-OR-</h2>
        </div>
        <div class="col-md-2">
					<?php echo $this->form->renderField('date_range'); ?>
        </div>

        <!-- Buttons -->
        <div class="d-flex justify-content-end align-items-center gap-2 mt-4">
          <button type="button" class="btn btn-primary p-2" onclick="showReport();">
            <i class="fa fa-eye me-1"></i>
            <?php echo Text::_("COM_DPE_ANNUAL_SHOW_REPORT"); ?>
          </button>
          <button type="button" id="downloadPdfBtn" class="btn btn-primary p-2" style="display: none;">
            <i class="fa fa-file-pdf-o me-1"></i>
            <?php echo Text::_("COM_TJUCM_DOCUMENT_DOWNLOAD_PDF"); ?>
          </button>
          <button type="button" class="btn btn-primary p-2" onclick="window.print();">
            <i class="fa fa-print me-1"></i>
            <?php echo Text::_("COM_DPE_PRINT"); ?>
          </button>
        </div>
      </div>
      	<?php echo HTMLHelper::_('form.token'); ?>

    </form>

  </div>
</div>
            
<div class="matviewbox">

<div id="tagaverages"></div>

<!-- Single cluster chart -->
<div id="main" style="width: 100%; height: 500px;"></div>

<div id="chartsContainer"></div>
</div>
