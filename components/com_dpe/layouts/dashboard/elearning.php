<?php
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);

$enrolledCourses = $displayData['enrolledCourses'];
$latestCourses   = $displayData['latestCourses'];
$completeCourses = $displayData['completeCourses'];
$ongoingCourses  = $displayData['ongoingCourses'];
$app             = Factory::getApplication();
$menu            = $app->getMenu();
$menuItem        = $menu->getItems('link', 'index.php?option=com_tjlms&view=courses&courses_to_show=all', true);
$allCourseLink   = Route::_($menuItem->link . '&Itemid=' . $menuItem->id);

// Check user org have elearining tool access
$user               = Factory::getUser();
$clusterUserModel   = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
$clusters           = $clusterUserModel->getUsersClusters($user->id);

if (!$user->authorise('core.manageall', 'com_cluster'))
{
	$elearingAccess = array();

	foreach ($clusters as $cluster)
	{
		$elearingAccess[] = RBACL::check($user->id, 'com_cluster', 'core.viewShika', 'com_tjlms', $cluster->cluster_id);
	}
}

$elearingAccess = array_filter($elearingAccess);
?>
<div class="pb-10">
	<h4><?php echo Text::_('COM_DPE_ELEARNING_DASHBOARD_HEAD'); ?></h4>
	<?php echo Text::sprintf('COM_DPE_ELEARNING_DASHBOARD_COUNT', $ongoingCourses, count($completeCourses)); ?>
</div>

<?php if (! empty($elearingAccess)) { ?>
<div class="pull-right">
	<a class="hasTooltip" target="_blank"
	href="<?php echo $allCourseLink; ?>" target="_blank">
		<?php echo Text::_('COM_DPE_DASHBOARD_VIEW_ALL_COURSES'); ?>
	</a>
</div>
<?php } ?>

<?php			
	// Tab structure start
	echo HTMLHelper::_('bootstrap.startTabSet', 'elearning', array('active' => 'enrolled'));

		// Call enrolled courses layout
		echo HTMLHelper::_('bootstrap.addTab', 'elearning', 'enrolled', Text::_('COM_DPE_ELEARNING_ENROLLED_COURSE_TAB', true));
		$layoutElearning    = new FileLayout('dashboard.enrolled');
		echo $layoutElearning->render(array('enrolledCourses' => $enrolledCourses, 'ongoingCourses' => $ongoingCourses, 'allCourseLink' => $allCourseLink, 'elearingAccess' => $elearingAccess));
		echo HTMLHelper::_('bootstrap.endTab');

		// Call latest courses layout

		/*
		echo HTMLHelper::_('bootstrap.addTab', 'elearning', 'latest', Text::_('COM_DPE_ELEARNING_LATEST_COURSE_TAB', true));
		$layoutElearning    = new FileLayout('dashboard.latestCourses');
		echo $layoutElearning->render(array('latestCourses' => $latestCourses));
		echo HTMLHelper::_('bootstrap.endTab');
		*/

	echo HTMLHelper::_('bootstrap.endTabSet');
	// Tab structure end
?>
