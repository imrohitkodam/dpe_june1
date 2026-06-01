//loading icon image path
//ajax url

var processing;
var hd_img = "";
var i = 0;
var pagCount = 0;      
var values;
var end_data = false;  
var captionShow = false;
var top_while_scroll;
var nextPageResponce = false;
var ajx_showimg_called = 0;
var gutterwidth = 0;
var formSubmittedAtOnce = false;

//Script Configuration


j =  jQuery.noConflict(true); 
       
jQuery(window).load( function (){

	
	/*Added by amol
	 * Submit form on page load if it finds bk = 1 in URL 
	 * */
	function getUrlVars()
	{
		var vars = {};
		var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
			vars[key] = value;
		});
		return vars;
	}
	
	var bk = getUrlVars()["bk"];

	if(bk == 1)
		{
		
		/* get some values from elements on the page: */
   		values = $('#searchform').serialize();
   
		$.ajax({
		beforeSend: function() { $("#loadings").show(); },
		  url: "index.php?option=com_advsearch&task=createsearchindexer.showdata",
		  type: "post",
		  data: values,
		  cache: false,
		  success: function(data){
		  formSubmittedAtOnce = true;
					  //console.log('Data is coming properly, there is problem with rearrange function');
			  $(".error").hide();
			  $(".main-div").hide(300);
			  $('.search').hide();
			  $('.modify-search').show();
			  $("#totalresult").show();
			  $(".pictures").html(data);
			  
			  
			  $("#totalcountdigit").text($(".advtotalcount").val());

			  if(!(data.indexOf("mpic") > 0 )){
					$("#loadings").hide();
					$("#totalresult").hide(); // Hide if not result found
					jQuery(".div-toggle-lable").hide();
		  			jQuery(".btnToggle").hide();
			  }else{
				  	jQuery(".div-toggle-lable").show();
			  		jQuery(".btnToggle").show();
			  }
			  
			  //get count of images added in eximage
			 var lastimage = $('.eximage').length;
				
				var imgNum=$('.nottodisplay').length;
			
				$('.nottodisplay').load(function(){
			
					if(!--imgNum)
					{
						rearrange();
						processing = false;
						$("#loadings").hide();
						$(".resultmessage").text($(".scount").val());
						$("#mdiv").show();
					}
				});
				
					
		  },
		  error:function(){
			  alert("failure");
			  $("#pictures").html('there is error while submit');
		  }
		});

	 }
	
	/*Added by amol
	 * Submit form on page load if it finds bk = 1 in URL
	 * Code ends 
	 * */

	
	
	
	
	
	
	
	j(".pictures").after('<div id="bload"><img src="'+loadingiconurl+'" /></div>');
	j(".pictures").after('<div id="norecord-found" >'+finishedmsg+'</div>');
	
			
	 //rearrange(); 
	 j('#loadings').hide();
	 j('.pictures').css('visibility','visible');
       
	//on scroll down make pagination
	jQuery(document).scroll(function(e){
	
		//--- set caption title ---
		if((j(window).scrollTop() - 150) > 0)
			top_while_scroll = j(window).scrollTop() - 150;
		//--- *** ---

		//check flag to stop multiple ajax requests
		
		if (processing == true || end_data == true)
			return false;
			
		if(!values)
			return false;
	  	
		if(j(window).scrollTop() + j(window).height() > j(document).height() - 1) {
			next_page();
		}
	});
	
      	  
