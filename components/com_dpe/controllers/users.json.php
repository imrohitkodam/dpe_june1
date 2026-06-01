<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();
use Joomla\CMS\Response\JsonResponse;

use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;


/**
 * Users list controller class.
 *
 * @since  __DEPLOY__VERSION__
 */
class DpeControllerUsers extends AdminController
{
	/**
	 * This function deassign the users from the document
	 *
	 * @return  string   html to build a assignment list view
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function deassign()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		$user = Factory::getUser();
		$data = $app->input->getArray();

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
			$isAllowedDeassign = RBACL::check($user->id, 'com_multiagency', 'core.deassign.lesson', 'com_tjlms', $data['cluster_id']);

			if (!$isAllowedDeassign)
			{
				echo new JsonResponse(null, Text::_("JERROR_ALERTNOAUTHOR"), true);
				$app->close();
			}
		}

		// Get cluster id from lesson_cluster_xref table
		$table = DPE::table('TjlmsClusterXref');
		$table->load(array('lesson_id' => $data['element_id']));

		// Check logged-in user associated with passed cluster_id
		JLoader::import("/components/com_cluster/libraries/cluster", JPATH_ADMINISTRATOR);

		// Check user is a member of cluster
		$cluster = ClusterCluster::getInstance($table->cluster_id);

		$userIds = $data['uid'];

		// If user is anonymous or not from the cluster then unset the value
		foreach ($userIds as $key => $value)
		{
			if (!$cluster->isMember($value))
			{
				unset($userIds[$key]);
			}
		}

		// Sanitize the input
		$userIds = ArrayHelper::toInteger($userIds);

		if (!is_array($userIds) || count($userIds) < 1)
		{
			echo  new JsonResponse(null, Text::_("COM_DPE_NO_ITEM_SELECTED"), true);
			$app->close();
		}

		$model = $this->getModel('users', '', array("ignore_request" => true));

		$result = $model->deassign($userIds, $data['element_id']);

		if ($result)
		{
			echo new JResponseJson($result, Text::plural('COM_DPE_USER_UNASSIGNED_SUCCESSFULLY', count($userIds)), false);
			$app->close();
		}
		else
		{
			echo new JResponseJson(null, Text::_('COM_DPE_DEASSIGN_FAILED'), true);
			$app->close();
		}
	}
	
	/**
	 * This function deassign the users from the jmail alert
	 *
	 * @return  Array  msg to 
	 *
	 * @since   __DEPLOY__VERSION__
	 */

	public function unsubJmailAlert()
	{
		$app = Factory::getApplication();
		$email  = $app->input->get('emailid','','STRING');

		if ($email)
		{
			$model = $this->getModel('users', '', array("ignore_request" => true));

			$userDetails = $model->getSubscribeduserDetails($email);

			foreach($userDetails as $userDetail)
			{
				if (($userDetail->id) && ($userDetail->user_id == 0))
				{
					$resultDatas = $model->unsubscribeGuestUser($userDetail->alert_id, $email);
				}
			}



			if($resultDatas == 'success')
			{
				$msg = Text::_('COM_JMAIL_ALERT_UNSUBCRIBE_SUCCESS');

			}elseif(empty($userDetails)){

				$msg = Text::_('COM_JMAIL_ALERT_EMAIL_ERROR_MSG_TITLE');
			}
			else
			{
				$msg = Text::_('COM_JMAIL_ALERT_CUSTOM_ERROR_MSG_TITLE');
			}

			$app->enqueueMessage($msg, 'success');

			echo  new JsonResponse(true, $msg, true);
			$app->close();
		}
	}

