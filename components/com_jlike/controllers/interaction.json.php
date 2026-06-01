<?php
/**
 * @package     JLike
 * @subpackage  COM_JLIKE
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Plugin\PluginHelper;


/**
 * Interaction controller class
 *
 * @since  1.0
 */
class JlikeControllerInteraction extends FormController
{
	/**
	 * Method to save posted item data and update the interactions data.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	public function Save($key = null, $urlVar = null)
	{
		$app   = Factory::getApplication();

		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN_NOTICE'), true);
			$app->close();
		}

		$todoId = $app->input->getInt('todo_id', 0);
		$type = $app->input->getString('type', '');
		$currentDate = Factory::getDate()->toSql();

		$data = array();
		$data['todo_id'] = $todoId;

		$model      = $this->getModel('interaction');

		/** @scrutinizer ignore-call */
		$item = $model->getItem($todoId);
		$message = '';

		switch ($type)
		{
			case 'read':
				// Not allowed to revert
				$data['read'] = 1;
				$data['read_date'] = $currentDate;
				break;
			case 'used':
				$used = $app->input->getString('used', 'false');
				$data['used'] = $used == 'true'? 1 : 0;
				$data['description'] = $app->input->getString('desc', '');

				if ($item->used_date == '0000-00-00 00:00:00')
				{
					$data['used_date'] = $currentDate;
				}
				else
				{
					$data['used_modified_date'] = $currentDate;
				}

				$message = Text::_('COM_JLIKE_INTERACTION_SAVE_SUCCESS');
				break;

			default:
				echo new JsonResponse(null, Text::_('COM_JLIKE_INTERACTION_SAVE_INVALID_REQUEST'), true);
				$app->close();
		}

		$validated = $model->validate(true, $data);

		if ($validated && $model->save($data))
		{
			    // DPE - Hack - Start - auto update todo of document type
				$data['type'] = $type;

				// onAfterInteractionSave called
				PluginHelper::importPlugin('system');
				Factory::getApplication()->triggerEvent('onAfterInteractionSave',array($data));

			echo new JsonResponse($model->getState('interaction.id'), $message);
			$app->close();
		}

		echo new JsonResponse(null, $model->getError(), true);
		$app->close();
	}
}
