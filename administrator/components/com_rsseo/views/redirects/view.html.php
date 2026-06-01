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

class rsseoViewRedirects extends HtmlView
{
	public function display($tpl = null) {
		$this->items 		= $this->get('Items');
		$this->pagination 	= $this->get('Pagination');
		$this->state 		= $this->get('State');
		$this->filterForm	= $this->get('FilterForm');
		$this->activeFilters= $this->get('ActiveFilters');
		
		$this->addToolBar();
		
		parent::display($tpl);
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_('COM_RSSEO_LIST_REDIRECTS'),'rsseo');
		
		ToolBarHelper::addNew('redirect.add');
		ToolBarHelper::editList('redirect.edit');
		ToolBarHelper::deleteList('COM_RSSEO_GLOBAL_CONFIRM_DELETE', 'redirects.delete');
		ToolBarHelper::publishList('redirects.publish');
		ToolBarHelper::unpublishList('redirects.unpublish');
		
		if (Factory::getUser()->authorise('core.admin', 'com_rsseo')) {
			ToolBarHelper::preferences('com_rsseo');
		}
	}
}