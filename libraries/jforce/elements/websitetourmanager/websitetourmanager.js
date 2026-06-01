/**
 * @copyright	Copyright (C) 2014 JoomlaForceTeam. All rights reserved.
 * @license		GNU General Public License version 3 or later; see LICENSE.txt
 */

 
//wrapperType added by sam 
function addstepstour(wrapperType,wrapper, stepcontent, tourtype, tourpos, steptitle, steptext, stepwidth, draggable, rotation, redirect_to, time_field) {
	
	
   
  	if (!rotation) rotation = '0';
	if (!stepwidth) stepwidth = '200';
    if (!wrapper) wrapper = '';
    if (!stepcontent) stepcontent = 'jfstep';
    if(!redirect_to) redirect_to = '';
    stepcontent = stepcontent.replace(/\|dq\|/g,"&quot;");
    
    if (!steptitle) steptitle = '';
    if (!steptext) steptext = '';
    
    //added by sam 
    var wrapperTypeOptionHTML = '';
    var wrapperTypeOptions = [{'key':'id','value':Joomla.JText._('ID', 'ID')},
                                {'key':'class','value':Joomla.JText._('Class', 'Class')},
                                {'key':'name','value':Joomla.JText._('Name', 'Name')}];
    
    for(var wrapperTypeOptionIndex=0;wrapperTypeOptionIndex<wrapperTypeOptions.length;wrapperTypeOptionIndex++)
    {
        selectedOption = (wrapperTypeOptions[wrapperTypeOptionIndex].key == wrapperType)?' selected="selected"':"";
        wrapperTypeOptionHTML += '<option value="'+wrapperTypeOptions[wrapperTypeOptionIndex].key+'"'+selectedOption+'>'+wrapperTypeOptions[wrapperTypeOptionIndex].value+'</option>';
    }
    
    //added by sam
    
    //type of tour tooltip, modal, nohighlight
    if (!tourtype) {
        tourtype = '';
        tourtypeoption = '<option value="modal" selected="selected">'+Joomla.JText._('Modal', 'Modal')+'</option>'
        +'<option value="tooltip">'+Joomla.JText._('Tooltip', 'Tooltip')+'</option>'
        +'<option value="nohighlight">'+Joomla.JText._('No Highlight', 'No Highlight')+'</option>';
        
    } else {
        if (tourtype == 'modal') {
            tourtypeoption = '<option value="modal" selected="selected">'+Joomla.JText._('Modal', 'Modal')+'</option>'
            +'<option value="tooltip">'+Joomla.JText._('Tooltip', 'Tooltip')+'</option>'
            +'<option value="nohighlight">'+Joomla.JText._('No Highlight', 'No Highlight')+'</option>';
        } else if (tourtype == 'tooltip') {
            tourtypeoption = '<option value="modal">'+Joomla.JText._('Modal', 'Modal')+'</option>'
            +'<option value="tooltip" selected="selected">'+Joomla.JText._('Tooltip', 'Tooltip')+'</option>'
            +'<option value="nohighlight">'+Joomla.JText._('No Highlight', 'No Highlight')+'</option>';
        } else {
            tourtypeoption = '<option value="modal" selected="selected">'+Joomla.JText._('Modal', 'Modal')+'</option>'
            +'<option value="tooltip" selected="selected">'+Joomla.JText._('Tooltip', 'Tooltip')+'</option>'
            +'<option value="nohighlight" selected="selected">'+Joomla.JText._('No Highlight', 'No Highlight')+'</option>'
        }
    }
	
	
    //position for tooltip and nohighlight type only: top, right, bottom, left
	if (!tourpos) {
        tourpos = '';
        tourposoption = '<option value="top" selected="selected">'+Joomla.JText._('Top', 'Top')+'</option>'
        +'<option value="bottom">'+Joomla.JText._('Bottom', 'Bottom')+'</option>'
		+'<option value="right">'+Joomla.JText._('Right', 'Right')+'</option>'
        +'<option value="left">'+Joomla.JText._('Left', 'Left')+'</option>';
     
    } else {
         if (tourpos == 'right') {
            tourposoption = '<option value="top">'+Joomla.JText._('Top', 'Top')+'</option>'
			+'<option value="bottom">'+Joomla.JText._('Bottom', 'Bottom')+'</option>'
            +'<option value="right" selected="selected">'+Joomla.JText._('Right', 'Right')+'</option>'
            +'<option value="left">'+Joomla.JText._('Left', 'Left')+'</option>';
        } else if (tourpos == 'bottom') {
            tourposoption = '<option value="top">'+Joomla.JText._('Top', 'Top')+'</option>'
			+'<option value="bottom" selected="selected">'+Joomla.JText._('Bottom', 'Bottom')+'</option>'
            +'<option value="right">'+Joomla.JText._('Right', 'Right')+'</option>'
            +'<option value="left">'+Joomla.JText._('Left', 'Left')+'</option>';

        } else if (tourpos == 'left') {
            tourposoption = '<option value="top">'+Joomla.JText._('Top', 'Top')+'</option>'
			+'<option value="bottom">'+Joomla.JText._('Bottom', 'Bottom')+'</option>'
            +'<option value="right">'+Joomla.JText._('Right', 'Right')+'</option>'
            +'<option value="left" selected="selected">'+Joomla.JText._('Left', 'Left')+'</option>';
          
        } else {
            tourposoption = '<option value="top">'+Joomla.JText._('Top', 'Top')+'</option>'
		    +'<option value="bottom">'+Joomla.JText._('Bottom', 'Bottom')+'</option>'
            +'<option value="right">'+Joomla.JText._('Right', 'Right')+'</option>'
            +'<option value="left">'+Joomla.JText._('Left', 'Left')+'</option>';
          
        }
    }
	
	if (!draggable) {
        draggable = '';
        draggableoption = '<option value="false" selected="selected">'+Joomla.JText._('No', 'No')+'</option>'
        +'<option value="true">'+Joomla.JText._('Yes', 'Yes')+'</option>';
	} else {
		if (draggable == 'false') {
            draggableoption = '<option value="true">'+Joomla.JText._('Yes', 'Yes')+'</option>'
			+'<option value="false" selected="selected">'+Joomla.JText._('No', 'No')+'</option>';
		} else {
		 	draggableoption = '<option value="true" selected="selected">'+Joomla.JText._('Yes', 'Yes')+'</option>'
			+'<option value="false">'+Joomla.JText._('No', 'No')+'</option>';
		}
	}

    index = checkIndex(0);
    var textareaStyle = "";
	if(jversion3)//make the width 100%
    {
        var jfsteplist = new Element('li', {
            'class': 'jfsteplist',
            'id': 'jfsteplist'+index,
            'style':'max-width:100%;'
            });
        textareaStyle = 'style="width:745px !important;height:200px;"';
    }
    else
    {
        var jfsteplist = new Element('li', {
            'class': 'jfsteplist',
            'id': 'jfsteplist'+index
            });
    }
    
  
    jfsteplist.set('html',
        '<div class="jfsteptop">'
		+'<div class="jfstephandle"></div>' // end jfstephandle
		+'<div class="jfstepnumber">'+index+' - '+steptitle+'</div>'
		
			+ '<div class="jfstepcontainer">'
       + '<a class="jfstepedit" href=""></a><input name="jfstepdelete'+index+'" class="jfstepdelete" type="button" value="" onclick="javascript:removestep(this.getParent().getParent().getParent());" />'
        + '</div>'
		
	 	+'</div>' //end step top
		
		+'<div class="jfaccordion" id="jfaccordion'+index+'" name="jfaccordion'+index+'">'
		
	
	    + '<div class="jfsteprow">'
        + '<span class="jfsteplabel">'+Joomla.JText._('MOD_WEBSITETOUR_STEP_TYPE','Type')+'</span>'
        + '<select name="jftourtype'+index+'" class="jftourtype">'+tourtypeoption+'</select>'
        + '</div>'
		
        + '<div class="jfsteprow">'
        + '<span class="jfsteplabel">'+Joomla.JText._('MOD_WEBSITETOUR_STEP_WRAPPERTYPE','Wrapper Type')+'</span>'
        + '<select name="jfstepwrappertype'+index+'" class="jfstepwrappertype">'+wrapperTypeOptionHTML+'</select>'
        + '</div>'
        
		+ '<div class="jfsteprow">'
        + '<span class="jfsteplabel">'+Joomla.JText._('MOD_WEBSITETOUR_STEP_WRAPPERID','Wrapper ID')+'</span>'
        + '<input name="jfstepwrapper'+index+'" id="jfstepwrapper'+index+'" class="jfstepwrapper hasTip" title="Insert an ID of page element HTML that you want to highlight. Leave Blank if type is modal" type="text" value="'+wrapper+'" />'
        + '</div>'
        
        + '<div class="jfsteprow">'
        + '<span class="jfsteplabel">'+Joomla.JText._('MOD_WEBSITETOUR_STEP_POSITION','Position')+'</span>'
        + '<select name="jftourpos'+index+'" class="jftourpos">'+tourposoption+'</select>'
        + '</div>'
		
		+ '<div class="jfsteprow">'
        + '<span class="jfsteplabel">'+Joomla.JText._('MOD_WEBSITETOUR_STEP_WIDTH', 'Width')+'</span>'
        + '<input name="jfstepwidth'+index+'" id="jfstepwidth'+index+'" class="jfstepwidth hasTip" title="Insert width of popup (only number)" type="text" value="'+stepwidth+'" />'
        + '</div>'
		
		+ '<div class="jfsteprow">'
        + '<span class="jfsteplabel">'+Joomla.JText._('MOD_WEBSITETOUR_STEP_DRAGGABLE', 'Draggable')+'</span>'
        + '<select name="jfstepdrag'+index+'" class="jfstepdrag">'+draggableoption+'</select>'
        + '</div>'
		
		
		+ '<div class="jfsteprow">'
        + '<span class="jfsteplabel hasTooltip" title="'+Joomla.JText._('MOD_WEBSITETOUR_STEP_ROTATION_DESC', 'Rotation')+'">'+Joomla.JText._('MOD_WEBSITETOUR_STEP_ROTATION', 'Rotation')+'</span>'
        + '<input name="jfstepgrade'+index+'" id="jfstepgrade'+index+'" class="jfstepgrade" type="text" value="'+rotation+'" />'
        + '</div>'
       /* + '<div class="jfsteprow">'
        + '<span class="jfsteplabel">'+Joomla.JText._('Content', 'Content')+'</span>'
        + '<input name="jfstepcontent'+index+'" class="jfstepcontent" type="text" value="'+stepcontent+'" onchange="javascript:storesetwarning();" />'
         + '</div>'*/
        //added by sam 
        + '<div class="jfsteprow">'
        + '<span class="jfsteplabel">'+Joomla.JText._('MOD_WEBSITETOUR_STEP_REDIRECT', 'Redirect to')+'</span>'
        + '<input name="jfstepredirect_to'+index+'" id="jfstepredirect_to'+index+'" class="jfstepredirect_to hasTip url" title="Insert redirect url if needed" type="text" value="'+redirect_to+'" />'
        + '</div>'
        
       + '<div class="jfsteprow">'
        + '<span class="jfsteplabel">'+Joomla.JText._('MOD_WEBSITETOUR_STEP_TIME', 'Time')+'</span>'
        + '<input name="jfsteptime'+index+'" id="jfsteptime'+index+'" class="jfsteptime hasTip url" title="Insert time in secodns" type="text" value="'+time_field+'" />'
        + '</div>'
        //added by sam end
         
		+'<input name="jfstepcontent'+index+'" class="jfstepcontent" type="hidden" value="'+stepcontent+index+'"  /> '
		 
        + '<hr>'
        + '<div class="jfsteprow">'
        + '<span class="jfsteplabel">'+Joomla.JText._('MOD_WEBSITETOUR_STEP_TITLE', 'Step Title')+'</span>'
        + '<input name="jfsteptitle'+index+'" id="jfsteptitle'+index+'" class="jfsteptitle hasTip" title="test titolo" type="text"  value="'+steptitle+'" />'
        + '</div>'
        + '<hr>'
        + '<div class="jfsteprow">'
        + '<span class="jfsteplabel">'+Joomla.JText._('MOD_WEBSITETOUR_STEP_TEXT', 'Step Text')+'</span>'
        + '<textarea name="jfsteptext'+index+'" id="jfsteptext'+index+'" class="jfsteptext" '+textareaStyle+'>'+steptext+'</textarea>'
	    + '</div>'
        + '<br><br>'
        + '</div>'
        
        +'</div>' //end accordion
        
    
        + '</div><div style="clear:both;"></div>');
		
	
	
    document.id('jfsteplist').adopt(jfsteplist);

    storesteptour();
    makesortables();
    jQuery("#jfsteptext"+index).wysihtml5({ });
	
	
	
	    
}


