<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license     GNU General Public License version 2 or later; see LICENSE
*/

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;

FormHelper::loadFieldClass('list');

JLoader::registerAlias('JFormFieldList', '\\Joomla\\CMS\\Form\\Field\\ListField');

class JFormFieldGsites extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $type = 'Gsites';
	
	protected function getOptions() {
		require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/gapi.php';
		
		try {
			$gapi = rsseoGoogleAPI::getInstance('gkeywords');
			
			return $gapi->getSites(true);
		} catch (Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
	}
}