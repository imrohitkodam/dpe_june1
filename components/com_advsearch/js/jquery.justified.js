var top_while_scroll = 0;
var callingFrom = "";
var myData = [];
var myDataKey = new Object();
var myDataLoop = 0;
var pagCount = 2;
var histClickedPage = 0;
var rowHeight = 150;
var imgMargin = 11;
var lastScrollPostion = 0;
var nextPageResponce = false;
var historybackpag = false;
var values = "";
var formSubmittedAtOnce = false;
var cuurrenturl_for_histback ="";
jQuery(document).ready(function(){

				
				processing =false;
				
				if(readCookie("settingtoggle") == "yes"){
					settingtoggle = true;
				}else if(readCookie("settingtoggle") == "no"){
					settingtoggle = false;
				}else{
					settingtoggle = true;
				}
				
				imgMargin = settingtoggle ? 13 : 13;
				
				//set toggle on history back
				//settingtoggle = jQuery('#chkToggle:checked').val() ? true : false;
				jQuery(".pictures").after('<div id="bload"><img src="'+loadingiconurl+'" /></div>');
				//jQuery(".pictures").after('<div id="bload"><span class="bloadCont"></span></div>');
				jQuery(".pictures").after('<div id="btnNorecordFound" >'+finishedmsg+'</div>');
				
				
				
				if( /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent) ) {
					 	window.addEventListener('orientationchange', handleOrientation, false);
						function handleOrientation() {
							if (orientation == 0 || orientation == 180) {
							  //portraitMode
							  reduceContWidth = 20;
							  orientation = "portaitmode";
							}
							else if (orientation == 90 || orientation == -90) {
							  //landscapeMode
							  orientation = "landscapemode";
							  reduceContWidth = 0;
							}
						}
						handleOrientation();
						mobileDevice = true;
				}else{
						var piccturesWidth = jQuery("#category-right").width() - 20;
						//console.log("piccturesWidth: "+piccturesWidth);
						jQuery(".pictures").css("width", piccturesWidth+"px");
						
						jQuery(window).resize(function(){
							var piccturesWidth_sd = jQuery("#category-right").width() - 20;
							var diff = piccturesWidth_sd - piccturesWidth;
							diff = (diff  > 0) ? diff : (diff * -1)
							
							//console.log(diff);
							if( diff > 15 ){
								piccturesWidth = piccturesWidth_sd;
								jQuery(".pictures").css("width", piccturesWidth+"px");
							}
						});
					
				}
				
				
				
				var userAgent = navigator.userAgent.toString().toLowerCase();
				if(userAgent.indexOf('chrome') > 0 ){
					broswerType = 'chrome';
				}
				
				//set data on history back
				if(window.location.href.split('#')[1] && (('localStorage' in window) && window['localStorage'] !== null)){
					jQuery("#bload").hide();
					jQuery("#loading").hide();
					historybackpag = true;
					jQuery("#pictures").addClass("justifiedGallery");
					cuurrenturl_for_histback = document.URL;
					toggleintarvalStart = false;
					processing = false;
					formSubmittedAtOnce = true;
					
					jQuery(".error").hide();
					jQuery(".main-div").hide();
					jQuery('.search').hide();
					jQuery('.modify-search').show();
					jQuery("#totalresult").show();
					jQuery(".div-toggle-lable").show();
					
					if(('localStorage' in window) && window['localStorage'] !== null){
						if ('pictures' in localStorage && window.location.hash) {
							jQuery("#pictures").html(localStorage.getItem('pictures'));
						}
						if ('main-div' in localStorage && window.location.hash) {
							jQuery(".main-div").html(localStorage.getItem('main-div'));
						}
						
						
					}

					//return false;
					urlHistoryImageId = window.location.href.split('#')[1];
					var histClickedPage = readCookie("histClickedPage");
					remove_count = readCookie("remove_count");
					lastScrollPostion = readCookie("histLastRowWidth");
					lastDiff = readCookie("lastDiff");
					histCont = jQuery("#pictures");
					callingFrom = "backhistory";
					
					if(readCookie("endData") == "yes" ){
						endData = true;
						if(checkScrollBar())
							jQuery("#btnNorecordFound").show();
					}
					pagCount = (histClickedPage > 0) ? histClickedPage : 1;
					if(histClickedPage > 0){
						pagCount = histClickedPage;
						lastRowArray = JSON.parse(readCookie("lastRowArray"));
					}else{
						pagCount = 0;
						lastRowArray = [];
						eraseCookie("lastRowArray");
					}
					values = readCookie("advancesearchvalues");
					jQuery("#totalcountdigit").text( readCookie("searchresultcount") );
					
					jQuery(".jg-image").each(function(count){
							var alt = jQuery(this).find("a").attr("title");
							var height = parseInt( jQuery(this).find("a").attr("org_height") );
							var href = jQuery(this).find("a").attr("href");
							var itemid = jQuery(this).find("a").attr("itemid");
							var orgheight = parseInt( jQuery(this).find("a").attr("org_height") );
							var orgwidth = parseInt( jQuery(this).find("a").attr("org_width") );
							var src = jQuery(this).find("img").attr("src");
							var title = jQuery(this).find("a").attr("title");
							var width  = parseInt( jQuery(this).find("a").attr("org_width") );
							var min_width = (width < min_img_cont_widht) ? (min_img_cont_widht - width) : 0;
							histImages.push({alt:alt, height:height, href:href, itemid:itemid, src:src, title:title, width:width, org_height:orgheight, org_width:orgwidth, min_width:min_width});
					});
						
					
					if(lastRowArray.length > 0){
						//console.log(lastRowArray.length);
						for(i = lastRowArray.length; i > 0; i--){
							var alt = lastRowArray[i - 1]['title'];
							var height = parseInt( lastRowArray[i - 1]['org_height'] );
							var href = lastRowArray[i - 1]['href'];
							var itemid = lastRowArray[i - 1]['itemid'];
							var orgheight = parseInt( lastRowArray[i - 1]['org_height'] );
							var orgwidth = parseInt( lastRowArray[i - 1]['org_width'] );
							var src = lastRowArray[i - 1]['src'];
							var title = lastRowArray[i - 1]['title'];
							var width  = parseInt( lastRowArray[i - 1]['org_width'] );
							var min_width = (width < min_img_cont_widht) ? (min_img_cont_widht - width) : 0;
							histImages.push({alt:alt, height:height, href:href, itemid:itemid, src:src, title:title, width:width, org_height:orgheight, org_width:orgwidth, min_width:min_width});
						}
					}
					
					
					
					 jQuery("#pictures").justifiedGallery({
						'usedSuffix':'lt240', 
						'justifyLastRow':false, 
						'rowHeight':rowHeight, 
						'fixedHeight':false, 
						//'lightbox':false, 
						'captions':false, 
						'margins':imgMargin,
						'refreshTime':500,
						'toggle':settingtoggle,
						'container':'#pictures',
						'data':'none',
						'callFunction':"backhistory"
					});
					jQuery(document).scrollTop(lastScrollPostion);
				
				}else{
					//jQuery(".loading").show();
					eraseCookie("endData");
					eraseCookie("lastRowArray");
					eraseCookie("histClickedImgId");
					eraseCookie("histClickedPage");
					eraseCookie("remove_count");
					eraseCookie("histBack");
					jQuery(".div-toggle-lable").hide();
					cuurrenturl_for_histback = document.URL+"#-imgId999999";
				}
				
				
				
				

				
				
				
				if(Object.keys(ajaxdata).length	> 0 && historybackpag == false){
					
					if(Object.keys(ajaxdata).length < imagecount)
						endData = true;
					
					jQuery("#pictures").justifiedGallery({
						'usedSuffix':'lt240', 
						'justifyLastRow':false, 
						'rowHeight':rowHeight, 
						'fixedHeight':false, 
						//'lightbox':false, 
						'captions':false, 
						'margins':imgMargin,
						'toggle':false,
						'toggleClicked':false,
						'container':'#myExample1'
					});
				}
				

				//on scroll down make pagination
				jQuery(document).scroll(function(e){ //return false;
						if(toggleintarvalStart)
							return false;
							
						lastScrollPostion = jQuery(document).scrollTop();
				        
						if(processing == true || endData == true)
							return false;
							
						if(jQuery(window).scrollTop() + jQuery(window).height() > jQuery(document).height() - 50) {
							callingFrom = "onScroll";
							return next_page();
						}
				});
				
				
				
				 jQuery('.toggle').live('click', function(e) {
					 //ajaxdata = whole_ajax_data;
					 remove_count = 0;
					 lastRowArray = []; //make empty incomplete row array
					 image_counter = 0;
					 console.log(processing + " : " + toggleintarvalStart  );
				 	if (processing == true || toggleintarvalStart == true)
							return false;
							
				 	 if (jQuery('#chkToggle:checked').val()) {
					 	jQuery('#chkToggle:checked').removeAttr("checked");
					 	jQuery(this).val("Captions On");
					 	settingtoggle = false;
					 	imgMargin = 13;
				   }else{
					 	jQuery("#chkToggle").attr('checked', "checked");
					 	jQuery(this).val("Captions Off");
					 	settingtoggle = true;
					 	imgMargin = 13;
				   }
				   var stng = settingtoggle ? "yes" : "no";
				   createCookie("settingtoggle", stng);
				   
				   jQuery("#pictures").justifiedGallery({
						'usedSuffix':'lt240', 
						'justifyLastRow':false, 
						'rowHeight':rowHeight, 
						'fixedHeight':false, 
						//'lightbox':false, 
						'captions':false, 
						'margins':imgMargin,
						'refreshTime':500,
						'toggle':settingtoggle,
						'container':'#pictures',
						'data':'none',
						'callFunction':"toggleLable"
					});
					
					
				   
				 });
				 
				 
				
				jQuery('.checkbox').after(function(){
					
					   if(settingtoggle) {
							jQuery("#chkToggle").attr('checked', "checked");
					   }
				
					   if (jQuery(this).is(":checked")) {
							return "<input type='button' class='toggle checked durr toggle-lable' value='Captions Off' />";
						 //return "<a href='#' class='toggle checked toggle-lable' ref='"+jQuery(this).attr("id")+"'></a>";
					   }else{
							return "<input type='button' class='toggle toggle-lable' value='Captions On' />";
						 //return "<a href='#' class='toggle toggle-lable' ref='"+jQuery(this).attr("id")+"'></a>";
					   }
				 });
				
				
				
				/*var loopcounter = 1;
				var ids = setInterval(function(){
					
					
					//jQuery(window).scrollTop()+jQuery(window).height()-jQuery("#pictures").height();
					
					if(loopcounter == 1){
							processing = false;
							loopcounter++;
						}else{
							callingFrom = "bigscreen";
						}
					
					if(processing)
						return false;
						
						
						var scrollTop = jQuery(window).scrollTop()+jQuery(window).height();
						//console.log("scrollTop: "+scrollTop +", "+jQuery("#pictures").height());
						
						if( scrollTop <   jQuery("#pictures").height()){
							clearInterval(ids);
							return false;
						}
						
						next_page(false, false);
					
					}, 500);*/
			 
			 
				
				
				jQuery('.jg-image').live("click", function(e) {
					
					//jQuery(this).css('overflow', 'visible');
					 //if(!settingtoggle){
						/*var arraysOfIds = jQuery(this).find('img').each(function(){
									var parent = jQuery(this).parent().parent();
									jQuery('#mydiv-1').remove();
									parent.append('<div id="mydiv-1" style="border:6px solid gray !important;">');
									var h = parseInt(jQuery(this).closest('.jg-row').height()) - 12;
									var w = parseInt(jQuery(this).width()) - 4;
									
									if(settingtoggle){
										h = h - 7;
										w = w - 1;
										jQuery('#mydiv-1').css('border-radius',"5px 5px 5px 5px");
									}
									
									//j('#mydiv').css('height',h);
									jQuery('#mydiv-1').css('width',w-2);
									jQuery('#mydiv-1').css('position',"absolute");
									jQuery('#mydiv-1').css('overflow','hidden');
									jQuery('#mydiv-1').css('top',0);
									jQuery('#mydiv-1').css('margin-bottom','5px');
									jQuery('#mydiv-1').append('<div style="height:'+h+'px;width:'+w+'px;"></div>');
									jQuery('#mydiv-image').css('visibility','hidden');
									//j('#mydiv').append(parent.parent().parent().children('#recordtitle').html());
						});*/
						
						/*var arraysOfIds = jQuery(this).find('img').each(function(){
									var parent = jQuery(this).parent().parent();
									jQuery('#mydiv-1').remove();
									parent.append('<div id="mydiv-1" style="border:6px solid gray !important;">');
									var h = jQuery(this).closest('.jg-row').height() - 12;
									var w = jQuery(this).width() - 6;
									
									/*if(settingtoggle){
										h = h - 7;
										w = w - 1;
										jQuery('#mydiv-1').css('border-radius',"5px 5px 5px 5px");
									}


									//j('#mydiv').css('height',h);
									jQuery('#mydiv-1').attr('width',w-2);
									jQuery('#mydiv-1').css('position',"absolute");
									jQuery('#mydiv-1').css('overflow','hidden');
									jQuery('#mydiv-1').css('top',0);
									jQuery('#mydiv-1').css('margin-bottom','5px');
									jQuery('#mydiv-1').css('visibility','hidden');
									jQuery('#mydiv-1').append('<div style="height:'+h+'px;width:'+w+'px;"></div>');
									jQuery('#mydiv-image').css('visibility','hidden');
									//j('#mydiv').append(parent.parent().parent().children('#recordtitle').html());
						});*/
					//}
					//return false;
					e.preventDefault();
					var histClickedImgId = "-"+jQuery(this).find(".imgLink").attr("id");
					var histClickedPage = jQuery(this).find(".imgLink").attr("pagcount");
					var href = jQuery(this).find(".imgLink").attr("href");
					
					var imgclkId = setInterval(function(){
							if(nextPageResponce)
								return false;
							else
								clearInterval(imgclkId);
								
							
							lastRowArray = JSON.stringify(lastRowArray);
							createCookie("lastRowArray", lastRowArray, 1);
							createCookie("histClickedImgId", histClickedImgId, 1);
							createCookie("histClickedPage", pagCount, 1);
							createCookie("remove_count", remove_count, 1);
							createCookie("histLastRowWidth", histLastRowWidth, 1);
							createCookie("lastDiff", lastDiff, 1);
							createCookie("advancesearchvalues", values, 1);
							createCookie("btnhistbackurl", cuurrenturl_for_histback, 1);
							createCookie("histLastRowWidth", lastScrollPostion, 1);
							var str_endData = endData ? "yes" : "no";
							createCookie("endData", str_endData, 1);
							window.location.hash = '#'+histClickedImgId;
							window.location.href = href;
						
					},200);
					
					
				});
				jQuery('#mydiv-1').css('visibility','visible');
				/*
				jQuery("#mydiv-1").attr("style", "border:6px solid gray !important;position:absolute;overflow:hidden;top:0;margin-bottom:5px");
				var mdivheight = jQuery("#mydiv-1").find("div").height();
				var mdivwidth = jQuery("#mydiv-1").find("div").width();
				jQuery("#mydiv-1").find("div").css("width", mdivwidth - 5);
				jQuery("#mydiv-1").find("div").css("height", mdivheight - 6);
				if(settingtoggle){
					jQuery('#mydiv-1').css('border-radius',"5px 5px 5px 5px");
				}
				*/
				
				jQuery(".jg-image").live('mouseenter',function(){
						
					if(settingtoggle)
						return false;
						
					//j(".imgTitleLeft").show(0);
					jQuery(".imgTitleLeft").stop(true, true).show();
					
					jQuery(".hoverwnn").html(jQuery(this).find("#recordwnn").text());
					jQuery(".hovertitle").html(jQuery(this).find("a").attr("title"));
					
					var arraysOfIds = jQuery(this).find('img').each(function(){
							var parent = jQuery(this).parent().parent();
							jQuery('#mydiv').remove();
							parent.append('<div id="mydiv" style="border:3px solid gray !important;">');
							var h = jQuery(this).closest('.jg-row').height() - 6;
							var w = jQuery(this).width() - 1;
							//j('#mydiv').css('height',h);
							jQuery('#mydiv').css('width',w-2);
							jQuery('#mydiv').css('overflow','hidden');
							jQuery('#mydiv').append('<div style="height:'+h+'px;width:'+w+'px;"></div>');
							jQuery('#mydiv-image').css('visibility','hidden');
							//j('#mydiv').append(parent.parent().parent().children('#recordtitle').html());
					});
					
				});
				
				
				//on hover out from image hide image title
				jQuery(".jg-image").live('mouseleave',function(){
					jQuery('#mydiv').remove();
					jQuery('.imgTitleLeft').delay(2000).hide(0);
				});
				
				
				jQuery("#btnNorecordFound").find("botton").live('click', function(){
					jQuery('body,html').animate({
					scrollTop: 0
					}, go_to_top_speed);
					
					return false;
				});
				 
				
			jQuery(document).scrollTop(lastScrollPostion);
     
		});
		
		
		
