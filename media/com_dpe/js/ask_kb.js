jQuery(document).ready(function($) {
	// Show/hide modal
	$('#ask-kb-trigger-btn').on('click', function() {
		$('#askKbModal').modal('show');
	});

	// Quick options clicks
	$('.ask-kb-option-btn').on('click', function() {
		var prompt = $(this).data('prompt');
		sendAskKbQuery(prompt);
	});

	// Input enter press
	$('#ask-kb-input').on('keypress', function(e) {
		if (e.which === 13) {
			e.preventDefault();
			var prompt = $(this).val().trim();
			if (prompt) {
				$(this).val('');
				sendAskKbQuery(prompt);
			}
		}
	});

	// Send button click
	$('#ask-kb-send-btn').on('click', function() {
		var prompt = $('#ask-kb-input').val().trim();
		if (prompt) {
			$('#ask-kb-input').val('');
			sendAskKbQuery(prompt);
		}
	});

	// Copy button click handler
	$('#ask-kb-chat-history').on('click', '.copy-msg-btn', function() {
		var btn = $(this);
		var targetId = btn.data('target');
		var textToCopy = $('#' + targetId).find('.msg-text').text().trim();
		
		navigator.clipboard.writeText(textToCopy).then(function() {
			var oldHtml = btn.html();
			btn.html('✅ Copied!');
			btn.css({'background-color': '#dcfce7', 'color': '#15803d', 'border-color': '#bbf7d0'});
			setTimeout(function() {
				btn.html(oldHtml);
				btn.css({'background-color': '#f1f5f9', 'color': '#64748b', 'border-color': '#cbd5e1'});
			}, 2000);
		}).catch(function(err) {
			console.error('Could not copy text: ', err);
		});
	});

	// Download button click handler
	$('#ask-kb-chat-history').on('click', '.download-msg-btn', function() {
		var btn = $(this);
		var targetId = btn.data('target');
		var $msgText = $('#' + targetId).find('.msg-text');
		var messageHtml = $msgText.html();
		var modal = $('#askKbModal');
		var itemId = modal.attr('data-item-id') || 0;
		var client = modal.attr('data-client') || '';
		
		var oldHtml = btn.html();
		btn.html('⏳ Downloading...');
		btn.prop('disabled', true);
		
		try {
			// Clone the message element to avoid modifying the screen representation
			var $tempDiv = $('<div>').html(messageHtml);
			
			// Find all canvases in the original message element
			var origCanvases = $('#' + targetId).find('canvas');
			
			// Find all canvases in the cloned tempDiv and replace them with images
			$tempDiv.find('canvas').each(function(index) {
				var origCanvas = origCanvases.get(index);
				if (origCanvas) {
					try {
						var imgDataUrl = origCanvas.toDataURL("image/png");
						var $img = $('<img>', {
							src: imgDataUrl,
							style: 'max-width: 450px; height: auto; display: block; margin: 15px auto;'
						});
						$(this).replaceWith($img);
					} catch (e) {
						console.error("Failed to convert canvas to image", e);
					}
				}
			});
			
			var processedHtml = $tempDiv.html();
			
			// Create a dynamic hidden form to trigger browser download
			var form = $('<form>', {
				action: 'index.php?option=com_tjucm&task=item.downloadAiReportPdf',
				method: 'POST'
			});
			
			form.append($('<input>', {
				type: 'hidden',
				name: 'id',
				value: itemId
			}));
			
			form.append($('<input>', {
				type: 'hidden',
				name: 'client',
				value: client
			}));
			
			form.append($('<input>', {
				type: 'hidden',
				name: 'report_html',
				value: processedHtml
			}));
			
			$('body').append(form);
			form.submit();
			form.remove();
			
			// Restore button state after a small timeout
			setTimeout(function() {
				btn.html(oldHtml);
				btn.prop('disabled', false);
			}, 2000);
			
		} catch (err) {
			console.error("PDF download initiation failed: ", err);
			btn.html(oldHtml);
			btn.prop('disabled', false);
			alert('Failed to generate PDF. Please try again.');
		}
	});

	function sendAskKbQuery(queryText) {
		// Append user message
		var userHtml = '<div class="chat-msg user" style="align-self: flex-end; max-width: 80%; background-color: #00aeef; color: white; border-radius: 12px 12px 0 12px; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-size: 14px; line-height: 1.5;">' + 
			escapeHtml(queryText) + '</div>';
		$('#ask-kb-chat-history').append(userHtml);
		scrollToBottom();

		// Add loading indicator
		var loadingId = 'loading-' + Date.now();
		var loadingHtml = '<div class="chat-msg assistant ' + loadingId + '" style="align-self: flex-start; max-width: 80%; background-color: white; border-radius: 12px 12px 12px 0; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); color: #64748b; font-size: 14px; display: flex; align-items: center; gap: 8px;">' +
			'<span class="spinner" style="display: inline-block; width: 16px; height: 16px; border: 2px solid #cbd5e1; border-top-color: #00aeef; border-radius: 50%; animation: spin 1s linear infinite;"></span> Analyzing form data and querying AI...</div>';
		$('#ask-kb-chat-history').append(loadingHtml);
		scrollToBottom();

		// Serialize live form inputs
		var formData = $('#item-form').serializeArray();
		var modal = $('#askKbModal');
		var itemId = modal.attr('data-item-id') || 0;
		var client = modal.attr('data-client') || '';

		$.ajax({
			url: 'index.php?option=com_tjucm&task=item.generateReport',
			type: 'POST',
			data: {
				id: itemId,
				client: client,
				custom_prompt: queryText,
				form_data: JSON.stringify(formData)
			},
			dataType: 'json',
			success: function(response) {
				$('.' + loadingId).remove();
				if (response.success) {
					var parsedReport = parseMarkdown(response.report);
					var msgId = 'msg-' + Date.now();
					var answerHtml = '<div class="chat-msg assistant" id="' + msgId + '" style="align-self: flex-start; max-width: 80%; background-color: white; border-radius: 12px 12px 12px 0; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); color: #334155; font-size: 14px; line-height: 1.5; display: flex; flex-direction: column; gap: 8px;">' +
						'<div class="msg-text">' + parsedReport + '</div>' +
						'<div style="text-align: right; width: 100%; margin-top: 4px; display: flex; justify-content: flex-end; gap: 6px;">' +
							'<button type="button" class="copy-msg-btn btn btn-xs" data-target="' + msgId + '" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px 8px; font-size: 11px; color: #64748b; cursor: pointer; transition: all 0.2s; font-weight: bold; display: inline-flex; align-items: center; gap: 4px; outline: none;">' +
								'📋 Copy' +
							'</button>' +
							'<button type="button" class="download-msg-btn btn btn-xs" data-target="' + msgId + '" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px 8px; font-size: 11px; color: #64748b; cursor: pointer; transition: all 0.2s; font-weight: bold; display: inline-flex; align-items: center; gap: 4px; outline: none;">' +
								'📥 Download' +
							'</button>' +
						'</div>' +
					'</div>';
					$('#ask-kb-chat-history').append(answerHtml);
					initAiCharts();
				} else {
					var errorHtml = '<div class="chat-msg assistant" style="align-self: flex-start; max-width: 80%; background-color: #fee2e2; color: #991b1b; border-radius: 12px 12px 12px 0; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-size: 14px; line-height: 1.5;">' +
						'<strong>Error:</strong> ' + escapeHtml(response.message) + '</div>';
					$('#ask-kb-chat-history').append(errorHtml);
				}
				scrollToBottom();
			},
			error: function() {
				$('.' + loadingId).remove();
				var errorHtml = '<div class="chat-msg assistant" style="align-self: flex-start; max-width: 80%; background-color: #fee2e2; color: #991b1b; border-radius: 12px 12px 12px 0; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-size: 14px; line-height: 1.5;">' +
					'An error occurred while communicating with the AI server.</div>';
				$('#ask-kb-chat-history').append(errorHtml);
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
