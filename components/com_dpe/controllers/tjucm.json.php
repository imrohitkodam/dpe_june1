<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined("_JEXEC") or die();
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use TJQueue\Admin\TJQueueProduce;

jimport("mpdf.mpdf");
JLoader::register(
    "TjucmAccess",
    JPATH_SITE . "/components/com_tjucm/includes/access.php"
);

/**
 * Users list controller class.
 *
 * @since  __DEPLOY__VERSION__
 */
class DpeControllerTjucm extends AdminController
{
    /**
     * Function UpdateUCMParent used to update parent id of ucm data
     *
     * @return  null
     *
     * @since  1.2.3
     */
    public function UpdateUCMParent()
    {
        $app = Factory::getApplication();

        if (!Session::checkToken()) {
            $app->enqueueMessage(Text::_("JINVALID_TOKEN"), "error");
            echo new JsonResponse(null, null, true);
            $app->close();
        }

        $UCMID = $app->input->post->get("UCMID", 0, "INT");
        $UpdatedParentID = $app->input->post->get("UpdatedParentID", 0, "INT");

        Table::addIncludePath(
            JPATH_ADMINISTRATOR . "/components/com_tjfields/tables"
        );
        $fieldTable = Table::getInstance("field", "TjfieldsTable");
        $fieldTable->load([
            "name" => "com_tjucm_ropdataflow_parentstepindataflow",
        ]);

        if (!property_exists($fieldTable, "id")) {
            $msg = Text::_("The parent field is not available.");
            echo new JsonResponse(0, $msg);
            $app->close();
        }

        $fieldsValueTable = Table::getInstance("fieldsvalue", "TjfieldsTable");
        $fieldsValueTable->load([
            "content_id" => $UCMID,
            "field_id" => $fieldTable->id,
        ]);

        // Build object to save record into xref table
        if (!property_exists($fieldsValueTable, "id")) {
            $fieldsValueTable->reset();
        }

        $fieldsValueTable->id = $fieldsValueTable->id
        ? $fieldsValueTable->id
        : 0;
        $fieldsValueTable->value = $UpdatedParentID;
        $fieldsValueTable->content_id = $UCMID;
        $fieldsValueTable->field_id = $fieldTable->id;
        $fieldsValueTable->user_id = Factory::getUser()->id;
        $fieldsValueTable->client = "com_tjucm.ropdataflow";

        $returnData = [];

        // Save into licence xref
        if ($fieldsValueTable->save($fieldsValueTable->getProperties())) {
            $returnData["msg"] = Text::_(
                "COM_TJUCM_DATA_FLOW_RELATION_SUCCESS_MSG"
            );

            echo new JsonResponse($returnData);
            $app->close();
        }
    }

    /**
     * Function getDynamicTree by UCM ID
     *
     * @return  null
     *
     * @since  1.2.3
     */
    public function getDynamicTree()
    {
        $app = Factory::getApplication();
        $returnData = [];
        $ucmId = $app->input->post->get("ucmId", 0, "INT");

        if (!Session::checkToken()) {
            $app->enqueueMessage(Text::_("JINVALID_TOKEN"), "error");
            echo new JsonResponse(null, null, true);
            $app->close();
        }

        if (!$ucmId) {
            $returnData["html"] = $app->enqueueMessage(
                Text::_("COM_TJUCM_DATA_FLOW_ERROR_MSG_TITLE"),
                "error"
            );
            echo new JsonResponse($returnData);
            $app->close();
        }

        JLoader::import("components.com_tjucm.models.itemform", JPATH_SITE);
        /* Get model instance here */
        $itemModel = BaseDatabaseModel::getInstance("itemForm", "TjucmModel", [
            "ignore_request" => true,
        ]);
        $itemModel->setState("item.id", $ucmId);
        $itemModel->setState("params", $app->getParams("com_tjucm"));

        // Get Details for UCM ID i.e Field set, UCM data, fields Details
        $itemData = $itemModel->getData($ucmId);

        $form_extra = $itemModel->getFormExtra([
            "clientComponent" => "com_tjucm",
            "client" => "com_tjucm.rop",
            "view" => "rop",
            "layout" => "edit",
            "content_id" => $ucmId,
        ]);

        // get Field Sets
        $xmlFileName = explode(".", $form_extra->getName());
        $formXml = simplexml_load_file(
            JPATH_SITE .
            "/administrator/components/com_tjucm/models/forms/" .
            $xmlFileName[1] .
            ".xml"
        );

        $count = 0;
        $xmlFieldSets = [];

        foreach ($formXml as $k => $xmlFieldSet) {
            $xmlFieldSets[$count] = $xmlFieldSet;
            $count++;
        }

        // Create Layout object
        $ropDataFlowDragDropLayout = new FileLayout(
            "detail.ropdataflowdragdrop",
            JPATH_ROOT . "/components/com_tjucm",
            ["component" => "com_tjucm", "client" => 0]
        );

        // Generate HTML string.
        $html = $ropDataFlowDragDropLayout->render([
            "xmlFormObject" => $xmlFieldSets,
            "formObject" => $form_extra,
            "itemData" => $itemData,
        ]);

        $returnData["html"] = $html;

        echo new JsonResponse($returnData);
        $app->close();
    }

