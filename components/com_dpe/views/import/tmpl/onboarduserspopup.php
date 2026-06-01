<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
$doc = Factory::getDocument();
// DPE hack
$doc->addStyleSheet(Uri::root() . 'media/system/css/modal.css');
// $doc->addScript(Uri::root() . 'media/system/js/mootools-core.js');
// $doc->addScript(Uri::root() . 'media/system/js/mootools-more.js');
$doc->addScript(Uri::root() . 'media/system/js/modal.js');


$document = Factory::getDocument();
$document->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
$document->addStyleSheet('templates/shaper_helix3/css/custom.css');
HTMLHelper::_('script', 'media/system/js/messages.min.js');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('bootstrap.renderModal');

$clusterId =  Factory::getApplication()->input->get('cluster_id');
$mannage =  Factory::getApplication()->input->get('mannagedefaultset');
$usetodotemplate =  Factory::getApplication()->input->get('usetodotemplate');

?>

<style>
    .centerloader {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        margin: auto;
    }
    #ropCopyLoader {
        border: 8px solid #22b8f0;
        border-radius: 50%;
        border-top: 8px solid #ccc;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
    }

</style>
<div id="ropCopyLoader" class="centerloader hide"></div>
<br><br>
<div id="system-message-container"></div>

<a id="sbox-btn-closed" class="btn btn-primary" href="#" role="button" aria-controls="sbox-window">Close</a>

