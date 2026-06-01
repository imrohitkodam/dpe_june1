var courseid='';
var scoid='';


jQuery( document ).ready(function() {
	jQuery('.sco-obj').click(function() {
		var scotitle= jQuery(this).attr("title");
		var res	=	 scotitle.split('&');

		if(res[0])
		{
			var c	=	res[0].split('=');
			var c_id	=	c[1];
		}
		if(res[1])
		{
			var s	=	res[1].split('=');
			var s_id	= s[1];
		}
		/*SET Course_d and sco_id globally*/
		var scorm	=	c_id;
		var scoid	=	s_id;
		var path	=	site_root +'index.php?option=com_tjlms&view=course&layout=loadsco&'+scotitle;


		setSCOinSession(scotitle,1);

	});


	jQuery('.toc-tool-collapse').click(function() {
			    jQuery(".tjlms_toc_panel").animate({width: 'toggle'});
			    jQuery(".tjlms_scorm_toc").removeClass('span3').addClass('span1');
			    jQuery(".tjlms_scorm_toc").css('width','auto');
			    jQuery(".tjlms_scom_player").removeClass('span9').addClass('span11');
				jQuery(".tjlms_tocpanel-collapsed").animate({width: 'toggle'});


		});
	jQuery('.toc-tool-expand').click(function() {
			    jQuery(".tjlms_toc_panel").animate({width: 'toggle'});;
			    jQuery(".tjlms_tocpanel-collapsed").animate({width: 'toggle'});
			    jQuery(".tjlms_scorm_toc").removeClass('span1').addClass('span3');
			       jQuery(".tjlms_scorm_toc").css('width','');
				jQuery(".tjlms_scom_player").removeClass('span11').addClass('span9');


		});
});

function setSCOinSession(scotitle,load)
{
	jQuery.ajax({
			url: root_url+ 'index.php?option=com_tjlms&controller=scorm&task=setSCOinSession',
			type: 'POST',
			dataType: 'json',
			data : {
				scotitle : scotitle,
			},
			error: function(){
				alert('Problem with AJAX in Loading SCO');
			},
			success: function(response)
			{
				if(response	==	1)
				{
					if(load	==	1)
						window.location.reload();
				}
			}
		});
}

