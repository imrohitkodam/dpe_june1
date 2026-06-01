<?php
/**
 * @package     TJ-UCM
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2025 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\Toolbar;

/**
 * View class for a list of Tjucm.
 *
 * @since  1.6
 */
class DpeViewucmbulkdownload extends BaseHtmlView
{

	protected $pagination;

	protected $state;

	/**
	 * @var  String
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public $toolbarHTML;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return boolean|void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{

		$app  = Factory::getApplication();
		$user = Factory::getUser();

		if (!$user->id)
		{
			$msg = Text::_('COM_TJUCM_LOGIN_MSG');

			// Get current url.
			 $current = Uri::getInstance()->toString();

			$url = base64_encode($current);
			Factory::getApplication()->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, Text::_('COM_TJUCM_LOGIN_MSG')));
		}

		$this->state        = $this->get('State');
		$this->items        = $this->get('Items'); 
      	$this->pagination   = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

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
		return $bar->render();
	}
}
