<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\User\User;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\Toolbar;

JLoader::register('RSTicketsProHelper', JPATH_ADMINISTRATOR . '/components/com_rsticketspro/helpers/rsticketspro.php');

/**
 * DPE RSTicketspro view class.
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeViewrsticketspro extends BaseHtmlView
{
	/**
	 * The user object
	 *
	 * @var  \User|null
	 */
	protected $user;

	/**
	 * DPE Config Parameter
	 */
	protected $params;

	/**
	 * The model state
	 *
	 * @var  CMSObject
	 */
	protected $state;

	/**
	 * The My Tickets object
	 *
	 * @var  \stdClass
	 */
	protected $items;

	/**
	 * @var  \Pagination
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $pagination;

	/**
	 * @var  \Form
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public $filterForm;

	/**
	 * @var  array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public $activeFilters;

	/**
	 * @var  string
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public $rsTicketdateFormat;

	/**
	 * @var  Object
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public $permissions;

	/**
	 * @var  String
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public $toolbarHTML;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function display($tpl = null)
	{
		$app        = Factory::getApplication();
		$this->user = Factory::getUser();

		$params     = ComponentHelper::getParams('com_multiagency');
		$groupMultiagecnyTrusteeId = (INT) $params->get('multiagency_trustee_group');

		// Dont allow Trustee to view tickets
		/*
		if (in_array($groupMultiagecnyTrusteeId, $this->user->groups))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
			$link = base64_encode((string) Uri::getInstance());
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $link, false));
		}
		*/

		// Validate user login.
		if (empty($this->user->id))
		{
			$app->enqueueMessage(Text::_('COM_DPE_LOGIN_ERROR_MSG'), 'warning');
			$link = base64_encode((string) Uri::getInstance());
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $link, false));
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->state              = $this->get('State');

		// Get com_subusers component status
		$subUserExist     = ComponentHelper::getComponent('com_subusers', true)->enabled;
		$this->clusterId  = $this->state->get('filter.agencies', '', 'INT');

		// Check user have permission to edit record of assigned cluster
		if ($subUserExist && !$this->user->authorise('core.manageall', 'com_cluster'))
		{
			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

			/*
			 *  @Todo migration for client 'com_cluster' in dpe
			 *  Com_dpe - Hack - start
			 *  Original Code : RBACL::authorise($user->id, 'com_cluster', 'core.manage', 'com_cluster', $this->clusterId)
			 */

			// Check user has permission for mentioned cluster
			if (is_numeric($this->clusterId) && !RBACL::check($this->user->id, 'com_cluster', 'core.viewTickets', 'com_multiagency', $this->clusterId))
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
				$app->setHeader('status', 403, true);

				return;
			}
		}

		$this->items              = $this->get('Items');
		$this->pagination         = $this->get('Pagination');
		$this->filterForm         = $this->get('FilterForm');
		$this->activeFilters      = $this->get('ActiveFilters');
		$this->permissions        = $this->get('permissions');
		$this->rsTicketdateFormat = RSTicketsProHelper::getConfig('date_format');
		$this->params             = $app->getParams('com_dpe');
		$this->trusteeTags 		  = $this->get('TrusteeTags');


		$this->_prepareDocument();
		parent::display($tpl);
	}

	/**
	 * Setup ACL based tjtoolbar
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getToolbar()
	{
		// Make a toolbar (you can give it any name if you do it this way)
		$bar = ToolBar::getInstance('toolbar');

		// Add whatever buttons you require
		$taskName = 'rsticketspro.createTicket';
		$task     = "onclick =Joomla.submitbutton('" . $taskName . "');";
		$bar->appendButton('Custom', '<button type="button" ' . $task . ' class="btn btn-success">
				<span class="icon-plus"></span> ' . Text::_('COM_DPE_MY_TICKETS_CREATE_NEW_TICKET') . ' </button>'
				);

		// Generate the html and return
		return $bar->render();
	}

	/**
	 * Method to display my tickets
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function _prepareDocument()
	{
		$app   = Factory::getApplication();
		$menus = $app->getMenu();

		// Because the application sets a default page title, we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_DPE_TITLE_TICKETS'));
		}

		$title = $this->params->get('page_title', '');

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
}
