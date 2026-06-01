<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class rsseoViewSitemap extends HtmlView
{
	public function display($tpl = null) {
		$this->sitemap		= file_exists(JPATH_SITE.'/sitemap.xml');
		$this->ror			= file_exists(JPATH_SITE.'/ror.xml');
		$this->form			= $this->get('Form');
		$this->percent		= $this->get('Percent');
		
		if (rsseoHelper::isJ4()) {
			Text::script('JGLOBAL_SELECT_NO_RESULTS_MATCH');
			Text::script('JGLOBAL_SELECT_PRESS_TO_SELECT');

			Factory::getDocument()->getWebAssetManager()->usePreset('choicesjs')->useScript('webcomponent.field-fancy-select');
		} else {
			HTMLHelper::_('formbehavior.chosen', '.advancedSelect');
		}
		
		$this->addToolBar();
		parent::display($tpl);
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_('COM_RSSEO_SITEMAP'),'rsseo');
		
		if (Factory::getUser()->authorise('core.admin', 'com_rsseo'))
			ToolBarHelper::preferences('com_rsseo');
	}
}