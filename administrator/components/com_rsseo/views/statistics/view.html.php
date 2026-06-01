<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class rsseoViewStatistics extends HtmlView
{
	public function display($tpl = null) {
		$layout			= $this->getLayout();
		$this->config	= rsseoHelper::getConfig();
		
		if ($layout == 'pageviews') {
			$this->pageviews	= $this->get('PageViews');
			$this->pagination	= $this->get('PageViewsPagination');
			
		} else {
			$this->from		= Factory::getDate()->modify('-7 days')->format('Y-m-d');
			$this->to		= Factory::getDate()->format('Y-m-d');
			
			$this->totalvisitors	= $this->get('TotalVisitors');
			$this->totalpageviews	= $this->get('TotalPageViews');
			$this->totalvisitorst	= $this->get('TotalVisitorsTimeframe');
			$this->totalpageviewst	= $this->get('TotalPageViewsTimeframe');
			
			$this->visitors		= $this->get('Visitors');
			$this->total		= $this->get('VisitorsTotal');
			$this->count		= count($this->visitors);
			
			$this->addToolBar();
		}
		
		parent::display($tpl);
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_('COM_RSSEO_LIST_STATISTICS'),'rsseo');
		ToolBarHelper::deleteList('','removeVisitors');

		ToolBar::getInstance('toolbar')->appendButton('Confirm',Text::_('COM_RSSEO_DELETE_ALL_VISITORS_MESSAGE',true),'delete',Text::_('COM_RSSEO_DELETE_ALL_VISITORS'),'removeAllVisitors',false);
		
		if (Factory::getUser()->authorise('core.admin', 'com_rsseo'))
			ToolBarHelper::preferences('com_rsseo');
		
		$this->document->addScript('https://www.google.com/jsapi');
		$this->document->addScriptDeclaration("google.load('visualization', '1', {packages: ['corechart', 'line']});");
		$this->document->addScriptDeclaration("jQuery(document).ready(function() {
			RSSeo.updateCharts();
			
			jQuery(window).resize(function() {
				RSSeo.updateCharts();
			});
			
			jQuery('a[href=\"#stat-visitors\"]').on('click', function() {
				RSSeo.updateCharts();
			});
		});");
	}
}