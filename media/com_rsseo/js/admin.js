if (typeof RSSeo == 'undefined') {
	var RSSeo = {};
}

RSSeo.seconds = 0;
RSSeo.titleLength = 50;
RSSeo.keywordsLength = 10;
RSSeo.descriptionLength = 300;

/*
 *	Initialize crawler
 */
RSSeo.initCrawler = function(url, id) {
	jQuery.ajax({
		url: url,
		type: 'post',
		dataType: 'html',
		headers: {
			'X-RSSEO-CRAWLER': 1
		},
		'data': { 
			rsseoInit : 1 
		},
		xhr: function() {
			var xhr = jQuery.ajaxSettings.xhr();
			var setRequestHeader = xhr.setRequestHeader;
			xhr.setRequestHeader = function(name, value) {
				if (name == 'X-Requested-With') return;
				setRequestHeader.call(this, name, value);
			}
			return xhr;
		}
	}).done(function(response) {
		RSSeo.crawl(url, id, 1, 0);
	});
}

/*
 *	Crawl pages
 */
RSSeo.crawl = function(url, id, init, original) {
	if (init && jQuery('#rssmessage').length) {
		jQuery('#rssmessage').css('display','');
	}
	
	if (init) {
		jQuery('#rsseoCrawling').css('display','');
		jQuery('.button_start').css('display','none');
		jQuery('.button_pause').css('display','');
	}
	
	url = url.replace(/&amp;/g, '&');
	
	jQuery.ajax({
		url: url,
		type: 'post',
		dataType: 'html',
		headers: {
			'X-RSSEO-CRAWLER': 1
		},
		'data': { 
			rsseoOriginal : original 
		},
		xhr: function() {
			var xhr = jQuery.ajaxSettings.xhr();
			var setRequestHeader = xhr.setRequestHeader;
			xhr.setRequestHeader = function(name, value) {
				if (name == 'X-Requested-With') return;
				setRequestHeader.call(this, name, value);
			}
			return xhr;
		}
	}).done(function(response, textStatus, jqXHR) {
		if (response.indexOf('<html') == -1) {
			// The requested URL is not a valid HTML file
			var data = {
				'id'				: id,
				'data[id]'			: id,
				'data[published]'	: '-1'
			}
		} else {
			var data = {
				'id'				 : id,
				'init'				 : init,
				'original'			 : original,
				'urls'				 : RSSeo.getLinks(response),
				'data[id]'			 : id,
				'data[title]'		 : RSSeo.getTitle(response),
				'data[description]'	 : RSSeo.getDescription(response),
				'data[keywords]'	 : RSSeo.getKeywords(response),
				'data[status]'	 	 : jqXHR.status,
				'data[imagesnoalt]'	 : RSSeo.getImagesNoAlt(response),
				'data[imagesnowh]'	 : RSSeo.getImagesNoWH(response),
				'data[densityparams]': RSSeo.getKeywordsDensity(response),
				'data[images]'		 : RSSeo.getImageSrc(response),
				
				'data[params]' : {
					'url_sef'				: RSSeo.isSef(url),
					'title_length'			: RSSeo.getTitleLength(response),
					'description_length'	: RSSeo.getDescriptionLength(response),
					'keywords'				: RSSeo.getKeywordsCount(response),
					'headings'				: RSSeo.getHeadings(response),
					'images'				: RSSeo.getImages(response),
					'images_wo_alt'			: RSSeo.getImages(response,'noalt'),
					'images_wo_hw'			: RSSeo.getImages(response,'nohw'),
					'links'					: RSSeo.getLinksCount(response)
				}
			}
		}
		
		// Add page to database
		RSSeo.addPage(data);
		
	}).fail(function(jqXHR, textStatus) {
		// The requested URL is invalid
		var data = {
			'id'				: id,
			'data[id]'			: id,
			'data[published]'	: '-1'
		}
		
		// Add page to database
		RSSeo.addPage(data);
	});
}

/*
 *	List internal / external URLs
 */
RSSeo.links = function(url, id) {
	jQuery.ajax({
		url: url,
		type: 'post',
		dataType: 'html',
		xhr: function() {
			var xhr = jQuery.ajaxSettings.xhr();
			var setRequestHeader = xhr.setRequestHeader;
			xhr.setRequestHeader = function(name, value) {
				if (name == 'X-Requested-With') return;
				setRequestHeader.call(this, name, value);
			}
			return xhr;
		}
	}).done(function(response) {
		var data = {
			'task': 'page.links',
			'id':	id,
			'urls': RSSeo.getLinks(response, true)
		}
		
		jQuery.ajax({
			converters: {
				"text json": RSSeo.parseJSON
			},
			url: 'index.php?option=com_rsseo',
			type: 'post',
			dataType: 'json',
			data: data
		}).done(function(urls) {
			if (typeof urls.external != 'undefined') {
				jQuery('#rsseo-external-links').css('display','');
				
				for (var key in urls.external) {
					var tr = jQuery('<tr>');
					var t1 = jQuery('<td>').html(key);
					var t2 = jQuery('<td>', {'align': 'center', 'class': 'center'}).html(urls.external[key]);
					
					tr.append(t1);
					tr.append(t2);
					jQuery('#rsseo-external-links table tbody').append(tr);
				}
			}
			
			if (typeof urls.internal != 'undefined') {
				for (var key in urls.internal) {
					var tr = jQuery('<tr>');
					var t1 = jQuery('<td>').html(key);
					var t2 = jQuery('<td>', {'align': 'center', 'class': 'center'}).html(urls.internal[key]);
					
					tr.append(t1);
					tr.append(t2);
					jQuery('#rsseo-internal-links table tbody').append(tr);
				}
				
				jQuery('#rsseo-internal-links').css('display','');
			}
			
			jQuery('#rsseo-links-loader').css('display','none');
		});
	});
}

/*
 *	Add a new page after crawl
 */
