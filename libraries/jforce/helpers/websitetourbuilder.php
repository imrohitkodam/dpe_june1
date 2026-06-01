<?php

/**
 * @version     1.4
 * @package     mod_websitetourbuilder
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      JoomlaForce Team <support@joomlaforce.com> - http://www.joomlaforce.com
 */

// No direct access allowed to this file

defined('_JEXEC') or die;
class DoTourHelper{

    public static function CreateTour($items,$params,$jquery_var){
        global $websiteTourModuleId;
		$displayas = $params->get('displayas');     
		//Cookies Settings
		$enable_cookies="";
		$enable_cookies= $params->get('enable_cookies',0); 
		$expire_cookies= $params->get('expire_cookies'); 
		//Add Custom Stylesheet for LightBox		
		$overbck = $params->get('ltoverlaycolor');	
		$overopacity = $params->get('ltoverlayopacity');	
		

		$onload='false';
        $autoStartJs = '';
        $redirectPendingJS = '';
		if(($enable_cookies)&&($displayas == 2)){
			
            $autoStartJs = "
			var getck = getCookie('tourlightbox');
							 if ( getck !=1){
								gQuery.pagewalkthrough('show', 'walkthrough');\n
							 } ";
			$redirectPendingJS = "
              if(redirectCookie!='undefined' && redirectCookie!='' && !isNaN(redirectCookie))\n
              {
                setCookie('redirect_pending', '', -1);\n
               }\n";
			$onload='true';
			
        } else if((!$enable_cookies)&&($displayas == 2)){
		 $autoStartJs = "gQuery.pagewalkthrough('show', 'walkthrough');\n ";
		} else
        {
            $redirectPendingJS = "
              if(redirectCookie!='undefined' && redirectCookie!='' && !isNaN(redirectCookie))\n
              {
                setCookie('redirect_pending', '', -1);\n
                if(gQuery('.popup-exit').length > 0)\n
                    clearPopup();\n
                gQuery.pagewalkthrough('show', 'walkthrough');\n
               }\n";
        }
        
        $onload = 'true';
        $enableKeyboard = '';
        if($params->get('enable_keyboard',0))
        {
            //board
            $enableKeyboard = "enableKeyboard: true,\n";
        }
        
        //added by sam for remembering steps from cookie
        $remember_last_step = "walkthroughCookieKey: '".'walkthrought_last_step'.$websiteTourModuleId."',\n
                               cookie_expire_day: 365,\n
                               ";
        
        $timerControlJS = "";
        $enableTimer = "";
        if($params->get('show_timer_controls',0))
        {
            $timerControlJS = DoTourHelper::getTimerControlScripts();
            $enableTimer = "enableTimer: true,\n";
        }
		
	
		if(($enable_cookies)&&($displayas == 3)){	
			
			$lighboxjs = "//Lightbox and Cookies
						  //ToDO: utilizza cookie fino alla chiusure del browser
							 var getck = getCookie('tourlightbox');
							 if ( getck !=1){
								gQuery('#example-popup').addClass('visible');\n
							 } else { clearPopup();}";
				
		} elseif ($displayas == 3)  {
		 	
		 	$lighboxjs = "setCookie('tourlightbox', 0, 365);
					  	  gQuery('#example-popup').addClass('visible');\n "; 
		} else {$lighboxjs = "";} 
        
        
		
        $totalSteps = count($items);
		$stepsscript = "	
		var redirectCookie = getCookie('redirect_pending');\n
        var websiteTourTimer=false;\n
        var websiteTourTime=0;\n
		//For Cookies
		var expck = $expire_cookies;\n
						
        var totalSteps = $totalSteps;\n
        gQuery(document).ready(function(){\n
		

		//inizio popup
         gQuery('html').addClass('overlay');\n
		
		//LightBox and Cookies 
		gQuery('.popup-overlay').css({'background':'$overbck', 'opacity':'$overopacity','z-index':'999' });
		$lighboxjs;
		
		//end
		function clearPopup() {
         gQuery('.popup.visible').addClass('transitioning').removeClass('visible');
         gQuery('html').removeClass('overlay');
		 gQuery('.popup-overlay').css({'background':'', 'opacity':'0','z-index':'999' });
       
	    setTimeout(function () {
             gQuery('.popup').removeClass('transitioning');
        }, 200);
  		}";
		$stepsscript .= $jquery_var."(document).keyup(function (e) {
        if (e.keyCode == 27 &&  gQuery('html').hasClass('overlay')) {
            clearPopup();
        }
   		 });

   		  gQuery('.popup-exit').click(function () {
     	    clearPopup();
			setCookie('tourlightbox', 1, expck);  
    	  });

		 gQuery('.addslidebutton').click(function () {
     	   clearPopup();
		   setCookie('tourlightbox', 1, expck);  
         });

    	 gQuery('.popup-overlay').click(function () {
     	   clearPopup();
		   setCookie('tourlightbox', 1, expck);
   		 });

		  gQuery('.tour-menu ul li a#open-walkthrough').click(function () {
     	   clearPopup();
		   setCookie('tourlightbox', 1, expck);  
   		 });

		//fine popup ";
        
		$stepsscript .= "
		gQuery('#walkthrough').pagewalkthrough({
		steps:
        [
		";
		for ($i = 0; $i < $totalSteps; ++$i) {
			$item = $items[$i];
			//print_r($item->stepcontent);
			//echo $items->stepcontent;
			$stepsscript .="{";          
			if ($item->tourtype=='modal'){
				$stepsscript .=" wrapper: '', ";
			} else {
			    if($item->wrappertype == 'id')
                {
                    $stepsscript .=" wrapper: '#$item->wrapper', ";
                }
                else if($item->wrappertype == 'class')
                {
                    $stepsscript .=" wrapper: '."."$item->wrapper', ";
                }
				else if($item->wrappertype == 'name')
                {
                    $stepsscript .=" wrapper: '[name=".$item->wrapper."]', ";
                }
			}
			$stepsscript .=" margin: '0', popup: { content: '#$item->stepcontent', type: '$item->tourtype', ";
			if ($item->tourtype=='modal'){
				$stepsscript .=" position:'', ";
			} else {
				$stepsscript .=" position:'$item->tourpos', ";
			}
			$stepsscript .=" offsetHorizontal: 0, offsetVertical: 0, width: '$item->stepwidth', draggable: $item->stepdrag, contentRotation: $item->rotation }, ";
			
            $comma = '';
            if((($i+1)<count($items)))
            {
                $comma = ',';
            }
            if($item->redirect_to!="")
            {
                $stepsscript .=" redirect_to:'$item->redirect_to', ";
            }
            if($item->time!="" && is_numeric($item->time) && $item->time > 0)
            {
                $stepsscript .=" hault_time:".(int)$item->time.", ";
            }
            $stepsscript .=" overlay: true }  ".$comma;

		} // end for

		 $stepsscript .="  

        ],
        $enableKeyboard
        $enableTimer
        $remember_last_step        
        name: 'Walkthrough',
        onLoad: $onload,
        onClose: function(){
            gQuery('.tour-menu ul li a#open-walkthrough').removeClass('active');
			setCookie('tourlightbox', 1, expck);  
            return true;
        },
        onCookieLoad: function(){
           //console.log('This callback executed when onLoad cookie is FALSE');	
		return null;
					}
	});


// START THE TOUR
	gQuery('.tour-menu a').each(function(){
      gQuery('.tour-menu').find('a.active').removeClass('active');
      gQuery(this).click(function(){
          gQuery(this).addClass('active');
              var id = gQuery(this).attr('id').split('-');
              if(id == 'parameters') return;
              gQuery.pagewalkthrough('show', id[1]); 
      });
});
  gQuery( \"body\" ).on( \"click\", \".prev-step\", function(e) {
    
    
    if(websiteTourTimer)
    {
        gQuery('#time_progress','#tooltipInner').chrony('set', { destroy: true });
        websiteTourTimer = false;
    }    
	gQuery.pagewalkthrough('prev',e);
  });
  