//function to arrange images loaded on page
function rearrange()
{
		captionShow ? gutterwidth = gutterwidthl2 : gutterwidth = gutterwidthl1;
		
		// call gallery script 
		j(function() {
		jQuery('.pictures').gpGallery('img',{ row_min_height: rowminheigt,
									 		  row_max_height: rowmaxheight,
									  		  row_max_width: rowmaxwidth,
											  gutter: gutterwidth,
											  imgcnr_minwidth:imgcnr_minwidth,
											  captionShow:captionShow
											 }
									);
		})
		
		

		//get x coordinate of first left side image to identify new line
		var x = Math.round( Number(j('.eximage').eq(0).offset().left) );
		
		//check is data end or not
		var imgPicCount = j('.mpic').length;
		var total_images =  j('.advtotalcount').val();
		
		
		if(!(total_images > 0)){
				total_images = j("#totalcountdigit").text();
		}
		if(total_images <= imgPicCount  ){
			end_data = true;
		}
		
		
		//call our custom scrtip to arrange images
		arrange();
		//setTimeout( function() { arrange(); }, 5000 );
		
		
	
		if(!(imagecount > j('.nottodisplay').length)){
			//loop to remove last row images and save it in javascript variable
			for(i = j('.eximage').length; i>0; i--)
			{
				//get x coordinate of current image
				var leftadded = j(".eximage").eq(i-1).parent().attr("leftadded");

				//remove attribute from last row image and make it as original image
				j(".mpic").eq(i-1).find('a').find('img').unwrap();
				j(".mpic").eq(i-1).find('a').find('img').css('height', 'auto');
				j(".mpic").eq(i-1).find('a').find('img').css('width', 'auto');
				j(".mpic").eq(i-1).find('a').find('img').addClass('nottodisplay');
				j(".eximage").eq(i-1).attr('used', 'no');
			
				//remove last row images and save it in javascript variable
				var mpic_html = j('.mpic').eq(i-1).html();
				hd_img = hd_img + '<span class="mpic">'+mpic_html+"</span>";
				j('.mpic').eq(i-1).remove();
			
					
				//check is new row started or not. if yes the stop the loop
				if( leftadded == 'yes')
					i=0;
			}
		}else
		{
			hd_img = '';
		}
		
		//show new appended images
		//jQuery('.nottodisplay').css('position', 'inherit');
		//j(".nottodisplay").css('visibility', 'visible').animate({opacity: 1.0},animate_time);
		//j(".nottodisplay").removeClass('nottodisplay');
		jQuery('#bload').hide();
		j('#loadings').hide();
		
		
    	
    	
    		
    		
    		/*j(".nottodisplay").bind("main_event", function(event, params) {
				console.log(params);
			});*/

			showImage();
			
		
		
		if(end_data == true ){
		
					if(hd_img != ""  || hd_img != null)
						{
								j('.pictures').append(hd_img);
								var lastimage = jQuery('.eximage').length;
								//on last image load call rearrange
								jQuery('.eximage').eq(lastimage - 1).load(function(){
								// call gallery script 
								j(function() {
								jQuery('.pictures').gpGallery('img',{ row_min_height: rowminheigt,
															 		  row_max_height: rowmaxheight,
															  		  row_max_width: rowmaxwidth,
																	  gutter: gutterwidth,
																	  imgcnr_minwidth:imgcnr_minwidth
																	 }
															);
								})

								//arrange();
						
								});
								hd_img ="";
								showImage();
					}
					
		return false;
		}
		
		//processing = false;
}

function next_page(showCaptions, toggle){
		processing = true; //set flag to stop multiple ajax requests
		nextPageResponce = true //set flag for ajax responce 
		
		//pagCount ++; 
		
		//jQuery('#bload').show(); 
		var param = "";
		if(showCaptions == true || showCaptions == false){
			var showCaptionsval = showCaptions ? "show" : "hide";
			pagCount = 0;
			param = ajaxurl+'&start='+pagCount+'&showCaptions='+showCaptionsval;
			hd_img = "";
		}else{
			pagCount ++; 
			param = ajaxurl+'&start='+ pagCount;
		}
		
		toggle ? j('#loadings').show() : j('#bload').show();
		
		jQuery('#ncat').show();  
		
				
           	//Get data and append to  pictures div by ajax request
			jQuery.ajax({
					    url: param,
					    method:"GET",
					    data:values,
					    success: function(data){
							var response = jQuery(data);
							//var nextset = response.find('.pictures').html();
							//var mpic = response.find('.mpic').html();
							//mpic = mpic.replace(/^\s+|\s+$/g, "");
							
							 if(response == null && hd_img == "" || ! (data.indexOf("mpic") > 0 ) ){
							 	j("#norecord-found").show();
							 	j("#bload").hide();
							 	nextPageResponce = false;
							 	return false;
							 }
							//append html removed from last row
							 j('.pictures').append(hd_img);
							 //make blank string for temp html
							 hd_img = "";
							
							//append ajax data
							jQuery('.pictures').append(response);
							//console.log("appended");
							jQuery('.nottodisplay').css('position', 'fixed');
							//get count of images added in eximage
							var lastimage = jQuery('.eximage').length;
							//on last image load call rearrange
							/*jQuery('.eximage').eq(lastimage - 1).load(function(){
								rearrange();
								console.log("arranged");
								//processing = false;
							});*/
							
							var imgNum=jQuery('.nottodisplay').length;
							jQuery('.nottodisplay').load(function(){
								//console.log(imgNum);
								if(!--imgNum)
								{
									//console.log('All images loaded');
									rearrange();
								}
							});
							nextPageResponce = false;
					    }
					});

}