RSSeo.addPage = function(data) {
	jQuery.ajax({
		converters: {
			"text json": RSSeo.parseJSON
		},
		url: 'index.php?option=com_rsseo&task=ajaxcrawl',
		type: 'post',
		dataType: 'json',
		data: data
	}).done(function(response) {
		if (typeof RSSeo.simple != 'undefined') {
			jQuery(RSSeo.simpleEl).removeClass('rsseo-loading');
			jQuery('#page'+response.id).attr('class',response.color);
			jQuery('#page'+response.id).css('width',response.grade+'%');
			jQuery('#page'+response.id+' span').html(response.grade+'%');
			
			return;
		}
		
		if (typeof RSSeo.isRefresh != 'undefined') {
			jQuery('#title'+response.id).html(response.title);
			jQuery('#date'+response.id).html(response.date);
			jQuery('#page'+response.id).attr('class',response.color);
			jQuery('#page'+response.id).css('width',response.grade+'%');
			jQuery('#page'+response.id+' span').html(response.grade+'%');
			jQuery('#refresh'+response.id).css('display','');
			jQuery('#loading'+response.id).css('display','none');
			
			var status = response.status == 0 ? '-' : response.status;
			jQuery('#status'+response.id).html(status);
			if (response.status == 200) {
				jQuery('#status'+response.id).removeClass('badge-danger').removeClass('bg-danger').addClass('badge-success').addClass('bg-success');
			} else {
				jQuery('#status'+response.id).removeClass('badge-success').removeClass('bg-success').addClass('badge-danger').addClass('bg-danger');
			}
			
			if (RSSeo.refreshOriginal == 1) {
				jQuery('#img'+response.id).removeClass('icon-published').addClass('icon-unpublish');
			}
			
			return;
		}
		
		if (typeof RSSeo.pageTask != 'undefined') {
			jQuery('#rsseo-page-loading').css('display','none');
			jQuery('#rsseo-page-overlay').css('display','none');
			if (RSSeo.pageTask == 'page.apply') {
				document.location = RSSeo.redirectApply + RSSeo.pageID;
			} else {
				document.location = RSSeo.redirectSave;
			}
			
			return;
		}
		
		if (response.nextid == 0 || response.finished == 1) {
			jQuery('#url').html('<strong>' + response.finishtext + '</strong>');
			jQuery('#level').html('');
			jQuery('#scaned').html('');
			jQuery('#remaining').html('');
			jQuery('#total').html('');
			jQuery('#pause').val(0);
			
			if (jQuery('rssmessage').length) {
				jQuery('#rssmessage').css('display','none');
			}
			
			jQuery('#rsseoCrawling').css('display','none');
			jQuery('.button_start').css('display','none');
			jQuery('.button_pause').css('display','none');
			jQuery('.button_continue').css('display','none');
		} else {
			jQuery('#pageid').val(response.nextid);
			jQuery('#pageurl').val(response.next)
			
			if (jQuery('#pause').val() == 0) {
				jQuery('#url').html(response.url);
				jQuery('#level').html(response.level);
				jQuery('#scaned').html(response.crawled);
				jQuery('#remaining').html(response.remaining);
				jQuery('#total').html(response.total);
				
				if (RSSeo.seconds != 0) {
					setTimeout(function() { RSSeo.crawl(response.next, response.nextid); }, (parseInt(RSSeo.seconds) * 1000));
				} else {
					RSSeo.crawl(response.next, response.nextid);
				}
			}
		}
	}).fail(function(jqXHR, textStatus) {
		// The requested URL has failed and we get the current url and try one more time
		RSSeo.crawl(jQuery('#pageurl').val(), jQuery('#pageid').val());
	});
}

/*
 *	Save page metadata on simple view
 */
RSSeo.simpleCrawl = function(el, url, id) {
	jQuery(el).addClass('rsseo-loading');
	
	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'json',
		data: 'task=pages.ajax&' + jQuery(el).prop('name') + '=' + encodeURIComponent(jQuery(el).val())
	}).done(function(response) {
		RSSeo.simple = true;
		RSSeo.simpleEl = el;
		RSSeo.crawl(url, id, 0, 0);
	});
}

/*
 *	Save / Apply page
 */
RSSeo.savePage = function(task, url, original, newpage) {
	jQuery('#rsseo-page-loading').css('display','');
	jQuery('#rsseo-page-overlay').css('display','');
	jQuery('input[name="task"]').val(task);
	
	jQuery.ajax({
		converters: {
			"text json": RSSeo.parseJSON
		},
		url: 'index.php?option=com_rsseo&ajax=1',
		type: 'post',
		dataType: 'json',
		data: jQuery('#adminForm').serializeArray()
	}).done(function(response, textStatus) {
		if (typeof response.error != 'undefined') {
			jQuery('#rsseo-page-loading').css('display','none');
			jQuery('#rsseo-page-overlay').css('display','none');
			jQuery('#toolbar button').prop('disabled', false);
			Joomla.removeMessages();
			Joomla.renderMessages({ 'error': [response.error] });
		} else {
			if (response.keywordsdensity != '') {
				RSSeo.keywordsdensity = response.keywordsdensity;
			}
			
			url = newpage ? url + jQuery('#jform_url').val() : url;
			
			RSSeo.pageTask	= task;
			RSSeo.pageID	= response.id;
			
			RSSeo.crawl(url, response.id, 0, original);
		}
	});
}

/*
 *	Refresh a page
 */
RSSeo.refresh = function(url, id, original) {
	jQuery('#refresh'+id).css('display','none');
	jQuery('#loading'+id).css('display','');
	
	RSSeo.isRefresh = true;
	RSSeo.refreshOriginal = original;
	RSSeo.crawl(url, id, 0, original);
}

/*
 *	Pause the crawler
 */
RSSeo.pause = function() {
	jQuery('#pause').val(1);
	jQuery('#rsseoCrawling').css('display','none');
	jQuery('.button_pause').css('display','none');
	jQuery('.button_continue').css('display','');
}

/*
 *	Continue crawling
 */
RSSeo.continue = function() {
	jQuery('#pause').val(0);
	jQuery('#rsseoCrawling').css('display','');
	jQuery('.button_pause').css('display','');
	jQuery('.button_continue').css('display','none');
	RSSeo.crawl(jQuery('#pageurl').val(), jQuery('#pageid').val());
}

/*
 *	Check broken links
 */
RSSeo.broken = function(url, id) {
	jQuery('#loader_links').css('display','');
	jQuery('#brokenLinks').html('');
	jQuery('#brokenButton').prop('disabled',true);
	
	jQuery.ajax({
		url: url,
		type: 'get',
		dataType: 'html',
		xhr: function() {
			var xhr = jQuery.ajaxSettings.xhr();
			var setRequestHeader = xhr.setRequestHeader;
			xhr.setRequestHeader = function(name, value) {
				if (name == 'X-Requested-With') return;
				setRequestHeader.call(this, name, value);
			}
			return xhr;
		}
	}).done(function(response) {
		var links	= RSSeo.getLinks(response, true);
		var count	= jQuery.map(links, function() { return 1; }).length;
		var errors	= {};
		var i = 0;
		
		for (var link in links) {
			jQuery.ajax({
				url: link,
				type: 'get',
				async: false,
				dataType: 'html'
			}).fail(function(jqXHR, textStatus, errorThrown) {
				errors[link] = jqXHR.status;
				i++;
			}).done(function(response) {
				i++;
			});
		}
		
		if (i == count) {
			jQuery.ajax({
				url: 'index.php?option=com_rsseo&task=page.broken',
				type: 'post',
				dataType: 'html',
				data: {
					id:		id,
					urls:	errors
				}
			}).done(function(response) {
				jQuery('#loader_links').css('display','none');
				jQuery('#brokenButton').prop('disabled', false);
				document.location.reload();
			});
		}
	});
}

