/*
 *
 * This is a part of WP-Cat2Calendar WordPress Plugin
 * Plugin URI: http://codeispoetry.ru/?page_id=52
 * 
 */

(function($){

	$.fn.wpCat2Calendar = function() {

		var div = $(this);
		var topOffset = 20;
		var showDelay = 300;
		var hideDelay = 600;

		if(!div.hasClass('wp_cat2calendar')) {
			return $(this);
		}

		div.find('.has-posts').each(function(){

			var popup = $(this).find('.posts').clone();
			var data = {
				popup : popup,
				parent : $(this)
			};

			popup.addClass('wp-cat2calendar-popup')
				.css({
					position: 'absolute',
					opacity : 0
				}).hide();
				
			$('body').append(popup);

			popup.bind('mouseenter', data, function(){
				var o = data.popup;
				o.clearQueue();
			}).bind('mouseleave', data, function(){
				var o = data.popup;
				var p = data.parent;

				var n = o.queue("fx").length;
				if(n < 1) {
					p.trigger('mouseleave');
				}
			});

			$(this).bind('mouseenter', data, function(){
				var o = data.popup;
				var p = data.parent;
				var offset = p.offset();

				var n = o.queue("fx").length;
				var peakTop = offset.top - o.innerHeight() - topOffset + p.innerHeight()/4;
				var peakLeft = (offset.left + p.outerWidth()/2) - o.outerWidth()/2;

				// reposition popup if it is invisible
				if(n < 1) {
					o.css({
						top : peakTop,
						left : peakLeft
					});
					o.delay(showDelay);
				} else {
					o.stop(true);
				}

				o.show().animate({
					opacity : 1,
					top : peakTop + topOffset
				});
			})
			.bind('mouseleave', data, function(){
				var o = data.popup;
				var p = data.parent;
				var offset = p.offset();

				var n = o.queue("fx").length;
				var peakTop = offset.top - o.innerHeight() - topOffset + p.innerHeight()/4;

				if(n < 1) {
					o.delay(hideDelay);
				}

				o.animate({
					opacity : 0,
					top : peakTop
				}, {
					complete : function(){
						$(this).hide();
					}
				});
			});

		});


		return $(this);
	};

})(jQuery);
