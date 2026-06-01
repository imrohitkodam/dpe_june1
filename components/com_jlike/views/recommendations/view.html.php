<?php
/**
 * @package     JLike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

/**
 * JlikeViewRecommendations form view class.
 *
 * @package     JLike
 * @subpackage  com_jlike
 * @since       1.6.7
 */
class JlikeViewRecommendations extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $params;

	protected $create;

	protected $manageNotifications;

	protected $contentId;

	protected $deleteAllowedClusters;

	protected $allowedClusters;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$app                       = Factory::getApplication();
		$input                     = $app->input;
		$this->user                = Factory::getUser();
		$this->manageNotifications = true;
		$tmpl                      = $input->getString('tmpl');

		$this->logged_userid = Factory::getUser()->id;

		if (!$this->logged_userid)
		{
			$msg = Text::_('COM_JLIKE_LOGIN_MSG');
			$uri = $input->server->get('REQUEST_URI', '', 'STRING');
			$url = base64_encode($uri);
			$app->enqueueMessage($msg, 'error');
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, false));
		}

		$this->state       = $this->get('State');
		$this->create      = $this->user->authorise('core.create', 'com_jlike');
		$this->filterForm  = $this->get('FilterForm');

		// DPE Hack start to load assigned taks who dont have RBACL action of manage cluster

		if ($this->user->authorise('core.manageall', 'com_cluster'))
		{
			$this->delete = true;
		}

		if (!$this->user->authorise('core.manageall', 'com_cluster'))
		{
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters         =  $clusterUserModel->getUsersClusters($this->user->id);
			$this->allowedClusters  = array();

			// Check for Notification Manager action
			if (ComponentHelper::getComponent('com_subusers', true)->enabled)
			{
				foreach ($clusters as $cluster)
				{
					$isNotificationAllowed = RBACL::check($this->user->id, 'com_cluster', 'core.manageNotificationManager', 'com_jlike', $cluster->cluster_id);

					if ($isNotificationAllowed)
					{
						$this->allowedClusters[] = $cluster->cluster_id;
					}

					$this->delete = RBACL::check($this->user->id, 'com_cluster', 'core.deleteNotification', 'com_jlike', $cluster->cluster_id);

					// Get clusters where user having delete action
					if ($this->delete)
					{
						$this->deleteAllowedClusters[] = $cluster->cluster_id;
					}
				}
			}

			if (empty($this->allowedClusters))
			{
				$this->manageNotifications = false;

				// If user don't have access to manage notification manager then show assigned records only
				$this->state->set('assigned_to', $this->user->id);
			}

			if ($tmpl)
			{
				// On popup list show only assigned todos
				$this->state->set('assigned_to', $this->user->id);
				$this->state->set('view', "popup");
			}
		}

		if ($tmpl)
		{
			if (!$this->filterForm->getValue('filter')->status)
			{
				$this->state->set('filter.status', "I");
			}

			$this->state->set('assigned_to', $this->user->id);
		}

		// Hide filters
		$this->filterForm->removeField('records', 'filter');
		$this->filterForm->removeField('client', 'filter');

		/*
		if (!$this->create)
		{
			$this->state->set('assigned_to', $this->user->id);
		}
		*/

		// DPE Hack end

		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->params        = $app->getParams('com_jlike');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->_prepareDocument();
		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return void
	 *
	 * @since 1.5.0
	 */
	protected function _prepareDocument()
	{
		$app = Factory::getApplication();
		$menus = $app->getMenu();
		$title = null;

		/* Because the application sets a default page title,
		we need to get it from the menu item itself*/
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_JLIKE_DEFAULT_PAGE_TITLE'));
		}

		$title = $this->params->get('page_title', '');

		if (empty($title))
		{
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}

		$this->document->setTitle($title);

		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
	}
}
