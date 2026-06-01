<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.view');

/**
 * Certificates view
 *
 * @since       1.1.0
 * @deprecated  1.3.32 Use TJCertificate certificates view instead
 */
class TjlmsViewCertificates extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $comtjlmsHelper;

	public $filterForm;

	protected $user;

	protected $pageclass_sfx;

	protected $activeFilters;

	/**
	 * Display the  view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return mixed
	 */
	public function display($tpl = null)
	{
		$app = Factory::getApplication();
		$this->techjoomlacommon = new TechjoomlaCommon;
		$this->user	= Factory::getUser();

		if (!$this->user->id)
		{
			$url 	   = base64_encode(Uri::getInstance()->toString());
			$login_url = Route::_('index.php?option=com_users&view=login&return=' . $url, false);
			$app->enqueueMessage(Text::_('COM_TJLMS_LOGIN_MESSAGE'), 'error');
			$app->redirect($login_url);

			return false;
		}

		$this->comtjlmsHelper = new comtjlmsHelper;
		$this->state		= $this->get('State');
		$this->params		= $this->state->params;
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx'));

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
	 * @return  void
	 */
	protected function _prepareDocument()
	{
		$app		= Factory::getApplication();
		$menus		= $app->getMenu();
		$pathway	= $app->getPathway();
		$title		= null;

		// Because the application sets the default page title,
		// we need to get it from the menu item itself
		$menu		= $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_TJLMS_CERTIFICATES_DEFAULT_PAGE_TITLE'));
		}

		$title = $this->params->get('page_title', '');

		if (empty($title))
		{
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
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
