<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjucm
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;

$fieldsets_counter = 0;
$layout = Factory::getApplication()->input->get('layout');

if ($this->form_extra) {
	// Iterate through the normal form fieldsets and display each one
	$fieldSets = $this->form_extra->getFieldsets();

	foreach ($fieldSets as $fieldset) {
		if (count($fieldSets) > 1) {
			if ($fieldsets_counter == 0) {
				echo HTMLHelper::_('bootstrap.startTabSet', 'tjucm_myTab');
			}

			$fieldsets_counter++;

			if (count($this->form_extra->getFieldset($fieldset->name))) {
				foreach ($this->form_extra->getFieldset($fieldset->name) as $field) {
					if (!$field->hidden) {
						$tabName = OutputFilter::stringURLUnicodeSlug(trim($fieldset->name));
						echo HTMLHelper::_("bootstrap.addTab", "tjucm_myTab", $tabName, $fieldset->name);
						break;
					}
				}
			}
		}
		?>
		<div class="row">
			<?php
			// Iterate through the fields and display them
			foreach ($this->form_extra->getFieldset($fieldset->name) as $field) {
				if (!$field->hidden) {
					?>
					<div class="col-xs-12 col-md-6">
						<div class="form-group">
							<div class="col-sm-4 control-label">
								<?php echo $field->label; ?>
							</div>
							<div class="col-sm-8">
								<?php echo $field->input; ?>
							</div>
							<?php
							// TODO :- Check and remove
							if ($field->type == 'File') {
								?>
								<script type="text/javascript">
									jQuery(document).ready(function () {
										var fieldValue = "<?php echo $field->value; ?>";
										var AttrRequired = jQuery('#<?php echo $field->id; ?>').attr('required');
										if (typeof AttrRequired !== typeof undefined && AttrRequired !== false) {
											if (fieldValue) {
												jQuery('#<?php echo $field->id; ?>').removeAttr("required");
												jQuery('#<?php echo $field->id; ?>').removeClass("required");
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

		if (count($fieldSets) > 1) {
			if (count($this->form_extra->getFieldset($fieldset->name))) {
				foreach ($this->form_extra->getFieldset($fieldset->name) as $field) {
					if (!$field->hidden) {
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
	$enableGraph = isset($typeParams->ai_enable_graph) && $typeParams->ai_enable_graph == 1;

	if ($enableAi && $aiEnabledForType && count($fieldSets) > 1) {
		if ($enableGraph) {
			HTMLHelper::_('script', 'plugins/tjdashboardrenderer/piechart/assets/js/chartjs.js');
		}
		HTMLHelper::_('script', 'media/com_dpe/js/ai_helper.js');
		echo HTMLHelper::_("bootstrap.addTab", "tjucm_myTab", "ask-kb", "Ask KB");
		?>
		<div class="row">
			<div class="col-xs-12" style="padding: 20px 15px;">
				<div style="display: flex; justify-content: flex-end; margin-bottom: 15px;">
					<button type="button" id="ask-kb-trigger-btn" class="btn btn-success"
						style="background-color: #22c55e; border-color: #22c55e; font-weight: bold; font-size: 16px; padding: 10px 24px; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); transition: all 0.2s;">
						💬 Ask KB
					</button>
				</div>
				<div id="ask-kb-placeholder-text"
					style="color: #64748b; font-size: 15px; line-height: 1.6; border: 1px dashed #cbd5e1; padding: 25px; text-align: center; border-radius: 8px; background-color: #f8fafc;">
					Click the <strong>Ask KB</strong> button above to open the AI Knowledge Bank Assistant and get analysis,
					summaries, or ask custom queries about this assessment.
				</div>
			</div>
		</div>

		<!-- Chatbox Modal -->
		<div class="modal fade" id="askKbModal" tabindex="-1" role="dialog" aria-labelledby="askKbModalLabel" aria-hidden="true"
			style="z-index: 1050;">
			<div class="modal-dialog modal-lg" role="document" style="max-width: 800px; margin: 30px auto;">
				<div class="modal-content"
					style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
					<!-- Modal Header -->
					<div class="modal-header"
						style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color: white; padding: 15px 20px; border-bottom: none; display: flex; align-items: center; justify-content: space-between;">
						<h5 class="modal-title" id="askKbModalLabel"
							style="font-weight: bold; font-size: 18px; margin: 0; display: flex; align-items: center; gap: 8px; color: white;">
							<span>💬</span> AI Knowledge Bank Assistant
						</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"
							style="color: white; opacity: 0.8; font-size: 24px; border: none; background: none; outline: none; cursor: pointer;">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<!-- Modal Body (Chat Container) -->
					<div class="modal-body"
						style="padding: 0; background-color: #f1f5f9; display: flex; flex-direction: column; height: 500px;">
						<!-- Chat History -->
						<div id="ask-kb-chat-history"
							style="flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 15px;">
							<!-- Assistant Welcome Message -->
							<div class="chat-msg assistant"
								style="align-self: flex-start; max-width: 80%; background-color: white; border-radius: 12px 12px 12px 0; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); color: #334155; font-size: 14px; line-height: 1.5;">
								Hi! I am your Knowledge Bank Assistant. I can analyze your current assessment form inputs and
								provide instant insights. How can I help you today?
							</div>
						</div>

						<!-- Quick Options Container -->
						<div id="ask-kb-quick-options"
							style="padding: 10px 20px; background-color: #e2e8f0; border-top: 1px solid #cbd5e1; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
							<span style="font-size: 12px; color: #64748b; font-weight: bold; margin-right: 5px;">Quick
								Actions:</span>
							<button type="button" class="btn btn-xs ask-kb-option-btn" data-prompt="Need a summary"
								style="background-color: white; border: 1px solid #cbd5e1; border-radius: 20px; padding: 4px 12px; font-size: 12px; cursor: pointer; transition: all 0.2s; color: #334155;">📝
								Need a Summary</button>
							<button type="button" class="btn btn-xs ask-kb-option-btn"
								data-prompt="Share me report / assessment insights"
								style="background-color: white; border: 1px solid #cbd5e1; border-radius: 20px; padding: 4px 12px; font-size: 12px; cursor: pointer; transition: all 0.2s; color: #334155;">📋
								Share Report</button>
							<button type="button" class="btn btn-xs ask-kb-option-btn" data-prompt="Identify risks"
								style="background-color: white; border: 1px solid #cbd5e1; border-radius: 20px; padding: 4px 12px; font-size: 12px; cursor: pointer; transition: all 0.2s; color: #334155;">⚠️
								Identify Risks</button>
							<button type="button" class="btn btn-xs ask-kb-option-btn" data-prompt="Get recommendations"
								style="background-color: white; border: 1px solid #cbd5e1; border-radius: 20px; padding: 4px 12px; font-size: 12px; cursor: pointer; transition: all 0.2s; color: #334155;">💡
								Recommendations</button>
						</div>
					</div>
					<!-- Modal Footer (Chat Input) -->
					<div class="modal-footer"
						style="padding: 15px 20px; background-color: white; border-top: 1px solid #e2e8f0; display: flex; gap: 10px; align-items: center;">
						<input type="text" id="ask-kb-input" placeholder="Type a custom query about this form..."
							style="flex: 1; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s;" />
						<button type="button" id="ask-kb-send-btn" class="btn btn-success"
							style="background-color: #16a34a; border-color: #16a34a; padding: 10px 20px; font-weight: bold; border-radius: 8px; color: white;">Send</button>
					</div>
				</div>
			</div>
		</div>

		<!-- Script for Ask KB Logic -->
		<script type="text/javascript">
			jQuery(document).ready(function () {
				// Show/hide modal
				jQuery('#ask-kb-trigger-btn').on('click', function () {
					jQuery('#askKbModal').modal('show');
				});

				// Quick options clicks
				jQuery('.ask-kb-option-btn').on('click', function () {
					var prompt = jQuery(this).data('prompt');
					sendAskKbQuery(prompt);
				});

				// Input enter press
				jQuery('#ask-kb-input').on('keypress', function (e) {
					if (e.which === 13) {
						var prompt = jQuery(this).val().trim();
						if (prompt) {
							jQuery(this).val('');
							sendAskKbQuery(prompt);
						}
					}
				});

				// Send button click
				jQuery('#ask-kb-send-btn').on('click', function () {
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
							id: '<?php echo $this->id; ?>',
							client: '<?php echo $this->client; ?>',
							custom_prompt: queryText,
							form_data: JSON.stringify(formData)
						},
						dataType: 'json',
						success: function (response) {
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
						error: function () {
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

			});
		</script>
		<style>
			@keyframes spin {
				to {
					transform: rotate(360deg);
				}
			}

			.chat-msg p {
				margin-bottom: 8px;
			}

			.chat-msg p:last-child {
				margin-bottom: 0;
			}
		</style>
		<?php
		echo HTMLHelper::_("bootstrap.endTab");
	}

	if (count($fieldSets) > 1) {
		echo HTMLHelper::_('bootstrap.endTabSet');
	}
} else {
	?>
	<div class="alert alert-info">
		<?php echo Text::_('COM_TJLMS_NO_EXTRA_FIELDS_FOUND'); ?>
	</div>
	<?php
}
