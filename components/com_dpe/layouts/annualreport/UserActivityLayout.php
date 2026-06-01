<?php
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

HTMLHelper::script('plugins/tjdashboardrenderer/piechart/assets/js/chartjs.js');
HTMLHelper::script('plugins/tjdashboardrenderer/piechart/assets/js/chartjs-plugin-datalabels.js');
$document = Factory::getDocument();
$document->addStylesheet('templates/shaper_helix3/css/pierenderer.css');

$ticketData     = $displayData['ticketData'];
$complianceData = $displayData['complianceData'];
$breachLogData  = $displayData['breachLogData'];
$sarLogData     = $displayData['sarLogData'];
$foiLogData     = $displayData['foiLogData'];
$ropData        = $displayData['ropData'];
$checklist      = $displayData['checklist'];
$phishing       = $displayData['phishing'];
$redaction      = $displayData['redaction'];


$layoutAssignedTools = new FileLayout('dashboard.pieLayout');
?>
<div class="pb-10">
	<h4><strong><?php echo Text::_('COM_DPE_QUICK_ACCESS_DASHBOARD_HEAD'); ?></strong></h4>
</div>

<?php
if (is_null($ticketData['data']['count']) && empty($complianceData['data']['count']) && empty($breachLogData['data']['count']) && empty($sarLogData['data']['count']) && empty($foiLogData['data']['count']) && empty($ropData['data']['count']) && empty($checklist['data']['count']) && empty($phishing['data']['count']))
{?>
	<div class="alert alert-warning">
		<?php echo Text::_('COM_DPE_NO_TOOLS_ASSIGNED_MESSAGE');?>
	</div>
<?php
}
?>

<div class="widget-boxes assign-tools">
<?php 
// echo $layoutAssignedTools->render(array('toolsData' => $ticketData, 'title' => Text::_('COM_DPE_TICKET_TOOL_TITLE')));
echo $layoutAssignedTools->render(array('toolsData' => $sarLogData, 'title' => Text::_('COM_DPE_SAR_LOG_TOOL_TITLE')));
echo $layoutAssignedTools->render(array('toolsData' => $breachLogData, 'title' => Text::_('COM_DPE_BREACH_LOG_TOOL_TITLE')));
echo $layoutAssignedTools->render(array('toolsData' => $foiLogData, 'title' => Text::_('COM_DPE_FOI_LOG_TOOL_TITLE')));
echo $layoutAssignedTools->render(array('toolsData' => $complianceData, 'title' => Text::_('COM_DPE_COMPLIANCE_MANAGER_TOOL_TITLE')));
echo $layoutAssignedTools->render(array('toolsData' => $ropData, 'title' => Text::_('COM_DPE_ROP_TOOL_TITLE')));
echo $layoutAssignedTools->render(array('toolsData' => $checklist, 'title' => Text::_('COM_DPE_CHECKLIST_TOOL_TITLE')));


$layoutAssignedTools = new FileLayout('dashboard.boxLayout');
echo $layoutAssignedTools->render(array('toolsData' => $phishing, 'title' => Text::_('COM_DPE_PHISHING_TOOL_TITLE')));

$layoutAssignedTools = new FileLayout('dashboard.redactionLayout');
echo $layoutAssignedTools->render(array('toolsData' => $redaction, 'title' => Text::_('COM_DPE_REDACTION_TOOL_TITLE')));


?>
</div>
