<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Filter\OutputFilter;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;

$fieldsets_counter = 0;
$layout  = Factory::getApplication()->input->get('layout');
$ucmConfigs = ComponentHelper::getParams('com_tjucm');
$useTooltip = $ucmConfigs->get('enable_custom_tooltip');
$layout  = new FileLayout('feedbackformfield', JPATH_SITE . '/templates/shaper_helix3/html/layouts/com_tjucm/form');

Text::script('COM_DPE_TICKET_GENERATION_FAIL');
Text::script('COM_DPE_TICKET_GENERATION_SUCCESS');
Text::script('COM_DPE_TICKET_FIELD_REQUIRED');

HTMLHelper::_('script','media/com_dpe/js/dpefeedbacksubform.js');
Factory::getApplication()->input->set('extralayout', "default");

$user = Factory::getUser();	
 $params     			    = ComponentHelper::getParams('com_multiagency');
 $orgAdmin           		= (int) $params->get('multiagency_school_admin_group', '0', 'INT');
$orgAdmin 		   			= in_array($orgAdmin, $user->groups);
 
 // Check the log has link field to create ticket
	Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
	$typeDetails = Table::getInstance('Type', 'TjucmTable');
	$typeDetails->load(array('unique_identifier' => $this->client));
	$ticketConditionData = json_decode(json_decode($typeDetails->params)->type_options);
				
	// Get the id of link text box check that field is present or not.
	
	if (isset($ticketConditionData->toUser))
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
	 	$fieldTable = Table::getInstance('field', 'TjfieldsTable');
	 	$fieldTable->load(array('name'=>$ticketConditionData->addticketplace));
	}
	
			
	// Get the id of link text box check that field is present or not.
	if (isset($ticketConditionData->linkField))
	{
	 	$fieldTableLink = Table::getInstance('field', 'TjfieldsTable');
	 	$fieldTableLink->load(array('name'=>$ticketConditionData->linkField,'state'=>1));
  }
?>
<div id="item-form" >
	<div class="overlay" id="tjucm_loader" style="display:none;">
		<div class="loader"></div>
	</div>
