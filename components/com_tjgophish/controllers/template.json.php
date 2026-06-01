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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Http\Http;

/**
 * Template Controller
 *
 * @since  1.0.0
 */
class TjGoPhishControllerTemplate extends BaseController
{
	/**
	 * Method to get user list depending on the client chosen.
	 *
	 * @return   null
	 *
	 * @since    1.0.0
	 */
	public function getTemplate()
	{
		$app = Factory::getApplication();

		// Check for request forgeries.
		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$templateTitle = $app->input->get('ttitle', '', 'STRING');
		$template = array();

		// Check cluster selected or not
		if ($templateTitle)
		{
			$params = ComponentHelper::getParams('com_tjgophish');
			$goPhishApiEnd = $params->get('api_base_url');
			$goPhishApiKey = $params->get('api_key');

			$Http = new Http;
			$url = $goPhishApiEnd . 'api/templates/' . '?api_key=' . $goPhishApiKey;
			$response = $Http->get($url);
			$response = json_decode($response->body);

			foreach ($response as $templateObj)
			{
				if (trim($templateTitle) == trim($templateObj->name))
				{
					$template['subject'] = $templateObj->subject;
					$template['text'] = $templateObj->text;
					$template['html'] = $templateObj->html;

					break;
				}
			}
		}

		echo new JsonResponse($template);
		$app->close();
	}
}