/*
      When the toggle switch is clicked, check off / de-select the associated checkbox
     */
   j('.toggle').live('click', function(e) {
	 		//show loading text
	 		
	 		if((nextPageResponce == true && pagCount == 0) || formSubmittedAtOnce == false){
	 			/*j('.loadingText').show();
	 			setTimeout(function() {
							j('.loadingText').fadeOut('fast');
						}, 1000); */
	 			return false;
	 		}
	 		//-------

		   if (j('#chkToggle:checked').val()) {
		     	j('#chkToggle:checked').removeAttr("checked");
		     	//j(this).text("Turn Captions On");
		     	j(this).val("Captions On");
		   }else{
		     	//j('#chkToggle:checked').attr("checked","true");
		     	j("#chkToggle").attr('checked', "checked");
		     	//j(this).text("Turn Captions Off");
		     	j(this).val("Captions Off");
		   }
		   jQuery(this).toggleClass("checked");
		   		e.preventDefault();
		   		
		   	captionShow = j('#chkToggle:checked').val()?true:false;
		   	pagCount = 0;
    		j('.pictures').html("");
    		j("#norecord-found").hide();
    		j('#bload').hide();
    		j(".error").hide();
			j(".main-div").hide(300);
			j('.search').hide();
	 		j('.modify-search').show();
    		end_data = false;
    		next_page(captionShow, true);
    		jQuery('#footer').hide();
    });

//check window has scroll bar
function checkScrollBar() {
            var hContent = j("body").height() - 400; // get the height of your content
            var hWindow = j(window).height(); // get the height of the visitor's browser window

            if(hContent>hWindow) { // if the height of your content is bigger than the height of the browser window, we have a scroll bar
                return true;    
            }

            return false;
    }

function showImage(){
	ajx_showimg_called++;
	var delay = 0;
	var loop_counter = 0;
	var nottodisplay_count = j('.nottodisplay').length;
	var opacity = 0;
		
	var grid_animate_time =  1; //(captionShow == true) ? 1 : animate_time;
		
	j(".category-right").css("margin-bottom", "0px");
	j('.nottodisplay').each(function(){ 
			loop_counter ++;
			processing = true;
			//^^ do for every instance less than the 16th (starting at 0)
			j(this).delay(delay).css({visibility : 'visible', position : 'inherit'}).animate({ opacity: 1 }, {
				duration: grid_animate_time, 
				queue: true, 
				step: function(){
						if(captionShow){
								if(! j(this).parent().hasClass("hi")){
									showImage();
									return false;
								}
								j(this).parent().addClass("border1");
							}
				},
				complete: function() {
													
										j(this).addClass('imgShown');
										j(this).parent().parent().attr("href", '#showCaption'+captionShow);
										j(this).parent().parent().parent().find(".rectitle").attr("href", '#showCaption'+captionShow);
										
										nottodisplay_count = j('.nottodisplay').length;
										var opacity = j('.nottodisplay').eq(nottodisplay_count - 1).css('opacity');
										//on complete the last image show
										if(opacity == 1 || nottodisplay_count == 1){
												processing = false;
												
												//add margin to bottom on complete all images show then user can scroll down to view the next slot
												j(".category-right").css('margin-bottom', '10px'); 
												
												//show no record found on all images show
												if(checkScrollBar()){
													if(end_data == true && hd_img == "" ){
														j("#norecord-found").html(finishedmsg);
														j("#norecord-found").show();
													}
												}

											if(end_data == false ){
													var hasScrollbar = j('body').outerHeight() > j(window).height();
														if (!hasScrollbar) {
																j(this).removeClass('nottodisplay'); //remove class nottodisplay to show image
																return next_page();
															}
												}
											positionFooter2();
											jQuery('#footer').show();
										}else{
												processing = true;
										}
										
										//increase height of image div on caption show
										if(captionShow){
											j(this).parent().css("height", j(this).height() + 65);
											j(this).parent().find(".imagetitle").show();
										}

										j(this).removeClass('nottodisplay'); //remove class nottodisplay to show image
										
										
										// if scroller at bottom then scroll down automatically to fire next request 
										if(j(window).scrollTop() + j(window).height() > j(document).height() - 50) {
											j('html,body').animate({
											scrollTop: j(document).height() - 49
											}, 1);
										}
										
									}
			});
			delay += grid_animate_time;
			//console.log(nottodisplay_count + " : " + loop_counter);
			
	});

}


