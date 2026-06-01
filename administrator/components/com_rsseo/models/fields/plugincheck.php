<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license     GNU General Public License version 2 or later; see LICENSE
*/

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class JFormFieldPlugincheck extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $type = 'Plugincheck';
	
	public function __construct() {
		Factory::getDocument()->addScriptDeclaration("
			document.addEventListener('DOMContentLoaded', function() {
				document.getElementById('component-form').setAttribute('enctype', 'multipart/form-data');
			});
		");
	}
	
	protected function getLabel() {
		return '';
	}
	
	protected function getInput() {
		if (!PluginHelper::isEnabled('system', 'rsseo')) {
			return Text::_('COM_RSSEO_CONFIGURATION_PLEASE_ENABLE_RSSEO_PLUGIN');
		}
	}
}