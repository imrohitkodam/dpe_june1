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

class rsseoViewError extends HtmlView
{
	public function display($tpl = null) {
		$this->form 		= $this->get('Form');
		$this->item			= $this->get('Item');
		
		$this->addToolBar();
		parent::display($tpl);
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_('COM_RSSEO_ERROR_NEW_EDIT'),'rsseo');
		
		$this->document->addScriptDeclaration("RSSeo.errorType(".(isset($this->item->type) ? $this->item->type : 1).");");
			
		ToolBarHelper::apply('error.apply');
		ToolBarHelper::save('error.save');
		ToolBarHelper::cancel('error.cancel');
	}
}