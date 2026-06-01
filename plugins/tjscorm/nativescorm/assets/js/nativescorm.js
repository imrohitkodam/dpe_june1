jQuery(document).ready(function() {
	tjscorm_myInterval = setInterval(function () {
				plugdataObject.current_position = '';
				plugdataObject.total_content = '';
				plugdataObject.lesson_status = '';
				plugdataObject.time_spent = '';
				updateData(plugdataObject);

	}, 10000);
});