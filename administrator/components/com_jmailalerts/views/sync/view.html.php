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

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for sync
 *
 * @package  JMailAlerts
 *
 * @since    2.5
 */
class JmailalertsViewsync extends HtmlView
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
		// Get the model
		$model = $this->getModel();

		// Get alert name
		$this->alertname = $model->getAlertnames();

		// Get groups name
		$this->groups = $model->getUserGroups();

		// Get enables plugin names and element
		$this->plugin_data = $model->getPluginData();

		// Get the plugin names under email-alerts
		$this->email_alert_plugin_names = $model->getPluginNames();

		JmailalertsHelper::addSubmenu('sync');

		if (JVERSION < '4.0.0')
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since  1.6
	 *
	 * @return  void
	 */
	protected function addToolbar()
	{
		ToolbarHelper::title(Text::_('COM_JMAILALERTS') . ': ' . Text::_('COM_JMAILALERTS_SYNC'), 'users');

		ToolbarHelper::preferences('com_jmailalerts');
	}
}
