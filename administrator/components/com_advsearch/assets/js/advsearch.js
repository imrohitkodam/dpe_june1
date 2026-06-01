var histSelectHtml = "";

jQuery('document').ready(function()
{
	
	
	jQuery('.search').css('display', 'none');
		opt=1;
		jQuery(".fields").empty(); 	
		jQuery(".remove").empty();	
		jQuery(".myfields").empty(); 	
		jQuery(".savedsearch").empty(); 	
		jQuery(".result").empty();  // Bug #716	


		jQuery(".pictures").empty();  // added by amol to hide result from old search
		jQuery("#norecord-found").empty(); // same here

		
		jQuery(".error").hide(); 	
		
		//jQuery(".multiple").multiselect().multiselectfilter();
		var type = jQuery(this).val();	
		/*if(type == 1)
		{
			jQuery(".search").hide(); 
			return false;
		}*/
		
		jQuery('.loader').css('display', 'block');

		jQuery('#type').val(type);
		jQuery.ajax
			({ 
				type : 'POST',
                url: 'index.php?option=com_advsearch&task=get_attributes&type=1',
                success: function(data)
                {
									
					if(data)
					{
						jQuery("#totalresult").hide();
						opt=1;
						jQuery(".fields").append(data);
						histSelectHtml = data;
						setCookie("data", data, 1);
						jQuery("#historyData").val(data);
						jQuery(".remove").append('<span class="remove sach"></span>');	
						jQuery(".search").show(); 	
						jQuery('.loader').css('display', 'none');
						
						jQuery("#addfield").trigger("click");
						jQuery('.search_filter').chosen();
						jQuery('.search').css('display', 'block');
						
					}
				}
               
            }); 	
            
            jQuery(".showcontainer").find(".remove").attr("origin", 'yes');
	
	
	jQuery('#reset').click(function()
	{
		jQuery(".search_filters").val('');
		jQuery(".text-input").val('');
		jQuery(".search_filter").val('').trigger("chosen:updated");
		jQuery(".multiple").val('');
		jQuery(".range").val('');
		
	
	});

	jQuery("#searchform").submit(function(event)
	{ 
		end_data 			= false;
		endData 			= false;
		processing 			= false;
		pagCount 			= 1;  
		hd_img 				= "";
		formSubmittedAtOnce = true;
		lastRowArray 		= []; //make empty incomplete row array
		image_counter 		= 0;
		ajaxdata 			= {};
		histImages 			= [];
		var classific 		= jQuery("#classification option:selected").text();
		// add classification name to the total record found
		jQuery("#searchclassification").text(classific);
			
		jQuery(".error").hide();
		jQuery("#btnNorecordFound").hide();
		var res;
		jQuery("#loadings").show();

		/* stop form from submitting normally */
		event.preventDefault();

		/*clear result div*/
		jQuery("#pictures").html('');
		jQuery("#mdiv").hide();
		jQuery('#footer').hide();
		
		/* get some values from elements on the page: */
		values 		= jQuery("#searchform :input[value!=''][value!='.'][value!='0']").serialize();
		processing 	= true;
		window.open(ajaxurl+"&"+values, '_blank');
		
	});

	var myfields_count 	= 0;
	var bk 				= getUrlVars()["bk"];

	if(bk == 1)
		histSelectHtml = getCookie("data");
	
	// Calls get_attributes task & shows the list of search filters
	jQuery("#classification").on('change', function()
	{
		jQuery('.search').css('display', 'none');
		opt=1;
		jQuery(".fields").empty(); 	
		jQuery(".remove").empty();	
		jQuery(".myfields").empty(); 	
		jQuery(".savedsearch").empty(); 	
		jQuery(".result").empty();  // Bug #716	


		jQuery(".pictures").empty();  // added by amol to hide result from old search
		jQuery("#norecord-found").empty(); // same here

		
		jQuery(".error").hide(); 	
		
		//jQuery(".multiple").multiselect().multiselectfilter();
		var type = jQuery(this).val();	
		/*if(type == 1)
		{
			jQuery(".search").hide(); 
			return false;
		}*/
		
		jQuery('.loader').css('display', 'block');

		jQuery('#type').val(type);
		jQuery.ajax
			({ 
				type : 'POST',
                url: 'index.php?option=com_advsearch&task=get_attributes&type='+type,
                success: function(data)
                {
									
					if(data)
					{
						jQuery("#totalresult").hide();
						opt=1;
						jQuery(".fields").append(data);
						histSelectHtml = data;
						setCookie("data", data, 1);
						jQuery("#historyData").val(data);
						jQuery(".remove").append('<span class="remove sach"></span>');	
						jQuery(".search").show(); 	
						jQuery('.loader').css('display', 'none');
						
						jQuery("#addfield").trigger("click");
						jQuery('.search_filter').chosen();
						jQuery('.search').css('display', 'block');
						
					}
				}
               
            }); 	
            
            jQuery(".showcontainer").find(".remove").attr("origin", 'yes');
		
	});
	
	
	function setCookie(c_name,value,exdays)
	{
		var exdate=new Date();
		exdate.setDate(exdate.getDate() + exdays);
		var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
		document.cookie=c_name + "=" + c_value;
	}
	
	function getCookie(c_name)
	{
		var c_value = document.cookie;
		var c_start = c_value.indexOf(" " + c_name + "=");
		if (c_start == -1)
		  {
		  c_start = c_value.indexOf(c_name + "=");
		  }
		if (c_start == -1)
		  {
		  c_value = null;
		  }
		else
		  {
		  c_start = c_value.indexOf("=", c_start) + 1;
		  var c_end = c_value.indexOf(";", c_start);
		  if (c_end == -1)
		  {
		c_end = c_value.length;
		}
		c_value = unescape(c_value.substring(c_start,c_end));
		}
		return c_value;
	}
	function getUrlVars()
	{
		var vars = {};
		var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
			vars[key] = value;
		});
		return vars;
	}
	
	
		
	jQuery("#attributes").live("change", function()
	{
		var currentObj =	$(this);
		var ids;
		var opt;
		var adv_count = jQuery('#adv_fields_count').val();
		jQuery('#adv_fields_count').remove();
		if(adv_count)
			jQuery(this).parent().parent().attr('id', adv_count);
		
		if(jQuery(this).parent().parent().attr('id')==undefined){
		count++;
		 jQuery(this).parent().parent().attr('id', count);
		 ids=count;}
		 else{
         ids=jQuery(this).parent().parent().attr('id');

		 }
		var attribute = jQuery(this).val();
		jQuery(this).parent().parent().children('.field').remove(); // remove form field on field change
	
		var divname = jQuery(this).parent().parent().attr('class');
		jQuery.ajax
			({	 
				type : 'POST',
                url: 'index.php?option=com_advsearch&task=getFormfield&field_key='+attribute,	
                success: function(data)
                {
					if(data)
					{
						//currentObj.parent().parent().css("background-color", "red"); //return false;
						currentObj.parent().parent().append(data);
						//jQuery(".multiple").multiselect().multiselectfilter();
						
					}
				}
               
            });
            
	});
	
	
		// Add Form Field
	jQuery('#addfield').click(function()
	{
		var append1 = '<div class="myfields">';
			append1 += '<div class="remove"><span class="remove sach"></span></div>';
			append1 += '<div class="fields" id="4">';
			
		var append2 = '</div>';
			append2 += '</div>';
		
		if(histSelectHtml == ""){
			histSelectHtml = getCookie("data");
		}
		
		$(".newcontainer").append(append1 + histSelectHtml + append2);
		return false;
		var optionscount = jQuery("select")[3].length;	
		if(opt < optionscount)
		{
			console.log("in if");
			jQuery('.container').clone(true).removeClass('container').addClass('myfields').appendTo('.newcontainer').show();
			myfields_count++;
			count++;
			opt++;
		}
		if(optionscount == opt)
		jQuery(this).hide();
	});
	
	// Remove form field
	jQuery('.remove').bind('click', function()
	{
		jQuery(this).parent().parent().remove();
	});
	
	jQuery('.remove').live('click', function()
	{
		jQuery(this).parent().parent().remove();
	});
	
	jQuery('.myremove').click(function()
	{
		opt--;
		var classname = jQuery(this).parent().parent().attr('class');
		alert(classname);
		if(classname != "showcontainer")
		{
			jQuery(this).parent().parent().remove();
		}
		//jQuery(this).parent().remove();

	});
	
	// Modify Search
	jQuery('.modify-search').click(function()
	{
		 jQuery('.modify-search').hide();
		 jQuery(".main-div").show();
		 jQuery('.search').show();
		 jQuery('.savesearchedmessge').hide();
	});
	
	jQuery('#savesearch').click(function()
	{	
		if(jQuery(this).is(":checked"))
		jQuery('.savename').show();
		else
		jQuery('.savename').hide();
		
	});

	jQuery('#searchname').focus(function()
	{	
		if(jQuery(this).val() == 'search name')
		jQuery(this).val('');
		
	});
	
	jQuery('#searchname').blur(function()
	{	
		if(jQuery(this).val() == '')
		jQuery(this).val('search name');

	});
	
	
	
	
	
		
});
