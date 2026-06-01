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
use Joomla\CMS\Plugin\PluginHelper;

/**
 * DPE School view class.
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeViewSchools extends BaseHtmlView
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
	 * The Multiagency object
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

		// Validate user login.
		if (empty($this->user->id))
		{
			$app->enqueueMessage(Text::_('COM_DPE_LOGIN_ERROR_MSG'), 'warning');
			$link = base64_encode((string) Uri::getInstance());
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $link, false));
		}

		if (!$this->user->authorise('core.manageall', 'com_cluster'))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->setHeader('status', 403, true);

			return;
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->params       = $app->getParams('com_dpe');
		$this->state        = $this->get('State');
		$defaultFilterValue = $this->params->get('agencyFilterValue', 0, 'INT');

		// To set default value of filter
		if (!$this->state->get('filter.agencyFilter'))
		{
			$this->state->set('filter.agencyFilter', $defaultFilterValue);
		}

		$this->items              = $this->get('Items');
		$this->pagination         = $this->get('Pagination');
		$this->filterForm         = $this->get('FilterForm');

		// To show default value selected in filter
		$this->filterForm->setFieldAttribute('agencyFilter', 'default', $defaultFilterValue, 'filter');

		// Show Licence status filter to DPE admin only
		if (!$this->user->authorise('core.manageall', 'com_cluster'))
		{
			$this->filterForm->removeField('licenceStatus', 'filter');
		}

		$this->activeFilters      = $this->get('ActiveFilters');
		$this->permissions        = $this->get('permissions');

		$this->_prepareDocument();

		$dpeUtility = DPE::utilities();
		$dpeUtility->getLanguageConstant();

		
		PluginHelper::importPlugin('system');
		Factory::getApplication()->triggerEvent('onAftergetActivityScript');
		parent::display($tpl);
	}

	/**
	 * Method to display School Management
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