function checkIndex(i) {
    while ($('jfsteplist'+i)) i++;
    return i;
	
}


function removestep(slide) {
    if (confirm(Joomla.JText._('MOD_WEBSITETOUR_REMOVE')+' ?')) {
       slide.destroy();
       storesteptour();

    }
}

function storesetwarning() {
// $('idname').setStyle('background-color', 'red');

}

function storeremovewarning() {
// $('idname').setStyle('background-color', 'white');

}

function storesteptour() {
	
    var i = 0;
    var slides = new Array();
	    
    document.id('jfsteplist').getElements('.jfsteplist').each(function(el) {
        slide = new Object();
        slide['wrapper'] = el.getElement('.jfstepwrapper').value;
        slide['wrappertype'] = el.getElement('.jfstepwrappertype').value;
        slide['stepcontent'] = el.getElement('.jfstepcontent').value;
        slide['stepcontent'] = slide['stepcontent'].replace(/"/g,"|dq|");
        slide['tourtype'] = el.getElement('.jftourtype').value;
        slide['tourpos'] = el.getElement('.jftourpos').value;
        slide['steptitle'] = el.getElement('.jfsteptitle').value;
        slide['steptext'] = el.getElement('.jfsteptext').value;
		slide['stepwidth'] = el.getElement('.jfstepwidth').value;
		slide['stepdrag'] = el.getElement('.jfstepdrag').value;
		slide['rotation'] = el.getElement('.jfstepgrade').value;
        slide['redirect_to'] = el.getElement('.jfstepredirect_to').value;
        slide['time'] = el.getElement('.jfsteptime').value;
	    //slide['steptext'] = el.getElementById('jfsteptext').value;
        slides[i] = slide;
        i++;
		
		 
    });
	
	
	
    slides = JSON.encode(slides);
    slides = slides.replace(/"/g,"|qq|");
    document.id('jftoursstep').value = slides;
    storeremovewarning();
	

}

function callsteps() {
    
    var slides = JSON.decode(document.id('jftoursstep').value.replace(/\|qq\|/g,"\""));
    if (slides) {
		
		
        slides.each(function(slide) {		
            addstepstour(slide['wrappertype'],//added by sam
                slide['wrapper'],
                slide['stepcontent'],
                slide['tourtype'],
                slide['tourpos'],
                slide['steptitle'],
                slide['steptext'],
				slide['stepwidth'],
				slide['stepdrag'],
				slide['rotation'], 
                slide['redirect_to'],
                slide['time']
                );
							
        });
           
        storeremovewarning();
    }
}

function makesortables() {
	var sb = new Sortables('jfsteplist', {
        /* set options */
        clone:true,
        revert: true,
        handle:'.jfstephandle',
        /* initialization stuff here */
        initialize: function() {
      
        },
        /* once an item is selected */
        onStart: function(el) {
            el.setStyle('background','#add8e6');
        },
        /* when a drag is complete */
        onComplete: function(el) {
            el.setStyle('background','#fff');
            storesetwarning();
        },
        onSort: function(el, clone) {

        }
    });
}
    
function makecollapsed() {
	
jQuery(".jfaccordion").hide();
		jQuery('.jfstepedit').click(function(e){
    	e.preventDefault();
		var pos=jQuery(e.target).index(".jfstepedit");
		jQuery('#jfaccordion'+pos).toggle();
 		//alert (pos);
 		});
   
}

    
window.addEvent('domready', function() {
    callsteps();  
	makecollapsed();
	
    var script = document.createElement("script");
    script.setAttribute('type','text/javascript');
    script.text="Joomla.submitbutton = function(task){"
    +"storesteptour();"
    +"if (task == 'module.cancel' || document.formvalidator.isValid(document.id('module-form'))) {    Joomla.submitform(task, document.getElementById('module-form'));"
    +"if (self != top) {"
    +"window.top.setTimeout('window.parent.SqueezeBox.close()', 1000);"
    +"}"
    +"} else {"
    +"alert('Formulaire invalide');"
    +"}}";
    document.body.appendChild(script);
	    
	
});

