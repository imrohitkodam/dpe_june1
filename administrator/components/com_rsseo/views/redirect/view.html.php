<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class rsseoViewRedirect extends HtmlView
{
	public function display($tpl = null) {
		$this->form 		= $this->get('Form');
		$this->item			= $this->get('Item');
		$this->referrers	= $this->get('Referrers');
		$this->eid			= Factory::getApplication()->input->getString('eid','');
		
		if (!$this->eid) { 
			$this->document->addScriptDeclaration("jQuery(function($){ jQuery('#jform_from').on('keyup', function() { RSSeo.generateRSResults(1); }); });");
		}
		
		$this->addToolBar();
		parent::display($tpl);
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_('COM_RSSEO_REDIRECT_NEW_EDIT'),'rsseo');
		
		if ($this->eid) {
			ToolBarHelper::save('redirect.savemultiple');
		} else {
			ToolBarHelper::apply('redirect.apply');
			ToolBarHelper::save('redirect.save');
			ToolBarHelper::save2new('redirect.save2new');
		}
		
		ToolBarHelper::cancel('redirect.cancel');

		if ($this->item->id) {
			Toolbar::getInstance('toolbar')->appendButton('Confirm', Text::_('COM_RSSEO_REDIRECT_REMOVE_REFERRALS_PROMPT'), 'delete', Text::_('COM_RSSEO_REDIRECT_REMOVE_REFERRALS'), 'redirect.referrals', false);
		}
	}
}