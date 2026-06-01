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
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\CMS\Factory;

class rsseoViewBackup extends HtmlView
{
	public function display($tpl = null) {
		$this->process = Factory::getApplication()->input->getString('process');
		
		if ($this->process == 'backup') {
			$this->backup = $this->backup();
		} else if ($this->process == 'restore') {
			$this->restore = $this->restore();
		}
		
		$this->cleanup();
		$this->addToolBar();
		parent::display($tpl);
	}
	
	protected function addToolBar() {
		ToolBarHelper::title(Text::_('COM_RSSEO_BACKUP_RESTORE'),'rsseo');
		
		if ($this->process) {
			ToolBar::getInstance('toolbar')->appendButton('Link', 'arrow-left', Text::_('COM_RSSEO_GLOBAL_BACK'), 'index.php?option=com_rsseo&view=backup');
		}
		
		if (Factory::getUser()->authorise('core.admin', 'com_rsseo'))
			ToolBarHelper::preferences('com_rsseo');
	}
	
	protected function backup() {
		require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/backup.php';
		
		$db			= Factory::getDbo();
		$query		= $db->getQuery(true);
		$options	= array();
		
		$tables	= array('#__rsseo_pages' => 'id', 
			'#__rsseo_redirects' => 'id', 
			'#__rsseo_keywords' => 'id', 
			'#__rsseo_errors' => 'id',
			'#__rsseo_gkeywords_data' => 'id',
			'#__rsseo_gkeywords' => null
		);
		
		foreach ($tables as $table => $primary) {
			$options['queries'][] = array('query' => 'SELECT * FROM '.$table , 'primary' => $primary);
		}
		
		$package = new RSPackage($options);
		$package->backup();
		return $package->displayProgressBar();
	}
	
	protected function restore() {
		require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/backup.php';
		
		$options = array();
		$options['redirect'] = 'index.php?option=com_rsseo&view=backup';
		
		$package = new RSPackage($options);
		$package->restore();
		return $package->displayProgressBar();
	}
	
	protected function cleanup() {
		if ($folder = Factory::getApplication()->input->getString('delfolder')) {
			$folder = base64_decode($folder);
			if (is_dir(Path::clean($folder)))
				Folder::delete($folder);
		}
	}
}