/*
 *	Get page title
 */
RSSeo.getTitle = function(contents) {
	return jQuery(contents).filter('title').html();
}

/*
 *	Get page title length
 */
RSSeo.getTitleLength = function(contents) {
	return jQuery(contents).filter('title').text().length;
}

/*
 *	Get page description
 */
RSSeo.getDescription = function(contents) {
	return jQuery(contents).filter('meta[name="description"]').length ? jQuery(contents).filter('meta[name="description"]').prop('content') : '';
}

/*
 *	Get page description length
 */
RSSeo.getDescriptionLength = function(contents) {
	return jQuery(contents).filter('meta[name="description"]').length ? jQuery(contents).filter('meta[name="description"]').prop('content').length : '0';
}

/*
 *	Get page keywords
 */
RSSeo.getKeywords = function(contents) {
	return jQuery(contents).filter('meta[name="keywords"]').length ? jQuery(contents).filter('meta[name="keywords"]').prop('content') : '';
}

/*
 *	Get page keywords length
 */
RSSeo.getKeywordsCount = function(contents) {
	return jQuery(contents).filter('meta[name="keywords"]').length ? jQuery(contents).filter('meta[name="keywords"]').prop('content').split(',').length : 0;
}

/*
 *	Get page URLs
 */
RSSeo.getLinks = function(contents, withCounter) {
	var links = withCounter ? {} : [];
	
	jQuery(contents).find('a').each(function() {
		var href = jQuery(this).attr('href');
		
		if (href) {
			if (href.substr(0,7) == 'mailto:' || href.substr(0,11) == 'javascript:' || href.substr(0,6) == 'ymsgr:' || href.substr(0,1) == '#' || href.substr(0,4) == 'tel:' || href.substr(0,6) == 'skype:' || href.substr(0,9) == 'facetime:' || href.substr(0,13) == '/administrator' || href.length == 0 || href === 'http://' || href === 'https://') {
				return;
			}
			
			if (withCounter) {
				links[href] = links.hasOwnProperty(href) ? (links[href] + 1) : 1;
			} else {
				var tmp = {};
				tmp.href = href;
				tmp.rel = (jQuery(this).prop('rel') == 'undefined' || jQuery(this).prop('rel') == '') ? '' : jQuery(this).prop('rel');
				links.push(JSON.stringify(tmp));
			}
		}
	});
	
	return links;
}

/*
 *	Get page URLs count
 */
RSSeo.getLinksCount = function(contents) {
	return jQuery(contents).find('a').length;
}

/*
 *	Get page images
 */
RSSeo.getImageSrc = function (contents) {
	var imagesSrc = [];
	
	jQuery(contents).find('img').each(function() {
		src = jQuery(this).prop('src');
		if (imagesSrc.indexOf(src) == -1) {
			imagesSrc.push(src);
		}
	});
	
	return imagesSrc.length ? imagesSrc : '';
}
 
/*
 *	Get page images count
 */
RSSeo.getImages = function(contents, type) {
	if (type == 'noalt') {
		return jQuery(contents).find('img:not([alt])').length;
	} else if (type == 'nohw') {
		return jQuery(contents).find('img:not([width],[height])').length;
	}
	
	return jQuery(contents).find('img').length;
}

/*
 *	Get page images with no ALT attribute
 */
RSSeo.getImagesNoAlt = function(contents) {
	var imagesNoAlt = [];
	
	jQuery(contents).find('img:not([alt])').each(function() {
		src = jQuery(this).prop('src');
		if (imagesNoAlt.indexOf(src) == -1) {
			imagesNoAlt.push(src);
		}
	});
	
	return imagesNoAlt.length ? imagesNoAlt : '';
}

/*
 *	Get page images with no WIDTH or HEIGHT attribute
 */
RSSeo.getImagesNoWH = function(contents) {
	var imagesNoWH = [];
	
	jQuery(contents).find('img:not([width],[height])').each(function() {
		src = jQuery(this).prop('src');
		if (imagesNoWH.indexOf(src) == -1) {
			imagesNoWH.push(src);
		}
	});
	
	return imagesNoWH.length ? imagesNoWH : '';
}

/*
 *	Get page heading count
 */
RSSeo.getHeadings = function(contents) {
	var total = 0;
	
	for (i = 1; i <= 6; i++) {
		total += jQuery(contents).find('h' + i).length;
	}
	
	return total;
}

/*
 *	Check if the page URL is SEF
 */
RSSeo.isSef = function(url) {
	return url.indexOf('.php?') == -1 ? 1 : 0;
}

/*
 *	Get keyword density
 */
RSSeo.getKeywordsDensity = function(contents) {
	var densityparams = {};
	
	if (typeof RSSeo.keywordsdensity != 'undefined') {
		var keywords = RSSeo.keywordsdensity.toLowerCase().split(',');
		
		if (keywords.length) {
			// Remove line breaks
			contents = contents.replace(/(?:\n|\r\n|\r)/ig, " ");
			// Remove content in script tags.
			contents = contents.replace(/<\s*script[^>]*>[\s\S]*?<\/script>/mig, "");
			// Remove content in style tags.
			contents = contents.replace(/<\s*style[^>]*>[\s\S]*?<\/style>/mig, "");
			// Remove content in comments.
			contents = contents.replace(/<!--.*?-->/mig, "");
			// Remove !DOCTYPE
			contents = contents.replace(/<!DOCTYPE.*?>/ig, "");
			
			var html	= jQuery(contents).text().toLowerCase();
			var words	= RSSeo.getWords(html);
			var total	= RSSeo.countWords(html);
			
			for (var i=0; i < keywords.length; i++) {
				if (keywords[i].split(' ').length > 1) {
					// Composed keyword
					var regex = RegExp(RSSeo.quoteReg(keywords[i]), 'igm');
					used = (html.match(regex) || []).length;
					
					if (used == 0) {
						densityparams[keywords[i]] = 0;
					} else {
						densityparams[keywords[i]] = (used / total) * 100;
					}
				} else {
					// Single keyword
					if (words.length) {
						used = 0;
						for (var j=0; j< words.length; j++) {
							if (jQuery.trim(words[j]) == jQuery.trim(keywords[i])) {
								used++;
							}
						}
						
						// The keyword is not found in the content
						if (used == 0) {
							densityparams[keywords[i]] = 0;
						} else {
							densityparams[keywords[i]] = (used / total) * 100;
						}
					} else {
						// We dont have any words
						densityparams[keywords[i]] = 0;
					}
				}
			}
		}
	}
	
	return densityparams;
}

