<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('formbehavior.chosen', 'select');

HTMLHelper::_('bootstrap.renderModal');
JLoader::register('JForm', JPATH_LIBRARIES . '/src/Form/Form.php');

// Load the main form
$xmlFormPath = JPATH_SITE . '/components/com_dpe/models/forms/usersonboarding.xml';
$form = Form::getInstance('usersonboarding', $xmlFormPath);

if (!$form) {
    echo Text::_('JERROR_LOADFORM_FAILED');
    return;
}

    $doc = Factory::getDocument();
    $doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
    $doc->addScript('templates/shaper_helix3/js/main.js');
    $doc->addScript(Uri::root() . 'media/system/js/messages.min.js');


$app = Factory::getApplication();
$ucmId = $app->input->get('id');
$defaultId = $app->input->get('defaultsetID');
$useDefault = $app->input->get('usedefault');

if($defaultId)
{
    $ucmIdData =  ['id' => $defaultId];
}

// Load the table
Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
$onboarduserdata = Table::getInstance('OnboardXref', 'DpeTable');
$onboarduserdata->load($ucmIdData);

$formdata = json_decode($onboarduserdata->formdata, true); // Convert to an associative array

$clusterId  = $formdata['cluster_id'];
$typeOfSet = $formdata['type_of_set'];
$jsonArray = json_encode($formdata['document_subform']);


?>
<script>
    jQuery(document).ready(function(){

        var clusterId = "<?php echo ($formdata['clusterId'])?$formdata['clusterId']:$clusterId ?>";
        jQuery('#clusterId').val('<?php echo ($formdata['clusterId'])?>');
        var jobClusterId = "<?php echo $jobRoleClusterId;?>";
        var ucmId = "<?php echo $ucmId;?>";
 })

 function getUsers() {
   var userId='';
        // Get the selected value
        var selectedValue = jQuery('#userassignmentstatus_chosen').val();
        var ajaxUrl = Joomla.getOptions('system.paths').base + "/index.php?option=com_cluster&task=clusterusers.getUsersByClientId&format=json";
        var dataFields = {'cluster_id':jQuery('#clusterId').val(),'user_id':userId};
        var assigneeFieldId = 'selectuseforonboarding';
        jQuery('#'+assigneeFieldId+', .chzn-results').empty();
        jQuery.ajax({
         url: ajaxUrl,
         type: 'POST',
         data: dataFields,
         dataType:"json",
         success: function (response) {
            var selectOption = '';
            var op = '';
            var data = response.data;

            var specificuserdatas = jQuery('#specificuserdata').val();

// Parse the JSON array correctly
specificuserdatas = JSON.parse(specificuserdatas);

for (var index = 0; index < data.length; ++index) {
  var selectOption = '';

  if (specificuserdatas && specificuserdatas.length > 0) {
        // Convert the value to a string for comparison, since your data might be in string format
        var valueToCheck = data[index].value.toString();

        // Check if the value is in the array
        if (jQuery.inArray(valueToCheck, specificuserdatas) !== -1) {
         selectOption = ' selected="selected" ';
      }
   }

    // Create the option and append it to the select element
    var op = "<option value='" + data[index].value + "' " + selectOption + " >" + data[index]['text'] + "</option>";
    jQuery('#' + assigneeFieldId).append(op);
 }

 /* IMP : to update to chz-done selects*/
 jQuery("#"+assigneeFieldId).trigger("chosen:updated");
}
})
};

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
<div id="system-message-container"></div>

<form action="#" method="post" name="useronboardform" id="useronboardform">
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
    <input type="hidden" id="onboardsetId" name="onboardsetId" value="<?php echo ($useDefault==1)?'':$onboarduserdata->id;?>">
    <input type="hidden" name="specificuserdata" id="specificuserdata"value=<?php echo $speicificUSerData;?>>
    <input type="hidden" name="clusterId" id="clusterId" value=''>
    <input type="hidden" name="jobroletitle" id ="jobroletitle" value="">
    <input type="hidden" name="type_of_set" id ="type_of_set" value="">

</form>