  gQuery( \"body\" ).on( \"click\", \".next-step\", function(e) {
    
    if(websiteTourTimer)
    {
        gQuery('#time_progress','#tooltipInner').chrony('set', { destroy: true });
        websiteTourTimer = false;
    }   
	gQuery.pagewalkthrough('next',e);
  });
  
  gQuery( \"body\" ).on( \"click\", \".restart-step\", function(e) {
	gQuery.pagewalkthrough('restart',e);
  });
  
  gQuery( \"body\" ).on( \"click\", \".close-step\", function(e) {
	gQuery.pagewalkthrough('close');
  });
  $timerControlJS
  $autoStartJs
  $redirectPendingJS
}); 

";
return $stepsscript;
	}
    
    public static function getTimerControlScripts()
    {
        $js = "
  gQuery( \"body\" ).on( \"click\", \"#time-prev\", function(e) {
	var walkthroughIndex = gQuery.pagewalkthrough('currIndex');    
    e.preventDefault();
    if(walkthroughIndex > 0)
    {
        gQuery('a.prev-step','#tooltipInner').click();
    }
  });
  gQuery( \"body\" ).on( \"click\", \"#time-next\", function(e) {
	var walkthroughIndex = gQuery.pagewalkthrough('currIndex');
    e.preventDefault();
    if((walkthroughIndex+1) < totalSteps)
    {        
        gQuery('a.next-step','#tooltipInner').click();
    }
  });
  gQuery( \"body\" ).on( \"click\", \"#time-stop\", function(e) {
	var walkthroughIndex = gQuery.pagewalkthrough('currIndex');
    e.preventDefault();
    if(websiteTourTimer)
    {
        gQuery('#time_progress','#tooltipInner').chrony('set', { destroy: true });
    }
    gQuery.pagewalkthrough('close');
  });
  gQuery( \"body\" ).on( \"click\", \"#time-pause\", function(e) {
    e.preventDefault();
    if(websiteTourTimer)
    {
        gQuery('#time_progress','#tooltipInner').chrony('set', { paused: true });
    }
  });
  gQuery( \"body\" ).on( \"click\", \"#time-play\", function(e) {
    e.preventDefault();
    if(websiteTourTimer)
    {
        gQuery('#time_progress','#tooltipInner').chrony('set', { paused: false });
    }
  });";
        return $js;        
    }
}