        jQuery('document').ready(function(){
        /** global: plugdataObject */
        var play    = jQuery('#myVideo'),
            cincopa = {  
                wHeight           : jQuery(window.parent).height() - 50,
                wWidth            : jQuery(window.parent).width() - 50,
                videoResumed      : false,
                resetTime         : false,
                videoLength       : 0,
                lessonStartTime   : 0,
                lessonStoptime    : 0,
                getCurrentTime    : '', 
                current_position  : '' ,
                       
             player: function(){
             play.attr('width', cincopa.wWidth).attr('height', cincopa.wHeight);
             play.on({  
                play:function()  
                {
                 cincopa.lessonStartTime = new Date();
                 cincopa.resetTime       = false;
                    if (!cincopa.videoResumed) 
                    {   
                        /** global: plugdataObject */
                        cincopa.setCurrentTime(plugdataObject.seekTo);
                        cincopa.videoResumed = true;
                        cincopa.getDuration(play[0].duration);
                    }
                },
                pause: function() 
                {   cincopa.resetTime = true;
                    cincopa.sendTrackingData();
                },
                ended: function() 
                {   cincopa.resetTime = true;
                    cincopa.sendTrackingData();
                }
            });
        },     
        sendTrackingData : function ()
        {
            cincopa.current_position = cincopa.getCurrent_pos();

            if (cincopa.lessonStartTime) 
            {
                cincopa.lessonStoptime          = new Date();
                cincopa.timeinseconds           = Math.round((cincopa.lessonStoptime - cincopa.lessonStartTime) / 1000);
                cincopa.current_position        = Math.round(cincopa.current_position);
                cincopa.total_content           = Math.round(cincopa.videoLength);
                plugdataObject.current_position = cincopa.current_position;
                plugdataObject.total_content    = cincopa.total_content;
                plugdataObject.lesson_status    = (cincopa.current_position >= cincopa.total_content) ? "completed" : "incomplete";
                plugdataObject.time_spent       = cincopa.timeinseconds > 10 ? 10 : cincopa.timeinseconds;
                cincopa.lessonStartTime         = cincopa.resetTime ? 0 : new Date();
                updateData(plugdataObject);
            }
        },
        getCurrent_pos : function () 
        {
            return play[0].currentTime;
        },

        setCurrentTime : function (time) 
        {
            play[0].currentTime = time;
        },

        getDuration : function (duration) 
        {
            
            cincopa.videoLength = duration;
            setInterval(function() 
            {
               cincopa.sendTrackingData();
            }, 10000);
        },    
     }
        cincopa.player();
        jQuery.fn.onBeforeUnloadLessonPageUnload = cincopa.sendTrackingData;

                
    });
 
 
//used in search  gallery 
jQuery(function() 
{

            window.getHtml = function (param)
            {
                var lesson_id   = jQuery("#lesson").val(),
                    formId      = jQuery("#formId").val(),
                    rootUrl     = jQuery("#rooturl").val(),
                    loadMoreUrl = jQuery("#loadmore").val(),
                    tfoot       = jQuery("tfoot"),
                    tbody       = jQuery("tbody"),
                    button      = jQuery("button"),
                    input       =  jQuery("input");

                if (param == "search")
                {
                    tbody.hide();
                    tfoot.show();
                    button.prop("disabled","disabled");
                    input.prop("disabled","disabled");
                    var filter_search = jQuery("#filter_search").val();

                    jQuery.ajax({
                      url: rootUrl + loadMoreUrl + lesson_id + '&form_id=' + formId + '&type=ajax&qtext=' + filter_search,
                      type: "post",
                      datatype : "json",
                      
                    success:function(data)
                    {
                      tfoot.hide();
                      input.removeAttr("disabled");
                      button.removeAttr("disabled");
                      jQuery("#appendHtml"+lesson_id).html(data);
                      jQuery("#filter_search").val(filter_search);
                    },
                    error : function(data)
                    {
                      tfoot.hide();
                      input.removeAttr("disabled");
                      button.removeAttr("disabled");
                      jQuery("#modal-header").hide();
                            }
                        });
                }
                else if (param == "clear")
                {
                    tbody.hide();
                    tfoot.show();
                    button.prop("disabled","disabled");
                    input.prop("disabled","disabled");
                    jQuery.ajax({
                      url: rootUrl + loadMoreUrl + lesson_id + '&form_id=' + formId + '&type=ajax&qtext=',
                            type: "post",
                            datatype : "json",
                            success:function(data)
                            {
                                tfoot.hide();
                                input.removeAttr("disabled");
                                button.removeAttr("disabled");
                                jQuery("#appendHtml"+lesson_id).html(data);
                            },
                            error : function(data)
                            {
                                tfoot.hide();
                                input.removeAttr("disabled");
                                button.removeAttr("disabled");
                            }
                        });
                }
                else
                {   
                    var endlimit='';
                    tbody.hide();
                    tfoot.show();
                    button.prop("disabled","disabled");
                    input.prop("disabled","disabled");
                    var limit = jQuery("#list_limit").val();
                        endlimit = parseInt(limit) + 1;
                    jQuery.ajax({
                    url: rootUrl + loadMoreUrl + lesson_id + '&form_id=' + formId + '&qtext=&first=' + limit + "&max=" + endlimit + "&type=ajax",
                            type: "post",
                            datatype : "json",
                            success:function(data)
                            {
                                tfoot.hide();
                                input.removeAttr("disabled");
                                button.removeAttr("disabled");
                                jQuery("#appendHtml"+lesson_id).html(data);
                            },
                            error : function(data)
                            {
                                tfoot.hide();
                                input.removeAttr("disabled");
                                button.removeAttr("disabled");
                            }
                        });
                     }
               }
       
    });
              
