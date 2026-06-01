<?php
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

	$fieldTable= $this->fieldTable;
	$ticketConditionData = $this->ticketConditionData;
	$fieldTableLink = $this->fieldTableLink;
?>

<script>

jQuery(document).ready(function(){

	if ("<?php echo $fieldTable->id?>".length > 1)
	{
		// jQuery('#jform_'+"<?php echo $ticketConditionData->linkField?>").attr('readonly','readonly');

		if (jQuery('#jform_'+"<?php echo $ticketConditionData->linkField?>").val())
		{
			jQuery('.ticketbtn').addClass('d-none');
		}
	}
	
});
	function updateLinkField()
	{		
			if ("<?php echo $fieldTable->id?>".length > 1)
			{
				var recordId = jQuery('#recordId').val();
				logticket.afterSaveLinkFieldUpdate(recordId+','+ <?php echo ($fieldTableLink->id)?$fieldTableLink->id:0?>)
			}
	}
	

</script>
<script >
	
	    jQuery('document').ready(function(){
        
        jQuery('#sbox-btn-close').click(function(){window.parent.SqueezeBox.close();});

 var currentUrl = window.parent.jQuery('#ticketUrl').val()

  // Check if the URL contains "view-ticket"
  if ((currentUrl != undefined) &&(currentUrl.indexOf("view-ticket") !== -1)) {
        var organisationId = (window.parent.jQuery('#ticketcluster').val())?window.parent.jQuery('#ticketcluster').val():window.parent.jQuery('#schoolId').val();
        var subject = window.parent.jQuery('#ticket_subject').val();
        var message = window.parent.jQuery('.com-rsticketspro-has-overflow').text();
        var leadStaffMember  = window.parent.parent.jQuery('#ticketcustomer_id').val();
        

        jQuery('#jform_<?php echo $ticketConditionData->clusterId;?>').val(organisationId).trigger("chosen:updated");
        var clusterUserData = {cluster_id: organisationId, user_id: ''}

        var ajaxUrl  =  '<?php echo Uri::root();?>'+'index.php?option=com_cluster&task=clusterusers.getUsersByClientId&format=json';
        var ownershipFieldId = 'jform_<?php echo$ticketConditionData->toUser;?>';

		ownership.getUsers(clusterUserData, ajaxUrl, ownershipFieldId);

		setTimeout(function(){
        jQuery('#jform_<?php echo $ticketConditionData->toUser;?>').val(leadStaffMember).trigger("chosen:updated");

		},1500)

        jQuery('#jform_<?php echo $ticketConditionData->message;?>').val(message);
        jQuery('#jform_<?php echo $ticketConditionData->subject;?>').val(subject);
        jQuery('#jform_<?php echo $fieldTableLink->subject;?>').click();

        if (jQuery('#jform_<?php echo $fieldTableLink->name;?>').length)
        {
        	var ticketUrl = window.parent.jQuery('#ticketUrl').val();

        	var ticketId =  ticketUrl.substring(ticketUrl.lastIndexOf('view-ticket/') +  'view-ticket/'.length);

        	var FullTicketId = "<?php echo URI::root().'index.php?option=com_rsticketspro&view=ticket&id='?>"+ticketId;

        	jQuery('#jform_<?php echo $fieldTableLink->name;?>').val(FullTicketId);

        }
    }

    })

    function closePopup()
    {
    	if (window.parent.$('#logid').val().length > 0)
    	{
    		window.parent.SqueezeBox.close();
    	}
    	else
    	{
    		var result = confirm('<?php echo Text::_('COM_DPE_RSTICKET_SAVE_THELOG_FORM');?>');

	        if (result) {
	           
	            window.parent.SqueezeBox.close();
	        } else {
	            
	            return false;
	        }
    	}
    }
</script>