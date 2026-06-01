<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;
use Joomla\CMS\User\User;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Component\ComponentHelper;

/**
 * View to edit
 *
 * @since  1.0.0
 */
class TimelogViewActivityform extends HtmlView
{
	/**
	 * The Form object
	 *
	 * @var  Form
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var  object
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * The model state
	 *
	 * @var  object|array
	 */
	protected $params;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canSave;

	/**
	 * The user object
	 *
	 * @var  \User|null
	 */
	protected $user;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canCreateTimelog;

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

		$this->state   = $this->get('State');
		$this->item    = $this->get('Item');
		$this->params  = $app->getParams('com_timelog');
		$this->canSave = $this->get('CanSave');
		$this->form		= $this->get('Form');

		// Validate user login.
		if (empty($this->user->id))
		{
			$return = base64_encode((string) Uri::getInstance());
			$login_url_with_return = Route::_('index.php?option=com_users&return=' . $return);
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
			$app->redirect($login_url_with_return, 403);
		}

		if ($this->user->authorise('core.manageall', 'com_cluster'))
		{
			$this->canCreateTimelog = true;
		}

		if (!$this->user->authorise('core.manageall', 'com_cluster'))
		{
			$this->canCreateTimelog = RBACL::check($this->user->id, 'com_cluster', 'core.create.logs', 'com_timelog', $clusterId);
		}

		$params                   = ComponentHelper::getParams('com_multiagency');
		$externalLeadConsultant   = (int) $params->get('multiagency_leadconsultant_group');

		if (in_array($externalLeadConsultant, $this->user->groups))
		{
			$this->canCreateTimelog = true;
		}
		
		$params                   = ComponentHelper::getParams('com_multiagency');
		$externalLeadConsultant   = (int) $params->get('multiagency_leadconsultant_group');

		if (in_array($externalLeadConsultant, $this->user->groups))
		{
			$this->canCreateTimelog = true;
		}

		if (!$this->canCreateTimelog)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
// die("jikuolp");

		// DPE Hack - Check if you have access timelog for a activity
		JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);
		$input = $app->input;
		$id    = $input->getInt('id', 0);

		if (!$id)
		{
			$id = $app->getUserState('com_timelog.edit.activity.id');
		}

		if ($this->item->id)
		{
			if (!$this->item->client_id)
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);

				return;
			}

			$activityId     = $this->item->client_id;
			$slaSlaActivity = SlaSlaActivity::getInstance($activityId);

			if (empty($slaSlaActivity->id))
			{
				throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);

				return;
			}

			if (property_exists($slaSlaActivity, 'cluster_id'))
			{
				$clusterId = $slaSlaActivity->cluster_id;
			}

			if (!$this->user->authorise('core.manageall', 'com_cluster'))
			{
				if (!$clusterId)
				{
					throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
				}
			}
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
			$this->params->def('page_heading', Text::_('COM_TIMELOG_DEFAULT_PAGE_TITLE'));
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
