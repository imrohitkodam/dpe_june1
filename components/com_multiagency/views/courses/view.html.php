<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

jimport('joomla.application.component.view');

/**
 * View class for a list of Multiagency.
 *
 * @since  1.6
 */
class MultiagencyViewCourses extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $params;

	protected $agencies = array();

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
		$app = Factory::getApplication();
		$user      = Factory::getUser();
		$selectedAgencies = $app->input->get('agencies', '', 'ARRAY');
		$this->agenciesId = (int) $selectedAgencies[0];

		// Get agencies
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$multiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel');
		$params = ComponentHelper::getParams('com_multiagency');
		$memberRole = $params->get('member_role_id', '0', 'INT');
		$agencies = $multiagencyModel->getAllocatedAgencies($this->user->id, array($memberRole));
		$adminRole = $params->get('multyagency_admin_role_id', '0', 'INT');
		$dpeAdmin = RBACL::getRoleByUser($user->id, 'com_multiagency', 0);

		if (count($agencies) > 0)
		{
			$i = 1;

			foreach ($agencies as $agency)
			{
				if ($i == 1 && empty($this->agenciesId))
				{
					$this->agenciesId = $agency->id;
				}

				$this->agencies[] = HTMLHelper::_('select.option', $agency->id, $agency->title);
			}
		}

		$currentUserSuperUser = $user->authorise('core.admin');

		if (!$currentUserSuperUser && !in_array($adminRole, $dpeAdmin))
		{
			$subuserAuth = RBACL::authorise($user->id, 'com_multiagency', 'core.manageenrollment', 'com_multiagency', $this->agenciesId);

			if (!$subuserAuth)
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

				return $app->setHeader('status', 403, true);
			}
		}

		$this->state      = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->params     = $app->getParams('com_multiagency');

		require_once JPATH_SITE . '/components/com_tjlms/helpers/main.php';
		$comtjlmsHelper = new comtjlmsHelper;
		$this->orderItemid    = $comtjlmsHelper->getItemId('index.php?option=com_tjlms&view=enrolment');

		$this->user = Factory::getUser();

		if ($this->user->guest)
		{
			$return = base64_encode(Uri::getInstance());
			$login_url_with_return = Route::_('index.php?option=com_users&return=' . $return);
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
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
		$title = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_MULTIAGENCY_DEFAULT_PAGE_TITLE'));
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