	/**
	 * Method to get agency user Job title list by multiple clusters
	 *
	 * @return json data
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getJobTitleByClusters()
	{
		// Check Joomla token
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();

		// Get agencyId array from AJAX
		$clusterIds = $app->input->get('clusterIds', [], 'ARRAY'); // expects array of IDs

		if (empty($clusterIds)) {
			echo new JsonResponse([], Text::_('COM_DPE_NO_CLUSTERS_PROVIDED'), true);
			$app->close();
		}

		// Load models
		BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/components/com_dpe/models');
		$schoolModel = BaseDatabaseModel::getInstance('School', 'DpeModel');

		$allJobTitles = [];

		// Loop through each cluster ID and merge job titles
		foreach ($clusterIds as $clusterId) {
			$clusterId = (int) $clusterId;
			if ($clusterId <= 0) continue;

			$jobTitles = $schoolModel->getJobTitlesByClusterId($clusterId);
			if (!empty($jobTitles)) {
				$allJobTitles = array_merge($allJobTitles, $jobTitles);
			}
		}

		// Remove duplicates (optional)
		$uniqueJobTitles = [];
		foreach ($allJobTitles as $jt) {
			$uniqueJobTitles[$jt->id] = $jt->value;
		}

		// Build HTML options
		$options = '<option value="">' . Text::_('COM_MULTIAGENCY_SELECT_TITLE_OPTION') . '</option>';
		foreach ($uniqueJobTitles as $id => $value) {
			$options .= '<option value="' . $id . '">' . $value . '</option>';
		}

		echo new JsonResponse($options);
		$app->close();
	}

    /**
     * Method to Fetch the ClusterIds and Client Ids through tags.
     *
     * @return  array Cluster Ids
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getClusterClientIdsByTags()
    {
        $app = Factory::getApplication();

        $tagIds = $app->input->getString('tag_ids', '');
    
        if(empty($tagIds))
        {
            echo new JsonResponse(null, Text::_('COM_DPE_NO_TAGS_IDS_RECEIVED'), true);
            $app->close();
        }
        // Convert to array
        $tagIdsArray = array_filter(array_map('intval', explode(',', $tagIds)));
    
        if (empty($tagIdsArray))
        {
            echo new JsonResponse(null, Text::_('COM_DPE_NO_TAGS_IDS_RECEIVED'), true);
            $app->close();
        }

        JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
        $dashboardModel = JModelLegacy::getInstance('Dashboard', 'DpeModel');
        $clusterIds = $dashboardModel->getClusterIdsByTags($tagIdsArray);
    
        $user     = Factory::getUser();
        $activeLicenceClusterIds = array();
        $activeLicenceClusterClientIds = array();

        if ($user->authorise('core.manageall', 'com_cluster')){

            foreach ($clusterIds as $cluster) {

                Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
				$clusterInstance = Table::getInstance('Clusters', 'ClusterTable');
				// Get cluster Id
				$clusterInstance->load(array('id' => $cluster));

				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
                $licenceTableInstance = Table::getInstance('licence', 'MultiagencyTable');
                $licenceTableInstance->load(['multiagency_id' => $clusterInstance->client_id,'state'=> 1]);
            
                if (!empty($licenceTableInstance->id)) {
                    // Found a valid active licence
                    $activeLicenceClusterIds[] = $cluster;
                    $activeLicenceClusterClientIds[] = $clusterInstance->client_id;
                }
            }
                // Return both arrays
				echo new JsonResponse([
					'success' => true,
					'activeLicenceClusterIds' => $activeLicenceClusterIds,
					'activeLicenceClusterClientIds' => $activeLicenceClusterClientIds
				]);
            	$app->close();
        }
        
    }

	/**
	 * This function Subscribe the users from the event page to jmail alert public 
	 *
	 * @return  Array  msg to 
	 *
	 * @since   __DEPLOY__VERSION__
	 */

	public function jmailAlertSubFromEventFooter()
	{
		$app = Factory::getApplication();
		$email  = $app->input->get('emailid','','STRING');

		$model = $this->getModel('users', '', array("ignore_request" => true));
		$resultDatas = $model->saveAlertPrefernceFromEvent($email);

			if($resultDatas == 'success')
			{
				$msg = trim(Text::_('COM_JMAIL_ALERT_SUBSCRIBE_SUCCESS'), "'");
			}elseif($resultDatas == 'subscribed')
			{
				$msg = trim(Text::_('COM_JMAILALERTS_SETTINGS_EMAIL_USED'), "'");
			}
			else
			{
				$msg = Text::_('COM_JMAIL_ALERT_CUSTOM_ERROR_MSG_TITLE');
			}

			$app->enqueueMessage($msg, 'success');

			echo  new JsonResponse(true, $msg, true);
			$app->close();
		
		}
	