RSSeo.quoteReg = function(str) {
	return (str+'').replace(/[.?*+^$[\]\\(){}|-]/g, "\\$&");
};

RSSeo.getWords = function(string) {
	return string.split(' ');
}

RSSeo.countWords = function(string) {
	var total 	= 0;
	var words 	= RSSeo.getWords(string);
	var invalid = ['©','&','>','<','=','.','€'];
	
	for (var i = 0; i < words.length; i++) {
		if (words[i]) {
			if (jQuery.trim(words[i]) != '' && isNaN(jQuery.trim(words[i])) && invalid.indexOf(jQuery.trim(words[i])) == -1) {
				total++;
			}
			
		}
	}
	
	return total;
}

RSSeo.parseJSON = function(data) {
	if (typeof data != 'object') {
		// parse invalid data:
		var match = data.match(/{.*}/);

		return jQuery.parseJSON(match[0]);
	}

	return jQuery.parseJSON(data);
}

RSSeo.pageLoadingTime = function(id) {
	jQuery('#loader').css('display','');
	jQuery('#pageloadtr').css('display','none');
	jQuery('#pagesizetr').css('display','none');
	
	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'html',
		data: 'task=pagecheck&id=' + id
	}).done(function( response ) {
		if (response != 0) {
			jQuery('#loader').css('display','none');
			jQuery('#pageloadtr').css('display','');
			jQuery('#pagesizetr').css('display','');
			
			var response = response.split('RSDELIMITER');
			jQuery('#pageload').html(response[1]);
			jQuery('#pagesize').html(response[0]);
		} else {
			jQuery('#loader').css('display','none');
			jQuery('#pageloadtr').css('display','none');
			jQuery('#pagesizetr').css('display','none');
		}
	});
}

RSSeo.checkKeyword = function(id) {
	jQuery('#refresh'+id).css('display','none');
	jQuery('#loading'+id).css('display','');
	
	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'json',
		data: 'task=keywords.refresh&id=' + id
	}).done(function( response ) {
		jQuery('#position'+id).html(response.position);
		jQuery('#position'+id).attr('class','badge badge-' + response.badge + ' bg-' + response.badge);
		jQuery('#date'+id).html(response.date);
		
		jQuery('#loading'+id).css('display','none');
		jQuery('#refresh'+id).css('display','');
	});
}

RSSeo.createFile = function(file) {
	jQuery('#' + file + 'loading').show();
	jQuery('.rsseo-error, .rsseo-message').remove();

	jQuery.ajax({
		converters: {
			"text json": RSSeo.parseJSON
		},
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'json',
		data: 'task=sitemap.create&file=' + file
	}).done(function( response ) {
		if (response.status == 1) {
			jQuery('#' + file).hide();
			jQuery('#btn' + file).show();
			jQuery('#sitemapbtn').prop('disabled',false);

			var message = jQuery('<div class="alert alert-success rsseo-message">').text(response.message);
			jQuery('#system-message-container').append(message);
		} else {
			var alert = jQuery('<div class="alert alert-error rsseo-error">').text(response.message);
			jQuery('#system-message-container').append(alert);
		}

		jQuery('#' + file + 'loading').hide();
	}).fail(function(jqXHR, textStatus, errorThrown){
		var alert = jQuery('<div class="alert alert-error rsseo-error">').text(errorThrown);
		jQuery('#system-message-container').append(alert);
	});
}

RSSeo.analytics = function(layout, profileID, start, end) {
	jQuery('#img'+layout).css('display','');
	jQuery('#ga'+layout).html('');
	
	if (layout == 'sourceschart') {
		jQuery('#rss_pie').html('');
	} else if (layout == 'visits') {
		jQuery('#rss_visualization').html('');
	}
	
	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'html',
		data: 'view=analytics&layout=' + layout + '&ajax=1&profile=' + profileID + '&start=' + start + '&end=' + end
	}).done(function( response ) {
		jQuery('#img'+layout).css('display','none');
		jQuery('#ga'+layout).html(response);
		
		if (layout == 'general') {
			jQuery('.tooltip').hide(); 
			jQuery('.hasTooltip').tooltip({'html': true,'container': 'body'});
		}
	});
}

RSSeo.updateAnalytics = function() {
	var profileID	= jQuery('#profile').val();
	var start		= jQuery('#rsstart').val();
	var end			= jQuery('#rsend').val();
	
	if (profileID) {
		Joomla.removeMessages();
		var date = new Date();
		date.setTime(date.getTime()+(365*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
		document.cookie = "rsseoAnalyticsID="+profileID+expires+"; path=/";
		
		RSSeo.analytics('general',profileID,start,end);
		RSSeo.analytics('newreturning',profileID,start,end);
		RSSeo.analytics('visits',profileID,start,end);
		RSSeo.analytics('geocountry',profileID,start,end);
		RSSeo.analytics('browsers',profileID,start,end);
		RSSeo.analytics('mobiles',profileID,start,end);
		RSSeo.analytics('sources',profileID,start,end);
		RSSeo.analytics('sourceschart',profileID,start,end);
		RSSeo.analytics('content',profileID,start,end);
	} else {
		var messages = { 'error': [Joomla.JText._('COM_RSSEO_ANALYTICS_SELECT_ACCOUNT')] };
		Joomla.renderMessages(messages);
	}
}

RSSeo.checkPage = function(id, original) {
	jQuery('#refresh'+id).css('display','none');
	jQuery('#loading'+id).css('display','');
	
	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'json',
		data: 'task=crawl&init=0&id=' + id+'&original='+original
	}).done(function( response ) {
		jQuery('#title'+id).html(response.title);
		jQuery('#date'+id).html(response.date);
		jQuery('#page'+id).attr('class',response.color);
		jQuery('#page'+id).css('width',response.grade+'%');
		jQuery('#page'+id+' span').html(response.grade+'%');
		jQuery('#refresh'+id).css('display','');
		jQuery('#loading'+id).css('display','none');
		if (original == 1) {
			jQuery('#img'+id).attr('clsas','icon-unpublish');
		}
		
		var status = response.status == 0 ? '-' : response.status;
		jQuery('#status'+id).html(status);
		if (response.status == 200) {
			jQuery('#status'+id).removeClass('badge-danger').removeClass('bg-danger').addClass('badge-success').addClass('bg-success');
		} else {
			jQuery('#status'+id).removeClass('badge-success').removeClass('bg-success').addClass('badge-danger').addClass('bg-danger');
		}
	});
}

