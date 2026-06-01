<?php
/**
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.

defined("_JEXEC") or die();
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Date\Date;

jimport("techjoomla.tjnotifications.tjnotifications");

/**
 * Admin Model for an ROP cluster.
 *
 * @since  1.0.0
 */
class DpeModelImport extends FormModel
{
    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  Form|boolean  A Form object on success, false on failure
     *
     * @since   1.0.0
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm("com_dpe.import", "import", [
            "control" => "jform",
            "load_data" => $loadData,
        ]);

        return empty($form) ? false : $form;
    }

    /**
     * Method to get the records of the ucm ids.
     *
     * @param   array    $recordIds     Id's of th ucm records.
     * @param   String   $clientName client Name of the ucm.
     *
     * @return  all the values belongs to the  given client and the given ucmIds.
     *
     * @since   1.0.0
     */

    public function getDatasOfUcmRecordId($recordId, $clientName, $formName)
    {
        if (empty($recordId) || !$clientName) {
            return false;
        }

        $db = Factory::getDbo();

        $query = $db
        ->getQuery(true)
        ->select([
            "g.id AS group_id",
            "g.name AS group_name",
            "f.id AS field_id",
            "f.label",
            "f.name",
            "f.type",
            "f.params",
            "f.showFeedback",
            "v.content_id",
            "v.value",
            "tag.title AS tag_title",
        ])
        ->from($db->qn("#__tjfields_groups", "g"))
        ->join(
            "LEFT",
            $db->qn("#__tjfields_fields", "f") . " ON f.group_id = g.id"
        )
        ->join(
            "LEFT",
            $db->qn("#__tjfields_fields_value", "v") .
            " ON v.field_id = f.id AND v.content_id = " .
            (int) $recordId
        )
        ->join(
            "LEFT",
            $db->qn("#__contentitem_tag_map", "tm") .
            ' ON tm.content_item_id = f.id AND tm.type_alias = "com_tjfields.field"'
        )
        ->join("LEFT", $db->qn("#__tags", "tag") . " ON tm.tag_id = tag.id")
        ->where("g.client = " . $db->quote($clientName))
        ->where("f.state = 1")
        ->order($db->quoteName("g.name") . " ASC");
        $query->order($db->qn("f.ordering") . " ASC");

        $db->setQuery($query);
        $results = $db->loadObjectList();

        $groups = $this->getUcmGroups($clientName);
        $groupedFields = [];

        foreach ($groups as $group) {
            $groupName = $group->name;
            $groupedFields[$groupName] = [];

            foreach ($results as $field) {
                if ($field->group_name === $groupName) {
                    if (
                        !empty($field->value) &&
                        isset($field->showFeedback) &&
                        $field->showFeedback == 1
                    ) {
                        $optionQuery = $db
                        ->getQuery(true)
                        ->select($db->qn("feedback"))
                        ->from($db->qn("#__tjfields_options"))
                        ->where(
                            $db->qn("value") .
                            " = " .
                            $db->quote($field->value)
                        )
                        ->where(
                            $db->qn("field_id") .
                            " = " .
                            (int) $field->field_id
                        );

                        $db->setQuery($optionQuery);
                        $field->feedback = $db->loadResult();
                    }

                    $groupedFields[$groupName][] = $field;
                }
            }
        }

        foreach ($groupedFields as &$singleData) {
            for ($i = 0; $i < count($singleData); $i++) {
                if ($singleData[$i]->type == "ucmsubform") {
                    $dbsub = Factory::getDbo();
                    $query = $dbsub->getQuery(true);
                    $query
                    ->select("tj.id, tj.client")
                    ->from($dbsub->qn("#__tj_ucm_data", "tj"))
                    ->where(
                        "tj.parent_id = " .
                        (int) $singleData[$i]->content_id
                    )
                    ->where(
                        "tj.client = " .
                        $dbsub->quote($singleData[$i]->value)
                    );
                    $dbsub->setQuery($query);

                    $subFormFieldIds = $dbsub->loadObjectList();

                    $usbformData = [];

                    foreach ($subFormFieldIds as $key => $subFormFieldId) {
                        $usbformData[$key] = $this->getSubfunctionFieldData(
                            $subFormFieldId,
                            $singleData[$i]->value
                        );
                    }

                    $singleData[$i]->value = $usbformData;
                }
            }
        }

        $html = "";
        $groupedItems = [];
        $previousTag = "";
        $index = 0;
        foreach ($groupedFields as $tabName => $groupItems) {
            $html .=
            $index === 0
            ? '<div class="vpage-break">'
            : '<div class="page-break">';
            $html .=
            '<h2 class="section-header">' .
            htmlspecialchars($tabName) .
            "</h2><hr>";

            $reorderedItems = [];
            $usedIndexes = [];
            $index++;
            $count = count($groupItems);

            for ($i = 0; $i < $count; $i++) {
                if (in_array($i, $usedIndexes)) {
                    continue;
                }

                $current = $groupItems[$i];
                $reorderedItems[] = $current;
                $usedIndexes[] = $i;

                if (!empty($current->tag_title)) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $compare = $groupItems[$j];

                        if (
                            !in_array($j, $usedIndexes) &&
                            $compare->group_name === $current->group_name &&
                            $compare->tag_title === $current->tag_title
                        ) {
                            $reorderedItems[] = $compare;
                            $usedIndexes[] = $j;
                        }
                    }
                }
            }

            foreach ($reorderedItems as $item) {
                if ($item->type === "ucmsubform" && is_array($item->value)) {
                    $html .= '<div class="subform-section">';
                    $html .=
                    '<div class="field-label col-md-12"><strong>' .
                    htmlspecialchars($item->label) .
                    "</strong></div>";

                    foreach ($item->value as &$subItemSet) {
                        $previousSubTag = "";
                        $merged = [];

                        foreach ($subItemSet as $obj) {
                            $key = $obj->field_id . "||" . $obj->label;

                            if (!isset($merged[$key])) {
                                $merged[$key] = clone $obj;
                            } else {
                                $merged[$key]->value .= "<br>" . $obj->value;
                            }
                        }

                        // Replace original with merged (reindex to maintain numeric keys)
                        $subItemSet = array_values($merged);

                        foreach ($subItemSet as $subItem) {
                            if (
                                empty($subItem->label) &&
                                empty($subItem->value)
                            ) {
                                continue;
                            }

                            $label = trim($subItem->label ?? "");
                            $label =
                            $label !== ""
                            ? htmlspecialchars($label)
                            : "&nbsp;";
                            $value =
                            !empty($subItem->value) &&
                            $subItem->value != "tjlist:-"
                            ? $subItem->value
                            : "<em></em>";

                            if (!empty($subItem->feedback)) {
                                $subItem->feedback = preg_replace(
                                    "/<p[^>]*>/",
                                    "",
                                    $subItem->feedback
                                ); // Remove <p> or <p style="...">
                                $subItem->feedback = str_replace(
                                    "</p>",
                                    "",
                                    $subItem->feedback
                                ); // Remove </p>
                            }
                            $feedback = !empty($subItem->feedback)
                            ? '<div class="feedbackDetailColor">' .
                            $subItem->feedback .
                            "</div>"
                            : "";
                            $class = !empty($subItem->feedback)
                            ? "feedback"
                            : "";
                            $accordionClass = !empty($subItem->tag)
                            ? "accordion"
                            : "";

                            if (!empty($subItem->tag)) {
                                $html .= '<div class="col-12 accordDetail">';
                                $html .= '  <div class="row">';
                                $html .= '    <div class="col-md-12">';
                                $html .=
                                '      <span class="accordspan">' .
                                htmlspecialchars($subItem->tag) .
                                "</span>";
                                $html .= "    </div>";
                                $html .= "  </div>";
                                $html .= "</div>";
                            }

                            if (
                                $subItem->type == "assignee" ||
                                $subItem->type == "ownership"
                            ) {
                                $user = Factory::getUser($subItem->value);
                                $value = $user->username;
                            }

                            if (
                                $subItem->type == "hidden" ||
                                $subItem->type == "spacer"
                            ) {
                                $value = "";
                                $label = "";
                            }
                            if ($subItem->type == "calendar") {
                                if ($subItem->value) {
                                    $dateString = $subItem->value;
                                    $joomlaDate = new Date($dateString);
                                    $value = $joomlaDate->format("l, d F Y");
                                }
                            }
                            if ($subItem->type == "related") {
                                $params = json_decode($subItem->params);
                                if (
                                    isset(
                                        $params->fieldName->fieldName0->fieldIds
                                    )
                                ) {
                                    $fieldIds =
                                    $params->fieldName->fieldName0
                                    ->fieldIds;
                                }

                                Table::addIncludePath(
                                    JPATH_ADMINISTRATOR .
                                    "/components/com_tjfields/tables"
                                );
                                $fieldValueTable = Table::getInstance(
                                    "Fieldsvalue",
                                    "TjfieldsTable"
                                );
                                $fieldValueTable->load([
                                    "content_id" => $subItem->value,
                                    "field_id" => $fieldIds[0],
                                ]);
                                $value = $fieldValueTable->value;
                            }
                            if ($subItem->type == "checkbox") {
                                $value =
                                "<input type='checkbox'" .
                                (($subItem->value == 1) ? " checked" : "") .
                                ">";
                            }

                            if ($subItem->type == "cluster") {
                                JLoader::import(
                                    "clusters",
                                    JPATH_ADMINISTRATOR .
                                    "/components/com_cluster/tables"
                                );
                                $clustersTable = Table::getInstance(
                                    "clusters",
                                    "ClusterTable",
                                    []
                                );
                                $clustersTable->load(["id" => $subItem->value]);
                                $value = $reportData["organisation"] =
                                $clustersTable->name;
                                $reportData["cluster_id"] = $clustersTable->id;
                            }

                            if (($subItem->type == "captureimage") || ($subItem->type == "image") ||
                                ($subItem->type == "tjfile")) {
                                Table::addIncludePath(
                                    JPATH_ROOT .
                                    "/administrator/components/com_tjfields/tables"
                                );
                            $tjfieldsTable = Table::getInstance(
                                "field",
                                "TjfieldsTable"
                            );
                            $tjfieldsTable->load(["id" => $subItem->field_id]);

                            if(($subItem->type == "captureimage") || ($subItem->type == "tjfile"))
                            {
                                $uploadPath = json_decode($tjfieldsTable->params)
                                ->uploadpath;
                            }else
                            {  
                                $imageFolder = str_replace('com_tjucm.', '', $clientName);
                                $uploadPath = Uri::root() .'images/tjmedia/com_tjucm/'.$imageFolder;
                            }

                            $renderType = json_decode($tjfieldsTable->params)->renderer;
                            $height = json_decode($tjfieldsTable->params)->height;
                            $width = json_decode($tjfieldsTable->params)->width;

                            if (($uploadPath) && ($subItem->type != "tjfile"))
                            {
                                $value ="<img src='" . $uploadPath .$subItem->value ."' height='" . $height .
                                "' width='" . $width . "' /> ";
                            }elseif(($subItem->type == "tjfile"))
                            {   
                               $relativePath = str_replace(JPATH_ROOT . '/', '', $uploadPath);  
                               $uploadPath = Uri::root() . $relativePath.$subItem->value;
                               $value = '<div><strong class=""><a href="'.$uploadPath.'">'.$subItem->value.'</a></strong></div>';
                           }


                       }
                       if ($subItem->type == "numericcalculation") {
                        $colorValueData = json_decode(
                            json_decode($subItem->params)
                            ->colorcombination
                        );

                        foreach ($colorValueData as $combo) {
                            if (
                                isset($combo->min) &&
                                isset($combo->max) &&
                                $subItem->value >= $combo->min &&
                                $subItem->value <= $combo->max
                            ) {
                                $value =
                                '<p class="numericcalculation" style="color:' .
                                htmlspecialchars($combo->color) .
                                ';">' .
                                htmlspecialchars($combo->value) .
                                "</p>";
                                break;
                            }
                        }
                    }

                    if ($subItem->type == "tjlist") {
                        Table::addIncludePath(
                            JPATH_ROOT .
                            "/administrator/components/com_tjfields/tables"
                        );
                        $tjfieldTable = Table::getInstance(
                            "field",
                            "TjfieldsTable"
                        );
                        $tjfieldTable->load([
                            "id" => $subItem->field_id,
                        ]);
                        $multiple = json_decode($tjfieldTable->params)
                        ->multiple;
                        if ($multiple) {
                            if (
                                strpos($subItem->value, "<br") !== false
                            ) {
                                $tjListMUltiValues = explode(
                                    "<br>",
                                    $subItem->value
                                );
                                foreach (
                                    $tjListMUltiValues
                                    as $key => $tjListMUltiValue
                                ) {
                                    $fieldValue = $this->getTjListValues(
                                        $subItem->field_id,
                                        $tjListMUltiValue
                                    );
                                    $finallistData[$key] =
                                    $fieldValue->options . "<br>";
                                }
                                $value = is_array($finallistData)?implode(' ',$finallistData):$finallistData;

                            }else
                            {
                                $values = $this->getTjListValues(
                                    $subItem->field_id,
                                    $subItem->value
                                );
                                $value = $values->options;
                            }

                        } else {


                            $values = $this->getTjListValues(
                                $subItem->field_id,
                                $subItem->value
                            );
                            $value = $values->options;
                        }
                    }

                    $html .=
                    '<div class="flex-container" style="margin-bottom:20px;">';
                    $html .= '<div class="label">';

                    if ($subItem->type == "freetext") {
                        $html .=
                        '<div style="width:730px;">' .
                        $label .
                        "</div>";
                        $html .= "</div>";
                    } elseif (
                        $subItem->tag_title &&
                        $previousSubTag != $subItem->tag_title
                    ) {
                        $html .= '<div class="accordDetail">';
                        $html .=
                        '<span class="accordspan" style="    
                        border-bottom: 1px solid #000;font-size: 21px;font-weight: 600;">' .
                        htmlspecialchars($subItem->tag_title) .
                        "</span>";
                        $html .=
                        '<div class="field-label" style="margin-top:25px;">' .
                        $label .
                        "</div></div></div>";
                        $previousSubTag = $subItem->tag_title;
                    } else {
                        $html .=
                        '<div class="field-label">' .
                        $label .
                        "</div>";
                        $html .= "</div>";
                    }

                    if (
                        $subItem->tag_title &&
                        $previousSubTag == $subItem->tag_title
                    ) {
                        $html .=
                        '<div class="value" style="word-wrap: break-word;float:right;margin-top:-40px;">';
                        $html .= '<div class="field-data">' . $value;
                    } else {
                        $html .=
                        '<div class="value" style="word-wrap: break-word;float:right;margin-top:-40px;">';
                        $html .= '  <div class="field-data">' . $value;
                    }

                    if (!empty($feedback)) {
                        $html .= "<br><br>" . $feedback;
                    }
                    $html .= "</div>";
                    $html .= "</div>";

                    if (
                        $subItem->tag_title &&
                        $previousSubTag == $subItem->tag_title
                    ) {
                        $html .= "</div>";
                        $html .= "</div>";
                        $previousSubTag = $subItem->tag_title;
                        $html .=
                        $subItem->tag_title &&
                        $previousSubTag != $subItem->tag_title
                        ? "'<hr>'"
                        : "";
                    } else {
                        $html .= "</div>";
                                $html .= "</div>"; // End of flex-container
                            }
                        }
                    }
                    $html .= "</div>"; // End of subform-section
                    continue;
                }
                if (empty($item->label) && empty($item->value)) {
                    continue;
                }

                if (!empty($item->feedback)) {
                    $item->feedback = preg_replace(
                        "/<p[^>]*>/",
                        "",
                        $item->feedback
                    ); // Remove <p> or <p style="...">
                    $item->feedback = str_replace("</p>", "", $item->feedback); // Remove </p>
                }

                $label = trim($item->label ?? "");
                $label = $label !== "" ? htmlspecialchars($label) : "&nbsp;";
                $value =
                !empty($item->value) && $item->value != "tjlist:-"
                ? $item->value
                : "<em></em>";
                $feedback = !empty($item->feedback)
                ? '<div class="feedbackDetailColor">' .
                $item->feedback .
                "</div>"
                : "";
                $class = !empty($item->feedback) ? "feedback" : "";
                $accordionClass = !empty($item->tag) ? "accordion" : "";

                $html .=
                '<div class="flex-container" style="margin-bottom:15px;">';

                if (!empty($sectionTitle)) {
                    $html .=
                    '<div style="padding:10px;"><span class="accordspan">' .
                    $sectionTitle .
                    "</span></div>";
                }

                if ($item->type == "assignee" || $item->type == "ownership") {
                    $user = Factory::getUser($item->value);
                    $value = $user->username;
                    $reportData["conductedBy"] = $user->username;
                }

                if ($item->type == "hidden" || $item->type == "spacer") {
                    $value = "";
                    $label = "";
                }
                if ($item->type == "calendar") {
                    if ($item->value) {
                        $dateString = $item->value;
                        $joomlaDate = new Date($dateString);
                        $value = $joomlaDate->format("l, d F Y");
                    }
                }
                if ($item->type == "related") {
                    $params = json_decode($item->params);
                    if (isset($params->fieldName->fieldName0->fieldIds)) {
                        $fieldIds = $params->fieldName->fieldName0->fieldIds;
                    }

                    Table::addIncludePath(
                        JPATH_ADMINISTRATOR . "/components/com_tjfields/tables"
                    );
                    $fieldValueTable = Table::getInstance(
                        "Fieldsvalue",
                        "TjfieldsTable"
                    );
                    $fieldValueTable->load([
                        "content_id" => $item->value,
                        "field_id" => $fieldIds[0],
                    ]);
                    $value = $fieldValueTable->value;
                }
                if ($item->type == "checkbox") {
                    $value =
                    "<input type='checkbox'" .
                    (($item->value == 1) ? "checked" : "") .
                    ">";
                }

                if ($item->type == "cluster") {
                    JLoader::import(
                        "clusters",
                        JPATH_ADMINISTRATOR . "/components/com_cluster/tables"
                    );
                    $clustersTable = Table::getInstance(
                        "clusters",
                        "ClusterTable",
                        []
                    );
                    $clustersTable->load(["id" => $item->value]);
                    $value = $reportData["organisation"] = $clustersTable->name;
                    $reportData["cluster_id"] = $clustersTable->id;
                }
    if (($item->type == "captureimage") || ($item->type == "image") ||
                    ($item->type == "tjfile")) {
                    Table::addIncludePath(
                        JPATH_ROOT .
                        "/administrator/components/com_tjfields/tables"
                    );
                $tjfieldsTable = Table::getInstance(
                    "field",
                    "TjfieldsTable"
                );
                $tjfieldsTable->load(["id" => $item->field_id]);

                if(($item->type == "captureimage") || ($item->type == "tjfile"))
                {
                    $uploadPath = json_decode($tjfieldsTable->params)
                    ->uploadpath;
                }else
                {  
                    $imageFolder = str_replace('com_tjucm.', '', $clientName);
                    $uploadPath = Uri::root() .'images/tjmedia/com_tjucm/'.$imageFolder;
                }

                $renderType = json_decode($tjfieldsTable->params)->renderer;
                $height = json_decode($tjfieldsTable->params)->height;
                $width = json_decode($tjfieldsTable->params)->width;

                if (($uploadPath) && ($item->type != "tjfile"))
                {
                    $value ="<img src='" . $uploadPath .$item->value ."' height='" . $height .
                    "' width='" . $width . "' /> ";
                }elseif(($item->type == "tjfile"))
                {   
                   $relativePath = str_replace(JPATH_ROOT . '/', '', $uploadPath);  
                   $uploadPath = Uri::root() . $relativePath.$item->value;
                   $value = '<div><strong class=""><a href="'.$uploadPath.'">'.$item->value.'</a></strong></div>';
               }


           }
           if ($item->type == "numericcalculation") {
            $colorValueData = json_decode(
                json_decode($item->params)->colorcombination
            );

            foreach ($colorValueData as $combo) {
                if (
                    isset($combo->min) &&
                    isset($combo->max) &&
                    $item->value >= $combo->min &&
                    $item->value <= $combo->max
                ) {
                    $value =
                    '<p class="numericcalculation" style="color:' .
                    htmlspecialchars($combo->color) .
                    ';">' .
                    htmlspecialchars($combo->value) .
                    "</p>";
                    break;
                }
            }
        }
        if ($item->type == "radio") {
            Table::addIncludePath(
                JPATH_ROOT .
                "/administrator/components/com_tjfields/tables"
            );
            $tjFieldOptionTable = Table::getInstance(
                "Option",
                "TjfieldsTable"
            );
            $tjFieldOptionTable->load([
                "field_id" => $item->field_id,
                "value" => $item->value,
            ]);
            $value = $tjFieldOptionTable->options;
        }
        if ($item->type == "tjlist") {
            Table::addIncludePath(
                JPATH_ROOT .
                "/administrator/components/com_tjfields/tables"
            );
            $tjfieldTable = Table::getInstance(
                "field",
                "TjfieldsTable"
            );
            $tjfieldTable->load(["id" => $item->field_id]);
            $multiple = json_decode($tjfieldTable->params)->multiple;

            if ($multiple) {
                if (strpos($item->value, "<br") !== false) {
                    $tjListMUltiValues = explode(
                        "<br>",
                        $subItem->value
                    );
                    foreach (
                        $tjListMUltiValues
                        as $key => $tjListMUltiValue
                    ) {
                        $fieldValue = $this->getTjListValues(
                            $item->field_id,
                            $tjListMUltiValue
                        );
                        $finallistData[$key] = $fieldValue->options . "<br>";
                    }
                    $value = is_array($finallistData)?implode(' ',$finallistData):$finallistData;
                }
                else
                {
                    $values = $this->getTjListValues(
                        $subItem->field_id,
                        $subItem->value
                    );
                    $value = $values->options;
                }
            } else {
                $values = $this->getTjListValues(
                    $item->field_id,
                    $item->value
                );
                $value = $values->options;
            }
        }

        if ($item->tag_title && $previousTag != $item->tag_title) {
            $html .= '<br> <div class="accordDetail">';
            $html .= "    <div>";
            $html .=
            '<span class="accordspan">' .
            htmlspecialchars($item->tag_title) .
            "</span>";
            $html .= "    </div>";
            $html .= "  </div>";
            $previousTag = $item->tag_title;
        }

        if ($item->type == "dpechecklist") {
            $checklistParams = json_decode($item->params);

            if (isset($checklistParams->enablechecklistscore) && $checklistParams->enablechecklistscore) {
                if (isset($checklistParams->tjfields) && is_array($checklistParams->tjfields)) {
                    foreach ($checklistParams->tjfields as $optionValue) {
                        if (isset($optionValue->numeric_value) && $item->value == $optionValue->numeric_value) {
                            $item->value = $optionValue->optionvalue;
                        }
                    }
                }
            }

            $value = ($item->value == 'todo') ? '<span>To-Do</span>' :
                (($item->value == 'inprogress') ? '<span>In Progress</span>' :
                (($item->value == 'done') ? '<span>Done</span>' :
                (($item->value == 'na') ? '<span>N/A</span>' : $item->value)));
        }
        if ($item->type == "freetext") {
            $html .= '<div class="field-label freetextMod"><div style="width:730px;">' . $params = json_decode($item->params)->freetext . "</div></div>";
    
        } else {
            $html .= '<div class="label">';
            $html .= '<div class="field-label">' . $label . "</div>";
        }

        $html .= "</div>";
        if ($item->tag_title && $previousTag == $item->tag_title) {
            $html .=
            '<div class="value" style="word-wrap: break-word;float:right;margin-top:-40px;">';
            $html .= '<div class="field-data">' . $value;
        } else {
            $html .=
            '<div class="value" style="word-wrap: break-word;float:right;margin-top:-40px;">';
            $html .= '  <div class="field-data">' . $value;
        }
        if (!empty($feedback)) {
            $html .= "<br><br>" . $feedback;
        }
        $html .= "</div>";
        $html .= "</div>";

        if ($item->tag_title && $previousTag == $item->tag_title) {
            $html .= "</div>";
            $previousTag = $item->tag_title;
        }
        $html .=
        $item->tag_title && $previousTag != $item->tag_title
        ? "<hr>"
        : "";
        $html .= "</div>";
    }
    $html .= "</div>";
}

