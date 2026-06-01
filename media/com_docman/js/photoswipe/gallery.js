
kQuery(function($) {
    var options = {
            history: false,
            closeOnScroll: false,
            showAnimationDuration: 0,
            hideAnimationDuration: 0,
            showHideAnimationType: 'none'
        },
        openGallery = function(items, index) {
            options.dataSource = items;
            options.index = index;

            var url = window.location.href;
            var instance = new PhotoSwipe(options);

            updateSlides(url, instance.options.dataSource);

            instance.options.getImageURLForShare = function() {
                return instance.currItem.download_link || instance.currItem.src;
            };
            
            instance.on('uiRegister', function() {
                instance.ui.registerElement({
                    name: 'download-button',
                    order: 8,
                    isButton: true,
                    tagName: 'a',
                
                    // SVG with outline
                    html: {
                      isCustomSVG: true,
                      inner: '<path d="M20.5 14.3 17.1 18V10h-2.2v7.9l-3.4-3.6L10 16l6 6.1 6-6.1ZM23 23H9v2h14Z" id="pswp__icn-download"/>',
                      outlineID: 'pswp__icn-download'
                    },
                
                    onInit: (el, pswp) => {
                      el.setAttribute('download', '');
                      el.setAttribute('target', '_blank');
                      el.setAttribute('rel', 'noopener');
                
                      pswp.on('change', () => {
                        el.href = pswp.currSlide.data.src;
                      });
                    }
                });
            });

            // Get data just in time for faster startup
            instance.addFilter('itemData', function(item, index) {
                if (!item.src && !item.html)
                {
                    var element = item.el;

                    item.track = {
                        id: element.data('id'),
                        title: element.data('title')
                    };

                    if (element.hasClass('koowa_media__item__link--html')) {
                        item.html ='<iframe height="100%" width="100%" src="'+element.attr('href')+'"></iframe>';
                        item.download_link = element.attr('href');
                    } else {
                        item.src = element.attr('href');
                        item.w   = element.data('width') ? parseInt(element.data('width'), 10) : 0;
                        item.h   = element.data('height') ? parseInt(element.data('height'), 10) : 0;
                    }

                    if (element.find('.koowa_header__item')) {
                        item.title = $.trim(element.find('.koowa_header__item--title_container').text());
                    }
                }

                return item;
            });

            instance.on('contentLoadImage', function(item) {
                if (item.src) {
                    $(document).trigger('photoswipeImageView', [item]);
                }
            });

            instance.on('change', function() {
                var current_index = instance.currIndex;
                var current_slide = instance.currSlide.data;
                var last_index    = instance.options.dataSource.length - 1;

                // Previous button
                if (current_index == 0 && current_slide.prev)
                {
                    // Check for existing previous pagination for documents
                    $.getJSON(current_slide.prev).done(function (data) {
                            addSlidePages(data, instance, 'previous');
                    });

                    return false;
                }
                else if (current_index == last_index && current_slide.next)
                {
                    // Check for existing next pagination for documents
                    $.getJSON(current_slide.next).done(function (data) {
                            addSlidePages(data, instance, 'next');
                    });

                    return false;
                }
            });

            instance.init();
        },

        /**
         * Update preloaded slides pagination
         *
         * @param url
         * @param data_source
         */
        updateSlides = function(url, data_source) {
            $.getJSON(url).done(function ( data ) {
                var prev_url  = data.links.previous ? data.links.previous.href : null;
                var next_url  = data.links.next ? data.links.next.href : null;

                $.each(data_source, function (i, item){
                    item.next = next_url;
                    item.prev = prev_url;
                });
            });
        },

        /**
         * Add slides pagination
         *
         * @param url
         * @param instance
         * @param direction
         */
        addSlidePages = function(data, instance, direction) {
            var old_data_source = instance.options.dataSource;
            var data_source = [];
            var starting_slide;

            // Reset data source
            instance.options.dataSource = [];

                var documents = fetchEntities(data);
                var prev_url  = data.links.previous ? data.links.previous.href : null;
                var next_url  = data.links.next ? data.links.next.href : null;

                $.each(documents, function (index, document){
                    var url  = document.links.file.href;
                    var file = document.file;

                    data_source.push({
                        src: url,
                        w:file.width,
                        h:file.height,
                        next: next_url,
                        prev: prev_url
                    });
                });

                if (direction == 'next')
                {
                    // New starting slide's index
                    starting_slide = old_data_source.length - 1;
                    data_source = old_data_source.concat(data_source);
                }
                else if (direction == 'previous')
                {
                    // New starting slide's index
                    starting_slide = data_source.length;
                    data_source = data_source.concat(old_data_source);
                }

                // New data source
                instance.options.dataSource = data_source;
                instance.goTo(starting_slide);

                // Update all slides
                $.each(instance.options.dataSource, function (key, value) {
                    instance.refreshSlideContent(key);
                })
        },
        // Fetch the correct entities
        fetchEntities = function(data) {
            // `data.linked.documents` for Category documents
            return data.linked.documents ? data.linked.documents : data.entities;
        },
        getGalleryItems = function(gallery) {
            var items = [];

            $(gallery).find('.k-js-gallery-item').each(function(i, element) {
                element = $(element);
                element.data('index', i);

                items.push({
                    el: element // save link to element for getThumbBoundsFn
                });
            });

            return items;
        };

        $('a.k-js-gallery-item').click(function( event ) {

            event.preventDefault();

            if ($(this).length) {

                var elements = getGalleryItems($(this).parents('.koowa_media--gallery'));

                if (elements) {
                    openGallery(elements, $(this).data('index'));
                }
            }
        });
});
