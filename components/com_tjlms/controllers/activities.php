<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die;

require_once JPATH_COMPONENT . '/controller.php';

/**
 * Shipprofiles list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerActivities extends TjlmsController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    name
	 *
	 * @param   string  $prefix  prefix
	 *
	 * @return  object
	 *
	 * @since	1.6
	 */
	public function &getModel($name = 'Activities', $prefix = 'TjlmsModel')
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}
}
