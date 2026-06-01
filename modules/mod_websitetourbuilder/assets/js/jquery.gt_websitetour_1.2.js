/***
* Author: Erwin Yusrizal 
* Fork: JoomlaForceTeam
* UX by: Tai Nguyen
* Created On: 27/02/2013
* Version: 1.1.0
* Issue, Feature & Bug Support: erwin.yusrizal@gmail.com
***/
var expck;
;(function (gQuery, window, document, undefined) {

	/**
    * GLOBAR VAR
    */
    var _globalWalkthrough = {},
         _elements = [],
         _activeWalkthrough,
         _activeId,
         _counter = 0,
         _isCookieLoad,
         _firstTimeLoad = true,
         _onLoad = true,
         _index = 0,
         _isWalkthroughActive = true,
         $jpwOverlay = gQuery('<div id="jpwOverlay"></div>'),
         $jpWalkthrough = gQuery('<div id="jpWalkthrough"></div>'),
         $jpwTooltip = gQuery('<div id="jpwTooltip"></div>');

    /**
    * PUBLIC METHOD
    */

    var methods = {

        isPageWalkthroughActive: function () {
            if (_isWalkthroughActive) {
                return true;
            }
            return false;

        },

        currIndex: function () {
            return _index;
        },

        //init method
        init: function (options) {

            var options = gQuery.extend({}, gQuery.fn.pagewalkthrough.options, options);
            

            return this.each(function () {
                var $this = gQuery(this),
                    elementId = $this.attr('id');                    
                
                options = options || {};
                options.elementID = elementId;

                _globalWalkthrough[elementId] = options;
                _elements.push(elementId);

                //check if onLoad and this is first time load
                if (options.onLoad) {
                    _counter++;
                }

                //get first onload = true
                if (_counter == 1 && _onLoad) {
                    _activeId = elementId;
                    _activeWalkthrough = _globalWalkthrough[_activeId];      
                    _onLoad = false;    
                } 
				
				//Funzione per controllo lightbox onload!
				if (!getCookie('tourlightbox')){
					setCookie('tourlightbox', 0, expck);  
				}
				//alert(_counter);
                
                //set the _index from cookie if needed
                if(typeof(redirectCookie)!="undefined" && redirectCookie!="undefined" && redirectCookie!='' && !isNaN(redirectCookie) && typeof(_activeWalkthrough)!="undefined" && options.walkthroughCookieKey!="")
                {
                    var lastStepNo = getCookie(options.walkthroughCookieKey);
                    if(typeof(lastStepNo)!=="undefined" && !isNaN(lastStepNo)&& typeof(_activeWalkthrough.steps[_index])!=="undefined")
                    {
                        _index = parseInt(lastStepNo);
                    }
                }
                
                //read remember me cookie
                

                // when user scroll the page, scroll it back to keep walkthought on user view
                gQuery(window).scroll(function () {
                    if (typeof(_isWalkthroughActive)!="undefined" && _isWalkthroughActive && _activeWalkthrough.steps[_index].stayFocus) {
                        clearTimeout(gQuery.data(this, 'scrollTimer'));
                        gQuery.data(this, 'scrollTimer', setTimeout(function () {
                            scrollToTarget(_activeWalkthrough);
                        }, 250));
                    }

                    return false;

                });
                
                
                if(options.enableKeyboard)
                {
                    gQuery(document).bind('keydown.fb', function (e) {
                      var code   = e.which || e.keyCode,
                    		target = e.target || e.srcElement;
                      if (code === 27) {
                            //
                            if(gQuery('#jpwClose').length > 0)
                            {
                                methods.close();                                
                            }
                            return false;
                   	  }
                      if (!e.ctrlKey && !e.altKey && !e.shiftKey && !e.metaKey && !(target && (target.type || gQuery(target).is('[contenteditable]')))) 
                      {
                        
                        if((code == 39 || code == 40) && (gQuery('a.next-step','#tooltipInner').length > 0) )//next button
                        {
                            //gQuery('a.next-step','#tooltipInner').click();
                            gQuery.pagewalkthrough('next',e);
                        }
                        if((code == 37 || code== 38) && (gQuery('a.prev-step','#tooltipInner').length > 0) )//next button
                        {
                            gQuery.pagewalkthrough('prev',e);
                            //gQuery('a.prev-step','#tooltipInner').click();
                        }
                      }
                    });
                }

            });

        },

        renderOverlay: function () {

            //if each walkthrough has onLoad = true, throw warning message to the console
            if (_counter > 1) {
                debug('Warning: Only first walkthrough will be shown onLoad as default');
            }          
            
            //get cookie load
            _isCookieLoad = getCookie('_walkthrough-' + _activeId);

            //check if first time walkthrough
            if (_isCookieLoad == undefined) {
                _isWalkthroughActive = true;
                buildWalkthrough();
                showCloseButton();

                scrollToTarget();
                
                setTimeout(function () {
                    //call onAfterShow callback
                    if (_index == 0 && _firstTimeLoad) {
                        if (!onAfterShow()) return;
                    }
                }, 100);            
            } else {//check when user used to close the walkthrough to call the onCookieLoad callback
                onCookieLoad(_globalWalkthrough);
            }
        },

        restart: function (e) {
            if (_index == 0) return;

            _index = 0;
            if(!(onRestart(e)))return;
            if(!(onEnter(e)))return;
            buildWalkthrough();

            scrollToTarget();
            
        },

        close: function (target) {

            _index = 0;
            _firstTimeLoad = true;

            _isWalkthroughActive = false;

            if (target) {
                //set cookie to false
                setCookie('_walkthrough-' + target, 0, 365);
                _isCookieLoad = getCookie('_walkthrough-' + target);
            } else {
                //set cookie to false
                setCookie('_walkthrough-' + _activeId, 0, 365);
                _isCookieLoad = getCookie('_walkthrough-' + _activeId);
            }

            if (gQuery('#jpwOverlay').length) {
                $jpwOverlay.fadeOut('slow', function () {
                    gQuery(this).remove();
                });
            }

            if (gQuery('#jpWalkthrough').length) {
                $jpWalkthrough.fadeOut('slow', function () {
                    gQuery(this).html('').remove();
                });
            }

            if (gQuery('#jpwClose').length) {
                gQuery('#jpwClose').fadeOut('slow', function () {
                    gQuery(this).html('').remove();
                });
            }
            
            //added by sam
            var opt = _activeWalkthrough;
            
            if(typeof(opt)!="undefined" && typeof(opt.enableTimer)!="undefined" && opt.enableTimer && websiteTourTimer)
            {
                gQuery('#time_progress','#tooltipInner').chrony('set', { destroy: true });
            }
            
            //added by sam, reset the interval if exist
            //clearInterval(websiteTourTimer);
            //websiteTourTime = 0;
        },

        show: function (target) {
            _isWalkthroughActive = true;
            _firstTimeLoad = true;
            _activeId = target;
            _activeWalkthrough = _globalWalkthrough[target];

            buildWalkthrough();
            showCloseButton();

            scrollToTarget();
			
			

            //call onAfterShow callback
            if (_index == 0 && _firstTimeLoad) {
                if (!onAfterShow()) return;
            }

        },

        next: function (e) {
            
            _firstTimeLoad = false;
            if (_index == (_activeWalkthrough.steps.length - 1)) return;
            if(!onLeave(e))return;
            _index = parseInt(_index) + 1;
            
            if(!onEnter(e))return;
            buildWalkthrough();
            
            scrollToTarget();
            //added by sam to save step no. at cookie
            
            
            if(typeof(_activeWalkthrough)!=="undefined" && _activeWalkthrough.walkthroughCookieKey!="")
            {
                setCookie(_activeWalkthrough.walkthroughCookieKey, _index, _activeWalkthrough.cookie_expire_day);    
            }
            
            if(typeof(_activeWalkthrough)!=="undefined" && 
                typeof(_activeWalkthrough.steps[_index-1])!="undefined" && typeof(_activeWalkthrough.steps[_index-1].redirect_to)!="undefined")
            {
                //must be loadWalkthroughFromCookie enabled                
                setCookie('redirect_pending', _index, 1);  
                //put redirect tracker
                setCookie('redirect_tracker_'+_index,window.location.href);
				
                
                //redirect
                window.location.href = _activeWalkthrough.steps[_index-1].redirect_to;
                //close the tour
                methods.close();
            }
            else
            {
                //alert(123);
                setCookie('redirect_pending', "", -1);  
            }
        },

        prev: function (e) {
            if (_index == 0) return;
            var lastIndex = _index;//added by sam 
            if(!onLeave(e))return;
            _index = parseInt(_index) - 1;
            if(!onEnter(e))return;
            buildWalkthrough();

            scrollToTarget();
            //added by sam to save step no. at cookie
            
            if(typeof(_activeWalkthrough)!=="undefined" && _activeWalkthrough.walkthroughCookieKey!="")
            {
                setCookie(_activeWalkthrough.walkthroughCookieKey, _index, _activeWalkthrough.cookie_expire_day);    
            }
            if(typeof(_activeWalkthrough)!=="undefined" && 
                typeof(_activeWalkthrough.steps[lastIndex])!="undefined")
            {
                var redirectHistory = getCookie('redirect_tracker_'+lastIndex);
                if(typeof(redirectHistory)!="undefined" && redirectHistory!="undefined" && redirectHistory!="")
                {
                    setCookie('redirect_tracker_'+lastIndex,"",-1);
                    //must be loadWalkthroughFromCookie enabled                
                    setCookie('redirect_pending', _index, 1);  
                    window.location.href = redirectHistory;
                    //close the tour
                    methods.close();
                }
                else
                {
                    setCookie('redirect_pending', "", -1);  
                }
            }
        },

        getOptions: function (activeWalkthrough) {
            var _wtObj;

            //get only current active walkthrough
            if (activeWalkthrough) {
                _wtObj = {};
                _wtObj = _activeWalkthrough;
                //get all walkthrough
            } else {
                _wtObj = [];
                for (var wt in _globalWalkthrough) {
                    _wtObj.push(_globalWalkthrough[wt]);
                }
            }

            return _wtObj;
        }


    };//end public method



    /*
    * BUILD OVERLAY
    */
    function buildWalkthrough() {

        var opt = _activeWalkthrough;

        //call onBeforeShow callback
        if (_index == 0 && _firstTimeLoad) {
            if (!onBeforeShow()) return;
        }

        if (typeof(opt)!=="undefined" && opt.steps[_index].popup.type != 'modal' && opt.steps[_index].popup.type != 'nohighlight') {

            $jpWalkthrough.html('');

            //check if wrapper is not empty or undefined
            
            if (opt.steps[_index].wrapper == '' || opt.steps[_index].wrapper == undefined) {
                alert('Your walkthrough position is: "' + opt.steps[_index].popup.type + '" but wrapper is empty or undefined. Please check your "' + _activeId + '" wrapper parameter.');
                return;
            }            
            //added by sam  start
            //check the wrapper is really exist
            if(gQuery(opt.steps[_index].wrapper).length == 0)
            {
                return;
            }
            //added by sam end
            var topOffset = cleanValue(gQuery(opt.steps[_index].wrapper).offset().top);
            var leftOffset = cleanValue(gQuery(opt.steps[_index].wrapper).offset().left);
            var transparentWidth = cleanValue(gQuery(opt.steps[_index].wrapper).innerWidth()) || cleanValue(gQuery(opt.steps[_index].wrapper).width());
            var transparentHeight = cleanValue(gQuery(opt.steps[_index].wrapper).innerHeight()) || cleanValue(gQuery(opt.steps[_index].wrapper).height());

            //get all margin and make it gorgeous with the 'px', if it has no px, IE will get angry !!
            var marginTop = cssSyntax(opt.steps[_index].margin, 'top'),
                marginRight = cssSyntax(opt.steps[_index].margin, 'right'),
                marginBottom = cssSyntax(opt.steps[_index].margin, 'bottom'),
                marginLeft = cssSyntax(opt.steps[_index].margin, 'left'),
                roundedCorner = 30,
                overlayClass = '',
                killOverlay = '';

            var overlayTopStyle = {
                'height': cleanValue(parseInt(topOffset) - (parseInt(marginTop) + (roundedCorner)))
            }

            var overlayLeftStyle = {
                'top': overlayTopStyle.height,
                'width': cleanValue(parseInt(leftOffset) - (parseInt(marginLeft) + roundedCorner)),
                'height': cleanValue(parseInt(transparentHeight) + (roundedCorner * 2) + parseInt(marginTop) + parseInt(marginBottom))
            }


            //check if use overlay      
            if (opt.steps[_index].overlay == undefined || opt.steps[_index].overlay) {
                overlayClass = 'overlay';
            } else {
                overlayClass = 'noOverlay';
                killOverlay = 'killOverlay';
            }

            var overlayTop = gQuery('<div id="overlayTop" class="' + overlayClass + '"></div>').css(overlayTopStyle).appendTo($jpWalkthrough);
            var overlayLeft = gQuery('<div id="overlayLeft" class="' + overlayClass + '"></div>').css(overlayLeftStyle).appendTo($jpWalkthrough);

            if (!opt.steps[_index].accessable) {

                var highlightedAreaStyle = {
                    'top': overlayTopStyle.height,
                    'left': overlayLeftStyle.width,
                    'topCenter': {
                        'width': cleanValue(parseInt(transparentWidth) + parseInt(marginLeft) + parseInt(marginRight))
                    },
                    'middleLeft': {
                        'height': cleanValue(parseInt(transparentHeight) + parseInt(marginTop) + parseInt(marginBottom))
                    },
                    'middleCenter': {
                        'width': cleanValue(parseInt(transparentWidth) + parseInt(marginLeft) + parseInt(marginRight)),
                        'height': cleanValue(parseInt(transparentHeight) + parseInt(marginTop) + parseInt(marginBottom))
                    },
                    'middleRight': {
                        'height': cleanValue(parseInt(transparentHeight) + parseInt(marginTop) + parseInt(marginBottom))
                    },
                    'bottomCenter': {
                        'width': cleanValue(parseInt(transparentWidth) + parseInt(marginLeft) + parseInt(marginRight))
                    }
                }

                var highlightedArea = gQuery('<div id="highlightedArea"></div>').css(highlightedAreaStyle).appendTo($jpWalkthrough);

                highlightedArea.html('<div>' +
                                        '<div id="topLeft" class="' + killOverlay + '"></div>' +
                                        '<div id="topCenter" class="' + killOverlay + '" style="width:' + highlightedAreaStyle.topCenter.width + ';"></div>' +
                                        '<div id="topRight" class="' + killOverlay + '"></div>' +
                                    '</div>' +

                                    '<div style="clear: left;">' +
                                        '<div id="middleLeft" class="' + killOverlay + '" style="height:' + highlightedAreaStyle.middleLeft.height + ';"></div>' +
                                        '<div id="middleCenter" class="' + killOverlay + '" style="width:' + highlightedAreaStyle.middleCenter.width + ';height:' + highlightedAreaStyle.middleCenter.height + '">&nbsp;</div>' +
                                        '<div id="middleRight" class="' + killOverlay + '" style="height:' + highlightedAreaStyle.middleRight.height + ';"></div>' +
                                    '</div>' +

                                    '<div style="clear: left;">' +
                                        '<div id="bottomLeft" class="' + killOverlay + '"></div>' +
                                        '<div id="bottomCenter" class="' + killOverlay + '" style="width:' + highlightedAreaStyle.bottomCenter.width + ';"></div>' +
                                        '<div id="bottomRight" class="' + killOverlay + '"></div>' +
                                    '</div>');
            } else {

                //if accessable
                var highlightedAreaStyle = {
                    'top': overlayTopStyle.height,
                    'left': overlayLeftStyle.width,
                    'topCenter': {
                        'width': cleanValue(parseInt(transparentWidth) + parseInt(marginLeft) + parseInt(marginRight))
                    }
                }

                var accessableStyle = {

                    'topAccessable': {
                        'position': 'absolute',
                        'top': overlayTopStyle.height,
                        'left': overlayLeftStyle.width,
                        'topCenter': {
                            'width': cleanValue(parseInt(transparentWidth) + parseInt(marginLeft) + parseInt(marginRight))
                        }
                    },
                    'middleAccessable': {
                        'position': 'absolute',
                        'top': cleanValue(parseInt(overlayTopStyle.height) + roundedCorner),
                        'left': overlayLeftStyle.width,
                        'middleLeft': {
                            'height': cleanValue(parseInt(transparentHeight) + parseInt(marginTop) + parseInt(marginBottom))
                        },
                        'middleRight': {
                            'height': cleanValue(parseInt(transparentHeight) + parseInt(marginTop) + parseInt(marginBottom)),
                            'right': cleanValue(parseInt(transparentWidth) + roundedCorner + parseInt(marginRight) + parseInt(marginLeft))
                        }
                    },
                    'bottomAccessable': {
                        'left': overlayLeftStyle.width,
                        'top': cleanValue(parseInt(overlayTopStyle.height) + roundedCorner + parseInt(transparentHeight) + parseInt(marginTop) + parseInt(marginBottom)),
                        'bottomCenter': {
                            'width': cleanValue(parseInt(transparentWidth) + parseInt(marginLeft) + parseInt(marginRight))
                        }
                    }
                }

                var highlightedArea = gQuery('<div id="topAccessable" style="position:' + accessableStyle.topAccessable.position + '; top:' + accessableStyle.topAccessable.top + ';left:' + accessableStyle.topAccessable.left + '">' +
                                        '<div id="topLeft" class="' + killOverlay + '"></div>' +
                                        '<div id="topCenter" class="' + killOverlay + '" style="width:' + accessableStyle.topAccessable.topCenter.width + '"></div>' +
                                        '<div id="topRight" class="' + killOverlay + '"></div>' +
                                    '</div>' +

                                    '<div id="middleAccessable" class="' + killOverlay + '" style="clear: left;position:' + accessableStyle.middleAccessable.position + '; top:' + accessableStyle.middleAccessable.top + ';left:' + accessableStyle.middleAccessable.left + ';">' +
                                        '<div id="middleLeft" class="' + killOverlay + '" style="height:' + accessableStyle.middleAccessable.middleLeft.height + ';"></div>' +
                                        '<div id="middleRight" class="' + killOverlay + '" style="position:absolute;right:-' + accessableStyle.middleAccessable.middleRight.right + ';height:' + accessableStyle.middleAccessable.middleRight.height + ';"></div>' +
                                    '</div>' +

                                    '<div id="bottomAccessable" style="clear: left;position:absolute;left:' + accessableStyle.bottomAccessable.left + ';top:' + accessableStyle.bottomAccessable.top + ';">' +
                                        '<div id="bottomLeft" class="' + killOverlay + '"></div>' +
                                        '<div id="bottomCenter" class="' + killOverlay + '" style="width:' + accessableStyle.bottomAccessable.bottomCenter.width + ';"></div>' +
                                        '<div id="bottomRight" class="' + killOverlay + '"></div>' +
                                    '</div>').appendTo($jpWalkthrough);

            } //end checking accessable

            var highlightedAreaWidth = (opt.steps[_index].accessable) ? parseInt(accessableStyle.topAccessable.topCenter.width) + (roundedCorner * 2) : (parseInt(highlightedAreaStyle.topCenter.width) + (roundedCorner * 2));


            var overlayRightStyle = {
                'left': cleanValue(parseInt(overlayLeftStyle.width) + highlightedAreaWidth),
                'height': overlayLeftStyle.height,
                'top': overlayLeftStyle.top,
                'width': cleanValue(windowWidth() - (parseInt(overlayLeftStyle.width) + highlightedAreaWidth))
            }

            var overlayRight = gQuery('<div id="overlayRight" class="' + overlayClass + '"></div>').css(overlayRightStyle).appendTo($jpWalkthrough);

            var overlayBottomStyle = {
                'height': cleanValue(gQuery(document).height() - (parseInt(overlayTopStyle.height) + parseInt(overlayLeftStyle.height))),
                'top': cleanValue(parseInt(overlayTopStyle.height) + parseInt(overlayLeftStyle.height))
            }

            var overlayBottom = gQuery('<div id="overlayBottom" class="' + overlayClass + '"></div>').css(overlayBottomStyle).appendTo($jpWalkthrough);



            if (gQuery('#jpWalkthrough').length) {
                gQuery('#jpWalkthrough').remove();
            }

            $jpWalkthrough.appendTo('body').show();

            if (opt.steps[_index].accessable) {
                showTooltip(true);
            } else {
                showTooltip(false);
            }


        } else if(typeof(opt)!=="undefined" && opt.steps[_index].popup.type == 'modal'){

            if (gQuery('#jpWalkthrough').length) {
                gQuery('#jpWalkthrough').remove();
            }

            if (opt.steps[_index].overlay == undefined || opt.steps[_index].overlay) {
                showModal(true);
            } else {
                showModal(false);
            }

        }else{
            if (gQuery('#jpWalkthrough').length) {
                gQuery('#jpWalkthrough').remove();
            }


            if (typeof(opt)!=="undefined" && (opt.steps[_index].overlay == undefined || opt.steps[_index].overlay)) {
                noHighlight(true);
            } else {
                noHighlight(false);
            }
        }
    }

    /*
    * SHOW MODAL
    */
    function showModal(isOverlay) {
        var opt = _activeWalkthrough, overlayClass = '';

        if (isOverlay) {
            $jpwOverlay.appendTo('body').show();
        } else {
            if (gQuery('#jpwOverlay').length) {
                gQuery('#jpwOverlay').remove();
            }
        }

        var textRotation =  setRotation(parseInt(opt.steps[_index].popup.contentRotation));

        $jpwTooltip.css({ 'position': 'absolute', 'left': '50%', 'top': '50%', 'margin-left': -(parseInt(opt.steps[_index].popup.width) + 60) / 2 + 'px','z-index':'9999'});

        var tooltipSlide = gQuery('<div id="tooltipTop">' +
                                '<div id="topLeft"></div>' +
                                '<div id="topRight"></div>' +
                            '</div>' +

                            '<div id="tooltipInner">' +
                            '</div>' +

                            '<div id="tooltipBottom">' +
                                '<div id="bottomLeft"></div>' +
                                '<div id="bottomRight"></div>' +
                            '</div>');       

        $jpWalkthrough.html('');
        $jpwTooltip.html('').append(tooltipSlide)
                            .wrapInner('<div id="tooltipWrapper" style="width:'+cleanValue(parseInt(opt.steps[_index].popup.width) + 30)+'"></div>')
                            .append('<div id="bottom-scratch"></div>')
                            .appendTo($jpWalkthrough);
        
        $jpWalkthrough.appendTo('body');

        gQuery('#tooltipWrapper').css(textRotation);

        gQuery(opt.steps[_index].popup.content).clone().appendTo('#tooltipInner').show();
        
        //added by sam start for setting timer
        if(typeof(opt.enableTimer)!="undefined" && opt.enableTimer && typeof(opt.steps[_index])!="undefined" && typeof(opt.steps[_index].hault_time)!="undefined" 
            && !isNaN(opt.steps[_index].hault_time) && opt.steps[_index].hault_time > 0 && (gQuery('a.next-step','#tooltipInner').length > 0))
        {
            websiteTourTimer = true;
            gQuery('#time_progress','#tooltipInner').chrony({
    			seconds	: opt.steps[_index].hault_time,
    			finish	: function() {
    			    websiteTourTimer = false; 
    				gQuery('a.next-step','#tooltipInner').click();
    			}
    		});
        }
        //added by sam start for setting timer
        $jpwTooltip.css('margin-top', -(($jpwTooltip.height()) / 2)+ 'px');
        $jpWalkthrough.show();


    }


    /*
    * SHOW TOOLTIP
    */
    function showTooltip(isAccessable) {

        var opt = _activeWalkthrough;

        var tooltipWidth = (opt.steps[_index].popup.width == '') ? 300 : opt.steps[_index].popup.width,
            top, left, arrowTop, arrowLeft,
            roundedCorner = 30,
            overlayHoleWidth = (isAccessable) ? (gQuery('#topAccessable').innerWidth() + (roundedCorner * 2)) || (gQuery('#topAccessable').width() + (roundedCorner * 2)) : gQuery('#highlightedArea').innerWidth() || gQuery('#highlightedArea').width(),
            overlayHoleHeight = (isAccessable) ? gQuery('#middleAccessable').innerHeight() + (roundedCorner * 2) || gQuery('#middleAccessable').height() + (roundedCorner * 2) : gQuery('#highlightedArea').innerHeight() || gQuery('#highlightedArea').height(),
            overlayHoleTop = (isAccessable) ? gQuery('#topAccessable').offset().top : gQuery('#highlightedArea').offset().top,
            overlayHoleLeft = (isAccessable) ? gQuery('#topAccessable').offset().left : gQuery('#highlightedArea').offset().left,
            arrow = 30,
            draggable = '';  

        var textRotation = (opt.steps[_index].popup.contentRotation == undefined || parseInt(opt.steps[_index].popup.contentRotation) == 0) ? clearRotation() : setRotation(parseInt(opt.steps[_index].popup.contentRotation));


        //delete jwOverlay if any
        if (gQuery('#jpwOverlay').length) {
            gQuery('#jpwOverlay').remove();
        }

        var tooltipSlide = gQuery('<div id="tooltipTop">' +
                                '<div id="topLeft"></div>' +
                                '<div id="topRight"></div>' +
                            '</div>' +

                            '<div id="tooltipInner">' +
                            '</div>' +

                            '<div id="tooltipBottom">' +
                                '<div id="bottomLeft"></div>' +
                                '<div id="bottomRight"></div>' +
                            '</div>');

        $jpwTooltip.html('').css({ 'marginLeft': '0', 'margin-top': '0', 'position': 'absolute','z-index':'9999'})
                           .append(tooltipSlide)
                           .wrapInner('<div id="tooltipWrapper" style="width:'+cleanValue(parseInt(opt.steps[_index].popup.width) + 30)+'"></div>')
                           .appendTo($jpWalkthrough);

        if (opt.steps[_index].popup.draggable) {
            $jpwTooltip.append('<div id="drag-area" class="draggable-area"></div>');
        }        

        $jpWalkthrough.appendTo('body').show();

        gQuery('#tooltipWrapper').css(textRotation);

        gQuery(opt.steps[_index].popup.content).clone().appendTo('#tooltipInner').show();
        
        $jpwTooltip.append('<span class="' + opt.steps[_index].popup.position + '">&nbsp;</span>');
        
        
        //added by sam start for setting timer
        
        if(typeof(opt.enableTimer)!="undefined" && opt.enableTimer && typeof(opt.steps[_index])!="undefined" && typeof(opt.steps[_index].hault_time)!="undefined" 
            && !isNaN(opt.steps[_index].hault_time) && opt.steps[_index].hault_time > 0 && (gQuery('a.next-step','#tooltipInner').length > 0))
        {            
            websiteTourTimer = true;
            gQuery('#time_progress','#tooltipInner').chrony({
    			seconds	: opt.steps[_index].hault_time,
    			finish	: function() {
    			    websiteTourTimer = false; 
    				gQuery('a.next-step','#tooltipInner').click();
    			}
    		});
        }
        //added by sam start for setting timer
        
        switch (opt.steps[_index].popup.position) {

            case 'top':
                top = overlayHoleTop - ($jpwTooltip.height() + (arrow / 2)) + parseInt(opt.steps[_index].popup.offsetVertical) - 86;
                if (isAccessable) {
                    left = (overlayHoleLeft + (overlayHoleWidth / 2)) - ($jpwTooltip.width() / 2) - 40 + parseInt(opt.steps[_index].popup.offsetHorizontal);
                } else {
                    left = (overlayHoleLeft + (overlayHoleWidth / 2)) - ($jpwTooltip.width() / 2) - 5 + parseInt(opt.steps[_index].popup.offsetHorizontal);
                }
                arrowLeft = ($jpwTooltip.width() / 2) - arrow;
                arrowTop = '';
                break;
            case 'right':
                top = overlayHoleTop - (arrow / 2) + parseInt(opt.steps[_index].popup.offsetVertical);
                left = overlayHoleLeft + overlayHoleWidth + (arrow / 2) + parseInt(opt.steps[_index].popup.offsetHorizontal) + 105;
                arrowTop = arrow;
                arrowLeft = '';
                break;
            case 'bottom':

                if (isAccessable) {
                    top = (overlayHoleTop + overlayHoleHeight) + parseInt(opt.steps[_index].popup.offsetVertical) + 86;
                    left = (overlayHoleLeft + (overlayHoleWidth / 2)) - ($jpwTooltip.width() / 2) - 40 + parseInt(opt.steps[_index].popup.offsetHorizontal);
                } else {
                    top = overlayHoleTop + overlayHoleHeight + parseInt(opt.steps[_index].popup.offsetVertical) + 86;
                    left = (overlayHoleLeft + (overlayHoleWidth / 2)) - ($jpwTooltip.width() / 2) - 5 + parseInt(opt.steps[_index].popup.offsetHorizontal);
                }

                arrowLeft = ($jpwTooltip.width() / 2) - arrow;
                arrowTop = '';
                break;
            case 'left':
                top = overlayHoleTop - (arrow / 2) + parseInt(opt.steps[_index].popup.offsetVertical);
                left = overlayHoleLeft - $jpwTooltip.width() - (arrow) + parseInt(opt.steps[_index].popup.offsetHorizontal) - 105;
                arrowTop = arrow;
                arrowLeft = '';
                break;
        }

        gQuery('#jpwTooltip span.' + opt.steps[_index].popup.position).css({ 'left': cleanValue(arrowLeft) });

        $jpwTooltip.css({ 'top': cleanValue(top), 'left': cleanValue(left) });
        $jpWalkthrough.show();
    }

    /**
     * POPUP NO HIGHLIGHT
     */

     function noHighlight(isOverlay){
        var opt = _activeWalkthrough, overlayClass = '';
        if(typeof(opt)==="undefined")
            return;
        var wrapperTop = gQuery(opt.steps[_index].wrapper).offset().top,
            wrapperLeft = gQuery(opt.steps[_index].wrapper).offset().left,
            wrapperWidth = gQuery(opt.steps[_index].wrapper).width(),
            wrapperHeight = gQuery(opt.steps[_index].wrapper).height(),
            arrow = 30,
            draggable = '',
            top, left, arrowTop, arrowLeft; 

        if (isOverlay) {
            $jpwOverlay.appendTo('body').show();
        } else {
            if (gQuery('#jpwOverlay').length) {
                gQuery('#jpwOverlay').remove();
            }
        }

        $jpwTooltip.css(clearRotation());

        var textRotation = (opt.steps[_index].popup.contentRotation == 'undefined' || opt.steps[_index].popup.contentRotation == 0) ? '' : setRotation(parseInt(opt.steps[_index].popup.contentRotation));

        $jpwTooltip.css({ 'position': 'absolute','margin-left': '0px','margin-top':'0px','z-index':'9999'});

        var tooltipSlide = gQuery('<div id="tooltipTop">' +
                                '<div id="topLeft"></div>' +
                                '<div id="topRight"></div>' +
                            '</div>' +

                            '<div id="tooltipInner">' +
                            '</div>' +

                            '<div id="tooltipBottom">' +
                                '<div id="bottomLeft"></div>' +
                                '<div id="bottomRight"></div>' +
                            '</div>');            

        $jpWalkthrough.html('');
        $jpwTooltip.html('').append(tooltipSlide)
                            .wrapInner('<div id="tooltipWrapper" style="width:'+cleanValue(parseInt(opt.steps[_index].popup.width) + 30)+'"></div>')
                            .appendTo($jpWalkthrough);

        if (opt.steps[_index].popup.draggable) {
            $jpwTooltip.append('<div id="drag-area" class="draggable-area"></div>');
        }
        
        $jpWalkthrough.appendTo('body');

        gQuery('#tooltipWrapper').css(textRotation);

        gQuery(opt.steps[_index].popup.content).clone().appendTo('#tooltipInner').show();
        
        //added by sam start for setting timer
        
        if(typeof(opt.enableTimer)!="undefined" && opt.enableTimer && typeof(opt.steps[_index])!="undefined" && typeof(opt.steps[_index].hault_time)!="undefined" 
            && !isNaN(opt.steps[_index].hault_time) && opt.steps[_index].hault_time > 0 && (gQuery('a.next-step','#tooltipInner').length > 0))
        {
            websiteTourTimer = true;
            gQuery('#time_progress','#tooltipInner').chrony({
    			seconds	: opt.steps[_index].hault_time,
    			finish	: function() {
    			    websiteTourTimer = false; 
    				gQuery('a.next-step','#tooltipInner').click();
    			}
    		});
        }
        //added by sam start for setting timer
        
        
        $jpwTooltip.append('<span class="' + opt.steps[_index].popup.position + '">&nbsp;</span>');

        switch (opt.steps[_index].popup.position) {

            case 'top':
                top = wrapperTop - ($jpwTooltip.height() + (arrow / 2)) + parseInt(opt.steps[_index].popup.offsetVertical) - 86;
                left = (wrapperLeft + (wrapperWidth / 2)) - ($jpwTooltip.width() / 2) - 5 + parseInt(opt.steps[_index].popup.offsetHorizontal);
                arrowLeft = ($jpwTooltip.width() / 2) - arrow;
                arrowTop = '';
                break;
            case 'right':
                top = wrapperTop - (arrow / 2) + parseInt(opt.steps[_index].popup.offsetVertical);
                left = wrapperLeft + wrapperWidth + (arrow / 2) + parseInt(opt.steps[_index].popup.offsetHorizontal) + 105;
                arrowTop = arrow;
                arrowLeft = '';
                break;
            case 'bottom':
                top = wrapperTop + wrapperHeight + parseInt(opt.steps[_index].popup.offsetVertical) + 86;
                left = (wrapperLeft + (wrapperWidth / 2)) - ($jpwTooltip.width() / 2) - 5 + parseInt(opt.steps[_index].popup.offsetHorizontal);
                arrowLeft = ($jpwTooltip.width() / 2) - arrow;
                arrowTop = '';
                break;
            case 'left':
                top = wrapperTop - (arrow / 2) + parseInt(opt.steps[_index].popup.offsetVertical);
                left = wrapperLeft - $jpwTooltip.width() - (arrow) + parseInt(opt.steps[_index].popup.offsetHorizontal) - 105;
                arrowTop = arrow;
                arrowLeft = '';
                break;
        }

        gQuery('#jpwTooltip span.' + opt.steps[_index].popup.position).css({ 'left': cleanValue(arrowLeft) });

        $jpwTooltip.css({ 'top': cleanValue(top), 'left': cleanValue(left) });
        $jpWalkthrough.show();


     }

     /*
    * SCROLL TO TARGET
    */
    function scrollToTarget() {

        var options = _activeWalkthrough;
        if(typeof(options)==="undefined")
            return;
        if (options.steps[_index].autoScroll ||  options.steps[_index].autoScroll == undefined) {
            if (options.steps[_index].popup.position != 'modal') {

                var windowHeight = gQuery(window).height() || gQuery(window).innerHeight(),
                    targetOffsetTop = $jpwTooltip.offset().top,
                    targetHeight = $jpwTooltip.height() ||  $jpwTooltip.innerHeight(),
                    overlayTop = gQuery('#overlayTop').height();

                gQuery('html,body').animate({scrollTop: (targetOffsetTop + (targetHeight/2) - (windowHeight/2))}, options.steps[_index].scrollSpeed);    
                
            } else {
                gQuery('html,body').animate({ scrollTop: 0 }, options.steps[_index].scrollSpeed);
            }

        }
    }


    /**
     * SHOW CLOSE BUTTON
     */
     function showCloseButton(){
        
        if(!gQuery('jpwClose').length){
            gQuery('body').append('<div id="jpwClose"><a href="javascript:;"><span></span><br>'+Joomla.JText._('MOD_WEBSITE_CLICKTOCLOSE')+'</a></div>');
        }
     }




    /**
    /* CALLBACK
    /*/

    //callback for onLoadHidden cookie
    function onCookieLoad(options) {

        for (i = 0; i < _elements.length; i++) {
            if (typeof (options[_elements[i]].onCookieLoad) === "function") {
                options[_elements[i]].onCookieLoad.call(this);
            }
        }

        return false;
    }

    function onLeave(e) {
        var options = _activeWalkthrough;

        if (typeof options.steps[_index].onLeave === "function") {
            if (!options.steps[_index].onLeave.call(this, e, _index)) {
                return false;
            }
        }

        return true;

    }

    //callback for onEnter step
    function onEnter(e) {

        var options = _activeWalkthrough;
        if (typeof options.steps[_index].onEnter === "function") {
            if (!options.steps[_index].onEnter.call(this, e, _index)) {
                return false;
            }
        }

        return true;
    }

    //callback for onClose help
    function onClose() {
        var options = _activeWalkthrough;
        if (typeof options.onClose === "function") {
            if (!options.onClose.call(this)) {
                return false;
            }
        }
        
        

        //set help mode to false
        //_isWalkthroughActive = false;
        methods.close();

        return true;
    }

    //callback for onRestart help
    function onRestart(e) {
        var options = _activeWalkthrough;
        //set help mode to true
        _isWalkthroughActive = true;
        methods.restart(e);

        //auto scroll to target
        scrollToTarget();

        if (typeof(options)!=="undefined" && typeof options.onRestart === "function") {
            if (!options.onRestart.call(this)) {
                return false;
            }
        }

        return true;
    }

    //callback for before all first walkthrough element loaded
    function onBeforeShow() {
        var options = _activeWalkthrough;
        _index = 0;

        if (typeof(options)!=="undefined" && typeof (options.onBeforeShow) === "function") {
            if (!options.onBeforeShow.call(this)) {
                return false;
            }
        }

        return true;
    }

    //callback for after all first walkthrough element loaded
    function onAfterShow() {
        var options = _activeWalkthrough;
        _index = 0;

        if (typeof(options)!=="undefined" && typeof (options.onAfterShow) === "function") {
            if (!options.onAfterShow.call(this)) {
                return false;
            }
        }

        return true;
    }



	/**
    * HELPERS
    */

    function windowWidth() {
        return gQuery(window).innerWidth() || gQuery(window).width();
    }

    function debug(message) {
        if (window.console && window.console.log)
            window.console.log(message);
    }

    function clearRotation(){
        var rotationStyle = {
            '-webkit-transform': 'none', //safari
            '-moz-transform':'none', //firefox
            '-ms-transform': 'none', //IE9+
            '-o-transform': 'none', //opera
            'filter':'none', //IE7
            '-ms-transform' : 'none' //IE8
        }

        return rotationStyle;
    }

    function setRotation(angle){

        //for IE7 & IE8
        var M11, M12, M21, M22, deg2rad, rad;

        //degree to radian
        deg2rad = Math.PI * 2 / 360;
        rad = angle * deg2rad;

        M11 = Math.cos(rad);
        M12 = Math.sin(rad);
        M21 = Math.sin(rad);
        M22 = Math.cos(rad);

        var rotationStyle = {
            '-webkit-transform': 'rotate('+parseInt(angle)+'deg)', //safari
            '-moz-transform':'rotate('+parseInt(angle)+'deg)', //firefox
            '-ms-transform': 'rotate('+parseInt(angle)+'deg)', //IE9+
            '-o-transform': 'rotate('+parseInt(angle)+'deg)', //opera
            'filter':'progid:DXImageTransform.Microsoft.Matrix(M11 = '+M11+',M12 = -'+M12+',M21 = '+M21+',M22 = '+M22+',sizingMethod = "auto expand");', //IE7
            '-ms-transform' : 'progid:DXImageTransform.Microsoft.Matrix(M11 = '+M11+',M12 = -'+M12+',M21 = '+M21+',M22 = '+M22+',SizingMethod = "auto expand");' //IE8
        }

        return rotationStyle;

    }

    function cleanValue(value) {
        if (typeof value === "string") {
            if (value.toLowerCase().indexOf('px') == -1) {
                return value + 'px';
            } else {
                return value;
            }
        } else {
            return value + 'px';
        }
    }

    function cleanSyntax(val) {

        if (val.indexOf('px') != -1) {
            return true;
        } else if(parseInt(val) == 0){
            return true;
        }
        return false;
    }

    function cssSyntax(val, position) {
        var value = val,
            arrVal = value.split(' '),
            counter = 0,
            top, right, bottom, left, returnVal;

        for (i = 0; i < arrVal.length; i++) {
            //check if syntax is clean with value and 'px'
            if (cleanSyntax(arrVal[i])) {
                counter++;
            }
        }

        //all syntax are clean
        if (counter == arrVal.length) {

            for (i = 0; i < arrVal.length; i++) {

                switch (i) {
                    case 0:
                        top = arrVal[i];
                        break;
                    case 1:
                        right = arrVal[i];
                        break;
                    case 2:
                        bottom = arrVal[i];
                        break;
                    case 3:
                        left = arrVal[i];
                        break;
                }

            }

            switch (arrVal.length) {
                case 1:
                    right = bottom = left = top;
                    break;
                case 2:
                    bottom = top;
                    left = right;
                    break;
                case 3:
                    left = right;
                    break;
            }

            if (position == 'undefined' || position == '' || position == null) {
                console.log('Please define margin position (top, right, bottom, left)');
                return false;
            } else {

                switch (position) {
                    case 'top':
                        returnVal = top;
                        break;
                    case 'right':
                        returnVal = right;
                        break;
                    case 'bottom':
                        returnVal = bottom;
                        break;
                    case 'left':
                        returnVal = left;
                        break;
                }

            }

            return returnVal;

        } else {
            console.log('Please check your margin syntax..');
            return false;
        }
    }

    

    /**
     * BUTTON CLOSE CLICK
     */
     gQuery('#jpwClose a').live('click', onClose);


    /**
     * DRAG & DROP
     */

    gQuery('#jpwTooltip #drag-area').live('mousedown', function (e) {

        if (!gQuery(this).hasClass('draggable-area')) {
            return;
        }
        if (!gQuery(this).hasClass('draggable')) {
            gQuery(this).addClass('draggable').css('cursor','move');
        }

        var z_idx = gQuery(this).css('z-index'),
            drg_h = gQuery(this).outerHeight(),
            drg_w = gQuery(this).outerWidth(),
            pos_y = gQuery(this).offset().top + (drg_h*2) - e.pageY -10,
            pos_x = (e.pageX - gQuery(this).offset().left + drg_w) - (gQuery(this).parent().outerWidth() + drg_w) +20;

        gQuery(document).on("mousemove", function (e) {

            gQuery('.draggable').parent().offset({
                top: e.pageY + pos_y - drg_h,
                left: e.pageX + pos_x - drg_w 
            }).on("mouseup", function () {
                gQuery(this).children('#tooltipWrapper').removeClass('draggable').css({'z-index':z_idx,'cursor':'default'});
            });
        });
        e.preventDefault(); // disable selection
    }).live("mouseup", function () {
        gQuery(this).removeClass('draggable').css('cursor','default');
    });



	/**
    * MAIN PLUGIN
    */
    gQuery.pagewalkthrough = gQuery.fn.pagewalkthrough = function (method) {

        if (methods[method]) {

            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));

        } else if (typeof method === 'object' || !method) {

            methods.init.apply(this, arguments);

        } else {

            gQuery.error('Method ' + method + ' does not exist on jQuery.pagewalkthrough');

        }

    }

    /*setTimeout(function () {
        methods.renderOverlay();
    }, 500);
    */
	gQuery.fn.pagewalkthrough.options = {

		steps: [

			{
               wrapper: '', //an ID of page element HTML that you want to highlight
               margin: 0, //margin for highlighted area, may use CSS syntax i,e: '10px 20px 5px 30px'
               popup:
               {
					content: '', //ID content of the walkthrough
					type: 'modal', //tooltip, modal, nohighlight
                    position:'top',//position for tooltip and nohighlight type only: top, right, bottom, left
					offsetHorizontal: 0, //horizontal offset for the walkthrough
					offsetVertical: 0, //vertical offset for the walkthrough
					width: '320', //default width for each step,
					draggable: false, // set true to set walkthrough draggable,
					contentRotation: 0 //content rotation : i.e: 0, 90, 180, 270 or whatever value you add. minus sign (-) will be CCW direction
               },
               overlay:true,             
               accessable: false, //if true - you can access html element such as form input field, button etc
               autoScroll: true, //is true - this will autoscroll to the arror/content every step 
               scrollSpeed: 1000, //scroll speed
               stayFocus: false, //if true - when user scroll down/up to the page, it will scroll back the position it belongs
               onLeave: null, // callback when leaving the step
               onEnter: null // callback when entering the step
           }

		],
        name: '',
		onLoad: true, //load the walkthrough at first time page loaded
		onBeforeShow: null, //callback before page walkthrough loaded
		onAfterShow: null, // callback after page walkthrough loaded
		onRestart: null, //callback for onRestart walkthrough
		onClose: null, //callback page walkthrough closed
		onCookieLoad: null, //when walkthrough closed, it will set cookie and use callback if you want to create link to trigger to reopen the walkthrough
        //rest of the parameters have been added by sam 
        enableKeyboard: null, //keyboard handler
        loadWalkthroughFromCookie:false,//remember last step from cookie
        walkthroughCookieKey: "",
        cookie_expire_day: 365,
        enableTimer:false
	};

} (gQuery, window, document));

function setCookie(c_name, value, exdays) {
    var exdate = new Date();
    exdate.setDate(exdate.getDate() + exdays);
    var c_value = escape(value) + ((exdays == null) ? "" : "; expires=" + exdate.toUTCString()+'; path=/');
    document.cookie = c_name + "=" + c_value;
}

function getCookie(c_name) {
    var i, x, y, ARRcookies = document.cookie.split(";");
    for (i = 0; i < ARRcookies.length; i++) {
        x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
        y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
        x = x.replace(/^\s+|\s+$/g, "");
        if (x == c_name) {
            return unescape(y);
        }
    }
}