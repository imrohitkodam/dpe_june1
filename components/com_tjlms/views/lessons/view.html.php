<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Component\ComponentHelper;

/**
 * View to welcome
 *
 * @since  1.3.8
 */
class TjlmsViewLessons extends HtmlView
{
	protected $state;

	protected $items;

	protected $form;

	protected $pagination;

	protected $activeFilters;

	protected $olUser;

	protected $menuparams;

	public    $filterForm;

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
		$app          = Factory::getApplication();
		$this->olUser = Factory::getUser();

		// Get component params
		$params                          = ComponentHelper::getParams('com_tjlms');
		$this->launch_lesson_full_screen = $params->get('launch_full_screen', '0', 'INT');

		// Check if user is logged in or not.
		if (!$this->olUser->id)
		{
			$msg     = Text::_('COM_TJLMS_MESSAGE_LOGIN_FIRST');
			$current = Uri::getInstance()->toString();
			$url     = base64_encode($current);
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, false), $msg);
		}

		$this->items         = $this->get('Items');
		$this->state         = $this->get('State');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Get Menu params for adding display params accodingly.
		$this->menuparams = $this->state->get('parameters.menu');

		$this->prepareDocument();

		parent::display();
	}

	/**
	 * Prepares the document
	 *
	 * @return  void
	 */
	protected function prepareDocument()
	{
		$this->pageTitle = Text::_('COM_TJLMS_LIBRARY_LESSONS');

		if ($this->menuparams->get('show_page_heading'))
		{
			$this->pageTitle = $this->escape($this->menuparams->get('page_heading'));
		}
	}
}
