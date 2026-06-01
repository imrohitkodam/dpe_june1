function opentab(data,tabId)
{

  var anchorTags = document.querySelector('a[href*="' + tabId + '"]');
  var liElement = $(anchorTags).closest('li');
  liElement.addClass('active');

  var activeDiv = document.querySelector('.sppb-fade.active.in');

  if (activeDiv) {
    var activeDivId = activeDiv.id; 
    var liWithIdContainingId = document.querySelector('a[href*="' + activeDivId + '"]');
    var liElement = $(liWithIdContainingId).closest('li');    
    liElement.removeClass("active");  
}

var parentDiv = document.querySelector('.sppb-tab-content');

if (parentDiv) {
                // Find the child div with the specified ID within the parent
                var activeChildDiv = parentDiv.querySelector('#' + activeDivId);
                var activeChildOpenDiv = parentDiv.querySelector('#' + tabId);

                if (activeChildDiv) {
                    activeChildDiv.classList.remove('active', 'in');
                    activeChildOpenDiv.classList.add('active', 'in');
                }
            }
        }


        jQuery(document).ready(function () {

            if(document.querySelector('.sppb-tab-content'))
            {

                                var url = window.location.href;
            var urlObject = new URL(url);
            var fragment = urlObject.hash; 
            var checklist = fragment.slice(1);

            if (checklist.length>0)
            {
                checklist = checklist.replace(/%20/g, " ");
                checklist = checklist.replace(/-/g, " ");


      // Find all anchor tags with data-toggle attribute containing "sppb-tab"
      var anchorTags = jQuery('a[data-toggle*="sppb-tab"]');

      // Loop through the anchor tags
      anchorTags.each(function () {
          var anchorTag = jQuery(this);


    // Check if the anchor tag's text content is "Checklist"
    if (anchorTag.text().trim().toLowerCase() === checklist.toLowerCase()) {

       checklistHref = anchorTag.attr("href").replace(/^#/, '');

       var anchorTags = document.querySelector('a[href*="' + checklistHref + '"]');

       var liElement = jQuery(anchorTags).closest('li');
       liElement.addClass('active');

       var activeDiv = document.querySelector('.sppb-fade.active.in');

       if (activeDiv) {
        var activeDivId = activeDiv.id; 
        var liWithIdContainingId = document.querySelector('a[href*="' + activeDivId + '"]');
        var liElement =jQuery(liWithIdContainingId).closest('li');    
        liElement.removeClass("active");  
    }

    var parentDiv = document.querySelector('.sppb-tab-content');

    if (parentDiv) {
                // Find the child div with the specified ID within the parent
                var activeChildDiv = parentDiv.querySelector('#' + activeDivId);
                var activeChildOpenDiv = parentDiv.querySelector('#' + checklistHref);

                if (activeChildDiv) {
                    activeChildDiv.classList.remove('active', 'in');
                    activeChildOpenDiv.classList.add('active', 'in');
                }
            }

        }
    });
  }

            }


  
});
