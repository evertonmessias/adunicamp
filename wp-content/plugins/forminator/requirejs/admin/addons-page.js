(function ($) {
	formintorjs.define([
	], function() {
		var AddonsPage = Backbone.View.extend({
			el: '.wpmudev-forminator-forminator-addons',
			events: {
				"click button.addons-actions": "addons_actions",
				"click a.addons-actions": "addons_actions",
				"click .sui-dialog-close": "close",
				"click .addons-modal-close": "close",
				"click .addons-page-details": "open_addons_detail",
			},
			initialize: function () {
				var self = this;
				// only trigger on settings page
				if (!$('.wpmudev-forminator-forminator-addons').length) {
					return;
				}
			},

			addons_actions: function ( e ) {
				var self = this,
					$target = $( e.target ),
					request_data = {},
					nonce = $target.data('nonce'),
					actions = $target.data('action'),
					popup = $target.data('popup'),
					pid = $target.data('addon');

				request_data.action = 'forminator_' + actions;
				request_data.pid = pid;
				request_data._ajax_nonce = nonce;

				$target.addClass("sui-button-onload");
				self.$el.find(".sui-button.addons-actions:not(.disable-loader)")
					.attr( 'disabled', true );

				$.post({
					url: Forminator.Data.ajaxUrl,
					type: 'post',
					data: request_data
				}).done(function ( result ) {

					if ( 'undefined' !== typeof result.data.error ) {
						self.show_notification( result.data.error.message, 'error' );
						return false;
					}

					if ( 'addons-install' === actions ) {
						setTimeout(function () {
							self.active_popup( pid, 'show', 'forminator-activate-popup' );
							self.$el.find( '.sui-tab-content .addons-' + pid )
								.not( this )
								.replaceWith( result.data.html );
							self.loader_remove();
						}, 1000);

					} else {
						self.show_notification( result.data.message, 'success' );
						self.$el.find( '.sui-tab-content .addons-' + pid )
							.not( this )
							.replaceWith( result.data.html );

						if ( 'addons-update' === actions ) {

							var detailPopup = self.$el.find( '#forminator-modal-addons-details-' + pid );
							self.$el.find( '#updates-addons-content .addons-' + pid ).remove();

							var updateCounter = self.$el.find('#updates-addons-content .sui-col-md-6').length;
							if ( updateCounter < 1 ) {
								self.$el.find( '#updates-addons span.sui-tag').removeClass('sui-tag-yellow');
							}
							self.$el.find( '#updates-addons span.sui-tag').html( updateCounter );

							detailPopup.find( '.forminator-details-header--tags span.addons-update-tag').remove();

							var version = $target.data('version');
							detailPopup.find( '.forminator-details-header--tags span.addons-version').html( version );

							detailPopup.find( '.forminator-details-header button.addons-actions').remove();
							$target.remove();
						}
						if ( popup ) {
							location.reload();
						}
                    }
				}).fail( function () {
					self.show_notification( Forminator.l10n.commons.error_message, 'error' );
				});

				return false;
			},

			close: function( e ) {
				e.preventDefault();

				var $target = $( e.target ),
					pid = $target.data('addon'),
					element = $target.data('element');
				this.active_popup( pid, 'hide', element );
			},

			loader_remove: function () {
				this.$el.find(".sui-button.addons-actions:not(.disable-loader)")
					.removeClass("sui-button-onload")
					.attr( 'disabled', false );
			},

			show_notification: function ( message, status ) {
				var error_message = 'undefined' !== typeof message
					? message :
					Forminator.l10n.commons.error_message;
				Forminator.Notification.open( status, error_message, 4000 );
				this.loader_remove();
			},

			active_popup: function ( pid, status, element ) {
				var modalId = element + '-' + pid,
					focusAfterClosed = 'forminator-addon-' + pid + '__card';

				if ( 'show' === status ) {
					SUI.openModal(
						modalId,
						focusAfterClosed
					);
				} else {
					SUI.closeModal();
				}
			},

			open_addons_detail: function ( e ) {
				var self = this,
					$target = $( e.target ),
					pid = $target.data('form-id');
				self.active_popup( pid, 'show', 'forminator-modal-addons-details' );
			}
		});

		var AddonsPage = new AddonsPage();

		return AddonsPage;
	});
})(jQuery);
