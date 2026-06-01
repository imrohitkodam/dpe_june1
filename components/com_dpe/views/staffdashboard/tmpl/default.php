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
use Joomla\CMS\Component\ComponentHelper;

$user      = Factory::getUser();
$staffRole = ComponentHelper::getParams('com_multiagency')->get('member_role_id');

// Check user having staff role org
foreach ($this->userClusters as $cluster)
{
	$coreRoleId = RBACL::getCoreRoleByUser($user->id, 'com_cluster', $cluster->cluster_id);

	if (in_array($staffRole, $coreRoleId))
	{
		$clusterIds[] = $cluster->cluster_id;
	}
}
?>
<div class="container-fluid">
  <div class="row">
	<div class="col-xs-12 col-md-8">
		<!-- Elearning -->
		<div>
		<?php
			$layoutElearning    = new FileLayout('dashboard.elearning');
			echo $layoutElearning->render(array('enrolledCourses' => $this->enrolledCourses, 'latestCourses' => $this->latestCourses, 'completeCourses' => $this->completeCourses, 'ongoingCourses' => $this->onGoingCourses));
		?>
		</div>

		<!-- My Certificates -->
		<div>
		<?php
			$layoutCertificates    = new FileLayout('dashboard.certificates');
			echo $layoutCertificates->render(array('myCertificates' => $this->myCertificates));
		?>
		</div>

		<!-- Assigned Documents -->
		<div>
		<?php
			$layoutDocuments    = new FileLayout('dashboard.documents');
			echo $layoutDocuments->render(array('assignedDocuments' => $this->assignedDocuments, 'completedDocuments' => $this->completedDocuments));
		?> 
		</div>
		<!-- Quick Access -->
		<!-- Quick access is only for staff -->
		<?php if (! empty($clusterIds)) {?>
			<div>
			<?php
				$layoutAccess = new FileLayout('dashboard.quickaccess');
				echo $layoutAccess->render(array('ticketData' => $this->ticketData, 'complianceData' => $this->complianceData, 'breachLogData' => $this->breachLogData, 'sarLogData' => $this->sarLogData, 'foiLogData' => $this->foiLogData, 'ropData' => $this->ropData, 'checklist' => $this->checklist, 'phishing' => $this->phishing,'redaction'=>$this->redaction));
			?> 
			</div>
		<?php } ?>

		<!-- Assigned Logs -->
		<div>
		<?php
			$layoutAccess = new FileLayout('dashboard.assignedLogs');
			echo $layoutAccess->render(array('foi' => $this->assignedRecordsFoi, 'datalog' => $this->assignedRecordsSar, 'breach' => $this->assignedRecordsBreach));
		?>
		</div>
	</div>
	<div class="col-xs-12 col-md-4">
	<!-- My Todo -->
	<?php
		$layoutTodos    = new FileLayout('dashboard.todos');
		echo $layoutTodos->render(array('todos' => $this->todos, 'totalTodos' => $this->totalTodos, 'completedTodos' => $this->completedTodos));
	?>
	</div>
  </div>
</div>
