<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView as HTMLHelperView;

jimport('techjoomla.common');

/**
 * View of file download stats
 *
 * @since  1.4.0
 */
class TjlmsViewFileDownloadStats extends HTMLHelperView
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $activeFilters;

	protected $sidebar;

	public $filterForm;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @since  1.4.0
	 */
	public function display($tpl = null)
	{
		$this->state         = $this->get('State');
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		TjlmsHelper::addSubmenu('filedownloadstats');

		$this->sidebar = JHtmlSidebar::render();

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  Toolbar instance
	 *
	 * @since	1.4.0
	 */
	protected function addToolbar()
	{
		ToolbarHelper::title(Text::_('COM_TJLMS_TITLE_FILE_DOWNLAOD_STATUS'), 'list');

		// Show trash and delete for components that uses the state field
		ToolbarHelper::deleteList(Text::_('COM_TJLMS_SURE_DELETE'), 'filedownloadstats.delete', 'JTOOLBAR_DELETE');

		ToolbarHelper::preferences('com_tjlms');

		// Set sidebar action - New in 3.0
		JHtmlSidebar::setAction('index.php?option=com_tjlms&view=filedownloadstats');
	}
}