RSSeo.pauseCrawl = function() {
	jQuery('#pause').val(1);
	jQuery('#rsseoCrawling').css('display','none');
	jQuery('.button_pause').css('display','none');
	jQuery('.button_continue').css('display','');
}

RSSeo.continueCrawl = function() {
	jQuery('#pause').val(0);
	jQuery('#rsseoCrawling').css('display','');
	jQuery('.button_pause').css('display','');
	jQuery('.button_continue').css('display','none');
	RSSeo.doCrawl(0,0);
}

RSSeo.doCrawl = function(init, id) {
	if (init && jQuery('#rssmessage').length) {
		jQuery('#rssmessage').css('display','');
	}
	
	if (init) {
		jQuery('#rsseoCrawling').css('display','');
		jQuery('.button_start').css('display','none');
		jQuery('.button_pause').css('display','');
	}

	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'json',
		data: 'task=crawl&init=' + init + '&id=' + id + '&auto=' + jQuery('#auto').val()
	}).done(function( response ) {
		if (response.finished == 0) {
			if (jQuery('#pause').val() == 0) {
				jQuery('#url').html(response.url);
				jQuery('#level').html(response.level);
				jQuery('#scaned').html(response.crawled);
				jQuery('#remaining').html(response.remaining);
				jQuery('#total').html(response.total);
				
				if (RSSeo.seconds != 0) {
					setTimeout(function() { RSSeo.doCrawl(0,0); }, (parseInt(RSSeo.seconds) * 1000));
				} else {
					RSSeo.doCrawl(0,0);
				}
			}
		} else {
			jQuery('#url').html('<strong>' + response.finishtext + '</strong>');
			jQuery('#level').html('');
			jQuery('#scaned').html('');
			jQuery('#remaining').html('');
			jQuery('#total').html('');
			jQuery('#pause').val(0);
			
			if (jQuery('rssmessage').length) {
				jQuery('#rssmessage').css('display','none');
			}
			
			jQuery('#rsseoCrawling').css('display','none');
			jQuery('.button_start').css('display','none');
			jQuery('.button_pause').css('display','none');
			jQuery('.button_continue').css('display','none');
		}
	});
}

RSSeo.generateRSResults = function(isredirect) {
	var field	= isredirect ? jQuery('#jform_from') : jQuery('#jform_canonical');
	var type	= isredirect ? '&type=redirect' : '';
	
	if (field.val().length > 1) {
		jQuery.ajax({
			url: 'index.php?option=com_rsseo',
			type: 'post',
			dataType: 'html',
			data: 'task=search&search=' + encodeURIComponent(field.val()) + type
		}).done(function( response ) {
			jQuery('#rss_results').css('width',field.width());
			if (isredirect) jQuery('#rss_results').css('margin-left',jQuery('#rsroot').width() + 4);
			jQuery('#rsResultsUl').html(response);
			jQuery('#rss_results').css('display','block');
		});
	}
}

RSSeo.addCanonical = function(url) {
	jQuery('#jform_canonical').val(url);
	jQuery('#rss_results').css('display','none');
}

RSSeo.addRedirect = function(url) {
	jQuery('#jform_from').val(url);
	jQuery('#rss_results').css('display','none');
}

RSSeo.closeCanonicalSearch = function() {
	jQuery('#rss_results').css('display','none');
}

RSSeo.closeRedirectSearch = function() {
	jQuery('#rss_results').css('display','none');
}

RSSeo.checkBroken = function(id, pageId) {
	jQuery('#loader_links').css('display','');
	jQuery('#brokenLinks').html('');
	jQuery('#brokenProgress').css('display','');
	jQuery('#brokenButton').prop('disabled',true);
	
	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'json',
		data: 'task=page.check&id=' + id + '&pageId=' + pageId
	}).done(function( response ) {
		if (response.finished == 0) {
			jQuery('#brokenBar').css('width',response.percent + '%');
			jQuery('#brokenPercentage').html(response.percent + '%');
			RSSeo.checkBroken(id, response.id);
		} else {
			jQuery('#brokenBar').css('width','100%');
			jQuery('#brokenPercentage').html('100%');
			jQuery('#loader_links').css('display','none');
			jQuery('#brokenProgress').css('display','none');
			jQuery('#brokenButton').prop('disabled', false);
			document.location.reload();
		}
	});
}

RSSeo.errorType = function(type) {
	if (type == 1) {
		jQuery('#errorUrl').css('display','none');
		jQuery('#errorMessage').css('display','');
		jQuery('#errorItemid').css('display','');
	} else {
		jQuery('#errorMessage').css('display','none');
		jQuery('#errorItemid').css('display','none');
		jQuery('#errorUrl').css('display','');
	}
}

RSSeo.addCustomMetadata = function() {
	var table = jQuery('#customMeta');
	var rowid = Math.round(Math.random() * 100000);
	
	var row = jQuery('<tr>',{id: 'meta' + rowid });
	
	var cell1 = jQuery('<td>').addClass('text-center');
	var cell2 = jQuery('<td>').addClass('text-center');
	var cell3 = jQuery('<td>').addClass('text-center');
	var cell4 = jQuery('<td>').addClass('text-center');
	
	var input1 = jQuery('<input>', {
		type : 'text',
		name : 'jform[custom][name][]',
	});
	
	input1.addClass('form-control');
	
	var input2 = jQuery('<input>', {
		type : 'text',
		name : 'jform[custom][content][]',
	});
	
	input2.addClass('form-control');
	
	var select = jQuery('<select>', {
		name : 'jform[custom][type][]',
		size : '1'
	});
	
	select.addClass('custom-select');
	
	var option1 = jQuery('<option>', {
		text : Joomla.JText._('COM_RSSEO_METADATA_TYPE_NAME'),
		value: 'name'
	});
	
	var option2 = jQuery('<option>', {
		text : Joomla.JText._('COM_RSSEO_METADATA_TYPE_PROPERTY'),
		value: 'property'
	});
	
	select.append(option1);
	select.append(option2);
	
	cell1.append(select);
	cell2.append(input1);
	cell3.append(input2);
	cell4.html('<a href="javascript:void(0)" class="btn btn-danger" onclick="RSSeo.removeCustomMetadata(\''+rowid+'\')">' + Joomla.JText._('COM_RSSEO_DELETE') + '</a>');
	
	row.append(cell1);
	row.append(cell2);
	row.append(cell3);
	row.append(cell4);
	
	table.append(row);
	
	if (typeof jQuery.fn.chosen != 'undefined') {
		jQuery(document).ready(function (){
			jQuery('select').chosen({"disable_search_threshold":10,"allow_single_deselect":true});
		});
	}
	
	jQuery("#metaDraggable").tableDnD();
}

