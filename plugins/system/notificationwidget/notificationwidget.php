<?php
/**
 * @package     Jlike
 * @subpackage  Plg_NotificationWidget
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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
/**
 * Notification widget plugin work with the com_jlike component.
 *
 * @since  1.0.0
 */
class PlgSystemNotificationWidget extends CMSPlugin
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
		$app			= Factory::getApplication();
		$input = $app->input;
		$user = Factory::getUser();

		// Don't show widget on selected menus
		if (in_array($input->get('Itemid'), $this->params->get('menuitems')))
		{
			return;
		}

		if ($app->isClient('administrator') || $input->getString('tmpl') || !$user->id || !$this->isAuthorise())
		{
			return;
		}
		$uri = \Joomla\CMS\Uri\Uri::getInstance();
		$uri = $uri->toString();
		if (strpos($uri,'view=test') == false) {
			
			//HTMLHelper::_('bootstrap.renderModal', 'a.modal');
		}

		
		$document	= Factory::getDocument();
		$class		= 'notificationwidget';

// Build Custom CSS
$css	= <<<CSS
#notificationwidget {
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
	padding-bottom: 12px;
	right: 20px;
	bottom: 80px;
}

#notificationwidget:hover {
	color: #fff;
}

#notificationwidget > img {
	display: block;
	margin: 0 auto;
}
CSS;

$document->addStyleDeclaration($css);

$document->addScript(Uri::root() .'media/plg_system_notificationwidget/js/notificationwidget.js');

$application = Factory::getApplication();
$sitemenu = $application->getMenu();
$mainmenuItems = $sitemenu->getItems(array('unpublish-menu'), array(''));

foreach ($mainmenuItems as $mainmenuItem) {
    if ($mainmenuItem->link === 'index.php?option=com_jlike&view=recommendationform&layout=edit') {
       $menuItem = $mainmenuItem->id;
    }
}
$actualUrl = Route::_('index.php?option=com_jlike&tmpl=component&view=recommendationform&layout=edit&Itemid='.$menuItem,false);

			$js			= <<<SCRIPTHERE
			jQuery(document).ready(function() {
				var lessionId = jQuery('#lession_id').val();
				var hidetool = jQuery('#hidetool').val();
				var courseId = jQuery('input:hidden[name="test[course_id]"]').val();		
				if(((hidetool && lessionId)  || (!hidetool && !lessionId)) && (!courseId)){
				jQuery(document.body).notificationwidget({
				'className':	'$class',
					});
				}
			jQuery('<a id="notificationwidget" class="d-inline-block timelogwidget" href="javascript:void(0);" onclick="notificationwidget.openPopup(\'$actualUrl\')" title="Add To-do"><i class="fa fa-edit fa-2x"></i></a>').appendTo('body');

			
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

					$allowedClusters      = array_filter($allowedClusters);
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