function formValidation(cont){
	var valid = true;
	j(cont).each(function()
	{
		var str = j(this).val();
		var elementStyle = j(this).css('display');
		var containerStyle = j(this).closest(".container").css('display');
		(!containerStyle)  ? containerStyle = "nones" : containerStyle = containerStyle;
        if (containerStyle != "none" ){
		    if(str == "Parameter"  || str.match('^\\s*$') )
		    {
		    	console.log("str: "+str +", preg match: "+ !str.match(/^\S+$/)+ ", elementStyle: " + elementStyle +", containerStyle: "+containerStyle);
		        j(this).after('<span class="error">This field is required.</span>');
				valid = false;
		    }
        }
        
        
    });
    
    return valid;
    
}

jQuery("#searchform").submit(function(event)
{
	
	end_data = false;
	processing = false;
	pagCount = 0;  
	hd_img = "";
	formSubmittedAtOnce = true;
	
	// add classification name to the total record found
	var classific = jQuery("#classification option:selected").text();
	jQuery("#searchclassification").text(classific);	
		
	j(".error").hide();
	j("#norecord-found").hide();
	var res;
	
	if(!formValidation('.required'))
		return false;
	
	//return false;
	/*
	j.each(j('#searchform .text-input'), function()
	{
        if(j(this).val() == '')
        {
            j(this).after('<span class="error">This field is required.</span>');
			res = "Amol";	
			return res;
        }

    });*/
    
   /* if(j('.text-input').length < 1 )
	{
		
		alert('Please select a parameter.');
		res = "Amol";	
		j('#search').after('<span class="error">Please select a parameter.</span>');
		j('#totalresult').hide();
		
	}*/

	/*
	j.each(j('#searchform .text-range'), function()
	{
		var inputVal = j(this).val();
		var numericReg = /^\d*[0-9](|.\d*[0-9]|,\d*[0-9])?$/;
		if(j(this).val() == '' || !numericReg.test(inputVal))
        {
            j(this).after('<span class="error">This field is required.</span>');
			res = "Amol";	
			return res;
        }
    });*/
	
	if(res == "Amol")
		return false;	
	
	j("#loadings").show();

  /* stop form from submitting normally */
  event.preventDefault();

  /*clear result div*/
   j("#pictures").html('');
   
   j("#mdiv").hide();
	j('#footer').hide();
	
  /* get some values from elements on the page: */
   values = j(this).serialize()+'&formsubmit=1';
  /* Send the data using post and put the results in a div */

    j.ajax({
    beforeSend: function() { j("#loadings").show(); },
      url: "index.php?option=com_advsearch&task=createsearchindexer.showdata",
      type: "post",
      data: values,
      cache: false,
      success: function(data){
				  
		  j(".error").hide();
		  j(".main-div").hide(300);
		  j('.search').hide();
 		  j('.modify-search').show();
		  j("#totalresult").show();
          j(".pictures").html(data);
          
          
          j("#totalcountdigit").text(j(".advtotalcount").val());

		  if(! (data.indexOf("mpic") > 0 )){
		  		console.log("data not found");
	  			j("#loadings").hide();
	  			j("#totalresult").hide(); // Hide if not result found
	  			jQuery(".btnToggle").hide();
	  			jQuery(".div-toggle-lable").hide();
		  }else{
		  		jQuery(".div-toggle-lable").show();
		  		jQuery(".btnToggle").show();
		  		//console.log("data found");
	  		}
		  
          //get count of images added in eximage
		 var lastimage = jQuery('.eximage').length;
		 	
			var imgNum=jQuery('.nottodisplay').length;
		
			jQuery('.nottodisplay').load(function(){
		
				if(!--imgNum)
				{
					rearrange();
					processing = false;
					j("#loadings").hide();
					j(".resultmessage").text(j(".scount").val());
					j("#mdiv").show();
				}
			});
			
			if(data.indexOf("img") > 0)
				jQuery(".btnToggle").show();
			else
				jQuery(".btnToggle").hide();
				
      },
      error:function(){
          alert("failure");
          j("#pictures").html('there is error while submit');
      }
    }); 
    
});


});

