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

class rsseoViewGkeywords extends HtmlView
{	
	public function display($tpl = null) {
		$this->config	= rsseoHelper::getConfig();

		// Check if we can show the Google keywords form
		$this->check();
		
		$this->state 		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->pagination 	= $this->get('Pagination');
		$this->filterForm	= $this->get('FilterForm');
		$this->activeFilters= $this->get('ActiveFilters');
		$this->logs		 	= $this->get('Logs');
		
		$this->addToolBar();	
		
		parent::display($tpl);
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_('COM_RSSEO_GKEYWORDS'),'rsseo');
		ToolBarHelper::addNew('gkeyword.add');
		ToolBarHelper::editList('gkeyword.edit');
		ToolBarHelper::deleteList('COM_RSSEO_GLOBAL_CONFIRM_DELETE', 'gkeywords.delete');
		
		// Get the toolbar object instance
		$layout = new FileLayout('joomla.toolbar.popup');
		$dhtml = $layout->render(array('text' => Text::_('COM_RSSEO_GKEYWORDS_LOG'), 'btnClass' => 'btn', 'htmlAttributes' => '', 'selector' => 'rsseo-logs', 'name' => 'rsseo-logs', 'class' => 'icon-list', 'doTask' => ''));
		Toolbar::getInstance('toolbar')->appendButton('Custom', $dhtml, 'process');
		
		if (Factory::getUser()->authorise('core.admin', 'com_rsseo'))
			ToolBarHelper::preferences('com_rsseo');
	}
	
	protected function check() {
		$app	= Factory::getApplication();
		$secret	= Factory::getConfig()->get('secret');
		$config = rsseoHelper::getConfig();
		
		if (!extension_loaded('curl')) {
			$app->enqueueMessage(Text::_('COM_RSSEO_NO_CURL'));
			$app->redirect('index.php?option=com_rsseo');
		}
		
		if (!$config->enable_g_keywords) {
			$app->enqueueMessage(Text::_('COM_RSSEO_ENABLE_GOOGLE_KEYWORDS'));
			$app->redirect('index.php?option=com_rsseo');
		}
		
		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsseo/assets/keys/'.md5($secret.'private_key').'.json')) {
			$app->enqueueMessage(Text::_('COM_RSSEO_GSA_KEY_FILE_ERROR'));
			$app->redirect('index.php?option=com_rsseo');
		}
	}
}