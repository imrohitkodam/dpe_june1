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
use Joomla\CMS\Table\Table;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Helper\TagsHelper;

HTMLHelper::script('media/com_dpe/js/tjucm.js');
Text::script('COM_TJUCM_ROP_ITEM_FORM_NEXT_DATE_REVIEW_VALIDATION_MESSAGE');
JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjucm/tables');
JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
JLoader::import('components.com_tjfields.helpers.tjfields', JPATH_ADMINISTRATOR);


$fieldsets_counter = 0;
$layout             = Factory::getApplication()->input->get('layout');
$params             = ComponentHelper::getParams('com_dpe');
$reverseListClients = explode (",", $params->get('coredataReverseUcmTypes'));
$clusterFieldName   = '';
$app                = Factory::getApplication();
$calledFrom                = (strpos($baseUrl, 'administrator')) ? 'backend' : 'frontend';
$app                = Factory::getApplication();
$tmpl               = $app->input->get('tmpl', '', 'STRING');

$ucmConfigs = ComponentHelper::getParams('com_tjucm');
$useTooltip = $ucmConfigs->get('enable_custom_tooltip');

if ($this->item->id)
{
	$itemState = ($this->item->draft && ($this->allow_auto_save || $this->allow_draft_save)) ? 1 : 0;
}
else
{
	$itemState = ($this->allow_auto_save || $this->allow_draft_save) ? 1 : 0;
}

 
$tjfieldsHelper = new TjfieldsHelper;
?>
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
				echo HTMLHelper::_('bootstrap.startTabSet', 'tjucm_myTab');
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
		<div class="form-horizontal clear-both pull-left pb-10 w-100 dp-rop-form d-flex flex-wrap">
			<?php			

			$arr = array();

			// Iterate through the fields and display them
			foreach ($this->form_extra->getFieldset($fieldset->name) as $field)
			{	
				
				if(!empty($field->getAttribute('tags')))
				{
					$temp = new TagsHelper;
					$tagnames = $temp->getTagNames(array($field->getAttribute('tags')));

						if(array_key_exists($arr, $tagnames[0]))
						{
							$arr[$tagnames[0]][] = $field;
						}
						else
						{
							$arr[$tagnames[0]][] = $field;
						}
															
				}
			}

		
		
			if(!empty($arr)){
			foreach ($arr as $key => $fieldTagarray)
			{
				$i =0;
				
				?>
				<div class="accordion" id="accordion<?php echo $i++; ?>"><?php echo  ucfirst(str_replace('_', ' ', $key)); ?></div>
				<div id="pan" class="panel">

				<?php foreach($fieldTagarray as $fieldTag)
				{
					
					$isUcmsubform = 0;

					if ($fieldTag->type == 'Ucmsubform')
					{
						$customColClass = 'col-xs-12 col-md-12 ucmsubform';
					}
					else
					{
						$customColClass = 'col-md-4 col-xs-12';
					}

					if (strpos($fieldTag->class, 'twoColumnUcmsubform') !== false)
					{
						$isUcmsubform   = 0;
					}


					if (!$fieldTag->hidden)
					{
					$className = ($field->type == 'Spacer') ? 'w-100' : '';
					?>
				<div class="<?php echo $customColClass . ' ' . $className;  ?> custom-form-style">
						<div class="form-group">
								<div class="col-sm-12 control-label w-100 text-left">
									<?php echo $fieldTag->label; ?>
								</div>

								<?php
								// TODO :- Check and remove
								if ($fieldTag->type == 'File')
								{
									if ($this->copyRecId)
									{
										$fieldTag->setValue('');
									}

									?>
									<script type="text/javascript">
										jQuery(document).ready(function ()
										{
											var fieldValue = "<?php echo $fieldTag->value; ?>";
											var AttrRequired = jQuery('#<?php echo $field->id;?>').attr('required');
											if (typeof AttrRequired !== typeof undefined && AttrRequired !== false)
											{
												if (fieldValue)
												{
													jQuery('#<?php echo $fieldTag->id;?>').removeAttr("required");
													jQuery('#<?php echo $fieldTag->id;?>').removeClass("required");
												}
											}
										});
									</script>
								<?php
								}
								?>


							<div class="col-sm-12 rop-inputs w-100">
								<?php echo $fieldTag->input; ?>
									<div>
									<?php
									if (strpos($fieldTag->fieldname, 'clusterclusterid'))
									{
										$clusterFieldName = $fieldTag->fieldname;
									}
									?>
									</div>
							</div>			

						</div>
					</div>
			<?php
				}
				}?>
				</div>

			<?php	
			}
		}
		else
		{
			
			foreach ($this->form_extra->getFieldset($fieldset->name) as $field)
			{				
				$description = $field->description;

				if($useTooltip)
				{
					$field->description = '';
				}

				$isUcmsubform = 0;

				if ($field->type == 'Ucmsubform')
				{
					$customColClass = 'col-xs-12 col-md-12 ucmsubform';
				}
				else
				{
					$customColClass = 'col-md-4 col-xs-12';
				}

				if (strpos($field->class, 'twoColumnUcmsubform') !== false)
				{
					$isUcmsubform   = 0;
				}

				if (!$field->hidden)
				{
					$className = ($field->type == 'Spacer') ? 'w-100' : '';

				?>
					<div class="<?php echo $customColClass . ' ' . $className;  ?> custom-form-style">
						<div class="form-group">
								<div class="col-sm-12 control-label w-100 text-left">
									<?php echo $field->label; ?>
	
								<?php if($useTooltip && $description){?>
								<i class="fa fa-info-circle"  title=""  data-toggle="tooltip" data-content="<?php echo $description; ?>" data-original-title="<?php echo $description; ?>"></i>
								<?php }?>
								</div>
								
								
								<?php
								// TODO :- Check and remove
								if ($field->type == 'File')
								{
									if ($this->copyRecId)
									{
										$field->setValue('');
									}

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
								<div class="col-sm-12 rop-inputs w-100">
									<?php echo $field->input; ?>
										<div>
										<?php
										if (strpos($field->fieldname, 'clusterclusterid'))
										{
											$clusterFieldName = $field->fieldname;
										}
										?>
										</div>
								</div>
						</div>
					</div>
				<?php
				}


			}		
			
		}
			?>
		</div>
		<?php

		/* //if (count($fieldSets) > 1)
		{ */
			if (count($this->form_extra->getFieldset($fieldset->name)))
			{

				foreach ($this->form_extra->getFieldset($fieldset->name) as $field)
				{
					if (!$field->hidden)
					{
						echo HTMLHelper::_("bootstrap.endTab");?>

						<?php
							break;
					}
				}


			}
		//}



	}?>
		<div class="form-actions buttons-mobile-view border-0 bg-none action-btns">
	<?php
		// Show next previous buttons only when there are mulitple tabs/groups present under that field type
		$fieldArray = $this->form_extra;

		foreach ($fieldArray->getFieldsets() as $fieldName => $fieldset)
		{
			if (count($fieldArray->getFieldsets()) > 1)
			{
				$setnavigation = true;
			}
		}

		if (isset($setnavigation) && $setnavigation == true && empty($tmpl))
		{
			?>
		<!-- <button type="button" class="btn btn-primary mt-20" id="previous_button" >
			<i class="icon-arrow-left-2"></i>
			<?php //echo Text::_('COM_TJUCM_PREVIOUS_BUTTON'); ?>
		</button>
		<button type="button" class="btn btn-primary mt-20" id="next_button" >
			<?php //echo Text::_('COM_TJUCM_NEXT_BUTTON'); ?>
			<i class="icon-arrow-right-2"></i>
		</button> -->
		<?php
		}

		if ($calledFrom == 'frontend')
		{
			?>
			<span class="pull-right mt-20">
			<?php

			if (($this->allow_auto_save || $this->allow_draft_save) && $itemState)
			{
				?>
				<input type="button" class="btn btn-default px-25 mobile-space" id="tjUcmSectionDraftSave"
				value="<?php echo Text::_("COM_TJUCM_SAVE_AS_DRAFT_ITEM"); ?>"
				onclick="tjUcmItemForm.saveUcmFormData();" />
				<?php
			}
			?>

			<input type="button" class="btn btn-primary px-25 mobile-space" value="<?php echo Text::_('COM_TJUCM_SAVE_ITEM'); ?>" id="tjUcmSectionFinalSave" onclick="tjUcmItemForm.saveUcmFormData();" />

			<?php if (empty($tmpl)) : ?>
			<input type="button" class="btn btn-primary px-25 mobile-space" value="<?php echo Text::_("COM_TJUCM_SAVE_CLOSE_ITEM"); ?>" id="tjUcmSectionFinalSaveClose" onclick="tjUcmItemForm.saveUcmFormData();" />
			<input type="button" class="btn btn-warning mobile-space" value="<?php echo Text::_('COM_TJUCM_CANCEL_BUTTON'); ?>" onclick="Joomla.submitbutton('itemform.cancel');" />
			<?php endif; ?>
			</span>
			<?php
		}
	?>
