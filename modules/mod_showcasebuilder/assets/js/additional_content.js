/**
 * @version     1.1
 * @package     mod_showcasebuilder
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      JoomlaForce Team <support@joomlaforce.com> - http://www.joomlaforce.com
 */

(function($) {
	$(document).ready(function(){
		$('.content_slider_text_block_wrap').find('.button_hover_effect').hover(function(){
				var hoverClr = $(this).attr('data-hovercolor');
				$(this).stop(true).animate({'background-color': hoverClr}, 300);
		
			}, function(){
				var bgClr = $(this).attr('data-hoveroutcolor');
				$(this).stop(true).animate({'background-color': bgClr}, 300);
			});

		$('.content_slider_text_block_wrap').find('.content_img_wrap').hover(function(){
			$(this).find('.hover_link').show().stop(true).animate({'width' : 21, 'height' : 21, 'margin-top' : -10.5, 'margin-left' : -10.5, opacity : 1}, 150);
		}, function(){
			$(this).find('.hover_link').show().stop(true).animate({'width' : 0, 'height' : 0, 'margin-top' : 0, 'margin-left' : 0, opacity : 0}, 150, function(){$(this).hide();});
		});



	});
})(jQuery);