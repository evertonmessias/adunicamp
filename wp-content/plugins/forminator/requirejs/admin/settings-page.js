(function ($) {
	formintorjs.define([
	], function() {
		var SettingsPage = Backbone.View.extend({
			el: '.wpmudev-forminator-forminator-settings, .wpmudev-forminator-forminator-addons',
			events: {
				'click .sui-side-tabs label.sui-tab-item input': 'sidetabs',
				"click .sui-sidenav .sui-vertical-tab a": "sidenav",
				"change .sui-sidenav select.sui-mobile-nav": "sidenav_select",
				"click .stripe-connect-modal" : 'open_stripe_connect_modal',
				"click .paypal-connect-modal" : 'open_paypal_connect_modal',
				"click .forminator-stripe-connect" : 'connect_stripe',
				"click .disconnect_stripe": "disconnect_stripe",
				"click .forminator-paypal-connect" : 'connect_paypal',
				"click .disconnect_paypal": "disconnect_paypal",
				'click button.sui-tab-item': 'buttonTabs',
				"click .forminator-toggle-unsupported-settings": "show_unsupported_settings",
				"click .forminator-dismiss-unsupported": "hide_unsupported_settings",
				"click button.addons-configure": "open_configure_connect_modal",
			},
			initialize: function () {
				var self = this;

				// only trigger on settings page
				if (!$('.wpmudev-forminator-forminator-settings').length && !$('.wpmudev-forminator-forminator-addons').length ) {
					return;
				}
				// on submit
				this.$el.find('.forminator-settings-save').submit( function(e) {
					e.preventDefault();

					var $form = $( this ),
						nonce = $form.find('.wpmudev-action-done').data( "nonce" ),
						action = $form.find('.wpmudev-action-done').data( "action" ),
						title = $form.find('.wpmudev-action-done').data( "title" ),
						isReload = $form.find('.wpmudev-action-done').data( "isReload" )
					;

					self.submitForm( $( this ), action, nonce, title, isReload );
				});

				var hash = window.location.hash;
				if( ! _.isUndefined( hash ) && ! _.isEmpty( hash ) ) {
					this.sidenav_go_to( hash.substring(1), true );
				}

				this.renderHcaptcha();
				this.render( 'v2' );
				this.render( 'v2-invisible' );
				this.render( 'v3' );

				// Save captcha tab last saved
				this.captchaTab();
			},

			captchaTab: function() {
				var $captchaType = this.$el.find( 'input[name="captcha_tab_saved"]' ),
					$tabs	   	 = this.$el.find( 'button.captcha-main-tab' )
				;

				$tabs.on( 'click', function ( e ) {
					e.preventDefault();
					$captchaType.val( $( this ).data( 'tab-name' ) );
				} );
			},

			render: function ( captcha ) {
				var self = this,
					$container = this.$el.find( '#' + captcha + '-recaptcha-preview' )
				;

				var preloader =
					'<p class="fui-loading-dialog">' +
					'<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>' +
					'</p>'
				;

				$container.html( preloader );

				$.ajax({
					url : Forminator.Data.ajaxUrl,
					type: "POST",
					data: {
						action: "forminator_load_recaptcha_preview",
						captcha: captcha,
					}
				}).done(function (result) {
					if( result.success ) {
						$container.html( result.data );
					}
				});
			},

			renderHcaptcha: function () {
				var $hcontainer = this.$el.find( '#hcaptcha-preview' );

				var preloader =
					'<p class="fui-loading-dialog">' +
					'<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>' +
					'</p>'
				;

				$hcontainer.html( preloader );

				$.ajax({
					url : Forminator.Data.ajaxUrl,
					type: "POST",
					data: {
						action: "forminator_load_hcaptcha_preview",
					}
				}).done(function (result) {
					if( result.success ) {
						$hcontainer.html( result.data );
					}
				});
			},

			submitForm: function( $form, action, nonce, title, isReload ) {
				var data = {},
					self = this
				;

				data.action = 'forminator_save_' + action + '_popup';
				data._ajax_nonce = nonce;

				var ajaxData = $form.serialize() + '&' + $.param(data);

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: ajaxData,
					beforeSend: function() {
						$form.find('.sui-button').addClass('sui-button-onload');
					},
					success: function( result ) {
						var markup = _.template( '<strong>{{ tab }}</strong> {{ Forminator.l10n.commons.update_successfully }}' );

						Forminator.Notification.open( 'success', markup({
							tab: title
						}), 4000 );

						if( action === "captcha" ) {

							$captcha_tab_saved = $form.find( 'input[name="captcha_tab_saved"]' ).val();
							$captcha_tab_saved = '' === $captcha_tab_saved ? 'recaptcha' : $captcha_tab_saved;
							if ( 'recaptcha' === $captcha_tab_saved ) {
								self.render( 'v2' );
								self.render( 'v2-invisible' );
								self.render( 'v3' );
							} else if ( 'hcaptcha' === $captcha_tab_saved ) {
								self.renderHcaptcha();
							}

						}

						if (isReload) {
							window.location.reload();
						}
					},
					error: function ( error ) {
						Forminator.Notification.open( 'error', Forminator.l10n.commons.update_unsuccessfull, 4000 );
					}
				}).always(function(){
					$form.find('.sui-button').removeClass('sui-button-onload');
				});
			},

			sidetabs: function( e ) {
				var $this      = this.$( e.target ),
					$label     = $this.parent( 'label' ),
					$data      = $this.data( 'tab-menu' ),
					$wrapper   = $this.closest( '.sui-side-tabs' ),
					$alllabels = $wrapper.find( '.sui-tabs-menu .sui-tab-item' ),
					$allinputs = $alllabels.find( 'input' )
				;

				if ( $this.is( 'input' ) ) {

					$alllabels.removeClass( 'active' );
					$allinputs.removeAttr( 'checked' );
					$wrapper.find( '.sui-tabs-content > div' ).removeClass( 'active' );

					$label.addClass( 'active' );
					$this.prop( 'checked', 'checked' );

					if ( $wrapper.find( '.sui-tabs-content div[data-tab-content="' + $data + '"]' ).length ) {
						$wrapper.find( '.sui-tabs-content div[data-tab-content="' + $data + '"]' ).addClass( 'active' );
					}
				}
			},

			sidenav: function( e ) {
				var tab_name = $( e.target ).data( 'nav' );
				if ( tab_name ) {
					this.sidenav_go_to( tab_name, true );
				}
				e.preventDefault();

			},

			sidenav_select: function( e ) {
				var tab_name = $(e.target).val();
				if ( tab_name ) {
					this.sidenav_go_to( tab_name, true );
				}
				e.preventDefault();

			},

			sidenav_go_to: function( tab_name, update_history ) {

				var $tab 	 = this.$el.find( 'a[data-nav="' + tab_name + '"]' ),
				    $sidenav = $tab.closest( '.sui-vertical-tabs' ),
				    $tabs    = $sidenav.find( '.sui-vertical-tab' ),
				    $content = this.$el.find( '.sui-box[data-nav]' ),
				    $current = this.$el.find( '.sui-box[data-nav="' + tab_name + '"]' );

				if ( update_history ) {
					history.pushState( { selected_tab: tab_name }, 'Global Settings', 'admin.php?page=forminator-settings&section=' + tab_name );
				}

				$tabs.removeClass( 'current' );
				$content.hide();

				$tab.parent().addClass( 'current' );
				$current.show();
			},

			open_configure_connect_modal: function ( e ) {
				var $target = $(e.target),
					actions = $target.data('action');
				if ( 'stripe-connect-modal' === actions ) {
					this.open_stripe_connect_modal( e );
				} else if ( 'paypal-connect-modal' === actions ) {
					this.open_paypal_connect_modal( e );
				}
			},

			open_stripe_connect_modal: function (e) {

				e.preventDefault();

				var self = this;
				var $target  = $(e.target);
				var image    = $target.data('modalImage');
				var image_x2 = $target.data('modalImageX2');
				var title    = $target.data('modalTitle');
				var nonce    = $target.data('modalNonce');

				var popup_options = {
					title: title,
					image: image,
					image_x2: image_x2
				}

				Forminator.Stripe_Popup.open( function () {
					var popup = $(this);
					self.render_stripe_connect_modal_content(popup, nonce, {});
				}, popup_options );

				return false;
			},

			render_stripe_connect_modal_content: function (popup, popup_nonce, request_data) {
				var self = this;
				request_data.action      = 'forminator_stripe_settings_modal';
				request_data._ajax_nonce = popup_nonce;

				var preloader =
					'<p class="fui-loading-modal">' +
					'<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>' +
					'</p>'
				;

				popup.find(".sui-box-body").html(preloader);

				$.post({
					url: Forminator.Data.ajaxUrl,
					type: 'post',
					data: request_data
				})
					.done(function (result) {
						if (result && result.success) {
							// Imitate title loading
							popup.find(".sui-box-header h3.sui-box-title").show();

							// Render popup body
							popup.find(".sui-box-body").html(result.data.html);

							// Render popup footer
							var buttons = result.data.buttons;

							// Clear footer from previous buttons
							popup.find(".sui-box-footer").html('');

							// Append buttons
							_.each( buttons, function( button ) {
								popup.find( '.sui-box-footer' ).append( button.markup );
							});

							popup.find(".sui-button").removeClass("sui-button-onload");

							// Handle notifications
							if (!_.isUndefined(result.data.notification) &&
								!_.isUndefined(result.data.notification.type) &&
								!_.isUndefined(result.data.notification.text) &&
								!_.isUndefined(result.data.notification.duration)
							) {

								Forminator.Notification.open(result.data.notification.type, result.data.notification.text, result.data.notification.duration)
									.done(function () {
										// Notification opened
									});

								self.update_stripe_page( popup_nonce );
							}
						}
					});
			},

			update_stripe_page: function( nonce ) {
				var new_request = {
					action: 'forminator_stripe_update_page',
					_ajax_nonce: nonce,
				}

				$.post({
					url: Forminator.Data.ajaxUrl,
					type: 'get',
					data: new_request
				})
					.done(function (result) {
						// Update screen
						jQuery( '#sui-box-stripe' ).html( result.data );

						// Re-init SUI events
						Forminator.Utils.sui_delegate_events();

						// Close the popup
						Forminator.Stripe_Popup.close();
					});
			},

			show_unsupported_settings: function (e) {
				e.preventDefault();

				$('.forminator-unsupported-settings').show();
			},

			hide_unsupported_settings: function (e) {
				e.preventDefault();

				$('.forminator-unsupported-settings').hide();
			},

			connect_stripe: function (e) {
				e.preventDefault();

				var $target = $(e.target);
				$target.addClass('sui-button-onload');

				var nonce        = $target.data('nonce');
				var popup        = this.$el.find('#forminator-stripe-popup');
				var form         = popup.find('form');
				var data         = form.serializeArray();
				var indexedArray = {};

				$.map(data, function (n, i) {
					indexedArray[n['name']] = n['value'];
				});
				indexedArray['connect'] = true;

				this.render_stripe_connect_modal_content(popup, nonce, indexedArray);

				return false;
			},

			/**
			 * WAI-ARIA Side Tabs
			 *
			 * @since 1.7.2
			 */
			buttonTabs: function( e ) {

				var button = this.$( e.target ),
					wrapper = button.closest( '.sui-tabs' ),
					list    = wrapper.find( '.sui-tabs-menu .sui-tab-item' ),
					panes   = wrapper.find( '.sui-tabs-content .sui-tab-content' )
					;

				if ( button.is( 'button' ) ) {

					// Reset lists
					list.removeClass( 'active' );
					list.attr( 'tabindex', '-1' );

					// Reset panes
					panes.attr( 'hidden', true );
					panes.removeClass('active');

					// Select current tab
					button.removeAttr( 'tabindex' );
					button.addClass( 'active' );

					// Select current content
					wrapper.find( '#' + button.attr( 'aria-controls' ) ).addClass( 'active' );
					wrapper.find( '#' + button.attr( 'aria-controls' ) ).attr( 'hidden', false );
					wrapper.find( '#' + button.attr( 'aria-controls' ) ).removeAttr( 'hidden' );
				}

				e.preventDefault();

			},

			/**
			 * Paypal
			 *
			 * @param {*} e
			 * @since 1.7.1
			 */
			open_paypal_connect_modal: function (e) {
				e.preventDefault();
				var self = this;
				var $target = $(e.target);
				var image    = $target.data('modalImage');
				var image_x2 = $target.data('modalImageX2');
				var title    = $target.data('modalTitle');
				var nonce    = $target.data('modalNonce');
				Forminator.Stripe_Popup.open(function () {
					var popup = $(this);
					self.render_paypal_connect_modal_content(popup, nonce, {});
				}, {
					title: title,
					image: image,
					image_x2: image_x2,
				});

				return false;
			},

			render_paypal_connect_modal_content: function (popup, popup_nonce, request_data) {
				var self = this;
				request_data.action      = 'forminator_paypal_settings_modal';
				request_data._ajax_nonce = popup_nonce;

				$.post({
					url: Forminator.Data.ajaxUrl,
					type: 'post',
					data: request_data
				})
					.done(function (result) {
						if (result && result.success) {
							// Imitate title loading
							popup.find(".sui-box-header h3.sui-box-title").show();

							// Render popup body
							popup.find(".sui-box-body").html(result.data.html);

							// Render popup footer
							var buttons = result.data.buttons;

							// Clear footer from previous buttons
							popup.find(".sui-box-footer").html('');

							// Append buttons
							_.each( buttons, function( button ) {
								popup.find( '.sui-box-footer' ).append( button.markup );
							});

							popup.find(".sui-button").removeClass("sui-button-onload");

							// Handle notifications
							if (!_.isUndefined(result.data.notification) &&
								!_.isUndefined(result.data.notification.type) &&
								!_.isUndefined(result.data.notification.text) &&
								!_.isUndefined(result.data.notification.duration)
							) {

								Forminator.Notification.open(result.data.notification.type, result.data.notification.text, result.data.notification.duration)
									.done(function () {
										// Notification opened
									});

								self.update_paypal_page( popup_nonce );
							}
						}
					});
			},

			update_paypal_page: function( nonce ) {
				var new_request = {
					action: 'forminator_paypal_update_page',
					_ajax_nonce: nonce,
				}

				$.post({
					url: Forminator.Data.ajaxUrl,
					type: 'get',
					data: new_request
				})
					.done(function (result) {
						// Update screen
						jQuery( '#sui-box-paypal' ).html( result.data );

						// Re-init SUI events
						Forminator.Utils.sui_delegate_events();

						// Close the popup
						Forminator.Stripe_Popup.close();
					});
			},

			connect_paypal: function (e) {
				e.preventDefault();

				var $target = $(e.target);
				$target.addClass('sui-button-onload');

				var nonce        = $target.data('nonce');
				var popup        = this.$el.find('#forminator-stripe-popup');
				var form         = popup.find('form');
				var data         = form.serializeArray();
				var indexedArray = {};

				$.map(data, function (n, i) {
					indexedArray[n['name']] = n['value'];
				});
				indexedArray['connect'] = true;

				this.render_paypal_connect_modal_content(popup, nonce, indexedArray);

				return false;
			},

			disconnect_stripe: function (e) {
				var $target = $(e.target);
				var new_request = {
					action: 'forminator_disconnect_stripe',
					_ajax_nonce: $target.data('nonce'),
				}
				$target.addClass('sui-button-onload');

				$.post({
					url: Forminator.Data.ajaxUrl,
					type: 'get',
					data: new_request
				})
					.done(function (result) {
						// Update screen
						jQuery( '#sui-box-stripe' ).html( result.data.html );

						// Re-init SUI events
						Forminator.Utils.sui_delegate_events();

						// Close the popup
						Forminator.Stripe_Popup.close();

						// Handle notifications
						if (!_.isUndefined(result.data.notification) &&
							!_.isUndefined(result.data.notification.type) &&
							!_.isUndefined(result.data.notification.text) &&
							!_.isUndefined(result.data.notification.duration)
						) {

							Forminator.Notification.open(result.data.notification.type, result.data.notification.text, result.data.notification.duration)
								.done(function () {
									// Notification opened
								});

						}
					});

			},

			disconnect_paypal: function (e) {
				var $target = $(e.target);
				var new_request = {
					action: 'forminator_disconnect_paypal',
					_ajax_nonce: $target.data('nonce'),
				}
				$target.addClass('sui-button-onload');

				$.post({
					url: Forminator.Data.ajaxUrl,
					type: 'get',
					data: new_request
				})
					.done(function (result) {
						// Update screen
						jQuery( '#sui-box-paypal' ).html( result.data.html );

						// Re-init SUI events
						Forminator.Utils.sui_delegate_events();

						// Close the popup
						Forminator.Stripe_Popup.close();

						// Handle notifications
						if (!_.isUndefined(result.data.notification) &&
							!_.isUndefined(result.data.notification.type) &&
							!_.isUndefined(result.data.notification.text) &&
							!_.isUndefined(result.data.notification.duration)
						) {

							Forminator.Notification.open(result.data.notification.type, result.data.notification.text, result.data.notification.duration)
								.done(function () {
									// Notification opened
								});

						}
					});

			},
		});

		var SettingsPage = new SettingsPage();

		return SettingsPage;
	});
})(jQuery);

