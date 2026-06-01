<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('formbehavior.chosen', 'select');

HTMLHelper::_('bootstrap.renderModal');
JLoader::register('JForm', JPATH_LIBRARIES . '/src/Form/Form.php');
HTMLHelper::script('media/com_dpe/js/onboardingusers.js');

// Load the main form
$xmlFormPath = JPATH_SITE . '/components/com_dpe/models/forms/usersonboarding.xml';
$form = Form::getInstance('usersonboarding', $xmlFormPath);

if (!$form) {
    echo Text::_('JERROR_LOADFORM_FAILED');
    return;
}

$app = Factory::getApplication();
$ucmId = $app->input->get('id');
$jobRoleClusterId = $this->item->cluster_id;
$defaultId = $app->input->get('defaultsetid');
$urlClusterId = $app->input->get('cluster_id');
$useDefault = $app->input->get('usedefault');


$ucmIdData = ($ucmId) ? ['ucmid' => $ucmId,'type_of_set'=>'jobtitleset'] : "0";

if($defaultId)
{
    $ucmIdData =  ['id' => $defaultId];
}

// Load the table
Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
$onboarduserdata = Table::getInstance('OnboardXref', 'DpeTable');
$onboarduserdata->load($ucmIdData);

$formdata = json_decode($onboarduserdata->formdata, true); // Convert to an associative array

$typeOfSet = $formdata['type_of_set'];
$jsonArray = json_encode($formdata['document_subform']);

?>
<script>
    jQuery(document).ready(function(){

        var fullUrl = window.location.href;
        var urlParams = new URLSearchParams(window.location.search);
        var paramValue = urlParams.get('usedefault');
        if (paramValue == 1)
        {
           jQuery('input[name="type_of_set"][value="defaultset"]').prop('checked', true);
           jQuery('#useronboardform').show();
           jQuery('#type_of_set1').val('defaultset');
        }

        var clusterId = "<?php echo ($formdata['clusterId'])?$formdata['clusterId']:$urlClusterId;?>";
        var jobClusterId = "<?php echo $jobRoleClusterId;?>";
        var ucmId = "<?php echo $ucmId;?>";
        // 
        if (clusterId && !jobClusterId)
        {
         jQuery('#cluster_id').val(clusterId);
         jQuery('#jform_com_tjucm_role_clusterclusterid').val(clusterId);
         jQuery('#jform_com_tjucm_role_clusterclusterid').trigger('chosen:updated');   
     }
     if ((ucmId !== '' )||( ucmId != 0))
     {
       jQuery('input[name="type_of_set"][value="jobtitleset"]').prop('checked', true);
       var currentUrl = new URL(window.location.href);
       var urlParams = new URLSearchParams(currentUrl.search);
       jQuery('#showdefumessage').hide();
          jQuery('#showjobmessage').show();

       if (urlParams.has('defaultsetid')) {
        urlParams.delete('defaultsetid');
        currentUrl.search = urlParams.toString();
    }

    jQuery('#jobroletitle').val(jQuery('#jform_com_tjucm_role_name').val());
    jQuery('input[name="type_of_set"][value="defaultset"]').prop('disabled', true);
}
jQuery('.table-responsive').removeClass('table-responsive');
})
    setTimeout(function(){
        var docArray = <?php echo $jsonArray; ?>;

        Object.values(docArray).forEach(function(item, index) {

            var lessonListElements = document.querySelectorAll('.lessonlist');

            if (lessonListElements[index]) {
                lessonListElements[index].value = item.lessonfilter;
                jQuery('#'+lessonListElements[index].id).val(item.lessonfilter);
                jQuery('#'+lessonListElements[index].id).trigger("chosen:updated");
            }
        });
    }, 3000)


    getUsers();
</script>
<?php


// Bind the data to the main form
if ($form && $formdata) {
    $form->bind($formdata);
}

?>
</br></br>
<div class="control-group shadow padding-20">

    <div class="jobactivitydesc">
    <?php echo Text::_('COM_DPE_JOB_ACTIVITY_DESCRIPTION');?>
</div>
<div class="control-group btn-group">
    <div class="controls ">
        <input type="radio" id="defaultset" name="type_of_set" value="defaultset" required <?php echo ($typeOfSet == 'defaultset')?'checked':''?>>
        <label for="defaultset"><?php echo Text::_('COM_DPE_DEFAULTSET_ADD_ACTIVITY_AS_DEFAULT_SET');?>

    </label><br>
</div> &nbsp &nbsp
<div class="controls">
    <input type="radio" id="jobtitleset" name="type_of_set" value="jobtitleset" <?php echo ($typeOfSet == 'jobtitleset')?'checked':''?>>
    <label for="jobtitleset"><?php echo Text::_('COM_DPE_DEFAULTSET_ADD_ACTIVITY_AS_JOBTITLE_SET');?></label><br>
</div>
</div>

