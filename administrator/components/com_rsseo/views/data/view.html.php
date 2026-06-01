<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class rsseoViewData extends HtmlView
{
	public function display($tpl = null) {
		$this->form = $this->get('form');
		$this->tabs = $this->get('tabs');
		
		PluginHelper::importPlugin('rsseo');
		
		$this->addToolBar();
		parent::display($tpl);
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_('COM_RSSEO_STRUCTURED_DATA'),'rsseo');
		ToolBarHelper::apply('data.save');
		
		$this->document->addScriptDeclaration("jQuery(document).ready(function () {
			if (typeof(Storage) !== 'undefined') {
				if (sessionStorage.rsseoSelectedTab) {
					jQuery('#structuredDataTabs > li a[href=\"#' + sessionStorage.rsseoSelectedTab + '\"]').click();
				} else {
					jQuery('#structuredDataTabs > li a:first').click();
				}
				
				jQuery('#structuredDataTabs > li > a').click(function() {
					sessionStorage.rsseoSelectedTab = jQuery(this).attr('href').replace('#','');
				});
			}
		});");
		
		if (Factory::getUser()->authorise('core.admin', 'com_rsseo'))
			ToolBarHelper::preferences('com_rsseo');
	}
}