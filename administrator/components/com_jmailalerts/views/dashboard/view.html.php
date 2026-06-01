<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for JMailAlerts Dashboard.
 *
 * @package  JMailAlerts
 *
 * @since    2.5
 */
class JmailalertsViewDashboard extends HtmlView
{
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		// Get download id
		$params = ComponentHelper::getParams('com_jmailalerts');
		$this->downloadid = $params->get('downloadid');

		// Get model
		$model = $this->getModel();

		// Refresh update site
		$model->refreshUpdateSite();

		// Get new version
		$this->latestVersion = $model->getLatestVersion();

		// Get installed version from xml file
		$xml           = simplexml_load_file(JPATH_COMPONENT . '/jmailalerts.xml');
		$version       = (string) $xml->version;
		$this->version = $version;

		// Set toolbar
		$this->addToolbar();
		JmailalertsHelper::addSubmenu('dashboard');

		if (JVERSION < '4.0.0')
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolbar()
	{
		ToolbarHelper::title(Text::_('COM_JMAILALERTS') . ': ' . Text::_('COM_JMAILALERTS_TITLE_DASHBOARD'), 'dashboard.png');
		ToolbarHelper::preferences('com_jmailalerts', 550, 875);
	}
}
