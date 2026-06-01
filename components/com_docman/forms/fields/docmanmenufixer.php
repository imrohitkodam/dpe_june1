<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * This makes sure the form validates for existing menu items. Otherwise Joomla leaves the type field empty.
 *
 * Also adds some basic styling to parameters
 */
class JFormFieldDocmanmenufixer extends JFormField
{
    protected $type = 'Docmanmenufixer';

    protected function getInput()
    {
        if (class_exists('KObjectManager')) {
            try {
                $has_connect = KObjectManager::getInstance()->getObject('com://admin/docman.model.entity.config')->connectAvailable();
            }
            catch (Exception $e) {
                $has_connect = false;
            }
            
        } else {
            $has_connect = false;
        }
        
        $name = (string) $this->element['view'];

        $html = '
        <style type="text/css">#attrib-basic .control-group .control-label { width: 250px !important; } label#jform_params_docmanmenufixer-lbl { display: none }</style>
        <span class="js-docman-menu-fixer-anchor" style="display: none"></span>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded",function() {' .
            (!empty($name) ? 'document.getElementById("jform_type").value = '.json_encode(JText::_($name)).';' : '')
            . '
            
            var group = document.querySelector(".js-docman-menu-fixer-anchor").closest("div.control-group");

            if (group) {
                group.hidden = true;
            } 
            });
        </script>
        
        <script type="text/javascript">
                
        document.addEventListener("DOMContentLoaded", function() {
        
            var $ = jQuery;
            var last_key, key, show_list, hide_list, onChange, title, layout, hide;
        
            title   = $("#jform_params_document_title_link");
            layout  = $("#jform_request_layout");
            sort    = $("#jform_params_sort_categories");
            combine = $("#jform_request_combined");
            
            if (title.length && layout.length) {
                hide = {
                    "table:0": [
                        "#jform_params_embed_document"
                    ],
                    "table:download": [
                        "#jform_params_show_document_title",
                        "#jform_params_show_document_image",
                        "#jform_params_show_document_tags",
                        "#jform_params_show_document_created_by",
                        "#jform_params_show_document_description",
                        "#jform_params_show_document_modified",
                        "#jform_params_show_player",
                        "#jform_params_embed_document"
                    ],
                    "gallery:0": [
                        "#jform_params_embed_document"
                    ],
                    "gallery:slideshow": [
                        "#jform_params_embed_document"
                    ],
                    "gallery:download": [
                        "#jform_params_show_document_tags",
                        "#jform_params_show_document_created",
                        "#jform_params_show_document_created_by",
                        "#jform_params_show_document_description",
                        "#jform_params_show_document_modified",
                        "#jform_params_show_document_filename",
                        "#jform_params_show_document_size",
                        "#jform_params_show_document_hits",
                        "#jform_params_show_document_extension",
                        "#jform_params_show_player",
                        "#jform_params_show_slideshow",
                        "#jform_params_embed_document"
                    ],
                    "default:download": [
                        "#jform_params_show_player",
                        "#jform_params_allow_multi_download",
                        "#jform_params_embed_document"
                    ],
                    "default:details": [
                        "#jform_params_allow_multi_download",
                    ],
                    "default:0": [
                        "#jform_params_allow_multi_download",
                        "#jform_params_embed_document"
                    ],
                };
            
                onChange = function() {
                    key = layout.val()+\':\'+title.val();
                    show_list = (typeof hide[last_key] !== "undefined" && last_key !== key) ? hide[last_key] : [];
                    hide_list = typeof hide[key] !== "undefined" ? hide[key] : [];
            
                    $.each(show_list, function(i, selector) {
                        if ($.inArray(selector, hide_list)) {
                            $(selector).parents(".control-group").show();
                        }
                    });
            
                    $.each(hide_list, function(i, selector) {
                        $(selector).parents(".control-group").hide();
                    });
            
                    last_key = key;
                };
            
                title.change(onChange);
                layout.change(onChange);
            
                onChange();
            }

            var has_connect = "' . $has_connect . '";
            $("#jform_params_embed_document").prop("disabled", !has_connect);
            
            toggleSlideshow = function(disable = true) {
                $("#jform_params_document_title_link option").each(function (e) {
                    if ($(this).val() == "slideshow") {
                        $(this).attr("disabled", disable);
                        
                        if (disable && title.find(":selected").val() ==  "slideshow") {
                            $("#jform_params_document_title_link option:eq(0)").prop("selected", true);
                        }
                        
                        $(title).trigger("list:updated");
                    }
                });
            };

            toggleCategorySort = function(disable = true) {
                $("#jform_params_sort_categories option").each(function (e) {
                    if ($(this).val() == "custom") {
                        $(this).attr("disabled", disable);
                        
                        if (disable && sort.find(":selected").val() ==  "custom") {
                            $("#jform_params_sort_categories option:eq(0)").prop("selected", true);
                        }
                        
                        sort.trigger("list:updated");
                    }
                });
            };
            
            onLayoutChange = function() {
                if (layout.val() == "gallery") {
                    toggleSlideshow(false);
                } else {
                    toggleSlideshow(true);
                }
            };
            
            onCombineChange = function() {
                if ($(\'input[name="jform[request][combined]"]:checked\').val() == 1) {
                    toggleCategorySort(true);
                } else {
                    toggleCategorySort(false);
                }
            };
            
            layout.change(onLayoutChange);
            combine.change(onCombineChange);

            onLayoutChange();
            onCombineChange();
        }); 
        </script>
        ';

        return $html;
    }
}
