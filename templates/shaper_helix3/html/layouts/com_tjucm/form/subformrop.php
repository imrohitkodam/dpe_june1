<?php 
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

if ($displayData->type == 'tjlist' || $displayData->type == 'Radio' || $displayData->type == 'Checkbox' || $displayData->type == 'List')
{

	Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
	$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
	$tjFieldFieldTable->load(array('name' => $displayData->fieldname, 'state' => 1));
	$params = json_decode($tjFieldFieldTable->params);
	$isMultiple = ($params && isset($params->multiple) && is_scalar($params->multiple)) ? (int)$params->multiple : 0;
	$showFeedbackOnForm = ($params && isset($params->showFeedbackOnForm) && is_scalar($params->showFeedbackOnForm)) ? (int)$params->showFeedbackOnForm : 0;

	if ($showFeedbackOnForm)
	{ 
		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_tjfields/models');
		$model = JModelLegacy::getInstance('Fields', 'TjfieldsModel');
		$fieldFeedbackValue = $model->getFieldValueByFieldId($tjFieldFieldTable->id); 
		?>

		<script type="text/javascript">


			function checkCondition(className)
			{
				setTimeout(function() { 
					jQuery('div[data-showon]').each(function(index) {
									    var divElem = jQuery(this);
										var styleElem = divElem.attr('style');
									    var regexPattern = 'jform\\[' + className + '\\]';
									    
										var regex = new RegExp(regexPattern, 'g');	
										
										var dataShowonValue = divElem.attr('data-showon');

										var match = regex.exec(dataShowonValue);

										if (match !== null) {

										 if (styleElem == 'display: none;' ) {

										var nextDivId = divElem.closest('div').find('label').attr('id');
										var radioId = nextDivId.replace('jform_', '').replace('-lbl', '');
											
											if (radioId != className)
											{ 
												jQuery('.'+radioId).css('display','none');
											}
									    } else if (styleElem == 'display: block;'  ) {

									       	var nextDivId = divElem.closest('div').find('label').attr('id');
										var radioId = nextDivId.replace('jform_', '').replace('-lbl', '');

										if (radioId != className)
											{
												jQuery('.'+radioId).css('display','block');
											}
									    }
									}

									});	
									},500);	

			}
			function checkConditionOnload(className)
			{	
				setTimeout(function() { 
       				 jQuery('div[data-showon]').each(function(index) {
									    var divElem = jQuery(this);
										var styleElem = divElem.attr('style');
									    var regexPattern = 'jform\\[' + className + '\\]';
									    
										var regex = new RegExp(regexPattern, 'g');	
										
										var dataShowonValue = divElem.attr('data-showon');

										var match = regex.exec(dataShowonValue);

										if (match !== null) {

										 if (styleElem == 'display: none;' ) {

										var nextDivId = divElem.closest('div').find('label').attr('id');
										var radioId = nextDivId.replace('jform_', '').replace('-lbl', '');
											
											if (radioId != className)
											{ 
												jQuery('.'+radioId).css('display','none');
											}
									    } else if (styleElem == 'display: block;') {

									       	var nextDivId = divElem.closest('div').find('label').attr('id');
										var radioId = nextDivId.replace('jform_', '').replace('-lbl', '');

										if (radioId != className)
											{
												jQuery('.'+radioId).css('display','block');
											}
									    }
									}

									});		
  							  }, 500);
								

			}
			var fedbackValue_<?php echo $displayData->fieldname; ?> =  <?php echo json_encode($fieldFeedbackValue); ?>;
			if ( fedbackValue_<?php echo $displayData->fieldname; ?> != null)
			{ 
				var countItem = fedbackValue_<?php echo $displayData->fieldname; ?>.length;
				if ('<?php echo $displayData->type ;?>' == 'Radio')
				{ 
					for(var i = 0 ; i < countItem ; i++)
					{
						(function(i) {

							var className = "<?php echo str_replace("jform_","",$displayData->id) ; ?>"; 
							jQuery(document).ready(function() {

								if ( jQuery('#jform_'+ className + i).is(':checked'))
								{  
									jQuery('.'+className).append(fedbackValue_<?php echo $displayData->fieldname; ?>[i].feedback);
									checkConditionOnload(className);
								}
							});
							jQuery('#'+className+ i).click(function()
							{
								jQuery('.'+className).empty();
								jQuery('.'+className).append(fedbackValue_<?php echo $displayData->fieldname; ?>[i].feedback);	checkCondition(className);				       
							});
						})(i);
					}
				}

				if ('<?php echo $displayData->type ;?>' == 'Checkbox' )
				{
					for(var i = 0 ; i < countItem ; i++)
					{
						(function(i) {
							var className = "<?php echo str_replace("jform_","",$displayData->id) ; ?>"; 

							if (i == 0)
							{
								jQuery(document).ready(function() {

									if (jQuery('#<?php echo $displayData->id ; ?>').prop('checked') == true)
									{
										jQuery('.'+className).append((fedbackValue_<?php echo $displayData->fieldname; ?>[0].feedback)?fedbackValue_<?php echo $displayData->fieldname; ?>[0].feedback:'');
										checkConditionOnload(className);
									}
									else{

										jQuery('.'+className).append((fedbackValue_<?php echo $displayData->fieldname; ?>.length < 2)?'':edbackValue_<?php echo $displayData->fieldname; ?>[1].feedback);
									checkConditionOnload(className);
									}
									i++;
								});
							}

							jQuery('#<?php echo $displayData->id ; ?>').change(function() {

								if(this.checked)
								{ 
									jQuery('.'+className).empty();
									jQuery('.'+className).append(fedbackValue_<?php echo $displayData->fieldname; ?>[0].feedback); checkCondition(className);
								}
								else{
									jQuery('.'+className).empty();
									jQuery('.'+className).append((fedbackValue_<?php echo $displayData->fieldname; ?>.length < 2)?'':edbackValue_<?php echo $displayData->fieldname; ?>[1].feedback); checkCondition(className);
								}
							});
						})(i);
					}           
				}
			}																				
			if ('<?php echo $displayData->type ;?>' == 'tjlist' || '<?php echo $displayData->type ;?>' == 'List')
			{
				jQuery(document).ready(function()
				{
					checkConditionOnload('<?php echo $displayData->fieldname; ?>')
					var selectData = jQuery('#'+'<?php echo $displayData->id; ?>').chosen().val();
					var selectlable=[];
					
					if ( (selectData ))
					{
						jQuery('#'+'<?php echo $displayData->id; ?> option').each(function(i, selectedOptions) {
							selectlable.push(jQuery(selectedOptions).text());
						});
					}


					if ( selectData )
					{	
						if ( (selectData.length == 1 ))
						{
							jQuery('#'+'<?php echo $displayData->id; ?> option:selected').each(function(i, selectedOptions) {
								selectlable.push(jQuery(selectedOptions).text());
							});
						}

						countFeedBackItem = fedbackValue_<?php echo $displayData->fieldname; ?>.length;

						for(var j = 0 ; j < countFeedBackItem ; j++)
						{  
							(function(j) {
								if (fedbackValue_<?php echo $displayData->fieldname; ?>[j] != undefined)
								if (fedbackValue_<?php echo $displayData->fieldname; ?>[j].value == selectData)
								{ 
									var selectlabledata="";
									if ((selectData.length == 1) &&(fedbackValue_<?php echo $displayData->fieldname; ?>[j].feedback))
									{
										selectlabledata = "<b>" + fedbackValue_<?php echo $displayData->fieldname; ?>[j].value + "</b><b>: </b>";
									}

									jQuery('.'+'<?php echo str_replace("jform_","",$displayData->id) ; ?>').empty();
									jQuery('.'+'<?php echo str_replace("jform_","",$displayData->id) ; ?>').append( selectlabledata +fedbackValue_<?php echo $displayData->fieldname; ?>[j].feedback);
								}
								else{
									if(jQuery.inArray(fedbackValue_<?php echo $displayData->fieldname; ?>[j].value, selectData) !== -1)
									{   var selectLables = "";var brtag="";
									if (fedbackValue_<?php echo $displayData->fieldname; ?>[j].feedback.length != 0 )
									{
										var valLabel = jQuery('#<?php echo $displayData->id; ?> option[value="' + fedbackValue_<?php echo $displayData->fieldname; ?>[j].value + '"]').text();
										selectLables = "<b>" + valLabel + "</b><b>: </b>";
										brtag = "<br>";
									}
									jQuery('<?php echo $displayData->fieldname; ?>').empty();
									jQuery('.'+'<?php echo str_replace("jform_","",$displayData->id) ; ?>').append(selectLables+ fedbackValue_<?php echo $displayData->fieldname; ?>[j].feedback+brtag);
								}
							}

						})(j);
					}  
					checkCondition('<?php echo $displayData->fieldname; ?>')
				}

			}	
			)}	
				jQuery( "<div class ='<?php  ; echo str_replace("jform_","",$displayData->id) ?>'></div>" ).insertAfter( "#<?php echo $displayData->id; ?>" )
			</script>
			<?php 
		}
	}
