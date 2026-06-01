/* 
Justified Gallery
Version: 1.0.3
Author: Miro Mannino
Author URI: http://miromannino.it

Copyright 2012 Miro Mannino (miro.mannino@gmail.com)

This file is part of Justified Gallery.

This work is licensed under the Creative Commons Attribution 3.0 Unported License. 

To view a copy of this license, visit http://creativecommons.org/licenses/by/3.0/ 
or send a letter to Creative Commons, 444 Castro Street, Suite 900, Mountain View, California, 94041, USA.
*/

__justifiedGallery_galleryID = 0;
var settingtoggle = false;
var initialCall = true;
var historyContent = "";
var processing = false;
var lastRowImages = "";
var endData = false;
var reduceContWidth = 0;
var mobileDevice = false;
var toggleintarvalStart = false;
var orientation = "";
var prevorientation = "";
var diff = 0;
var lastDiff = 0;
var histCont= "";
var histImages = [];
var histImagesArray = {};
var histLastRowWidth = "";
var histSettings = "";
var imageId = 0;
var urlHistoryImageId = "";
var broswerType = "";
var ajaxdata = {};
var whole_ajax_data = {};
var lastRowArray = [];
var remove_count = 0;
var image_counter = 0;
(function(jQuery){
 
   jQuery.fn.justifiedGallery = function(options){

		var settings = jQuery.extend( {
			//'sizeSuffixes' : {'lt100':'_t', 'lt240':'_m', 'lt320':'_n', 'lt500':'', 'lt640':'_z', 'lt1024':'_b'},
			'sizeSuffixes' : {'lt100':'', 'lt240':'', 'lt320':'', 'lt500':'', 'lt640':'', 'lt1024':''},
			'usedSuffix' : 'lt240',
			'justifyLastRow' : true,
			'rowHeight' : 120,
			'fixedHeight' : false,
			//'lightbox' : false,
			'captions' : true,
			'margins' : 10,
			'refreshTime' : 500,
			'toggle': false,
			'container':'#myExample1',
			'data':'data',
			'callFunction':'none'
		}, options);
		
		processing = true;
		
		
		/*
		if(settings.callFunction == "toggleLable"){
			//settings.callFunction;
			//toggleLable();
			
			lastDiff = diff;
					
			if(settingtoggle){
				settings.margins = 20;
				settings.captions = true;
			}else{
				settings.margins = 10;
				settings.captions = false;
			}
			settings.toggle = settingtoggle;
			
			jQuery(histCont).find(".jg-row").remove();
					
			image_counter = 0;		
			//return false;
		}*/
		if(settings.callFunction == "toggleLable"){
			//settings.callFunction;
			histsettings = settings;
			toggleLable();
			return false;
		}else if(settings.callFunction == "backhistory"){
			processing =false;
			histsettings = settings;
			//checkWidth(jQuery, histCont, histImages, rowWidth, settings);
			lastRowWidth = histLastRowWidth;
			checkWidth(jQuery, histCont, histImages, histLastRowWidth, histsettings);
			return false;
		}

		/*function getErrorHtml(message, classOfError){
			return "<div class=\"" + classOfError + "\"style=\"font-size: 12px; border: 1px solid red; background-color: #faa; margin: 10px 0px 10px 0px; padding: 5px 0px 5px 5px;\">" + message + "</div>";
		}*/
		
		
				 

		return this.each(function(index, cont){
			
			jQuery(cont).addClass("justifiedGallery");

			var loaded = 0;
			var images = new Array(Object.keys(ajaxdata).length);

			__justifiedGallery_galleryID++;

			if(images.length == 0) return;
			
			jQuery(cont).append("<div class=\"jg-loading\"><div class=\"jg-loading-img\"></div></div>");
			var justifiedLength = 0;
			var index = 0;
			jQuery.each(ajaxdata, function(k, v){
				
				var lastarray = v['lastarray'] ? v['lastarray'] : 0;
				
				images[index] = new Array(7);
				
				images[index]["src"] = v["imgPath"];
				images[index]["alt"] = v['itemName'];
				images[index]["href"] = v['link'];
				images[index]["title"] = v['itemName'];
				images[index]["justified"] = "";
				images[index]["class"] = "";
				images[index]["itemid"] = v['itemid'];
				images[index]["lastarray"] = lastarray;
				
				
				var img = new Image();
  				
				if(images[index]["height"] != settings.rowHeight)
					images[index]["width"] = Math.ceil(this.width / (this.height / settings.rowHeight));
				else
					images[index]["width"] = this.width;
				images[index]["height"] = settings.rowHeight;
				images[index]["src"] = images[index]["src"];
				
				images[index]["org_height"] = images[index]['height'];
				images[index]["org_width"] = images[index]['width'];
				
				images[index]["min_width"] = (images[index]["width"] < min_img_cont_widht) ? (min_img_cont_widht - images[index]["width"]) : 0;
									 
				if(++loaded == images.length ) startProcess(cont, images, settings);
				
				jQuery(img).attr('src', images[index]["src"]);
				index++;

			});
		});
		
		
		/*jQuery.each(ajaxdata, function(k, v){
					
					//myData.push(v);
					html = '<a href="'+v['link']+'" title="'+v['itemName']+'" class="gray" itemid="'+v['itemid']+'" ><img alt="'+v['itemName']+'" src="'+v['imgPath']+'" /></a>';
					jQuery('#pictures').append(html);
					//myDataLoop++;
					
			
				});*/
		
		
		function startProcess(cont, images, settings){
		//console.log("startProcess: "+cont + ", images:" + images + ", settings:" + settings);
			//FadeOut the loading image and FadeIn the images after their loading
			//jQuery(cont).find(".jg-loading").fadeOut(500, function(){
			jQuery(".jg-loading").fadeOut(500, function(){
				jQuery(this).remove(); //remove the loading image
				processesImages(jQuery, cont, images, 0, settings, "startProcess");
			});
		}

		function buildImage(image, suffix, nw, nh, l, minRowHeight, settings, minwidth){
			imageId++;
			image_counter ++;
			var ris; 
			if(settingtoggle){
				var clsminwidth = (minwidth > 0) ? "clrwhite" : "";
				ris =  "<div class=\"jg-image border1 "+clsminwidth+"\" style=\"left:" + l + "px; height: " + minRowHeight + "px;text-align:center; \">";
			}else
				ris =  "<div class=\"jg-image\" style=\"left:" + l + "px\">";
				
			//ris += " <a id=\"imageid"+imageId+"\"  name=\"imageid"+imageId+"\" href=\"" + image["href"] + "\" class=\"imgLink\" onclick=\"javascript:window.location.href='#imageid"+imageId+"'\"  justified=\"yes\"";
			ris += " <a org_height=\""+image["org_height"]+"\" org_width=\""+image["org_width"]+"\" id=\"imgId"+image["itemid"]+"\"  href=\"" + image["href"] + "\" class=\"imgLink "+image['class']+"\" justified=\"yes\"  pagCount=\""+pagCount+"\" itemid=\""+image["itemid"]+"\"";

			/*if(settings.lightbox == true)
				ris += "rel=\"" + image["rel"] + "\"";
			else*/
			ris += " style=\"width: " + (nw + minwidth) + "px; height: " + (minRowHeight - 48) + "px;display:block;\"  ";
			
				ris +=     "target=\"_parent\"";

			ris +=     "title=\"" + image["title"] + "\">";
			//ris += "  <img alt=\"" + image["alt"] + "\" src=\"" + image["src"] + suffix + settings.extension + "\"";
						
			ris += "  <img  alt=\"\" src=\"" + image["src"] + "\"";
			ris +=        "style=\"width: " + nw + "px; height: " + nh + "px;\">";
			
			
			if(settingtoggle)
				ris += "  <div class=\"jg-image-label\">" + image["alt"] + "</div>";
				//ris += "  <div style=\"bottom:" + (nh - minRowHeight) + "px;\" class=\"jg-image-label\">" + image["alt"] + "</div>";

			ris += " </a></div>";
			return ris;
		}

		function buildContRow(row, images, extraW, settings){
		
			var j, l = 0;
			var minRowHeight;
			for(var j = 0; j < row.length; j++){
			
				row[j]["nh"] = Math.ceil(images[row[j]["indx"]]["height"] * 
					            ((images[row[j]["indx"]]["width"] + extraW) / 
							 	images[row[j]["indx"]]["width"]));
							 	
				row[j]["nw"] = images[row[j]["indx"]]["width"] + extraW;

				row[j]["suffix"] = ""; //getSuffix(row[j]["nw"], row[j]["nh"], settings);

				row[j]["l"] = l;

				if(!settings.fixedHeight){
					if(j == 0) 
						minRowHeight = row[j]["nh"];
					else
						if(minRowHeight > row[j]["nh"]) minRowHeight = row[j]["nh"];
				}
				
				 row[j]["min"] = settingtoggle ? images[row[j]["indx"]]["min_width"] : 0; //add min widht to image container
				l += row[j]["nw"] + row[j]["min"] + settings.margins;
			}

			if(settings.fixedHeight) minRowHeight = settings.rowHeight;
			
			var rowCont = "";
			if(settingtoggle)
				jQuery("#pictures").append( "<div class=\"jg-row page"+ pagCount +"\" style=\"height: " +  (minRowHeight + 73 )+ "px;\"></div>");
			else
				jQuery("#pictures").append( "<div class=\"jg-row page"+ pagCount +"\" style=\"height: " + minRowHeight + "px; margin-bottom:" + settings.margins + "px;\"></div>");
				
				var rowCount = jQuery(".jg-row").length - 1;
				
			for(var j = 0; j < row.length; j++){
				
				jQuery(".jg-row").eq(rowCount).append(buildImage(images[row[j]["indx"]], row[j]["suffix"], 
					                  row[j]["nw"], row[j]["nh"], row[j]["l"], minRowHeight + 64, settings, row[j]["min"]));
					                  
				/*rowCont += buildImage(images[row[j]["indx"]], row[j]["suffix"], 
					                  row[j]["nw"], row[j]["nh"], row[j]["l"], minRowHeight, settings);*/
			}
			
			/*
			if(settingtoggle)
				return "<div class=\"jg-row\" style=\"height: " +  (minRowHeight + 20 )+ "px;\">" + rowCont + "</div>";
			else
				return "<div class=\"jg-row\" style=\"height: " + minRowHeight + "px; margin-bottom:" + settings.margins + "px;\">" + rowCont + "</div>";
			*/
		}
/*
		function getSuffix(nw, nh, settings){
			var n;
			if(nw > nh) n = nw; else n = nh;
			if(n <= 100){
				return settings.sizeSuffixes.lt100; //thumbnail (longest side:100)
			}else if(n <= 240){
				return settings.sizeSuffixes.lt240; //small (longest side:240)
			}else if(n <= 320){
				return settings.sizeSuffixes.lt320; //small (longest side:320)
			}else if(n <= 500){
				return settings.sizeSuffixes.lt500; //small (longest side:320)
			}else if(n <= 640){
				return settings.sizeSuffixes.lt640; //medium (longest side:640)
			}else{
				return settings.sizeSuffixes.lt1024; //large (longest side:1024)
			}
		}*/

		function processesImages(jQuery, cont, images, lastRowWidth, settings, calledFrom){
			
				//console.log("calledFrom:"+calledFrom);
			
			var row = new Array();
			var row_i, i;
			var partialRowWidth = 0;
			var extraW;
			var rowWidth = jQuery(cont).width() - reduceContWidth;

			for(i = 0, row_i = 0; i < images.length; i++){
				var min_width = settingtoggle ? images[i]["min_width"] : 0;
				
				if(calledFrom=="startProcess" && images[i]['justified'] == 'yes') continue;
					
			
				if(images[i] == null ) continue;
				
				//console.log(i+" rowWidth: "+rowWidth +", partialRowWidth: "+partialRowWidth + ", images_width: "+images[i]["width"] + ", margin:"+ settings.margins +", total:"+(partialRowWidth + images[i]["width"] + settings.margins));
				
				if(partialRowWidth + images[i]["width"] + min_width + settings.margins <= rowWidth){
					//we can add the image
					partialRowWidth += images[i]["width"]+ min_width + settings.margins;
					row[row_i] = new Array(5);
					row[row_i]["indx"] = i;
					row_i++;
				}else{
					//the row is full
					extraW = Math.ceil((rowWidth - partialRowWidth + 1) / row.length);
					//jQuery(cont).append(buildContRow(row, images, extraW, settings));
					buildContRow(row, images, extraW, settings); 
					row = new Array();
					row[0] = new Array(5);
					row[0]["indx"] = i;
					row_i = 1;
					partialRowWidth = images[i]["width"]+ min_width + settings.margins;
				}
				
			}
			
			
			
			//last row----------------------
			//now we have all the images index loaded in the row arra
			if(settings.justifyLastRow){
				extraW = Math.ceil((rowWidth - partialRowWidth + 1) / row.length);	
			}else{
				extraW = 0;
			}
			jQuery(cont).append(buildContRow(row, images, extraW, settings));
			//buildContRow(row, images, extraW, settings)
			//---------------------------

			//lightbox-------------------
			/*if(settings.lightbox){
				try{
					jQuery(cont).find(".jg-image a").colorbox({maxWidth:"80%",maxHeight:"80%",opacity:0.8,transition:"elastic", current:""});
				}catch(e){
					jQuery(cont).html(getErrorHtml("No Colorbox founded!", "jg-noColorbox"));
				}
			}*/

			//Captions---------------------
			/*if(settings.captions){
				jQuery(cont).find(".jg-image").mouseenter(function(sender){
					jQuery(sender.currentTarget).find(".jg-image-label").stop();
					jQuery(sender.currentTarget).find(".jg-image-label").fadeTo(500, 0.7);
				});
				jQuery(cont).find(".jg-image").mouseleave(function(sender){
					jQuery(sender.currentTarget).find(".jg-image-label").stop();
					jQuery(sender.currentTarget).find(".jg-image-label").fadeTo(500, 0);
				});
			}*/
			
			jQuery(cont).find(".jg-resizedImageNotFound").remove();
			//jQuery(cont).find(".jg-image img").show();
			//jQuery(cont).find(".jg-image img").delay(50000).css('opacity', 1);
			//if(initialCall == false){
					  
			if( endData == false){ 
					  var lastRow = jQuery(".jg-row").length;
					  //jQuery(".jg-row").eq(lastRow - 1).css("background-color","red");
					  lastRowArray = [];
					  remove_count = jQuery(".jg-row").eq(lastRow - 1).find('a').length;
					  
					  jQuery(".jg-row").eq(lastRow - 1).find('a').each(function(){
					  		var href = jQuery(this).attr("href");
					  		var title = jQuery(this).attr("title");
					  		var src = jQuery(this).find("img").attr("src");
					  		var itemid = jQuery(this).attr("itemid");
					  		var org_height = jQuery(this).attr("org_height");
					  		var org_width = jQuery(this).attr("org_width");
					  		lastRowArray.push( {href:href, title:title, src:src, itemid:itemid, org_height:org_height, org_width:org_width} );
					  		//lastRowImages += '<a href="'+href+'" title="'+title+'" class=" gray lastRowImages'+(pagCount+1)+'" itemid="'+itemid+'" ><img alt="'+title+'" src="'+src+'" /></a>';
					  });
					  
					  //console.log(remove_count);
					  jQuery(".jg-row").eq(lastRow - 1).remove();
			}

					  jQuery("#bload").hide();
					  jQuery(".loading").hide();
						
					  jQuery(cont).find(".jg-image img").css('opacity', 1);
					  processing = false;
					  nextPageResponce = false;
					  ajaxdata = {};
					  
					  
					  if(endData == true && checkScrollBar() == true ){
							jQuery("#btnNorecordFound").show();
						
						}
					 // console.log(jQuery(".page"+pagCount).html());
					 /* var html = "";
					  jQuery(".page"+pagCount).each(function(){
						  html +='<div class="jg-row page'+pagCount+'">' + jQuery(this).html() + '</div>';
						});
					  
					  //console.log(html);
					  
					  var params = { htmldata: html };
					  jQuery.ajax({
						type: "POST",
						data:  params,

						success: function(data){

						}
						});*/
					  

					  if(urlHistoryImageId != "" && broswerType == 'chrome'){
						/*pageElement = document.getElementById(urlHistoryImageId);
						pageElement = pageElement.replace("-", "");
						scrollToElement(pageElement);
						urlHistoryImageId = "";*/
					  }
					  
					  if(settings.callFunction == "toggleLable" || calledFrom == "checkWidth")
						jQuery(document).scrollTop(lastScrollPostion);
			/*}else{
				jQuery(cont).find(".jg-image img")
					  .delay(800)
					  .queue( function(next){ 
						jQuery(this).css('opacity', 1);
						next(); 
					  });
			}*/
  
			//fade in the images that we have changed and need to be reloaded
			/*jQuery(cont).find(".jg-image img").load(function(){
					jQuery(this).fadeTo(500, 1);
			}).error(function(){
				jQuery(cont).prepend(getErrorHtml("The image can't be loaded: \"" +  jQuery(this).attr("src") +"\"", "jg-resizedImageNotFound"));
			}).each(function(){
					if(this.complete) jQuery(this).load();
			});*/
			//console.log(JSON.stringify(images));
				
			histCont = cont;
			//histImages = images;
			if( calledFrom != "checkWidth"){
				//histImages.unshift(images);
				for(i = 0, row_i = 0; i < images.length; i++){
					if( !(images[i]['lastarray']))
						histImages.push(images[i]);
				}
			}
			
			
			
			histLastRowWidth = rowWidth;
			histsettings = settings;
			checkWidth(jQuery, cont, histImages, rowWidth, settings);
			
		}
		
		
		function scrollToElement(pageElement) {
					var positionX = 0,         
						positionY = 0;    

					while(pageElement != null){
						positionX += pageElement.offsetLeft;        
						positionY += pageElement.offsetTop;        
						pageElement = pageElement.offsetParent;        
						window.scrollTo(positionX, positionY);    
					}
				}

		function checkWidth(jQuery, cont, images, lastRowWidth, settings){
						 
			var id = setInterval(function(){
			
				if(processing)
					return false;
					
					
				if(mobileDevice){
					if(prevorientation != orientation){
						prevorientation = orientation;
					}else{
						return false;
					}
				}
				
				diff = lastRowWidth - (jQuery(cont).width() - reduceContWidth);
				//console.log("lastDiff: "+ lastDiff +", diff: "+ diff + ", lastRowWidth: "+lastRowWidth +", histLastRowWidth: "+ histLastRowWidth);
				if(lastDiff == diff){
					return false;
				}
				
				
				if( lastRowWidth != (jQuery(cont).width() - reduceContWidth) || settingtoggle != settings.toggle || mobileDevice == true ){
				//console.log("settingtoggle: "+ (lastRowWidth != (jQuery(cont).width() - reduceContWidth)) + ", settings.toggle:" + (settingtoggle != settings.toggle));
					lastDiff = diff;
					//console.log("resize called");
					//console.log("settingtoggle: "+ (lastRowWidth != (jQuery(cont).width() - reduceContWidth)) + ", settings.toggle:" + (settingtoggle != settings.toggle));
					
					if(settingtoggle){
						//settings.margins = 20;
						settings.captions = true;
					}else{
						//settings.margins = 10;
						settings.captions = false;
					}
					settings.toggle = settingtoggle;
					
					jQuery(cont).find(".jg-row").remove();
					clearInterval(id);
					
					if(processing == true)	return false;
					
					processing = true;
					image_counter = 0;
					lastRowArray = []; //make empty incomplete row array
					processesImages(jQuery, cont, images, lastRowWidth, settings, "checkWidth");
					return;
				}
			}, settings.refreshTime);
		}
		
		
		function toggleLable(){  

					//console.log("settingtoggle: "+ (lastRowWidth != (jQuery(histCont).width() - reduceContWidth)) + ", settings.toggle:" + (settingtoggle != settings.toggle));
					lastDiff = diff;
					
					if(settingtoggle){
						//settings.margins = 20;
						settings.captions = true;
					}else{
						//settings.margins = 10;
						settings.captions = false;
					}
					settings.toggle = settingtoggle;
					
					
					
					if(histImages.length < 2000){
						jQuery(histCont).find(".jg-row").remove();
						processesImages(jQuery, histCont, histImages, histLastRowWidth, histsettings, "checkWidth");
						return false;
					}
					
					var loopInit = 1;
					var ajaxdatatgl = [];
					var i = 1;
					var intervalTime = 500;
					var itervalCount = 0;
					
					if(toggleintarvalStart)
						return false;
					
					var intervalTgl = setInterval(function(){
							itervalCount ++;
							
							if(loopInit >= histImages.length){
								clearInterval(intervalTgl);
								toggleintarvalStart = false;
								//console.log("complete");
								//console.log(lastScrollPostion);
								jQuery(document).scrollTop(lastScrollPostion);
								return false;
							}
							
							toggleintarvalStart = true;
							
							//console.log(lastRowArray.length);
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
									
									ajaxdatatgl.push({alt:alt, height:height, href:href, itemid:itemid, src:src, title:title, width:width, org_height:orgheight, org_width:orgwidth});
								}
								lastRowArray = [];
							}
							
							
							//console.log("hiiii loopInit:"+loopInit+", histImages.length: "+histImages.length);
							//console.log(ajaxdatatgl.length);
							var lpcounter = 0;
							for(i=loopInit; i<=histImages.length; i++ ){
								//console.log("in loop :"+i);
								
									if(lpcounter > 500){
										break;
									}
										
										
									//console.log("next in loop");
								
									var alt = histImages[i - 1]['title'];
									var height = parseInt( histImages[i - 1]['org_height'] );
									var href = histImages[i - 1]['href'];
									var itemid = histImages[i - 1]['itemid'];
									var orgheight = parseInt( histImages[i - 1]['org_height'] );
									var orgwidth = parseInt( histImages[i - 1]['org_width'] );
									var src = histImages[i - 1]['src'];
									var title = histImages[i - 1]['title'];
									var width  = parseInt( histImages[i - 1]['org_width'] );
									ajaxdatatgl.push({alt:alt, height:height, href:href, itemid:itemid, src:src, title:title, width:width, org_height:orgheight, org_width:orgwidth});
									
									
									lpcounter++;
									
							}
							//console.log("loopInit: "+loopInit+", lpcounter: "+lpcounter);
							loopInit = loopInit + lpcounter;
							
							//console.log(histImages);
							if(itervalCount == 1){
								jQuery(histCont).find(".jg-row").remove();
							}
							processesImages(jQuery, histCont, ajaxdatatgl, histLastRowWidth, histsettings, "checkWidth");
							ajaxdatatgl = [];
							
					}, intervalTime);
				
		}
		
		
		
		

   }
 
})(jQuery);