$reportData["html"] = $html;
$now = Factory::getDate();
$reportData["date"] = $now->format("d-m-Y");
$reportData["title"] = ($formName)?$formName:str_replace('com_tjucm.', '', $clientName);
return $reportData;
}

    /**
     * Retrieves the UCM (Universal Content Model) field groups for a given client.
     *
     * This method is used to fetch metadata about the field groups defined for a specific
     * UCM type (client), which can be used to organize fields when rendering forms or outputs.
     *
     * @param string $client The UCM client identifier (e.g., 'com_example.function').
     *
     * @return array An array of field group objects associated with the given UCM client.
     */
public function getUcmGroups($client)
{
    $db = Factory::getDbo();
    $query = $db->getQuery(true);
    $query->select("grp.id,grp.name FROM `#__tjfields_groups` as grp");
    $query
    ->where('grp.state=1 AND client="' . $client . '"')
    ->order($db->quoteName("grp.ordering") . " ASC");
    $db->setQuery($query);
    return $db->loadObjectList();
}

    /**
     * Retrieves the subform field data for a given content record.
     *
     * This method is used to fetch structured data from a subform field associated with
     * a UCM (Universal Content Model) record, based on the content ID and client type.
     * Typically used to render subform fields like repeatable or grouped fieldsets.
     *
     * @param int    $contentId The ID of the UCM content item.
     * @param string $client    The client identifier (e.g., 'com_example.function').
     *
     * @return array An array of subform field data, each entry representing one row of subform inputs.
     */