    /**
     * Function tjucm reverselist by UCM ID
     *
     * @return  null
     *
     * @since  1.2.3
     */
    public function getReverseList()
    {
        $app = Factory::getApplication();
        $returnData = [];
        $clusterFieldValue = $app->input->post->get(
            "clusterFieldValue",
            0,
            "INT"
        );
        $recordId = $app->input->post->get("recordId", 0, "INT");
        $params = ComponentHelper::getParams("com_dpe");
        $reverseListClients = explode(
            ",",
            $params->get("coredataReverseUcmTypes")
        );

        if (!Session::checkToken()) {
            $app->enqueueMessage(Text::_("JINVALID_TOKEN"), "error");
            echo new JsonResponse(null, null, true);
            $app->close();
        }

        if (!$clusterFieldValue) {
            $returnData["html"] = "";
            echo new JsonResponse($returnData);
            $app->close();
        }

        // Get Cluster name
        Table::addIncludePath(
            JPATH_ADMINISTRATOR . "/components/com_cluster/tables"
        );
        $clusterTable = Table::getInstance("clusters", "ClusterTable");
        $clusterTable->load(["id" => $clusterFieldValue]);

        $UcmTypes = $reverseListClients;
        JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
        $tjUcmFrontendHelper = new TjucmHelpersTjucm();
        $html = "";

        foreach ($UcmTypes as $UcmType) {
            if ($UcmType == "com_tjucm.role") {
                continue;
            }

            // Get UCM Type name
            Table::addIncludePath(
                JPATH_ADMINISTRATOR . "/components/com_tjucm/tables"
            );
            $ucmTable = Table::getInstance("type", "TjucmTable");
            $ucmTable->load(["unique_identifier" => $UcmType]);

            // Get Process Addtion form Itemid
            $TypeItemId = $tjUcmFrontendHelper->getItemId(
                "index.php?option=com_tjucm&view=items&client=" . $UcmType
            );

            if ($UcmType == "com_tjucm.software") {
                $reverseListLink = Route::_(
                    "index.php?option=com_tjucm&view=items&Itemid=" .
                    $TypeItemId .
                    "&cluster_id=" .
                    $clusterFieldValue .
                    "&tmpl=component" .
                    "&softwareManagedby=" .
                    $recordId
                );
                $PopUpUrl =
                '"' .
                addslashes(Route::_($reverseListLink . "&reverselist=1")) .
                '"';
                $PopUpBtnText = Text::_("COM_TJUCM_RELATED_SOFT_BTN_TITLE");
            } elseif ($UcmType == "com_tjucm.ithardware") {
                $reverseListLink = Route::_(
                    "index.php?option=com_tjucm&view=items&Itemid=" .
                    $TypeItemId .
                    "&cluster_id=" .
                    $clusterFieldValue .
                    "&tmpl=component"
                );
                $PopUpUrl =
                '"' .
                addslashes(Route::_($reverseListLink . "&reverselist=1")) .
                '"';
                $PopUpBtnText = Text::_("COM_TJUCM_RELATED_HARD_BTN_TITLE");
            }

            $onclickText =
            "tjucm.itmes.openMasterlistPopups(" . $PopUpUrl . ", this)";
            $html .=
            "<div class='mb-20'>" .
            "<a href='javascript:void(0);' class='btn btn-primary' " .
            " onclick='" .
            $onclickText .
            "' >" .
            $PopUpBtnText .
            "</a>" .
            "</div>";
        }
        // Get UCM type details

        $returnData["html"] = $html;
        echo new JsonResponse($returnData);
        $app->close();
    }

