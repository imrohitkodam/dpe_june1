<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

jimport('joomla.application.component.view');

/**
 * View class for a list of Tjlms.
 *
 * @since  1.0.0
 */
class TjlmsViewLessonreport extends HtmlView
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
		$this->filterForm->removeField('statusfilter', 'filter');
		$this->filterForm->removeField('attempt_starts', 'filter');
		$this->filterForm->removeField('attempt_ends', 'filter');
		$this->filterForm->removeField('coursefilter', 'filter');
		$this->activeFilters = $this->get('ActiveFilters');

		$input = Factory::getApplication()->input;

		$this->filterAttemptState = $this->state->get('filter.attemptState');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		TjlmsHelper::addSubmenu('lessonreport');

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  Toolbar instance
	 *
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/tjlms.php';

		$state	= $this->get('State');
		$bar = JToolBar::getInstance('toolbar');
		ToolBarHelper::title(Text::_('COM_TJLMS_TITLE_LESSON_REPORT'), 'list');

		if (!empty($this->items))
		{
			$button = "<a class='btn' class='button'
				type='submit' onclick=\"Joomla.submitbutton('lessonreport.csvexport');document.id('task').value='';\" href='#'><span title='Export'
				class='icon-download'></span>" . Text::_('COM_TJLMS_CSV_EXPORT') . "</a>";
			$bar->appendButton('Custom', $button);
		}

		$this->extra_sidebar = '';
	}
}
