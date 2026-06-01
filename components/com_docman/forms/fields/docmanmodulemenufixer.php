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
class JFormFieldDocmanmodulemenufixer extends JFormField
{
    protected $type = 'Docmanmodulemenufixer';

    protected function getInput()
    {
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
            
            mod_categories_sort = $("#jform_params_sort");
            show_subcategory    = $(\'input[name="jform[params][subcategories]"]\');
            
            // Toggle ordering in docman modules
            toggleCategorySort = function(disable = true) {
                $("#jform_params_sort option").each(function (e) {
                    if ($(this).val() == "reverse_title") {
                        $(this).attr("disabled", disable);
                        
                        if (disable && mod_categories_sort.find(":selected").val() ==  "reverse_title") {
                            $("#jform_params_sort option:eq(0)").prop("selected", true);
                        }
                        
                        mod_categories_sort.trigger("list:updated");
                    }
                    
                    if ($(this).val() == "reverse_created_on") {
                        $(this).attr("disabled", disable);
                        
                        if (disable && mod_categories_sort.find(":selected").val() ==  "reverse_created_on") {
                            $("#jform_params_sort option:eq(0)").prop("selected", true);
                        }
                        
                        mod_categories_sort.trigger("list:updated");
                    }
                });
            };
            
            onShowSubCategoryChange = function() {
                if ($(\'input[name="jform[params][subcategories]"]:checked\').val() == 1) {
                    toggleCategorySort(true);
                } else {
                    toggleCategorySort(false);
                }
            };
            
            show_subcategory.change(onShowSubCategoryChange);
            onShowSubCategoryChange();
        }); 
        </script>
        ';

        return $html;
    }
}
