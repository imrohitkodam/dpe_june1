<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class rsseoViewAnalytics extends HtmlView
{	
	public function display($tpl = null) {
		$this->config	= rsseoHelper::getConfig();
		$this->app		= Factory::getApplication();
		
		if ($this->app->input->getInt('ajax',0)) {
			$layout = $this->getLayout();
			$this->{$layout} = $this->get('GA'.ucfirst($layout));
		} else {
			// Check if we can show the analytics form
			$this->check();
			
			$this->document->addScriptDeclaration("jQuery(document).ready(function () {
				if (jQuery('#profile').val() != '') {
					RSSeo.updateAnalytics();
				}
			});
			google.load('visualization', '1', {packages: ['corechart','corechart']});");
			
			// Get user profiles
			$this->profiles = $this->get('Profiles');
			$this->selected = $this->get('Selected');
			
			$now			= Factory::getDate()->toUnix();
			$this->rsstart	= HTMLHelper::_('date', ($now - 604800), 'Y-m-d');
			$this->rsend	= HTMLHelper::_('date', ($now - 86400), 'Y-m-d');
			$this->tabs		= $this->get('Tabs');
			
			$this->addToolBar();
		}
		
		parent::display($tpl);
		
		if ($this->app->input->getInt('ajax')) {
			$this->app->close();
		}
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_('COM_RSSEO_GOOGLE_ANALYTICS'),'rsseo');
		
		if (Factory::getUser()->authorise('core.admin', 'com_rsseo'))
			ToolBarHelper::preferences('com_rsseo');
		
		$this->document->addScript('https://www.google.com/jsapi');
	}
	
	protected function check() {
		$secret	= Factory::getConfig()->get('secret');
		
		if (!extension_loaded('curl')) {
			$this->app->enqueueMessage(Text::_('COM_RSSEO_NO_CURL'));
			$this->app->redirect('index.php?option=com_rsseo');
		}
		
		if (!$this->config->analytics_enable) {
			$this->app->enqueueMessage(Text::_('COM_RSSEO_ENABLE_GOOGLE_ANALYTICS'));
			$this->app->redirect('index.php?option=com_rsseo');
		}
		
		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsseo/assets/keys/'.md5($secret.'private_key').'.json')) {
			$this->app->enqueueMessage(Text::_('COM_RSSEO_GSA_KEY_FILE_ERROR'));
			$this->app->redirect('index.php?option=com_rsseo');
		}
	}
}