<script type="text/javascript">

 jQuery(document).ready(function(){
    jQuery('.table-responsive').removeClass('table-responsive');
   jQuery('.onboardtitledata').css("width","40%");
   jQuery('.fa-info-circle').css('display','none');

   jQuery('.input-append').hide();
   jQuery('#start_date-lbl').attr('style', 'display: none !important');
   jQuery('#start_date_btn').css('background', 'white');
   jQuery('.input-append').css('width','41%');
   
   setTimeout(function() {
     jQuery('[id*="coursefilter_chosen"]').each(function() {
      var elementId = jQuery(this).attr('id');
      console.log(elementId);
      jQuery('#'+elementId).closest('.onboardusers').attr('style', 'width: 45% !important;');
      jQuery('#'+elementId).attr('style', 'width: 40%');

   });       

     jQuery('[id*="lessonfilter_chosen"]').each(function() {
      var elementId = jQuery(this).attr('id');
      jQuery('#'+elementId).closest('.onboardusers').attr('style', 'width: 45% !important;');
      jQuery('#'+elementId).attr('style', 'width: 40%');
   });   
     jQuery('.titletodo').closest('.control-group').attr('style', 'width: 45% !important;');

     jQuery('.courseday').closest('.control-group').css('float', 'right');
     jQuery('.courseday').closest('.control-group').css('margin-top', '-68px');
     jQuery('.courseday').closest('.control-group').css('width', '45%');
     jQuery('.titletodo').attr('style', 'width: 100% !important;');
     jQuery('.completiondaytodo').closest('.control-group').css('width', '46.5%');
     jQuery('.completiondaytodo').attr('style', 'margin-left:-1px');
     jQuery('.descriptiontodo').closest('.control-group').css('float', 'right');
     jQuery('.descriptiontodo').closest('.control-group').css('margin-top', '-68px');
     jQuery('.descriptiontodo').closest('.control-group').css('width', '45%');
     jQuery('.reminderday').closest('.control-group').css('float', 'right');
     jQuery('.reminderday').closest('.control-group').css('margin-top', '-68px');
     jQuery('.reminderday').closest('.control-group').css('width', '45%');
     jQuery('#userassignmentstatus_chosen').attr('style', 'width: 41% !important;');
     jQuery('#selectuseforonboarding_chosen').hide();

     jQuery('.reminderday').after(`
      <div>
      <p class="btn btn-primary todotemplate" onclick= "saveastemplate(this) "style="width:36% !important;margin-top: 6Px;"value="">Save as Template</p>
      </div>
      `);
     jQuery('.reminderday').after(`
      <div>
      <p class="btn btn-primary todotemplate" onclick= "usetemplate(this) "style="width:36% !important; float: right;
      margin-top: 6Px;"value="">Use Templates</p>
      </div>
      `);

     var assignedUsers = jQuery('#userassignmentstatus').chosen().val();

     if(assignedUsers === 'specificusers')
     {
      getUsers();
      jQuery('#selectuseforonboarding_chosen').show();

   }
}, 500);   

   getlessons();

})

 jQuery(document).on('click', '.group-add', function (event, row) {
  setTimeout(function() {
     jQuery('.courselist').chosen();
     var elementId = '' ;
     jQuery('[id*="coursefilter_chosen"]').each(function() {
      elementId = jQuery(this).attr('id');

      jQuery('#'+elementId).closest('.onboardusers').attr('style', 'width: 45% !important;');
      jQuery('#'+elementId).attr('style', 'width: 40%');

   });    jQuery('[id*="lessonfilter_chosen"]').each(function() {
      var elementId = jQuery(this).attr('id');
      jQuery('#'+elementId).closest('.onboardusers').attr('style', 'width: 45% !important;');
      jQuery('#'+elementId).attr('style', 'width: 40%');


   });  
   jQuery('.courseday').closest('.control-group').css('float', 'right');
   jQuery('.courseday').closest('.control-group').css('margin-top', '-68px');
   jQuery('.courseday').closest('.control-group').css('width', '45%');
   jQuery('.titletodo').closest('.control-group').attr('style', 'width: 45% !important;');
   jQuery('.titletodo').attr('style', 'width: 100% !important;');
   jQuery('.completiondaytodo').attr('style', 'margin-left:-1px');
   jQuery('.completiondaytodo').closest('.control-group').css('width', '46.2%');
   jQuery('.descriptiontodo').closest('.control-group').css('float', 'right');
   jQuery('.descriptiontodo').closest('.control-group').css('margin-top', '-68px');
   jQuery('.descriptiontodo').closest('.control-group').css('width', '45%');
   jQuery('.reminderday').closest('.control-group').css('float', 'right');
   jQuery('.reminderday').closest('.control-group').css('margin-top', '-68px');
   jQuery('.reminderday').closest('.control-group').css('width', '45%');
   getlessons();
   jQuery('.fa-info-circle').css('display','none');
   jQuery('.todotemplate').remove();
   jQuery('.reminderday').after(`
      <div>
      <p class="btn btn-primary todotemplate" onclick= "saveastemplate(this) "style="width:36% !important;margin-top: 6Px;"value="">Save as Template</p>
      </div>
      `);
   jQuery('.reminderday').after(`
      <div>
      <p class="btn btn-primary todotemplate" onclick= "usetemplate(this) "style="width:36% !important; float: right;
      margin-top: 6Px; "value="">Use Templates</p>
      </div>
      `);
}, 450);
});


 jQuery(document).ready(function(){

   jQuery('#userassignmentstatus').change(function() {
      var userassignstatus = jQuery('#userassignmentstatus').chosen().val();
      if(userassignstatus == 'specificusers'){

         jQuery('#selectuseforonboarding_chosen').show();

      }else
      {
         jQuery('#selectuseforonboarding_chosen').hide();
      }
   });


   jQuery('#userassignmentstatus').change(function() {

    getUsers();

 });

});



     function getlessons()
     {     
      var clusterId = jQuery('#clusterId').val();

      if (clusterId == 0)
      {
         return false;
      }

      jQuery.ajax({
         url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&format=json",
         data: "&task=lesson.getLessonsAsPerCluster&clusterId=" + clusterId,
         dataType: 'json',
         headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')},
         success: function(data)
         {
           var lessons = data.data;
           var nextDiv = '';
           jQuery('.lessonlist').each(function(index) {

              nextDiv = jQuery(this).next('div');
              var nextDivId = nextDiv.length ? nextDiv.attr('id') : 'No next sibling';

             // if (jQuery('#'+nextDivId+' .chosen-single span').text() == 'Select Document')
             // {
               var nextDivId = nextDivId.replace('_chosen', '');
               var $select = jQuery('#'+nextDivId);
               $select.empty(); // Clear existing options
               var firstOption = jQuery('<option>', {
                  text: 'Select Document',
                  disabled: true,
                  selected: true
               });
               $select.append(firstOption);

               if (data.data != 'fail')
               {
                lessons.forEach(function(item) {
                  var option = jQuery('<option>', {
                   value: item.value,
                   text: item.text,
                   disabled: item.disable
                });
                  $select.append(option);
               });
             }
             $select.trigger('chosen:updated'); 
           // }
        });
        }
     });
      
   }

   function saveonboardusers()
   {

       // Serialize form data
       var formData = new FormData($('#useronboardform')[0]);
       var clusterId = jQuery('#clusterId').val();
       var title = jQuery('#onboardtitle').val();
       var msg = '';   
      
 if (clusterId == 0 || title.length < 1)
 {

  if (clusterId == 0)
  {
     msg = 'Please select an organisation to save the default set';
     message(msg);
     return false;
  }

  if (title.length < 1 )
  {
   var selectedValue = jQuery('input[name="type_of_set"]:checked').val();

   if (selectedValue === 'defaultset')
   {
      msg = 'Please add a title to save the default set';
      message(msg);
      return false;
   }
}
}
var assignedUsers = jQuery('#userassignmentstatus').chosen().val();

if(assignedUsers === 'specificusers')
{
   if(jQuery('#selectuseforonboarding').chosen().val().length < 1)
   {
      msg = 'Please add at leaset one user to save the default set.';
      message(msg);
      return false;
   }
}
var userConfirmation = confirm("Are you sure you want to save?");

if (userConfirmation == false) {

   return false;
}
jQuery.ajax({
  url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=tjucm.saveonboardusers",
  type: 'POST',
  data: formData,
       processData: false,  // Important! Prevent jQuery from processing the data
       contentType: false,  // Important! Prevent jQuery from setting the content type
       success: function(response) {
        // Handle success
        if(JSON.parse(response).data.success == true)
        {
         var msg = 'Default set saved successfully';
         jQuery('<div id="system-message-container"></div>').appendTo('#system-message-container');
         Joomla.renderMessages({'success' : [msg]}); 
         jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
         setTimeout(function(){
             window.parent.SqueezeBox.close();
         },2000)
        
         
      }else{
        var msg = 'Something Wrong! plesae try again';
        jQuery('<div id="system-message-container"></div>').appendTo('#system-message-container');
        Joomla.renderMessages({'error' : [msg]}); 
        jQuery('html, body').animate({ scrollTop: 0 }, 'slow');

     }       
  },
  error: function(jqXHR, textStatus, errorThrown) {
   var msg = 'Error saving default set data:', textStatus, errorThrown;
   jQuery('<div id="system-message-container"></div>').appendTo('#system-message-container');
   Joomla.renderMessages({'success' : [msg]}); 
   jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
   return false;
}
});
}