//function to arrange alingment of images
function arrange()
{
	var imgcount = 0;
	var container_width = j('.pictures').width() - 15; //main container width
	var rowwidth = 0;
	var diff = 0;
	var aranged = "";
	var leftadded = "";
	var divwidth = 0;
	var totalLeftAdded = j(".leftadded").length;
	var imgArray = new Array();
	var initialCount = j(".arranged").filter(".leftadded").length;
	
	for(b = initialCount; b<= totalLeftAdded; b ++){
	
			indexPosition = j(".leftadded").eq(b).filter(".hi").index(".hi");
			nextIndexPosition = j(".leftadded").eq(b+1).filter(".hi").index(".hi");
			
			//console.log("totalLeftAdded: "+totalLeftAdded + " , b"+b+", end_data: "+end_data);
			if(totalLeftAdded == b && end_data == true){
				indexPosition = j(".leftadded").eq(b - 1).filter(".hi").index(".hi");
				nextIndexPosition = j(".hi").length;
			}
			
			imgcount = nextIndexPosition - indexPosition;
			
			if(nextIndexPosition > 0){
			
				for(i = indexPosition;  i<nextIndexPosition; i ++){
					var divwidth = j(".hi").eq(i).width();
					rowwidth = rowwidth + divwidth; 
				}
				//rowwidth = rowwidth - divwidth;
				diff = container_width - rowwidth;	
				diff = diff - (gutterwidth * imgcount - 2);
				
				//make top margin to 5px
				var firstRowMargin_top = (indexPosition == 0) ? true : false; 
				
				for(i = indexPosition;  i<nextIndexPosition; i ++)
				{
						if(captionShow){
								if(totalLeftAdded == b && end_data == true){
									if(container_width - rowwidth < 100){
										diff = (i == indexPosition) ? diff - (10 * (imgcount - 1) ) : diff;
										var padding = ((diff/imgcount) - 1) / 2;
									}else{
										var padding = 5;
									}
									
									var paddingBottom = 17;
									//var marginleft = (i == indexPosition) ?  0 : (  parseInt (j('.hi').eq(i).css('margin-left')) - 8);
									//j('.hi').eq(i).css('margin-left', marginleft);
								}else{
									//var padding = ((diff/imgcount) - 4) / 2;
									var paddingBottom = ((diff/imgcount) - 1);
									var margin = ((diff/imgcount) - 2 ) + parseInt( j('.hi').eq(i).css('margin-left') );
								}
								
								/*j('.hi').eq(i).css('padding-left', padding);
								j('.hi').eq(i).css('padding-right', padding);*/
								
								if(i != indexPosition){
									j('.hi').eq(i).css('margin-left', margin+"px");
								}
								
								j('.hi').eq(i).css('padding-bottom', paddingBottom);
								j('.hi').eq(i).addClass("arranged");
								
								firstRowMargin_top ? j('.hi').eq(i).css('margin-top', '5px') : "" ;
								
						}else{
								if(totalLeftAdded == b && end_data == true){
									var padding = 4;
									var paddingBottom = 17;
								}else{
									var padding = ((diff/imgcount) - 0);
									//var padding = diff;
									var paddingBottom = ((diff/imgcount) - 1);
								}
								
								if(i != indexPosition){
									j('.hi').eq(i).css('padding-left', padding);
								}
								j('.hi').eq(i).css('padding-bottom', paddingBottom);
								j('.hi').eq(i).addClass("arranged");
								firstRowMargin_top ? j('.hi').eq(i).css('margin-top', '1px') : "" ;
						}
						
						//if(b == initialCount)
							//j('.hi').eq(i).find("img").css('border',"5px solid red");
						
				}
				
				rowwidth = 0;
			}
	}
}

 	 