<div>
    <p class="hide" id="showdefumessage"><span style="color:red">* </span>  This will create default activity set for the organisation.</p>
     <p class="hide" id="showjobmessage"><span style="color:red">* </span>  This will create an activity set for the Job title.</p>
</div>
<div class="control-group float-end btn-group mt-2">
    <div class="controls">
        <p class="btn btn-group btn-info text-white" onclick="getDefaultSet();"><?php echo Text::_('COM_DPE_DEFAULTSET_USE_DEFAULT_SET');?></p> &nbsp &nbsp
    </div>
    <div class="controls">
        <p class="btn btn-group btn-info text-white managedefault" onclick="mannagedefaultset();">
            <?php echo Text::_('COM_DPE_DEFAULTSET_MANNAGE_DEFAULT_SET');?> </p>
        </div>
    </div>
    <form action="#" method="post" name="useronboardform" id="useronboardform">

        <!-- Radio Buttons -->


        <br>
        <br><br>
        <!-- Onboard Title -->
        <div class="control-group onboardtitledata <?php echo ($typeOfSet == 'defaultset')?'':''?>?>">
            <div class="control-label">
                <?php echo $form->getLabel('onboardtitle'); ?>
            </div>
            <div class="controls">
                <?php echo $form->getInput('onboardtitle'); ?>
            </div>
        </div>

        <!-- eLearning Subform -->
        <div class="control-group shadow padding-20">
            <div class="control-label">
                <?php echo $form->getLabel('elearning_subform'); ?>
            </div>
            <div class="controls">
                <?php echo $form->getInput('elearning_subform'); ?>
            </div>
        </div>

        <!-- Document Subform -->
        <div class="control-group  shadow padding-20">
            <div class="control-label">
                <?php echo $form->getLabel('document_subform'); ?>
            </div>
            <div class="controls">
                <?php echo $form->getInput('document_subform'); ?>
            </div>
        </div>

        <!-- ToDo Subform -->
        <div class="control-group shadow padding-20">
            <div class="control-label">
                <?php echo $form->getLabel('todo_subform'); ?>
            </div>
            <div class="controls">
                <?php echo $form->getInput('todo_subform'); ?>
            </div>

        </div>

        <!-- User Assignment Status -->
        <div class="control-group">
            <div class="control-label">
                <?php echo $form->getLabel('userassignmentstatus'); ?>
            </div>
            <div class="controls">
                <?php echo $form->getInput('userassignmentstatus'); ?>
            </div>
        </div>

        <!-- Onboarding Selection -->
        <div class="control-group">
            <div class="control-label">
                <?php echo $form->getLabel('selectuseforonboarding'); ?>
            </div>
            <div class="controls">
                <?php echo $form->getInput('selectuseforonboarding'); ?>
            </div>
        </div>
        <div class="control-group">
            <div class="control-label">
                <?php echo $form->getLabel('start_date'); ?>
            </div>
            <div class="controls">
                <?php echo $form->getInput('start_date'); ?>
            </div>
        </div>

        <!-- Submit Button -->
        <a class="btn btn-primary" onclick="saveonboardusers();">Submit</a>

        <?php echo JHtml::_('form.token'); 
        $speicificUSerData = json_encode($formdata['selectuseforonboarding']);
        ?>
        <input type="hidden" id="ucmrecordId" name="ucmrecordId" value="">
        <input type="hidden" id="onboardsetId" name="onboardsetId" value="<?php echo ($useDefault==1)?'':$onboarduserdata->id;?>">
        <input type="hidden" name="specificuserdata" id="specificuserdata"value=<?php echo $speicificUSerData;?>>
        <input type="hidden" name="clusterId" id="clusterId" value=''>
        <input type="hidden" name="jobroletitle" id ="jobroletitle" value="">
        <input type="hidden" name="type_of_set" id ="type_of_set1" value="">

    </form>

    </div>

    <script type="text/javascript">
        jQuery(document).ready(function(){

             selectedValue = jQuery('input[name="type_of_set"]:checked').val();

             if(jQuery('#userassignmentstatus').val() != 'specificusers')
            {
               setTimeout(function(){
                jQuery('#selectuseforonboarding_chosen').hide();
                jQuery('#selectuseforonboarding-lbl').prop('style','display:none !important');
               },2000)
                
            }

            if (!jQuery('#recordId').val() && selectedValue != 'defaultset')
            {
                jQuery('#useronboardform').hide();
            }else if(selectedValue == 'defaultset')
            {
                 jQuery('#type_of_set1').val(jQuery('#defaultset').val());
                jQuery('.input-append').show();
                jQuery('#start_date-lbl').show();
                jQuery('#userassignmentstatus_chosen').css('display','');
                jQuery('#userassignmentstatus-lbl').attr('style','');               
                jQuery('.onboardtitledata').show();
            }

            var typeofacticity = jQuery('input[name="type_of_set"]:checked').val();

            if (typeofacticity == 'jobtitleset') {
                 jQuery('#type_of_set1').val(jQuery('#jobtitleset').val());

                jQuery('#userassignmentstatus_chosen').css('display','none');
                jQuery('#userassignmentstatus-lbl').css('display','none');
                setTimeout(function(){
                jQuery('#userassignmentstatus_chosen').css('display','none');
                jQuery('#userassignmentstatus-lbl').attr('style','display:none !important');
                jQuery('#selectuseforonboarding_chosen').css('display','none');
                jQuery('#selectuseforonboarding-lbl').css('display','none !important');
                },2000)
            }

            jQuery('#defaultset').click(function(){

                jQuery('#useronboardform').show();
                jQuery('#jform_com_tjucm_role_name').hide();
                jQuery('#jform_com_tjucm_role_name-lbl').attr('style', 'display:none !important');
                jQuery('.action-btns').hide();
                jQuery('#type_of_set').val(jQuery('#defaultset').val());
                jQuery('.jobactivitydesc').css('margin-top','75px');
                jQuery('#userassignmentstatus_chosen').show();
                jQuery('#userassignmentstatus-lbl').show();
                jQuery('#showdefumessage').show();
                jQuery('#showjobmessage').hide();
                jQuery('#type_of_set1').val(jQuery('#defaultset').val());
            })
            jQuery('#jobtitleset').click(function(){

                var selectedValue = jQuery('input[name="type_of_set"]:checked').val();

                if (selectedValue === 'jobtitleset') {

                }

                jQuery('#jform_com_tjucm_role_name').show();
                jQuery('#jform_com_tjucm_role_name-lbl').show();
                jQuery('.action-btns').show();
                jQuery('#type_of_set1').val(jQuery('#jobtitleset').val());
                jQuery('.jobactivitydesc').css('margin-top','14px');

                if(jQuery('#recordId').val()){

                    jQuery('#useronboardform').show();
                    jQuery('#userassignmentstatus_chosen').hide();
                    jQuery('#userassignmentstatus-lbl').hide();
                    jQuery('#showdefumessage').hide();
                    jQuery('#showjobmessage').show();
                }else
                {  
                   jQuery('#useronboardform').show(); 
                   jQuery('.onboardtitledata').hide();
       
               var msg = "Please add a job title and save the form before adding activities to the job title."
               messageDisplay(msg, 'primary');
                jQuery('.pull-right').css({'border': '2px solid red', 'padding': '20px'});
                setTimeout(function(){
                    jQuery('.pull-right').css({'border': '0px solid red', 'padding': '20px'});
                    },3000)

               return false;
           }

       })

            if (jQuery('#recordId').val().length > 0) {
                jQuery('#ucmrecordId').val(jQuery('#recordId').val());
                jQuery('#onboardsetId').val(0);
            }
        });

        function getDefaultSet()
        {  
           var typeofset = jQuery('input[name="type_of_set"]:checked').val();

           if(!typeofset)
           {
            jQuery('#defaultset').css({
                'outline': '2px solid red',   
                'border-radius': '50%',       
                'width': '12px',              
                'height': '12px',              
                'appearance': 'none'          
            });;
            jQuery('#jobtitleset').css({
                'outline': '2px solid red',   
                'border-radius': '50%',       
                'width': '12px',              
                'height': '12px',              
                'appearance': 'none'          
            });

            setTimeout(function(){
                jQuery('#defaultset').removeAttr('style');
                jQuery('#jobtitleset').removeAttr('style');
            },3000)
            var msg = "Please select an activity first."
            messageDisplay(msg, 'error');

            return false;
        }
        if(jQuery('#jform_com_tjucm_role_clusterclusterid').val().length < 1)
        {
            var msg = "Please select an organisation first."
            messageDisplay(msg, 'error');
            return false;
        }
        var url = Joomla.getOptions('system.paths').base+'/index.php?option=com_dpe&view=import&layout=onboarduserspopup&tmpl=component&cluster_id='+jQuery('#jform_com_tjucm_role_clusterclusterid').val();
        var wwidth = jQuery(window).width() -1050;
        var wheight = jQuery(window).height() - 350;
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
            onClose: function()
            {

            }

        });
    }
    function mannagedefaultset()
    {   
        if(jQuery('#jform_com_tjucm_role_clusterclusterid').val().length < 1)
        {
            var msg = "Please select an organisation first."
            messageDisplay(msg, 'error');
            return false;
        }
        var url = Joomla.getOptions('system.paths').base+'/index.php?option=com_dpe&view=import&layout=onboarduserspopup&tmpl=component&mannagedefaultset=1&cluster_id='+jQuery('#jform_com_tjucm_role_clusterclusterid').val();
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

    function messageDisplay(msg, type){

      jQuery('<div id="system-message-container"></div>').appendTo('#system-message-container');
      Joomla.renderMessages({[type] : [msg]}); 
      setTimeout(function() {
       jQuery('joomla-alert').fadeOut('slow', function() {
        $(this).remove();
    });
   }, 10000); 
  }
</script>
