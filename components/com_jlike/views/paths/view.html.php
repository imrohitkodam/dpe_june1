<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_JLike
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View to edit
 *
 * @since  1.6
 */
class JLikeViewPaths extends JViewLegacy
{
	protected $state;

	protected $item;

	protected $form;

	protected $params;

	protected $canSave;

	protected $app;

	protected $user;

	protected $menu;

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
		$this->app  = JFactory::getApplication();
		$this->user = JFactory::getUser();
		$this->menu = $this->app->getMenu();

		// Validate user login
		if (!$this->user->id)
		{
			$current = JUri::getInstance()->toString();
			$url     = base64_encode($current);
			$this->app->redirect(JRoute::_('index.php?option=com_users&view=login&return=' . $url, false));
		}

		$this->state      = $this->get('State');

		JLoader::import('components.com_jlike.models.pathnodegraphs', JPATH_SITE);
		$pathNodeGraphModel = JModelLegacy::getInstance('Pathnodegraphs', 'JLikeModel');
		$pathNodeGraphModel->setState('filter.isPath', '1');
		$pathNodeGraphModel->setState('filter.parent', '1');

		$this->items = $this->get('Items');

		$this->pagination = $this->get('Pagination');
		$this->params     = $this->app->getParams('com_jlike');

		$this->filterForm = $this->get('FilterForm');

		$this->activeFilters = $this->get('ActiveFilters');

		// To check user is subscribed for path
		JLoader::import('components.com_jlike.helpers.path', JPATH_SITE);
		$comJlikePathHelper = new ComjlikePathHelper;
		$isSubscribedPath = $comJlikePathHelper->isSubscribedPath($this->user->id);

		// If user is subscribed for path then redirects to pathdetail
		if (!empty($isSubscribedPath))
		{
			$menuItem = $this->menu->getItems('link', 'index.php?option=com_jlike&view=pathdetail', true);
			$linkUrl = 'index.php?option=com_jlike&view=pathdetail&path_id=' . $isSubscribedPath->path_id . '&Itemid=' . $menuItem->id;

			$link = JRoute::_($linkUrl, false);
			$this->app->redirect($link);
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		parent::display($tpl);
	}
}