		/**
	 * Exports users data based on applied filters and selected columns.
	 *
	 *
	 * @return  void
	 *
	 * @throws  Exception  When SLA-related validation fails in the model
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function export()
	{
		$app  = Factory::getApplication();
		$user = Factory::getUser();

		// RBAC / Guest check
		if ($user->guest) {
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			return;
		}

		$input   = $app->input;
		$filters = [];

		// Collect filters (support flat + array style)
		$filterInput = (array) $input->get('filter', [], 'array');

		$filters['search']         = $input->getString('filter_search', $filterInput['search'] ?? '');
		$filters['agencies']       = $filterInput['agencies'] ?? $input->get('agencies', '', 'string');
		$filters['role_id']        = $filterInput['role_id'] ?? $input->getInt('role_id');
		$filters['tags']           = $filterInput['tags'] ?? [];
		$filters['sla_filter']     = $input->get('sla_filter', 'all', 'string');
		$filters['export_columns'] = explode(',', urldecode($input->get('export_columns', '', 'string')));

		$model = $this->getModel('Users');

		try {
			$data = $model->getExportData($filters);
		} catch (Exception $e) {
			header('Content-Type: application/json');
			echo new JsonResponse(null, $e->getMessage(), true);
			$app->close();
		}

		if (empty($data)) {
			header('Content-Type: application/json');
			echo new JsonResponse(null, Text::_('COM_MULTIAGENCY_NO_DATA_FOUND_FOR_EXPORT'), true);
			$app->close();
		}

		$filename = 'users_export_' . date('Ymd_His') . '.xls';

		if (ob_get_level()) {
			ob_end_clean();
		}

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private', false);
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Transfer-Encoding: binary');

		echo '<!DOCTYPE html>';
		echo '<html><head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
		echo '</head><body>';
		echo '<table border="1">';

		// Table Head
		echo '<thead><tr>';
		echo '<th>' . Text::_('COM_MULTIAGENCY_USERS_NAME') . '</th>';
		echo '<th>' . Text::_('COM_MULTIAGENCY_USERS_EMAIL') . '</th>';
		echo '<th>' . Text::_('COM_MULTIAGENCY_ORGANISATION') . '</th>';

		foreach ($filters['export_columns'] as $col) {
			switch ($col) {
				case 'jobtitle':
					echo '<th>' . Text::_('COM_MULTIAGENCY_USERS_JOBTITLE') . '</th>';
					break;
				case 'role':
					echo '<th>' . Text::_('COM_MULTIAGENCY_USERS_ROLE') . '</th>';
					break;
				case 'dpelead':
					echo '<th>' . Text::_('COM_MULTIAGENCY_FORM_DPELEAD_LIST') . '</th>';
					break;
				case 'registerDate':
					echo '<th>' . Text::_('COM_MULTIAGENCY_CREATED_DATE') . '</th>';
					break;
			}
		}
		echo '</tr></thead>';

		// Table Body
		echo '<tbody>';
		foreach ($data as $row) {
			echo '<tr>';
			echo '<td>' . htmlspecialchars($row->name) . '</td>';
			echo '<td>' . htmlspecialchars($row->email) . '</td>';
			echo '<td>' . htmlspecialchars($row->title) . '</td>';

			foreach ($filters['export_columns'] as $col) {
				switch ($col) {
					case 'jobtitle':
						echo '<td>' . htmlspecialchars($row->jobtitle) . '</td>';
						break;
					case 'role':
						echo '<td>' . htmlspecialchars($row->role_title) . '</td>';
						break;
					case 'dpelead':
						echo '<td>' . ($row->dpelead == '1' ? 'Yes' : 'No') . '</td>';
						break;
					case 'registerDate':
						echo '<td>' . ($row->registerDate && $row->registerDate != '0000-00-00 00:00:00' ? Factory::getDate($row->registerDate)->format('d-m-Y') : '') . '</td>';
						break;
				}
			}
			echo '</tr>';
		}
		echo '</tbody>';

		echo '</table>';
		echo '</body></html>';

		$app->close();
	}

}