    /**
     * Function get Cluster of UCM record
     *
     * @return  null
     *
     * @since  1.2.3
     */
    public function getClusterId()
    {
        $app = Factory::getApplication();
        $returnData = [];
        $recordId = $app->input->post->get("recordId", 0, "INT");

        if (!Session::checkToken()) {
            $app->enqueueMessage(Text::_("JINVALID_TOKEN"), "error");
            echo new JsonResponse(null, null, true);
            $app->close();
        }

        if (!$recordId) {
            $returnData["clusterId"] = "";
            echo new JsonResponse($returnData);
            $app->close();
        }

        // Get cluster ID from record ID
        Table::addIncludePath(
            JPATH_ADMINISTRATOR . "/components/com_tjucm/tables"
        );
        $ucmTable = Table::getInstance("item", "TjucmTable");
        $ucmTable->load(["id" => $recordId]);

        if (property_exists($ucmTable, "cluster_id")) {
            $clusterId = $ucmTable->cluster_id;
        }

        $returnData["clusterId"] = $clusterId;
        echo new JsonResponse($returnData);
        $app->close();
    }

    /**
     * Method to remove data from ROP Dataflow Subform
     *
     * @return void
     *
     * @throws Exception
     *
     * @since 1.6
     */
    public function ropSubformRemove()
    {
        $app = Factory::getApplication();

        if (!Session::checkToken()) {
            $app->enqueueMessage(Text::_("JINVALID_TOKEN"), "error");
            echo new JsonResponse(null, null, true);
            $app->close();
        }

        $ucmDataId = $app->input->post->get("ucmDataId", 0, "INT");
        $canDelete = 0;

        if (!$ucmDataId) {
            $app->enqueueMessage(Text::_("JERROR_ALERTNOAUTHOR"), "error");
            echo new JsonResponse(null, null, true);
            $app->close();
        }

        // Get Cluster ID of UCM record
        Table::addIncludePath(
            JPATH_ADMINISTRATOR . "/components/com_tjucm/tables"
        );
        $UCMDataTable = Table::getInstance("data", "TjucmTable");
        $UCMDataTable->load(["id" => (int) $ucmDataId]);

        // Get Parent UCM data details
        if ($UCMDataTable->parent_id) {
            // Get Cluster ID of UCM record
            Table::addIncludePath(
                JPATH_ADMINISTRATOR . "/components/com_tjucm/tables"
            );
            $UCMParentDataTable = Table::getInstance("data", "TjucmTable");
            $UCMParentDataTable->load(["id" => (int) $UCMDataTable->parent_id]);

            if (!$UCMParentDataTable->client) {
                $app->enqueueMessage(
                    Text::_("COM_TJUCM_CUSTOM_ERROR_MSG_TITLE"),
                    "error"
                );
                echo new JsonResponse(null, null, true);
                $app->close();
            }

            $client = $UCMParentDataTable->client;

            if (!$client) {
                $app->enqueueMessage(
                    Text::_("COM_TJUCM_CUSTOM_ERROR_MSG_TITLE"),
                    "error"
                );
                echo new JsonResponse(null, null, true);
                $app->close();
            }

            // Get UCM type id from unique identifier
            BaseDatabaseModel::addIncludePath(
                JPATH_ADMINISTRATOR . "/components/com_tjucm/models"
            );
            $tjUcmModelType = BaseDatabaseModel::getInstance(
                "Type",
                "TjucmModel"
            );
            $ucmTypeId = $tjUcmModelType->getTypeId($client);

            if (!$ucmTypeId) {
                $app->enqueueMessage(Text::_("JERROR_ALERTNOAUTHOR"), "error");
                echo new JsonResponse(null, null, true);
                $app->close();
            }

            $canDelete = TjucmAccess::canDelete($ucmTypeId, $ucmDataId);

            if (!$canDelete) {
                $app->enqueueMessage(Text::_("JERROR_ALERTNOAUTHOR"), "error");
                echo new JsonResponse(null, null, true);
                $app->close();
            }

            // create model object
            BaseDatabaseModel::addIncludePath(
                JPATH_SITE . "/components/com_tjucm/models"
            );
            $model = BaseDatabaseModel::getInstance("Itemform", "TjucmModel");
            $return = $model->delete($ucmDataId);

            if (!$return) {
                $app->enqueueMessage(
                    Text::_("COM_TJUCM_CUSTOM_ERROR_MSG_TITLE"),
                    "error"
                );
                echo new JsonResponse(null, null, true);
                $app->close();
            }

            JLoader::import(
                "components.com_tjfields.tables.field",
                JPATH_ADMINISTRATOR
            );
            $fieldTable = Table::getInstance("field", "TjfieldsTable");
            $fieldTable->load([
                "name" => "com_tjucm_ropdataflow_parentstepindataflow",
            ]);

            if (!property_exists($fieldTable, "id") || !$fieldTable->id) {
                $app->enqueueMessage(
                    Text::_("COM_TJUCM_CUSTOM_ERROR_MSG_TITLE"),
                    "error"
                );
                echo new JsonResponse(null, null, true);
                $app->close();
            }

            $db = Factory::getDbo();
            $query = $db->getQuery(true);

            // Fields to update.
            $fields = [$db->quoteName("value") . " = 0"];

            // Conditions for which records should be updated.
            $conditions = [
                $db->quoteName("field_id") . " = " . (int) $fieldTable->id,
                $db->quoteName("value") . " = " . (int) $ucmDataId,
            ];

            $query
            ->update($db->quoteName("#__tjfields_fields_value"))
            ->set($fields)
            ->where($conditions);
            $db->setQuery($query);
            $result = $db->execute();

            if (!$result) {
                $app->enqueueMessage(
                    Text::_("COM_TJUCM_CUSTOM_ERROR_MSG_TITLE"),
                    "error"
                );
                echo new JsonResponse(null, null, true);
                $app->close();
            }

            echo new JResponseJson(
                $return,
                Text::_("COM_TJUCM_ROP_DELETE_RECORDS_SUCCESS_MSG")
            );
            $app->close();
        }
    }

