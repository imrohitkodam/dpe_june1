<?php
/**
 * @package     Jlike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use TJQueue\Admin\TJQueueProduce;

/**
 * Recommendationform controller
 *
 * @since  __DEPLOY_VERSION__
 */
class JlikeControllerRecommendationForm extends FormController
{
	/**
	 * Function to save multiple records
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 *
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function save($key = null, $urlVar = null)
	{
		$app        = Factory::getApplication();
		$returnData = array();

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		$user = Factory::getUser();
		$data = $app->input->post->get('jform', array(), 'array');
	
		// DPE hack for sql injection

		for ($i = 0;$i< count($data['reminder']);$i++) 
		{
			$data['reminder']['reminder'.$i]['duration'] = (int) $data['reminder']['reminder'.$i]['duration'];
		}

		$model = $this->getModel();

		
		/* DPE hack
		 Below code is commented as curently we are not using this but in future it may required.
		 Call onbeforesave todo to create content for  course before save todo . can go in Core.
		$dispatcher = JDispatcher::getInstance();
		PluginHelper::importPlugin('system');
		$dispatcher->trigger('onbeforeTodoSave', array(&$data));
		*/

		// Validate the posted data.
		$form = $model->getForm($data, false);

		// If all users option selected then remove required from assigned to field
		if (($data['all_cluster_users'] || $data['course_status'] || $data['used_in_practice'] || $data['read_and_understood'] )
			&& !empty($data['contentId']))
		{
			$form->setFieldAttribute('assigned_to_users', 'required', 'false');
			$form->setFieldAttribute('assigned_to', 'required', 'false');
			$form->setFieldAttribute('course_status', 'required', 'false');
		}
		else if(($data['all_cluster_users'] || $data['course_status'] || $data['used_in_practice'] || $data['read_and_understood'] )
			&& empty($data['contentId']))
		{
			$form->setFieldAttribute('assigned_to_users', 'required', 'false');
			$form->setFieldAttribute('assigned_to', 'required', 'false');
			$form->setFieldAttribute('course_status', 'required', 'false');
		}

		if (($data['used_in_practice'] == 0 || $data['read_and_understood'] == 0 ) && !empty($data['contentId']))
		{
			$form->setFieldAttribute('assigned_to', 'required', 'false');
			$form->setFieldAttribute('assigned_to_users', 'required', 'false');
		}

		if ($data['id'])
		{
			$form->setFieldAttribute('assigned_to_users', 'required', 'false');
		}
		else
		{
			$form->setFieldAttribute('assigned_to', 'required', 'false');
		}

		if ($data['course_id'])
		{
			$form->setFieldAttribute('assigned_to', 'required', 'false');
			$form->setFieldAttribute('assigned_to_users', 'required', 'false');
		}

		if ($data['course_status'])
		{
			$form->setFieldAttribute('course_status', 'required', 'true');
		}

		if (!$data['course_id'])
		{
			$form->setFieldAttribute('course_status', 'required', 'false');
		}

		// DPE Hack for check RBACL

		if (ComponentHelper::getComponent('com_cluster', true)->enabled)
		{
			// Get com_subusers component status
			$subUserExist = ComponentHelper::getComponent('com_subusers', true)->enabled;

			// Check user have permission to edit record of assigned cluster
			if ($subUserExist && ($data['clusters']) && !$user->authorise('core.manageall', 'com_cluster'))
			{
				JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

				// Check user has permission for mentioned cluster
				if (!RBACL::check($user->id, 'com_cluster', 'core.manageNotificationManager', 'com_jlike', $data['clusters']))
				{
					$returnData['msg'] = Text::_('JERROR_ALERTNOAUTHOR');
				}
			}
		}

		$validData = $model->validate($form, $data);

