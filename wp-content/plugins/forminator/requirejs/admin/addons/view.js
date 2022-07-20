(function ($) {
	formintorjs.define([
		'text!tpl/popups.html'
	], function( popupsTpl ) {
		return Backbone.View.extend({
			className: 'wpmudev-section--integrations',

			loaderTpl: Forminator.Utils.template( $( popupsTpl ).find( '#popup-loader-tpl' ).html() ),

			model: {},

			events: {
				"click .forminator-addon-connect": "connect_addon",
				"click .forminator-addon-disconnect" : "disconnect_addon",
				"click .forminator-addon-form-disconnect" : "form_disconnect_addon",
				"click .forminator-addon-next" : "submit_next_step",
				"click .forminator-addon-back" : "go_prev_step",
				"click .forminator-addon-finish" : "finish_steps"
			},

			initialize: function( options ) {
				this.slug      = options.slug;
				this.nonce     = options.nonce;
				this.action    = options.action;
				this.form_id   = options.form_id;
				this.multi_id  = options.multi_id;
				this.global_id = options.global_id;
				this.step      = 0;
				this.next_step = false;
				this.prev_step = false;
				this.scrollbar_width = this.get_scrollbar_width();

				var self = this;

				// Add closing event
				this.$el.find( ".forminator-integration-close, .forminator-addon-close" ).on("click", function () {
					self.close(self);
				});

				return this.render();
			},

			render: function() {
				var data = {};

				data.action = this.action;
				data._ajax_nonce = this.nonce;
				data.data = {};
				data.data.slug = this.slug;
				data.data.step = this.step;
				data.data.current_step = this.step;
				data.data.global_id = this.global_id;
				if (this.form_id) {
					data.data.form_id = this.form_id;
				}
				if (this.multi_id) {
					data.data.multi_id = this.multi_id;
				}

				this.request( data, false, true );
			},

			request: function ( data, close, loader ) {
				var self            = this,
				    function_params = {
					    data  : data,
					    close : close,
					    loader: loader,
				    };

				if ( loader ) {
					this.$el.find(".forminator-integration-popup__header").html( '' );
					this.$el.find(".forminator-integration-popup__body").html( this.loaderTpl() );
					this.$el.find(".forminator-integration-popup__footer").html( '' );
				}

				this.$el.find(".sui-button:not(.disable-loader)").addClass("sui-button-onload");

				this.ajax = $.post({
					url: Forminator.Data.ajaxUrl,
					type: 'post',
					data: data
				})
				.done(function (result) {
					if (result && result.success) {
						// Reset hidden elements.
						self.render_reset();

						// Render popup body
						self.render_body( result );

						// Render popup footer
						self.render_footer( result );

						// Hide elements when empty
						self.hide_elements();

						// Shorten result data
						var result_data = result.data.data;

						self.on_render( result_data );

						self.$el.find(".sui-button").removeClass("sui-button-onload");

						// Handle close modal
						if( close || ( !_.isUndefined( result_data.is_close ) && result_data.is_close ) ) {
							self.close( self );
						}

						// Add closing event
						self.$el.find( ".forminator-addon-close" ).on("click", function () {
							self.close(self);
						});

						// Handle notifications
						if( !_.isUndefined( result_data.notification ) &&
							!_.isUndefined( result_data.notification.type ) &&
							!_.isUndefined( result_data.notification.text ) ) {

							Forminator.Notification.open( result_data.notification.type, result_data.notification.text, 4000 );
						}

						// Handle back button
						if( !_.isUndefined( result_data.has_back ) ) {
							if( result_data.has_back ) {
								self.$el.find('.forminator-addon-back').show();
							} else {
								self.$el.find('.forminator-addon-back').hide();
							}
						} else {
							self.$el.find('.forminator-addon-back').hide();
						}

						if (result_data.is_poll) {
							setTimeout(self.request(function_params.data, function_params.close, function_params.loader), 5000);
						}

						//check the height
						var $popup_box = $( "#forminator-integration-popup .sui-box" ),
						    $popup_box_height = $popup_box.height(),
						    $window_height = $(window).height();

						// scrollbar appear
						if ($popup_box_height > $window_height) {
							// make scrollbar clickable
							$("#forminator-integration-popup .sui-dialog-overlay").css('right', self.scrollbar_width + 'px');
						} else {
							$("#forminator-integration-popup .sui-dialog-overlay").css('right', 0);
						}
					}
				});

				//remove the preloader
				this.ajax.always(function () {
					self.$el.find(".fui-loading-dialog").remove();
				});
			},

			render_reset: function() {
				var integration_body = $( '.forminator-integration-popup__body' ),
					integration_footer = $( '.forminator-integration-popup__footer' );

				// Show hidden body.
				if ( integration_body.is( ':hidden' ) ) {
					integration_body.css( 'display', '' );
				}

				// Show empty footer.
				if ( integration_footer.is( ':hidden' ) ) {
					integration_footer.css( 'display', '' );
				}
			},

			render_body: function ( result ) {
				// Render content inside `body`.
				this.$el.find(".forminator-integration-popup__body").html( result.data.data.html );

				// Append header elements to `sui-box-header`.
				var integration_header = this.$el.find( '.forminator-integration-popup__body .forminator-integration-popup__header' ).remove();
				if ( integration_header.length > 0 ) {
					this.$el.find( '.forminator-integration-popup__header' ).html( integration_header.html() );
				}
			},

			render_footer: function ( result ) {
				var self = this,
					buttons  = result.data.data.buttons
				;

				// Clear footer from previous buttons
				self.$el.find(".sui-box-footer").html('');

				// Append footer elements from `body`.
				var integration_footer = this.$el.find( '.forminator-integration-popup__body .forminator-integration-popup__footer-temp' ).remove();
				if ( integration_footer.length > 0 ) {
					this.$el.find( '.forminator-integration-popup__footer' ).html( integration_footer.html() );
				}

				// Append buttons from php template.
				_.each( buttons, function (button) {
					self.$el.find( '.sui-box-footer' ).append( button.markup );
				});

				// Align buttons.
				self.$el.find( '.sui-box-footer' )
					.removeClass( 'sui-content-center' )
					.addClass( 'sui-content-separated' );

				if ( self.$el.find( '.sui-box-footer' ).children( '.forminator-integration-popup__close' ).length > 0 ) {
					self.$el.find( '.sui-box-footer' )
						.removeClass( 'sui-content-separated' )
						.addClass( 'sui-content-center' );
				}
			},

			hide_elements: function() {
				var integration_body = $( '.forminator-integration-popup__body' ),
					integration_footer = $( '.forminator-integration-popup__footer' ),
					integration_content = integration_body.html(),
					integration_footer_html = integration_footer.html();

				// Hide empty body.
				if ( ! integration_content.trim().length ) {
					integration_body.hide();
				}

				// Hide empty footer.
				if ( ! integration_footer_html.trim().length ) {
					integration_footer.hide();
				}
			},

			on_render: function ( result ) {
				this.delegateEvents();

				// Delegate SUI events
				Forminator.Utils.sui_delegate_events();
				// multi select (Tags)
				Forminator.Utils.forminator_select2_tags( this.$el, {} );

				// Update current step
				if( !_.isUndefined( result.forminator_addon_current_step ) ) {
					this.step = +result.forminator_addon_current_step;
				}

				// Update has next step
				if( !_.isUndefined( result.forminator_addon_has_next_step ) ) {
					this.next_step = result.forminator_addon_has_next_step;
				}

				// Update has prev step
				if( !_.isUndefined( result.forminator_addon_has_prev_step ) ) {
					this.prev_step = result.forminator_addon_has_prev_step;
				}
			},

			get_step: function () {
				if( this.next_step ) {
					return this.step + 1;
				}

				return this.step;
			},

			get_prev_step: function () {
				if( this.prev_step ) {
					return this.step - 1;
				}

				return this.step;
			},

			connect_addon: function ( e ) {
				var data     = {},
					form     = this.$el.find( 'form' ),
					params   = {
						'slug' : this.slug,
						'step' : this.get_step(),
						'global_id' : this.global_id,
						'current_step' : this.step,
					},
					formData = form.serialize()
				;

				if (this.form_id) {
					params.form_id = this.form_id;
				}
				if (this.multi_id) {
					params.multi_id = this.multi_id;
				}

				formData = formData + '&' + $.param( params );
				data.action = this.action;
				data._ajax_nonce = this.nonce;
				data.data = formData;

				this.request( data, false, false );
			},

			submit_next_step: function(e) {
				var data     = {},
				    form     = this.$el.find( 'form' ),
				    params   = {
					    'slug' : this.slug,
					    'step' : this.get_step(),
						'global_id' : this.global_id,
					    'current_step' : this.step,
				    },
				    formData = form.serialize()
				;

				if (this.form_id) {
					params.form_id = this.form_id;
				}

				formData = formData + '&' + $.param( params );
				data.action = this.action;
				data._ajax_nonce = this.nonce;
				data.data = formData;

				this.request( data, false, false );
			},

			go_prev_step: function(e) {
				var data     = {},
				    params   = {
					    'slug' : this.slug,
					    'step' : this.get_prev_step(),
						'global_id' : this.global_id,
					    'current_step' : this.step,
				    }
				;

				if (this.form_id) {
					params.form_id = this.form_id;
				}
				if (this.multi_id) {
					params.multi_id = this.multi_id;
				}

				data.action = this.action;
				data._ajax_nonce = this.nonce;
				data.data = params;

				this.request( data, false, false );
			},

			finish_steps: function ( e ) {
				var data     = {},
					form     = this.$el.find( 'form' ),
					params   = {
						'slug' : this.slug,
						'step' : this.get_step(),
						'global_id' : this.global_id,
						'current_step' : this.step,
					},
					formData = form.serialize()
				;

				if (this.form_id) {
					params.form_id = this.form_id;
				}
				if (this.multi_id) {
					params.multi_id = this.multi_id;
				}

				formData = formData + '&' + $.param( params );
				data.action = this.action;
				data._ajax_nonce = this.nonce;
				data.data = formData;

				this.request( data, false, false );
			},

			disconnect_addon: function ( e ) {
				var data = {};
				data.action = 'forminator_addon_deactivate';
				data._ajax_nonce = this.nonce;
				data.data = {};
				data.data.slug = this.slug;
				data.data.global_id = this.global_id;

				this.request( data, true, false );
			},

			form_disconnect_addon: function ( e ) {
				var data = {};
				data.action = 'forminator_addon_deactivate_for_module';
				data._ajax_nonce = this.nonce;
				data.data = {};
				data.data.slug = this.slug;
				data.data.form_id = this.form_id;
				data.data.form_type = 'form';
				if (this.multi_id) {
					data.data.multi_id = this.multi_id;
				}

				this.request( data, true, false );
			},

			close: function( self ) {
				// Kill AJAX hearbeat
				self.ajax.abort();

				// Remove the view
				self.remove();

				// Close the modal
				Forminator.Integrations_Popup.close();

				// Refrest add-on list
				Forminator.Events.trigger( "forminator:addons:reload" );
			},

			get_scrollbar_width: function () {
				//https://github.com/brandonaaron/jquery-getscrollbarwidth/
				var scrollbar_width = 0;
				if ( navigator.userAgent.match( "MSIE" ) ) {
					var $textarea1 = $('<textarea cols="10" rows="2"></textarea>')
						    .css({ position: 'absolute', top: -1000, left: -1000 }).appendTo('body'),
					    $textarea2 = $('<textarea cols="10" rows="2" style="overflow: hidden;"></textarea>')
						    .css({ position: 'absolute', top: -1000, left: -1000 }).appendTo('body');
					scrollbar_width = $textarea1.width() - $textarea2.width();
					$textarea1.add($textarea2).remove();
				} else {
					var $div = $('<div />')
						.css({ width: 100, height: 100, overflow: 'auto', position: 'absolute', top: -1000, left: -1000 })
						.prependTo('body').append('<div />').find('div')
						.css({ width: '100%', height: 200 });
					scrollbar_width = 100 - $div.width();
					$div.parent().remove();
				}
				return scrollbar_width;
			}
		});
	});
})(jQuery);