// noinspection JSUnusedGlobalSymbols
var forminator_render_admin_captcha = function () {
	setTimeout( function () {
		var $captcha = jQuery( '.forminator-g-recaptcha' ),
			sitekey = $captcha.data('sitekey'),
			theme = $captcha.data('theme'),
			size = $captcha.data('size')
		;
		window.grecaptcha.render( $captcha[0], {
			sitekey: sitekey,
			theme: theme,
			size: size
		} );
	}, 100 );
};

// noinspection JSUnusedGlobalSymbols
var forminator_render_admin_captcha_v2 = function () {
	setTimeout( function () {
		var $captcha_v2 = jQuery( '.forminator-g-recaptcha-v2' ),
			sitekey_v2 = $captcha_v2.data('sitekey'),
			theme_v2 = $captcha_v2.data('theme'),
			size_v2 = $captcha_v2.data('size')
		;
		window.grecaptcha.render( $captcha_v2[0], {
			sitekey: sitekey_v2,
			theme: theme_v2,
			size: size_v2
		} );
	}, 100 );
};

// noinspection JSUnusedGlobalSymbols
var forminator_render_admin_captcha_v2_invisible = function () {
	setTimeout( function () {
		var $captcha = jQuery( '.forminator-g-recaptcha-v2-invisible' ),
			sitekey = $captcha.data('sitekey'),
			theme = $captcha.data('theme'),
			size = $captcha.data('size')
		;
		window.grecaptcha.render( $captcha[0], {
			sitekey: sitekey,
			theme: theme,
			size: size,
			badge: 'inline'
		} );
	}, 100 );
};

var forminator_render_admin_captcha_v3 = function () {
	setTimeout( function () {
		var $captcha = jQuery( '.forminator-g-recaptcha-v3' ),
			sitekey = $captcha.data('sitekey'),
			theme = $captcha.data('theme'),
			size = $captcha.data('size')
		;
		window.grecaptcha.render( $captcha[0], {
			sitekey: sitekey,
			theme: theme,
			size: size,
			badge: 'inline'
		} );
	}, 100 );
};

var forminator_render_admin_hcaptcha = function () {
	setTimeout( function () {
		var $hcaptcha = jQuery( '.forminator-hcaptcha' ),
			sitekey = $hcaptcha.data( 'sitekey' )
			// theme = $captcha.data('theme'),
			// size = $captcha.data('size')
		;

		hcaptcha.render( $hcaptcha[0], {
			sitekey: sitekey
		} );
	}, 1000 );
};