    /**
     * Method to remove data
     *
     * @return void
     *
     * @throws Exception
     *
     * @since 1.6
     */
    public function remove()
    { 
        $app = Factory::getApplication();

        if (!Session::checkToken()) {
            $app->enqueueMessage(Text::_("JINVALID_TOKEN"), "error");
            echo new JsonResponse(null, null, true);
            $app->close();
        }

        $recordIds = $app->input->post->get("recordIds", "", "STRING");
        $client = $app->input->get("client");

        if (empty($recordIds)) {
            $app->enqueueMessage(
                Text::_("Please select record to delete"),
                "error"
            );
            echo new JsonResponse(null, null, true);
            $app->close();
        }

        if ($client) {
            // Get UCM type id from unique identifier
            BaseDatabaseModel::addIncludePath(
                JPATH_ADMINISTRATOR . "/components/com_tjucm/models"
            );
            $tjUcmModelType = BaseDatabaseModel::getInstance(
                "Type",
                "TjucmModel"
            );
            $ucmTypeId = $tjUcmModelType->getTypeId($client);
        }

        // create model object
        BaseDatabaseModel::addIncludePath(
            JPATH_SITE . "/components/com_tjucm/models"
        );
        $model = BaseDatabaseModel::getInstance("Itemform", "TjucmModel");

        BaseDatabaseModel::addIncludePath(
            JPATH_SITE . "/components/com_dpe/models"
        );
        $userModel = BaseDatabaseModel::getInstance("Users", "DpeModel");

        // Get the current logged-in user
        $currentUser = Factory::getUser();
        $currentUserId = $currentUser->id;

        // First, validate all records before deleting any
        $recordsToDelete = [];
        $unauthorizedRecords = [];
        $nonDraftRecords = [];

        // Check general delete permissions (using first record as reference)
        $firstRecordId = reset($recordIds);
        $canDeleteOwn = TjucmAccess::canDeleteOwn($ucmTypeId, $firstRecordId);
        $canDelete = TjucmAccess::canDelete($ucmTypeId, $firstRecordId);

        // If user doesn't have any delete permissions, check each record
        if (!$canDelete && !$canDeleteOwn) {
            foreach ($recordIds as $recordId) {
                // Load the UCM record to check creator and state
                Table::addIncludePath(
                    JPATH_ADMINISTRATOR . "/components/com_tjucm/tables"
                );
                $UCMDataTable = Table::getInstance("data", "TjucmTable");
                $UCMDataTable->load(["id" => (int) $recordId]);

                // Check if record is draft and created by current user
                $isDraftByCurrentUser = ($UCMDataTable->draft == 1 && 
                                        $UCMDataTable->created_by == $currentUserId);

                // If draft by current user, allow deletion
                if ($isDraftByCurrentUser) {
                    $recordsToDelete[] = $recordId;
                } else {
                    // User has no delete permissions and it's not their draft
                    // Show message immediately and stop
                    $app->enqueueMessage(Text::_("COM_TJUCM_DELETE_ONLY_DRAFT_RECORDS"), "error");
                    echo new JsonResponse(null, null, true);
                    $app->close();
                }
            }
        } else {
            // User has delete permissions, proceed with all records
            $recordsToDelete = $recordIds;
        }
        
        // Now delete all authorized records
        foreach ($recordsToDelete as $recordId) {
            $isJobTitlePresent = "";

            if ($client == "com_tjucm.role") {
                $isJobTitlePresent = $userModel->checkUserForJobTitle(
                    $recordId
                );

                if (!empty($isJobTitlePresent)) {
                    $app->enqueueMessage(
                        Text::_("COM_DPE_JOBTITLE_ERROR_MSG_SAVE"),
                        "error"
                    );
                    echo new JsonResponse(null, null, true);
                    $app->close();
                }
            }

            $model->setState("ucmType.id", $ucmTypeId);
            $return = $model->delete($recordId);

            if ($client == 'com_tjucm.rop') {
                PluginHelper::importPlugin("dpe");
                Factory::getApplication()->triggerEvent('onAfterRopDeleteRemoveRecordFromFlatTable', array($recordId, $client));
            }

            if (!$return) {
                $app->enqueueMessage(Text::_("Something went wrong"), "error");
                echo new JsonResponse(null, null, true);
                $app->close();
            }
        }

        echo new JResponseJson(
            $return,
            Text::_("Records deleted successfully.")
        );
        $app->close();
    }

