
    jQuery(document).ready(function(){

        if(!jQuery('button').hasClass('closepopup'))
        {   
            loadpreloader();
        }
       
    });
    
    function loadpreloader ()
    {   

        jQuery('.preloader-wrap').css('display','');
        var width = 100,
        perfData = window.performance.timing, // The PerformanceTiming interface represents timing-related performance information for the given page.

        EstimatedTime = -(perfData.loadEventEnd - perfData.navigationStart),
        time = parseInt((EstimatedTime/1000)%60)*100;
       
        if (Math.sign(time) === -1)
        {
            time = Math. abs(time);
            time = time+500;
        }

        // Loadbar Animation
        jQuery(".loadbar").animate({
          width: width + "%"
        }, time);

        // Loadbar Glow Animation
        jQuery(".glow").animate({
          width: width + "%"
        }, time);

        // Percentage Increment Animation
        var PercentageID = jQuery(".percentage"),
            start = 0,
            end = 100,
            durataion = time;
    
        animateValue(PercentageID, start, end, durataion);  
       
        // Fading Out Loadbar on Finised
        jQuery( document ).ajaxStop(function() {
            
         // jQuery('#getdata').text('Completed');
         jQuery('.preloader-wrap').fadeOut(200);
         setTimeout(function(){
           jQuery('.loadbar').css('width','');
           jQuery('.glow').css('width','');
         },200)
    });
}

        function loadpreloaderformore ()
        {   
        jQuery('.preloader-wrap-loadmore').css('display','');
        var width = 100,
        perfData = window.performance.timing, // The PerformanceTiming interface represents timing-related performance information for the given page.

        EstimatedTime = -(perfData.loadEventEnd - perfData.navigationStart),
        time = parseInt((EstimatedTime/1000)%60)*100;

         if (Math.sign(time) === -1)
        {
            time = Math. abs(time);
            time = time + 700;
        }

        // Loadbar Animation
        jQuery(".loadbar-loadmore").animate({
          width: width + "%"
        }, time);

        // Loadbar Glow Animation
        jQuery(".glow-loadmore").animate({
          width: width + "%"
        }, time);

        // Percentage Increment Animation
        var PercentageID = jQuery(".percentage-loadmore"),
            start = 0,
            end = 100,
            durataion = time;
    
        animateValue(PercentageID, start, end, durataion);  
       
        // Fading Out Loadbar on Finised
        jQuery( document ).ajaxStop(function() {
            
         // jQuery('#getdata').text('Completed');
         jQuery('.preloader-wrap-loadmore').fadeOut(300);
          setTimeout(function(){
           jQuery('.loadbar-loadmore').css('width','');
           jQuery('.glow-loadmore').css('width','');
         },300)

         });
    }

    function animateValue(id, start, end, duration) {
      
        var range = end - start,
          current = start,
          increment = end > start? 1 : -1,
          stepTime = Math.abs(Math.floor(duration / range)),
          obj = jQuery(id);
       
        var timer = setInterval(function() {
            current += increment;

            jQuery(obj).text(current + "%");
            // jQuery(obj).text(current + "%");

            if (current == end) {

                clearInterval(timer);
            }
        }, stepTime);
    }
