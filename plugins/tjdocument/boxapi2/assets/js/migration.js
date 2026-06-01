var tjBoxapi2 = typeof tjBoxapi2 == "undefined" ? {} : tjBoxapi2;
tjBoxapi2.migrate = typeof tjBoxapi2.migrate == "undefined" ? {} : tjBoxapi2.migrate;
tjBoxapi2.migrate = {
	stopprocess : false,
	ajaxObj: null,
	$progContainer : null,
	init : function (container){

		this.hideProgressBar();

		if(!confirm(Joomla.JText._('PLG_TJDOCUMENT_BOXAPI2_CONFIRM_TO_MIGRATE')))
		{
			return false;
		}

		var successDoc = 0;
		var failedDoc = 0;
		var totalDoc = 0;
		var lastId = 0;
		var reqToken = '';

		var that  = this;
		var progressContainer = container ? container : 'form[name="adminForm"]';
		tjBoxapi2.migrate.$progContainer = jQuery(progressContainer);
		tjBoxapi2.migrate.showProgressBar();

		jQuery("button#box-migrate").attr("disabled", true);

		startProcessingDoc();

		// Get total documents
		function startProcessingDoc()
		{
			var postData = {'subtask':'gettotal'};
			tjBoxapi2.migrate.ajaxObj = jQuery.ajax({
				type: "POST",
				url: tjBoxapi2.migrate.url,
				data: postData,
				dataType: "JSON"
			})
			.done(function(request) {
				console.log('done')
				if (request['success'] == 1)
				{
					totalDoc = request['total'];
					reqToken    = request['token'];

					// If there are any documents start migration
					if (totalDoc)
					{
						migrateDoc(0);
					}
					else
					{
						var messages = [];
						messages['error'] = [request['error']];
						Joomla.renderMessages(messages);
						tjBoxapi2.migrate.hideProgressBar();
					}
				}
				else if(request['error'])
				{
					var messages = [];
					messages['error'] = [Joomla.JText._('PLG_TJDOCUMENT_BOXAPI2_NO_DOCUMENTS_TO_MIGRATE')];
					Joomla.renderMessages(messages);
				}
			})
			.fail(function(request) {
				var messages = [];
				messages['error'] = [Joomla.JText._('PLG_TJDOCUMENT_BOXAPI2_ERROR_GETTING_DOCUMENTS')];
				Joomla.renderMessages(messages);
			});
		}
		function migrateDoc()
		{
			var postData = {last_id : lastId, token : reqToken};
			tjBoxapi2.migrate.ajaxObj = jQuery.ajax({
				type: "POST",
				url: tjBoxapi2.migrate.url,
				data: postData,
				dataType: "JSON"
			})
			.done(function(request) {
				console.log('done')
				lastId = request['lastId'];
				if (request['success'] == 1)
				{
					successDoc++;
				}
				else
				{
					failedDoc++;
				}
				updateProgress();
			})
			.fail(function(request) {
				console.log('fail')
				failedDoc++;
			})
			.always(function() {
				console.log('always')
			});
		}

		function updateProgress()
		{
			var messages = [];
			if (successDoc)
			{
				messages['success'] = [tjBoxapi2.migrate.sprintf('PLG_TJDOCUMENT_BOXAPI2_N_DOCS_MIGRATED_SUCCESSFULLY', successDoc)];
			}
			if (failedDoc)
			{
				messages['error'] = [tjBoxapi2.migrate.sprintf('PLG_TJDOCUMENT_BOXAPI2_N_DOCS_MIGRATION_FAILED', failedDoc)];
			}

			var totalProcessed = successDoc + failedDoc;
			messages['notice'] = [tjBoxapi2.migrate.sprintf('PLG_TJDOCUMENT_BOXAPI2_X_DOCS_OUT_OF_Y_PROCESSED', totalProcessed, totalDoc)];

			Joomla.renderMessages(messages);

			updateLoader();

			if (totalProcessed < totalDoc && !tjBoxapi2.migrate.stopprocess)
			{
				migrateDoc();
			}
			else
			{
				tjBoxapi2.migrate.hideProgressBar();
			}
		}

		function updateLoader()
		{
			var totalProcessed = successDoc + failedDoc;
			var percent = parseInt(totalProcessed / totalDoc * 100);
			jQuery(".progress .bar",tjBoxapi2.migrate.$progContainer).css("width", percent+'%');
			jQuery(".progress .bar",tjBoxapi2.migrate.$progContainer).text(percent+'%');
		}
	},
	showProgressBar:function(){
		this.$progContainer.prepend(
			"<div class='progress progress-striped active' style='min-height: 25px;'>" +
				"<div class='bar'></div>" +
				"<button onclick='return tjBoxapi2.migrate.abort(); return false;' class='btn btn-danger btn-small pull-right'>" +
					Joomla.JText._('PLG_TJDOCUMENT_BOXAPI2_ABORTED') +
				"</button>" +
			"</div>" +
			"<div id='j-main-container'><div class='logmsg alert alert-info'>"+
			"<button type='button' data-dismiss='alert' class='close'>×</button>" +
			tjBoxapi2.migrate.sprintf('PLG_TJDOCUMENT_BOXAPI2_CHECK_LOG_FILE', tjBoxapi2.migrate.logpath) +
			"</div></div>");
	},
	hideProgressBar:function(){
		jQuery('.progress', this.$progContainer).remove();
	},
	abort : function(){
		if(!confirm(Joomla.JText._('PLG_TJDOCUMENT_BOXAPI2_CONFIRM_TO_ABORT'))){
			return false;
		}
		tjBoxapi2.migrate.ajaxObj.abort();
		tjBoxapi2.migrate.hideProgressBar();
		tjBoxapi2.migrate.stopprocess = true;
		jQuery('#system-message-container').append(
		'<div class="alert alert-warning">' +
			'<button type="button" data-dismiss="alert" class="close">×</button>' +
			'<h4 class="alert-heading"></h4><div>'+
				Joomla.JText._('PLG_TJDOCUMENT_BOXAPI2_MIGRATION_ABORTED')
			+'</div></div>');
		return false;
	},
	sprintf: function(format)
	{
		format = Joomla.JText._(format);

		for( var i=1; i < arguments.length; i++ ) {
			format = format.replace( /%s/, arguments[i] );
		}
		return format;
	}
}
