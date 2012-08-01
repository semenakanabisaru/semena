/**
 * jGrowl 1.2.5

 */
(function(jQuery) {

	/** jGrowl Wrapper - Establish a base jGrowl Container for compatibility with older releases. **/
	jQuery.jGrowl = function( m , o ) {
		// To maintain compatibility with older version that only supported one instance we'll create the base container.
		if ( jQuery('#jGrowl').size() == 0 ) 
			jQuery('<div id="jGrowl"></div>').addClass( (o && o.position) ? o.position : jQuery.jGrowl.defaults.position ).appendTo('body');

		// Create a notification on the container.
		jQuery('#jGrowl').jGrowl(m,o);
	};



	/** Raise jGrowl Notification on a jGrowl Container **/
	jQuery.fn.jGrowl = function( m , o ) {
		if ( jQuery.isFunction(this.each) ) {
			var args = arguments;

			return this.each(function() {
				var self = this;

				/** Create a jGrowl Instance on the Container if it does not exist **/
				if ( jQuery(this).data('jGrowl.instance') == undefined ) {
					jQuery(this).data('jGrowl.instance', jQuery.extend( new jQuery.fn.jGrowl(), { notifications: [], element: null, interval: null } ));
					jQuery(this).data('jGrowl.instance').startup( this );
				}

				/** Optionally call jGrowl instance methods, or just raise a normal notification **/
				if ( jQuery.isFunction(jQuery(this).data('jGrowl.instance')[m]) ) {
					jQuery(this).data('jGrowl.instance')[m].apply( jQuery(this).data('jGrowl.instance') , jQuery.makeArray(args).slice(1) );
				} else {
					jQuery(this).data('jGrowl.instance').create( m , o );
				}
			});
		};
	};

	jQuery.extend( jQuery.fn.jGrowl.prototype , {

		/** Default JGrowl Settings **/
		defaults: {
			pool: 			0,
			header: 		'',
			group: 			'',
			sticky: 		false,
			position: 		'top-right',
			glue: 			'after',
			theme: 			'default',
			themeState: 	'highlight',
			corners: 		'10px',
			check: 			250,
			life: 			3000,
			closeDuration:  'normal',
			openDuration:   'normal',
			easing: 		'swing',
			closer: 		true,
			closeTemplate: '&times;',
			closerTemplate: '<div>[ close all ]</div>',
			log: 			function(e,m,o) {},
			beforeOpen: 	function(e,m,o) {},
			afterOpen: 		function(e,m,o) {},
			open: 			function(e,m,o) {},
			beforeClose: 	function(e,m,o) {},
			close: 			function(e,m,o) {},
			dont_close:     false, 
			animateOpen: 	{
				opacity: 	'show'
			},
			animateClose: 	{
				opacity: 	'hide'
			}
		},
		
		notifications: [],
		
		/** jGrowl Container Node **/
		element: 	null,
	
		/** Interval Function **/
		interval:   null,
		
		/** Create a Notification **/
		create: 	function( message , o ) {
			var o = jQuery.extend({}, this.defaults, o);

			/* To keep backward compatibility with 1.24 and earlier, honor 'speed' if the user has set it */
			if (typeof o.speed !== 'undefined') {
				o.openDuration = o.speed;
				o.closeDuration = o.speed;
			}

			this.notifications.push({ message: message , options: o });
			
			o.log.apply( this.element , [this.element,message,o] );
		},
		
		render: 		function( notification ) {
			var document_width = ( $(document).width() );
			var self = this;
			var message = notification.message;
			var o = notification.options;

			var notification = jQuery(
				'<div class="jGrowl-notification ' + o.themeState + ' ui-corner-all' + 
				((o.group != undefined && o.group != '') ? ' ' + o.group : '') + '">' +
				'<div class="jGrowl-close">' + o.closeTemplate + '</div>' +
				'<div class="jGrowl-header">' + o.header + '</div>' +
				'<div class="jGrowl-message"></div></div>'
			).data("jGrowl", o).addClass(o.theme).children('div.jGrowl-close').bind("click.jGrowl", function() {
				jQuery(this).parent().trigger('jGrowl.close');
			}).parent();
			
			notification.find('div.jGrowl-message').append(message);
			notification.find('div.jGrowl-close').click(o.close);

			/** Notification Actions **/
			jQuery(notification).bind("mouseover.jGrowl", function() {
				jQuery('div.jGrowl-notification', self.element).data("jGrowl.pause", true);
			}).bind("mouseout.jGrowl", function() {
				jQuery('div.jGrowl-notification', self.element).data("jGrowl.pause", false);
			}).bind('jGrowl.beforeOpen', function() {
				if ( o.beforeOpen.apply( notification , [notification,message,o,self.element] ) != false ) {
					jQuery(this).trigger('jGrowl.open');
				}
			}).bind('jGrowl.open', function() {
				if ( o.open.apply( notification , [notification,message,o,self.element] ) != false ) {
					if ( o.glue == 'after' ) {
						jQuery('div.jGrowl-notification:last', self.element).after(notification);
					} else {
						jQuery('div.jGrowl-notification:first', self.element).before(notification);
					}
					
					jQuery(this).animate(o.animateOpen, o.openDuration, o.easing, function() {
						// Fixes some anti-aliasing issues with IE filters.
						if (jQuery.browser.msie && (parseInt(jQuery(this).css('opacity'), 10) === 1 || parseInt(jQuery(this).css('opacity'), 10) === 0))
							this.style.removeAttribute('filter');

						jQuery(this).data("jGrowl").created = new Date();
						
						jQuery(this).trigger('jGrowl.afterOpen');
					});
				}
			}).bind('jGrowl.afterOpen', function() {
				o.afterOpen.apply( notification , [notification,message,o,self.element] );
			}).bind('jGrowl.beforeClose', function() {
				if ( o.beforeClose.apply( notification , [notification,message,o,self.element] ) != false )
					jQuery(this).trigger('jGrowl.close');
			}).bind('jGrowl.close', function() {
				// Pause the notification, lest during the course of animation another close event gets called.
				jQuery(this).data('jGrowl.pause', true);
				jQuery(this).animate(o.animateClose, o.closeDuration, o.easing, function() {
					jQuery(this).remove();
					var close = o.close.apply( notification , [notification,message,o,self.element] );

					if ( jQuery.isFunction(close) )
						close.apply( notification , [notification,message,o,self.element] );
				});
			}).trigger('jGrowl.beforeOpen');
		
			/** Optional Corners Plugin **/
			if ( o.corners != '' && jQuery.fn.corner != undefined ) jQuery(notification).corner( o.corners );

			/** Add a Global Closer if more than one notification exists **/
			if ( jQuery('div.jGrowl-notification:parent', self.element).size() > 1 && 
				 jQuery('div.jGrowl-closer', self.element).size() == 0 && this.defaults.closer != false ) {
				jQuery(this.defaults.closerTemplate).addClass('jGrowl-closer ui-state-highlight ui-corner-all').addClass(this.defaults.theme)
					.appendTo(self.element).animate(this.defaults.animateOpen, this.defaults.speed, this.defaults.easing)
					.bind("click.jGrowl", function() {
						jQuery(this).siblings().trigger("jGrowl.beforeClose");

						if ( jQuery.isFunction( self.defaults.closer ) ) {
							self.defaults.closer.apply( jQuery(this).parent()[0] , [jQuery(this).parent()[0]] );
						}
					});
			};
		
			//if( $.browser.msie ) {
				//$(this.element).css('left', (document_width - $(this.element).width()-50) + 'px');
			//}			
		},

		/** Update the jGrowl Container, removing old jGrowl notifications **/
		update:	 function() {
			jQuery(this.element).find('div.jGrowl-notification:parent').each( function() {
				if ( jQuery(this).data("jGrowl") != undefined && jQuery(this).data("jGrowl").created != undefined && jQuery(this).data("jGrowl").dont_close==false &&
					 (jQuery(this).data("jGrowl").created.getTime() + parseInt(jQuery(this).data("jGrowl").life))  < (new Date()).getTime() && 
					 jQuery(this).data("jGrowl").sticky != true && 
					 (jQuery(this).data("jGrowl.pause") == undefined || jQuery(this).data("jGrowl.pause") != true) ) {

					// Pause the notification, lest during the course of animation another close event gets called.
					jQuery(this).trigger('jGrowl.beforeClose');
				}
			});

			if ( this.notifications.length > 0 && 
				 (this.defaults.pool == 0 || jQuery(this.element).find('div.jGrowl-notification:parent').size() < this.defaults.pool) )
				this.render( this.notifications.shift() );

			if ( jQuery(this.element).find('div.jGrowl-notification:parent').size() < 2 ) {
				jQuery(this.element).find('div.jGrowl-closer').animate(this.defaults.animateClose, this.defaults.speed, this.defaults.easing, function() {
					jQuery(this).remove();
				});
			}
		},

		/** Setup the jGrowl Notification Container **/
		startup:	function(e) {
			this.element = jQuery(e).addClass('jGrowl').append('<div class="jGrowl-notification"></div>');
			this.interval = setInterval( function() { 
				jQuery(e).data('jGrowl.instance').update(); 
			}, parseInt(this.defaults.check));
			
			if (jQuery.browser.msie && parseInt(jQuery.browser.version) < 7 && !window["XMLHttpRequest"]) {
				jQuery(this.element).addClass('ie6');
			}
			

		},

		/** Shutdown jGrowl, removing it and clearing the interval **/
		shutdown:   function() {
			jQuery(this.element).removeClass('jGrowl').find('div.jGrowl-notification').remove();
			clearInterval( this.interval );
		},
		
		close: 	function() {
			jQuery(this.element).find('div.jGrowl-notification').each(function(){
				jQuery(this).trigger('jGrowl.beforeClose');
			});
		},
		
		closeAllNow: 	function() {
			jQuery(this.element).find('div.jGrowl-notification').each(function(){
				jQuery(this).trigger('jGrowl.beforeClose');
				jQuery(this).remove();
			});
		}
	});
	
	/** Reference the Defaults Object for compatibility with older versions of jGrowl **/
	jQuery.jGrowl.defaults = jQuery.fn.jGrowl.prototype.defaults;

})(jQuery);