RSSeo.removeCustomMetadata = function(id) {
	jQuery('#meta'+id).remove();
}

RSSeo.drawKeywordsChart = function() {
	// DEPRECATED
}

RSSeo.saveSimpleCrawl = function(el) {
	var id;
	var name = jQuery(el).prop('name');
	
	jQuery(el).addClass('rsseo-loading');
	
	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'json',
		data: 'task=pages.ajax&' + jQuery(el).prop('name') + '=' + encodeURIComponent(jQuery(el).val())
	}).done(function( response ) {
		jQuery(el).removeClass('rsseo-loading');
		name.replace(/\[(.+?)\]/g, function($0, $1) { id = $1 });
		if (typeof id != 'undefined') {
			jQuery('#page'+id).attr('class',response.color);
			jQuery('#page'+id).css('width',response.grade+'%');
			jQuery('#page'+id+' span').html(response.grade+'%');
		}
	});
}

RSSeo.drawVisitorsChart = function() {
	jQuery('#chart_visitors_loading').css('display','');
	jQuery('#chart_visitors').css('opacity','0.25');
	
	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'json',
		data: 'task=visitors&from=' + jQuery('#rsfrom').val() + '&to=' + jQuery('#rsto').val()
	}).done(function( response ) {
		jQuery('#chart_visitors_loading').css('display','none');
		jQuery('#chart_visitors').css('opacity','1');
		
		if (response.visitors.length) {
			var data = google.visualization.arrayToDataTable(response.visitors);
			var options = {
				vAxis: {format : '0'},
				legend: { position: 'none' },
				backgroundColor: '#f5f5f5'
			};

			var chart = new google.visualization.LineChart(document.getElementById('chart_visitors'));
			chart.draw(data, options);
		}
		
		jQuery('#visitors-timeframe').html(response.total);
		jQuery('#total-visitors').html(response.all);
	});
}

RSSeo.drawPageviewsChart = function() {
	jQuery('#chart_pageviews_loading').css('display','');
	jQuery('#chart_pageviews').css('opacity','0.25');
	
	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'json',
		data: 'task=pageviews&from=' + jQuery('#rsfrom').val() + '&to=' + jQuery('#rsto').val()
	}).done(function( response ) {
		jQuery('#chart_pageviews_loading').css('display','none');
		jQuery('#chart_pageviews').css('opacity','1');
		
		if (response.pageviews.length) {
			var data = google.visualization.arrayToDataTable(response.pageviews);
			var options = {
				vAxis: {format : '0'},
				legend: { position: 'none' },
				backgroundColor: '#f5f5f5'
			};

			var chart = new google.visualization.LineChart(document.getElementById('chart_pageviews'));
			chart.draw(data, options);
		}
		
		jQuery('#pageviews-timeframe').html(response.total);
		jQuery('#total-pageviews').html(response.all);
	});
}

RSSeo.updateCharts = function() {
	RSSeo.drawVisitorsChart();
	RSSeo.drawPageviewsChart();
}

RSSeo.updateVisitors = function() {
	RSSeo.updateCharts();
	jQuery('#visitors-table table tbody tr').remove();
	RSSeo.loadVisitors();
}

RSSeo.loadVisitors = function() {
	var limitstart = jQuery('#visitors-table table tbody tr').length;
	
	jQuery('.rsseo-btn').html(Joomla.JText._('COM_RSSEO_LOADING'));
	
	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'json',
		data: 'view=statistics&layout=visitors&format=raw&from=' + jQuery('#rsfrom').val() + '&to=' + jQuery('#rsto').val() + '&limitstart=' + limitstart
	}).done(function( response ) {
		jQuery('#visitors-total').text(response.total);
		jQuery('#visitors-table table tbody').append(response.html);
		jQuery('.rsseo-btn').html(Joomla.JText._('COM_RSSEO_LOAD_MORE'));
		
		if (jQuery('#visitors-table table tbody tr').length >= parseInt(response.total)) {
			jQuery('#visitors-pagination').css('display','none');
		} else {
			jQuery('#visitors-pagination').css('display','');
		}
	});
}

RSSeo.checkLinks = function(id) {
	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'json',
		data: 'task=page.links&id=' + id
	}).done(function( response ) {
		if (typeof response.external != 'undefined') {
			jQuery('#rsseo-external-links').css('display','');
			
			for (var key in response.external) {
				var tr = jQuery('<tr>');
				var t1 = jQuery('<td>').html(key);
				var t2 = jQuery('<td>', {'align': 'center', 'class': 'center'}).html(response.external[key]);
				
				tr.append(t1);
				tr.append(t2);
				jQuery('#rsseo-external-links table tbody').append(tr);
			}
		}
		
		if (typeof response.internal != 'undefined') {
			for (var key in response.internal) {
				var tr = jQuery('<tr>');
				var t1 = jQuery('<td>').html(key);
				var t2 = jQuery('<td>', {'align': 'center', 'class': 'center'}).html(response.internal[key]);
				
				tr.append(t1);
				tr.append(t2);
				jQuery('#rsseo-internal-links table tbody').append(tr);
			}
			
			jQuery('#rsseo-internal-links').css('display','');
		}
		
		jQuery('#rsseo-links-loader').css('display','none');
	});
}

