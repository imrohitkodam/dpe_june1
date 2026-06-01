<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Factory;

class rsseoViewGkeyword extends HtmlView
{
	public function display($tpl = null) {
		$template			= Factory::getApplication()->input->get('tpl');
		$tpl				= $template ? $template : $tpl;
		$this->form 		= $this->get('Form');
		$this->item			= $this->get('Item');
		$this->dates		= $this->get('Dates');
		$this->data			= $this->get('Data');
		$this->total		= $this->get('Total');
		$this->json			= $this->get('Json');
		$this->devices		= $this->get('Devices');
		$this->device		= $this->get('Device');
		$this->countries	= $this->get('Countries');
		$this->country		= $this->get('Country');
		$this->from			= $this->get('From');
		$this->to			= $this->get('To');
		
		if ($tpl == 'page') {
			$this->items 	= $this->get('Pages');
		}
		
		$this->addToolBar();
		parent::display($tpl);
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_($this->item->id ? 'COM_RSSEO_GKEYWORD_VIEW_DATA' : 'COM_RSSEO_GKEYWORD_NEW'),'rsseo');
		
		ToolBarHelper::apply('gkeyword.apply');
		ToolBarHelper::save('gkeyword.save');
		ToolBarHelper::save2new('gkeyword.save2new');
		ToolBarHelper::cancel('gkeyword.cancel');
		
		if ($this->item->id) {
			$this->document->addScript('https://www.gstatic.com/charts/loader.js');
			
			if ($this->data) {
				$this->document->addScriptDeclaration("RSSeo.jsonPositionChartData = ".$this->json.";
					google.charts.load('current', {packages: ['corechart', 'line']});
					google.charts.setOnLoadCallback(function() {
						RSSeo.drawGoogleKeywordChart();
					});
		
					jQuery(document).ready(function() {
						jQuery(window).resize(function() {
							RSSeo.drawGoogleKeywordChart();
						});
					});
				");
			}
			
			// Get the toolbar object instance
			$layout = new FileLayout('joomla.toolbar.popup');
			$dhtml = $layout->render(array('text' => Text::_('COM_RSSEO_GKEYWORD_IMPORT'), 'btnClass' => 'btn', 'htmlAttributes' => '', 'selector' => 'process-data', 'name' => 'process-data', 'class' => 'icon-cog', 'doTask' => ''));
			Toolbar::getInstance('toolbar')->appendButton('Custom', $dhtml, 'process');
		}
	}
}