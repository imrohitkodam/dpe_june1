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
 * Healthcheck view class
 *
 * @since  2.5.0
 */
class JmailalertsViewHealthcheck extends HtmlView
{
	/**
	 * Data
	 *
	 * @var  array
	 */
	protected $data;

	/**
	 * Plugin names
	 *
	 * @var  object|string
	 */
	protected $pluginsNames;

	/**
	 * Sidebar HTML
	 *
	 * @var  string
	 */
	protected $sidebar;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		// Get the model
		$model = $this->getModel();

		// Get the plugin names under email-alerts
		$this->data         = $model->healthcheck();
		$this->pluginsNames = $model->getPluginNames();

		ToolbarHelper::title(Text::_('COM_JMAILALERTS') . ': ' . Text::_('COM_JMAILALERTS_HEALTHCHECK'), 'wrench');

		JmailalertsHelper::addSubmenu('healthcheck');
		ToolbarHelper::preferences('com_jmailalerts');

		if (JVERSION < '4.0.0')
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		parent::display($tpl);
	}
}
