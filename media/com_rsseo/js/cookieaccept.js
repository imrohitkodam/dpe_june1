(function(funcName, baseObj) {
    "use strict";
    // The public function name defaults to window.docReady
    // but you can modify the last line of this function to pass in a different object or method name
    // if you want to put them in a different namespace and those will be used instead of 
    // window.docReady(...)
    funcName = funcName || "docReady";
    baseObj = baseObj || window;
    var readyList = [];
    var readyFired = false;
    var readyEventHandlersInstalled = false;
    
    // call this when the document is ready
    // this function protects itself against being called more than once
    function ready() {
        if (!readyFired) {
            // this must be set to true before we start calling callbacks
            readyFired = true;
            for (var i = 0; i < readyList.length; i++) {
                // if a callback here happens to add new ready handlers,
                // the docReady() function will see that it already fired
                // and will schedule the callback to run right after
                // this event loop finishes so all handlers will still execute
                // in order and no new ones will be added to the readyList
                // while we are processing the list
                readyList[i].fn.call(window, readyList[i].ctx);
            }
            // allow any closures held by these functions to free
            readyList = [];
        }
    }
    
    function readyStateChange() {
        if ( document.readyState === "complete" ) {
            ready();
        }
    }
    
    // This is the one public interface
    // docReady(fn, context);
    // the context argument is optional - if present, it will be passed
    // as an argument to the callback
    baseObj[funcName] = function(callback, context) {
        // if ready has already fired, then just schedule the callback
        // to fire asynchronously, but right away
        if (readyFired) {
            setTimeout(function() {callback(context);}, 1);
            return;
        } else {
            // add the function and context to the list
            readyList.push({fn: callback, ctx: context});
        }
        // if document already ready to go, schedule the ready function to run
        // IE only safe when readyState is "complete", others safe when readyState is "interactive"
        if (document.readyState === "complete" || (!document.attachEvent && document.readyState === "interactive")) {
            setTimeout(ready, 1);
        } else if (!readyEventHandlersInstalled) {
            // otherwise if we don't have event handlers installed, install them
            if (document.addEventListener) {
                // first choice is DOMContentLoaded event
                document.addEventListener("DOMContentLoaded", ready, false);
                // backup is window load event
                window.addEventListener("load", ready, false);
            } else {
                // must be IE
                document.attachEvent("onreadystatechange", readyStateChange);
                window.attachEvent("onload", ready);
            }
            readyEventHandlersInstalled = true;
        }
    }
})("rsseoDocReady", window);

(function(id, flag, target) {
	"use strict";
	
	var elem, trg, flg, alpha;
	
	function tween() {
		if (alpha == trg) {
			clearInterval(elem.si);
		}else{
			var value = Math.round(alpha + ((trg - alpha) * .05)) + (1 * flg);
			elem.style.opacity = value / 100;
			elem.style.filter = 'alpha(opacity=' + value + ')';
			elem.style.visibility = 'hidden';
			alpha = value
		}
	}
	
	window['rsseoFade'] = function(id, flag, target) {
		elem = document.getElementById(id);
		clearInterval(elem.si);
		trg = target ? target : flag ? 100 : 0;
		flg = flag || -1;
		alpha = elem.style.opacity ? parseFloat(elem.style.opacity) * 100 : 0;
		elem.si = setInterval(function(){tween()}, 20);
	}
	
})("rsseoFade", window);

function rsseo_addEvent(obj, evType, fn, useCapture){
	if (obj.addEventListener){
		obj.addEventListener(evType, fn, useCapture);
		return true;
	} else if (obj.attachEvent){
		var r = obj.attachEvent("on"+evType, fn);
		return r;
	} else {
		alert("Handler could not be attached");
	}
}

function rsseo_get(name) {
	var name = name + "=";
	var cookies = document.cookie.split(';');
	
	for (var i=0;i < cookies.length;i++) {
		var cookie = cookies[i];
		
		while (cookie.charAt(0)==' ') {
			cookie = cookie.substring(1, cookie.length);
		}
		
		if (cookie.indexOf(name) == 0) {
			return cookie.substring(name.length, cookie.length);
		}
	}
	
	return null;
}

function rsseo_set(name, value) {
	var date = new Date();
	date.setTime(date.getTime()+(365*24*60*60*1000));
	var expires = "; expires="+date.toGMTString();
	
	document.cookie = name+"="+value+expires+"; path=/";
}

function rsseo_accept() {
	var hasCookie = rsseo_get('rsseoaccept');
	
	if (!hasCookie) {
		rsseo_set('rsseoaccept',true);
	}
	
	rsseoFade('rsseo-cookie-accept', 0, 0);
}

rsseoDocReady(function() {
	var button = document.getElementById('rsseo-cookie-accept-btn');
	rsseo_addEvent(button, 'click', rsseo_accept);
});