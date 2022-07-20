(function ($) {
	formintorjs.define([
		'admin/addons/view'
	], function (SettingsView) {
		var Addons = Backbone.View.extend({
			el: '.sui-wrap.wpmudev-forminator-forminator-integrations',

			currentTab: 'forminator-integrations',

			events: {
				"change .forminator-addon-toggle-enabled" : "toggle_state",
				"click .connect-integration" : "connect_integration",
				"click .forminator-integrations-wrapper .sui-vertical-tab a" : "go_to_tab",
				"change .forminator-integrations-wrapper .sui-sidenav-hide-lg select" : "go_to_tab",
				"change .forminator-integrations-wrapper .sui-sidenav-hide-lg.integration-nav" : "go_to_tab",
				"keyup input.sui-form-control": "required_settings"
			},

			initialize: function( options ) {
				if( $( this.el ).length > 0 ) {
					this.listenTo( Forminator.Events, "forminator:addons:reload", this.render_addons_page );
					return this.render();
				}
			},

			render: function () {
				// Check if addons wrapper exist
				this.render_addons_page();

				this.update_tab();
			},

			render_addons_page: function () {
				var self = this,
					data = {}
				;

				this.$el.find( '#forminator-integrations-display' ).html(
					'<div role="alert" id="forminator-addons-preloader" class="sui-notice sui-active" style="display: block;" aria-live="assertive">' +
						'<div class="sui-notice-content">' +
							'<div class="sui-notice-message">' +
								'<span class="sui-notice-icon sui-icon-loader sui-loading" aria-hidden="true"></span>' +
								'<p>Fetching integration list…</p>' +
							'</div>' +
						'</div>' +
					'</div>'
				);

				data.action      = 'forminator_addon_get_addons';
				data._ajax_nonce = Forminator.Data.addonNonce;
				data.data = {};

				var ajax = $.post({
					url: Forminator.Data.ajaxUrl,
					type: 'post',
					data: data
				})
				.done(function (result) {
					if (result && result.success) {
						self.$el.find( '#forminator-integrations-page' ).html( result.data.data );
					}
				});

				//remove the preloader
				ajax.always(function () {
					self.$el.find("#forminator-addons-preloader").remove();
				});
			},

			connect_integration: function (e) {
				e.preventDefault();

				var $target = $(e.target);

				if (!$target.hasClass('connect-integration')) {
					$target = $target.closest('.connect-integration');
				}

				var nonce    = $target.data('nonce'),
				    slug     = $target.data('slug'),
				    global_id= $target.data('multi-global-id'),
				    title    = $target.data('title'),
				    image    = $target.data('image'),
				    image_x2 = $target.data('imagex2'),
				    action   = $target.data('action'),
				    form_id  = $target.data('form-id'),
				    multi_id = $target.data('multi-id')
				;

				Forminator.Integrations_Popup.open(function () {
					var view = new SettingsView({
						slug    : slug,
						nonce   : nonce,
						action  : action,
						form_id : form_id,
						multi_id : multi_id,
						global_id : global_id,
						el      : $(this)
					});
				}, {
					title   : title,
					image   : image,
					image_x2: image_x2,
				});
			},

			go_to_tab: function (e) {
				e.preventDefault();
				var target = $(e.target),
					href   = target.attr('href'),
					tab_id = '';
				if (!_.isUndefined(href)) {
					tab_id = href.replace('#', '', href);
				} else {
					var val = target.val();
					tab_id  = val;
				}

				if (!_.isEmpty(tab_id)) {
					this.currentTab = tab_id;
				}

				this.update_tab();

				e.stopPropagation();
			},

			update_tab_select: function() {

				if ( this.$el.hasClass( 'wpmudev-forminator-forminator-integrations' ) ) {
					this.$el.find('.sui-sidenav-hide-lg select').val(this.currentTab);
					this.$el.find('.sui-sidenav-hide-lg select').trigger('sui:change');
				}
			},

			update_tab: function () {

				if ( this.$el.hasClass( 'wpmudev-forminator-forminator-integrations' ) ) {

					this.clear_tabs();

					this.$el.find( '[data-tab-id=' + this.currentTab + ']' ).addClass( 'current' );
					this.$el.find( '.wpmudev-settings--box#' + this.currentTab ).show();
				}
			},

			clear_tabs: function () {

				if ( this.$el.hasClass( 'wpmudev-forminator-forminator-integrations' ) ) {
					this.$el.find( '.sui-vertical-tab ').removeClass( 'current' );
					this.$el.find( '.wpmudev-settings--box' ).hide();
				}
			},

			required_settings: function( e ) {

				var input = $( e.target ),
					field = input.parent(),
					error = field.find( '.sui-error-message' )
					;

				var tabWrapper = input.closest( 'div[data-nav]' ),
					tabFooter  = tabWrapper.find( '.sui-box-footer' ),
					saveButton = tabFooter.find( '.wpmudev-action-done' )
					;

				if ( this.$el.hasClass( 'wpmudev-forminator-forminator-settings' ) ) {

					if ( input.hasClass( 'forminator-required' ) && ! input.val() ) {

						if ( field.hasClass( 'sui-form-field' ) ) {
							field.addClass( 'sui-form-field-error' );
							error.show();
						}
					}

					if ( input.hasClass( 'forminator-required' ) && input.val() ) {

						if ( field.hasClass( 'sui-form-field' ) ) {
							field.removeClass( 'sui-form-field-error' );
							error.hide();
						}
					}

					if ( tabWrapper.find( 'input.sui-form-control' ).hasClass( 'forminator-required' ) ) {

						if ( tabWrapper.find( 'div.sui-form-field-error' ).length === 0 ) {
							saveButton.prop( 'disabled', false );
						} else {
							saveButton.prop( 'disabled', true );
						}
					}
				}

				e.stopPropagation();

			},
		});

		//init after jquery ready
		jQuery(function () {
			new Addons();
		});
	});
})(jQuery);