//used for select the video and bind the video to the lesson

    var thisbtnData          = '',
        openVideoCreater     = '',
        receiveMessage       = '',
        rootUrl              = '',
        format_formx         = '' ,
        format_form_idx      = '',
        gccId                = '',
        bindCincopa          = '',
        openListVideos_all   = '',
        validatevideocincopa = '',
        PublishVideo         = '',
        hitURL               = '',
        openListVideoss      = '' ,
        publishLesson = '',
        url1                 = jQuery("#url1").val(),
        url2                 = jQuery("#url2").val()
        window.wWidth        = jQuery(window).width()-400,
        window.wHeight       = jQuery(window).height()-100;

     openVideoCreater = function (thisbtn)
     {
          format_formx        =   jQuery(thisbtn).closest('.lesson-format-form');
          format_form_idx     =   jQuery(format_formx).attr('id');
          window.thisbtnData  =   format_form_idx.replace('lesson-format-form_','');
     }

    receiveMessage = function (e) 
    {
           rootUrl = jQuery("#rooturl").val();
        // Update the div element to display the message.
           gccId = JSON.parse (e.data);
        jQuery("#lesson-format-form_" + window.thisbtnData +" .cincopa_video").val(gccId['id']);
        jQuery.ajax({
            url: rootUrl + url1 + gccId['id'],
            success: function(response)
            {
                var arr = JSON.parse(response);
                jQuery("#lesson-format-form_" + window.thisbtnData +" .cincopa_text").text(arr['displayname']);
                jQuery("#lesson-format-form_" + window.thisbtnData +" .cincopa_href").attr("src", arr['thumbnail_url']);
            }
        });

        jQuery.ajax({
            url:  rootUrl + url2,
            success: function(response)
            {
                jQuery("#lesson-basic-form_" + window.thisbtnData  +" label[for=jform_state0"+window.thisbtnData+"]").removeClass('active btn-success');
                jQuery("#lesson-basic-form_" + window.thisbtnData  +" label[for=jform_state1"+window.thisbtnData+"]").addClass('active btn-danger');
                jQuery("#lesson-basic-form_" + window.thisbtnData  +" input[id=jform_state0"+window.thisbtnData+"]").removeAttr('checked');
                jQuery("#lesson-basic-form_" + window.thisbtnData  +" input[id=jform_state1"+window.thisbtnData+"]").attr('checked', 'checked');
            }
        });
    }

   // Setup an event listener that calls receiveMessage() when the window
   // receives a new MessageEvent.
   window.addEventListener("message", receiveMessage);

   bindCincopa = function (form_id,ids,displayname,href,des, ownername)
   {
        if (VarsameLessonName === 1)
        {
            jQuery("#lesson-basic-form_" + form_id  +" input[id=jform_name]").val(displayname);
        }
        jQuery("#lesson-basic-form_"  + form_id  +" textarea[id=jform_description]").val(des);
        jQuery("#lesson-format-form_" + form_id +" .cincopa_video").val(ids);
        jQuery("#lesson-format-form_" + form_id +" .cincopa_text").text(displayname);
        jQuery("#lesson-format-form_" + form_id +" .cincopa_ownername").html('<i class="icon-user pull-left"></i>').append(ownername);
        jQuery("#lesson-format-form_" + form_id +" .cincopa_href").attr("src", href);
        jQuery("#lesson-format-form_" + form_id +" #publish_button").addClass('hide');
        jQuery("#lesson-format-form_" + form_id +" #publish_message").addClass('hide');
        jQuery("#lesson-format-form_" + form_id +" .download-btn").removeClass('hide');
        window.parent.SqueezeBox.close();
    }

    openListVideos_all = function (thisbtn,url)
    {
        
        SqueezeBox.open(url, {
            size: {x: window.wWidth, y: window.wHeight},
            sizeLoading: { x: window.wWidth, y: window.wHeight },
            classWindow: 'tjlms-modal',
            classOverlay: 'tjlms_lesson_screen_overlay',
            onClose: function() {
            }
        });
    }

    /* Function to load the loading image. */
    validatevideocincopa = function (formid,format,subFormat,media_id)
    {
        var res = {check: 1, message: ""};
        var format_lesson_form = jQuery("#lesson-format-form_"+ formid);
        if(media_id === 0)
        {
            if (!jQuery("#lesson_format #" + format + " [name='lesson_format[cincopa][url]']",format_lesson_form).val())
            {
                res.check = '0';
                res.message = "<?php echo Text::_('PLG_TJVIDEO_CINCOPA_URL_MISSING');?>";
            }
        }
        return res;
    }

    /*Function to hit url*/
    hitURL = function (url)
    {
        jQuery.ajax(
        {
            url: url,
            beforeSend: function(){
            },
            success: function(result)
            {
                if (result)
                {
                    window.open(result,'_blank');
                }
            }
        });
    }

    /*Function to publish video*/
    PublishVideo = function (thisbtn, url)
    {
        jQuery.ajax(
        {
            url: url,
            beforeSend: function(){
                jQuery("#"+thisbtn).text('Processing');
            },
            success: function(result)
            {
                if (result === 1)
                {
                    jQuery("#"+thisbtn).hide();
                    jQuery(".download-btn").removeClass('hide');
                    jQuery("#publish_message").text('Video published successfully.');
                    publishLesson();
                }
            }
        });
    }
