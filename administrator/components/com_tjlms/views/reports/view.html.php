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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

jimport('joomla.application.component.view');

/**
 * View class for a list of Tjlms.
 *
 * @since  1.0.0
 */
class TjlmsViewReports extends HtmlView
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
		$app  = Factory::getApplication();
		$canDo = TjlmsHelper::getActions();

		if (!$canDo->get('view.reports'))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
			$app->setHeader('status', 500, true);

			return false;
		}

		$input = $app->input;

		$filterData  = $input->get('filters', '', 'ARRAY');
		$model = $this->getModel();

		// Get respected plugin data
		$this->items		= $model->getData($filterData);

		// Get all columns of that report
		$this->colNames	= $this->get('ColNames');

		// Get saved queries by the logged in users
		$this->saveQueries = $this->get('SavedQueries');

		// Call helper function
		$TjlmsHelper = new TjlmsHelper;
		$TjlmsHelper->getLanguageConstant();

		// Get all enable plugins
		$this->enableReportPlugins = $this->get('enableReportPlugins');

		// Get saved data
		$queryId = $input->get('queryId', '0', 'INT');

		$this->colToshow = array();

		if ($queryId != 0)
		{
			$model = $this->getModel();
			$colToSelect = array('colToshow');
			$QueryData = $model->getQueryData($queryId, $colToSelect);
			$this->colToshow = $QueryData->colToshow;
		}

		$input = Factory::getApplication()->input;

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		TjlmsHelper::addSubmenu('reports');

		$this->addToolbar();

		if (JVERSION >= '3.0')
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		if (!empty($this->saveQueries))
		{
			$saveQueries = array();
			$saveQueries[] = JHTML::_('select.option', '', Text::_('COM_TJLMS_SELONE_QUERY'));

			foreach ($this->saveQueries as $eachQuery)
			{
				$saveQueries[] = JHTML::_('select.option', $eachQuery->report_name . '_' . $eachQuery->id, $eachQuery->name);
			}

			$this->saveQueriesList = $saveQueries;
		}

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

		$bar = JToolBar::getInstance('toolbar');
		ToolBarHelper::title(Text::_('COM_TJLMS_TITLE_REPORT'), 'list');

		$button = "<a class='btn' class='button'
			type='submit' onclick=\"Joomla.submitbutton('reports.csvexport');\" href='#'><span title='Export'
			class='icon-download'></span>" . Text::_('COM_TJLMS_CSV_EXPORT') . "</a>";
		$bar->appendButton('Custom', $button);

		foreach ($this->enableReportPlugins as $eachPlugin) :
				$button = "<a class='btn button report-btn' id='" . $eachPlugin->element . "'
							 onclick=\"loadReport('" . $eachPlugin->element . "'); \" ><span
							class='icon-list'></span>" . Text::_($eachPlugin->name) . "</a>";
				$bar->appendButton('Custom', $button);
		endforeach;

		$this->extra_sidebar = '';
	}
}