function next_page(showCaptions, toggle){ 

	//console.log(endData + " : " +formSubmittedAtOnce);

	if(endData == true || formSubmittedAtOnce == false)
		return false;
		
	processing = true; //set flag to stop multiple ajax requests
	nextPageResponce = false //set flag for ajax responce 
	image_counter = image_counter - remove_count;
	
		//jQuery('#bload').show(); 
		var param = "";
		
		param = ajaxurl+"&"+values+'&page='+ pagCount+'&imagecount='+ parseInt(imagecount)+'&s=s&ajaxCall=yes';
		//param+='&ajaxCall=yes';
		
		if(callingFrom != ""){
			jQuery("#bload").show();
			callingFrom = "";
		}
		
		jQuery.getJSON(param, function(data) {
				nextPageResponce = true;
				pagCount ++;
				
				//console.log(lastRowArray.length);
				if(typeof(data) == "string"){
					data ={};
					var imagesl = 0;
					endData = true;
				}else{
					var imagesl = new Array(Object.keys(data).length);
				}
				
				if(imagesl.length == 0){
					endData = true;
					if(checkScrollBar())
						jQuery("#btnNorecordFound").show();
					return false;
				}else if( imagesl.length < imagecount ){
					endData = true;
					//jQuery("#btnNorecordFound").show();
				}
				
				//ajaxdata = data;
				var count = Object.keys(whole_ajax_data).length + 1;
				var data_count = remove_count;
				remove_count = 0;
			
				ajaxdata = {};
				var i = 1
				//console.log(lastRowArray.length);
				if(lastRowArray.length > 0){
					//console.log(lastRowArray.length);
					for(i = lastRowArray.length; i > 0; i--){
						//console.log("history:"+i);
						//ajaxdata[i] = lastRowArray[i-1];
						//var obj = {};
						//obj[i] = {
						ajaxdata[i] = {
							"link":lastRowArray[i - 1]['href'],
							"itemName":lastRowArray[i - 1]['title'],
							"imgPath":lastRowArray[i - 1]['src'],
							"width":lastRowArray[i - 1]['org_width'],
							"height":lastRowArray[i - 1]['org_height'],
							"itemid":lastRowArray[i - 1]['itemid'],
							"lastarray":"1"
						};
					}
				}
				
				
				var ajaxdata_count = (lastRowArray.length == 0 ) ? 1 : lastRowArray.length + 1;
				
				jQuery.each(data, function(k, v){
					//console.log("images: "+images.length+", data_count: "+data_count);
					//if(data_count < imagesl.length)
						//whole_ajax_data[count] = data[data_count];
						
						//console.log(ajaxdata_count);
					ajaxdata[ajaxdata_count] = data[k];
						
					count ++;
					data_count ++;
					ajaxdata_count ++;
				});
				
				//console.log(JSON.stringify(ajaxdata));
				lastRowArray = []; //make empty incomplete row array
			
				jQuery("#pictures").justifiedGallery({
					'usedSuffix':'lt240', 
					'justifyLastRow':false, 
					'rowHeight':rowHeight, 
					'fixedHeight':false, 
					//'lightbox':false, 
					'captions':false, 
					'margins':imgMargin,
					'refreshTime':500,
					'toggle':settingtoggle,
					'container':'#pictures',
					'data':'none',
					'callFunction':'none'
				});
				
				
			
		});
}

function createCookie(name,value,days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
	}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function eraseCookie(name) {
	createCookie(name,"",-1);
}

//check window has scroll bar
function checkScrollBar() {
            var hContent = jQuery("body").height() + 60; // get the height of your content
            var hWindow = jQuery(window).height(); // get the height of the visitor's browser window
            
            if(hContent>hWindow) { // if the height of your content is bigger than the height of the browser window, we have scroll bar
                return true;    
            }

            return false;
    }


jQuery(window).unload(function () {
	jQuery("#pictures").find("script").remove();
    if (('localStorage' in window) && window['localStorage'] !== null) {
        var pictures = jQuery("#pictures").html();
        var main_div = jQuery(".main-div").html();
        localStorage.setItem('pictures', pictures);
        localStorage.setItem('main-div', main_div);
        
    }
    createCookie("histClickedPage", (pagCount), 1);
});
