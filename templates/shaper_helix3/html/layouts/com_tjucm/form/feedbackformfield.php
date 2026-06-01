
<?php
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

// echo $displayData->type; 
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
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjfields/models');
		$model = BaseDatabaseModel::getInstance('Fields', 'TjfieldsModel');
		$fieldFeedbackValue = $model->getFieldValueByFieldId($tjFieldFieldTable->id);
		?>
		<script>
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
							var className = "<?php echo $displayData->fieldname; ?>"; 
							jQuery(document).ready(function() {
								checkConditionOnload(className)						
		
								if ( jQuery('#jform_'+ className + i).is(':checked'))
								{
									jQuery('.'+className).append(fedbackValue_<?php echo $displayData->fieldname; ?>[i].feedback);
								}
							});

							jQuery('#jform_'+className+ i).click(function()
							{ 	
								jQuery('.'+className).empty();
								jQuery('.'+className).append(fedbackValue_<?php echo $displayData->fieldname; ?>[i].feedback);

								checkCondition(className)											
							});
						})(i);
					}
				}
				if ('<?php echo $displayData->type ;?>' == 'Checkbox' )
				{
					for(var i = 0 ; i < countItem ; i++)
					{
						(function(i) {

							var className = "<?php echo $displayData->fieldname; ?>"; 
							if (i == 0)
							{
								jQuery(document).ready(function() {

									if (jQuery('#jform_'+className).is(':checked'))
									{
										checkConditionOnload(className);
									}
									else
									{
										checkConditionOnload(className)
									}
									(jQuery('#jform_'+className).is(':checked')) ? jQuery('.'+className).append(fedbackValue_<?php echo $displayData->fieldname; ?>[0].feedback) : (fedbackValue_<?php //echo $displayData->fieldname; ?>.length < 2)?'':jQuery('.'+className).append(fedbackValue_<?php //echo //$displayData->fieldname; ?>[1].feedback);
								});
							}
							jQuery('#jform_<?php echo $displayData->fieldname; ?>').change(function() {

								if(this.checked)
								{
									jQuery('.'+className).empty();
									jQuery('.'+className).append(fedbackValue_<?php echo $displayData->fieldname; ?>[0].feedback);

									checkCondition(className)

								}
								else{
									jQuery('.'+className).empty();
									jQuery('.'+className).append((fedbackValue_<?php echo $displayData->fieldname; ?>.length < 2)?'':fedbackValue_<?php echo $displayData->fieldname; ?>[1].feedback);
									
									checkCondition(className)
								}
							});

						})(i);
					}
				}
			}								
			
			
			if ('<?php echo $displayData->type ;?>' == 'tjlist' || '<?php echo $displayData->type ;?>' == 'List')
			{
				jQuery(document).ready(function() {
					checkConditionOnload('<?php echo $displayData->id; ?>');

					var selectData = jQuery("#jform_<?php echo $displayData->fieldname; ?>").chosen().val();
					var selectlable=[];
					if ( selectData )
					{
						jQuery('#'+'<?php echo $displayData->id; ?> option').each(function(i, selectedOptions) {
							selectlable.push(jQuery(selectedOptions).text());
						});
						
					}
					
					var countFieldItem = fedbackValue_<?php echo $displayData->fieldname; ?>.length;


					if ( selectData )
					{

						feedBackadataa = {};

						if ( (selectData.length == 1 ))
						{
							jQuery('#'+'<?php echo $displayData->id; ?> option:selected').each(function(i, selectedOptions) {
								selectlable.push(jQuery(selectedOptions).text());
							});
						}
						
						for(var j = 0 ; j < countFieldItem ; j++)
						{
							
							(function(j) {

								
								if (fedbackValue_<?php echo $displayData->fieldname; ?>[j] != undefined)
								if (fedbackValue_<?php echo $displayData->fieldname; ?>[j].value == selectData)
								{ 
									var selectlabledata="";
									if (selectData.length == 1 && (fedbackValue_<?php echo $displayData->fieldname; ?>[j].feedback))
									{
										selectlabledata = "<b>" + selectlable[0]+ "</b><b>: </b>";
									}
									
									jQuery('.'+'<?php echo $displayData->fieldname; ?>').empty();
									jQuery('.'+'<?php echo $displayData->fieldname; ?>').append(selectlabledata+fedbackValue_<?php echo $displayData->fieldname; ?>[j].feedback);
								}
								else if(selectData && ('<?php echo $isMultiple ?>' == 1))
								{
									
									if(jQuery.inArray(fedbackValue_<?php echo $displayData->fieldname; ?>[j].value, selectData) !== -1)
									{ 
										var selectLables = ""; var brtag ="";
										if (fedbackValue_<?php echo $displayData->fieldname; ?>[j].feedback )
										{
											selectLables = "<b>" + fedbackValue_<?php echo $displayData->fieldname; ?>[j].value + "</b><b>: </b>";
											brtag = "<br>";
										}
										feedBackadataa[j] = selectLables + fedbackValue_<?php echo $displayData->fieldname; ?>[j].feedback + brtag;
										jQuery('.'+'<?php echo $displayData->fieldname; ?>').empty();
									}
								}
								
							})(j);
						} 
						var data = JSON.stringify(feedBackadataa)
						data = JSON.parse(data);
						Object.values(data).forEach(val => {
							jQuery('.'+'<?php echo $displayData->fieldname; ?>').append(val)
						});	

					}

					jQuery("#jform_<?php echo $displayData->fieldname; ?>").on('change', function(event, params)
					{
						checkCondition('<?php echo $displayData->fieldname; ?>')
						var feedBackarray = {};
						var ListData = jQuery("#jform_<?php echo $displayData->fieldname; ?>").chosen().val();
						if(ListData)
						{
							ListDataa = ListData.toString().split(",");
						}
						if (!ListData || (Array.isArray(ListData) && ListData.length === 0))
						{
							jQuery('.'+'<?php echo $displayData->fieldname; ?>').empty();
						}
						
						for(var i = 0 ; i < countFieldItem ; i++)
						{
							(function(i) {
								;
								if (fedbackValue_<?php echo $displayData->fieldname; ?>[i] != undefined)
								if (fedbackValue_<?php echo $displayData->fieldname; ?>[i].value == ListData && ('<?php echo $isMultiple ?> '!= 1))
								{
									jQuery('.'+'<?php echo $displayData->fieldname; ?>').empty();
									jQuery('.'+'<?php echo $displayData->fieldname; ?>').append(fedbackValue_<?php echo $displayData->fieldname; ?>[i].feedback);
								}
								else if(ListData && ('<?php echo $isMultiple ?>' == 1))
								{			
									
									if(jQuery.inArray(fedbackValue_<?php echo $displayData->fieldname; ?>[i].value, ListDataa) !== -1)
									{  
										feedBackarray[i] = "<b>" + fedbackValue_<?php echo $displayData->fieldname; ?>[i].value + "</b><b> : </b>"+fedbackValue_<?php echo $displayData->fieldname; ?>[i].feedback;
										jQuery('.'+'<?php echo $displayData->fieldname; ?>').empty();
									}
									
								}
							})(i);
						} 

						var data = JSON.stringify(feedBackarray)
						data = JSON.parse(data);
						Object.values(data).forEach(val => {
							jQuery('.'+'<?php echo $displayData->fieldname; ?>').append(val+"<br>")
						});	

						checkCondition('<?php echo $displayData->fieldname; ?>')								     
					})     
				})	
}						
</script>
<?php 
echo "<div class='".$displayData->fieldname."'>  </div>";
}
} 