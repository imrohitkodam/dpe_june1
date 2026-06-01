<?php
/**
 * @package     Jlike
 * @subpackage  Plg_NotificationListWidget
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Uri\Uri;

JLoader::import('components.com_jlike', JPATH_SITE);

/**
 * Notification list widget plugin work with the com_jlike component.
 *
 * @since  1.0.0
 */
class PlgSystemNotificationListWidget extends CMSPlugin
{
	/**
	 * Add the timelog widget to body
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAfterRoute()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$user  = Factory::getUser();

		// Don't show widget on selected menus
		if (in_array($input->get('Itemid'), $this->params->get('menuitems')))
		{
			return;
		}

		if ($app->isClient('administrator') || $input->getString('tmpl') || !$user->id || !$this->isAuthorise())
		{
			return;
		}

		// Show todo count for the user
		JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters         = $clusterUserModel->getUsersClusters($user->id);
		$clusterIds = array();

		foreach ($clusters as $cluster)
		{
				$clusterIds[] = $cluster->cluster_id;
		}

		$cluster = implode(",", $clusterIds);

		JModelLegacy::addIncludePath(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_dpe' . DIRECTORY_SEPARATOR . 'models');

		// Get instance of model class, where class name will be JlikeModelrecommendations
		$userModel = JModelLegacy::getInstance('Users', 'DpeModel', array('ignore_request' => true));

		$userModel->setState("list.limit", 0);
		$assigned_to = $user->id;
		$status = "I";
		$userData = $userModel->getTodoCount($status, $assigned_to, $cluster);

		$params        = ComponentHelper::getParams('com_dpe');

		// Check if the Hours orange and green is not set in config bydefault it will take 72 hours for the orange color and 73 hours for the green color
		$dayForOrange  = empty($params->get('dayForOrange'))?'72':$params->get('dayForOrange');
		$dayForGreen   = empty($params->get('dayForGreen'))?'73':$params->get('dayForGreen');
		$today 		   = new Date('now');
		$dateForOrange = 'now +' . $dayForOrange . 'hour';
		$dateForGreen  = 'now +' . $dayForOrange . 'hour';

		$dateForOrange = new Date($dateForOrange);
		$dateForGreen  = new Date($dateForGreen);
		$overdue       = 0;
		$orangeDays    = 0;
		$greenDays     = 0;
		$html          = '';

		foreach ($userData as $userDatas)
		{

				$dueDate = $userDatas->due_date;

				if ($today > $dueDate && $userDatas->status == 'I')
				{
					$overdue++;
				}
				elseif ($dueDate <= $dateForOrange &&  $dueDate < $dateForGreen && $userDatas->status == 'I')
				{
					$orangeDays++;
				}
				elseif ($userDatas->status == 'I')
				{
					$greenDays++;
				}
		}

					if ($overdue >= 1)
					{
						$html = "<span id='overdue'>" . $overdue . "</span>";
					}
					elseif ($orangeDays >= 1)
					{
						$html = "<span id='orangeday'>" . $orangeDays . "</span>";
					}
					elseif ($greenDays >= 1)
					{
						$html = " <span id='greenday'>" . $greenDays . "</span>";
					}

		$uri = \Joomla\CMS\Uri\Uri::getInstance();
		$uri = $uri->toString();

		if (strpos($uri, 'view=test') == false)
		{
		   HTMLHelper::_('bootstrap.renderModal', 'a.modal');
		}

		$document	= Factory::getDocument();
		$class		= 'notificationlistwidget';

// Build Custom CSS
$css	= <<<CSS
#notificationlistwidget {
	cursor: pointer;
	font-size: 0.9em;
	position: fixed;
	text-align: center;
	z-index: 9999;
	-webkit-transition: background-color 0.2s ease-in-out;
	-moz-transition: background-color 0.2s ease-in-out;
	-ms-transition: background-color 0.2s ease-in-out;
	-o-transition: background-color 0.2s ease-in-out;
	transition: background-color 0.2s ease-in-out;

	background: #34b8f0;
	color: #fff;
	border-radius: 25px;
	padding-left: 12px;
	padding-right: 12px;
	padding-top: 12px;
    padding-bottom: 3px;
    right: 20px;
	bottom: 140px;
}

#notificationlistwidget:hover {
	color: #fff;
}

#notificationlistwidget > img {
	display: block;
	margin: 0 auto;
}

.todored {
	background: #d94623;

}

.todoorange{
    background: #f27d11;

}

.todogreen {
    background: #368180;
}

.todocommon {
	float: left;
    height: 35px;
    width: 35px;
    border-radius: 50%;
    box-shadow: 1px 3px 5px 1px rgb(0 0 0 / 47%);
    display: inline-block;
    color: #fdf9f9;
    margin-top: -52px;
    margin-left: -38px;
    text-align: center;
    padding: 6px;
    font-weight: bolder;
    font-size: large;
    position: relative;

}


CSS;

$document->addStyleDeclaration($css);

$document->addScript(Uri::root() . 'media/plg_system_notificationlistwidget/js/notificationlistwidget.js');

			$js			= <<<SCRIPTHERE
	jQuery(document).ready(function() {
			    var lessionId = jQuery('#lession_id').val();
				var hidetool = jQuery('#hidetool').val();
				var courseId = jQuery('input:hidden[name="test[course_id]"]').val();		
				if(((hidetool && lessionId)  || (!hidetool && !lessionId)) && (!courseId)){
				jQuery(document.body).notificationlistwidget({
				'className':	'$class',
				});
		}
		
	
	var html = "<p>$html</p>"
	 jQuery('#notificationlistwidget').append(html);
	 jQuery("#overdue").addClass("todocommon todored");
 	 jQuery("#orangeday").addClass("todocommon todoorange");
	 jQuery("#greenday").addClass("todocommon todogreen");

});
SCRIPTHERE;

		$document->addScriptDeclaration($js);
	}

	/**
	 * Authorise the user to view notification list icon
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	private function isAuthorise()
	{
		static $authorised = null;

		if (is_null($authorised))
		{
			$user       = Factory::getUser();
			$authorised = true;

			JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters         = $clusterUserModel->getUsersClusters($user->id);

			// Check for Notification Manager action
			if (ComponentHelper::getComponent('com_subusers', true)->enabled)
			{
				JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

				if (!$user->authorise('core.manageall', 'com_cluster'))
				{
					$allowedClusters = array();
					$allowedStaffClusters = array();

					foreach ($clusters as $cluster)
					{
						if (RBACL::check($user->id, 'com_cluster', 'core.manageNotificationManager', 'com_jlike', $cluster->cluster_id))
						{
							$allowedClusters[] = $cluster->cluster_id;
						}

						if (RBACL::check($user->id, 'com_cluster', 'core.own.manageNotifications', 'com_jlike', $cluster->cluster_id))
						{
							$allowedStaffClusters[] = $cluster->cluster_id;
						}
					}

					$allowedClusters = array_filter($allowedClusters);
					$allowedStaffClusters = array_filter($allowedStaffClusters);

					if (empty($allowedClusters))
					{
						$authorised = false;
					}

					if (!$authorised && $allowedStaffClusters)
					{
						$authorised = true;
					}
				}
			}
		}

		return $authorised;
	}
}
