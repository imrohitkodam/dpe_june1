<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * HTML Emails View class for the JMA component
 *
 * @since  1.5
 */
class JmailalertsViewEmails extends HtmlView
{
	protected $altid;

	protected $cntalert;

	protected $defaultoption;

	protected $defaultSetting;

	protected $pageTitle;

	protected $params;

	protected $print;

	protected $qryConcat;

	protected $user;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		$this->state  = $this->get('State');
		$this->params = ComponentHelper::getParams('com_jmailalerts');
		$this->user   = Factory::getUser();
		$model        = $this->getModel();

		// Get no of count alert
		$cntalert       = $model->gettotalalertcount();
		$this->cntalert = $cntalert;

		if (trim($cntalert) != 0)
		{
			// Creating query for concat from enable plugin for compair to user selected alert
			$qryConcat = $model->alertqryconcat();
			$this->qryConcat = $qryConcat;

			// Get the default alert user selected alerts or default alerts
			$this->defaultoption = $model->getdefaultalertid();

			// Checking user default alert id or not
			$this->defaultSetting = $model->isdefaultset();

			// Getting all alert created alert ids
			$altid       = $model->get_all_alertid();
			$this->altid = $altid;
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
		$app   = Factory::getApplication();

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $app->getMenu()->getActive();

		// Trick - need to do this as we are not using Admin / Formmodel here
		$this->params = $this->params->merge($menu->getParams());

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_JMAILALERTS_VIEW_TITLE_EMAIL_PREFERENCES'));
		}

		$title = $this->params->def('page_title', Text::_('COM_JMAILALERTS_VIEW_TITLE_EMAIL_PREFERENCES'));

		$this->setDocumentTitle($title);

		$app->getPathway()->addItem($title);

		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetaData('robots', $this->params->get('robots'));
		}
	}
}
