<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController as JAdminController;

/**
 * File download stats list controller class.
 *
 * @since  1.4.0
 */
class TjlmsControllerFileDownloadStats extends JAdminController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   STRING  $name    model name
	 * @param   STRING  $prefix  model prefix
	 * @param   ARRAY   $config  configuration
	 *
	 * @return  JModelLegacy|boolean
	 *
	 * @since  1.4.0
	 */
	public function getModel($name = 'FileDownloadStats', $prefix = 'TjlmsModel', $config = Array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}
}
