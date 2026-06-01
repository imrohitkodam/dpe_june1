jQuery(document).ready(function($) {

  jQuery.prototype.gpGallery = function(selector, options) {

    var $settings = {
        is_first_big: true,
        row_min_height: 180,
        row_max_height: 250,
        row_max_width: null,
        gutter: 5
    };
    if (options) {
        $.extend($settings, options);
    }

    function getWidthForBucket(bucket, extra) {
        var width = 0;
        if (bucket.length) {
            width = $settings.gutter * (bucket.length - 1);
            $.each(bucket, function(idx, item) {
                //width += item.width;
                width += item.cont_width;
            });
        }
        //console.log("previous width: " + width);
        if (extra) {
            //width += extra.width;
            width += extra.cont_width;
        }
        return width;
    }

    return this.each(function() {
    
        var $container = $(this);
        var max_bucket_width = $settings.row_max_width || $container.width() - 4;
        var buckets = [],
            last_bucket = {
                items: [],
                width: 0,
                height: 0
            };
        $container.find(selector).each(function() {
        
			var used =  $(this).attr('used');
            
            if(used != 'yes')
            {
		        var $this = $(this);
		        var $pic = $this;

		        if ($pic[0].nodeName.toUpperCase() != 'IMG') {
		            $pic = $pic.find('img');
		        } else {
		            $this = $('<div class="hi">').insertBefore($pic).append($pic);
		            var container = $(this).parent();
		            container.append("<div class='mydiv'>");
		            container.find(".mydiv").append( $(this).parent().parent().parent().find(".recordtitle").html() );
		        }
		        if (!$pic.length) return;

		        $this.css({width: 'auto', float: 'left', position: 'relative'});

		        var item = {
		            pic: $pic,
		            container: $this,
		            original_height: $pic.height() || $pic.attr('height'),
		            original_width: $pic.width() || $pic.attr('width')
		        };
		        item.aspect = item.original_width / item.original_height;
		        item.scale = $settings.row_min_height / item.original_height;
		        item.width = item.original_width * item.scale;
		        item.height = item.original_height * item.scale;
		        
		         if(item.width < $settings.imgcnr_minwidth && captionShow == true){
		         	item.cont_width = parseFloat($settings.imgcnr_minwidth) + $settings.gutter;
		         }else{
		         	item.cont_width = item.width;
		         }
		        
		        var new_bucket_width = getWidthForBucket(last_bucket.items, item);
		        if (new_bucket_width > (max_bucket_width - 15)  ) {
		            buckets.push(last_bucket);
		            last_bucket = {
		                items: [],
		                width: 0,
		                height: 0
		            };
		        }
		        last_bucket.items.push(item);
		        
		        //tws
		        $(this).attr('used','yes');
          }
            
        });
        buckets.push(last_bucket);
        last_bucket.last = true;

        $.each(buckets, function(idx, bucket) {
        
        
            if (!bucket.last) {
                bucket.scale = (max_bucket_width - (bucket.items.length - 1) * $settings.gutter) / getWidthForBucket(bucket.items);
            }
            var $last_item;

            $.each(bucket.items, function(idx2, item) {
                if (bucket.scale) {
                    item.width = Math.round(item.width * bucket.scale);
                    item.height = Math.round(item.height * bucket.scale);
                    item.cont_width = Math.round(item.cont_width * bucket.scale);
                }
                var pic = item.pic,
                    container = item.container;
                $last_item = item;

                pic.css({
                    height: item.height+"px",
                    width: item.width+"px"
                });
                item.container.css({
                    height: item.height+"px",
                    width: item.cont_width+"px",
                    marginTop: $settings.gutter + 'px'
                });
                if (idx2 > 0) {
                    item.container.css({
                        marginLeft: $settings.gutter + 'px'
                    });
                } else {
                    item.container.css({
                        clear: 'left'
                    });
                     item.container.attr({
                        leftadded: 'yes'
                    });
                    item.container.addClass(
                        'leftadded'
                    );
                }
               /* pic.is_hover = null;
                pic.hover(function() {
                    pic.stop().addClass('gp-gallery-picture-hover');
                    container.addClass('gp-gallery-hover');

                    if (item.original_height > item.height && item.original_width > item.width) {
                        pic.is_hover = setTimeout(function() {
                            pic.removeClass('gp-gallery-picture-hover');
                            pic.addClass('gp-gallery-picture-zoom');

                            if (pic.is_hover) {
                                pic.animate({
                                    marginTop: '-' + (item.original_height - item.height)/2 + 'px',
                                    marginLeft: '-' + (item.original_width - item.width)/2 + 'px',
                                    width: item.original_width + 'px',
                                    height: item.original_height + 'px'
                                }, 800);
                            }
                        }, 200);
                    }
                }, function() {
                    if (pic.is_hover) {
                        clearTimeout(pic.is_hover);
                        pic.is_hover = null;
                    }
                    if (item.original_height > item.height && item.original_width > item.width && pic.hasClass('gp-gallery-picture-zoom')) {
                        pic.stop().animate({
                            marginTop: '-6px',
                            marginLeft: '-6px',
                            width: item.width + 'px',
                            height: item.height + 'px'
                        }, 50, function() {
                            container.removeClass('gp-gallery-hover');
                            pic.removeClass('gp-gallery-picture-hover').removeClass('gp-gallery-picture-zoom').css({
                                margin: ''
                            });
                        });
                    } else {
                        container.removeClass('gp-gallery-hover');
                        pic.removeClass('gp-gallery-picture-hover').removeClass('gp-gallery-picture-zoom');
                    }
                });*/
            });
            
            if (!bucket.last && $last_item) {
                $last_item.width = $last_item.width + max_bucket_width - getWidthForBucket(bucket.items);
                $last_item.pic.css({
                    width: $last_item.width + 'px'
                });
            }
        });
    });
  };

});

