/*!
 * gQuery Chrony - A Count Down Plugin - http://wbotelhos.com/chrony
 * ------------------------------------------------------------------
 *
 * gQuery Chrony is a plugin that creates a chronometer.
 *
 * Licensed under The MIT License
 *
 * @version        0.2.0
 * @since          2011.10.23
 * @author         Washington Botelho
 * @documentation  wbotelhos.com/chrony
 * @twitter        twitter.com/wbotelhos
 *
 * Usage with default values:
 * ------------------------------------------------------------------
 * gQuery('#time').chrony({ hour: 1, minute: 2, second: 3 });
 *
 * <div id="time"></div>
 *
 */

;(function(gQuery) {

	var methods = {
		init: function(options) {
			return this.each(function() {

				var self	= this,
					$this	= gQuery(self);

				self.opt = gQuery.extend(true, {}, gQuery.fn.chrony.defaults, options);

				if ($this.data('chrony')) {
					return;
				}

				$this.data('chrony', true);

				var opt			= self.opt,
					separator	= '<span style="float: left;">:</span>';

				if (opt.text) {
					var text = opt.text.split(':');

					if (text.length != 3) {
						gQuery.error('The format must be the following HH:mm:ss!');
					}

					opt.second = text[2];
					opt.minute = text[1];
					opt.hour = text[0];
				} else if (opt.hours) {
					if (opt.hours >= 24) {
						opt.hour = 23;
						opt.minute = 59;
						opt.second = 59;
					} else {
						opt.second = 0;
						opt.minute = 0;
						opt.hour = opt.hours;
					}
				} else if (opt.minutes) {
					opt.second = 0;
					opt.minute = opt.minutes % 60;
					opt.hour = (opt.minutes - opt.minute) / 60;
				} else if (opt.seconds) {
					opt.second = opt.seconds % 60;
					opt.minute = ((opt.seconds - opt.second) / 60) % 60;
					opt.hour = ((opt.seconds - opt.second) - (opt.minute * 60)) / 60 / 60;
				}

				var message = methods.checkTime(opt.hour, opt.minute, opt.second);
	
				if (message) {
					$this.html('<div style="color: #F00; font-size: 9px;">Number out of range!</div>');
					gQuery.error(message);
				}

				var hour		= methods.getNumber(opt.hour),
					minute		= methods.getNumber(opt.minute),
					second		= methods.getNumber(opt.second),
					$hour		= gQuery('<div />', { id: 'hour', html: hour, style: 'float: left;' }),
					$minute		= gQuery('<div />', { id: 'minute', html: minute, style: 'float: left;' }),
					$second		= gQuery('<div />', { id: 'second', html: second, style: 'float: left;' }),
					timer		= 0;

				if (opt.displayHours) {
					$this.append($hour);

					if (opt.displayMinutes || opt.displaySeconds) {
						$this.append(separator);
					}
				}

				if (opt.displayMinutes) {
					$this.append($minute);

					if (opt.displaySeconds) {
						$this.append(separator);
					}
				}

				if (opt.displaySeconds) {
					$this.append($second);
				}

				var $separators = $this.children('span');

				methods.checkAlert.call(self, hour, minute, second);
                gQuery(this).data('options',opt);
				timer = setInterval(function() {
				    if(self.opt.paused)
						return false;
					if(self.opt.destroy)
					{
					    gQuery($this).html('');
						clearInterval(timer);
						return false;
					}
					if (self.opt.blink) {
						$separators.fadeOut(self.opt.blinkTime, function() {
						    gQuery(this).fadeIn(self.opt.blinkTime);
						});
					}

					if (second == 0) {
						if (minute == 0) {
							if (hour > 0) {
								hour = methods.getNumber(hour - self.opt.decrement);
								minute = 59;
								second = 59;

								$hour.html(hour);
								$minute.html(minute);
								$second.html(second);
							}
						} else {
							minute = methods.getNumber(minute - self.opt.decrement);
							second = 59;

							$minute.html(minute);
							$second.html(second);
						}
					} else {
						second = methods.getNumber(second - self.opt.decrement);
						$second.html(second);
					}

					if (self.opt.finish && second == 0 && minute == 0 && hour == 0) {
						self.opt.finish.call(self);

						clearInterval(timer);
					}

					methods.checkAlert.call(self, hour, minute, second);
				}, 1000);
			});
		}, checkAlert: function(hour, minute, second) {
			var $this	= gQuery(this),
				alert	= this.opt.alert;

			if (alert && $this.css('color') != '') {
				if (hour <= alert.hour && minute <= alert.minute && second <= alert.second) {
					$this.css('color', alert.color);
				}
			}
		}, checkTime: function(hour, minute, second) {
			if (hour < 0 || hour > 24) {
				return 'The hour must be >= 0 or <= 24';
			}

			if (minute < 0 || minute > 59) {
				return 'The minute must be >= 0 or <= 59';
			}

			if (second < 0 || second > 59) {
				return 'The second must be >= 0 or <= 59';
			}
		}, getNumber: function(number) {
			return (number < 10) ? '0' + ((number < 0) ? '0' : number) : number;
		}, set: function(options) {
			return this.each(function() {
				this.opt = gQuery.extend({}, gQuery.fn.chrony.defaults, gQuery(this).data('options'), options);
                gQuery(this).data('options',this.opt)
			});
		}, get:function(optionName){
			//console.log(jQuery(this).data('options'));
			var tOptions = gQuery(this).data('options');
			var optionValue = null;
			for(var i in tOptions)
			{
				if(optionName == i)
				{
					optionValue = tOptions[i];
					break;
				}
			}
			return optionValue;
		}
	};

	gQuery.fn.chrony = function(method) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
		} else {
			gQuery.error('Method ' + method + ' does not exist!');
		} 
	};

	gQuery.fn.chrony.defaults = {
		alert			: { color: '#F00', hour: 0, minute: 0, second: 10 },
		blink			: false,
		blinkTime		: 130,
		finish			: undefined,
		decrement		: 1,
		displayHours	: true,
		displayMinutes	: true,
		displaySeconds	: true,
		hour			: 0,
		hours			: undefined,
		minute			: 0,
		minutes			: undefined,
		second			: 0,
		seconds			: undefined,
		text			: undefined,
		paused			: false,
		destroy			: false
	};

})(gQuery);