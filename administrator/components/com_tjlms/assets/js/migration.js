var database_functions = ['fixCourseAlias', 'fixLessonAlias', 'fixOtherDbChanges','migrateCourseTrack','removeOrphanedLessonFiles','updateCertificateTags','migrateTests'];
var fc = 0;
var eachfunprogress = parseInt(100/database_functions.length,10);

function fixdatabase()
{
	jQuery('.tjBs3').hide();
	jQuery('.fix-info').html('<div class="progress-container"></div>');
	jQuery('.fix-info').show();

	/* Hide all alerts msgs */
	var obj = jQuery('.fix-info .progress-container');
	var status = new createProgressbar(obj);

	var eachfunprogress = parseInt(100/database_functions.length,10);

	database_functions.forEach(function(functiontocall)
	{
		statusdiv = "<div class='"+ functiontocall +" alert alert-plain'>" +
						"<span class='before'>" + Joomla.JText._('COM_TJLMS_TOOLBAR_DATABASE_FIX' + functiontocall.toUpperCase()) + "</span>" +
						"<span class='after'>" + Joomla.JText._('COM_TJLMS_TOOLBAR_DATABASE_FIX_SUCCESS_MSG')+ "</span>" +
					"</div>";
		jQuery('.fix-info').append(statusdiv);
	});

	extecuteFunctions(status);
	return false;
}

function extecuteFunctions(status)
{
	var functiontocall = database_functions[fc];
	jQuery.ajax({
			url: 'index.php?option=com_tjlms&task=database.' + functiontocall + '&tmpl=component',
			type: 'POST',
			dataType:'json',
			async:false,

			success: function(response){

				if (functiontocall != 'migrateTests')
				{
					progressper = eachfunprogress * (fc+1);
					status.setProgress(progressper);

					jQuery('.fix-info .' + functiontocall).removeClass('alert-plain').addClass('alert-success');
					jQuery('.fix-info .' + functiontocall + ' .after').show();

					fc++;
					extecuteFunctions(status);
				}

				else
				{
					var test_ids = response;
					for (var cnt = 0; cnt < test_ids.length; cnt++)
					{
						migrateTestQuestions(test_ids[cnt]);
					}
				}
			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				jQuery('.fix-info .' + functiontocall).removeClass('alert-plain').addClass('alert-error');
				jQuery('.fix-info .' + functiontocall + ' .after').html(jqXHR.responseText);
				jQuery('.fix-info .' + functiontocall + ' .after').show();
			}
		});

		if (functiontocall === 'migrateTests')
		{
			status.setProgress(100);

			jQuery('.fix-info .' + functiontocall).removeClass('alert-plain').addClass('alert-success');
			jQuery('.fix-info .' + functiontocall + ' .after').show();
		}
}

function migrateTestQuestions(test_id)
{
	jQuery.ajax({
		url: 'index.php?option=com_tjlms&task=database.migrateTestQuestions&tmpl=component',
		data: {test_id:test_id},
		type: 'POST',
		dataType:'json',
		success: function(response)
		{
			msg = '<div>' + "<?php echo JText::_('COM_TJLMS_TOOLBAR_DATABASE_FIX_TEST_MGR_SUCCESS_MSG')?>" + test_id + '</div>';
			jQuery('.fix-info .tmttest-fix').append(msg);
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			jQuery('.fix-info .tmttest-fix').removeClass('alert-plain').addClass('alert-error');
			jQuery('.fix-info .tmttest-fix .after').html(jqXHR.responseText);
			jQuery('.fix-info .tmttest-fix .after').show();
		}
	});
}

/*function fixColumnIndexes()
{
	jQuery('.tjBs3').hide();
	jQuery('.fix-info').html('<div class="progress-container"></div>');
	jQuery('.fix-info').show();

	var obj = jQuery('.fix-info .progress-container');
	var status = new createProgressbar(obj);

	var functiontocall = 'fixColumnIndexes';

	statusdiv = "<div class='"+ functiontocall +" alert alert-plain'>" +
						"<span class='before'>" + Joomla.JText._('COM_TJLMS_TOOLBAR_DATABASE_FIX' + functiontocall.toUpperCase()) + "</span>" +
						"<span class='after'>" + Joomla.JText._('COM_TJLMS_TOOLBAR_DATABASE_FIX_SUCCESS_MSG')+ "</span>" +
					"</div>";
	jQuery('.fix-info').append(statusdiv);


	jQuery.ajax({
			url: 'index.php?option=com_tjlms&task=database.' + functiontocall + '&tmpl=component',
			type: 'POST',
			dataType:'json',
			async:false,

			success: function(response){

				status.setProgress(100);

				jQuery('.fix-info .' + functiontocall).removeClass('alert-plain').addClass('alert-success');
				jQuery('.fix-info .' + functiontocall + ' .after').show();

			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				jQuery('.fix-info .' + functiontocall).removeClass('alert-plain').addClass('alert-error');
				jQuery('.fix-info .' + functiontocall + ' .after').html(jqXHR.responseText);
				jQuery('.fix-info .' + functiontocall + ' .after').show();
			}
		});

	return false;
}*/

function addReminderTemplates()
{
	jQuery('.tjBs3').hide();
	jQuery('.fix-info').html('<div class="progress-container"></div>');
	jQuery('.fix-info').show();

	/* Hide all alerts msgs */
	var obj = jQuery('.fix-info .progress-container');
	var status = new createProgressbar(obj, Joomla.JText._('COM_TJLMS_TOOLBAR_DATABASE_ADDREMINDERTEMPLATES_CREATING'));

	var functiontocall = 'addReminderTemplates';

	statusdiv = "<div class='"+ functiontocall +" alert alert-plain'>" +
						"<span class='before'>" + Joomla.JText._('COM_TJLMS_TOOLBAR_DATABASE_' + functiontocall.toUpperCase()) + "</span>" +
						"<span class='after'>" + Joomla.JText._('COM_TJLMS_TOOLBAR_DATABASE_FIX_SUCCESS_MSG')+ "</span>" +
					"</div>";
	jQuery('.fix-info').append(statusdiv);


	jQuery.ajax({
			url: 'index.php?option=com_tjlms&task=database.' + functiontocall + '&tmpl=component',
			type: 'POST',
			dataType:'json',
			async:false,

			success: function(response){

				status.setProgress(100);

				jQuery('.fix-info .' + functiontocall).removeClass('alert-plain').addClass('alert-success');
				jQuery('.fix-info .' + functiontocall + ' .after').show();
				jQuery('div#toolbar > .btn-wrapper:last-child').hide();

			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				jQuery('.fix-info .' + functiontocall).removeClass('alert-plain').addClass('alert-error');
				jQuery('.fix-info .' + functiontocall + ' .after').html(jqXHR.responseText);
				jQuery('.fix-info .' + functiontocall + ' .after').show();
			}
		});

	return false;
}
