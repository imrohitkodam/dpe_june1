<?php
/**
 * @package     JLike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;

use Joomla\Utilities\ArrayHelper;
//DPE HACK
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use TJQueue\Admin\TJQueueProduce;


if (ComponentHelper::getComponent('com_tjqueue', true)->enabled)
{
	jimport('tjqueueproduce', JPATH_ADMINISTRATOR . '/components/com_tjqueue/libraries');
}


//HACK END

JLoader::import('components.com_jlike.includes.jlike', JPATH_SITE);
JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);


/**
 * Recommendation controller class.
 *
 * @since  1.6
 */
class JlikeControllerRecommendation extends FormController
{
	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->view_list = 'recommendations';
		parent::__construct();
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since   3.0.0
	 */
	public function &getModel($name = 'Recommendation', $prefix = 'JlikeModel', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}

	protected function setError($msg) {
		$this->errors[] = $msg;
	}

	/**
	 * Method to save.  DPE Hack in save function parameters are manadatory  can go in core.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   3.0.0
	 */
	public function save($key = null, $urlVar = null)
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$post  = $input->post;

		// Get the input
		$userIds = $post->get('uid', array(), 'array');
		$user = Factory::getUser();
		$data = array();

		if (!is_array($userIds) || count($userIds) < 1)
		{
			$app->enqueueMessage(Text::_('COM_JLIKE_NO_ITEM_SELECTED'), 'error');

			return false;
		}

		// Sanitize the input
		$userIds = ArrayHelper::toInteger($userIds);

		$redirectUrl = $post->get('redirect_url', '', 'STR');

		$notify  = $post->get('notify', '', 'INT');
		$data['element'] = $post->get('client', '', 'STR');
		$data['url'] = $post->get('url', '', 'STR');
		$data['element_id'] = $post->get('element_id', '', 'INT');
		$data['title'] = $post->get('title', '', 'STR');
		$data['img'] = $post->get('img', '', 'STR');
		$data['assigned_by'] = $user->id;
		$data['type'] = $post->get('type', 'reco', 'STR');
		$data['start_date'] = $post->get('start_date', '', 'DATETIME');
		$data['due_date'] = $post->get('due_date', '', 'DATETIME');
		$data['status'] = $post->get('status', 'I', 'STR');
		$data['state'] = $post->get('state', '1', 'INT');
		$data['created_by'] = $post->get('created_by', '', 'INT');
		$data['sender_msg'] = $post->get('sender_msg', '', 'STR');
		$data['context'] = $post->get('context', '', 'STR');

		$error = true;
		$msg = Text::_('COM_JLIKE_TASK_ERROR');

		foreach ($userIds as $userId)
		{
			$data['assigned_to'] = $userId;

			// Get the model
			$model = $this->getModel();

			// Save the items.
			$result = $model->setTodo($data, $notify);

			if ($result == true)
			{
				$error = false;
				$msg = Text::_('COM_JLIKE_TASK_SUCCESS');
				$this->setMessage(Text::sprintf('COM_JLIKE_SAVE_SUCCESS', count($userIds)));
			}
			else
			{
				$this->setMessage(Text::_('COM_JLIKE_SAVE_FAILED'), 'error');
			}
		}