public function getSubfunctionFieldData($contentId, $client)
{
    $dbsub = Factory::getDbo();
    $query = $dbsub->getQuery(true);

    $query
    ->select([
        "tj.label",
        "v.value",
        "tj.type",
        "tj.id AS field_id",
        "tj.showFeedback",
        "tag.title AS tag_title",
    ])
    ->from($dbsub->qn("#__tjfields_fields", "tj"))
    ->join(
        "LEFT",
        $dbsub->qn("#__tjfields_fields_value", "v") .
        " ON v.field_id = tj.id AND v.content_id = " .
        (int) $contentId->id
    )
    ->join(
        "LEFT",
        $dbsub->qn("#__contentitem_tag_map", "tm") .
        ' ON tm.content_item_id = tj.id AND tm.type_alias = "com_tjfields.field"'
    )
    ->join(
        "LEFT",
        $dbsub->qn("#__tags", "tag") . " ON tm.tag_id = tag.id"
    )
    ->where("tj.client = " . $dbsub->quote($client))
    ->where("tj.state = 1");
    $query->order($dbsub->qn("tj.ordering") . " ASC");

    $dbsub->setQuery($query);
    $subFormData = $dbsub->loadObjectList();

        // Loop through and add feedback if required
    foreach ($subFormData as &$field) {
            // Extract showFeedback from field params
        $showFeedback = isset($field->showFeedback)
        ? $field->showFeedback
        : 0;

        if (!empty($field->value) && $showFeedback == 1) {
            $feedbackQuery = $dbsub
            ->getQuery(true)
            ->select("feedback")
            ->from($dbsub->qn("#__tjfields_options"))
            ->where("value = " . $dbsub->quote($field->value))
            ->where("field_id = " . (int) $field->field_id);

            $dbsub->setQuery($feedbackQuery);
                $field->feedback = $dbsub->loadResult(); // Add feedback to the field
            }
        }
        return $subFormData;
    }

    /**
     * Stores the generated report download details for a specific user and cluster.
     *
     * This method is typically called after a report is generated and a download URL is available.
     * It can be used to log or persist the report URL, associated cluster ID, and user ID for future reference.
     *
     * @param string $downloadUrl The URL where the generated report can be downloaded.
     * @param int    $clusterId   The ID of the cluster associated with this report.
     * @param int    $userId      The ID of the user for whom the report was generated.
     *
     * @return void
     */
    public function storeReportDetailsForUser(
        $downloadUrl,
        $clusterId,
        $userId,
        $fileTitle
    ) {
        if (!$downloadUrl) {
           return false;
        }

        Table::addIncludePath(
            JPATH_ADMINISTRATOR . "/components/com_dpe/tables"
        );
        $bulkUcmTable = Table::getInstance("Bulkucmreport", "DpeTable");
        $currentDate = new DateTime();

        $reportData["cluster_id"] = implode(",", $clusterId);
        $reportData["user_id"] = $userId;
        $reportData["download_url"] = $downloadUrl;
        $reportData["name_of_the_file"] = $fileTitle;
        $reportData["created_at"] = $currentDate->format("Y-m-d H:i:s");

        if (!$bulkUcmTable->bind($reportData)) {
            Factory::getApplication()->enqueueMessage(
                $bulkUcmTable->getError(),
                "error"
            );
        }

        // Save the record
        if (!$bulkUcmTable->store()) {
            return false;
        } else {
            $this->notifyUserForBulkReportDownload(
                $downloadUrl,
                $userId,
                $fileTitle
            );
            return $table->id;
        }
    }

    /**
     * Sends an email notification to the user for bulk report download.
     *
     * This method is used to notify the specified user via email that their requested
     * bulk report is ready for download. It includes the download URL in the email content.
     *
     * @param string $downloadUrl The URL where the user can download the generated reports.
     * @param int    $userId      The ID of the user to notify.
     *
     * @return void
     */
    public function notifyUserForBulkReportDownload(
        $downloadUrl,
        $userId,
        $fileTitle
    ) {
        $app = Factory::getApplication();
        $user = Factory::getUser($userId);

        $recipients = [
            "email" => [
                "to" => [$user->email],
            ],
        ];
        $key = "com_dpe.notifyBulkUcmReportDownload";

        $options = new Registry();
        $replacements = new stdClass();
        $replacements->user = new stdClass();
        $replacements->user->name = $user->name;
        $replacements->user->url = Route::_($downloadUrl);
        $replacements->user->fileTitle = $fileTitle;

        $result = Tjnotifications::send(
            "com_dpe",
            $key,
            $recipients,
            $replacements,
            $options
        );
        return $result["success"];
    }

    public function getTjListValues($fieldId, $value)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select("t.options");
        $query->from("`#__tjfields_options` AS t");
        $query->where("t.field_id = " . $db->quote($fieldId));
        $query->where("t.value = " . $db->quote($value));
        $db->setQuery($query);
        $fieldsOptions = $db->loadObject();
        return $fieldsOptions;
    }

    /**
     * Get all UCM records for a specific client and cluster.
     *
     * This method retrieves Universal Content Model (UCM) records based on the
     * given client and cluster ID. It is used to filter records for specific
     * client-component contexts and associated cluster data.
     *
     * @param   string  $client   The client identifier (e.g., 'com_tjucm.client_key').
     * @param   int     $cluster  The ID of the cluster to filter records by.
     *
     * @return  array             An array of UCM record objects matching the criteria.
     */
    public function getAllUcmRecordByClientCluster($client, $cluster, $search = '')
    {
        if (!$client && !$cluster)
        {
            return false;
        }

$db    = Factory::getDbo();
$query = $db->getQuery(true);
$user  = Factory::getUser();

// Select the required fields from the table
$query->select($db->quoteName(['t.id', 'u.id'], ['t_id', 'u_id']))
    ->from($db->quoteName('#__tj_ucm_data', 't'))
    ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON (' .
        $db->quoteName('t.created_by') . ' = ' . $db->quoteName('u.id') .
        ' AND ' . $db->quoteName('u.block') . ' = 0)'
    )
    ->where($db->quoteName('t.client') . ' = ' . $db->quote($client))
    ->where($db->quoteName('t.state') . ' = 1')
    ->where($db->quoteName('t.created_by') . ' IS NOT NULL');

if (!empty($search)) {
    $search = $db->escape(trim((string) $search), true);
    
    if ($search !== '') {
        $subQuery = $db->getQuery(true);
        $subQuery->select(1);
        
        $searchTable = ($client == 'com_tjucm.rop') ? '#__tjfields_fields_value_flat' : '#__tjfields_fields_value';
        $subQuery->from($db->qn($searchTable, 'v'));
        
        $subQuery->where($db->qn('v.value') . ' LIKE ' . $db->q('%' . $search . '%'));
        $subQuery->where($db->qn('v.content_id') . '=' . $db->qn('t.id'));
        $query->where("EXISTS (" . $subQuery . ")");
    }
}

// Restrict to non-draft entries if user lacks permission
if (!$user->authorise('core.manageall', 'com_cluster')) {
    $query->where($db->quoteName('t.draft') . ' = 0');
}

// Handle single or multiple cluster IDs
if (is_array($cluster)) {
    $clusterIds = array_map([$db, 'quote'], $cluster);
    $query->where($db->quoteName('t.cluster_id') . ' IN (' . implode(',', $clusterIds) . ')');
} else {
    $query->where($db->quoteName('t.cluster_id') . ' = ' . (int) $cluster);
}

$db->setQuery($query);
// Or just the t.id values:
return array_column($db->loadAssocList(), 't_id');

    }
}
