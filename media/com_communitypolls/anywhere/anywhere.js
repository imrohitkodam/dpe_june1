/*!
 * CoreJoomla Community Polls Anywhere Plugin
 * http://www.corejoomla.com
 * Copyright www.corejoomla.com, All rights reserved.
 * Licensed under Gnu/GPL-2.0
 */

/*!
* jQuery Cookie Plugin
* https://github.com/carhartl/jquery-cookie
*
* Copyright 2011, Klaus Hartl
* Dual licensed under the MIT or GPL Version 2 licenses.
* http://www.opensource.org/licenses/mit-license.php
* http://www.opensource.org/licenses/GPL-2.0
*/

(function(window){
	
    var cj$ = null;
	var website = 'http://dev-kb.dataprotection.education/';
//	var website = 'http://localhost/j3/';
	var imgsrc = website+'components/com_communitypolls/assets/images/loading_sb.gif';
	var form_submitted = false;

	function PollsAnywhere(options){
		
		var params = {
			id : options.id,
			chart: options.chart,
			bgcolor : options.bgcolor,
			width : options.width,
			height : options.height,
			charttypename : options.chart || 'global',
			canvas : options.container || 'cjpollsanywhere',
			template : options.template || 'default',
			chartbgcolor : options.bgcolor || 'white',
			chartwidth : options.width || 300,
			chartheight : options.height || 200,
			pallete : options.pallete || 'default',
			noscripts: options.noscripts || false,
			anywhere: options.anywhere != 'undefined' ? options.anywhere : true
		};
		
		var factory = new PollsAnywhereFactory(params);
	    
		if(params.noscripts == false){
			
	    	if (typeof window.jQuery == 'undefined') {
	    		factory.load_script('http://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js', factory.load_anywhere );
	    	} else {
	    		factory.load_anywhere();
	    	}
		} else {
			
			factory.load_anywhere();
		}
	}
	
	function PollsAnywhereFactory(params){
		
		var my = this;
		
	    this.load_script = function(url, callback) {
	        var script = document.createElement("script");
	        script.type = "text/javascript";
	        if (script.readyState) {
	            script.onreadystatechange = function () {
	                if (script.readyState == "loaded" || script.readyState == "complete") {
	                    script.onreadystatechange = null;
	                    callback(params);
	                }
	            };
	        } else {
	            script.onload = function () {
	                callback(params);
	            };
	        }
	        script.src = url;
	        document.getElementsByTagName("head")[0].appendChild(script);
	    };
	    
		this.load_anywhere = function(){
			
	    	cj$ = window.jQuery.noConflict();
	    	params.container = cj$('.'+params.canvas);
	    	cj$('.' + params.canvas).append(cj$('<img>', {src: imgsrc}));
	    	
	        cj$.cookie = function(key, value, options) {
	            // key and at least value given, set cookie...
	            if (arguments.length > 1 && (!/Object/.test(Object.prototype.toString.call(value)) || value === null || value === undefined)) {
	                options = cj$.extend({}, options);

	                if (value === null || value === undefined) {
	                    options.expires = -1;
	                }

	                if (typeof options.expires === 'number') {
	                    var days = options.expires, t = options.expires = new Date();
	                    t.setDate(t.getDate() + days);
	                }

	                value = String(value);

	                return (document.cookie = [
	                    encodeURIComponent(key), '=', options.raw ? value : encodeURIComponent(value),
	                    options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
	                    options.path ? '; path=' + options.path : '',
	                    options.domain ? '; domain=' + options.domain : '',
	                    options.secure ? '; secure' : ''
	                ].join(''));
	            }

	            // key and possibly options given, get cookie...
	            options = value || {};
	            var decode = options.raw ? function(s) { return s; } : decodeURIComponent;

	            var pairs = document.cookie.split('; ');
	            for (var i = 0, pair; pair = pairs[i] && pairs[i].split('='); i++) {
	                if (decode(pair[0]) === key) return decode(pair[1] || ''); // IE saves cookies with empty string as "c; ", e.g. without "=" as opposed to EOMB, thus pair[1] may be undefined
	            }
	            return null;
	        };
	        
	        cj$('<script/>', {
				type: 'text/javascript', 
				src: website+'media/com_cjlib/chartjs/chart.bundle.min.js'
			}).appendTo('head');
	        
	        if(params.noscripts == false){
				
				cj$('<link/>', {
					rel: 'stylesheet', 
					type: 'text/css', 
					href: website+'media/com_communitypolls/anywhere/templates/'+params.template+'/style.css'
				}).appendTo('head');
				
		    	cj$.ajax({
		    		url: 'https://www.google.com/jsapi?callback',
		    		cache: true,
		    		dataType: 'script',
		    		success: function(){
		    			google.load('visualization', '1', {packages:['corechart'], 'callback' : function(){my.load_poll();}});
		    			return true;
		    		}
		    	});
	        } else {
	        	
	        	my.load_poll();
	        }
	    };
		
		this.build_template = function(poll, content, buttons){
			var tpl = poll.template;
			tpl = tpl.replace('{title}', poll.title)
					.replace('{description}', poll.description)
					.replace('{date}', poll.created)
					.replace('{author}', poll.username)
					.replace('{avatar}', poll.avatar)
					.replace('{votes}', poll.votes)
					.replace('{notices}', poll.notices)
					.replace('{chart}', content.html())
					.replace('{lastvote}', poll.last_voted)
					.replace('{actions}', buttons.html());
			return tpl;
		};
		
		this.get_content = function(poll, forcechart){
			var content = cj$('<div>',{'class': 'poll-content-wrapper'});
			if(poll.answers == null || poll.answers.length == 0) return content;
			
			if(poll.publish_results == false) {
				poll.notices = poll.notices + '<div class="poll-notice">'+poll.no_result_view_msg+'</div>';
				poll.eligible = 1;
				forcechart = false;
			}
			
			if(poll.type == 'radio' || poll.type == 'checkbox'){
				if(poll.eligible == 1 && !forcechart){
					var answerslist = cj$('<ul>', {'class': 'poll-content', 'style': 'margin-left: 0; padding-left: 0;'});
					for(var i=0; i<poll.answers.length; i++){
						var poll_answer = cj$('<li>');
						var url = null;
						var youtube = null;
						var images = new Array();
						
						if(poll.answers[i].resources != null){
							cj$.each(poll.answers[i].resources, function(index, resource){
								if(resource.type == 'image'){
									images.push(cj$('<img>', {'src': resource.src}));
								}else if(resource.type == 'url'){
									url = resource.value;
								}
								else if (resource.type == 'youtube')
								{
									youtube = resource.value;
								}
							});
						}
						
						if(url != null){
							poll_answer.append(
								cj$('<div>', {'class': 'poll-answer'}).append(
									cj$('<input>', {'type': poll.type, 'name': 'answer[]', 'value': poll.answers[i].id, 'id': poll.random+'_'+i}),
									cj$('<label>', {'for': poll.random+'_'+i}).html(cj$('<a>', {'href': url, 'target': '_blank'}).html(poll.answers[i].title))
								)
							);
						}else{
							poll_answer.append(
								cj$('<div>', {'class': 'poll-answer'}).append(
									cj$('<input>', {'type': poll.type, 'name': 'answer[]', 'value': poll.answers[i].id, 'id': poll.random+'_'+i}),
									cj$('<label>', {'for': poll.random+'_'+i}).html(poll.answers[i].title)
								)
							);
						}
						
						if(images.length > 0){
							var poll_resources = cj$('<div>', {'class': 'poll-resources'});
							for(var t=0; t<images.length; t++){
								poll_resources.append(images[t]);
							}
							poll_answer.append(poll_resources);
						}
						
						if(youtube != null && youtube){
							var poll_resources = cj$('<iframe width="560" height="315" src="'+youtube+'" frameborder="0" allowfullscreen></iframe>');
							poll_answer.append(poll_resources);
						}
						
						answerslist.append(poll_answer);
					}
					if(poll.custom_answer == 1){
						answerslist.append(cj$('<li>').append(
							cj$('<label>', {'for': poll.random+'_custom'}).html(poll.lbl_custom_answer),
							cj$('<br>'),
							cj$('<input>', {'type': 'text', 'name': 'custom_answer', 'id': poll.random+'_custom', 'size': 25})
						));
					}
					content.append(answerslist);
				} else {
					if(poll.chart == 'sbar'){
						content.append(cj$('<div>', {'class': 'poll-bar-chart-wrapper'}).append(my.get_bar_chart(poll)));
					} else if(poll.chart == 'gpie') {
						content.append(
							cj$('<div>', {'class': 'poll-pie-chart-wrapper'}).append(cj$('<div>', {id: poll.random+'_piechart'}).css({width: params.chartwidth+'px', height: params.chartheight+'px'})),
							cj$('<div>', {'class': 'poll-bar-chart-wrapper'}).append(my.get_bar_chart(poll))
						);
					} else {
						var wrapper = cj$('<div>', {'class': 'poll-pie-chart-wrapper'});
						wrapper.append(cj$('<div>', {id: poll.random+'_modernchart'}));
						wrapper.append('<div class="chart-toggle-buttons clearfix"><div class="pull-right">' +
								'<button class="btn btn-info btn-mini btn-pie-chart">'+poll.lbl_pie+'</button>' +
								'<button class="btn btn-info btn-mini btn-bar-chart">'+poll.lbl_bar+'</button>' +
								'<button class="btn btn-info btn-mini btn-line-chart">'+poll.lbl_line+'</button></div></div>');
						content.append(wrapper);
					}
				}
			} else if(poll.type == 'grid'){
				var grid = cj$('<table>', {'class': 'poll-content-grid'});
				var header_row = cj$('<tr>').append(cj$('<td>'));
				for(var i=0; i<poll.columns.length; i++){
					header_row.append(cj$('<th>').html(poll.columns[i].title));
				}
				grid.append(cj$('<thead>').append(header_row));
				var grid_content = cj$('<tbody>', {'class': 'poll-content'});
				var content_row = null;
				for(var i=0; i< poll.answers.length; i++){
					content_row = cj$('<tr>').append(cj$('<th>').html(poll.answers[i].title));
					for(var j=0; j<poll.columns.length; j++){
						if(poll.eligible ==1 && !forcechart){
							content_row.append(
								cj$('<td>').html(
									cj$('<input>', {'type': 'radio', 'name': 'answer'+poll.answers[i].id, 'value': poll.answers[i].id+'_'+poll.columns[j].id, 'id': poll.random+'_'+i+'_'+j})
								)
							);
						} else {
							var found = false;
							for(var k=0; k<poll.gridvotes.length; k++){
								if(poll.gridvotes[k].option_id == poll.answers[i].id && poll.gridvotes[k].column_id == poll.columns[j].id){
									found = true;
									content_row.append(cj$('<td>').html(poll.gridvotes[k].votes));
								}
							}
							if(!found){
								content_row.append(cj$('<td>').html('0'));
							}
						}
					}
					grid_content.append(content_row);
				}
				grid.append(grid_content);
				content.append(grid);
			}
			
			content.append(cj$('<input>', {'name': 'pollid', 'type': 'hidden'}).val(poll.id));
			return content;
		};
		
		this.get_modern_chart = function(wrapper, poll){
			var container = cj$('#'+poll.random+'_modernchart');
			
			var colors = poll.rgb_colors;
			var answers = poll.answers;
			var legend_position = 'bottom';
			
			var values = new Array();
			var labels = new Array();
			var bgColors = new Array();
			var length = answers.length;

			cj$.each(answers, function(i, answer){
				labels.push(answer.title);
				values.push(parseInt(answer.votes));
				bgColors.push(colors[i % length]);
			});
			
			var config = {
		        type: 'doughnut',
		        data: {
		            datasets: [{
		                data: values,
		                backgroundColor: bgColors,
		                borderColor: bgColors,
		                fill: false
		            }],
		            
		            labels: labels
		        },
		        options: {
		            responsive: true,
		            maintainAspectRatio: false,
		            
		            legend: {
		                position: legend_position,
		            },
		            title: {
		                display: false,
		            },
		            animation: {
		                animateScale: true,
		                animateRotate: true
		            },
		            scales: {
		                xAxes: [{
		                            gridLines: {
		                                display:true
		                            },
		                            ticks: {
		                                beginAtZero: true,
		                                stepSize: 1
		                            }
		                        }],
		                yAxes: [{
		                            gridLines: {
		                                display:true
		                            },
		                            ticks: {
		                                beginAtZero: true,
		                                stepSize: 1
		                            }
		                        }]
	                },
	                elements: {
	                    rectangle: {
	                        borderWidth: 2,
	                    },
	                    line: {
	                    	tension: 0.4
	                    }
	                }
		        }
		    };
			
			var pieConfig = cj$.extend(true, {}, config);
			var barConfig = cj$.extend(true, {}, config);
			var lineConfig = cj$.extend(true, {}, config);
			
			pieConfig.options.scales.xAxes[0].display = false;
			pieConfig.options.scales.yAxes[0].display = false;
			
			barConfig.type = 'horizontalBar';
			barConfig.options.legend.display = false;
			
			lineConfig.type = 'line';
			lineConfig.options.legend.display = false;
			
			// Define a plugin to provide data labels
	        Chart.plugins.register({
	            afterDatasetsDraw: function(chart, easing) {
	                // To only draw at the end of animation, check for easing === 1
	                var ctx = chart.ctx;

	                chart.data.datasets.forEach(function (dataset, i) {
	                    var meta = chart.getDatasetMeta(i);
	                    if (!meta.hidden) {
	                        meta.data.forEach(function(element, index) {
	                            // Draw the text in black, with the specified font
	                            ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';

	                            var fontSize = 14;
	                            var fontStyle = 'normal';
	                            var fontFamily = 'Helvetica Neue';
	                            ctx.font = Chart.helpers.fontString(fontSize, fontStyle, fontFamily);

	                            // Just naively convert to string for now
	                            var dataString = dataset.data[index].toString();

	                            // Make sure alignment settings are correct
	                            ctx.textAlign = 'center';
	                            ctx.textBaseline = 'middle';

	                            var padding = 5;
	                            var position = element.tooltipPosition();
	                            ctx.fillText(dataString, position.x, position.y - (fontSize / 2) - padding);
	                        });
	                    }
	                });
	            }
	        });
			
			var ctx = cj$('<canvas>');
			container.empty().append(ctx);
			var chart = new Chart(ctx, pieConfig);
			
			wrapper.find('.btn-pie-chart').click(function(){
				chart.destroy();
				var ctx = cj$('<canvas>');
				container.empty().append(ctx);
				chart = new Chart(ctx, pieConfig);
			});
			
			wrapper.find('.btn-bar-chart').click(function(){
				chart.destroy();
				var ctx = cj$('<canvas>');
				container.empty().append(ctx);
				chart = new Chart(ctx, barConfig);
			});
			
			wrapper.find('.btn-line-chart').click(function(){
				chart.destroy();
				var ctx = cj$('<canvas>');
				container.empty().append(ctx);
				chart = new Chart(ctx, lineConfig);
			});
		},
		
		this.get_pie_chart = function(poll){
			var rows = new Array();
			var row = null;
			cj$.each(poll.answers, function(i, answer){
				row = new Array();
				row.push(answer.title);
				row.push(parseInt(answer.votes));
				rows.push(row);
			});
			var data = new google.visualization.DataTable();
			data.addColumn('string', "Answers");
			data.addColumn('number', "Votes");
			data.addRows(rows);
			
			var chartcolors = new Array();
			var colors_count = poll.colors.length;
			for(var i=0; i<poll.answers.length; i++){
				chartcolors = chartcolors.concat(poll.colors[i%colors_count]);
			}
			
			var canvas = cj$('#'+poll.random+'_piechart');
			new google.visualization.PieChart(canvas.get(0)).draw(data, {
				is3D: true, 
				legend: 'none',
				width: params.chartwidth, 
				height: params.chartheight,
				backgroundColor: params.chartbgcolor,
				colors: chartcolors});
			return true;
		},
		
		this.get_bar_chart = function(poll){
			var chart = cj$('<div>', {'class': 'poll-bar-chart'});
			var colors_count = poll.colors.length;
            cj$.each(poll.answers, function(i, answer){
    			var url = null;
    			var images = new Array();
    			var poll_answer = cj$('<div>', {'class': 'poll-answer-wrapper'});
    			
    			if(poll.answers[i].resources != null){
    				cj$.each(poll.answers[i].resources, function(index, resource){
    					if(resource.type == 'image'){
    						images.push(cj$('<img>', {'src': resource.src}));
    					}else if(resource.type == 'url'){
    						url = resource.value;
    					}
    				});
    			}
    			
    			if(url != null){
    				poll_answer.append(
    					cj$('<div>',{'class':'poll-answer-title'}).html(cj$('<a>', {'href': url, 'target': '_blank'}).html(answer.title +' ( '+answer.votes+' '+(answer.votes == 1 ? poll.lbl_vote+' )' : poll.lbl_votes+' )')))
    				);
    			}else{
    				poll_answer.append(
    					cj$('<div>',{'class':'poll-answer-title'}).html(answer.title+' ( '+answer.votes+' '+(answer.votes == 1 ? poll.lbl_vote+' )' : poll.lbl_votes+' )'))
    				);
    			}
    			
    			if(images.length > 0){
    				var poll_resources = cj$('<div>', {'class': 'poll-resources'});
    				for(var t=0; t<images.length; t++){
    					poll_resources.append(images[t]);
    				}
    				poll_answer.append(poll_resources);
    			}
    			
    			poll_answer.append(
                    cj$('<div>', {'class': 'poll-bar-wrapper'}).append(
                    	cj$('<div>', {'class':'poll-bar-pct'}).html(answer.pct+'%'),
                    	cj$('<div>', {'class':'poll-bar-block','style': 'width:'+answer.pct+'%; background-color:'+poll.colors[i%colors_count]}),
                    	cj$('<div>', {'class':'poll-clear'})
                    )
                );
    			
    			chart.append(poll_answer);
            });
            return chart;			
		},
		
		this.load_poll = function() {
			var ajxdata = cj$.extend({}, params);
			delete ajxdata.container;
			var task = params.anywhere ? 'jsonp' : 'json';
			
			cj$.ajax({
				url : website+'index.php?option=com_communitypolls&task=anywhere.'+task+'&id='+params.id+'&format=json&callback=?',
				dataType : 'jsonp',
				data: {'params': ajxdata},
				type: 'post'
            })
            .done(function(data){
            	params.container.poll = data.data;
            	if(typeof params.container.poll.error != 'undefined'){
            		alert(params.container.poll.error);
            		return;
            	}
				if(typeof params.chart != 'undefined' && (params.chart == 'pie' || params.chart == 'bar')){
					params.container.poll.chart = params.chart;
				}
				
				var buttons = cj$('<div>', {'class': 'poll-actions'});
				var btn_vote_form = cj$('<button>', {'class': 'btn-vote-form'}).html(params.container.poll.lbl_vote_form);
				var btn_vote_now = cj$('<button>', {'class': 'btn-vote-now'}).html(params.container.poll.lbl_vote_now);
				var btn_view_result = cj$('<button>', {'class': 'btn-view-result'}).html(params.container.poll.lbl_view_result);
				
				if(params.container.poll.closed != true && cj$.cookie('cpollcookie'+params.container.poll.id) == null){
					buttons.empty();
					if(params.container.poll.publish_results == true){
						buttons.append(btn_view_result);
					}
					buttons.append(btn_vote_now);
					params.container.poll.eligible = 1;
				} else {
					params.container.poll.eligible = 0;
				}
				
				params.container.poll.notices = '';
				if(params.container.poll.closed == true){
					params.container.poll.notices = '<div class="poll-notice">'+params.container.poll.msg_poll_closed+'<div>';
				}
				
				var content = my.get_content(params.container.poll, false);
				params.container.html(my.build_template(params.container.poll, content, buttons));
				if(params.container.find('#'+params.container.poll.random+'_piechart').length > 0){
					my.get_pie_chart(params.container.poll);
				}
				
				params.container.on('click', '.btn-view-result', function(){
					content = my.get_content(params.container.poll, true);
					buttons.empty().append(btn_vote_form);
					params.container.hide();
					params.container.html(my.build_template(params.container.poll, content, buttons));
					if(params.container.find('#'+params.container.poll.random+'_piechart').length > 0){
						my.get_pie_chart(params.container.poll);
					}
					params.container.show('slow', function(){
						if(params.container.find('#'+params.container.poll.random+'_modernchart').length > 0){
							my.get_modern_chart(params.container, params.container.poll);
						}
					});
				});
				
				params.container.on('click', '.btn-vote-form', function(){
					content = my.get_content(params.container.poll, false);
					buttons.empty().append(btn_view_result, btn_vote_now);
					params.container.hide();
					params.container.html(my.build_template(params.container.poll, content, buttons));
					if(params.container.find('#'+params.container.poll.random+'_piechart').length > 0){
						my.get_pie_chart(params.container.poll);
					}
					params.container.show('slow');
				});
				
				form_submitted = false;
				params.container.on('click', '.btn-vote-now', function(){
					
					if(form_submitted) return false;
					form_submitted = true;
					
					var answers = Array();
					var custom_answer = '';
					
					if(params.container.find('.poll-content').find('input:checked').length == 0 && params.container.find('.poll-content').find('input[name="custom_answer"]').val() == ''){
						alert(params.container.poll.msg_no_answer);
						return false;
					}
					
					params.container.find('.poll-content').find('input:checked').each(function(){
						answers.push(cj$(this).val());
					});
					
					if(params.container.find('.poll-content').find('input[name="custom_answer"]').length > 0){
						custom_answer = params.container.find('.poll-content').find('input[name="custom_answer"]').val();
					}
					
					cj$(this).parent().prepend(cj$('<div>', {'style': 'text-align: center;'}).html( cj$('<img>', {src: imgsrc, 'class': 'progress_anim'})));
					var reqType = params.anywhere ? 'jsonp' : 'json';
					var pollId = params.container.find('input[name="pollid"]').val();
					
					cj$.ajax({
						url : website+'index.php?option=com_communitypolls&task=poll.ajxvote&callback=?',
	                    data: {'id': pollId,'answers': answers, 'custom_answer': custom_answer, 'params': ajxdata},
	                    dataType: reqType,
	    				type: 'post',
					})
					.done(function(npoll) {
                    	if(typeof npoll.error != 'undefined' && npoll.error != null && npoll.error.length > 0){
                    		alert(npoll.error);
                    	} else {
                    		npoll = npoll.poll;
                    		npoll.notices = '';
        					if(npoll.closed == true){
        						npoll.notices = npoll.notices + '<div class="poll-notice">'+npoll.msg_poll_closed+'<div>';
        					}
        					
        					if(npoll.end_message)
        					{
        						npoll.notices = npoll.notices + '<div class="poll-notice">'+npoll.end_message+'<div>';
        					}
        					
                    		npoll.random = params.container.poll.random;
	                    	content = my.get_content(npoll, true);
	                    	buttons.empty();
	                    	params.container.toggle('slow');
	                    	params.container.html(my.build_template(npoll, content, buttons));
	    					if(params.container.find('#'+params.container.poll.random+'_piechart').length > 0){
	    						my.get_pie_chart(npoll);
	    					} else if(params.container.find('#'+params.container.poll.random+'_modernchart').length > 0){
	    						my.get_modern_chart(params.container, npoll);
	    					}
	    					
	    					var date = new Date();
	    					date.setTime(date.getTime() + (params.container.poll.expire * 60 * 1000));
	                    	cj$.cookie('cpollcookie'+params.container.poll.id, '1', {expires: date, path: '/'});
	                    	params.container.toggle('slow');
                    	}
                    	params.container.find('.progress_anim').remove();
                    	form_submitted = false;
					})
					.fail(function(data){
		    			alert(data.message);
		    		});
				});
            })
            .fail(function(data){
    			alert(data.message);
    		});
		};
    	
    	return true;
	}
	
	if(typeof window.PollsAnywhere == 'undefined'){
		
		window.PollsAnywhere = PollsAnywhere;
	}
})(window);