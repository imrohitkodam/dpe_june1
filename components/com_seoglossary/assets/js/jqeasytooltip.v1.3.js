;(function( window, $, undefined ) {

/**
* jQuery Easytip - jqeasytooltip - Easy tooltips for everyone
* http://jqeasytooltip.j3dlab.com/
*
* @version: 1.3.1 - May 27, 2014
* Buy a Comercial license
* http://www.codecanyon.net
*
* @author: j3dlab
* info@j3dlab.com
* http://www.j3dlab.com/
*
*/

	var $element;
    var document = window.document;
    var windowDimensions = {};
    var windowoldDimensions = {};


    var helpers,
    $tip =  $('<div/>'),
    $loading = $('<div />').addClass('jqeasytooltip-loading'),
    defaultevent= 'mouseover',
    defaulteventout= 'mouseout',
    defaultfollowcursor = true,
    minmargin = 20,
    open = function(){}, //callback functions
    close = function(){},
    init = function(){},
    enabled = function(){},
    disabled = function(){};


    //plugin methos (init, open, close, destroy, enable, disable, get)
	methods = {

	//Init Plugin
	init : function(settings){


		return this.each(function() {

			var config = {
				position: "tiptop",
				content: "",
				maxwidth: "",
				theme: "tiptheme",
				tagcontent: "",
				tagcontenthide: false,
				icon:"",
				event: defaultevent,
				eventout: defaulteventout,
				followcursor: defaultfollowcursor,
				delayopen: 0,
				delayclose: 0,
				animation:"tipopen",
				animationout:"tipclose",
				minmargin:minmargin,
				mouseleave: true,
				init: function() {},
				mousemove: function(){}
			};


			if (settings) {
				$.extend(config, settings);
			}

			var $this = $(this);

			//if already inicalizated,clear and init
			if(helpers.is_init($this)){
				$(this).jqeasytooltip('clear');
			}

			windowDimensions = {
           		width: $(window).width(),
            	height:  Math.max($(document).height(),$(window).height(),/* For opera: */document.documentElement.clientHeight)
    		};

    		windowoldDimensions = windowDimensions;


			//get values in 'data' attributtes and set data-attributes  if data is empty
			config = helpers.get_data_tips($this,config);
			helpers.set_data_tips($this,config);

			//remove div content
			if(config.tagcontenthide){
				var $remove = $('#'+config.tagcontent);

				config.content=	$('#'+config.tagcontent).html();
				config.tagcontent = "";
				config.tagcontenthide = false;

				helpers.set_data_tips($this,config);

				$remove.remove();
			}


			//clone new tip
			var newtip = $tip.clone();
			$this.data("otip",newtip);

			$this.enabled = true;


			if(config.event==config.eventout){
			//if event triggering open and close are the same
				$this.off(config.event).on(config.event, function(e)
				{
					e.preventDefault();
					e.stopPropagation ? e.stopPropagation() : (e.cancelBubble=true);

				    var state = $(this).data('state');

				    switch(state){
				        case "closed" : $this.jqeasytooltip('open',config); break;
				        case undefined : $this.jqeasytooltip('open',config); break;
				        case "open" : $this.jqeasytooltip('close',config); break;
				    }

				});


			}else{
				//event for open
				$this.off(config.event).on(config.event, function(e){
					e.preventDefault();
					e.stopPropagation ? e.stopPropagation() : (e.cancelBubble=true);

					$this.jqeasytooltip('open',config);
				});

				//event for close
				$this.off(config.eventout).on(config.eventout, function(e){
					e.preventDefault();
					e.stopPropagation ? e.stopPropagation() : (e.cancelBubble=true);

					$this.jqeasytooltip('close',config);

				});
			}

			//add mouseover event to all tooltips
			helpers.get_tip($this).off('mouseover').on('mouseover',function(e){
				$(this).data('inside','tip');
			});

			$this.off('mouseenter').on('mouseenter', function(e){
					$(this).data('inside','true');
			});
			$this.off('mouseleave').on('mouseleave', function(e){
					$(this).data('inside','false');

					if($(this).data("tipmouseleave"))
						$this.jqeasytooltip('close',config);
			});



			//follow cursor activate
			if(config.followcursor){

				//event for mousemove if config is true
				$this.off('mousemove').on('mousemove', function(e){

					e.preventDefault();
					e.stopPropagation ? e.stopPropagation() : (e.cancelBubble=true);

					var state = $(this).data('state');
					var $tipelement = helpers.get_tip($(this));

					if (!jQuery.contains(document, $tipelement[0])) {
    					//Element is detached
    					$this.jqeasytooltip('open',config);
					}

					helpers.set_position($this,e);
					$this.trigger('jqeasytooltipmousemove', $this);
					config.mousemove.apply($this);

				});

			}

			$this.trigger('jqeasytooltipinit', $this);
			config.init.apply($this);


		});


	},

	//Destroy tooltips
	destroy : function(){

		//remove tip
		return this.each(function() {
			var $tipelement = helpers.get_tip($(this));
			$tipelement.remove();

			$(this).removeData();

			 if($(this).hasClass('jqeasytooltip'))
			 	$(this).removeClass('jqeasytooltip');

			$(this).trigger('jqeasytooltipdestroyed', $element);
			$(this).unbind();
		});

		return;

	},

	//Clear tooltips
	clear : function(){

		//remove tip
		return this.each(function() {
			var $tipelement = helpers.get_tip($(this));
			$tipelement.remove();
			$(this).unbind();
		});

		return;
	},

	//open tooltip
	open : function(settings){


		return this.each(function() {

			var config = {
				position: "tiptop",
				content: "",
				maxwidth: "",
				theme: "tiptheme",
				tagcontent: "",
				tagcontenthide: false,
				icon:"",
				event: defaultevent,
				followcursor: defaultfollowcursor,
				animation:"tipopen",
				delayopen:1,
				open: function() {}
			};


			if (settings) {
				$.extend(config, settings);
			}


			$element = $(this);

			config = helpers.get_data_tips($element,config);

			//get de tooltip element
			var $tipelement = helpers.get_tip($element);


			if($tipelement==undefined){
				$element.jqeasytooltip('init',config);
			}


			//set values to tip
			if(config.maxwidth)
				$tipelement.css('width',config.maxwidth+'px');


			$tipelement.addClass(config.theme);


			if(config.tagcontent!=""){
				config.content =  $('#'+config.tagcontent).html();
			}




			var positionated = false;

			//if content is a return function
			if (typeof config.content === 'function'){

			 		$tipelement.html($loading.clone().fadeIn());
			 		$tipelement.css("padding","20px");
			 		$('body').append($tipelement);

			 		$tipelement.removeClass(config.animationout);
					$tipelement.addClass(config.animation);

			 		helpers.set_position($element,undefined,true);

			 		setTimeout(function(){
						config.content = config.content.call(this, function( response ) {

							$tipelement.css("top","0").html($("<div class='jqcont'/>").hide().html(response));

							setTimeout(function(){
								$tipelement.find(".jqcont").show();
								helpers.set_position($element,undefined,true);

								//trigger event for customize and call callback open
								$element.trigger('jqeasytooltipopen', $element);
								config.open.apply($element);

								$element.data('state', 'open');
							},100);


						});
					},100);

			}else{

			 	$tipelement.html(config.content);
			 	//create tip
				$('body').append($tipelement);

				//set position of the element
		       	helpers.set_position($element,undefined,true);

		       	$tipelement.removeClass(config.animationout);
				$tipelement.addClass(config.animation);

		       	//trigger event for customize and call callback open
				$(this).trigger('jqeasytooltipopen', $element);
				config.open.apply($element);

				$element.data('state', 'open');
			}

			if(config.icon!=""){
				$tipelement.addClass('tipicon').prepend($("<i/>").addClass(config.icon));
				if($tipelement.find("i").not(".fa"))
					$tipelement.find("i").addClass("fa");

			}


       });

	},

	//close tooltip
	close : function(settings){

		return this.each(function() {

			var config = {
				remove: true,
				delayclose: 1,
				animationout: "tipclose",
				close: function() {}
			};

			if (settings) {
				$.extend(config, settings);
			};



			$element = $(this);

			//get de tooltip element
			var $tipelement = helpers.get_tip($element);


			//check when transitions ends when close is fired
			var transitionEnd = helpers.whichTransitionEvent();
			var animationEnd = helpers.whichAnimationEvent();


			$tipelement.off("customtransitionend").on("customtransitionend", function(e){
				if(($tipelement.hasClass(config.animationout) || $tipelement.data('state') =='closed') && (! $tipelement.hasClass('tipopen'))){
					if(config.remove){
						$tipelement.remove();
					}else{
						$tipelement.css("left","-9999px");
					}
				}
			});

			if(animationEnd){
				$tipelement.off(animationEnd).on(animationEnd, function(e){
					if(($tipelement.hasClass(config.animationout) || $tipelement.data('state') =='closed') && (! $tipelement.hasClass('tipopen'))){
						if(config.remove){
							$tipelement.remove();
						}else{
							$tipelement.css("left","-9999px");
						}
					}
				});
			}

			//close with delay time
			setTimeout(function(){
					$tipelement.removeClass(config.animation);
					$tipelement.addClass(config.animationout);
					$element.data('state', 'closed');
					$tipelement.data('state', 'closed');

					var time = 1300;
					if(animationEnd==undefined && transitionEnd==undefined){
						time=200;
					}

					helpers.emulateTransitionEnd($tipelement,time,config.remove,config.animationout);


					$element.trigger('jqeasytooltipclosed', $element);
					config.close.apply($element);

			},config.delayclose);

		});

	},

	//enable tooltip
	enabled : function(){
		$(this).jqeasytooltip();
	},

	//disable tooltip
	disabled : function(){
		this.each(function() {
			$(this).enabled = false;
			$(this).unbind();
		});

	},

	//Get parameters or objects
	get : function(field){

		//get tooltips elements
		if (field === null || field == 'tips') {
			var $tips = [];
			this.each(function(){
				$tips.push(helpers.get_tip($(this)));
			});
			return $tips;
		}

		//get tooltip enable
		if (field == 'tip') {
			return helpers.get_tip($(this));
		}

		//get elements with tooltip enable
		if (field == 'enable') {
			var $enables = [];
			this.each(function(){
				$enables.push($(this).enabled);
			});
			return $enables;
		}

		//get constant minmargin
		if (field == 'minmargin') {
			return minmargin;
		}

	}




	};//end method


	//AutoInicializating declarative
	$(document).ready(function() {
		if($(".jqeasytooltip")){
			$(".jqeasytooltip").jqeasytooltip('init',{});
		}
	});

	//when ajax AutoInicializating
	if (document.addEventListener){
  		document.addEventListener('DOMNodeInserted', initWhenajax, false);
	} else if (document.attachEvent){
	  	document.attachEvent('DOMNodeInserted', initWhenajax);
	}

	function initWhenajax(){
		$(".jqeasytooltip").each(function(){
			if(!$(this).data("otip")){
				$(this).jqeasytooltip('init',{});
			}
		});
	}


	$(document).ajaxComplete(function() {
		$(".jqeasytooltip").each(function(){
			if(!$(this).data("otip")){
				$(this).jqeasytooltip('init',{});
			}
		});
	});


	//onresize, update window dimensions for collision and reposition tooltip
	$(window).on('resize',function(e){
		 windowDimensions = {
       		width: $(window).width(),
        	height:  Math.max($(document).height(),$(window).height(),/* For opera: */document.documentElement.clientHeight)
        };


       if(!$element)
       	return;

       if($element.data('state')!='undefined')
	       if($element.data('state')=="closed")
	       		return;

       if(!$element.data("tipfollowcursor") && $element.data("tipmouseleave")){
	        $element.jqeasytooltip('open');
	        return;
       }

       var pospercent = $element.data("oldcursor");
       var x = $element.offset().left + ($element.width() * pospercent.xpercent)/100;
       var y = $element.offset().top + ($element.height() * pospercent.ypercent)/100;

       helpers.set_position($element,{pageX:x,pageY:y},false);

	});


	//Plugin
	$.fn.jqeasytooltip = function(method){

		 //private methods
		helpers = {

			//is already inicializated
			is_init: function($element){

				if($element.data("otip"))
					return true;

				return false;

			},

			//get tooltip element
			get_tip: function($element) {

				var $tipelement = $element.data("otip");

				if (! $tipelement){
					$tipelement = $tip.clone();
				}

				return $tipelement;

			},

			//get data attributes
            get_data_tips: function($element,settings) {


                if (($element.data('tipposition')!=undefined)&&($element.data('tipposition')!="")){
					settings.position = $element.data('tipposition');
				}
				if (($element.data('tipcontent')!=undefined)&&($element.data('tipcontent')!="")){
					settings.content= $element.data('tipcontent');
				}
				if (($element.data('tipmaxwidth')!=undefined)&&($element.data('tipmaxwidth')!="")){
					settings.maxwidth = $element.data('tipmaxwidth');
				}
				if (($element.data('tiptheme')!=undefined)&&($element.data('tiptheme')!="")){
					settings.theme = $element.data('tiptheme');
				}
				if (($element.data('tiptagcontent')!=undefined)&&($element.data('tiptagcontent')!="")){
					settings.tagcontent = $element.data('tiptagcontent');
				}
				if (($element.data('tiptagcontenthide')!=undefined)){
					settings.tagcontenthide = $element.data('tiptagcontenthide');
				}
				if (($element.data('tipicon')!=undefined)&&($element.data('tipicon')!="")){
					settings.icon = $element.data('tipicon');
				}
				if (($element.data('tipevent')!=undefined)&&($element.data('tipevent')!="")){
					settings.event = $element.data('tipevent');
				}
				if (($element.data('tipeventout')!=undefined)&&($element.data('tipeventout')!="")){
					settings.eventout = $element.data('tipeventout');
				}
				if (($element.data('tipfollowcursor')!=undefined)){
					settings.followcursor = $element.data('tipfollowcursor');
				}
				if (($element.data('tipdelayopen')!=undefined)&&($element.data('tipdelayopen')!="")){
					settings.delayopen = $element.data('tipdelayopen');
				}
				if (($element.data('tipdelayclose')!=undefined)&&($element.data('tipdelayclose')!="")){
					settings.delayclose = $element.data('tipdelayclose');
				}
				if (($element.data('tipanimation')!=undefined)&&($element.data('tipanimation')!="")){
					settings.animation = $element.data('tipanimation');
				}
				if (($element.data('tipanimationout')!=undefined)&&($element.data('tipanimationout')!="")){
					settings.animationout = $element.data('tipanimationout');
				}
				if (($element.data('tipminmargin')!=undefined)&&($element.data('tipminmargin')!="")){
					settings.minmargin = $element.data('tipminmargin');
				}
				if (($element.data('tipmouseleave')!=undefined)){
					settings.mouseleave = $element.data('tipmouseleave');
				}



				return settings;
            },

            //set data atributtes
            set_data_tips: function($element,settings) {

            	$element.data('tipposition',settings.position);
            	$element.data('tipcontent',settings.content);
            	$element.data('tipmaxwidth',settings.maxwidth);
            	$element.data('tiptheme',settings.theme);
            	$element.data('tiptagcontent',settings.tagcontent);
            	$element.data('tiptagcontenthide',settings.tagcontenthide);
            	$element.data('tipicon',settings.icon);
            	$element.data('tipevent',settings.event);
            	$element.data('tipeventout',settings.eventout);
            	$element.data('tipfollowcursor',settings.followcursor);
            	$element.data('tipdelayopen',settings.delayopen);
            	$element.data('tipdelayclose',settings.delayclose);
            	$element.data('tipanimation',settings.animation);
            	$element.data('tipanimationout',settings.animationout);
            	$element.data('tipminmargin',settings.minmargin);
            	$element.data('tipmouseleave',settings.mouseleave);
            },

            //create bounding box collisions for check positions
            boundingbox : function(center,tooltip,minmargin){

            	var marginWidthcorrector = parseInt(tooltip.tip.css('margin-top').replace('px','')) + parseInt(tooltip.tip.css('margin-bottom').replace('px',''));
            	var marginHeightcorrector = parseInt(tooltip.tip.css('margin-right').replace('px','')) + parseInt(tooltip.tip.css('margin-left').replace('px',''));

            	//class Point
            	var Point = function() {
  					this.x = 0;
  					this.y = 0;
  				};

  				//class Boundingbox
  				var BoundingBox = function() {
  					var $this = this;
  					this.center = null;
  					this.radioW = 0;
  					this.radioH = 0;

  					this.top = 0;
  					this.bottom = 0;

  					this.right = 0;
  					this.left = 0;

  					this.points = function() {
  						var p1 = new Point();
	            		p1.x= center.x - this.radioW;
	            		p1.y= center.y - this.radioH;

	            		$this.left = p1.x;
	            		$this.top = p1.y;

	            		var p2 = new Point();
	            		p2.x= center.x + this.radioW;
	            		p2.y= center.y - this.radioH;

	            		var p3 = new Point();
	            		p3.x= center.x - this.radioW;
	            		p3.y= center.y + this.radioH;

	            		var p4 = new Point();
	            		p4.x= center.x + this.radioW;
	            		p4.y= center.y + this.radioH;

	            		$this.right = p4.x;
	            		$this.bottom = p4.y;

	            		return {p1:p1,p2:p2,p3:p3,p4:p4};
  					};

  					this.pointsCollision = function(box,positioncheck) {

						 return ( (this.right > box.right && positioncheck=='tipright') ||
						  		  (this.left < box.left && positioncheck=='tipleft') ||
						  		  (this.bottom < box.bottom && positioncheck=='tipbottom') ||
						  		  (this.top > box.top && positioncheck=='tiptop') );

  					};

  					//drawing for logging
  					this.draw = function(){
  						$('body').append('<div style="position:absolute;top:'+this.top+'px;left:'+this.left+'px;border:1px solid #666;height:' + (this.bottom - this.top) + 'px;width:'+ (this.right-this.left) + 'px "></div>');
  					}

  				};

  				var collision = false;


				if((tooltip.position=="tiptop")||(tooltip.position=="tipbottom")){
					//check collision with minboundingbox
					var minboundingbox = new BoundingBox();
	  				minboundingbox.center = center;
	  				minboundingbox.radioW = (minmargin + tooltip.width/2 + marginWidthcorrector);
	  				minboundingbox.radioH = tooltip.height/2 + marginHeightcorrector;
	  				minboundingbox.points();
	  				//minboundingbox.draw();
	  				collision = minboundingbox.pointsCollision({top:windowDimensions.height,right:windowDimensions.width-20,bottom:0,left:0},tooltip.position);

				}else{
					//check collision with maxboundingbox
					var maxboundingbox = new BoundingBox();
	  				maxboundingbox.center = center;
	  				maxboundingbox.radioW = (minmargin + tooltip.width + marginWidthcorrector);
	  				maxboundingbox.radioH = (minmargin + tooltip.height) + marginHeightcorrector;
	  				maxboundingbox.points();
	  				//maxboundingbox.draw();
	  				collision = maxboundingbox.pointsCollision({top:windowDimensions.height,right:windowDimensions.width-20,bottom:0,left:0},tooltip.position);

				}

				return collision;

            },

            //Starter point for init tooltip
            get_position_point: function($element,position){

            	//class Point
            	var Point = function() {
  					this.x = 0;
  					this.y = 0;
  				};

  				var pfinal = new Point();

  				//calculate global height and width
  				var height = $element.height()+parseInt($element.css('padding-top').replace('px',''))+parseInt($element.css('padding-bottom').replace('px',''))+parseInt($element.css('border-top-width').replace('px',''))+parseInt($element.css('border-bottom-width').replace('px',''));
				var width = $element.width()+parseInt($element.css('padding-left').replace('px',''))+parseInt($element.css('padding-right').replace('px',''))+parseInt($element.css('border-left-width').replace('px',''))+parseInt($element.css('border-right-width').replace('px',''));


  				switch(position)
				{
					case "tipbottom":
					  pfinal.x= $element.offset().left + (width/2);
	           		  pfinal.y= $element.offset().top + height;
					  break;
					case "tipleft":
					  pfinal.x= $element.offset().left;
	           		  pfinal.y= $element.offset().top + (height/2);
					  break;
					case "tipright":
					  pfinal.x= $element.offset().left + width;
	           		  pfinal.y= $element.offset().top + (height/2);
					  break;
					default:
					  pfinal.x= $element.offset().left + (width/2);
	           		  pfinal.y= $element.offset().top;
				}

				return pfinal;

            },

            //set old% cursor position
            set_oldcursorposition : function($element,e){

            	var x = e.x - $element.offset().left;
				var y = e.y - $element.offset().top;

            	var xp = (100 * x) / $element.width();
            	var yp = (100 * y) / $element.height();

            	$element.data("oldcursor", {xpercent:xp, ypercent:yp});
            },



            //set new position for tooltip
            set_position : function($element,e,usemouse){

            	var minmargin = $element.data("tipminmargin");
            	var $tipelement = helpers.get_tip($element);
            	var pos;

            	if (e)
            		pos= {x:e.pageX,y:e.pageY};


				var height,width,positions;

				//when use tip generic, converts in titop
				if($tipelement.hasClass('tip')){
					$tipelement.addClass('tiptop');
				}

				//priority order for positions
				switch($element.data("tipposition"))
				{
				case "tipbottom":
				  positions = ["tipbottom","tiptop","tipleft","tipright"];
				  break;
				case "tipleft":
				  positions = ["tipleft","tipright","tiptop","tipbottom"];
				  break;
				case "tipright":
				  positions = ["tipright","tipleft","tiptop","tipbottom"];
				  break;
				default:
				  positions = ["tiptop","tipbottom","tipleft","tipright"];
				}


				var p = 0;

				var widthend,heightend;
				var widthCorrect,heightCorrect;


				//check positions collision
				for (i=0;i<positions.length;i++){

					$tipelement.removeClass("tiptop");
					$tipelement.removeClass("tipbottom");
					$tipelement.removeClass("tipleft");
					$tipelement.removeClass("tipright");
					$tipelement.addClass(positions[i]);

					height = parseInt($tipelement.css('height').replace('px',''))+parseInt($tipelement.css('padding-top').replace('px',''))+parseInt($tipelement.css('padding-bottom').replace('px',''));
					width = parseInt($tipelement.css('width').replace('px',''))+parseInt($tipelement.css('padding-left').replace('px',''))+parseInt($tipelement.css('padding-right').replace('px',''));


					//set x , y , from mouse or element tooltip
            		if(usemouse){
            			var newpos = helpers.get_position_point($element,positions[i]);
            			pos={x:newpos.x ,y:newpos.y};
            		}else{
            			//set more margin if mouse follow
            			if(minmargin<=20)
            				minmargin = minmargin + 10;
            		}


					var colision = helpers.boundingbox(pos,{tip:$tipelement,width:width,height:height,position: positions[i]},minmargin);


					if (!colision){
						break;
					}else{
						$tipelement.removeClass(positions[i]);
					}
				};

				//get height and width refresh
				height = parseInt($tipelement.css('height').replace('px',''))+parseInt($tipelement.css('padding-top').replace('px',''))+parseInt($tipelement.css('padding-bottom').replace('px',''));
				width = parseInt($tipelement.css('width').replace('px',''))+parseInt($tipelement.css('padding-left').replace('px',''))+parseInt($tipelement.css('padding-right').replace('px',''));



				//default class tiptop when no position is posible
				if(i==positions.length){
					$tipelement.addClass('tiptop');
				};


				if($tipelement.hasClass('tiptop')){
					widthend= -width/2;
					heightend = -height -minmargin;
				};
				if($tipelement.hasClass('tipright')){
					heightend = -height/2;
					widthend= minmargin;
				};
				if($tipelement.hasClass('tipbottom')){
					widthend= -width/2;
					heightend = minmargin;
				};
				if($tipelement.hasClass('tipleft')){
					heightend = -height/2;
					widthend= -width-minmargin;
				};


				var top = pos.y + heightend;
				var left = pos.x + widthend;
					if (left<0) {
					left=0;
					}
				helpers.set_oldcursorposition($element,pos);

				//set position
				$tipelement.css('top',top+'px');
				$tipelement.css('left',left+'px');

				return {top:top, left:left};

            },

            //Returns the event name Transitionend
            whichTransitionEvent : function(){
        		var t;
			    var el = document.createElement('fakeelement');
			    var transitions = {
			      'transition':'transitionend',
			      'OTransition':'oTransitionEnd',
			      'MozTransition':'transitionend',
			      'WebkitTransition':'webkitTransitionEnd'
			    };

			    for(t in transitions){
			        if( el.style[t] !== undefined ){
			            return transitions[t];
			        };
			    };
            },

            //Returns the event name Animationnend
            whichAnimationEvent : function(){

        	 	var t;
			    var el = document.createElement('fakeelement');
			    var animations = {
			      'transition':'animationend',
			      'OTransition':'oAnimationEnd',
			      'MozTransition':'animationend',
			      'WebkitTransition':'webkitAnimationEnd'
			    };

			    for(t in animations){
			        if( el.style[t] !== undefined ){
			            return animations[t];
			        };
			    };

            },

            //emulate transion end
            emulateTransitionEnd : function($element,duration,remove,animationout){
            	var called = false, $el = this;

			  var callback = function() {
			  	if (!called) $element.trigger('customtransitionend');
			  };

			   setTimeout(callback, duration);
            }



        };

        if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments,1));
		} else if (typeof method === 'object' || !method){
			return methods.init.apply(this,arguments);
		} else {
			$.error('Method ' + method + ' does not exist');
		};

	};


	//Global parameters
	$.fn.jqeasytooltip.defaults = {
		init : {
			// position:     string
			// content:      string/function
			// maxwidth:     int
			// theme:        string
			// tagcontent:   string
			// icon:         string
			// event:        string/event
			// eventout:     string/event
			// followcursor: bool
		},
		open : {
			// tip:          string
			// content:      string/function
			// maxwidth:     int
			// theme:        string
			// tagcontent:   string
			// icon:         string

		},
		close : {
			// remove:   	 bool
			// delay:        int
			// close:    	 function
		}

	};


})(window, jQuery);