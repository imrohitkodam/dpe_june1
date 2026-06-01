<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjfields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2016 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');


if(JVERSION >= '3.0')
{
	Factory::getDocument()->getWebAssetManager()
    ->usePreset('choicesjs')
    ->useScript('webcomponent.field-fancy-select');
}

$input = Factory::getApplication()->input;
$fullClient = $input->get('client', '', 'STRING');
$fullClient =  explode('.', $fullClient);

$client = $fullClient[0];
$clientType = $fullClient[1];

$link = Route::_('index.php?option=com_tjfields&view=field&layout=edit&id=0&client=' . $input->get('client', '', 'STRING'), false);




// Import helper for declaring language constant
JLoader::import('TjfieldsHelper', Uri::root() . 'administrator/components/com_tjfields/helpers/tjfields.php');
// Call helper function
TjfieldsHelper::getLanguageConstant();

// Import CSS
$document = Factory::getDocument();
$document->addStyleSheet('components/com_tjfields/assets/css/tjfields.css');

if(($this->form->getValue('type') == 'radio') || ($this->form->getValue('type') =='tjlist'))
{	
    // HTMLHelper::script('media/system/js/mootools-core.js');
    // HTMLHelper::script('media/system/js/mootools-more.js');
    HTMLHelper::script('media/system/js/modal.js');
    HTMLHelper::script('templates/shaper_helix3/js/bootstrap.min.js');
    $document->addStyleSheet(str_replace('administrator/', '', Uri::base()) . 'media/system/css/modal.css');
}
?>
<script type="text/javascript">
	var invalidFormErrorMsg = '<?php echo $this->escape(Text::_('COM_TJFIELDS_INVALID_FORM')); ?>';
	var editFormlink = '<?php echo $link;?>';

	jQuery(document).ready(function(){
		jQuery("#field-form #jform_type").attr('onchange', 'show_option_div(this.value);');
	});
</script>
<?php // DPE hack To show navigation link of field groups and condition as per there ucm type 
$category_url    = Route::_('index.php?option=com_categories&extension=' . $this->item->client);
$field_group_url = Route::_('index.php?option=com_tjfields&view=groups&client=' . $this->item->client);
$fields_url      = Route::_('index.php?option=com_tjfields&view=fields&client=' . $this->item->client . '&extension=' . $this->item->client);
$conditionsUrl   = Route::_('index.php?option=com_tjfields&view=conditions&client=' . $this->item->client);

?>
<div class="float-end">
	<a href="<?php echo $field_group_url;?>" class="btn btn-success"><i class="fa fa-link" aria-hidden="true"></i>&nbsp<?php echo Text::_('COM_TJUCM_TYPES_FIELD_GROUP_URL');?></a>
<a href="<?php echo $fields_url;?>" class="btn btn-success"><i class="fa fa-link" aria-hidden="true"></i>&nbsp <?php echo Text::_('COM_TJUCM_TYPES_FIELDS_URL');?></a>
<a href="<?php echo $conditionsUrl;?>" class="btn btn-success"><i class="fa fa-link" aria-hidden="true"></i>&nbsp <?php echo Text::_('COM_TJUCM_TYPES_CONDITIONS_URL');?></a>
<br>
</div>


