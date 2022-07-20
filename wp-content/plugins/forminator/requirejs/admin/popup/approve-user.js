(function ($) {
	formintorjs.define([
		'text!tpl/dashboard.html',
	], function (popupTpl) {
		return Backbone.View.extend({
			className: 'wpmudev-section--popup',

			popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-approve-user-popup-tpl').html()),
			events: {
				"click .approve-user.popup-confirmation-confirm" : 'approveUser',
			},
			initialize: function( options ) {
				this.nonce = options.nonce;
				this.referrer = options.referrer;
				this.content = options.content || Forminator.l10n.popup.cannot_be_reverted;
				this.activationKey = options.activationKey;
			},

			render: function () {
				this.$el.html(this.popupTpl({
					nonce: this.nonce,
					id: this.id,
					referrer: this.referrer,
					content: this.content,
					activationKey: this.activationKey,
				}));
			},

			submitForm: function( $form, nonce, activationKey ) {
				var data = {},
					self = this
				;

				data.action = 'forminator_approve_user_popup';
				data._ajax_nonce = nonce;
				data.activation_key = activationKey;

				var ajaxData = $form.serialize() + '&' + $.param(data);

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: ajaxData,
					beforeSend: function() {
						$form.find('.sui-button').addClass('sui-button-onload');
					},
					success: function( result ) {
						if (result && result.success) {
							Forminator.Notification.open('success', Forminator.l10n.commons.approve_user_successfull, 4000);
							window.location.reload();
						} else {
							Forminator.Notification.open( 'error', result.data, 4000 );
						}
					},
					error: function ( error ) {
						Forminator.Notification.open( 'error', Forminator.l10n.commons.approve_user_unsuccessfull, 4000 );
					}
				}).always(function(){
					$form.find('.sui-button').removeClass('sui-button-onload');
				});
			},

			approveUser: function(e) {
				e.preventDefault();

				var $target = $(e.target);
				$target.addClass('sui-button-onload');

				var popup   = this.$el.find('.form-approve-user');
				var form    = popup.find('form');

				this.submitForm( form, this.nonce, this.activationKey );

				return false;
			}
		});
	});
})(jQuery);
