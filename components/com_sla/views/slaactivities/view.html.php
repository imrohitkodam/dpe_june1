<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Sla activities view
 *
 * @since  1.0.0
 */
class SlaViewSlaActivities extends HtmlView
{
	/**
	 * An array of items
	 *
	 * @var  array
	 */
	protected $items;

	/**
	 * The pagination object
	 *
	 * @var  JPagination
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * Form object for search filters
	 *
	 * @var  JForm
	 */
	public $filterForm;

	/**
	 * Logged in User
	 *
	 * @var  JObject
	 */
	public $user;

	/**
	 * The active search filters
	 *
	 * @var  array
	 */
	public $activeFilters;

	/**
	 * The sidebar markup
	 *
	 * @var  string
	 */
	protected $sidebar;

	/**
	 * The access varible
	 *
	 * @var  CMSObject
	 *
	 * @since  1.0.0
	 */
	protected $canDo;

	protected $canCreateSlaActivity;

	protected $canView;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{

		$app          = Factory::getApplication();
		$input        = $app->input;
		$this->user   = Factory::getUser();
		$licenseId    = $input->getInt('license_id', 0);
		$this->params = $app->getParams('com_sla');

		if (!$this->user->id)
		{
			$msg = Text::_('COM_SLA_MESSAGE_LOGIN_FIRST');
			$uri = $input->server->get('REQUEST_URI', '', 'STRING');
			$url = base64_encode($uri);
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, false), $msg);
		}

		// DPE hack to check RBACL permissions and removed core permission check from this file
		$this->canView = RBACL::check($this->user->id, 'com_cluster', 'core.view.activity', 'com_sla');

		if ($this->user->authorise('core.manageall', 'com_cluster'))
		{
			$this->canCreateSlaActivity = true;
			$this->canView              = true;
			$this->canTimelog           = true;
		}

		if (!$this->canView)
		{
			$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');

			return;
		}

		// Get state
		$this->state = $this->get('State');

		if (!empty($licenseId))
		{
			$this->state->set('filter.license_id', $licenseId);
		}

		// This calls model function getItems()
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');

		// DPE Hack to restrict tags filter visibility
		
		if (!($this->user->authorise('core.manageall', 'com_cluster')))
		{
			$this->filterForm->removeField('tags', 'filter');
		}

		// To show default value selected in filter
		$this->filterForm->setFieldAttribute('state', 'default', 1, 'filter');

		if (!$this->user->authorise('core.manageall', 'com_cluster'))
		{
			$this->filterForm->removeField('lead_consultant_id', 'filter');
			$this->filterForm->removeField('state', 'filter');
			$this->canCreateSlaActivity = RBACL::check($this->user->id, 'com_cluster', 'core.create.activity', 'com_sla');
			$this->canTimelog           = RBACL::check($this->user->id, 'com_cluster', 'core.timelog', 'com_timelog');
		}

		$this->activeFilters = $this->get('ActiveFilters');
		$this->_prepareDocument();

		// Display the view
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
		$title = Text::_('COM_SLA_ACTIVITIES_LIST_PAGE_TITLE');

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_SLA_ACTIVITIES_LIST_PAGE_TITLE'));
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
	 * Method to order fields
	 *
	 * @return ARRAY
	 */
	protected function getSortFields()
	{
		return array(
			'sa.id' => Text::_('JGRID_HEADING_ID'),
			'sa.sla_id' => Text::_('COM_SLA_LIST_SLA_ACTIVITY_SLA'),
			'sa.sla_service_id' => Text::_('COM_SLA_LIST_SLA_ACTIVITY_SERVICE'),
			'sa.cluster_id' => Text::sprintf('COM_SLA_LIST_SLA_ACTIVITY_SCHOOL', Text::_('COM_MULTIAGENCY_ORGANISATION')),
			'sa.ordering' => Text::_('JGRID_HEADING_ORDERING'),
			'sa.state' => Text::_('JSTATUS'),
		);
	}
}
