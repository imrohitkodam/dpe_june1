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

class rsseoViewErrorlinks extends HtmlView
{	
	public function display($tpl = null) {
		$layout = $this->getLayout();
		
		if ($layout == 'referrals') {
			$this->referrals	= $this->get('Referrals');
		} else {
			$this->items 		= $this->get('Items');
			$this->pagination 	= $this->get('Pagination');
			$this->state 		= $this->get('State');
			$this->filterForm	= $this->get('FilterForm');
			
			$this->addToolBar();
		}
		
		parent::display($tpl);
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_('COM_RSSEO_LIST_ERROR_LINKS'),'rsseo');
		ToolBarHelper::custom('errorlinks.createRedirect','new', 'new', 'COM_RSSEO_CREATE_REDIRECT');
		ToolBarHelper::deleteList('COM_RSSEO_GLOBAL_CONFIRM_DELETE', 'errorlinks.delete');
		
		if (Factory::getUser()->authorise('core.admin', 'com_rsseo'))
			ToolBarHelper::preferences('com_rsseo');
	}
}