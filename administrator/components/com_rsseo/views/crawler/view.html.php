<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;

class rsseoViewCrawler extends HtmlView
{	
	public function display($tpl = null) {
		
		$config			= Factory::getConfig();
		$this->config  	= rsseoHelper::getConfig();
		$this->offline 	= $config->get('offline');
		$this->shared	= $config->get('shared_session');
		
		if ($this->offline) {
			Factory::getApplication()->enqueueMessage(Text::_('COM_RSSEO_CRAWLER_SITE_OFFLINE'), 'error');
		}
		
		$this->document->addScriptDeclaration('RSSeo.seconds = '.$this->config->request_timeout.';');
		
		$this->addToolBar();
		parent::display($tpl);
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_('COM_RSSEO_CRAWLER'),'rsseo');
		
		if (Factory::getUser()->authorise('core.admin', 'com_rsseo'))
			ToolBarHelper::preferences('com_rsseo');
	}
}