<?php
/**
 * @package     Plg_System_Tjlms
 * @subpackage  Plg_System_Tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Date\Date;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use TJQueue\Admin\TJQueueProduce;

$lang = Factory::getLanguage();

/**
 * Plugin to send Todo in a bulk.
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgSystemDpeAddtodo extends CMSPlugin
{
	/**
	 * Constructor - Function used as a contructor
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 *
	 * @retunr  class object
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function __construct($subject, $config)
	{   
		parent::__construct($subject, $config);
	}

	/**
	 * Get the content id from lessin id
	 *
	 * @param   Int  $lessonId  The lesson Id
	 * 
	 * @retunr array Content id
	 *
	 * @retun  Int 
	 * 
	 * @since  __DEPLOY_VERSION__
	 */

	public function onGetContentId($lessonId)
	{
			Table::addIncludePath(JPATH_SITE . '/components/com_jlike/tables');
			$table = Table::getInstance('Content', 'JlikeTable');			
			$table->load(array("element_id" => $lessonId, "element" => 'com_tjlms.lesson' ));

			return $table->id;
	}

	/**
	 * Get the Url from contentId
	 *
	 * @param   Int  $contentId  The contentId of users
	 * 
	 * @retun array User id
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getUrlbycontentId($contentId=null)
	{
		if (empty($contentId))
		{
			return false;
		}

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jlike/tables');
  		$JlikeContentTable = Table::getInstance('Content', 'JlikeTable');
    	$JlikeContentTable -> load(array('id' => $contentId));
    	$url = $JlikeContentTable->url;

		return array('url' => $url);
	}	

	/**
	 * Function to save multiple records
	 *
	 * @param   Array  $data  Todo data to save.
	 *
	 * @return  Json
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAfterTodoSave($data)
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
		BaseDatabaseModel::addIncludePath(JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_jlike' . DIRECTORY_SEPARATOR . 'models');

		// Get instance of model class, where class name will be fooModelBar
		$model = BaseDatabaseModel::getInstance('RecommendationForm', 'JlikeModel');

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


			// Call onbeforesave todo to create content for  course before save todo . can go in Core.
			PluginHelper::importPlugin('system');
			Factory::getApplication()->triggerEvent('onbeforeTodoSave', array(&$data));
		
		    // Add assigned users for the todo
			$assigned_to_users = $data['assigned_to_users'];
			unset($data['assigned_to_users']);
			unset($data['url']);
			$validData = $data;
			
			// add due date 
			if ($data['due_date'])
			{	
				$date = new Date($data['due_date']);
				$validData['due_date'] = $date->format(Text::_('DATE_FORMAT_FILTER_DATETIME'));
			}
			
			$validData['state']    = 1;
			$validData['cc_users'] = implode(',', (array) $data['cc_users']);

			// if id is not present for the todo then add below data for the todo
			if (!$data['id'])
			{
				$validData['status']      = 'I';
				$validData['assigned_by'] = Factory::getUser()->id;
				$validData['context']     = "";
				$validData['type']        = "assign";
				$validData['title']      = $data['title'];
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
			if ($validData['current_page_link'])
			{
				$urlData                      = array();
				$urlData['current_page_link'] = $data['current_page_link'];
				$validData['params']          = json_encode($urlData);
			}
			

			if ($assigned_to_users)
			{
				$userIds = $assigned_to_users;
			}

			if (empty($userIds))
			{
				$returnData['msg'] = Text::_('COM_JLIKE_NO_USERS_IN_CLUSTER_ERROR');
			}

			// Check for add bulk todos
			if ($userIds)
			{	
				// Queue limit checked
				$userLimit = ComponentHelper::getParams('com_dpe')->get('tjqueue_todo_limit');

				// If user count is greater than 15 and tjqueue is enabled then processs using tjqueue
				if (count($userIds) > $userLimit && ComponentHelper::isEnabled('com_tjqueue'))
				{
					$remindersArray = $validData['reminder'];

					foreach ($remindersArray as $subKey => $reminderArray)
					{
						if (!$reminderArray['duration'])
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
						if (!$data['clusters'])
						{
							JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

							$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
							$validData['clusters'] = $clusterUserModel->getUsersClusters($userId);
							$validData['clusters'] = $validData['clusters'][0]->cluster_id;
						}

						$validData['assigned_to'] = $userId;
						$response                 = $model->addToQueue($validData,$queueProducer);

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
