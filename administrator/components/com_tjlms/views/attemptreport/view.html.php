<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Component\ComponentHelper;

jimport('joomla.application.component.view');
jimport('techjoomla.tjtoolbar.button.csvexport');

/**
 * View class for a list of Tjlms.
 *
 * @since  1.0.0
 */
class TjlmsViewAttemptreport extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');

		$this->pagination	= $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');
		$this->filterForm->removeField('lesonformat', 'filter');
		$this->activeFilters = $this->get('ActiveFilters');

		$input = Factory::getApplication()->input;

		$this->tjlmsparams = ComponentHelper::getParams('com_tjlms');
		$this->adminKey = $this->tjlmsparams->get('admin_key_review_answersheet', 'abcd1234', 'STRING');
		$this->adminKey = md5($this->adminKey);

		// Call helper function
		$TjlmsHelper = new TjlmsHelper;
		$TjlmsHelper->getLanguageConstant();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		TjlmsHelper::addSubmenu('attemptreport');

		$this->addToolbar();

		if (JVERSION >= '3.0')
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
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/tjlms.php';
		$user      = Factory::getUser();
		$canCreate = $user->authorise('core.create', 'com_tjlms');
		$bar = JToolBar::getInstance('toolbar');

		ToolBarHelper::title(Text::_('COM_TJLMS_TITLE_ATTEMPT_REPORT'), 'list');

		if (!empty($this->items) && $canCreate === true && $user->id)
		{
			$message               = array();
			$message['success']    = Text::_("COM_TJLMS_EXPORT_FILE_SUCCESS");
			$message['error']      = Text::_("COM_TJLMS_EXPORT_FILE_ERROR");
			$message['inprogress'] = Text::_("COM_TJLMS_EXPORT_FILE_NOTICE");
			$message['text']       = Text::_("COM_TJLMS_EXPORT_TOOLBAR_TITLE");

			$bar->appendButton('CsvExport', $message);
		}

		// Show archive attempts and delete from lesson track
		ToolBarHelper::archiveList('attemptreport.archiveAttempts', 'COM_TJLMS_ARCHIVE_ATTEMPTS');

		// Show trash and delete for components that uses the state field
		ToolBarHelper::deleteList(Text::_('COM_TJLMS_SURE_DELETE'), 'attemptreport.delete', 'JTOOLBAR_DELETE');

		$this->extra_sidebar = '';
	}
}
