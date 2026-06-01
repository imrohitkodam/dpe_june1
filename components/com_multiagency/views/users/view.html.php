<?php
/**
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2020 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Uri\Uri;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;

jimport('joomla.application.component.view');
JPATH_SITE . 'components/com_multiagency/helpers/subusers.php';
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);

/**
 * View class for a list of Subusers.
 *
 * @since  1.6
 */
class MultiagencyViewUsers extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $params;

	protected $agencyListArray = array();

	protected $managerRoleId;

	protected $agenciesId;

	protected $isSuperAdmin = false;

	protected $addOwnUser = false;

	protected $editOwnUser = false;

	protected $addUser = false;

	protected $editUser = false;

	protected $removeOwnUser = false;

	protected $removeUser = false;

	protected $canCreate = false;

	protected $canEditUser = false;

	/**
	 * @var  array
	 *
	 */
	public $activeFilters;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$app                 = Factory::getApplication();
		$this->user	         = Factory::getUser();
		$this->state         = $this->get('State');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->params        = $app->getParams('com_multiagency');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check the agency value is numeric
		$this->agenciesId    = $this->getState('filter.agencies');

		// DPE Hack start
		// Assign agency id by calling cluster table because system is using cluster dropdown
		if ((int) $this->agenciesId)
		{
			$clustertable = ClusterFactory::table('Clusters');
			$clustertable->load(array('id' => $this->agenciesId));

			$this->agenciesId = $clustertable->client_id;
		}
		// DPE Hack end

		// Code use to show delete button
		if (!$this->user->authorise('core.manageall', 'com_cluster'))
		{
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
			$MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));

			$params               = ComponentHelper::getParams('com_multiagency');
			$memberRole           = $params->get('member_role_id', '0', 'INT');
			$leadConsultantRoleId = $params->get('organization_lead_consultant_role_id', '0', 'INT');
			$agenciesList         = $MultiagencyModel->getAllocatedAgencies($this->user->id, array($memberRole, $leadConsultantRoleId));

			foreach ($agenciesList as $agency)
			{
				$this->agencyListArray[] = $agency->id;
			}

			if (!((int) $this->agenciesId) && count($agenciesList) > 0)
			{
				$this->agenciesId = $agenciesList[0]->id;
			}
		}

		$this->isSuperAdmin  = $this->user->authorise('core.manageall', 'com_cluster');
		$this->viewUsers     = RBACL::check($this->user->id, 'com_multiagency', 'core.viewUsers', 'com_multiagency');
		$this->addOwnUser    = RBACL::check($this->user->id, 'com_multiagency', 'core.own.adduser', 'com_multiagency', $this->agenciesId);
		$this->editOwnUser   = RBACL::check($this->user->id, 'com_multiagency', 'core.own.edituser', 'com_multiagency', $this->agenciesId);
		$this->addUser       = RBACL::check($this->user->id, 'com_multiagency', 'core.adduser', 'com_multiagency', $this->agenciesId);
		$this->editUser      = RBACL::check($this->user->id, 'com_multiagency', 'core.edituser', 'com_multiagency', $this->agenciesId);
		$this->canCreate     = RBACL::check($this->user->id, 'com_multiagency', 'core.own.adduser', 'com_multiagency', $this->agenciesId);
		$this->canEditUser   = RBACL::check($this->user->id, 'com_multiagency', 'core.own.edituser', 'com_multiagency', $this->agenciesId);
		$this->removeOwnUser = RBACL::check($this->user->id, 'com_multiagency', 'core.own.removeuser', 'com_multiagency', $this->agenciesId);
		$this->removeUser    = RBACL::check($this->user->id, 'com_multiagency', 'core.removeuser', 'com_multiagency', $this->agenciesId);

		// Check user is not super user
		if (!$this->isSuperAdmin && !$this->addOwnUser && !$this->addUser && !$this->viewUsers)
		{
			$return = base64_encode(Uri::getInstance());
			$login_url_with_return = Route::_('index.php?option=com_users&return=' . $return);
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect($login_url_with_return, 403);
		}

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
	 * @throws Exception
	 */
	protected function _prepareDocument()
	{
		$app   = Factory::getApplication();
		$menus = $app->getMenu();
		$title = Text::_('COM_MULTIAGENCY_USERS_LIST_PAGE_TITLE');

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_MULTIAGENCY_DEFAULT_USERS_TITLE'));
		}

		if (empty($title))
		{
			$title = $app->get('sitename');
		}
		elseif ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
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

	/**
	 * Check if state is set
	 *
	 * @param   mixed  $state  State
	 *
	 * @return bool
	 */
	public function getState($state)
	{
		return isset($this->state->{$state}) ? $this->state->{$state} : false;
	}
}
