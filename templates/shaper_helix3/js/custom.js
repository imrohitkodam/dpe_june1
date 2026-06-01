jQuery(document).ready(function() {

    // jQuery('.sp-megamenu-parent>li' || '.sp-dropdown-sub .sp-dropdown-items>li').hover(function() {
    //     var parentMenuItem = jQuery(this);
    //     var parentMenuName = parentMenuItem.find('a').text();
    //     var parentMenucount = (parentMenuItem.find('a').length) - 1;
    //     var subMenuNames = [];
    //     var subMenuCount = 0;

    //     // var subParentmenuItem =jQuery('.sp-dropdown-main .sp-dropdown-inner .sp-dropdown-items .sp-menu-item.sp-has-child .sp-dropdown-sub .sp-dropdown-inner .sp-dropdown-items').find('li').length;
       

    //     parentMenuItem.find('.sp-has-child > .sp-dropdown-sub > .sp-dropdown-inner > .sp-dropdown-items > .sp-menu-item').each(function() {
    //         var subMenuName = jQuery(this).find('a').text();
    //         subMenuNames.push(subMenuName);
    //         subMenuCount++;
    //     });
    //     //console.log("subParentmenuItem",subParentmenuItem);
    //     console.log("parentMenuName=", parentMenuName);
    //     console.log("parentMenucount=", parentMenucount);

    //     console.log("subMenuName=", subMenuNames.join(","));
    //     console.log("subSubMenuCount=", subMenuCount);
    //     var totalcount = parentMenucount - subMenuCount;
    //     console.log("total=", totalcount);
    //     if (totalcount > 5) {
            
    //         jQuery(".sp-megamenu-parent .sp-dropdown .sp-dropdown-items").css('grid-template-columns', 'repeat(2, 1fr)');
            
    //     } else {
    //         jQuery(".sp-megamenu-parent .sp-dropdown .sp-dropdown-items").css('grid-template-columns', 'repeat(1, 1fr)');
    //     }
    // });
    // Count total ULs
    // var totalULs = document.querySelectorAll('.sp-dropdown.sp-dropdown-main .sp-dropdown-inner > ul').length;
    // console.log('Total count of UL is ' + totalULs);

    // // Count LI items for each UL
    // var ulElements = document.querySelectorAll('.sp-dropdown.sp-dropdown-main .sp-dropdown-inner > ul');
    // ulElements.forEach(function(ul, index) {
    //     var liCount = ul.querySelectorAll(':scope > li').length;
    //     console.log('The count of li items of ' + (index + 1) + 'st ul is = ' + liCount);
    // });

  
    // // Count total ULs
    var totalULs = document.querySelectorAll('.sp-dropdown.sp-dropdown-main .sp-dropdown-inner > ul').length;
    //console.log('Total count of UL is ' + totalULs);

    // Count LI items for each UL excluding blank ones
    var ulElements = document.querySelectorAll('.sp-dropdown.sp-dropdown-main .sp-dropdown-inner > ul');
    ulElements.forEach(function(ul, index) {
        var liItems = ul.querySelectorAll(':scope > li:not(:empty)');
        var liCount = liItems.length;
        //console.log('The count of li items of ' + (index + 1) + 'st ul is = ' + liCount);
        if (liCount > 4) {
            ul.classList.add('gridclass');
            var parentOfParentUl = ul.closest('.sp-dropdown');
            if (parentOfParentUl) {
                parentOfParentUl.classList.add('width400');
            }
            
        }
        // console.log('Names of LI items:');
        // liItems.forEach(function(li, liIndex) {
        //     console.log('  ' + li.textContent);
        // });
    });

    // // Count total ULs
    // var totalULs = document.querySelectorAll('sp-dropdown.sp-dropdown-main .sp-dropdown-inner > ul').length;
    // console.log('Total count of UL is ' + totalULs);

    // // Count LI items and print names for each UL excluding blank ones
    // var ulElements = document.querySelectorAll('sp-dropdown.sp-dropdown-main .sp-dropdown-inner > ul');
    // ulElements.forEach(function(ul, index) {
    //     var liItems = ul.querySelectorAll(':scope > li:not(:empty)');
    //     var liCount = liItems.length;
    //     console.log('The count of li items of ' + (index + 1) + 'st ul is = ' + liCount);
    //     console.log('Names of LI items:');
    //     liItems.forEach(function(li, liIndex) {
    //         console.log('  ' + li.textContent);
    //     });
    // });

});