RSSeo.createSitemap = function(isnew, cron) {
	var modified_date  	= jQuery('#jform_modified_date').val();
	var modified 		= jQuery('#jform_modified').val();
	var images 			= jQuery('#jform_images').val();
	var website 		= encodeURIComponent(jQuery('#jform_website').val());
	
	jQuery('#jform_modified_date').prop('disabled',true);
	jQuery('#jform_modified').prop('disabled',true);
	jQuery('#jform_website').prop('disabled',true);
	jQuery('#jform_images').prop('disabled',true);
	jQuery('#sitemapbtn').prop('disabled',true);
	jQuery('#sitemapInfo').css('display', '');
	jQuery('.rsseo-error, .rsseo-message').remove();

	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'json',
		converters: {
			"text json": RSSeo.parseJSON
		},
		data: 'task=sitemap.generate&new='+isnew+'&website='+website+'&modified_date='+modified_date+'&modified='+modified+'&images='+images+(cron ? '&cron='+ cron : '') 
	}).done(function( response ) {
		if (response.status == 1) {
			if (response.finished != 1) {
				//set the width and the procentage of the status bar
				var percent = response.progress + '%';
				jQuery('#com-rsseo-bar').css('width', percent);
				jQuery('#com-rsseo-bar').html(percent);
				RSSeo.createSitemap(0, response.cron);
			} else {
				jQuery('#com-rsseo-bar').css('width', '100%');
				jQuery('#com-rsseo-bar').html('100%');

				jQuery('#jform_modified_date').prop('disabled',false);
				jQuery('#jform_modified').prop('disabled',false);
				jQuery('#jform_website').prop('disabled',false);
				jQuery('#jform_images').prop('disabled',false);
				jQuery('#sitemapbtn').prop('disabled',false);

				var message = jQuery('<div class="alert alert-success rsseo-message">').text(response.message);
				jQuery('#system-message-container').append(message);
				jQuery('#sitemapInfo').css('display', 'none');
			}
		} else {
			var alert = jQuery('<div class="alert alert-error rsseo-error">').text(response.message);
			jQuery('#system-message-container').append(alert);
		}
	}).fail(function(jqXHR, textStatus, errorThrown){
		var alert = jQuery('<div class="alert alert-error rsseo-error">').text(errorThrown);
		jQuery('#system-message-container').append(alert);
	});
}

RSSeo.counters = function(object) {
	var count = 0;
	var id = object.prop('id');
	var property = id.replace('jform_','');
	var counter = 0;
	
	if (id == 'jform_keywords') {
		var keywords = object.val().split(',');
		for (var i = 0; i < keywords.length; i++) {
			if (keywords[i] != '') {
				count++;
			}
		}
	} else {
		count = object.val().length;
	}
	
	counter = parseInt(RSSeo[property + 'Length']) - parseInt(count);
	
	jQuery('#' + property + 'Counter').html(counter);
}

RSSeo.updateSnippet = function() {
	jQuery('#jform_title').on('keyup', function() {
		jQuery('.rsseo-snippet-title a').html(jQuery(this).val());
	});
	
	jQuery('#jform_description').on('keyup', function() {
		jQuery('.rsseo-snippet-description').html(jQuery(this).val());
	});
}

RSSeo.importKeywordData = function(btn, date) {
	jQuery(btn).find('i').css('display', 'inline-block');
	
	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'json',
		data: 'task=gkeyword.import&id=' + jQuery('#jform_id').val() + '&date=' + date
	}).done(function( response ) {
		if (typeof response.error != 'undefined') {
			jQuery(btn).find('i').css('display', 'none');
			jQuery('#rsseo_import_message').removeClass();
			jQuery('#rsseo_import_message').addClass('text-error');
			jQuery('#rsseo_import_message').html(response.error);
		} else {
			var elemes = jQuery(btn).parents('ul').find('li').length;
			var elemsContainer = jQuery(btn).parents('.rsseo_month_container');
			
			jQuery(btn).parents('li').remove();
			jQuery('#rsseo_import_message').removeClass();
			jQuery('#rsseo_import_message').addClass('text-success');
			jQuery('#rsseo_import_message').html(response.message);
			
			if (elemes - 1 == 0) {
				elemsContainer.remove();
			}
		}
		
		if (RSSeo.runAll) {
			RSSeo.importAllKeywordData();
		}
		
	});
}

RSSeo.importAllKeywordData = function() {
	RSSeo.runAll = true;
	
	// Check the Pause value
	if (jQuery('#rsseoPause').val() == 1) {
		jQuery('#rsseoPause').val('0');
		jQuery('#rsseoAllBtn').find('span').html(Joomla.JText._('COM_RSSEO_GKEYWORD_RUN_ALL'));
		jQuery('#rsseoAll').css('display','none');
		document.getElementById('rsseoAllBtn').onclick = null;
		document.getElementById('rsseoAllBtn').onclick = function() {
			RSSeo.importAllKeywordData();
		};
		
		RSSeo.runAll = false;
		return;
	}
	
	if (jQuery('.rsseo_month_container').length > 0) {
		
		// Get the first available import date
		jQuery('#rsseoAll').css('display','inline-block');
		jQuery('.rsseo_month_container:first-child li:first-child button').click();
		
		// Change the button to PAUSE
		jQuery('#rsseoAllBtn').find('span').html(Joomla.JText._('COM_RSSEO_GKEYWORD_RUN_ALL_PAUSE'));
		jQuery('#rsseoAllBtn').off('click');
		document.getElementById('rsseoAllBtn').onclick = null;
		document.getElementById('rsseoAllBtn').onclick = function() {
			jQuery('#rsseoPause').val('1');
		};
	} else {
		jQuery('#rsseoAllBtn').find('span').html(Joomla.JText._('COM_RSSEO_GKEYWORD_RUN_ALL'));
		jQuery('#rsseoAll').css('display','none');
		jQuery('#process-data').modal('hide');
		document.location.reload();
	}
}

RSSeo.showPages = function(id, date) {
	jQuery('#rsseo-pages').data('modal', null);
	jQuery('#rsseo-pages').find('.modal-body').html('');
	jQuery('#rsseo-pages').on('show.bs.modal', function (e) {
		jQuery(this).find('.modal-body').load('index.php?option=com_rsseo&view=gkeyword&layout=edit&tpl=page&tmpl=component&id=' + id + '&date=' + date);
	});
	jQuery('#rsseo-pages').modal('show');
}

RSSeo.drawGoogleKeywordChart = function() {
	var data = new google.visualization.DataTable();
	data.addColumn('string', Joomla.JText._('COM_RSSEO_GKEYWORD_DATE'));
	data.addColumn('number', Joomla.JText._('COM_RSSEO_GKEYWORD_POSITION'));
	data.addRows(RSSeo.jsonPositionChartData);
	
	var options = {
		vAxis: {format : '0'},
		legend: { position: 'none' },
		backgroundColor: '#fff'
	};
	
	var chart = new google.visualization.LineChart(document.getElementById('chart'));
	chart.draw(data, options);
}

RSSeo.drawGoogleKeywordChartDashboard = function() {
	var value = jQuery('#keyword').val();
	
	if (value == '') {
		jQuery('#chart').html('');
		return;
	}
	
	jQuery('#chart_keywords_loading').css('display','');
	jQuery('#chart').css('opacity','0.25');
	
	jQuery.ajax({
		url: 'index.php?option=com_rsseo',
		type: 'post',
		dataType: 'json',
		data: 'task=gkeyword.statistics&id=' + value + '&from=' + jQuery('#rsfrom').val() + '&to=' + jQuery('#rsto').val()
	}).done(function( response ) {
		jQuery('#chart_keywords_loading').css('display','none');
		jQuery('#chart').css('opacity','1');
		
		var data = new google.visualization.DataTable();
		data.addColumn('string', Joomla.JText._('COM_RSSEO_GKEYWORD_DATE'));
		data.addColumn('number', Joomla.JText._('COM_RSSEO_GKEYWORD_POSITION'));
		data.addRows(response);
		
		var options = {
			vAxis: {format : '0'},
			legend: { position: 'none' },
			backgroundColor: '#f5f5f5'
		};
		
		var chart = new google.visualization.LineChart(document.getElementById('chart'));
		chart.draw(data, options);
	});
}

