<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class rsseoModelRobots extends BaseDatabaseModel
{
	public function getIsFile() {
		return file_exists(JPATH_SITE.'/robots.txt');
	}
	
	public function getIsWrittable() {
		return is_writable(JPATH_SITE.'/robots.txt');
	}
	
	public function getContents() {
		return file_exists(JPATH_SITE.'/robots.txt') ? file_get_contents(JPATH_SITE.'/robots.txt') : false;
	}
}