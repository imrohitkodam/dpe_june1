<?php
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Layout\FileLayout;

JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);

$foiRecords     = $displayData['foi'];
$datalogRecords = $displayData['datalog'];
$breachRecords  = $displayData['breach'];
$app            = Factory::getApplication();
$menu           = $app->getMenu();
$formMenuItems  = $menu->getItems('link', 'index.php?option=com_tjucm&view=itemform');
$listMenuItems  = $menu->getItems('link', 'index.php?option=com_tjucm&view=items');
$dpeParams    	= ComponentHelper::getParams('com_dpe');
$foiTitleField  = $dpeParams->get('foiTitleField');
$sarTitleField  = $dpeParams->get('sarTitleField');
$breachTitleField  = $dpeParams->get('breachTitleField');

foreach($formMenuItems as $formMenuItem)
{
	$params = $formMenuItem->getParams();

	if ($params->get('ucm_type') === 'foi-log')
	{
		$foiMenuId = $formMenuItem->id;
	}

	if ($params->get('ucm_type') === 'sar-log')
	{
		$sarMenuId = $formMenuItem->id;
	}

	if ($params->get('ucm_type') === 'breach-log')
	{
		$breachMenuId = $formMenuItem->id;
	}
}

foreach($listMenuItems as $listMenuItem)
{
	$listParams = $listMenuItem->getParams();

	if ($listParams->get('ucm_type') === 'foi-log')
	{
		$listFoiMenuId = $listMenuItem->id;
	}

	if ($listParams->get('ucm_type') === 'sar-log')
	{
		$listSarMenuId = $listMenuItem->id;
	}

	if ($listParams->get('ucm_type') === 'breach-log')
	{
		$listBreachMenuId = $listMenuItem->id;
	}
}

?>
<div class="pb-10">
	<h4><strong><?php echo Text::_('COM_DPE_ASSIGNED_RECORDS_DASHBOARD_HEAD'); ?></strong></h4>
</div>

<?php 

if (empty($datalogRecords) && empty($breachRecords) && empty($foiRecords))
{?>
	<div class="alert alert-warning">
		<?php echo Text::_('COM_DPE_NO_ASSIGNED_RECORDS_MESSAGE');?>
	</div>
<?php 
}
?>

<?php 
if (! empty($datalogRecords)) 
{
	$layoutAssignedRecords = new FileLayout('dashboard.assignedLogLayout');
	echo $layoutAssignedRecords->render(
	array('records'      => $datalogRecords, 
		  'formMenuId'   => $sarMenuId,
		  'viewAllLink'  => 'index.php?option=com_tjucm&view=items&client=com_tjucm.sarlog',
		  'listMenuId'   => $listSarMenuId,
		  'itemLink'     => 'index.php?option=com_tjucm&view=itemform',
		  'titleFieldId' => $sarTitleField,
		  'logHeading'   => Text::_('COM_DPE_SAR_ASSIGNED_RECORDS')   
		  ));
}
?>

<?php 
if (! empty($breachRecords)) 
{
	$layoutAssignedRecords = new FileLayout('dashboard.assignedLogLayout');
	echo $layoutAssignedRecords->render(
	array('records'      => $breachRecords, 
		  'formMenuId'   => $breachMenuId,
		  'viewAllLink'  => 'index.php?option=com_tjucm&view=items&client=com_tjucm.breachlog',
		  'listMenuId'   => $listBreachMenuId,
		  'itemLink'     => 'index.php?option=com_tjucm&view=itemform',
		  'titleFieldId' => $breachTitleField,
		  'logHeading'   => Text::_('COM_DPE_BREACH_ASSIGNED_RECORDS')   
		  ));
}
?>

<?php 
if (! empty($foiRecords)) 
{ 
	$layoutAssignedRecords = new FileLayout('dashboard.assignedLogLayout');
	echo $layoutAssignedRecords->render(
	array('records'      => $foiRecords, 
		  'formMenuId'   => $foiMenuId,
		  'field_id'     => $foiTitleField,
		  'viewAllLink'  => 'index.php?option=com_tjucm&view=items&client=com_tjucm.FOIlog',
		  'listMenuId'   => $listFoiMenuId,
		  'itemLink'     => 'index.php?option=com_tjucm&view=itemform',
		  'titleFieldId' => $foiTitleField,
		  'logHeading'   => Text::_('COM_DPE_FOI_ASSIGNED_RECORDS')   
		  ));
}
?>
