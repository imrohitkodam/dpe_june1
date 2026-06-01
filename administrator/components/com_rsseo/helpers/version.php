<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

class RSSeoVersion {
	public $version  = '1.21.23';
	public $key		 = 'SEO56H8K3U';
	// Unused
	public $revision = null;
	
	// Get version
	public function __toString() {
		return $this->version;
	}
	
	// Legacy, keep revision
	public function __construct() {
		list($j, $revision, $bugfix) = explode('.', $this->version);
		$this->revision = $revision;
	}
}

$version = new RSSeoVersion();
define('RSSEO_REVISION', $version->revision);