RSSeo.showModal = function(url) {
	jQuery('#rsseoModal').on('show.bs.modal', function() {
		jQuery('body').addClass('modal-open');
		var modalBody = jQuery(this).find('.modal-body');
		modalBody.find('iframe').remove();
		modalBody.prepend('<iframe class="iframe" src="' + url + '" height="600"></iframe>');
	}).on('shown.bs.modal', function() {
		var modalHeight = jQuery('div.modal:visible').outerHeight(true),
		modalHeaderHeight = jQuery('div.modal-header:visible').outerHeight(true),
		modalBodyHeightOuter = jQuery('div.modal-body:visible').outerHeight(true),
		modalBodyHeight = jQuery('div.modal-body:visible').height(),
		modalFooterHeight = jQuery('div.modal-footer:visible').outerHeight(true),
		padding = document.getElementById('rsseoModal').offsetTop,
		maxModalHeight = (jQuery(window).height()-(padding*2)),
		modalBodyPadding = (modalBodyHeightOuter-modalBodyHeight),
		maxModalBodyHeight = maxModalHeight-(modalHeaderHeight+modalFooterHeight+modalBodyPadding);
		var iframeHeight = jQuery('.iframe').height();
		
		if (iframeHeight > maxModalBodyHeight) {
			jQuery('.modal-body').css({'max-height': maxModalBodyHeight, 'overflow-y': 'auto'});
			jQuery('.iframe').css('max-height', maxModalBodyHeight-modalBodyPadding);
		}
	}).on('hide.bs.modal', function () {
		jQuery('body').removeClass('modal-open');
		jQuery('.modal-body').css({'max-height': 'initial', 'overflow-y': 'initial'});
		jQuery('.modalTooltip').tooltip('destroy');
	});

	jQuery('#rsseoModal').modal('show');
}

RSSeo.editShort = function(val) { 
	if (jQuery('#jform_short').val() != '') {
		jQuery('#jform_short_dummy').val(jQuery('#jform_short').val());
	} else {
		jQuery('#jform_short_dummy').val(val);
	}
	
	jQuery('#jform_short_dummy').prop('readonly', false);
	jQuery('#editShortBtn').css('display', 'none');
	jQuery('#copyShortBtn').css('display', 'none');
	jQuery('#saveShortBtn').css('display', '');
	jQuery('#cancelShortBtn').css('display', '');
}

RSSeo.cancelShort = function(root) {
	if (jQuery('#jform_short').val() != '') {
		jQuery('#jform_short_dummy').val(root + jQuery('#jform_short').val());
	} else {
		jQuery('#jform_short_dummy').val('');
	}
	
	jQuery('#jform_short_dummy').prop('readonly', true);
	jQuery('#editShortBtn').css('display', '');
	jQuery('#copyShortBtn').css('display', '');
	jQuery('#saveShortBtn').css('display', 'none');
	jQuery('#cancelShortBtn').css('display', 'none');
}

RSSeo.saveShort = function(root) {
	pattern = new RegExp(/^[a-z0-9]+$/i);
	
	if (jQuery('#jform_short_dummy').val() != '') {
		if (pattern.test(jQuery('#jform_short_dummy').val())) {
			jQuery('#jform_short').val(jQuery('#jform_short_dummy').val());
			jQuery('#jform_short_dummy').val(root + jQuery('#jform_short').val());
			jQuery('#jform_short_dummy').prop('readonly', true);
			jQuery('#editShortBtn').css('display', '');
			jQuery('#copyShortBtn').css('display', '');
			jQuery('#saveShortBtn').css('display', 'none');
			jQuery('#cancelShortBtn').css('display', 'none');
		} else {
			alert(Joomla.JText._('COM_RSSEO_ONLY_ALPHANUM'));
		}
	} else {
		jQuery('#jform_short').val('');
		jQuery('#jform_short_dummy').val('');
		jQuery('#jform_short_dummy').prop('readonly', true);
		jQuery('#editShortBtn').css('display', '');
		jQuery('#copyShortBtn').css('display', '');
		jQuery('#saveShortBtn').css('display', 'none');
		jQuery('#cancelShortBtn').css('display', 'none');
	}
}

RSSeo.copyShort = function() {
	document.getElementById("jform_short_dummy").select();
	document.execCommand('copy');
}

RSSeo.removeErrorReferal = function (id) {
    jQuery.ajax({
        url: 'index.php?option=com_rsseo',
        type: 'post',
        data: 'task=errorlinks.referrals&id=' + id
    }).done(function( response ) {
        window.parent.location.reload();
    });
}

function number_format (number, decimals, dec_point, thousands_sep) {
  // http://kevin.vanzonneveld.net
  // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +     bugfix by: Michael White (http://getsprink.com)
  // +     bugfix by: Benjamin Lupton
  // +     bugfix by: Allan Jensen (http://www.winternet.no)
  // +    revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
  // +     bugfix by: Howard Yeend
  // +    revised by: Luke Smith (http://lucassmith.name)
  // +     bugfix by: Diogo Resende
  // +     bugfix by: Rival
  // +      input by: Kheang Hok Chin (http://www.distantia.ca/)
  // +   improved by: davook
  // +   improved by: Brett Zamir (http://brett-zamir.me)
  // +      input by: Jay Klehr
  // +   improved by: Brett Zamir (http://brett-zamir.me)
  // +      input by: Amir Habibi (http://www.residence-mixte.com/)
  // +     bugfix by: Brett Zamir (http://brett-zamir.me)
  // +   improved by: Theriault
  // +      input by: Amirouche
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // Strip all characters but numerical ones.
  number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
  var n = !isFinite(+number) ? 0 : +number,
    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
    s = '',
    toFixedFix = function (n, prec) {
      var k = Math.pow(10, prec);
      return '' + Math.round(n * k) / k;
    };
  // Fix for IE parseFloat(0.55).toFixed(0) = 0;
  s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
  if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
  }
  if ((s[1] || '').length < prec) {
    s[1] = s[1] || '';
    s[1] += new Array(prec - s[1].length + 1).join('0');
  }
  return s.join(dec);
}