<?php
defined('_JEXEC') or die;

$itemId = isset($this->id) ? (int)$this->id : (isset($this->item->id) ? (int)$this->item->id : 0);

if ($itemId > 0) :
	$db = Joomla\CMS\Factory::getDbo();
	$query = $db->getQuery(true)
		->select($db->quoteName('params'))
		->from($db->quoteName('#__tj_ucm_types'))
		->where($db->quoteName('unique_identifier') . ' = ' . $db->quote($this->client));
	$db->setQuery($query);
	$typeParamsJson = $db->loadResult();
	$typeParams = json_decode($typeParamsJson);
	$enableGraph = isset($typeParams->ai_enable_graph) && $typeParams->ai_enable_graph == 1;
	if ($enableGraph) {
		Joomla\CMS\HTML\HTMLHelper::_('script', 'plugins/tjdashboardrenderer/piechart/assets/js/chartjs.js');
	}
	Joomla\CMS\HTML\HTMLHelper::_('script', 'media/com_dpe/js/ai_helper.js');
	Joomla\CMS\HTML\HTMLHelper::_('script', 'media/com_dpe/js/ask_kb.js');
?>

<!-- Floating Ask KB Button -->
<div style="position: fixed; bottom: 200px; right: 20px; z-index: 1040;">
	<button type="button" id="ask-kb-trigger-btn" class="btn btn-primary">
		💬 Ask KB
	</button>
</div>

<!-- Chatbox Modal -->
<div class="modal fade" id="askKbModal" tabindex="-1" role="dialog" aria-labelledby="askKbModalLabel" aria-hidden="true" style="z-index: 1050;" data-item-id="<?php echo $itemId; ?>" data-client="<?php echo htmlspecialchars($this->client); ?>">
	<div class="modal-dialog modal-lg" role="document" style="max-width: 800px; margin: 30px auto;">
		<div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
			<!-- Modal Header -->
			<div class="modal-header" style="background: linear-gradient(135deg, #00aeef 0%, #0087b7 100%); color: white; padding: 15px 20px; border-bottom: none; display: flex; align-items: center; justify-content: space-between;">
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
				<button type="button" id="ask-kb-send-btn" class="btn btn-primary" style="background-color: #00aeef; border-color: #00aeef; padding: 10px 20px; font-weight: bold; border-radius: 8px; color: white;">Send</button>
			</div>
		</div>
	</div>
</div>
<style>
	@keyframes spin {
		to { transform: rotate(360deg); }
	}
	.chat-msg p { margin-bottom: 8px; }
	.chat-msg p:last-child { margin-bottom: 0; }
	
	.chat-msg.assistant h1, .chat-msg.assistant h2, .chat-msg.assistant h3 {
		color: #0087b7;
		font-weight: bold;
		margin-top: 14px;
		margin-bottom: 8px;
		line-height: 1.3;
	}
	.chat-msg.assistant h1:first-child, .chat-msg.assistant h2:first-child, .chat-msg.assistant h3:first-child {
		margin-top: 0;
	}
	.chat-msg.assistant h1 { font-size: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
	.chat-msg.assistant h2 { font-size: 15px; }
	.chat-msg.assistant h3 { font-size: 14px; }
	.chat-msg.assistant ul, .chat-msg.assistant ol {
		margin: 0 0 10px 18px;
		padding: 0;
	}
	.chat-msg.assistant li {
		margin-bottom: 4px;
	}
	.chat-msg.assistant p {
		margin-bottom: 10px;
	}
	.chat-msg.assistant blockquote {
		background: #f8fafc;
		border-left: 3px solid #00aeef;
		padding: 8px 12px;
		margin: 0 0 10px 0;
		color: #475569;
		font-style: italic;
	}

	#ask-kb-trigger-btn {
		background-color: #00aeef;
		border-color: #00aeef;
		font-weight: bold;
		font-size: 14px;
		padding: 12px 20px;
		border-radius: 30px;
		box-shadow: 0 4px 12px rgba(0,0,0,0.15);
		transition: all 0.3s ease;
		display: flex;
		align-items: center;
		gap: 8px;
		color: white !important;
	}
	#ask-kb-trigger-btn:hover {
		background-color: #0087b7;
		border-color: #0087b7;
		transform: translateY(-2px);
		box-shadow: 0 6px 16px rgba(0,0,0,0.2);
	}
	.copy-msg-btn:hover {
		background-color: #e2e8f0 !important;
		color: #334155 !important;
		border-color: #94a3b8 !important;
	}
</style>
<?php endif; ?>

