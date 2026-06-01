<?php
/**
 * @package     TjGoPhish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;

/**
 * Groups Controller
 *
 * @since  1.0.0
 */
class TjGoPhishControllerGroups extends BaseController
{
	/**
	 * Method to get user list depending on the client chosen.
	 *
	 * @return   null
	 *
	 * @since    1.0.0
	 */
	public function getClusterGroups()
	{
		$app = Factory::getApplication();

		//Check for request forgeries.  DPE hack failed to session check need to check.
		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$clusterId = $app->input->getInt('cluster_id', 0);

		// Check cluster selected or not
		if ($clusterId)
		{
			JLoader::import('components.com_tjgophish.models.groups', JPATH_SITE);
			$groupsModel = BaseDatabaseModel::getInstance('Groups', 'TjGoPhishModel', array('ignore_request' => true));
			$groupsModel->setState('filter.cluster_id', $clusterId);
			$groups = $groupsModel->getItems();
		}

		$groupOptions = array();

		if (!empty($groups))
		{
			foreach ($groups as $group)
			{
				$groupOptions[] = HTMLHelper::_('select.option', $group->gophish_group_title, $group->gophish_group_title);
			}
		}

		echo new JsonResponse($groupOptions);
		$app->close();
	}
}
