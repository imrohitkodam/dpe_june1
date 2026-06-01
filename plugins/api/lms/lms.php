<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('_JEXEC') or die( 'Restricted access' );
use Joomla\CMS\Factory;

jimport('joomla.plugin.plugin');

/**
 * Plugin API Tjlms
 *
 * @since  1.0.0
 */
class PlgAPILms extends ApiPlugin
{
	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *
	 * @since   3.7.0
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config = array());

		ApiResource::addIncludePath(dirname(__FILE__) . '/lms');

		/*load language file for plugin frontend*/
		$lang = Factory::getLanguage();
		$lang->load('plg_api_lms', JPATH_ADMINISTRATOR,'',true);

		// Set the login resource to be public
		$this->setResourceAccess('courses', 'public', 'get');
		$this->setResourceAccess('activities', 'public', 'get');
		$this->setResourceAccess('course', 'public', 'get');
		$this->setResourceAccess('lesson', 'public', 'get');
	}
}