    /**
     * Method to get the Link to ticket field data
     *
     * @return array FieldValue | fieldname
     *
     */
    public function getLogFieldValue()
    {
        $app = Factory::getApplication();
        $ucmId = $app->input->post->get("ucmId", 0, "INT");
        $fieldId = $app->input->post->get("fieldId", 0, "INT");

        // Call the table to get the ticket details.

        Table::addIncludePath(
            JPATH_ADMINISTRATOR . "/components/com_tjfields/tables"
        );
        $fieldsValueTable = Table::getInstance("Fieldsvalue", "TjfieldsTable");
        $fieldsValueTable->load([
            "field_id" => $fieldId,
            "content_id" => $ucmId,
        ]);

        // Get the id of link text box check that field is present or not.
        Table::addIncludePath(
            JPATH_ADMINISTRATOR . "/components/com_tjfields/tables"
        );
        $fieldTable = Table::getInstance("field", "TjfieldsTable");
        $fieldTable->load(["id" => $fieldId]);

        if ($fieldsValueTable->value) {
            echo new JsonResponse([
                "fieldValue" => $fieldsValueTable->value,
                "fieldId" => $fieldTable->name,
            ]);
            $app->close();
        }
    }

    /**
     * Method to get the comment section of jlike
     *
     * @return array FieldValue | fieldname
     *
     */
    public function ajaxShowComment()
    {
        $app = Factory::getApplication();
        $input = $app->input;

        $ucmData = new stdClass();
        $ucmData->id = $input->get("recordId", "", "STRING");
        $ucmData->client = $input->get("client", "", "STRING");
        $ucmData->ajaxCallFromUcm = true;
        $params = [];

        $plugin = PluginHelper::getPlugin("content", "jlike_dpe");

        if ($plugin) {
            PluginHelper::importPlugin("content", "jlike_dpe");

            $results = Factory::getApplication()->triggerEvent(
                "onContentAfterDisplay",
                ["com_tjucm.itemform", &$ucmData, &$params]
            );

            $results = trim(implode("\n", $results));
            echo new JsonResponse(["results" => $results]);
        }

        $app->close();
    }

