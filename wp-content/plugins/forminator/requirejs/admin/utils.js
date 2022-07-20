(function ($) {
	window.empty = function (what) { return "undefined" === typeof what ? true : !what; };
	window.count = function (what) { return "undefined" === typeof what ? 0 : (what && what.length ? what.length : 0); };
	window.stripslashes = function (what) {
		return (what + '')
		.replace(/\\(.?)/g, function (s, n1) {
			switch (n1) {
				case '\\':
					return '\\'
				case '0':
					return '\u0000'
				case '':
					return ''
				default:
					return n1
			}
		});
	};
	window.forminator_array_value_exists = function ( array, key ) {
		return ( !_.isUndefined( array[ key ] ) && ! _.isEmpty( array[ key ] ) );
	};
	window.decodeHtmlEntity = function(str) {
		if( typeof str === "undefined" ) return str;

		return str.replace( /&#(\d+);/g, function( match, dec ) {
			return String.fromCharCode( dec );
		});
	};
	window.encodeHtmlEntity = function(str) {
		if( typeof str === "undefined" ) return str;
		var buf = [];
		for ( var i=str.length-1; i>=0; i-- ) {
			buf.unshift( ['&#', str[i].charCodeAt(), ';'].join('') );
		}
		return buf.join('');
	};
	window.singularPluralText = function( count, singular, plural ) {
		var txt  = '';

		if ( count < 2 ) {
			txt = singular;
		} else {
			txt = plural;
		}

		return count + ' ' + txt;
	};

	formintorjs.define([
		'text!admin/templates/popups.html'
	], function ( popupTpl ) {
		var Utils = {
			/**
			 * generated field id
			 * {
			 *  type : [id,id]
			 * }
			 * sample
			 * {text:[1,2,3],phone:[1,2,3]}
			 */
			fields_ids: [],

			/**
			 * Forminator_Google_font_families
			 * @since 1.0.5
			 */
			google_font_families: [],
			/*
			 * Returns if touch device ( using wp_is_mobile() )
			 */
			is_touch: function () {
				return Forminator.Data.is_touch;
			},

			/*
			 * Returns if window resized for browser
			 */
			is_mobile_size: function () {
				if ( window.screen.width <= 782 ) return true;

				return false;
			},

			/*
			 * Return if touch or windows mobile width
			 */
			is_mobile: function () {
				if( Forminator.Utils.is_touch() || Forminator.Utils.is_mobile_size() ) return true;

				return false;
			},

			/*
			 * Extend default underscore template with mustache style
			 */
			template: function( markup ) {
				// Each time we re-render the dynamic markup we initialize mustache style
				_.templateSettings = {
					evaluate : /\{\[([\s\S]+?)\]\}/g,
					interpolate : /\{\{([\s\S]+?)\}\}/g
				};

				return _.template( markup );
			},

			/*
			 * Extend default underscore template with PHP
			 */
			template_php: function( markup ) {
				var oldSettings = _.templateSettings,
				tpl = false;

				_.templateSettings = {
					interpolate : /<\?php echo (.+?) \?>/g,
					evaluate: /<\?php (.+?) \?>/g
				};

				tpl = _.template(markup);

				_.templateSettings = oldSettings;

				return function(data){
					_.each(data, function(value, key){
						data['$' + key] = value;
					});

					return tpl(data);
				};
			},

			/**
			 * Capitalize string
			 *
			 * @param value
			 * @returns {string}
			 */
			ucfirst: function( value ) {
				return value.charAt(0).toUpperCase() + value.slice(1);
			},

			/*
			 * Returns slug from title
			 */
			get_slug: function ( title ) {
				title = title.replace( ' ', '-' );
				title = title.replace( /[^-a-zA-Z0-9]/, '' );
				return title;
			},

			/*
			 * Returns slug from title
			 */
			sanitize_uri_string: function ( string ) {
				// Decode URI components
				var decoded = decodeURIComponent( string );

				// Replace interval with -
				decoded = decoded.replace( /-/g, ' ' );

				return decoded;
			},

			/*
			 * Return URL param value
			 */
			get_url_param: function ( param ) {
				var page_url = window.location.search.substring(1),
					url_params = page_url.split('&')
				;

				for ( var i = 0; i < url_params.length; i++ ) {
					var param_name = url_params[i].split('=');
					if ( param_name[0] === param ) {
						return param_name[1];
					}
				}

				return false;
			},

			/**
			 * Check if email acceptable by WP
			 * @param value
			 * @returns {boolean}
			 */
			is_email_wp: function (value) {
				if (value.length < 6) {
					return false;
				}

				// Test for an @ character after the first position
				if (value.indexOf('@', 1) < 0) {
					return false;
				}

				// Split out the local and domain parts
				var parts = value.split('@', 2);

				// LOCAL PART
				// Test for invalid characters
				if (!parts[0].match(/^[a-zA-Z0-9!#$%&'*+\/=?^_`{|}~\.-]+$/)) {
					return false;
				}

				// DOMAIN PART
				// Test for sequences of periods
				if (parts[1].match(/\.{2,}/)) {
					return false;
				}

				var domain = parts[1];
				// Split the domain into subs
				var subs = domain.split('.');
				if (subs.length < 2) {
					return false;
				}

				var subsLen = subs.length;
				for (var i = 0; i < subsLen; i++) {
					// Test for invalid characters
					if (!subs[i].match(/^[a-z0-9-]+$/i)) {
						return false;
					}
				}
				return true;
			},

			forminator_select2_tags: function( $el, options ) {
				var select = $el.find( 'select.sui-select.fui-multi-select' );

				// SELECT2 forminator-ui-tags
				select.each( function() {
					var select       = $( this ),
						getParent    = select.closest( '.sui-modal-content' ),
						getParentId  = getParent.attr( 'id' ),
						selectParent = ( getParent.length ) ? $( '#' + getParentId ) : $( 'SUI_BODY_CLASS' ),
						hasSearch    = ( 'true' === select.attr( 'data-search' ) ) ? 0 : -1,
						isSmall      = select.hasClass( 'sui-select-sm' ) ? 'sui-select-dropdown-sm' : '';

					options = _.defaults( options, {
						dropdownParent: selectParent,
						minimumResultsForSearch: hasSearch,
						dropdownCssClass: isSmall
					});

					// reorder-support, it will preserve order based on user tags added
					if ( select.attr( 'data-reorder' ) ) {
						select.on( 'select2:select', function( e ) {
							var elm  = e.params.data.element,
							    $elm = $( elm ),
							    $t   = select;

							$t.append( $elm );
							$t.trigger( 'change.select2' );
						});
					}

					select.SUIselect2( options );
				});
			},

			forminator_select2_custom: function ($el, options) {
				// SELECT2 custom
				$el.find( 'select.sui-select.custom-select2' ).each( function() {
					var select       = $( this ),
						getParent    = select.closest( '.sui-modal-content' ),
						getParentId  = getParent.attr( 'id' ),
						selectParent = ( getParent.length ) ? $( '#' + getParentId ) : $( 'body' ),
						hasSearch    = ( 'true' === select.attr( 'data-search' ) ) ? 0 : -1,
						isSmall      = select.hasClass( 'sui-select-sm' ) ? 'sui-select-dropdown-sm' : '';

					options = _.defaults( options, {
						dropdownParent: selectParent,
						minimumResultsForSearch: hasSearch,
						dropdownCssClass: isSmall
					});

					// Reorder-support, it will preserve order based on user tags added.
					if ( select.attr( 'data-reorder' ) ) {
						select.on( 'select2:select', function( e ) {
							var elm  = e.params.data.element,
							    $elm = $(elm),
							    $t   = $( this );
							$t.append( $elm );
							$t.trigger( 'change.select2' );
						});
					}

					select.SUIselect2( options );
				});
			},

			/*
			 * Initialize Select 2
			 */
			init_select2: function(){
				var self = this;
				if ( 'object' !== typeof window.SUI ) return;
			},

			load_google_fonts: function (callback) {
				var self = this;
				$.ajax({
					url : Forminator.Data.ajaxUrl,
					type: "POST",
					data: {
						action: "forminator_load_google_fonts",
						_wpnonce: Forminator.Data.gFontNonce
					}
				}).done(function (result) {
					if (result.success === true) {
						// cache result
						self.google_font_families = result.data;
					}
					// do callback even font_families is empty
					callback.apply(result, [self.google_font_families]);
				});
			},

			sui_delegate_events: function() {
				var self = this;
				if ( 'object' !== typeof window.SUI ) return;

				// Time it out
				setTimeout( function() {
					// Rebind Accordion scripts.
					SUI.suiAccordion($('.sui-accordion'));

					// Rebind Tabs scripts.
					SUI.suiTabs( $( '.sui-tabs' ) );

					// Rebind Select2 scripts.
					$( 'select.sui-select[data-theme="icon"]' ).each( function() {
						SUI.select.initIcon( $( this ) );
					});

					$( 'select.sui-select[data-theme="color"]' ).each( function() {
						SUI.select.initColor( $( this ) );
					});

					$( 'select.sui-select[data-theme="search"]' ).each( function() {
						SUI.select.initSearch( $( this ) );
					});

					$( 'select.sui-select:not([data-theme]):not(.custom-select2):not(.fui-multi-select)' ).each( function() {
						SUI.select.init( $( this ) );
					});

					// Rebind Variables scripts.
					$( 'select.sui-variables' ).each( function() {
						SUI.select.initVars( $( this ) );
					});

					// Rebind Circle scripts.
					SUI.loadCircleScore( $( '.sui-circle-score' ) );

					// Rebind Password scripts.
					SUI.showHidePassword();

				}, 50);
			},
		};

		var Popup = {
			$popup: {},
			_deferred: {},

			initialize: function () {

				var tpl = Forminator.Utils.template( $( popupTpl ).find( '#popup-tpl' ).html() );

				if ( ! $( "#forminator-popup" ).length ) {
					$( "main.sui-wrap" ).append( tpl({}) );
				} else {
					$( "#forminator-popup" ).remove();
					this.initialize();
				}

				this.$popup = $( "#forminator-popup" );
				this.$popupId = 'forminator-popup';
				this.$focusAfterClosed = 'wpbody-content';

			},

			open: function ( callback, data, size, title ) {
				this.data             = data;
				this.title            = '';
				this.action_text      = '';
				this.action_callback  = false;
				this.action_css_class = '';
				this.has_custom_box   = false;
				this.has_footer       = true;

				var header_tpl = '';

				switch ( title ) {
					case 'inline':
						header_tpl = Forminator.Utils.template( $( popupTpl ).find( '#popup-header-inline-tpl' ).html() );
						break;

					case 'center':
						header_tpl = Forminator.Utils.template( $( popupTpl ).find( '#popup-header-tpl' ).html() );
						break;
				}

				if ( !_.isUndefined( this.data ) ) {
					if ( !_.isUndefined( this.data.title ) ) {
						this.title = this.data.title;
					}

					if ( !_.isUndefined( this.data.has_footer ) ) {
						this.has_footer = this.data.has_footer;
					}

					if ( !_.isUndefined( this.data.action_callback ) &&
						!_.isUndefined( this.data.action_text ) ) {
						this.action_callback = this.data.action_callback;
						this.action_text = this.data.action_text;
						if ( !_.isUndefined( this.data.action_css_class ) ) {
							this.action_css_class = this.data.action_css_class;
						}
					}

					if ( !_.isUndefined( this.data.has_custom_box ) ) {
						this.has_custom_box = this.data.has_custom_box;
					}
				}

				this.initialize();

				// restart base structure
				if ( '' !== header_tpl ) {
					this.$popup.find( '.sui-box' ).html( header_tpl({
						title: this.title
					}) );
				}

				var self = this,
					close_click = function () {
						self.close();
						return false;
					}
				;

				// Set modal size
				if ( size ) {
					this.$popup.closest( '.sui-modal' )
						.addClass( 'sui-modal-' + size );
				}

				if ( this.has_custom_box ) {
					callback.apply( this.$popup.find( '.sui-box' ).get(), data );
				} else {
					var box_markup = '<div class="sui-box-body">' +
						'</div>';

					if( this.has_footer ) {
						box_markup += '<div class="sui-box-footer">' +
							'<button class="sui-button forminator-popup-cancel">' + Forminator.l10n.popup.cancel +'</button>' +
						'</div>';
					}

					this.$popup.find('.sui-box').append( box_markup );
					callback.apply(this.$popup.find(".sui-box-body").get(), data);
				}

				// Add additional Button if callback_action available
				if (this.action_text && this.action_callback) {
					var action_callback = this.action_callback;
					this.$popup.find('.sui-box-footer').append(
						'<div class="sui-actions-right">' +
							'<button class="forminator-popup-action sui-button ' + this.action_css_class + '">' + this.action_text + '</button>' +
						'</div>'
					);
					this.$popup.find('.forminator-popup-action').on('click', function(){
						if( action_callback ) {
							action_callback.apply();
						}
						self.close();
					});
				} else {
					this.$popup.find('.forminator-popup-action').remove();
				}

				// Add closing event
				this.$popup.find( ".forminator-popup-close" ).on( "click", close_click );
				this.$popup.find( ".forminator-popup-cancel" ).on( "click", close_click );
				this.$popup.on( "click", '.forminator-popup-cancel', close_click );

				// Open Modal
				SUI.openModal(
					this.$popupId,
					this.$focusAfterClosed,
					undefined,
					true,
					true
				);

				// Delegate SUI events
				Forminator.Utils.sui_delegate_events();

				this._deferred = new $.Deferred();
				return this._deferred.promise();
			},

			close: function ( result, callback ) {
				var self = this;
				// Close Modal
				SUI.closeModal();

				setTimeout(function () {

					// Remove modal size.
					self.$popup.closest( '.sui-modal' )
						.removeClass( 'sui-modal-sm' )
						.removeClass( 'sui-modal-md' )
						.removeClass( 'sui-modal-lg' )
						.removeClass( 'sui-modal-xl' );

					if( callback ) {
						callback.apply();
					}
				}, 300);

				this._deferred.resolve( this.$popup, result );
			}
		};

		var Notification = {
			$notification: {},
			_deferred: {},

			initialize: function () {

				if ( ! $( ".sui-floating-notices" ).length ) {

					$( "main.sui-wrap" )
						.prepend(
							'<div class="sui-floating-notices">' +
								'<div role="alert" id="forminator-floating-notification" class="sui-notice" aria-live="assertive"></div>' +
							'</div>'
						);

				} else {
					$( ".sui-floating-notices" ).remove();
					this.initialize();
				}

				this.$notification = $( "#forminator-floating-notification" );
			},

			open: function ( type, text, closeTime ) {
				var self = this;
				var noticeTimeOut = closeTime;

				if ( ! _.isUndefined( closeTime ) ) {
					noticeTimeOut = 5000;
				}

				this.uniq = 'forminator-floating-notification';
				this.text = '<p>' + text + '</p>';
				this.type = '';
				this.time = closeTime || 5000;

				if ( ! _.isUndefined( type ) && '' !== type ) {
					this.type = type;
				}

				if ( ! _.isUndefined( closeTime ) ) {
					this.time = closeTime;
				}

				this.opts = {
					type: this.type,
					autoclose: {
						show: true,
						timeout: this.time
					}
				};

				this.initialize();

				SUI.openNotice(
					this.uniq,
					this.text,
					this.opts
				);


				setTimeout( function () {
					self.close();
				}, 3000 );

				this._deferred = new $.Deferred();
				return this._deferred.promise();
			},

			close: function ( result ) {
				this._deferred.resolve( this.$popup, result );
			}
		};

		var Integrations_Popup = {
			$popup: {},
			_deferred: {},

			initialize: function () {
				var tpl = Forminator.Utils.template( $( popupTpl ).find( '#popup-integration-tpl' ).html() );

				if ( ! $( "#forminator-integration-popup" ).length ) {

					$( "main.sui-wrap" ).append( tpl({
						provider_image: '',
						provider_image2: '',
						provider_title: ''
					}));

				} else {
					$( "#forminator-integration-popup" ).remove();
					this.initialize();
				}

				this.$popup = $( "#forminator-integration-popup" );
				this.$popupId = 'forminator-integration-popup';
				this.$focusAfterClosed = 'forminator-integrations-page';

			},

			open: function ( callback, data, className ) {
				this.data             = data;
				this.title            = '';
				this.image            = '';
				this.image_x2         = '';
				this.action_text      = '';
				this.action_callback  = false;
				this.action_css_class = '';
				this.has_custom_box   = false;
				this.has_footer       = true;

				if ( !_.isUndefined( this.data ) ) {
					if ( !_.isUndefined( this.data.title ) ) {
						this.title = this.data.title;
					}

					if ( !_.isUndefined( this.data.image ) ) {
						this.image = this.data.image;
					}

					if ( !_.isUndefined( this.data.image_x2 ) ) {
						this.image_x2 = this.data.image_x2;
					}
				}

				this.initialize();

				// restart base structure
				var tpl = Forminator.Utils.template( $( popupTpl ).find( '#popup-integration-content-tpl' ).html() );

				this.$popup.find('.sui-box').html(tpl({
					image: this.image,
					image_x2: this.image_x2,
					title: this.title
				}));

				var self = this,
					close_click = function () {
						self.close();
						return false;
					}
				;

				// Add custom class
				if ( className ) {
					this.$popup
						.addClass( className )
					;
				}

				callback.apply(this.$popup.get(), data);

				// Add additional Button if callback_action available
				if (this.action_text && this.action_callback) {
					var action_callback = this.action_callback;
					this.$popup.find('.sui-box-footer').append(
						'<button class="forminator-popup-action sui-button ' + this.action_css_class + '">' + this.action_text + '</button>'
					);
					this.$popup.find('.forminator-popup-action').on('click', function(){
						if( action_callback ) {
							action_callback.apply();
						}
						self.close();
					});
				} else {
					this.$popup.find('.forminator-popup-action').remove();
				}

				// Add closing event
				this.$popup.find( ".sui-dialog-close" ).on( "click", close_click );
				this.$popup.on( "click", '.forminator-popup-cancel', close_click );

				// Open Modal
				SUI.openModal( this.$popupId, this.$focusAfterClosed );

				// Delegate SUI events
				Forminator.Utils.sui_delegate_events();

				this._deferred = new $.Deferred();
				return this._deferred.promise();
			},

			close: function ( result, callback ) {

				// Refrest add-on list
				Forminator.Events.trigger( "forminator:addons:reload" );

				// Close Modal
				SUI.closeModal();

				setTimeout(function () {
					if( callback ) {
						callback.apply();
					}
				}, 300);

				this._deferred.resolve( this.$popup, result );
			}
		};

		var Stripe_Popup = {
			$popup: {},
			_deferred: {},

			initialize: function () {
				var tpl = Forminator.Utils.template( $( popupTpl ).find( '#popup-stripe-tpl' ).html() );

				if ( ! $( "#forminator-stripe-popup" ).length ) {

					$( "main.sui-wrap" ).append( tpl({
						provider_image: '',
						provider_image2: '',
						provider_title: ''
					}));

				} else {
					$( "#forminator-stripe-popup" ).remove();
					this.initialize();
				}

				this.$popup = $( "#forminator-stripe-popup" );
				this.$popupId = 'forminator-stripe-popup';
				this.$focusAfterClosed = 'wpbody-content';

			},

			open: function ( callback, data ) {
				this.data             = data;
				this.title            = '';
				this.image            = '';
				this.image_x2         = '';
				this.action_text      = '';
				this.action_callback  = false;
				this.action_css_class = '';
				this.has_custom_box   = false;
				this.has_footer       = true;

				if ( !_.isUndefined( this.data ) ) {
					if ( !_.isUndefined( this.data.title ) ) {
						this.title = this.data.title;
					}

					if ( !_.isUndefined( this.data.image ) ) {
						this.image = this.data.image;
					}

					if ( !_.isUndefined( this.data.image_x2 ) ) {
						this.image_x2 = this.data.image_x2;
					}
				}

				this.initialize();

				// restart base structure
				var tpl = Forminator.Utils.template( $( popupTpl ).find( '#popup-stripe-content-tpl' ).html() );

				this.$popup.find('.sui-box').html(tpl({
					image: this.image,
					image_x2: this.image_x2,
					title: this.title
				}));

				this.$popup.find('.sui-box-footer').css({
					'padding-top': '0',
				});

				var self = this,
				    close_click = function () {
					    self.close();
					    return false;
				    }
				;

				callback.apply(this.$popup.get(), data);

				// Add additional Button if callback_action available
				if (this.action_text && this.action_callback) {
					var action_callback = this.action_callback;
					this.$popup.find('.sui-box-footer').append(
						'<div class="sui-actions-right">' +
						'<button class="forminator-popup-action sui-button ' + this.action_css_class + '">' + this.action_text + '</button>' +
						'</div>'
					);
					this.$popup.find('.forminator-popup-action').on('click', function(){
						if( action_callback ) {
							action_callback.apply();
						}
						self.close();
					});
				} else {
					this.$popup.find('.forminator-popup-action').remove();
				}

				// Add closing event
				this.$popup.find( ".forminator-popup-close" ).on( "click", close_click );
				this.$popup.on( "click", '.forminator-popup-cancel', close_click );

				// Open Modal
				SUI.openModal(
					this.$popupId,
					this.$focusAfterClosed,
					undefined,
					true,
					true
				);

				// Delegate SUI events
				Forminator.Utils.sui_delegate_events();

				this._deferred = new $.Deferred();
				return this._deferred.promise();
			},

			close: function ( result, callback ) {

				// Close Modal
				SUI.closeModal();

				var self = this;

				setTimeout(function () {

					// Remove modal size.
					self.$popup.closest( '.sui-modal' )
						.removeClass( 'sui-modal-sm' )
						.removeClass( 'sui-modal-md' )
						.removeClass( 'sui-modal-lg' )
						.removeClass( 'sui-modal-xl' );

					if( callback ) {
						callback.apply();
					}
				}, 300);

				this._deferred.resolve( this.$popup, result );
			}
		};

		return {
			Utils: Utils,
			Popup: Popup,
			Integrations_Popup: Integrations_Popup,
			Stripe_Popup: Stripe_Popup,
			Notification: Notification
		};
	});

})(jQuery);
