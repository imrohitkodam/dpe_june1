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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

JLoader::import('components.com_multiagency.helpers.multiagency', JPATH_SITE);

/**
 * View to edit
 *
 * @since  1.6
 */
class MultiagencyViewMultiagencyform extends HtmlView
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
		$app        = Factory::getApplication();
		$input      = $app->input;
		$user       = Factory::getUser();
		$MultiagencyFrontendHelpers = new MultiagencyFrontendHelpers;

		// View is accessible for dpe admin and superuser only
		$isGRI  = $user->authorise('core.edit', 'com_multiagency');
		$view   = $input->get('view');
		$layout = $input->get('layout');

		if ($user->guest)
		{
			$return = base64_encode(Uri::getInstance());
			$login_url_with_return = Route::_('index.php?option=com_users&return=' . $return);
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
			$app->redirect($login_url_with_return, 403);
		}
		elseif(!($isGRI) && ($view === "multiagencyform" && $layout != "agencyinfo"))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->setHeader('status', 403, true);

			return;
		}

		// DPE Hack to check access of cluster

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			if ($view === "multiagencyform" && $layout === "agencyinfo")
			{
				// Check cluster id and check cluster access
				JLoader::import("/components/com_cluster/libraries/cluster", JPATH_ADMINISTRATOR);

				$clusterId = $input->get('clusterId', 0);
				$clustertable = ClusterFactory::table('Clusters');
				$clustertable->load(array('id' => $clusterId));

				if (!$clustertable->client_id)
				{
					$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

					return;
				}

				// Check logged-in user is a member of cluster
				$cluster = ClusterCluster::getInstance($clusterId);

				$isValid = $cluster->isMember($user->id);

				// Check user not manageall cluster permission & not a members of cluster
				if (!$isValid)
				{
					$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

					return;
				}
			}
		}

		// DPE Hack end

		$this->state   = $this->get('State');
		$this->item    = $this->get('Data');
		$this->model   = $this->getModel('multiagencyForm');
		$this->params  = $app->getParams('com_multiagency');
		$this->canSave = $this->get('CanSave');
		$this->form	   = $this->get('Form');

		$path = JPATH_SITE . '/components/com_tjfields/helpers/geo.php';

		if (!class_exists('tmtTestsHelper'))
		{
			// Require_once $path
			JLoader::register('TjGeoHelper', $path);
			JLoader::load('TjGeoHelper');
		}

		$tjGeoHelper = TjGeoHelper::getInstance('TjGeoHelper');

		// Get country list
		$defaultCountry = array();
		$defaultCountry['id'] = '';
		$defaultCountry['country'] = Text::_('COM_MULTIAGENCY_SELECT_COUNTRY');
		$this->countryList = (array) $tjGeoHelper->getCountryList();
		$this->countryList = array_merge(array($defaultCountry), $this->countryList);

		// Get manager list
		$defaultManager = array();
		$defaultManager['id'] = '';
		$defaultManager['username'] = Text::sprintf('COM_MULTIAGENCY_SELECT_MULTIAGENCY_MANAGER', Text::_('COM_MULTIAGENCY_ORGANISATION'));
		$this->managerList = (array) $this->model->getMultiagencyManagers();
		$this->managerList = array_merge(array($defaultManager), $this->managerList);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->_prepareDocument();

		$offset = $this->state->get('list.offset');

		// Process the content plugins.
		PluginHelper::importPlugin('content');
		Factory::getApplication()->triggerEvent('onContentPrepare', array ('com_multiagency.multiagency', &$this->item, &$this->item->params, $offset));

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
}
