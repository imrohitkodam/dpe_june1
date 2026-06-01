<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class rsseoViewRsseo extends HtmlView
{
	public function display($tpl=null) {
		$this->version		= (string) new RSSeoVersion();
		$this->code			= rsseoHelper::getConfig('global_register_code');
		$this->pages		= rsseoHelper::getMostVisited();
		$this->lastcrawled	= $this->get('LastCrawled');
		$this->info			= $this->get('Info');
		$this->keywords		= $this->get('Keywords');
		$this->cache		= $this->get('Cache');
		
		$this->addToolBar();
		parent::display($tpl);
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_('COM_RSSEO_GLOBAL_COMPONENT'),'rsseo');
		
		if ($this->keywords) {
			$this->document->addScript('https://www.gstatic.com/charts/loader.js');
			$this->document->addScriptDeclaration("google.charts.load('current', {packages: ['corechart', 'line']});
			google.charts.setOnLoadCallback(function() {
				RSSeo.drawGoogleKeywordChartDashboard();
			});

			jQuery(document).ready(function() {
				jQuery(window).resize(function() {
					RSSeo.drawGoogleKeywordChartDashboard();
				});
			});");
		}
		
		if (Factory::getUser()->authorise('core.admin', 'com_rsseo'))
			ToolBarHelper::preferences('com_rsseo');
	}
}