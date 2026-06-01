<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license     GNU General Public License version 2 or later; see LICENSE
*/

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

class JFormFieldRsscripts extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $type = 'Rsscripts';

	public function __construct() {
		$js = array();
		
		$js[] = "function rsseo_sitemap() {\n";
		$js[] = "\t document.getElementById('sitemapToken').innerHTML = document.getElementById('jform_sitemap_cron_security').value;\n";
		$js[] = "\t document.getElementById('siteAddress').innerHTML = '".addslashes(Uri::root())."';\n";
		$js[] = "}\n";
		$js[] = "\n";
		$js[] = "document.addEventListener('DOMContentLoaded', function() { rsseo_sitemap(); });";
		
		
		Factory::getDocument()->addScriptDeclaration(implode('',$js));
	}
	
	protected function getInput() {}
}