</div>
	<?php

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
	$enableGraph = isset($typeParams->ai_enable_graph) && $typeParams->ai_enable_graph == 1;

	if ($enableAi && $aiEnabledForType && count($fieldSets) > 1)
	{
		if ($enableGraph)
		{
			HTMLHelper::_('script', 'plugins/tjdashboardrenderer/piechart/assets/js/chartjs.js');
		}
		echo HTMLHelper::_("bootstrap.addTab", "tjucm_myTab", "ask-kb", "Ask KB");
		?>
		<div class="row">
			<div class="col-xs-12" style="padding: 20px 15px;">
				<div style="display: flex; justify-content: flex-end; margin-bottom: 15px;">
					<button type="button" id="ask-kb-trigger-btn" class="btn btn-success" style="background-color: #22c55e; border-color: #22c55e; font-weight: bold; font-size: 16px; padding: 10px 24px; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); transition: all 0.2s;">
						💬 Ask KB
					</button>
				</div>
				<div id="ask-kb-placeholder-text" style="color: #64748b; font-size: 15px; line-height: 1.6; border: 1px dashed #cbd5e1; padding: 25px; text-align: center; border-radius: 8px; background-color: #f8fafc;">
					Click the <strong>Ask KB</strong> button above to open the AI Knowledge Bank Assistant and get analysis, summaries, or ask custom queries about this assessment.
				</div>
			</div>
		</div>

		<!-- Chatbox Modal -->
		<div class="modal fade" id="askKbModal" tabindex="-1" role="dialog" aria-labelledby="askKbModalLabel" aria-hidden="true" style="z-index: 1050;">
			<div class="modal-dialog modal-lg" role="document" style="max-width: 800px; margin: 30px auto;">
				<div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
					<!-- Modal Header -->
					<div class="modal-header" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color: white; padding: 15px 20px; border-bottom: none; display: flex; align-items: center; justify-content: space-between;">
						<h5 class="modal-title" id="askKbModalLabel" style="font-weight: bold; font-size: 18px; margin: 0; display: flex; align-items: center; gap: 8px; color: white;">
							<span>💬</span> AI Knowledge Bank Assistant
						</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 0.8; font-size: 24px; border: none; background: none; outline: none; cursor: pointer;">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<!-- Modal Body (Chat Container) -->
					<div class="modal-body" style="padding: 0; background-color: #f1f5f9; display: flex; flex-direction: column; height: 500px;">
						<!-- Chat History -->
						<div id="ask-kb-chat-history" style="flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 15px;">
							<!-- Assistant Welcome Message -->
							<div class="chat-msg assistant" style="align-self: flex-start; max-width: 80%; background-color: white; border-radius: 12px 12px 12px 0; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); color: #334155; font-size: 14px; line-height: 1.5;">
								Hi! I am your Knowledge Bank Assistant. I can analyze your current assessment form inputs and provide instant insights. How can I help you today?
							</div>
						</div>
						
						<!-- Quick Options Container -->
						<div id="ask-kb-quick-options" style="padding: 10px 20px; background-color: #e2e8f0; border-top: 1px solid #cbd5e1; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
							<span style="font-size: 12px; color: #64748b; font-weight: bold; margin-right: 5px;">Quick Actions:</span>
							<button type="button" class="btn btn-xs ask-kb-option-btn" data-prompt="Need a summary" style="background-color: white; border: 1px solid #cbd5e1; border-radius: 20px; padding: 4px 12px; font-size: 12px; cursor: pointer; transition: all 0.2s; color: #334155;">📝 Need a Summary</button>
							<button type="button" class="btn btn-xs ask-kb-option-btn" data-prompt="Share me report / assessment insights" style="background-color: white; border: 1px solid #cbd5e1; border-radius: 20px; padding: 4px 12px; font-size: 12px; cursor: pointer; transition: all 0.2s; color: #334155;">📋 Share Report</button>
							<button type="button" class="btn btn-xs ask-kb-option-btn" data-prompt="Identify risks" style="background-color: white; border: 1px solid #cbd5e1; border-radius: 20px; padding: 4px 12px; font-size: 12px; cursor: pointer; transition: all 0.2s; color: #334155;">⚠️ Identify Risks</button>
							<button type="button" class="btn btn-xs ask-kb-option-btn" data-prompt="Get recommendations" style="background-color: white; border: 1px solid #cbd5e1; border-radius: 20px; padding: 4px 12px; font-size: 12px; cursor: pointer; transition: all 0.2s; color: #334155;">💡 Recommendations</button>
						</div>
					</div>
					<!-- Modal Footer (Chat Input) -->
					<div class="modal-footer" style="padding: 15px 20px; background-color: white; border-top: 1px solid #e2e8f0; display: flex; gap: 10px; align-items: center;">
						<input type="text" id="ask-kb-input" placeholder="Type a custom query about this form..." style="flex: 1; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s;" />
						<button type="button" id="ask-kb-send-btn" class="btn btn-success" style="background-color: #16a34a; border-color: #16a34a; padding: 10px 20px; font-weight: bold; border-radius: 8px; color: white;">Send</button>
					</div>
				</div>
			</div>
		</div>

		<!-- Script for Ask KB Logic -->
		<script type="text/javascript">
			jQuery(document).ready(function() {
				// Show/hide modal
				jQuery('#ask-kb-trigger-btn').on('click', function() {
					jQuery('#askKbModal').modal('show');
				});

				// Quick options clicks
				jQuery('.ask-kb-option-btn').on('click', function() {
					var prompt = jQuery(this).data('prompt');
					sendAskKbQuery(prompt);
				});

				// Input enter press
				jQuery('#ask-kb-input').on('keypress', function(e) {
					if (e.which === 13) {
						var prompt = jQuery(this).val().trim();
						if (prompt) {
							jQuery(this).val('');
							sendAskKbQuery(prompt);
						}
					}
				});

				// Send button click
				jQuery('#ask-kb-send-btn').on('click', function() {
					var prompt = jQuery('#ask-kb-input').val().trim();
					if (prompt) {
						jQuery('#ask-kb-input').val('');
						sendAskKbQuery(prompt);
					}
				});

				function sendAskKbQuery(queryText) {
					// Append user message
					var userHtml = '<div class="chat-msg user" style="align-self: flex-end; max-width: 80%; background-color: #16a34a; color: white; border-radius: 12px 12px 0 12px; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-size: 14px; line-height: 1.5;">' + 
						escapeHtml(queryText) + '</div>';
					jQuery('#ask-kb-chat-history').append(userHtml);
					scrollToBottom();

					// Add loading indicator
					var loadingId = 'loading-' + Date.now();
					var loadingHtml = '<div class="chat-msg assistant ' + loadingId + '" style="align-self: flex-start; max-width: 80%; background-color: white; border-radius: 12px 12px 12px 0; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); color: #64748b; font-size: 14px; display: flex; align-items: center; gap: 8px;">' +
						'<span class="spinner" style="display: inline-block; width: 16px; height: 16px; border: 2px solid #cbd5e1; border-top-color: #16a34a; border-radius: 50%; animation: spin 1s linear infinite;"></span> Analyzing form data and querying AI...</div>';
					jQuery('#ask-kb-chat-history').append(loadingHtml);
					scrollToBottom();

					// Serialize live form inputs
					var formData = jQuery('#item-form').serializeArray();

					jQuery.ajax({
						url: 'index.php?option=com_tjucm&task=item.generateReport',
						type: 'POST',
						data: {
							id: '<?php echo $this->item->id; ?>',
							client: '<?php echo $this->client; ?>',
							custom_prompt: queryText,
							form_data: JSON.stringify(formData)
						},
						dataType: 'json',
						success: function(response) {
							jQuery('.' + loadingId).remove();
							if (response.success) {
								var parsedContent = parseMarkdown(response.report);
								var answerHtml = '<div class="chat-msg assistant" style="align-self: flex-start; max-width: 80%; background-color: white; border-radius: 12px 12px 12px 0; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); color: #334155; font-size: 14px; line-height: 1.5;">' +
									parsedContent + '</div>';
								jQuery('#ask-kb-chat-history').append(answerHtml);
								initAiCharts();
							} else {
								var errorHtml = '<div class="chat-msg assistant" style="align-self: flex-start; max-width: 80%; background-color: #fee2e2; color: #991b1b; border-radius: 12px 12px 12px 0; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-size: 14px; line-height: 1.5;">' +
									'<strong>Error:</strong> ' + escapeHtml(response.message) + '</div>';
								jQuery('#ask-kb-chat-history').append(errorHtml);
							}
							scrollToBottom();
						},
						error: function() {
							jQuery('.' + loadingId).remove();
							var errorHtml = '<div class="chat-msg assistant" style="align-self: flex-start; max-width: 80%; background-color: #fee2e2; color: #991b1b; border-radius: 12px 12px 12px 0; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-size: 14px; line-height: 1.5;">' +
								'An error occurred while communicating with the AI server.</div>';
							jQuery('#ask-kb-chat-history').append(errorHtml);
							scrollToBottom();
						}
					});
				}

				function scrollToBottom() {
					var history = document.getElementById('ask-kb-chat-history');
					if (history) {
						history.scrollTop = history.scrollHeight;
					}
				}

				function escapeHtml(text) {
					return text
						.replace(/&/g, "&amp;")
						.replace(/</g, "&lt;")
						.replace(/>/g, "&gt;")
						.replace(/"/g, "&quot;")
						.replace(/'/g, "&#039;");
				}

				// Lightweight markdown parser for clean display
				function parseMarkdown(md) {
					if (!md) return '';
					var html = md;

					// Parse [CHART:type]...[/CHART]
					var chartIndex = 0;
					html = html.replace(/\[CHART:(pie|bar)\]([\s\S]*?)\[\/CHART\]/gi, function(match, type, jsonStr) {
						try {
							var cleanJson = jsonStr.replace(/```json|```/g, '').trim();
							var chartData = JSON.parse(cleanJson);
							var canvasId = 'ai-chart-' + Date.now() + '-' + (++chartIndex);
							
							var labelsAttr = encodeURIComponent(JSON.stringify(chartData.labels || []));
							var dataAttr = encodeURIComponent(JSON.stringify(chartData.data || []));
							var titleAttr = encodeURIComponent(chartData.title || '');

							return '<div class="ai-chart-container" style="margin: 15px 0; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; align-items: center; justify-content: center;">' +
								'<h4 style="margin: 0 0 10px 0; font-size: 14px; font-weight: bold; color: #1e3a8a;">' + (chartData.title || '') + '</h4>' +
								'<div style="position: relative; width: 100%; max-width: 320px; height: 180px; display: flex; justify-content: center;">' +
								'<canvas class="ai-chart-canvas" id="' + canvasId + '" data-chart-type="' + type + '" data-chart-title="' + titleAttr + '" data-chart-labels="' + labelsAttr + '" data-chart-data="' + dataAttr + '" style="width: 100%; height: 180px;"></canvas>' +
								'</div>' +
								'</div>';
						} catch (e) {
							console.error('Failed to parse chart JSON', e, jsonStr);
							return '<div class="alert alert-warning" style="margin: 10px 0; font-size: 12px;">Failed to render chart.</div>';
						}
					});
					
					// Replace blockquotes
					html = html.replace(/^\>\s+(.+)$/gm, '<blockquote>$1</blockquote>');
					
					// Replace headers
					html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
					html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
					html = html.replace(/^# (.*$)/gim, '<h1>$1</h1>');
					
					// Replace bold/italic
					html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
					html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
					
					// Replace lists
					html = html.replace(/^\s*-\s+(.+)$/gm, '<li>$1</li>');
					
					// Group list elements
					html = html.replace(/(<li>.*<\/li>)/gms, '<ul>$1</ul>');
					
					// Replace double newlines with paragraphs
					html = html.replace(/\n\s*\n/g, '</p><p>');
					html = '<p>' + html + '</p>';
					
					// Clean empty tags
					html = html.replace(/<p><\/p>/g, '');
					html = html.replace(/<p>\s*<ul>/g, '<ul>');
					html = html.replace(/<\/ul>\s*<\/p>/g, '</ul>');
					
					return html;
				}

				function initAiCharts() {
					if (typeof Chart === 'undefined') {
						console.warn('Chart.js is not loaded yet.');
						return;
					}
					jQuery('.ai-chart-canvas').each(function() {
						var $canvas = jQuery(this);
						if ($canvas.data('initialized')) {
							return;
						}
						$canvas.data('initialized', true);

						var type = $canvas.data('chart-type');
						var labels = JSON.parse(decodeURIComponent($canvas.data('chart-labels')));
						var data = JSON.parse(decodeURIComponent($canvas.data('chart-data')));
						var title = decodeURIComponent($canvas.data('chart-title'));

						var ctx = this.getContext('2d');
						var clrs = ["#4CB03B", "#F1CF0D", "#D84123", "#22b8f0", "#9c27b0", "#ff9800", "#009688", "#795548"];

						var config = {
							type: type === 'bar' ? 'bar' : 'doughnut',
							data: {
								labels: labels,
								datasets: [{
									label: title,
									backgroundColor: clrs.slice(0, labels.length),
									data: data,
									borderWidth: type === 'bar' ? 1 : 2,
									borderColor: '#ffffff'
								}]
							},
							options: {
								responsive: true,
								maintainAspectRatio: false,
								plugins: {
									legend: {
										display: type !== 'bar',
										position: 'bottom',
										labels: {
											boxWidth: 10,
											font: { size: 11 }
										}
									},
									tooltip: {
										enabled: true,
										callbacks: {
											label: function(context) {
												return ' ' + context.label + ': ' + context.raw;
											}
										}
									}
								}
							}
						};

						if (type === 'bar') {
							config.options.scales = {
								y: {
									beginAtZero: true,
									ticks: { font: { size: 10 } }
								},
								x: {
									ticks: { font: { size: 10 } }
								}
							};
						} else {
							config.options.cutout = '50%';
						}

						new Chart(ctx, config);
					});
				}
			});
		</script>
		<style>
			@keyframes spin {
				to { transform: rotate(360deg); }
			}
			.chat-msg p { margin-bottom: 8px; }
			.chat-msg p:last-child { margin-bottom: 0; }
		</style>
		<?php
		echo HTMLHelper::_("bootstrap.endTab");
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
		<?php echo Text::_('COM_TJLMS_NO_EXTRA_FIELDS_FOUND');?>
	</div>
<?php
}?>

<?php

// DPE - Hack - To copy the record

if ($this->copyRecId)
{
?>
<script type="text/javascript">
	jQuery(document).ready(function ()
	{
		// Check record id is empty and user tried to copy record
		if (jQuery.trim(jQuery('#recordId').val()) == '' || jQuery('#recordId').val() == undefined)
		{
			// Find the all parent contentid fields of subforms
			jQuery('.ucmsubform').find("input[name*='_contentid']").each(function(){

				// Check the field type is hidden and confirm its parent reference number
				if (jQuery(this).attr('type') == 'hidden')
				{
					// Reset the field value if trying to copy the record
					jQuery(this).val('');
				}
			});
		}
	});


</script>
<?php
}
?>
<input type="hidden" name="clusterFieldName" id="clusterFieldUniqueName" value="<?php echo $clusterFieldName; ?>"/>
<?php
$tmpl  = $app->input->get('tmpl', '', 'STRING');

if ($clusterFieldName == 'com_tjucm_ropvendors_clusterclusterid' && empty($tmpl))
{
$doc = Factory::getDocument();
$doc->addScript(Uri::root() . 'media/com_dpe/js/tjucmreverselist.js');
?>

<script type="text/javascript">
jQuery(document).ready(function() {
	alert(" bv");
	jQuery("#jform_<?php echo $clusterFieldName; ?>").change(function(){
		tjucmreverselist.getReverseListUrl();
	});
});
</script>
<?php
}
// DPE - Hack - End
?>
<script type="text/javascript">
jQuery(document).ready(function() {

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
</script>
