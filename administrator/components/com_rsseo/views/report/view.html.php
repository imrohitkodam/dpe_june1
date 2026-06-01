<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class rsseoViewReport extends HtmlView
{	
	public function display($tpl = null) {
		$layout = $this->getLayout();
		
		if ($layout == 'generate') {
			$this->data			= $this->get('Data');
			$this->config		= rsseoHelper::getConfig();
			$this->lcrawled		= $this->get('LastCrawled');
			$this->mvisited		= $this->get('MostVisited');
			$this->notitle		= $this->get('NoTitle');
			$this->nodesc		= $this->get('NoDesc');
			$this->elinks		= $this->get('ErrorLinks');
			$this->keywords		= $this->get('GKeywords');
		} else {
			HTMLHelper::_('formbehavior.chosen', '.advancedSelect');
			
			$this->form 		= $this->get('Form');
			$this->tabs 		= $this->get('Tabs');
		
			$this->addToolBar();
		}
		
		ob_start();
		parent::display($tpl);
		
		if ($layout == 'generate') {
			Factory::getDocument()->setMimeEncoding('application/pdf');
			require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/pdf.php';
			$out = ob_get_clean();
			
			$filename = Text::_('COM_RSSEO_REPORT_FILENAME').' '.HTMLHelper::_('date', 'now', 'Y-m-d H:i:s').'.pdf';
			
			$pdf = RsseoPDF::getInstance();
			$pdf->render($filename, $out);
			die;
		}
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_('COM_RSSEO_REPORT'),'rsseo');
		ToolBarHelper::apply('report.save');
		ToolBarHelper::link('index.php?option=com_rsseo&view=report&layout=generate', Text::_('COM_RSSEO_REPORT_GENERATE'), 'cog');
		
		if (Factory::getUser()->authorise('core.admin', 'com_rsseo'))
			ToolBarHelper::preferences('com_rsseo');
	}
}