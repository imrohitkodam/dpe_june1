<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Form\FormHelper;

require_once JPATH_COMPONENT . '/controller.php';
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\Log\Logger\FormattedtextLogger;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;


JLoader::import('components.com_jlike.includes.jlike', JPATH_SITE);



/**
 * Users list controller class.
 *
 * @since  1.6
 */
class DpeControllerUsers extends BaseController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional
	 * @param   array   $config  Configuration array for model. Optional
	 *
	 * @return object	The model
	 *
	 * @since	1.6
	 */
	public function &getModel($name = 'Users', $prefix = 'DpeModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Method to assignUser Assign Document to selected users
	 *
	 * @return  boolean  True on success
	 *
	 * @since  1.6
	 */
	public function assignUser()
	{
		$db = Factory::getDBO();
		$app = Factory::getApplication();
		$post  = $app->input->post;

		// Get the input
		$userIds = $post->get('uid', array(), 'array');

		// Sanitize the input
		$userIds = ArrayHelper::toInteger($userIds);
		$user = Factory::getUser();

		// Check user is loggedin or not
		if (!$user->id)
		{
			$uri = $app->input->server->get('REQUEST_URI', '', 'STRING');
			$url = base64_encode($uri);
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, false), Text::_('COM_DPE_LOGIN_MSG'));
		}

		// Get the model.
		$model = $this->getModel();

		// Validate the selected users exist in manager agencies
		$userIds = $model->getAgencyUserIds($userIds, $user);

		$data = array();
		$error = true;
		$msg = Text::_('COM_DPE_SAVE_FAILED');

		$redirectUrl = $post->get('redirect_url', '', 'STRING');
		$notify      = $post->get('notify', '', 'INT');
		$data['element'] = $post->get('client', '', 'STRING');
		$data['url'] = $post->get('url', '', 'STRING');
		$data['element_id'] = $post->get('element_id', '', 'INT');
		$data['title'] = $post->get('title', '', 'STRING');
		$data['img'] = $post->get('img', '', 'STRING');

		if (!is_array($userIds) || count($userIds) < 1)
		{
			if ($redirectUrl)
			{
				$this->setMessage(Text::_('COM_DPE_NO_ITEM_SELECTED'), 'error');
				$this->setRedirect(Route::_($redirectUrl, false));
			}
			else
			{
				$msg = Text::_('COM_DPE_NOT_ALLOCATED_USER_SELECTED');
				echo new JsonResponse($data, $msg, $error);
				$app->close();
			}

			return false;
		}

		// Load contentform model to get content id
		JLoader::import('contentform', JPATH_SITE . '/components/com_jlike/models');
		$contentId = JlikeModelContentForm::getContentID($data);

		$data['assigned_by'] = $user->id;
		$data['content_id']  = $contentId;
		$data['type']        = $post->get('type', 'reco', 'STRING');
		$data['start_date']  = $post->get('start_date', '', 'STRING');
		$data['due_date']    = $post->get('due_date', '', 'STRING');
		$data['created_date'] = $post->get('created_date', '', 'STRING');
		$data['status']      = $post->get('status', 'I', 'STRING');
		$data['state']       = $post->get('state', '1', 'INT');
		$data['created_by']  = $post->get('created_by', '', 'INT');
		$data['sender_msg']  = $post->get('sender_msg', '', 'STRING');
		$data['context']     = $post->get('context', '', 'STRING');
		$data['clusterId']   = $post->get('clusterId', '', 'INT');
		$data['params']      = ["current_page_link"=> $post->get('url', '', 'STRING')];
		$data['cc_users']    = ($post->get('cc_users', '', 'INT'))?$post->get('cc_users', '', 'INT'):'0';

		if (!empty($data['start_date']))
		{
			$stDate = new Date($data['start_date'], 'UTC');
			$data['start_date'] = $stDate->toSQL();
		}

		// Check due date exist or not
		if (!empty($data['due_date']))
		{
			$dueData = new Date($data['due_date'], 'UTC');
			$data['due_date'] = $dueData->toSQL();
		}
		else
		{
			echo new JsonResponse($data, $msg, $error);
			$app->close();

			return false;
		}

		// Due date must be grater than current date/ start date
		if ($data['due_date'] < $data['start_date'])
		{
			$msg = Text::_('COM_DPE_COMPLIANCE_ASSIGN_USER_DUE_DATE_VALIDATION');
			echo new JsonResponse($data, $msg, $error);
			$app->close();

			return false;
		}

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models');


		foreach ($userIds as $userId)
		{
			$data['assigned_to'] = $userId;

			// Get the model
			$jlikeModel = BaseDatabaseModel::getInstance('recommendation_child', 'DpeModel', array('ignore_request' => true));

			// Save the items.
			$result = $jlikeModel->setTodo($data, $notify);

			if ($result == true)
			{	
				$error = false;
				$msg = Text::sprintf('COM_DPE_SAVE_SUCCESS', count($userIds));
				$this->setMessage(Text::sprintf('COM_DPE_SAVE_SUCCESS', count($userIds)));
			}
			else
			{
				$this->setMessage(Text::_('COM_DPE_SAVE_FAILED'), 'error');
			}
		}

		if ($redirectUrl)
		{
			$this->setRedirect(Route::_($redirectUrl, false));
		}
		else
		{
			echo new JsonResponse($data, $msg, $error);
			$app->close();
		}
	}

	/**
	 * Get the user id and count from respected plugins
	 *
	 * @return object	users id 
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	public function getUserCount()
	{	
		$app = Factory::getApplication();

		// Report filters present in $data which will be used in plugin .
		$data  = $app->input->post->getArray();

		if (empty($data))
		{
			return false;
		}

		try
		{
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/plugins/tjreports/' . $data['reportToBuild']);
			$model    = BaseDatabaseModel::getInstance($data['reportToBuild'], 'TjreportsModel', (array) $config);
			$userData = $model->getUserDeatilsforAdtodo($data);
			

			foreach($userData as $user)
			{
				$userIds[] = $user['user_id'];
			}

			if ($userIds[0])
			{
				$userCount = ($userIds)?count($userIds):'0';
			}

			echo new JsonResponse( array('count'=>$userCount,'userIds'=>$userIds));
			$app->close();
		}
		catch (Exception $e)
		{	

			echo new JResponseJson(null, Text::_('PLG_CONTENT_JLIKE_MULTIAGENCY_FIELD_COURSE_STATUS_ERROR'), true);
			$app->close();
		}
	}

	/**
	 * Save the todo as per users
	 * 
	 * @return object	users id 
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	public function todoSave()
	{
		PluginHelper::importPlugin('system');

		$app = Factory::getApplication();

			// all the users data with todo form data is present in this $data variable and will used in addtodo plugin to add the todo.
		$data = $app->input->post->getArray();
		
		if(empty($data))
		{
			return false;
		}
		try
		{
			$userId = array_filter(Factory::getApplication()->triggerEvent('onAfterTodoSave',$data));

			echo new JsonResponse($data);
			$app->close();
		}
		catch (Exception $e)
		{
			echo new JResponseJson(null, Text::_('PLG_CONTENT_JLIKE_MULTIAGENCY_FIELD_COURSE_STATUS_ERROR'), true);
			$app->close();
		}	
	}

	public function getLogsReportData()
	{	

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$querySub = $db->getQuery(true);

		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
		$cluster            = JFormHelper::loadFieldType('cluster', false);
		$clusterLists       = $cluster->getOptionsExternally();

		foreach ($clusterLists as $clusterList)
		{
			$usersClusters[] = "'".$clusterList->value."'";
			$usersClustersid[] = $clusterList->value;
		}

		$clusterId = implode("," , $usersClusters);

		$querySub->select($db->quoteName('clusters.id') . ', MAX(' . $db->quoteName('tag.title') . ') AS title')
		->from($db->quoteName('#__tj_clusters', 'clusters'))
		->leftJoin($db->quoteName('#__contentitem_tag_map', 'tm') . ' ON ' . $db->quoteName('tm.content_item_id') . ' = ' . $db->quoteName('clusters.client_id'))
		->leftJoin($db->quoteName('#__tags', 'tag') . ' ON ' . $db->quoteName('tag.id') . ' = ' . $db->quoteName('tm.tag_id'))
		->where($db->quoteName('clusters.id') . ' IN (' . $clusterId . ')')
		->group($db->quoteName('clusters.id'))
		->order($db->quoteName('clusters.name'));

		$db->setQuery($querySub);

		$tagResult = $db->loadObjectList();
		$newItem = new stdClass();
		$newItem->value = '';
		$newItem->title = '';
		array_unshift($tagResult,$newItem);



		$query->select(array('
			COUNT(IF(a.created_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR), a.id, NULL)) AS `_12monthlogs`',
			'COUNT(IF(a.created_date >= DATE_SUB(CURDATE(), INTERVAL 3 YEAR), a.id, NULL)) AS `_36monthlogs`',
			'a.client',
			'clust.name',
			    //'tag.title',
			'clust.id as  clustid'
		));
		$query->from($db->quoteName('z467w_tj_ucm_data', 'a'));
		$query->join('LEFT', $db->quoteName('z467w_tj_clusters', 'clust') . ' ON ' . $db->quoteName('clust.id') . ' = ' . $db->quoteName('a.cluster_id'));
			//$query->join('LEFT', $db->quoteName('z467w_contentitem_tag_map', 'tm') . ' ON ' . $db->quoteName('tm.content_item_id') . ' = ' . $db->quoteName('clust.client_id'));
			//$query->join('LEFT', $db->quoteName('z467w_tags', 'tag') . ' ON ' . $db->quoteName('tag.id') . ' = ' . $db->quoteName('tm.tag_id'));

		$query->where($db->quoteName('a.client') . ' IN (' . $db->quote('com_tjucm.breachlog') . ', ' . $db->quote('com_tjucm.sarlog') . ')');
		$query->where($db->quoteName('a.type_id') . ' IN (1, 3)');
		$query->where($db->quoteName('a.cluster_id') . ' IN ('.$clusterId.')');

			// $query->where('(' . $db->quoteName('a.created_date') . ' >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) OR ' . $db->quoteName('a.created_date') . ' >= DATE_SUB(CURDATE(), INTERVAL 3 YEAR))');
		$query->group(array($db->quoteName('a.client'), $db->quoteName('clust.name')));
		$query->order('a.client ASC');
		$db->setQuery($query);
		$results = $db->loadObjectList();


		foreach ($clusterLists as $key => $clusternames) {


			foreach ($results as $key1 => $object) {

				if (($object->clustid === $usersClustersid[$key]) && ( $object->client === 'com_tjucm.breachlog')) 
				{

					$foundKey = $key1;
					break ;

				}
				else
				{
					$foundKey = '';
				}


				if (($object->clustid === $usersClustersid[$key]) && ( $object->client === 'com_tjucm.sarlog')) 
				{

					$sarKey= $key1;
					break ;

				}
				else
				{
					$sarKey = '';
				}

			}

			if ($foundKey >= 0 )
			{



				$breachlog12month = ($results[$foundKey]->client=='com_tjucm.breachlog')?$results[$foundKey]->_12monthlogs:'0';
				$breachlog36month = ($results[$foundKey]->client=='com_tjucm.breachlog')?$results[$foundKey]->_36monthlogs:'0' ;


				$alldata[$key] = array( 'schoolname' => $clusternames->text,'tag'=>$tagResult[$key]->title, 'Total_number_of_Data_Breaches logged_within_last_twelve_months'=>$breachlog12month, 'Total_number_of_Data_Breaches logged_within_last_36months'=>$breachlog36month);
			}
			else
			{
				$alldata[$key] = array('schoolname' => $clusternames->text, 'tag'=>'Null', 'Total_number_of_Data_Breaches logged_within_last_twelve_months'=>0, 'Total_number_of_Data_Breaches logged_within_last_36months'=>0,'Total_number_of_Data_Rights_Requests_logged_within_last_twelve_months'=>0,
					'Total_number_of_Data_Rights_Requests_logged_within_last_36_months'=>0);
			}

		}
		foreach ($clusterLists as $key3 => $clusternames) {
			foreach ($results as $key2 => $objectas) {
				
				if (($objectas->clustid === $usersClustersid[$key3]) && ( $objectas->client === 'com_tjucm.sarlog')) 
				{
					$sarKey= $key2;
					break ;
				}
				else
				{
					$sarKey = '';
				} 
			}
			if( $sarKey >= 0 ){

				$bsarlog12month = ($results[$sarKey]->client=='com_tjucm.sarlog')?$results[$sarKey]->_12monthlogs:'0';
				$sarlog36month = ($results[$sarKey]->client=='com_tjucm.sarlog')?$results[$sarKey]->_36monthlogs:'0' ;

				$sData[$key3] = array('schoolname' => $clusternames->text, 'tag'=>$tagResult[$key3]->title, 'Total_number_of_Data_Rights_Requests_logged_within_last_twelve_months'=>$bsarlog12month,
					'Total_number_of_Data_Rights_Requests_logged_within_last_36_months'=>$sarlog36month );
			}
		}

		foreach ($alldata as &$item1) {
			foreach ($sData as $item2) { 

				if ($item1['schoolname'] === $item2['schoolname']) {

					$item1['Total_number_of_Data_Rights_Requests_logged_within_last_twelve_months'] = $item2['Total_number_of_Data_Rights_Requests_logged_within_last_twelve_months'];
					$item1['Total_number_of_Data_Rights_Requests_logged_within_last_36_months'] = $item2['Total_number_of_Data_Rights_Requests_logged_within_last_36_months'];
				}
			}
		}

		// define output settings csv 
		header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=Report_" . JHtml::date('now', 'Y_m_d_h_i_s', 'Australia/Brisbane') . ".csv");  // create unique document name
        header("Pragma: no-cache");
        header("Expires: 0");

        // get db data
        $resultset = $alldata;
        $column_heads = array_map('ucwords', str_replace('_', ' ', array_keys($resultset[0])));  // pretty up the column headings

        // write output
        $fp = fopen("php://output", "w");
        fputcsv ($fp, $column_heads);
        foreach ($resultset as $row)
        {
        	fputcsv($fp, $row);
        }
        fclose($fp);

        // Close the application gracefully.
        Factory::getApplication()->close();	
    }

    /**
	 * Fetch the default set by ID
	 *
	 * @return array
	 */


    public function getDefaultSetdataById()
    {
    	$app = Factory::getApplication();
    	$id = $app->input->get('id');

    	$model = $this->getModel();
    	$result = $model->getDefaultsetById($id);


    	echo new JsonResponse($result);
    	$app->close();
    }

    /**
	 * Delete Default set.
	 *
	 * @return array
	 */

    public function deleteDefaultSet()
    {
    	// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
		
    	$app = Factory::getApplication();
    	$id = $app->input->get('id');

		$user  = Factory::getUser();

		if (!$user->authorise('core.manageall', 'com_cluster') )
			{
				$params                  = ComponentHelper::getParams('com_multiagency');
				$orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
				$isOrgAdmin 			  = in_array($orgAdminRoleId, $user->groups);

				if(!$isOrgAdmin)
				{
					$msg = Text::_('COM_DPE_DEFAULTSET_DELETE_MSG_FAILED');
			    	echo new JsonResponse($msg);
			    	$app->close();
				}
			}
			

    	Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
    	$onboardXref = Table::getInstance('OnboardXref', 'DpeTable');


    	if ($onboardXref->load(array('id'=>$id))) {

    		$onboardXref->state = '0';

    		if ($onboardXref->store()) {
    			$msg = Text::_('COM_DPE_DEFAULTSET_DELETE_MSG_SUCCESS');
    		} else {
    			$msg = Text::_('COM_DPE_DEFAULTSET_DELETE_MSG_FAILED');
    		}
    	} else {
    		echo "Record not found!";
    	}

    	echo new JsonResponse($msg);
    	$app->close();
    }


    /**
	 * Create Main Default set for the organisation.
	 *
	 * @return array
	 */

    public function makeMainDefaultSet()
    {
    	$app = Factory::getApplication();
    	$id = $app->input->get('defaultsetId');
    	$clusterId = $app->input->get('clusterId');

    	if (!$id || !$clusterId)
    	{
    		return false;
    	}

    	$model = $this->getModel();
    	$result = $model->setMainDefaultSet($id, $clusterId);

    	if ($result)
    	{
    		$msg = Text::_('COM_DPE_MAIN_DEFAULTSET_SUCESS');
    	}else{
    		$msg = Text::_('COM_DPE_MAIN_DEFAULTSET_FAIL');
    	}
    	echo new JsonResponse($msg);
    	$app->close();

    }

    /**
	 * Assign the todos as per start date of default set
	 *
	 * @return void
	 */
    public function assginedDefaultSetWithStartDate()
    {
    	$start_date = Factory::getDate()->format('Y-m-d');
    	$model = $this->getModel();
    	$result = $model->assginedDefaultSetWithStartDate($start_date);

    	$config = array(
    		'text_file' => 'defaultsetassign_success.log'
    	);

    	$logger  = new FormattedtextLogger($config);
    	$logText = $result;

    	$entry = new LogEntry($logText, Log::INFO);

    	$logger->addEntry($entry);
    }

    /**
	 * Use to save the Todo as template
	 *
	 * @return void
	 */

    public function saveTemplate()
    {
    	$app = Factory::getApplication();
    	$todo_completion = $app->input->get('todocompletiondayVal','', 'INT');
    	$todo_description = $app->input->get('tododescriptionVal','', 'STRING');
		$todo_title = $app->input->get('todoTitleVal','', 'STRING');
    	$todo_reminder = $app->input->get('reminderday');
    	$clusterId = $app->input->get('clusterId');

    	if (!$clusterId || !$todo_title)
    	{
    		return false;
    	}

    	Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
    	$onboardTodoTemplate = Table::getInstance('OnbaordTodoTemplate', 'DpeTable');

    	// if ($onboardXref->load(array('title'=>$id,'cluster_id' = $clusterId))) {
    		  $onboardTodoTemplate->cluster_id = $clusterId;
    		  $onboardTodoTemplate->todo_title = $todo_title;
    		  $onboardTodoTemplate->todo_description = $todo_description ;
    		  $onboardTodoTemplate->todo_completion = (int)$todo_completion;
    		  $onboardTodoTemplate->todo_reminder = (int)$todo_reminder;
    		  
    		if ($onboardTodoTemplate->store()) {

    			$msg = Text::_('COM_DPE_JOBTITLLE_TODO_TEMPLATE');
    		} else {
    			$msg = Text::_('COM_DPE_TODO_TEMPLATE_SAVE_UNSUCCESFULL!');
    		}

    	echo new JsonResponse($msg);
    	$app->close();

    }

    public function sendOtpToUser()
    {
    	$app = Factory::getApplication();
		$username = urldecode($app->input->get('username', '', 'RAW'));
    	$password = urldecode($app->input->get('password', '', 'RAW'));
	
    	if (!$username || !$password)
    	{

     		$msg['type']='warning';
			$msg['msg']= Text::_('COM_DPE_USERNAME_PASSWORD_IS_MANDAORY');
			$msg['action']= false;

			echo new JsonResponse($result);
    		$app->close();

    	}

    	$model = $this->getModel();
    	$result = $model->sendOtpToUser($username, $password);

    	echo new JsonResponse($result);
    	$app->close();

    }

    /**
	 * Use to check the otp is correct or not.
	 *
	 * @return void
	 */

    public function checkOtp()
    {
    	$app = Factory::getApplication();

        // CSRF check
        if (!Session::checkToken('post')) {
            echo new JsonResponse([
                'msg' => Text::_('JINVALID_TOKEN'),
                'success' => false,
                'type' => 'error'
            ]);
            $app->close();
        }

        // Get raw POST data
        $username = trim(urldecode($app->input->post->get('username', '', 'RAW')));
        $password = trim(urldecode($app->input->post->get('password', '', 'RAW')));
        $otp      = $app->input->post->getInt('otp');

        // Basic validation
        if (!$username || !$password || !$otp) {
            echo new JsonResponse([
                'msg' => Text::_('COM_DPE_OTP_MISMATCH'),
                'success' => false,
                'type' => 'error'
            ]);
            $app->close();
        }

        // Strict OTP format validation (numeric, 4-8 digits)
        if (!preg_match('/^\d{4,8}$/', $otp)) {
            echo new JsonResponse([
                'msg' => Text::_('COM_DPE_INVALID_OTP_FORMAT'),
                'success' => false,
                'type' => 'error'
            ]);
            $app->close();
        }

        // Optional: implement rate limiting here
        // Example: check DB table for failed OTP attempts and block if exceeded

        // Call the model to verify OTP
        $model = $this->getModel();
        $result = $model->checkOtp($username, $password, $otp);

        // Sanitize message before sending (defense-in-depth)
        if (isset($result['msg'])) {
            $result['msg'] = htmlspecialchars($result['msg'], ENT_QUOTES, 'UTF-8');
        }

        // Send JSON response
        echo new JsonResponse($result);
        $app->close();


    }

     /**
	 * Use to check the user is DPE admin superuser or not.
	 *
	 * @return void
	 */

    public function checkUserValidation()
    {
    	$app      = Factory::getApplication();
		$username = urldecode($app->input->get('username', '', 'RAW'));

		$model = $this->getModel();
    	$result = $model->checkUserValidation($username);

    	echo new JsonResponse($result);
    	$app->close();

    }
	/**
	 * Method to delete UCM bulk files older than 7 days.
	 *
	 * This method calculates the date 7 days prior to today,
	 * deletes UCM bulk files older than that date via the model,
	 * and logs the operation result into a specified log file.
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
    public function deleteUcmBulkFile()
    {

		$sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
		JLoader::register('DpeModelUcmbulkdownload', JPATH_SITE . '/components/com_dpe/models/rsticketspro.php');
		$model = BaseDatabaseModel::getInstance('Ucmbulkdownload', 'DpeModel', array('ignore_request' => true));
    	$result = $model->deleteUcmBulkFile($sevenDaysAgo);

    	$config = array(
    		'text_file' => 'removeUcm_file_success.log'
    	);

    	$logger  = new FormattedtextLogger($config);
    	$logText = $result;

    	$entry = new LogEntry($logText, Log::INFO);

    	$logger->addEntry($entry);
    }

	/**
	 * Store ordering preferences (column and direction) into Joomla session.
	 *
	 * Called via AJAX when the user sorts the user list. This persists the
	 * sort state across page reloads using the session.
	 *
	 * @return void
	 *
	 * @since  5.1
	 */
	public function setOrderFilter()
	{
		$app   = Factory::getApplication();
		$input = $app->input;

		// Fetch ordering inputs with safe filtering
		$order = $input->getString('filter_order', 'a.name');
		$dir   = $input->getCmd('filter_order_Dir', 'asc');

		// Store values in user session
		$app->setUserState('com_multiagency.users.filter_order', $order);
		$app->setUserState('com_multiagency.users.filter_order_Dir', $dir);

		// Return response
		echo new JsonResponse([
			'status' => 'success',
			'order'  => $order,
			'dir'    => $dir,
		]);

		$app->close();
	}

	/** 
	 * Method to delete UCM bulk files older than 7 days.
	 *
	 * This method calculates the date 7 days prior to today,
	 * deletes UCM bulk files older than that date via the model,
	 * and logs the operation result into a specified log file.
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
    public function getTicketsDataByTags()
    {

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Select fields
		$query->select([
		    'DISTINCT a.*',
		    'st.name AS status',
		    'rsxref.emails',
		    'logx.logId','c.name AS customer',
		    'cluster.name AS agencyTitle',
		    'rsth.user_id AS ClosedBy'
		]);

		// From main table
		$query->from($db->qn('#__rsticketspro_tickets', 'a'));

		// Joins
		$query->leftJoin($db->qn('#__users', 'c') . ' ON ' . $db->qn('a.customer_id') . ' = ' . $db->qn('c.id'));
		$query->leftJoin($db->qn('#__users', 's') . ' ON ' . $db->qn('a.staff_id') . ' = ' . $db->qn('s.id'));
		$query->leftJoin($db->qn('#__rsticketspro_statuses', 'st') . ' ON ' . $db->qn('a.status_id') . ' = ' . $db->qn('st.id'));
		$query->leftJoin($db->qn('#__rsticketspro_priorities', 'pr') . ' ON ' . $db->qn('a.priority_id') . ' = ' . $db->qn('pr.id'));
		$query->leftJoin($db->qn('#__rsticket_integration_xref', 'rsxref') . ' ON ' . $db->qn('a.id') . ' = ' . $db->qn('rsxref.ticket_id'));
		$query->leftJoin($db->qn('#__tj_clusters', 'cluster') . ' ON ' . $db->qn('rsxref.agency_id') . ' = ' . $db->qn('cluster.id'));
		$query->leftJoin($db->qn('#__rsticketspro_ticket_notes', 'rsxnote') . ' ON ' . $db->qn('rsxnote.ticket_id') . ' = ' . $db->qn('rsxref.ticket_id'));
		$query->leftJoin($db->qn('#__contentitem_tag_map', 'tagsMap') . ' ON ' . $db->qn('tagsMap.content_item_id') . ' = ' . $db->qn('cluster.client_id'));
		$query->leftJoin($db->qn('#__ticket_log_xref', 'logx') . ' ON ' . $db->qn('a.id') . ' = ' . $db->qn('logx.ticketId'));
		$query->leftJoin($db->qn('#__rsticketspro_ticket_history', 'rsth') . ' ON ' . $db->qn('a.id') . ' = ' . $db->qn('rsth.ticket_id') . ' AND ' . $db->qn('rsth.type') . ' = ' . $db->quote('close'));

		// Where conditions
		$query->where($db->qn('tagsMap.tag_id') . ' IN (311)');
		$query->where($db->qn('tagsMap.type_alias') . ' = ' . $db->quote('com_multiagency.multiagency'));

		// Order by
		$query->order($db->qn('a.id') . ' DESC');

		// Set the query and get results
		$db->setQuery($query);

		try {
		    $results = $db->loadObjectList();	

		// Set headers to download as CSV
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="tickets_report.csv"');

		// Open output stream
		$output = fopen('php://output', 'w');

		// CSV Header Row
		fputcsv($output, [
		    'Ticket Number',
		    'Summary',
		    'Date Entered/Created',
		    'Date Closed',
		    'Site Name',
		    'Contact Name',
		    'Status Description',
		    'Service Sub Type'
		]);

		// Loop and add rows
		foreach ($results as $row) {
			
			if ($row->logId)
			{
				$db = Factory::getDbo();

				$query = $db->getQuery(true)
				    ->select($db->quoteName('types.title'))
				    ->from($db->quoteName('#__tj_ucm_data', 'data'))
				    ->join(
				        'INNER',
				        $db->quoteName('#__tj_ucm_types', 'types') . ' ON ' . $db->quoteName('types.unique_identifier') . ' = ' . $db->quoteName('data.client')
				    )
				    ->where($db->quoteName('data.id') . ' = ' . (int) $row->logId);

				$db->setQuery($query);
				$title = $db->loadResult();

			}
		    fputcsv($output, [
		        $row->id ?? '',
		        $row->subject ?? '',
		        $row->date ?? '',
		        $row->closed ?? '',
		        $row->referer ?? '',
		        $row->customer ?? '',
		        $row->status ?? '',
		        ($row->logId)?$title:''
		    ]);
		}

		// Close output stream
		fclose($output);
		exit;

		//dataprotection.education/index.php?option=com_dpe&task=users.getTicketsDataByTags&tmpl=component
		} catch (Exception $e) {
		    echo "Query failed: " . $e->getMessage();
		}
		}
}

