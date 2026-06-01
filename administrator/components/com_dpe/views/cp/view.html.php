<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Control Panel controller class.
 *
 * @package  DPE
 * @since    __DEPLOY_VERSION__
 */
class DPEViewCp extends BaseHtmlView
{
	/**
	 * Function to display.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths
	 *
	 * @return  mixed  A string.
	 *
	 * @since	__DEPLOY_VERSION__
	 */
	public function display($tpl = null)
	{
		$this->_setToolBar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function _setToolBar()
	{
		ToolBarHelper::title(Text::_('COM_DPE_DASHBOARD'), 'list');
		ToolBarHelper::preferences('com_dpe');

		// To Display database fix button
		ToolbarHelper::custom('database.fix', 'refresh', 'refresh', 'COM_DPE_FIX_DATABASE', false);
	}
}
