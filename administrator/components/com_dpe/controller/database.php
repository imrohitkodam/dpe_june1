<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Database controller class.
 *
 * @package  DPE
 * @since    __DEPLOY_VERSION__
 */
class DpeControllerDatabase extends BaseController
{
	/**
	 * Update database
	 *
	 * @return void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function fix()
	{
		$app   = Factory::getApplication();
		$model = $this->getModel('database');
		$model->fix();

		// Purge updates
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_joomlaupdate/models', 'JoomlaupdateModel');
		$updateModel = BaseDatabaseModel::getInstance('default', 'JoomlaupdateModel');
		$updateModel->purge();

		// Refresh versionable assets cache
		$app->flushAssets();

		// Add a message to the message queue
		$app->enqueueMessage(Text::_('COM_DPE_DATABASE_UPDATED'), 'success');
		$this->setRedirect(Route::_('index.php?option=com_dpe&view=cp', false));
	}
}