<?php $document->addScript(Uri::root() . 'administrator/components/com_tjfields/assets/js/field.js'); ?>
<div class="techjoomla-bootstrap">
	<form action="<?php echo JRoute::_('index.php?option=com_tjfields&view=field&layout=edit&id='.(int) $this->item->id).'&client='.$input->get('client','','STRING'); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="field-form" class="form-validate">
		<div class="form-horizontal">
			<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>
			<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'general', Text::_('COM_TJFIELDS_TITLE_FIELD', true)); ?>
			<div>&nbsp;</div>
			<div class="row">
				<div class="col-md-6">
					<div class="adminform">
						<legend>
							<?php
								echo Text::_('COM_TJFIELDS_BASIC_FIELDS_VALUES');
							?>
						</legend>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
						</div>
						<?php echo $this->form->getInput('title');?>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('label'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('label'); ?></div>
							<span class="control-label">&nbsp;</span>
							<span class="controls alert alert-info alert-help-inline alert_no_margin">
							<?php echo Text::_('COM_TJFIELDS_LABEL_LANG_CONSTRAINT_ONE'); ?>
							<span class="alert-text-change">
							<?php echo Text::sprintf('COM_TJFIELDS_LABEL_LANG_CONSTRAINT_TWO', $client); ?>
							</span>
							</span>
						</div>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('name'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('name'); ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('type'); ?></div>
							<?php
								if (!empty($this->item->id))
								{
									?>
									<div class="controls">
										<input type="text" name="jform[type]" id="jform_type" value="<?php echo $this->item->type;?>" class="required" required="required" aria-required="true" aria-invalid="false" readonly="true"/>
									</div>
									<?php
								}
								else
								{
									?>
									<div class="controls"><?php echo $this->form->getInput('type'); ?></div>
									<?php
								}
							?>
						</div>
							<?php
								// DPE Hack to load numeric values from database for checklist
								$params = null;
								if (!empty($this->item->id)) {
									Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
									$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
									$tjFieldFieldTable->load(array('id' => $this->item->id));
									$params = json_decode($tjFieldFieldTable->params);
								}

								foreach ($this->form->getFieldsets('params') as $name => $fieldSet)
								{
									foreach ($this->form->getFieldset($name) as $field)
									{

									// DPE Hack start
									if($params->enablechecklistscore){
										if ($params && isset($params->tjfields) && strpos($field->fieldname, 'numeric_value') !== false) {
											
											if (preg_match('/tjfields\[(\d+)\]\[numeric_value\]/', $field->fieldname, $matches)) {

												$idx = (int)$matches[1];
												if (is_array($params->tjfields) && isset($params->tjfields[$idx]) && is_object($params->tjfields[$idx])) {
													$obj = $params->tjfields[$idx];
													if (isset($obj->numeric_value)) {
														$field->setValue($obj->numeric_value);
													}
												}
											}
										}
									}
									 // DPE Hack end
										echo $field->renderField();
									}
								}
								echo $this->form->getInput('options');
								?>
						<?php
						$type = $this->form->getValue('type');

						if ($type == 'radio' || $type == 'single_select' || $type == 'multi_select' || $type == 'tjlist')
						{?>
							<div class="control-group showFeedback">
								<div class="control-label"><?php echo $this->form->getLabel('showFeedback'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('showFeedback'); ?></div>
							</div>
							<!-- DPE HACK-->
							<div class="control-group showFeedbackOnForm">
								<div class="control-label"><?php echo $this->form->getLabel('showFeedbackOnForm'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('showFeedbackOnForm'); ?></div>
							</div>
		
							<div class="control-group notificonfig">
								<div class="control-label"><?php echo $this->form->getLabel('showNotificationConfiguration'); ?></div>
								<div class="controls"><?php echo $this->form->getInput('showNotificationConfiguration'); ?></div>
							</div>
							<div class="control-group hide" id="notificationconfig">
								<div class="control-label"><?php echo Text::_('COM_DPE_TJFIELDS_FORM_LBL_FIELD_NOTIFICATION_CONFIGURATION_BUTTON_LABLE');?></div>
								<div class="controls"><div class="btn btn-success"><?php echo Text::_('COM_DPE_TJFIELDS_FORM_LBL_FIELD_NOTIFICATION_BUTTON');?></div></div>
							</div>
						<!-- DPE HACK-->
							<div class="control-group feedback">
							<div class="control-label"><?php echo $this->form->getLabel('fieldoption'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('fieldoption'); ?></div>
							</div>
							<?php
						} ?> 
						
					</div>
					<div class="fileUploadAlert hide">
						<span class="alert alert-info alert-help-inline span9 alert_no_margin">
							<?php
								echo Text::_('COM_TJFIELDS_FORM_LBL_FILE_UPLOAD_PATH_NOTICE');
							?>
						</p>
					</div>
					<input type="hidden" name="jform[client]" value="<?php echo $input->get('client','','STRING'); ?>" />
				</div>
				<div class="col-md-5 form-horizontal">
					<div class="adminform form-horizontal">
						<legend>
							<?php
								echo Text::_('COM_TJFIELDS_EXTRA_FIELDS_VALUES');
								?>
						</legend>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('group_id'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('group_id'); ?></div>
						</div>
						<div class="control-group" >
							<div class="control-label"><?php echo $this->form->getLabel('state'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('state'); ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('required'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('required'); ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('readonly'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('readonly'); ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('showonlist'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('showonlist'); ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('tags'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('tags'); ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('created_by'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('created_by'); ?></div>
						</div>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('category') ; ?></div>
							<div class="controls">
								<?php
								echo $this->form->getInput('category');?>
							</div>
							<span class="control-label">&nbsp;</span>
							<span class="controls alert alert-warning alert-help-inline col-md-9 alert_no_margin">
								<?php echo Text::_('COM_TJFIELDS_CATEGORY_NOTE'); ?>
							</span>
						</div>
						<div class="control-group">
							<div class="control-label"><?php echo $this->form->getLabel('filterable'); ?></div>
							<div class="controls">
								<?php echo $this->form->getInput('filterable'); ?>
							</div>
							<span class="control-label">&nbsp;</span>
							<span class="controls alert alert-info alert-help-inline col-md-9 alert_no_margin">
							<?php echo Text::_('COM_TJFIELDS_FILTERABLE_NOTE'); ?>
							</span>

						</div>
						<!---->
						<div class="control-group description">
							<div class="control-label"><?php echo $this->form->getLabel('description'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('description'); ?></div>
						</div>
						<div class="control-group js_function">
							<div class="control-label"><?php echo $this->form->getLabel('js_function'); ?></div>
							<div class="controls">
								<?php echo $this->form->getInput('js_function'); ?>
							</div>
						</div>
						<div class="control-group validation_class">
							<div class="control-label"><?php echo $this->form->getLabel('validation_class'); ?></div>
							<div class="controls">
								<?php echo $this->form->getInput('validation_class'); ?>
							</div>
							<span class="control-label"></span>
							<span class="controls alert alert-info alert-help-inline col-md-9 alert_no_margin">
							<?php echo Text::_('COM_TJFIELDS_VALIDATION_CLASS_NOTE'); ?>
							</span>
						</div>
					</div>
				</div>
				<!--</div>-->
			</div>
			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
			<?php if (Factory::getUser()->authorise('core.admin','com_tjfields')) : ?>
				<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
				<?php echo $this->form->getInput('rules'); ?>
				<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
			<?php endif; ?>
		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
			<input type="hidden" name="client_type" value="<?php echo $clientType;?>" />
			<input type="hidden" name="task" value="" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
		<!--row fuild ends-->
</div>
<!--techjoomla ends-->
</form>
</div>

<script>
// DPE hack
	jQuery(document).ready(function($) {

		jQuery('.subformtext').css('width','100%');
		jQuery('.subformvalue').css('width','100%');
		jQuery('.table-responsive').css('margin-left','-244px');
		jQuery('.table-responsive').css('margin-top','50px');
		jQuery(".feedBackValue").css('width','306px');
		jQuery('#subfieldList_jform_fieldoption th').removeAttr('style');

		if (jQuery('#jform_params_colorcombination').length)
		{ 
			jQuery( "#jform_params_colorcombination" ).after( "<p id='color_combinstion_p'></p>" );
		}

		var feedBackOption = jQuery('input[name="jform[showFeedback]"]:checked').val();
		var feedBackOnFormOption = jQuery('input[name="jform[showFeedbackOnForm]"]:checked').val();

		if (feedBackOption == 0 && feedBackOnFormOption != 1)
		{
			jQuery('td:nth-child(4)').hide();
			jQuery('th:nth-child(4)').hide();
		}
		else
		{
			jQuery(".feedback").insertAfter("#general");
			jQuery(".js_function").insertBefore(".showFeedback");

			 
		}


		jQuery("input[name='jform[showFeedback]']").change(function(){
			changeStatusFeedback();

		});
		jQuery("input[name='jform[showFeedbackOnForm]']").change(function(){
			changeStatusFeedback();
		});
		jQuery("input[name='jform[params][multiple]']").change(function(){

			changeStatusFeedback();
		});

		if(jQuery('#jform_fieldoption-lbl').text()!== null)
		{
			jQuery('.subform-repeatable-wrapper').addClass( 'feedbackSubform' );
			jQuery(".feedBackValue").parent().parent().addClass('feedback_contorl_section'); 
			
		}

	});
	jQuery(document).on('click', function(evt) {
		if(jQuery(evt.target).is('.icon-plus') || jQuery(evt.target).is('.group-add')) {
			hideFeedback();
			jQuery(".feedBackValue").parent().parent().addClass('feedback_contorl_section');
			jQuery('.subformtext').css('width','100%');
			jQuery('.subformvalue').css('width','100%');
			//jQuery('.table-responsive').css('width','300%');
			jQuery('.table-responsive').css('margin-left','-244px');
			jQuery('.table-responsive').css('margin-top','50px');
			jQuery(".feedBackValue").css('width','306px');
			jQuery('#subfieldList_jform_fieldoption th').removeAttr('style');
		}
	});

	function hideFeedback()
	{
		var feedbackButtonValue = jQuery('input[name="jform[showFeedback]"]:checked').val();
		var feedbackOnFormValue = jQuery('input[name="jform[showFeedbackOnForm]"]:checked').val();

		if (feedbackButtonValue == 0 && feedbackOnFormValue != 1)
		{ 
			setTimeout(function () {
				jQuery('td:nth-child(4)').hide();
			}, 50);

		}             
	}
	function changeStatusFeedback()
	{
		var feedbackButtonValue = jQuery('input[name="jform[showFeedback]"]:checked').val();
		var feedbackOnFormValue = jQuery('input[name="jform[showFeedbackOnForm]"]:checked').val();

		if (feedbackButtonValue == 1 || feedbackOnFormValue == 1) 
		{
			jQuery('td:nth-child(4)').show();
			jQuery('th:nth-child(4)').show();
			jQuery(".feedback").insertAfter("#general");
			jQuery(".js_function").insertBefore(".showFeedback");
			jQuery('#subfieldList_jform_fieldoption th').removeAttr('style');
		}
		else if(feedbackButtonValue == 0 && feedbackOnFormValue != 1)
		{    	
			jQuery('td:nth-child(4)').hide();
			jQuery('th:nth-child(4)').hide();
			jQuery(".feedback").insertAfter("#notificationconfig");
			jQuery(".js_function").insertAfter(".description");
			jQuery(".validation_class").insertAfter(".description");
		}

	}

	function checkJson()
	{
		
		try{
          encodedJson = jQuery.parseJSON(jQuery('#jform_params_colorcombination').val());

           if (encodedJson)
           {
              
              jQuery( "#color_combinstion_p" ).html( "valid Json" );
              jQuery( "#color_combinstion_p" ).css( "color",'green' );
              jQuery('.button-apply').prop('disabled', false);
           }
         }catch(error){
                jQuery('.button-apply').prop('disabled', true);
                jQuery( "#color_combinstion_p" ).html( "Not valid Json" );
                jQuery( "#color_combinstion_p" ).css( "color",'red' );
              }
          }

          jQuery(document).ready(function($) {

          	if (jQuery('input[name="jform[showNotificationConfiguration]"]:checked').val() === '1')
          	{
            		jQuery('#notificationconfig').removeClass('hide');
     			}
     			else
     			{
						jQuery('#notificationconfig').addClass('hide');
     			}


     			jQuery('input[name="jform[showNotificationConfiguration]"]').on('click', function()
     			{
		           if (jQuery('input[name="jform[showNotificationConfiguration]"]:checked').val() === '1')
		            {
		                jQuery('#notificationconfig').removeClass('hide');
		            }
		            else
		     			{
								jQuery('#notificationconfig').addClass('hide');
		     			}
       		 });

     			if ('<?php echo $this->item->show_notification?>' == 1)
     			{
     				jQuery('input[name="jform[showNotificationConfiguration]"][value="1"]').prop('checked', true);

     				jQuery('#notificationconfig').removeClass('hide');
     			}

     			jQuery('#notificationconfig').click(function(){

     				var currentUrl = window.location.href;
					var url = new URL(currentUrl);
					var id = url.searchParams.get('id');
					var client = url.searchParams.get('client');

     				url='index.php?option=com_dpe&view=ucmnotification&tmpl=component&id='+id+'&client='+client; //for local
     				var wwidth = jQuery(window).width() -250;
				   var wheight = jQuery(window).height() - 150;

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

     			})    			
     			
          })
</script>
