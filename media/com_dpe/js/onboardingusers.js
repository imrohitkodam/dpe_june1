 jQuery(document).ready(function(){

   jQuery('#jobtitleset').click(function(){
      jQuery('#onboardtitle').hide();
});
   jQuery('#defaultset').click(function(){
      jQuery('#onboardtitle').show();
});
   jQuery('.onboardtitledata').css("width","40%");
   jQuery('.onboardtitledata').css("display","none")
   jQuery('.fa-info-circle').css('display','none');

   // alert(jQuery('#cluster_id').val());
   jQuery('#clusterId').val((jQuery('#cluster_id').val())?jQuery('#cluster_id').val():jQuery('#jform_com_tjucm_role_clusterclusterid').chosen.val());
   jQuery('.input-append').hide();
   jQuery('#start_date-lbl').attr('style', 'display: none !important');
   jQuery('#start_date_btn').css('background', 'white');
   jQuery('.input-append').css('width','41%');
   setTimeout(function() {
    jQuery('[id*="coursefilter_chosen"]').each(function() {
      var elementId = jQuery(this).attr('id');
      jQuery('#'+elementId).closest('.onboardusers').attr('style', 'width: 45% !important;');
      jQuery('#'+elementId).attr('style', 'width: 100%');

   });       
    jQuery('#clusterId').val((jQuery('#cluster_id').val())?jQuery('#cluster_id').val():jQuery('#jform_com_tjucm_role_clusterclusterid').chosen.val());

    jQuery('[id*="lessonfilter_chosen"]').each(function() {
      var elementId = jQuery(this).attr('id');
      jQuery('#'+elementId).closest('.onboardusers').attr('style', 'width: 45% !important;');
      jQuery('#'+elementId).attr('style', 'width: 100%');
   });   
    jQuery('.titletodo').closest('.onboardusers').attr('style', 'width: 45% !important;');

    jQuery('.courseday').closest('.control-group.data-subject-field.row').css('float', 'right');
    jQuery('.courseday').closest('.control-group.data-subject-field.row').css('margin-top', '-68px');
    jQuery('.courseday').closest('.control-group.data-subject-field.row').css('width', '45%');
    jQuery('.titletodo').attr('style', 'width: 100% !important;');
    jQuery('.completiondaytodo').closest('.control-group.data-subject-field.row').css('width', '46.5%');
    jQuery('.completiondaytodo').attr('style', 'margin-left:-14px');
    jQuery('.descriptiontodo').closest('.control-group.data-subject-field.row').css('float', 'right');
    jQuery('.descriptiontodo').closest('.control-group.data-subject-field.row').css('margin-top', '-68px');
    jQuery('.descriptiontodo').closest('.control-group.data-subject-field.row').css('width', '45%');
    jQuery('.reminderday').closest('.control-group.data-subject-field.row').css('float', 'right');
    jQuery('.reminderday').closest('.control-group.data-subject-field.row').css('margin-top', '-68px');
    jQuery('.reminderday').closest('.control-group.data-subject-field.row').css('width', '45%');
    jQuery('#userassignmentstatus_chosen').attr('style', 'width: 41% !important;');
    jQuery('#selectuseforonboarding_chosen').hide();
    jQuery('#selectuseforonboarding-lbl').prop('style','display:none !important');

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

   if(jQuery('#jform_com_tjucm_role_name').val().length > 0)
   {
      jQuery('.managedefault').hide();
   }

})

 jQuery(document).on('click', '.group-add', function (event, row) {
    setTimeout(function() {
       jQuery('.courselist').chosen();
       var elementId = '' ;
       jQuery('[id*="coursefilter_chosen"]').each(function() {
         elementId = jQuery(this).attr('id');

         jQuery('#'+elementId).closest('.onboardusers').attr('style', 'width: 45% !important;');
         jQuery('#'+elementId).attr('style', 'width: 100%');

      });    jQuery('[id*="lessonfilter_chosen"]').each(function() {
         var elementId = jQuery(this).attr('id');
         jQuery('#'+elementId).closest('.onboardusers').attr('style', 'width: 45% !important;');
         jQuery('#'+elementId).attr('style', 'width: 100%');


      });  
      jQuery('.courseday').closest('.control-group.data-subject-field.row').css('float', 'right');
      jQuery('.courseday').closest('.control-group.data-subject-field.row').css('margin-top', '-68px');
      jQuery('.courseday').closest('.control-group.data-subject-field.row').css('width', '45%');
      jQuery('.titletodo').closest('.onboardusers').attr('style', 'width: 45% !important;');
      jQuery('.titletodo').attr('style', 'width: 100% !important;');
      jQuery('.completiondaytodo').attr('style', 'margin-left:-14px');
      jQuery('.completiondaytodo').closest('.control-group.data-subject-field.row').css('width', '46.2%');
      jQuery('.descriptiontodo').closest('.control-group.data-subject-field.row').css('float', 'right');
      jQuery('.descriptiontodo').closest('.control-group.data-subject-field.row').css('margin-top', '-68px');
      jQuery('.descriptiontodo').closest('.control-group.data-subject-field.row').css('width', '45%');
      jQuery('.reminderday').closest('.control-group.data-subject-field.row').css('float', 'right');
      jQuery('.reminderday').closest('.control-group.data-subject-field.row').css('margin-top', '-68px');
      jQuery('.reminderday').closest('.control-group.data-subject-field.row').css('width', '45%');
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
         jQuery('#selectuseforonboarding-lbl').show();

      }else
      {
         jQuery('#selectuseforonboarding_chosen').hide();
         jQuery('#selectuseforonboarding-lbl').prop('style','display:none !important');

         

      }
   });

   jQuery('input[name="type_of_set"]').on('click', function() {
        // Get the value of the checked radio button
        var selectedValue = jQuery('input[name="type_of_set"]:checked').val();
        
        if (selectedValue == 'defaultset') {

         if(jQuery('#jform_com_tjucm_role_clusterclusterid').val() == '')
         {
           msg = 'Please select an orgnaistion.';
           message(msg ,'primary');
        }

        jQuery('.onboardtitledata').show();
        jQuery('.input-append').show();
        jQuery('#start_date-lbl').show();

        jQuery('#userassignmentstatus_chosen').css('display','');
        jQuery('#userassignmentstatus-lbl').attr('style','');
        jQuery('#selectuseforonboarding-lbl').prop('style','display:none !important');


         // Additional actions for 'defaultset'
      } else if (selectedValue == 'jobtitleset') {

        jQuery('#showdefumessage').hide();
        jQuery('#showjobmessage').show();
        jQuery('.ucmrecordId').val();
        jQuery('.input-append').hide();
        jQuery('#start_date-lbl').attr('style', 'display: none !important');
        jQuery('#userassignmentstatus_chosen').css('display','none');
        jQuery('#userassignmentstatus-lbl').attr('style','display:none !important');
        jQuery('#selectuseforonboarding_chosen').css('display','none');
        jQuery('#selectuseforonboarding-lbl').prop('style','display:none !important');
     }
  });
   jQuery('#userassignmentstatus').change(function() {

     getUsers();

  });

})


 function getUsers() {
   var userId='';
        // Get the selected value
        var selectedValue = jQuery('#userassignmentstatus_chosen').val();
        var ajaxUrl = Joomla.getOptions('system.paths').base + "/index.php?option=com_cluster&task=clusterusers.getUsersByClientId&format=json";
        var dataFields = {'cluster_id':jQuery('#cluster_id').val(),'user_id':userId};
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
     function getlessons()
     {     
      var clusterId = (jQuery('#cluster_id').val()!=0)?jQuery('#cluster_id').val():jQuery('#jform_com_tjucm_role_clusterclusterid').val();

      if (clusterId == 0)
      {
         return false;
      }

      jQuery.ajax({
         url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&format=json",
         data: "task=lesson.getLessonsAsPerCluster&clusterId=" + clusterId ,
         dataType: 'json',
         success: function(data)
         {
          var lessons = data.data;
          var nextDiv = '';
          jQuery('.lessonlist').each(function(index) {

             nextDiv = jQuery(this).next('div');
             var nextDivId = nextDiv.length ? nextDiv.attr('id') : 'No next sibling';

             if (jQuery('#'+nextDivId+' .chosen-single span').text() == 'Select Document')
             {
               var nextDivId = nextDivId.replace('_chosen', '');
               var $select = jQuery('#'+nextDivId);
               // $select.empty(); // Clear existing options
               var firstOption = jQuery('<option>', {
                  text: 'Select Document',
                  disabled: true,
                  selected: true
               });
               // $select.append(firstOption);

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
           }
        });
       }
    });
      
   }

   function saveonboardusers()
   {

      if(jQuery('#ucmrecordId').val().length < 1)
      {
         jQuery('#ucmrecordId').val(jQuery('#recordId').val());
      }
       // Serialize form data
       var formData = new FormData($('#useronboardform')[0]);
       var clusterId = jQuery('#clusterId').val();
       var title = jQuery('#onboardtitle').val();
       var msg = '';

        // Get the value of the checked radio button
        var selectedValue = jQuery('input[name="type_of_set"]:checked').val();

        if(selectedValue == undefined)
        {
         msg = 'Please choose the type of default set';
         message(msg);
         return false;
        }
      if(selectedValue == 'jobtitleset')
      {        
       formData.append('type_of_set', selectedValue);

        if(jQuery('#jform_com_tjucm_role_name').val().length < 1)
        {
         msg = 'Please add job title as you selected Add activities to the job title';
         message(msg);
         return false;
      }else
      {
         if(((jQuery('#ucmrecordId').val().length < 1) || (jQuery('#ucmrecordId').val() == undefined)) 
            &&  (jQuery('#recordId').val().length < 1))
         {
           msg = 'Please save the job role before saving the job title associated with it.';
           message(msg);
           return false;
        }
     }
  }

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
           var currentUrl = window.location.href;
           var defaultsetidIndex = currentUrl.indexOf('&defaultsetid');
           if(defaultsetidIndex != '-1')
           {
      // Trim the URL up to the defaultsetidIndex if applicable
      var trimmedUrl = currentUrl.substring(0, defaultsetidIndex);

          // Convert the trimmed string to a URL object for parameter manipulation
          var newUrl = new URL(trimmedUrl, window.location.origin);  // Ensure to use window.location.origin for base
          // Safely remove the 'usedefault' parameter if it exists
          if (newUrl.searchParams.has('usedefault')) {
             newUrl.searchParams.delete('usedefault');
          }
           window.location.href = newUrl.toString();
       }else
       {
           window.location.href = currentUrl.toString();
       }


          // Redirect the browser to the updated URL
         
       }, 1000);


         
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
jQuery(document).ready(function() {

   jQuery('#jform_com_tjucm_role_clusterclusterid').on('change', function() {
      var clusterid = jQuery(this).val();
      jQuery('#cluster_id').val(clusterid);
      jQuery('#clusterId').val(clusterid);
      setTimeout(function(){
        getlessons();
     }
     ,500)
      getUsers();

    // jQuery('.control-group.shadow.padding-20').each(function() {
    // jQuery(this).find('input, textarea, select').val('');
    // jQuery(this).find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
    // jQuery(this).find('select').trigger('chosen:updated');
// });
});
});
function message(msg,type=null)
{
 var type = (type != null)? type:'error';
 jQuery('<div id="system-message-container"></div>').appendTo('#system-message-container');
 Joomla.renderMessages({[type] : [msg]}); 
 jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
}

function usetemplate(element)
{  
 var $element = $(element);

    // Find the closest ancestor `div` that contains an element with class `reminderday`
    var closestAncestor = $element.closest('.col-sm-12').find('.reminderday');
    
    // Get the ID of the closest ancestor div
    var ancestorId = closestAncestor.attr('id');
    var baseId = ancestorId.split('__')[0];
    var clusterId = jQuery('#clusterId').val();

    if (clusterId == 0 )
    {
      alert("Please select the organisation first");
      return false;
   }
   var url = Joomla.getOptions('system.paths').base+'/index.php?option=com_dpe&view=import&layout=onboarduserspopup&tmpl=component&usetodotemplate=1&generatedId='+baseId+'&cluster_id='+jQuery('#jform_com_tjucm_role_clusterclusterid').val();
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
    var closestAncestor = $element.closest('.col-sm-12').find('.reminderday');
    
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
$(document).on('change', '.chosen-select', function() {
    // Get the selected value of the current chosen dropdown
    var currentValue = $(this).val();
    // Flag to check if the value is already used
    var isAlreadyUsed = false;
    
    // Loop through all other chosen dropdowns to check for duplicates
    $('.chosen-select').not(this).each(function() {

        if ($(this).val() === currentValue) {
            isAlreadyUsed = true;
            return false;  // Exit the loop early if a duplicate is found
        }
    });

    // If the value is already used, show an alert
    if (isAlreadyUsed) {
        alert('This option is already selected in another dropdown!');
        
        // Optionally, you can reset the selection
        $(this).val('').trigger('chosen:updated');  // Reset chosen dropdown
    }
});