<?php
if((!$mannage )&& ($usetodotemplate != 1)){

    BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_DPE/models');
    $model = BaseDatabaseModel::getInstance('Users', 'DpeModel');
    $allDefaultSet = $model->getAllDefaultSet($clusterId);

    $options = array();
    $options[] = "<divclass='btn-group'<label>Choose Default set to use</label>&nbsp&nbsp&nbsp<select id='selectdefaultset' class='chosen-select w-50'><option value=''>Select Default Set</option>";

    foreach($allDefaultSet as $defaultset)
    {


        $options[] = "<option value='".$defaultset['id']."'>".$defaultset['title']."</option>";
    }

    $options[] = "</select></div>";

    echo implode("\n", $options);
    ?>
    <script>
        jQuery(document).ready(function(){

            jQuery('#selectdefaultset').chosen();

            jQuery('#selectdefaultset').on('change', function() {
                var selectedValue = jQuery(this).val();

                if (selectedValue) {
            var parentUrl = window.parent.location.href;
            var newUrl = updateQueryStringParameter(parentUrl, 'defaultsetid', selectedValue);
            newUrl = updateQueryStringParameter(newUrl, 'usedefault', '1');
            window.parent.location.href = newUrl;
            window.close();
        }
    });

       
        });
    </script>

<?php }else if($usetodotemplate == 1){
    BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_DPE/models');
    $model = BaseDatabaseModel::getInstance('Users', 'DpeModel');
    $allTemplates = $model->getAllTododTemplateByCluster($clusterId);
    $generatedId =  Factory::getApplication()->input->get('generatedId');

    $options = array();
    $options[] = "<divclass='btn-group'<label>Choose Template to use</label>&nbsp&nbsp&nbsp<select id='selcttemplate' class='chosen-select w-50'><option value=''>Select Template</option>";

    foreach($allTemplates as $allTemplate)
    {

        $options[] = "<option value='".$allTemplate['id']."'>".$allTemplate['todo_title']."</option>";
    }

    $options[] = "</select></div>";

    echo implode("\n", $options);

    ?>
    <script>
        jQuery(document).ready(function(){
            jQuery('#selectdefaultset').chosen();

            jQuery('#selcttemplate').on('change', function() {
    var selectedValue = parseInt(jQuery(this).val()); // Convert to integer
    var generatedId = "<?php echo $generatedId; ?>";
    var alltemplateData = <?php echo json_encode($allTemplates); ?>;

    if (selectedValue) {
        var result = alltemplateData.filter(function(item) {
            return item.id === selectedValue;
        });

        if (result.length > 0) {
            window.parent.jQuery('#'+generatedId+'__todotitle').val(result[0].todo_title);
            window.parent.jQuery('#'+generatedId+'__tododescription').val(result[0].todo_description);
            window.parent. jQuery('#'+generatedId+'__todocompletionday').val(result[0].todo_completion);
            window.parent.jQuery('#'+generatedId+'__todoreminderday').val(result[0].todo_reminder);

          var msg = "Template added to form succesfully" ;
            jQuery('<div id="system-message-container"></div>').appendTo('#system-message-container');
            Joomla.renderMessages({'success' : [msg]}); 
           setTimeout(function() {
    jQuery('#system-message-container').fadeOut('slow', function() {
        jQuery(this).remove(); // Optionally remove it from the DOM after fading out
    });
}, 5000); // 5000 milliseconds = 5 seconds


        } else {
            console.log("No matching template found."); // Debug if no match is found
        }
    }
});

        });


    </script>
<?php }else{?>

    <h3><?php echo Text::_("COM_DPE_MANNAGE_DEFAULT_SET_TITLE");?></h3>
   

    <?php
    BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_DPE/models');
    $model = BaseDatabaseModel::getInstance('Users', 'DpeModel');
    $allDefaultSet = $model->getAllDefaultSet($clusterId);

    ?>
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-striped" id="">

                    <thead>
                        <tr>

                            <!-- TODO- copy and copy to other feature is not fully stable hence relate buttons are hidden-->
                            <!-- <th width="1%" class="">
                                <input type="checkbox" name="checkall-toggle" value="" title="<?php// echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
                            </th> -->

                            <th width="2%">
                                <?php echo HTMLHelper::_('grid.sort', 'COM_TJUCM_ITEMS_ID', 'a.id', $listDirn, $listOrder); ?>
                            </th>

                            <th>
                               <?php echo HTMLHelper::_('grid.sort', 'COM_TJUCM_DATE_LOGGED', 'a.created_date', $listDirn, $listOrder); ?>
                           </th>

                           <th class="center" width="10%">
                            <?php echo Text::_('COM_DPE_DEFULT_SET_TITLE'); ?>
                        </th>          

                        <th class="center" width="10%">
                            <?php echo Text::_('COM_DPE_DEFULT_SET_ACTION'); ?>
                        </th>
                        <th width="2%">
                            <?php echo Text::_('COM_DPE_SET_MAIN_DEFULT_SET');?>
                        </th>

                    </tr>
                </thead>

                <tbody>
                    <div class="tjucm-wrapper">
                        <?php 
                        foreach($allDefaultSet as $allDefaultSetData)
                        {
                            ?>
                            <tr>
                               <!--  <td class="center " width="1%">
                                    <?php //echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                </td> -->
                                <td class="center text-nowrap" width="10%">
                                   <?php echo $allDefaultSetData['id']; ?>
                               </td>
                               <td class="center text-nowrap" width="10%"> <?php echo $allDefaultSetData['created_date']; ?></td>
                               <td class="center text-nowrap" width="10%">
                                <?php echo $allDefaultSetData['title']; ?>
                            </td>
                            <td class="center text-nowrap">
                                <span class="">
                                    <a class="btn btn-info actionbutton"style="background: #ffffff00;
                                    border: none;" href="#" type="button" title="<?php //echo Text::_('COM_TJUCM_EDIT_ITEM_TITLE');?>" onclick="editonboard(<?php echo $allDefaultSetData['id']; ?>);">
                                   <i class="fas fa-edit"></i><?php //echo Text::_('COM_TJUCM_EDIT_ITEM');?>
                                </a>
                            </span>
                            <span class="">
                                <a class="btn btn-info actionbutton" style="background: #ffffff00;
                                border: none;" href="#" type="button" title="<?php //echo Text::_('COM_TJUCM_EDIT_ITEM_TITLE');?>"onclick="deleteOnboard(<?php echo $allDefaultSetData['id']; ?>);">
                                <i class="icon-trash" aria-hidden="true"></i><?php //echo Text::_('COM_TJUCM_EDIT_ITEM');?>
                            </a>
                        </span>
                    </td>
                    <td class="center text-nowrap" width="10%">
                        <input 
                        type="checkbox" 
                        id="setasmaindefault" 
                        name="setasmaindefault" 
                        value="1" 
                        <?php echo ($allDefaultSetData['set_as_main_default_set'] == 1) ? 'checked' : ''; ?> 
                        class="maindefault" 
                        onclick="setasmaindefault(this, '<?php echo $allDefaultSetData['id']; ?>', '<?php echo $clusterId; ?>')"
                        >
                    </td>

                </tr>
                <?php
            }
            ?>
        </tbody>
    </div>
</table>
</div>
<div class="pager" id="pagination">
    <?php //echo //$this->pagination->getPagesLinks(); ?>
    <!-- <hr class="hr hr-condensed"/> -->
</div>
</div>
</div>
<?php
}
?>
<script type="text/javascript">


    jQuery(document).ready(function(){

        jQuery('#sbox-btn-closed').prop('style','    margin-top: 0px; width: 66px; margin-right: 41px !important; float: right;');jQuery('#sbox-btn-closed').click(function(){window.parent.SqueezeBox.close();})
    })

    function editonboard($id)
    {

        var selectedValue = $id;
        if (selectedValue) {
 var url =Joomla.getOptions('system.paths').base+'/index.php?option=com_dpe&view=import&layout=userdefaultsetmanage&tmpl=component&mannagedefaultset=1&defaultsetID='+selectedValue;
      var wwidth = jQuery(window).width() -50;
      var wheight = jQuery(window).height() - 50;
      SqueezeBox.open(url, {
        handler: 'iframe',
        closable: true,
        size: {
            x: wwidth,
            y: wheight
        },
        sizeLoading: {
            x: wwidth,
            y: wheight
        },
        classWindow: '',
         onOpen: function() {
             jQuery('#loader').show();
            // Monitor when the iframe content is fully loaded
            jQuery('iframe').on('load', function() {
                // Hide the loader once the iframe content is fully loaded
                jQuery('#loader').hide();
            });
        },
        onClose: function()
        {

        }

    });


}
}