//on mouse hover on image show image title
j(document).ready(function() {
	//open in new tab
    j('.imageInfoUrl').live('mousedown', function(event) {
     
    try{
			var href = j(this).attr("imageInfoUrl");
			if(event.ctrlKey == true || event.which == 2 || event.which == 3){
		      	j(this).attr("href", href);
			}else{
				j(this).attr("href", '#showCaption'+captionShow);
				//console.log("window location");
				//window.location.assign(href);
				window.location.href = href;
				//j(this).trigger(event);
				j(this).attr("href", href);
				j(this).trigger("click");
				//window.open(href);
				//document.location.href = href;
			}
		}catch(e){
			console.log(e);
		}
    });
    

	j("#norecord-found").live('click', function(){
			j('body,html').animate({
			scrollTop: 0
			}, go_to_top_speed);
			
			//j(".imgTitleLeft").css("margin-top", "0px");
			return false;
	});
	
	j(".mpic").live('mouseenter',function(){
		
	if(!j(this).find(".hi").find(".eximage").hasClass("imgShown") )
		return false;
		
	if(captionShow)
		return false;
	
	var titlestr = j(this).find(".rectitle").html();
	titlestr = titlestr.replace('<div class="imageTitleText">', '');
	titlestr = titlestr.replace('</div>', '');
	
	if( !(titlestr.match('^\\s*$')) ){
		j(".imgTitleLeft").show();
		j(".hoverwnn").html(j(this).find("#recordwnn").text()); 	
		j(".hovertitle").html(titlestr);
	 } 
	
		var arraysOfIds = j(this).find('img').each(function(){
					var parent = j(this).parent();
					j('#mydiv').remove();
					parent.append('<div id="mydiv">');
					var elH = j(this).css('height') - 1;
					var h = parent.height();
					var w = parent.width();
					//j('#mydiv').css('height',h);
					j('#mydiv').css('width',w-2);
					j('#mydiv').css('overflow','hidden');
					j('#mydiv').append('<div style="height:'+h+'px;width:'+w+'px;"></div>');
					j('#mydiv-image').css('visibility','hidden');
					//j('#mydiv').append(parent.parent().parent().children('#recordtitle').html());
		});
		
	});
    //on hover out from image hide image title
    j(".mpic").live('mouseleave',function(){
    	j('#mydiv').remove();
    	j(".imgTitleLeft").hide();
    });
    
  //set caption show / hide as history
	if(showCaptionsCookie == "show" || j('#chkToggle:checked').val()){
			showCaptions = true;
			captionShow = true;
			j("#chkToggle").attr('checked', "checked");
			j(".toggle").addClass("checked");
	}else if(showCaptionsCookie == "hide"){
			showCaptions = false;
			captionShow = false;
	}else{
			captionShow = true;
			showCaptions = true;
			j("#chkToggle").attr('checked', "checked");
			j(".toggle").addClass("checked");
	}
	// ---------------------------
	
	/*
      Add toggle switch after each checkbox.  If checked, then toggle the switch.
    */
     j('.checkbox').after(function(){
		   if (jQuery(this).is(":checked")) {
		   		return "<input type='button' class='toggle checked toggle-lable btnToggle' value='Captions Off' />";
		   }else{
		   		return "<input type='button' class='toggle toggle-lable  btnToggle' value='Captions On' />";
		   }
     });
     
     j(".modify-search").live("click", function(){
			positionFooter2();
	 });
	 
	 j("#classification").live("change", function(){
			jQuery('#footer').hide();
			var id = setInterval(function(){
				clearInterval(id);
				positionFooter2();
			},1000);
			
			
	 });
     
});


function positionFooter2() {
       
                footerHeight = jQuery("#footer").height();
                footerTop = ( (jQuery(window).scrollTop()+jQuery(window).height()-footerHeight) - 10 ) +"px";
				//jQuery('#footer').hide();
				console.log("footerTop: "+footerTop);
               if ( (jQuery(document.body).height()+footerHeight) < jQuery(window).height()) {
                   jQuery("#footer").css({
                        position: "absolute",
                        width:"100%"
                   }).animate({
                        top: footerTop
                   },0, function(){
					   //console.log("called");
					    jQuery('#footer').show();
					   })
                   
                   //jQuery('#footer').show();
               } else {
                   jQuery("#footer").css({
                        position: "static",
                        width:"100%"
                   })
                   jQuery('#footer').show();
               }
               
       }
