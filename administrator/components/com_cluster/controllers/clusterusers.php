<?php
/**
 * @package    Cluster
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

/**
 * Cluster Users controller class.
 *
 * @since  1.0.0
 */
class ClusterControllerClusterUsers extends AdminController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   STRING  $name    model name
	 * @param   STRING  $prefix  model prefix
	 *
	 * @return  Joomla\CMS\MVC\Model\BaseDatabaseModel |boolean  Model object on success; otherwise false on failure.
	 *
	 * @since  1.0.0
	 */
	public function getModel($name = 'ClusterUser', $prefix = 'ClusterModel', $config = [])
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}
}