</div>
<?php
if ($this->form_extra)
{
	// Iterate through the normal form fieldsets and display each one
	$fieldSets = $this->form_extra->getFieldsets();

	foreach ($fieldSets as $fieldset)
	{
		if (count($fieldSets) > 1)
		{
			if ($fieldsets_counter == 0)
			{
				echo HTMLHelper::_('bootstrap.startTabSet', 'tjucm_myTab',array('active' => OutputFilter::stringURLUnicodeSlug(trim($fieldset->name))));
				
			}

			$fieldsets_counter++;

			if (count($this->form_extra->getFieldset($fieldset->name)))
			{
				foreach ($this->form_extra->getFieldset($fieldset->name) as $field)
				{
					if (!$field->hidden)
					{
						$tabName = OutputFilter::stringURLUnicodeSlug(trim($fieldset->name));
						echo HTMLHelper::_("bootstrap.addTab", "tjucm_myTab", $tabName, $fieldset->name);
						break;
					}
				}
			}
		}
		?>
		<div class="section-title visible-print"><h3><?php echo $fieldset->name;?></h3></div>
		<div class="form-horizontal col-12 custom-form-fields">
			<?php
			// Iterate through the fields and display them
			foreach ($this->form_extra->getFieldset($fieldset->name) as $field)
			{	
				// To remove the mandatory diable of copied items.
				$field->disabled = '';
				$field->readonly = '';
				
				$isUcmsubform = 0;
				$description = $field->description;

              	if($useTooltip)
				{
					$field->description = '';
				}


				if ($field->type == 'Ucmsubform')
				{
					$isUcmsubform   = 1;
					if($field->max == 1)
					{
					?>

					<script>
						jQuery(document).ready(function(){

							jQuery('.btn-toolbar').hide();
						})
					</script>

				<?php }
				}

				if (strpos($field->class, 'twoColumnUcmsubform') !== false)
				{
					$isUcmsubform   = 0;
				}

				if (!$field->hidden)
				{
					?>
					<div class="col-12 <?php echo $isUcmsubform? ' custom-form-style ucmsubform': ''?>">
						<div class="form-group row">
							<?php
								if($useTooltip && $description)
								{
									$field->description = $description;
								}
							?>
							<?php if($isUcmsubform){ ?>
								<div class="<?php echo ' col-sm-12 control-label w-100 text-left'?>">
									<?php echo $field->label; ?>
								</div>
								<div class="<?php echo ' col-sm-12 rop-inputs w-100'?>">
									<?php echo $field->input; ?>
								</div>
							<?php }
							elseif ($field->type == 'DpechecklistExpectation')
							{
								?>
								<div class="<?php echo ' col-sm-4'?>">
									<?php echo $field->label; ?>
								</div>
								<div class="<?php echo ' col-sm-8 mb-10'?>">
									<?php echo $field->input; ?>
								</div>
								<?php
							}
							else
							{
								echo $field->renderField();

								if (isset($ticketConditionData->linkField) && $field->value && 'jform_'.$fieldTableLink->name == $field->id)
													{ 
														?>
														<div class="row ticketbtnclass" style="margin-top: -40px;">
															<div class="<?php echo 'col-sm-4';?> float-right" >
															</div>
															<div class="<?php echo 'col-sm-5 mb-10';?> float-right fw-bold" >
															<a href="<?php echo $field->value;?> "target="_blank"><?php echo Text::_('COM_DPE_LOG_TO_TICKET');?> </a>
															</div>
														</div>
														<script type="text/javascript">
															
															jQuery('#<?php echo $field->id;?>').hide();
														</script>

														<?php
													}

								if (isset($ticketConditionData->linkField) && !empty($fieldTableLink->id) && $ticketConditionData->isCreateTicket == 'true' && 'jform_'.$fieldTable->name == $field->id && (!$field->value && $user->authorise('core.manageall', 'com_cluster')))
							  {

								  	$ticketConditionDatas = json_encode($ticketConditionData);
									  $ticketConditionDatas = str_replace('"', '&quot;', $ticketConditionDatas);

								  	?>
								  	<div class='float-end ticketbtn'>
								  	<input type="button"  class='btn btn-sm btn-primary ' name="addTicket" id='addTicket' value="<?php echo Text::_('COM_DPE_ADDTICKET')?>" onclick="logticket.addTicketfromUcm('<?php echo $ticketConditionDatas ?>'); <?php if ($fieldTableLink->id){?> updateLinkField();<?php }?>"><br> <br><p id='addTicketMessage' class='d-none '></p>
								  </div>
								 
								  	<?php
								  }
							}
							?>
							<div class="control-group  data-subject-field row">
									<div class="<?php echo 'col-sm-4 col-12';?>">
									</div>
								<div class="col-sm-5 custom-calender">
									<div class="<?php echo 'col-sm-10 col-12 ms-51';?>">
									<?php 
										echo $layout->render($field);
									?>
									</div>
								</div>
							</div>	
						<?php
							// TODO :- Check and remove
							if ($field->type == 'File')
							{
								?>
								<script type="text/javascript">
									jQuery(document).ready(function ()
									{
										var fieldValue = "<?php echo $field->value; ?>";
										var AttrRequired = jQuery('#<?php echo $field->id;?>').attr('required');
										if (typeof AttrRequired !== typeof undefined && AttrRequired !== false)
										{
											if (fieldValue)
											{
												jQuery('#<?php echo $field->id;?>').removeAttr("required");
												jQuery('#<?php echo $field->id;?>').removeClass("required");
											}
										}
									});
								</script>
							<?php
							}
							?>
						</div>
					</div>
				<?php
				}
			}
			?>
		</div>
		<?php

		if (count($fieldSets) > 1)
		{
			if (count($this->form_extra->getFieldset($fieldset->name)))
			{
				foreach ($this->form_extra->getFieldset($fieldset->name) as $field)
				{
					if (!$field->hidden)
					{
						echo HTMLHelper::_("bootstrap.endTab");
						break;
					}
				}
			}
		}
	}

	// Check if AI is enabled globally and for this type
	$dpeParams = \Joomla\CMS\Component\ComponentHelper::getParams('com_dpe');
	$enableAi = $dpeParams->get('enable_ai', 0);
	
	// Load type params
	$db = Factory::getDbo();
	$query = $db->getQuery(true)
		->select('params')
		->from('#__tj_ucm_types')
		->where('unique_identifier = ' . $db->quote($this->client));
	$db->setQuery($query);
	$typeParamsJson = $db->loadResult();
	$typeParams = json_decode($typeParamsJson);

	$aiEnabledForType = isset($typeParams->ai_enable_insights) && $typeParams->ai_enable_insights == 1;

	if ($enableAi && $aiEnabledForType && count($fieldSets) > 1)
	{
		include __DIR__ . '/ask_kb.php';
	}

	if (count($fieldSets) > 1)
	{
		echo HTMLHelper::_('bootstrap.endTabSet');
	}
}
else
{
	?>
	<div class="alert alert-info">
		<?php  echo Text::_('COM_TJLMS_NO_EXTRA_FIELDS_FOUND');?>
	</div>
	<?php
}
?>
<script>
	jQuery(document).ready(function(){ 
	jQuery(document).change(function(evt) {
			 var url = '<?php echo Uri::root();?>'
			getSubFormsFeedback(evt,url);
			
			});
		});
	jQuery(document).ready(function($){
				 // Temp solution for conditional field

				var dataShowonFields = jQuery("#item-form").find("div[data-showon]");
					
					dataShowonFields.each(function() {
						var value = jQuery(this).data("showon");
						var fieldName = value[0].field;
						var signVal = value[0].sign;

						var inputValue = jQuery("select[name=\'"+ fieldName +"[]\']").val();
						if (inputValue == null && (signVal == "!="))
						{
							jQuery(this).css("display", "");
						}
					});
					jQuery("select[multiple]").on("change", function () {

						dataShowonFields.each(function() {
						var value = jQuery(this).data("showon");
						var fieldName = value[0].field;
						var inputValue = jQuery("select[name=\'"+ fieldName +"[]\']").val();
						var signVal = value[0].sign;
                                            
						if (inputValue == null && signVal == "!=")
						{
							jQuery(this).css("display", "");
						}
					});

						});
				});