function getUsersByCluster()
{
   jQuery.ajax({
      url: Joomla.getOptions('system.paths').base + "/index.php?option=com_sla&task=slaactivity.getUsersByClusterId&format=json",
      type: 'POST',
      data:{
         license: dataFields.licenseId
      },
      dataType:"json",
      headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')}, 
      success: function (response) {

         let selectOption = '';
         let op = '';
         let data = response.data;

         if (data != null)
         {
            for(index = 0; index < data.length; ++index)
            {
               selectOption = '';
               if (dataFields.userId == data[index].value)
               {
                  selectOption = ' selected="selected" ';
               }
               op="<option value='"+data[index].value+"' "+selectOption+" > " + data[index]['text'] + "</option>" ;
               jQuery('.cluster_user').append(op);
            }

            /* IMP : to update to chz-done selects*/
            jQuery(".cluster_user").trigger("chosen:updated");
         }
      }
   });
}

function message(msg)
{
  jQuery('<div id="system-message-container"></div>').appendTo('#system-message-container');
  Joomla.renderMessages({'error' : [msg]}); 
  jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
}

function usetemplate(element)
{  
  var $element = $(element);

    // Find the closest ancestor `div` that contains an element with class `reminderday`
    var closestAncestor = $element.closest('.controls').find('.reminderday');
    
    // Get the ID of the closest ancestor div
    var ancestorId = closestAncestor.attr('id');
    var baseId = ancestorId.split('__')[0];
     var clusterId = jQuery('#clusterId').val();

    if (clusterId == 0 )
    {
      alert("Please select the organisation first");
      return false;
   }
    var url = Joomla.getOptions('system.paths').base+'/index.php?option=com_dpe&view=import&layout=onboarduserspopup&tmpl=component&usetodotemplate=1&generatedId='+baseId+'&cluster_id='+'';
    var wwidth = jQuery(window).width() -850;
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
 function saveastemplate(element) {
    // Convert the element to a jQuery object
    var $element = $(element);
    // Find the closest ancestor `div` that contains an element with class `reminderday`
    var closestAncestor = $element.closest('.controls').find('.reminderday');
    
    // Get the ID of the closest ancestor div
    var ancestorId = closestAncestor.attr('id');
    var reminderDay = ancestorId;

    var baseId = ancestorId.split('__')[0]; // Gets the part before '__'
    var todotitle = baseId+'__todotitle';
    var tododescription = baseId+'__tododescription';
    var todocompletionday = baseId+'__todocompletionday';

    var todoTitleVal = jQuery('#'+todotitle).val();
    var tododescriptionVal = jQuery('#'+tododescription).val();
    var todocompletiondayVal = jQuery('#'+todocompletionday).val();
    var reminderDayVal = jQuery('#'+reminderDay).val();

    var clusterId = jQuery('#clusterId').val();

    if (clusterId == 0 )
    {
      alert("Please select the organisation first");
      return false;
   }
   if(todoTitleVal.length < 1 )
   {
      alert("Please add Todo title");
      return false;
   }
   jQuery.ajax({
     url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=users.saveTemplate",
     type: 'POST',
     data: {'todoTitleVal': todoTitleVal,'clusterId': clusterId,
     'tododescriptionVal':tododescriptionVal,'todocompletiondayVal':todocompletiondayVal,'reminderday':reminderDayVal},
     dataType: "json",
     headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')},
     success: function(response) {
      if (response.success)
      {
         alert(response.data);
      }
      else
      {
         alert(response.data);  
      }
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
