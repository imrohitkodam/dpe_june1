<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Session\Session;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);
JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);

/**
 * The sla activity controller
 *
 * @since  1.0.0
 */
class SlaControllerSlatools extends FormController
{
	/**
	 * Function to get the tools for SLA
	 *
	 * @return  object  object
	 */
	public function getSlaTools()
	{
		if (!Session::checkToken('get'))
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
		}

		$app = Factory::getApplication();

		$slaId    = $app->input->get('slaId', 0, 'INT');

		if (empty($slaId))
		{
			return;
		}
		
		$slaModel = SlaFactory::model('Sla', array('ignore_request' => true));

		$html = $slaModel->getSlaToolsHtml($slaId);
		
		echo new JsonResponse($html);
		$app->close();

	}
}