    /**
     * Method to Store the org id in session
     *
     * @return array FieldValue | fieldname
     *
     */
    public function saveOrgSession()
    {
        // Get the application object and the input object
        $app = Factory::getApplication();
        $input = $app->input;

        // Get the session object
        $session = Factory::getSession();

        $session->clear("dashboardOrgansiationID");
        // Check if the AJAX request has the selectedCluster value
        $selectedCluster = $input->getString("selectedCluster"); // 'getString' is a more secure way to retrieve a string input
        $selectedCluster = str_replace('"', "", $selectedCluster);

        // Set the session value from the AJAX request
        $session->set("dashboardOrgansiationID", $selectedCluster);
        $session->set("fromDashboard", 1);

        echo new JsonResponse($selectedCluster);
        $app->close();
    }

    /**
     * Method to get the due date of the checklist for the checklist module
     *
     * @return array FieldValue | fieldname
     *
     */
    public function getChecklistContentId()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $checklistId = $input->get("recordId", "", "STRING");
        $checklistId = $input->get("recordId", "0", "INT");

        if (!$checklistId) {
            return false;
        }
        Table::addIncludePath(JPATH_SITE . "/components/com_dpe/tables");
        $checklistReview = Table::getInstance(
            "ChecklistNextReviewDate",
            "DpeTable"
        );
        $checklistReview->load(["content_id" => $checklistId]);

        Table::addIncludePath(
            JPATH_ROOT . "/administrator/components/com_jlike/tables"
        );
        $todosTable = Table::getInstance("Todos", "JlikeTable");
        $todosTable->load(["id" => $checklistReview->todo_id]);
        $dueDate = $todosTable->due_date;