function deleteOnboard(id)
{
    var result = confirm("Are you sure you want to delete the Default Set?");
    
    // Check the user's response
    if (result) {

         jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=users.deleteDefaultSet",
        type: 'POST',
        data:{
            id: id
        },
        dataType:"json",
        headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')}, 
        success: function (response) {

            var msg = response.data ;
            jQuery('<div id="system-message-container"></div>').appendTo('#system-message-container');
            Joomla.renderMessages({'success' : [msg]}); 
            setTimeout(function() {
                jQuery('#system-message-container').fadeOut('slow', function() {
                    jQuery(this).remove(); // Optionally remove it from the DOM after fading out
                });
                location.reload();
            }, 5000);
        }
    });
    }
   

}

function updateQueryStringParameter(uri, key, value) {
    var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
    var separator = uri.indexOf('?') !== -1 ? "&" : "?";
    if (uri.match(re)) {
        return uri.replace(re, '$1' + key + "=" + value + '$2');
    }
    else {
        return uri + separator + key + "=" + value;
    }
}

function setasmaindefault(checkbox, defaultsetId, clusterId) {

    if (checkbox.checked) {
        // Disable all other checkboxes
        var checkboxes = document.querySelectorAll('.maindefault');
        checkboxes.forEach(function(cb) {
            if (cb !== checkbox) {
                cb.disabled = true;
            }
        });
    } else {
        // Enable all checkboxes
        var checkboxes = document.querySelectorAll('.maindefault');
        checkboxes.forEach(function(cb) {
            cb.disabled = false;
        });
        var uncheck = 1;
    }

    jQuery.ajax({
        url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=users.makeMainDefaultSet",
        type: 'POST',
        data: {'defaultsetId': defaultsetId,'clusterId': clusterId},
        dataType: "json",
        headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')},
        success: function (response) {

            if (uncheck)
            {
                var msg = 'Main default set is removed successfully';
            }else
            {
                var msg = response.data;
            }
            
            jQuery('<div id="system-message-container"></div>').appendTo('#system-message-container');
            Joomla.renderMessages({'success' : [msg]}); 
            setTimeout(function(){
                location.reload();
            },1500)
        },
        error: function (xhr, status, error) {
            // Handle any errors
            console.log('Error:', status, error);
        }
    });
}

</script>