		if ($redirectUrl)
		{
			$this->setRedirect($redirectUrl);
		}
		else
		{
			echo new JsonResponse($data, $msg, $error);
			$app->close();
		}
	}
	
		// DPE hack to delete todo
	
	/**
	 * Method to delete.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function delete()
	{
		$app  = Factory::getApplication();
		$user = Factory::getUser();

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			$app->close();
		}

		// DPE hack to get the multiple id of selected value to delete can go in core
		$ids       = ($app->input->get('mcid', array(), 'array')) ? $app->input->get('mcid', array(), 'array') : 
					 $app->input->get('cid', array(), 'array'); //DPE hack

					 $contentId = $app->input->getInt('content_id');

					 if ($ids)
					 {
					 	
					 	$requestCompleteCount = 0;
						$totalRequestCount = 0;
				// DPE Hack to check RBACL
					 	foreach ($ids as $id)
					 	{
					 		$totalRequestCount++;

					 		$todoClusterXrefTable = Jlike::table('TodosClusterXref');
					 		$todoClusterXrefTable->load($id);

					 		if (!$user->authorise('core.manageall', 'com_cluster'))
					 		{
				// Check user has create permission for mentioned cluster
					 			if (!RBACL::check($user->id, 'com_cluster', 'core.deleteNotification', 'com_jlike', $todoClusterXrefTable->cluster_id))
					 			{
					 				if($app->input->get('mcid', array(), 'array'))
					 				{
					 					($totalRequestCount>0)?$totalRequestCount--:'';
					 					continue;
					 				}
					 				else
					 				{
					 					$this->setError(Text::_("JERROR_ALERTNOAUTHOR"));
					 					return false;
					 				}
					 				
					 			}
					 		}

					 		$model = $this->getModel();

				// DPE hack to restrcit the page relode if the delete operation is handled from action not from multiselect checkbox. can go in core
					 		if ($model->delete($id))
					 		{
								if(!$app->input->get('mcid', array(), 'array'))//DPE hack
								{

									$app->enqueueMessage(Text::_('COM_JLIKE_ITEM_DELETED_SUCCESSFULLY'), 'success');

									if (!empty($app->input->getString('tmpl', '')))
									{
										$app->redirect(Route::_('index.php?option=com_jlike&tmpl=component&view=recommendations&content_id=' . $contentId, false));
									}
									else
									{
										$app->redirect(Route::_('index.php?option=com_jlike&view=recommendations', false));
									}	
								}
								else
								{
									$requestCompleteCount++;
								}

							}
							else
							{
								$app->enqueueMessage($model->getError());
							}

					}
					

		if($app->input->get('mcid', array(), 'array') && ($requestCompleteCount >= $totalRequestCount))//DPE hack
		{
			if($requestCompleteCount == 0)
			{
				$message = array('success'=>true,'msg'=> Text::_('COM_JLIKE_NO_PERMISSON_TO_DELETE'));
			}
			else
			{
				$message = array('success'=>true,'msg'=> Text::sprintf('COM_JLIKE_MULTIPLE_ITEM_DELETED_SUCCESSFULLY',$requestCompleteCount));
			}
			
			echo new JsonResponse($message);
			$app->close();
		}
	}
}
	
	// DPE Hack to update status

	/**
	 * Method to Complete todo.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function updateTodoStatus()
	{
		$app       = Factory::getApplication();
		$todoIds    = ($app->input->get('mcid', array(), 'array'))?($app->input->get('mcid', array(), 'array')):$app->input->getInt('id');
		$contentId = $app->input->getInt('content_id');
		$status    = $app->input->get('status');
		$user      = Factory::getUser();


		if (empty($todoIds))
		{
			return false;
		}

		if (!is_array($todoIds))
		{
			$todoIds = [$todoIds];
		}

		$requestCompleteCount = 0;
		$totalRequestCount = 0;

		foreach($todoIds as $todoId)
		{
			$totalRequest++;

			$data           = array();
			$data['id']     = $todoId;

			if ($status === "C" || $status === "I")
			{
				$data['status'] = $status;
			}
			else
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

				return false;
			}

			// DPE hack for RBACL check
			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				// Get Todo Cluster Xref data
				$todoClusterXrefTable = Jlike::table('TodosClusterXref');
				$todoClusterXrefTable->load($todoId);

				$manageNotification    = RBACL::check($user->id, 'com_cluster', 'core.manageNotificationManager', 'com_jlike', $todoClusterXrefTable->cluster_id);
				$manageOwnNotification = RBACL::check($user->id, 'com_cluster', 'core.own.manageNotifications', 'com_jlike', $todoClusterXrefTable->cluster_id);

				// Get Todo  data
				$todoTable = Jlike::table('Recommendation');
				$todoTable->load($todoId);

				// If user only have manage own access then check user should complete his own record only
				if (!$manageNotification && ($manageOwnNotification && $todoTable->assigned_to != $user->id))
				{
					$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

					return false;
				}

				if (!$manageNotification && !$manageOwnNotification)
				{
					$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

					return false;
				}
			}

			$recommendationFormModel = Jlike::model('Recommendationform', array('ignore_request' => true));
			$result                  = $recommendationFormModel->save($data);

			if ($result && !$app->input->get('mcid', array(), 'array'))
			{
				if ($data['status'] === "C")
				{
					$app->enqueueMessage(Text::_('COM_JLIKE_ITEM_SAVED_SUCCESSFULLY'), 'success');
				}
				elseif ($data['status'] === "I")
				{
					$app->enqueueMessage(Text::_('COM_JLIKE_MARKED_INCOMPLETE_SUCCESSFULLY'), 'success');
				}

				if (!empty($app->input->getString('tmpl', '')))
				{
					$app->redirect(Route::_('index.php?option=com_jlike&tmpl=component&view=recommendations&content_id=' . $contentId, false));
				}
				elseif($app->input->getString('view') === 'recommendationform')
				{
					$app->redirect(Route::_('index.php?option=com_jlike&view=recommendationform&id=' . $todoId, false));
				}
				else
				{
					$app->redirect(Route::_('index.php?option=com_jlike&view=recommendations', false));
				}
			}
			elseif($result && $app->input->get('mcid', array(), 'array')) //DPE hack
			{
				$requestCompleteCount++;
			}
			else
			{
				$app->enqueueMessage($recommendationFormModel->getError());
			}	
		}

		if(($result && $app->input->get('mcid', array(), 'array')) && ($requestCompleteCount >= $totalRequestCount))//DPE hack  can go in core
		{	

			if ($data['status'] === "C")
			{
				$message = array('success'=>true,'msg'=> Text::sprintf('COM_JLIKE_MULTIPLE_ITEM_SAVED_SUCCESSFULLY',$requestCompleteCount));
				
			}
			elseif ($data['status'] === "I")
			{
				$message = array('success'=>true,'msg'=> Text::sprintf('COM_JLIKE_MULTIPLE_MARKED_INCOMPLETE_SUCCESSFULLY',$requestCompleteCount));
				
			}

			echo new JsonResponse($message);
			$app->close();
		}
	}


	/**
	 * Method to push data in queue.
	 *
	 * @param   array  $data  record data
	 *
	 * @return  array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function addToQueue()
	{
		$app       = Factory::getApplication();
		$todoIds    = ($app->input->get('mcid', array(), 'array'))?($app->input->get('mcid', array(), 'array')):$app->input->getInt('id');
		$status    = $app->input->get('status');

		if (empty($todoIds) && ($status != ' resend'))
		{
			return false;
		}

		if (!is_array($todoIds))
		{
			$todoIds = [$todoIds];
		}

		$return = array();
		$data = array();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jlike/tables');

		foreach($todoIds as $key => $todoId)
		{
			$tododDataTable = Table::getInstance('Todos', 'JlikeTable');
		    $tododDataTable->load(array('id' => $todoId));
		    
		    if($tododDataTable->status == 'C') 
		    {
		    	$return[$key]['id'] = $todoId;
				$return[$key]['success'] = 0;
				$return[$key]['message'] = 'complete';

		    	continue;
		    }

			$data['todoId'] = $todoId;
			$messageBody = (object) $data;


			try
			{
				$TJQueueProduce = new TJQueueProduce;

				// Set message body
				$TJQueueProduce->message->setBody(json_encode($messageBody));

				// @Params client, value
				$TJQueueProduce->message->setProperty('client', 'jlike.resendtodos');
				$TJQueueProduce->produce();
			}
			catch (Exception $e)
			{
				$return[$key]['success'] = 0;
				$return[$key]['id'] = $todoId;
				$return[$key]['message'] = $e->getMessage();

			}

			$return[$key]['id'] = $todoId;
			$return[$key]['success'] = 1;
			$return[$key]['message'] = 'Success';

		}
		
		echo new JsonResponse($return);
		$app->close();
	}
}
