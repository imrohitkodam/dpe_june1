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

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

jimport('joomla.application.component.view');

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsViewDashboard extends HtmlView
{
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since  1.0.0
	 */
	public function display($tpl = null)
	{
		$app = Factory::getApplication();
		$this->userid = Factory::getUser()->id;

		if (!$this->userid)
		{
			$msg = Text::_('COM_TJLMS_MESSAGE_LOGIN_FIRST');

			// Get current url.
			$current = Uri::getInstance()->toString();
			$url = base64_encode($current);
			$app->enqueueMessage($msg, 'notice');
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, false), 403);
		}

		// Get user specific dashboard blocks
		$comtjlmsHelper = new comtjlmsHelper;
		$input = $app->input;
		$model = $this->getModel();

		$this->dash_icons_path = Uri::root(true) . '/media/com_tjlms/images/default/icons/';

		$this->dashboardData = $model->getdashboardData();

		parent::display($tpl);
	}
}
