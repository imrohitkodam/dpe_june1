var template = {
	groupsUrl: Joomla.getOptions('system.paths').base + "/index.php?option=com_tjgophish&task=template.getTemplate&format=json",

	/* This function to get groups list ajax */
	getTemplate: function (templateFieldId) {
		var goPhishCampTemplate = jQuery('#'+templateFieldId).val();

		if (goPhishCampTemplate == '')
		{
			jQuery('.tjgophish-template-subject-title').addClass('hidden');
			jQuery('.tjgophish-template-text-title').addClass('hidden');
			jQuery('.tjgophish-template-html-title').addClass('hidden');
			jQuery('.tjgophish-template-preview').addClass('hidden');
			jQuery('.tjgophish-template-subject').html("");
			jQuery('.tjgophish-template-text').html("");
			jQuery('.tjgophish-template-html').html("");

			return;
		}

		var templateData = {'ttitle' : goPhishCampTemplate};

		jQuery.ajax({
			url: this.groupsUrl,
			type: 'POST',
			data: templateData,
			dataType:"json",
			headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token', '')},
			success: function (response) {
				response = response.data;

				if (jQuery('.tjgophish-template-subject').length > 0 && response.subject != '')
				{
					jQuery('.tjgophish-template-preview').removeClass('hidden');
					jQuery('.tjgophish-template-subject-title').removeClass('hidden');
					jQuery('.tjgophish-template-subject').html(response.subject);
				}
				else
				{
					jQuery('.tjgophish-template-html-title').addClass('hidden');
				}

				if (jQuery('.tjgophish-template-text').length > 0 && response.text != '')
				{
					jQuery('.tjgophish-template-preview').removeClass('hidden');
					jQuery('.tjgophish-template-text-title').removeClass('hidden');
					jQuery('.tjgophish-template-text').html(response.text);
				}
				else
				{
					jQuery('.tjgophish-template-html-title').addClass('hidden');
				}

				if (jQuery('.tjgophish-template-html').length > 0 && response.html != '')
				{
					jQuery('.tjgophish-template-preview').removeClass('hidden');
					jQuery('.tjgophish-template-html-title').removeClass('hidden');
					jQuery('.tjgophish-template-html').html(response.html);
				}
				else
				{
					jQuery('.tjgophish-template-html-title').addClass('hidden');
				}
			}
		});
	}
}
