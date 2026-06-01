<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Form\FormHelper;

/**
 * View to edit
 *
 * @since  1.6
 */
class MultiagencyViewUserform extends HtmlView
{
	protected $state;

	protected $item;

	protected $form;

	protected $params;

	protected $canSave;

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
		$app  = Factory::getApplication();
		$this->user = Factory::getUser();

		$addOwnUser = RBACL::authorise($this->user->id, 'com_multiagency', 'core.own.adduser', 'com_multiagency');
		$this->addUser = RBACL::authorise($this->user->id, 'com_multiagency', 'core.adduser', 'com_multiagency');
		$this->editOwnUser = RBACL::authorise($this->user->id, 'com_multiagency', 'core.own.edituser', 'com_multiagency');
		$this->editUser = RBACL::authorise($this->user->id, 'com_multiagency', 'core.edituser', 'com_multiagency');

		if (!$addOwnUser && !$this->addUser && !$this->user->authorise('core.manageall', 'com_cluster'))
		{
			$return = base64_encode(Uri::getInstance());
			$login_url_with_return = Route::_('index.php?option=com_users&return=' . $return);
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect($login_url_with_return, 403);
		}

		$this->state         = $this->get('State');
		$this->item          = $this->get('Data');
		$this->params        = $app->getParams('com_multiagency');
		$this->canSave       = $this->get('CanSave');
		$this->form          = $this->get('Form');
		$this->model         = $this->getModel('userform');
		$this->staffRoleId   = $this->params->get("member_role_id", "0", "INT");
		$this->trusteeRoleId = $this->params->get("organization_trustee_role_id", "0", "INT");

		// Get the role list
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_multiagency/models/fields/');
		$agency           = FormHelper::loadFieldType('agency', false);
		$this->agencyList = $agency->getOptionsExternally();

		// Get the related role list
		FormHelper::addFieldPath(JPATH_SITE . '/plugins/system/dpe/fields/');
		$relatedrole           = FormHelper::loadFieldType('relatedrole', false);
		$this->relatedroleList = $relatedrole->getOptionsExternally();

		if ($this->item->id && (!$this->editOwnUser && !$this->editUser && !$this->user->authorise('core.manageall', 'com_cluster')))
		{
			$return = base64_encode(Uri::getInstance());
			$login_url_with_return = Route::_('index.php?option=com_users&return=' . $return);
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect($login_url_with_return, 403);
		}

		$isMultiagencyUser = $app->input->get('isMultiagencyUser', '0', 'INT');

		if ($this->user->guest)
		{
			$return = base64_encode(Uri::getInstance());
			$login_url_with_return = Route::_('index.php?option=com_users&return=' . $return);
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
			$app->redirect($login_url_with_return, 403);
		}

		if ($isMultiagencyUser)
		{
			$app->setUserState("user.isMultiagencyUser", "1");
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

		if (empty($title))
		{
			if (!empty($this->item->user_id))
			{
				$title = Factory::getUser($this->item->user_id)->name;
			}
			else
			{
				$title = Text::_('COM_MULTIAGENCY_USER_FORM_PAGE_TITLE');
			}
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
}