//fucntion to open video gallery list
    openListVideoss = function (thisbtn,url)
    {
        var format_form    = jQuery(thisbtn).closest('.lesson-format-form'),
            format_form_id = jQuery(format_form).attr('id'),
            form_id        = format_form_id.replace('lesson-format-form_','');
           
        url += "&form_id=" + form_id;
        SqueezeBox.open(url, {
            size: {x: window.wWidth, y: window.wHeight},
            sizeLoading: { x: window.wWidth, y: window.wHeight },
            classWindow: 'tjlms-modal',
            classOverlay: 'tjlms_lesson_screen_overlay',
            onClose: function() {
            }
        });
    }
    /*Function to publish video*/
    publishLesson = function ()
    {
         var lesson_id = jQuery("#lesson").val(),
              rootUrl = jQuery("#rooturl").val();
        jQuery.ajax({
            url:  rootUrl + 'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=cincopa&plgtask=updateLessonState&callType=1&lesson_state=1&lesson_id='+lesson_id,
            success: function(response)
            {
                jQuery("#lesson-basic-form_" + window.thisbtnData  +" input[id=jform_state01]").attr('checked');
                jQuery("#lesson-basic-form_" + window.thisbtnData  +" label[for=jform_state01]").attr('class', 'active btn-success');
                jQuery("#lesson-basic-form_" + window.thisbtnData  +" input[id=jform_state11]").removeAttr('checked', 'checked');
                jQuery("#lesson-basic-form_" + window.thisbtnData  +" label[for=jform_state11]").removeAttr('class', 'active btn-danger');
            }
        });
    }
    

    var VarsameLessonName = 0;
            jQuery('document').ready(function () {
            
                jQuery('#sbox-content').scroll(function() 
                {
                    var lesson_id = jQuery("#lesson").val();
                    var $this   = jQuery(this);
                    var results = jQuery("#appendHtml"+lesson_id);
                    var searchData= jQuery('#filter_search').val();

                    if (typeof searchData !== 'undefined') 
                    {
                        if(searchData.length < 0)
                        {
                            if ($this.scrollTop() + $this.height() == results.height())
                            {
                                loadVideoList();
                            }
                        }
                     }
                    
                });
            
            function sameLessonName()
            {
                if (sameLessonName.checked === true)
                {
                    VarsameLessonName = 1;
                }
                else
                {
                    VarsameLessonName = 0;
                }
            }
            
            var loadVideoList = function ()
            {
                var page      = Number(jQuery('#limit').val()),
                    lesson_id = jQuery("#lesson").val(),
                    formId    = jQuery("#formId").val(),
                    rootUrl   = jQuery("#rooturl").val(),
                    max       = "<?php echo $maxSearch ?>";
                    max       = max > 25 ? 25 : max;
                var pageCount = 1;
                // var limitValue = first + max;
                page = page + pageCount;
                
                if (page > pageCount)
                {
                 jQuery('#load').removeAttr('hidden');
                  var url = rootUrl +
                   'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=cincopa&plgtask=loadMore&callType=1&lesson_id=' 
                   + lesson_id + '&form_id=' + formId;
                    jQuery('#limit').val(page);
                      jQuery.ajax({
                       url: url + '&page=' + page + '&max=' + max,
                       type: "get",
                       complete: function(){
                           jQuery('#load').hide();
                        },
                       success: function(response)
                       {
                         jQuery(".cincopa_video_tr:last").after(response).show().fadeIn("slow");
                       },
                      });
                }
            }
             jQuery("#back").click(function()
             {
                    var url = jQuery("#link1").val();
                    openListVideos_all(this,url);
              });
            });