		if ($validData == false)
		{
			$errors = $model->getErrors();

			if (!empty($errors))
			{
				$msg  = array();

				// Push up to three validation messages out to the user.
				for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
				{
					if ($errors[$i] instanceof Exception)
					{
						$msg[] = $errors[$i]->getMessage();
					}
					else
					{
						$msg[] = $errors[$i];
					}
				}

				$errormsg = implode("<br>", $msg);
				echo new JsonResponse(0, $errormsg, true);
			}
		}
		else
		{	
			//DPE Hack to add checklist id if present
			if($data['chceklist_id'])
			{
				$validData['checklistUrl'] = $data['chceklist_id'];
			}
			
			$validData['state']    = 1; 
			$validData['cc_users'] = implode(',', (array) $data['cc_users']);

			if (!$data['id'])
			{
				$validData['status']      = 'I';
				$validData['assigned_by'] = Factory::getUser()->id;
				$validData['context']     = "";
				$validData['type']        = "assign";
			}

			$contentData               = array();
			$contentData['element']    = $data['element'];
			$contentData['element_id'] = $data['element_id'];
			$contentData['url']        = $data['url'];
			$contentData['title']      = $data['content_title'];

			if (!$data['content_id'])
			{
				$contentFormModel        = Jlike::model('Contentform', array('ignore_request' => true));
				$validData['content_id'] = $contentFormModel->getContentID($contentData);
			}

			// DPE Hack to store URL in params
			if ($validData['currentLink'])
			{
				$urlData                      = array();
				$urlData['current_page_link'] = $data['current_page_link'];
				$validData['params']          = json_encode($urlData);
			}

			// If All option selected then get all users from cluster to assign notification

			if ($data['all_cluster_users'] == 1)
			{
				JLoader::import('components.com_cluster.models.clusterusers', JPATH_ADMINISTRATOR);
				$clusterObj = BaseDatabaseModel::getInstance('ClusterUsers', 'ClusterModel', array('ignore_request' => true));
				$clusterObj->setState('filter.block', 0);
				$clusterObj->setState('filter.cluster_id', $data['clusters']);
				$clusterObj->setState('list.group_by_user_id', 1);
				$clusterUsers = $clusterObj->getItems();

				if (empty($clusterUsers))
				{
					$returnData['msg'] = Text::_('COM_TJGOPHISH_GROUP_SAVE_FAILED_NO_USERS_IN_CLUSTER_ERROR');
				}

				$userIds = array_column($clusterUsers, 'user_id');
			}
			elseif ((!empty($data['read_and_understood']) || !empty($data['used_in_practice'])) && $data['course_status'] == '')
			{
				JLoader::import('components.com_jlike.models.extendedtodos', JPATH_ADMINISTRATOR);
				$read = BaseDatabaseModel::getInstance('ExtendedTodos', 'JLikeModel', array('ignore_request' => true));

				if ($data['read_and_understood'] == 1)
				{
					$read->setState('filter.read', 1);
					$read->setState('filter.contentId', $data['contentId']);
				}
				elseif($data['read_and_understood'] == 2)
				{
					$read->setState('filter.read', 0);
					$read->setState('filter.contentId', $data['contentId']);
				}
				else
				{
					$read->setState('filter.read', '');
					$read->setState('filter.contentId', $data['contentId']);
				}

				if ($data['used_in_practice'] == 1)
				{
					$read->setState('filter.used', 1);
					$read->setState('filter.contentId', $data['contentId']);
				}
				elseif($data['used_in_practice'] == 2)
				{
					$read->setState('filter.used', 0);
					$read->setState('filter.contentId', $data['contentId']);
				}
				else
				{
					$read->setState('filter.used', '');
					$read->setState('filter.contentId', $data['contentId']);
				}

				$readUsers = $read->getItems();

				if (empty($readUsers))
				{
				$returnData['msg'] = Text::_('COM_TJGOPHISH_GROUP_SAVE_FAILED_NO_USERS_IN_CLUSTER_ERROR');
				}

				$userIds = array_unique(array_column($readUsers, 'assigned_to'));
			}
			elseif ($data['course_status'] == 'I' || $data['course_status'] == 'C')
			{
				JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
					$dpeModelObj = JModelLegacy::getInstance('users', 'DpeModel');

					if (!empty($data['course_id']) && !empty($data['course_status']) && !empty($data['clusters']))
					{
					// Call to the method to get the id of assigned users to the course

					$courseTodoUserId = $dpeModelObj->getAgencyUserId($data['course_id'], $data['course_status'], $data['clusters']);
					}
					else
					{
					$returnData['msg'] = Text::_('PLG_CONTENT_JLIKE_MULTIAGENCY_FIELD_COURSE_STATUS_ERROR');
					}

					if (empty($courseTodoUserId))
					{
					$returnData['msg'] = Text::_('COM_TJGOPHISH_GROUP_SAVE_FAILED_NO_USERS_IN_CLUSTER_ERROR');
					}
					else
					{
						$userIds = array_column($courseTodoUserId, 'user_id');
					}
			}
			elseif ($data['assigned_to_users'])
			{
				$userIds = $data['assigned_to_users'];
			}

			if (empty($userIds))
			{
				$returnData['msg'] = Text::_('COM_JLIKE_NO_USERS_IN_CLUSTER_ERROR');
			}

			// Check for add bulk todos
			if ($userIds)
			{
				$userLimit = ComponentHelper::getParams('com_dpe')->get('tjqueue_todo_limit');

				// If user count is greater than 15 and tjqueue is enabled then processs using tjqueue
				if (count($userIds) > $userLimit && ComponentHelper::isEnabled('com_tjqueue'))
				{
					$remindersArray = $validData['reminder'];

					foreach($remindersArray as $subKey => $reminderArray)
					{
						if(!$reminderArray['duration'])
						{
							unset($remindersArray[$subKey]);
						}
					}

					if (empty($remindersArray))
					{
						unset($validData['reminder']);
					}
					else
					{
						$validData['reminder'] = $remindersArray;
					}

					$queueProducer = new TJQueueProduce;
					foreach ($userIds as $userId)
					{
						$validData['assigned_to'] = $userId;

						$response                 = $model->addToQueue($validData, $queueProducer);
						$msg                      = $response ? Text::_("COM_JLIKE_TODOS_ADDED_TO_QUEUE_SUCCESSFULLY") : Text::_("COM_JLIKE_TODOS_FAILED");
						$returnData['msg']        = $msg;
					}
				}
				else
				{
					foreach ($userIds as $userId)
					{
						$validData['assigned_to'] = $userId;

						$todoIds[] = $model->save($validData);
					}
				}
			}

			if ($todoIds)
			{
				$returnData['msg'] = Text::sprintf('COM_JLIKE_TOTAL_TODOS_ADDED', count($todoIds));
			}

			echo new JsonResponse($returnData);
			$app->close();
		}
	}
}
