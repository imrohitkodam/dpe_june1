<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;

class rsseoViewStatistics extends HtmlView
{	
	public function display($tpl = null) {
		$data			= array();
		$this->config	= rsseoHelper::getConfig();
		$this->items	= $this->get('Visitors');
		$this->total	= $this->get('VisitorsTotal');
		$data['html']	= $this->loadTemplate($tpl);
		$data['total']	= $this->total;
		
		header('Content-Type: application/json');
		
		echo json_encode($data);
		die();
	}
}