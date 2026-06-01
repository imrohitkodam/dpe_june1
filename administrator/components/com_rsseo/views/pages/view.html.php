<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Factory;

class rsseoViewPages extends HtmlView
{
	public function display($tpl = null) {
		$this->simple		= Factory::getSession()->get('com_rsseo.pages.simple',false);
		$this->items 		= $this->get('Items');
		$this->pagination 	= $this->get('Pagination');
		$this->state 		= $this->get('State');
		$this->config 		= rsseoHelper::getConfig();
		$this->hash         = Factory::getApplication()->input->getString('hash');
		$this->batch		= $this->get('BatchFields');
		$this->sef			= Factory::getConfig()->get('sef');
		$this->filterForm	= $this->get('FilterForm');
		$this->activeFilters= $this->get('ActiveFilters');
		
		$this->addToolBar();
		parent::display($tpl);
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_('COM_RSSEO_LIST_PAGES'),'rsseo');
		
		$toolbar = Toolbar::getInstance('toolbar');
		ToolBarHelper::addNew('page.add');
		
		if (rsseoHelper::isJ4()) {
			$dropdown = $toolbar->dropdownButton('status-group')
				->text('JTOOLBAR_CHANGE_STATUS')
				->toggleSplit(false)
				->icon('fas fa-ellipsis-h')
				->buttonClass('btn btn-action')
				->listCheck(true);

			$childBar = $dropdown->getChildToolbar();
			
			$childBar->edit('page.edit')->listCheck(true);
			
			$childBar->delete('pages.delete')
				->text('JTOOLBAR_DELETE')
				->message('JGLOBAL_CONFIRM_DELETE')
				->icon('icon-trash')
				->listCheck(true);
			
			$childBar->publish('pages.publish')->listCheck(true);
			$childBar->unpublish('pages.unpublish')->listCheck(true);
			
			$toolbar->appendButton('Confirm',Text::_('COM_RSSEO_DELETE_ALL_PAGES_MESSAGE',true),'delete',Text::_('COM_RSSEO_DELETE_ALL_PAGES'),'pages.removeall',false);
			
			if (!$this->simple) {
				$childBar->appendButton(
					'Custom', '<joomla-toolbar-button><button onclick="Joomla.submitbutton(\'pages.addsitemap\')" '
					. 'class="button-addsitemap dropdown-item"><span class="fas fa-plus" aria-hidden="true"></span>'
					. Text::_('COM_RSSEO_PAGE_ADDTOSITEMAP') . '</button></joomla-toolbar-button>', 'addsitemap'
				);
				$childBar->appendButton(
					'Custom', '<joomla-toolbar-button><button onclick="Joomla.submitbutton(\'pages.removesitemap\')" '
					. 'class="button-removesitemap dropdown-item"><span class="fas fa-trash" aria-hidden="true"></span>'
					. Text::_('COM_RSSEO_PAGE_REMOVEFROMSITEMAP') . '</button></joomla-toolbar-button>', 'removesitemap'
				);
				$childBar->appendButton(
					'Custom', '<joomla-toolbar-button><button onclick="Joomla.submitbutton(\'restore\')" '
					. 'class="button-restore dropdown-item"><span class="fas fa-flag" aria-hidden="true"></span>'
					. Text::_('COM_RSSEO_RESTORE_PAGES') . '</button></joomla-toolbar-button>', 'restore'
				);
				$childBar->appendButton(
					'Custom', '<joomla-toolbar-button><button onclick="Joomla.submitbutton(\'refresh\')" '
					. 'class="button-refresh dropdown-item"><span class="fas fa-refresh" aria-hidden="true"></span>'
					. Text::_('COM_RSSEO_BULK_REFRESH') . '</button></joomla-toolbar-button>', 'refresh'
				);
				$toolbar->appendButton(
					'Custom', '<joomla-toolbar-button><button onclick="Joomla.submitbutton(\'pages.simple\')" '
					. 'class="btn"><span class="fas fa-compress" aria-hidden="true"></span>'
					. Text::_('COM_RSSEO_SIMPLE_VIEW') . '</button></joomla-toolbar-button>', 'simple'
				);
			} else {
				$toolbar->appendButton(
					'Custom', '<joomla-toolbar-button><button onclick="Joomla.submitbutton(\'pages.standard\')" '
					. 'class="btn"><span class="fas fa-expand" aria-hidden="true"></span>'
					. Text::_('COM_RSSEO_STANDARD_VIEW') . '</button></joomla-toolbar-button>', 'standard'
				);
			}
		} else {
			ToolBarHelper::editList('page.edit');
			ToolBarHelper::deleteList('COM_RSSEO_PAGE_CONFIRM_DELETE','pages.delete');
			$toolbar->appendButton('Confirm',Text::_('COM_RSSEO_DELETE_ALL_PAGES_MESSAGE',true),'delete',Text::_('COM_RSSEO_DELETE_ALL_PAGES'),'pages.removeall',false);
			ToolBarHelper::publishList('pages.publish');
			ToolBarHelper::unpublishList('pages.unpublish');
			
			if (!$this->simple) {
				ToolBarHelper::custom('pages.addsitemap','new','new',Text::_('COM_RSSEO_PAGE_ADDTOSITEMAP'));
				ToolBarHelper::custom('pages.removesitemap','trash','trash',Text::_('COM_RSSEO_PAGE_REMOVEFROMSITEMAP'));
				ToolBarHelper::custom('restore','flag','flag',Text::_('COM_RSSEO_RESTORE_PAGES'));
				ToolBarHelper::custom('refresh','refresh','refresh',Text::_('COM_RSSEO_BULK_REFRESH'));
				ToolBarHelper::custom('pages.simple','contract','contract',Text::_('COM_RSSEO_SIMPLE_VIEW'),false);
			} else {
				ToolBarHelper::custom('pages.standard','expand','expand',Text::_('COM_RSSEO_STANDARD_VIEW'),false);
			}
		}
		
		$layout = new FileLayout('joomla.toolbar.popup');
		$dhtml = $layout->render(array('text' => Text::_('COM_RSSEO_BATCH'), 'btnClass' => 'btn', 'htmlAttributes' => '', 'name' => 'batchpages', 'selector' => 'batchpages', 'class' => 'icon-checkbox-partial', 'doTask' => ''));
		$toolbar->appendButton('Custom', $dhtml, 'batch');
		
		$script = array();
		$script[] = "Joomla.submitbutton = function(task) {";
		$script[] = "if (task == 'refresh') {";
		$script[] = "jQuery('input[name=\"cid[]\"]:checked').each(function() {";
		$script[] = $this->config->crawler_type == 'ajax' ? "jQuery('#refresh' + jQuery(this).val()).click();" : "RSSeo.checkPage(jQuery(this).val(),0);";
		$script[] = "});";
		$script[] = "} else if (task == 'restore') {";
		$script[] = "jQuery('input[name=\"cid[]\"]:checked').each(function() {";
		$script[] = $this->config->crawler_type == 'ajax' ? "jQuery('#restore' + jQuery(this).val()).click();" : "RSSeo.checkPage(jQuery(this).val(),1);";
		$script[] = "});";
		$script[] = "} else Joomla.submitform(task);";
		$script[] = "return false;";
		$script[] = "}";
		
		$this->document->addScriptDeclaration(implode("\n", $script));
		
		if (Factory::getUser()->authorise('core.admin', 'com_rsseo'))
			ToolBarHelper::preferences('com_rsseo');
	}
}