        echo new JsonResponse(["duedate" => $dueDate]);
        $app->close();
    }

    /**
     * Function is triggered for downloading PDF file
     *
     * @return  pdf
     *
     * @since  __DEPLOY_VERSION__
     */

	public function getUcmDetailPdfDownload()
	{

    // Get the input data
		$input = Factory::getApplication()->input;
		$data = json_decode($input->get('data', '', 'RAW'));

    // Validate input data
		if (empty($data) || empty($data->title) || empty($data->content)) {

			throw new RuntimeException('Invalid or missing data.');
		} 


        // Image URL for the logo
        $imageUrl = Uri::root() . "images/DataProtectionEd_Logo150H.jpg";
        $mpdf = new \Mpdf\Mpdf([
            "mode" => "utf-8",
            "format" => "A4",
            "shrink_tables_to_fit" => 0, // Prevents auto shrinking of tables
            "default_font_size" => 13,
            "allow_output_buffering" => true,
        ]);

        $currentPageNumber = $mpdf->page;

        // Prepare the HTML content
        $html =
        '<html><head><meta name="viewport" content="width=device-width, initial-scale=1"><meta charset="utf-8"/>';
        $html .= '<style>
        @page{margin-top:120px;margin-bottom:80px;header:html_myHeader;footer:html_myFooter}body{font-family:"Open Sans",sans-serif!important;margin-bottom:60px;padding:0;margin-left:25px}
        .content-wrapper{margin-top:120px;display:flex;justify-content:space-between;align-items:center;gap:10px}.accordspan{text-decoration:underline;margin-top:10px}.detailnumeric{width:65%}
        .section{margin-top:20px}.label{flex:1;padding:10px;width:30%;display:inline-block;padding:8px;word-wrap:break-word;vertical-align:top;font-weight:400}.value{flex:1;padding:10px;width:65%;display:inline-block;padding:8px;word-wrap:break-word;vertical-align:top;color:#777}.section-header{font-size:30px;color:#333;margin:20px 0 10px 0;font-weight:400}.feedback-row{padding-top:5px;padding-bottom:15px;width:65%}.feedback-content{color:black;font-size:.9em}a{color:#22B8F0;text-decoration:none}img{max-width:200px;max-height:150px;margin:5px 0}
        .page-break{page-break-after:always}.page-number:before{content:counter(page);font-size:12px;color:grey}.headingOfReport{font-size:40px;color:black;font-weight:700;text-align:center;margin-top:10px}.flex-container{display:flex;align-items:flex-start;gap:10px}.numericcalculation{background:#d3d3da;padding:4px;width:100%;font-weight:700;color:white;margin-left:-1px;text-align:center;font-size:inherit}
        .freetextMod { width: 100% !important; font-size: 16px !important; line-height: 25px !important;}
        </style>';
        $html .= "</head><body>";
        $html .=
        '<htmlpageheader name="myHeader" style="margin-bottom:20px;">
        <table width="100%">
        <tr>
        <td width="60%">
        <p style="margin: 0;">' .
        ($currentPageNumber == 0 ? htmlspecialchars($data->title) : "") .
        '</p>
        <p style="margin: 0;">
        ' .
        ($currentPageNumber == 0 ? htmlspecialchars($data->orgname) : "") .
        "    " .
        ($currentPageNumber == 0 ? htmlspecialchars($data->date) : "") .
        '
        </p>
        </td>
        <td width="40%" align="right">
        <img src="' .
        $imageUrl .
        '" style="height: 40px;">
        </td>
        </tr>
        </table>
        </htmlpageheader>';

        // **Define Footer**
        $html .= '<htmlpagefooter name="myFooter">
        <hr style="border-top: 1px solid black;">
        <table width="100%">
        <tr>
        <td align="right" style="font-size: 10px;">Page {PAGENO} of {nbpg}</td>
        </tr>
        </table>
        </htmlpagefooter>';

        $page = 1;
        //$html .= $this->generateHeader($page, $imageUrl, $data);
        $html .= '<div class="content-wrapper">';

        // Cover Page
        $html .=
        '<div class="section" style="text-align: center; padding-top: 90px;">
        <h1 class="headingOfReport">' .
        htmlspecialchars($data->title) .
        '</h1>
        <h1 class="headingOfReport">Report</h1><br>
        <h3 style="font-size: 24px; margin-top: 30px;">' .
        htmlspecialchars($data->orgname) .
        '</h3>
        <h4 style="font-size: 24px; margin-top: 25px;">' .
        htmlspecialchars($data->conductedBy) .
        '</h4>
        <h4 style="font-size: 24px; margin-top: 20px;">' .
        htmlspecialchars($data->date) .
        '</h4>
        </div>';

        $html .= '<div class="page-break"></div>';

        // Content Pages
        foreach ($data->content as $tab) {
            $html .= '<div class="section">';
            $html .=
            '<h2 class="section-header">' .
            htmlspecialchars($tab->title) .
            "</h2><hr/>";
            foreach ($tab->fields as $field) {
                if (is_array($field->fields)) {
                    $html .=
                    '<h3 class="subsection-header">' .
                    htmlspecialchars($field->title) .
                    "</h3>";
                    foreach ($field->fields as $subField) {
                        $html .= $this->generateDivRow($subField);
                    }
                } else {
                    $html .= $this->generateDivRow($field);
                }
            }
            $html .= "</div>";
            $html .= '<div class="page-break"></div>';
        }

        $html .= "</div></body></html>";
        $pdfName =
        $data->title . " " . $data->orgname . date("YmdHis") . ".pdf";

        $mpdf->WriteHTML($html);

        $mpdf->Output($pdfName, "D");

        Factory::getApplication()->close(); 
    }

    /**
     * Generates a div row for the PDF report with a label, value, optional image, and feedback.
     *
     * @param object $field  Field data containing:
     *                       - label (string): Field label.
     *                       - value (string): Field value.
     *                       - image (string, optional): Image URL.
     *                       - feedback (string, optional): Additional feedback.
     * @return string  Generated HTML for the div row.
     * @since  __DEPLOY_VERSION__
     */
    private function generateDivRow($field)
    {
        // Check if 'freetextMod' exists in the label
        if (str_contains($field->label, "freetextMod")) {
            // Remove all classes from <div> and add inline styles
            $field->label = preg_replace(
                '/<div\s+class="[^"]*"/',
                '<div style="width:730px;"',
                $field->label
            );
            $tdClass = ""; // No class for <td>
        } else {
            $tdClass = "label";
        }

        // Generate the div row
        $row = '<div class="flex-container" style="margin-bottom:20px;">';

        if ($field->accordion != "" && $field->accordion != "acc present") {
            $row .=
            '<div style="padding:10px;">' . $field->accordion . "</div>";
        }

        $row .= '<div class="' . $tdClass . '">' . $field->label . "</div>";

        if (!str_contains($field->label, "freetextMod")) {
            $row .=
            '<div class="value" style="word-wrap: break-word;float:right;margin-top:-40px;">';

            if ($field->image) {
                $row .=
                '<img src="' .
                $field->image .
                '" style="height:60%;width:60px;"><br>';
            }

            $row .= $field->value . "</div>";

            $row .= "</div>"; // Close flex contai
        }
        if ($field->accordion != "" && $field->accordion != "acc present") {
            // $row .= '<hr style="margin-top:20px;">';
        }
        return $row;
    }

    /**
     * Method to insert copy records in queue table
     *
     * @return  boolean
     *
     * @since   __DEPLOY_VERSION__
     */
    public function UcmCopyItem()
	{
		$app = Factory::getApplication();

		// Get Input Data
		$data = array(
			"sourceClient" => $app->input->get('client', '', 'string'),
			"filter"       => $app->input->get('filter', '', 'ARRAY'),
			"clusterIds"   => $app->input->get('cluster_list', '', 'string'),
			"copyIds"      => $app->input->get('cid'),
			"userId"       =>Factory::getUser()->id
		);

			$messageBody = (object) $data;

			try
			{
				$TJQueueProduce = new TJQueueProduce;

				// Set message body
				$TJQueueProduce->message->setBody(json_encode($messageBody));

				// @Params client, value
				$TJQueueProduce->message->setProperty('client', 'dpe.ucmcopyitem');
				$TJQueueProduce->produce();

				$msg =Text::_("COM_DPE_COPY_TO_QUEUE_SUCCESSFULLY");
				echo new JsonResponse($messageBody, $msg);
			}
			catch (Exception $e)
			{
				$msg = Text::_("COM_TJUCM_FORM_SAVE_FAILED");
				echo new JsonResponse($messageBody, $msg);
			}

			
	}

    /**
     * Method to store the selected reports data into queue.
     *
     * @return  boolean
     *
     * @since   __DEPLOY_VERSION__
     */
    public function storeReportDataInQueue()
    {
        // Get Joomla Database Object
        $db = Factory::getDbo();
        $app = Factory::getApplication();

        $user = Factory::getUser();
        $userId = $user->id;
        // Get Input Data
        $reportDatas =  $app->input->get("data", "", "raw");
        $return = 0;

        
        $cluster = $reportDatas[0]['cluster'];
        $client = $reportDatas[0]['client'];
        $agencyTag = $reportDatas[0]['tags'];
        $search = isset($reportDatas[0]['search']) ? $reportDatas[0]['search'] : '';

        if (!empty($agencyTag) && $cluster[0] == 'all')
		{	
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
			$dashBoardModel = JModelLegacy::getInstance('Dashboard', 'DpeModel');
			$cluster = $dashBoardModel->getClusterIdsByTags($agencyTag);

		}
		
        BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models');
        $model = BaseDatabaseModel::getInstance('Import', 'DpeModel');
        $recordIds = $model->getAllUcmRecordByClientCluster($client, $cluster, $search);
    
    if(!empty($recordIds))
    {
            unset($reportDatas[0]['cluster']);
            $reportDatas[0]['id'] = $recordIds;
            $reportDatas[0]['userId'] = $userId;

            $messageBody = (object) $reportDatas;


            try {
                $TJQueueProduce = new TJQueueProduce();

            // Set message body
                $TJQueueProduce->message->setBody(json_encode($messageBody));

            // @Params client, value
                $TJQueueProduce->message->setProperty("client", 'dpe.ucmreportdownload');
                $TJQueueProduce->produce();
                $return++;
                
            } catch (Exception $e) {
                $msg = Text::_("COM_TJUCM_FORM_SAVE_FAILED");
                echo new JsonResponse($messageBody, $msg);
            }
    }
    
        if ($return)
        {
            $msg = Text::_("COM_DPE_REPORT_ADDED_TO_QUEUE_SUCCESSFULLY");
                echo new JsonResponse($messageBody, $msg);
        }
    }


    /**
     * Method to Fetch the Cluster Ids through tags.
     *
     * @return  array Cluster Ids
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getClusterIdsByTags()
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
                }
            }

            echo new JsonResponse($activeLicenceClusterIds);
            $app->close();
        }
        
    }

   
}
