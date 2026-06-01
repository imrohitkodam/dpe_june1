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
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

class rsseoViewPage extends HtmlView
{
	public function display($tpl = null) {
		$this->layout		= $this->getLayout();
		$this->item			= $this->get('Item');
		$this->config 		= rsseoHelper::getConfig();
		$this->html			= Factory::getConfig()->get('sef_suffix') ? '.html' : '';
		$this->sef			= Factory::getConfig()->get('sef');
		
		if ($this->layout == 'details') {
			$this->details 		 = $this->get('Details');
		} elseif($this->layout == 'links') {

		} else {
			$this->form 		 = $this->get('Form');
			$this->broken 		 = $this->get('Broken');
		}
		
		$this->addToolbar();
		parent::display($tpl);
	}
	
	protected function addToolbar() {
		if ($this->layout == 'details') {
			ToolBarHelper::title(Text::_('COM_RSSEO_PAGE_SIZE_DETAILS'),'rsseo');
			
			$bar 		= ToolBar::getInstance('toolbar');
			$bar->appendButton('Link', 'arrow-left', Text::_('COM_RSSEO_GLOBAL_BACK'), 'index.php?option=com_rsseo&view=page&layout=edit&id='.$this->item->id);
		} elseif($this->layout == 'links') {
			ToolBarHelper::title(Text::_('COM_RSSEO_PAGE_INT_EXT_LINKS'),'rsseo');
			
			$bar 		= ToolBar::getInstance('toolbar');
			$bar->appendButton('Link', 'arrow-left', Text::_('COM_RSSEO_GLOBAL_BACK'), 'index.php?option=com_rsseo&view=page&layout=edit&id='.$this->item->id);
			
			$script = array();
			$script[] = "jQuery(document).ready(function() {";
			$script[] = $this->config->crawler_type == 'ajax' ? "RSSeo.links('".Uri::root().$this->item->url."', '".$this->item->id."');" : "RSSeo.checkLinks('".$this->item->id."');";
			$script[] = "});";
			$this->document->addScriptDeclaration(implode("\n",$script));
			
		} else {
			ToolBarHelper::title(Text::_('COM_RSSEO_PAGE_NEW_EDIT'),'rsseo');
		
			ToolBarHelper::apply('page.apply');
			ToolBarHelper::save('page.save');
			ToolBarHelper::cancel('page.cancel');
			
			HTMLHelper::script('com_rsseo/jquery.tablednd.js', array('relative' => true, 'version' => 'auto'));
			
			$script = array();
			$script[] = "Joomla.submitbutton = function(task) {";
			$script[] = "if (task == 'page.cancel') {";
			$script[] = "Joomla.submitform(task, document.adminForm);";
			$script[] = "} else {";
			$script[] = "if (document.formvalidator.isValid(document.adminForm)) {";
			$script[] = "if (!jQuery('#jform_short_dummy').prop('readonly')) RSSeo.saveShort('".Uri::root()."');";
			
			if ($this->config->crawler_type == 'ajax') {
				$script[] = "jQuery('#toolbar button').prop('disabled', true);";
				$script[] = "RSSeo.redirectSave  = '".Route::_('index.php?option=com_rsseo&view=pages', false)."'";
				$script[] = "RSSeo.redirectApply = '".Route::_('index.php?option=com_rsseo&view=page&layout=edit&id=', false)."'";
				
				if ($this->item->id) {
					$script[] = "RSSeo.savePage(task, '".Uri::root().$this->item->url."', jQuery('#jform_original:checked').length);";
				} else {
					$script[] = "RSSeo.savePage(task, '".Uri::root()."', jQuery('#jform_original:checked').length, true);";
				}
			} else {
				$script[] = "Joomla.submitform(task, document.adminForm);";
			}
			
			$script[] = "} else {";
			$script[] = "alert('".Text::_('JGLOBAL_VALIDATION_FORM_FAILED')."');";
			$script[] = "}";
			$script[] = "}";
			$script[] = "}";
			$script[] = "jQuery(document).ready(function() {";
			$script[] = "RSSeo.updateSnippet();";
			$script[] = "jQuery('#metaDraggable').tableDnD();";
			$script[] = "jQuery('#jform_title, #jform_keywords, #jform_description').each(function() {";
			$script[] = "RSSeo.counters(jQuery(this));";
			$script[] = "jQuery(this).on('keyup', function() {";
			$script[] = "RSSeo.counters(jQuery(this));";
			$script[] = "})";
			$script[] = "});";
			$script[] = "jQuery('#jform_canonical').on('keyup', function() {";
			$script[] = "RSSeo.generateRSResults(0);";
			$script[] = "});";
			$script[] = "});";
			$script[] = "RSSeo.titleLength = ".(int) $this->config->title_length.";";
			$script[] = "RSSeo.keywordsLength = ".(int) $this->config->keywords_length.";";
			$script[] = "RSSeo.descriptionLength = ".(int) $this->config->description_length.";";
			
			$this->document->addScriptDeclaration(implode("\n",$script));
		}
	}
}