</script>
<script type="text/javascript">

jQuery(document).on('subform-row-add', function(event, row){
	jQuery("[data-toggle=tooltip]").tooltip();
	
	var subformname = event.detail.row.getAttribute('data-group');
	accordionCount = jQuery(event.detail.row).find(".accordion");

		for (i = 0; i < accordionCount.length; i++) {

		jQuery(event.detail.row).find(".accordion").addClass(subformname+i);

		  accordionCount[i].addEventListener("click", function() {
			  
		 this.classList.toggle("active");

			var panel = this.nextElementSibling;
			if (panel.style.display === "block") {
			  panel.style.display = "none";
			} else {
			  panel.style.display = "block";
			}
		  });

		}
	
	});	
	
	
jQuery(document).ready(function() {
 jQuery(".panel.row").css("display", "none");

var acc = document.getElementsByClassName("accordion");
var i;

for (i = 0; i < acc.length; i++) {
  acc[i].addEventListener("click", function() {
 this.classList.toggle("active");

    var panel = this.nextElementSibling;
    if (panel.style.display === "block") {
      panel.style.display = "none";
    } else {
      panel.style.display = "block";
    }
  });
}
});

    jQuery('document').ready(function(){
        
        jQuery('#sbox-btn-close').click(function(){window.parent.SqueezeBox.close();});
        	    jQuery(".ticketbtnclass").insertAfter(".row-fluid");


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
    		var result = confirm("<?php echo Text::_('COM_DPE_RSTICKET_SAVE_THELOG_FORM');?>");

	        if (result) {
	           
	            window.parent.SqueezeBox.close();
	        } else {
	            
	            return false;
	        }
    	}
    }
</script>

	<?php

	// Code to run the script
	if ($ticketConditionData)
	{
		$this->fieldTable= $fieldTable;
		$this->ticketConditionData = $ticketConditionData;
		$this->fieldTableLink = $fieldTableLink;
		echo $this->loadTemplate('extraticketgeneration');
	}
	?>