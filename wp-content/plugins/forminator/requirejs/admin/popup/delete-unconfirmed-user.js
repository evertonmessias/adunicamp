(function ($) {
	formintorjs.define([
		'text!tpl/dashboard.html',
	], function (popupTpl) {
		return Backbone.View.extend({
			className: 'wpmudev-section--popup',

			popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-delete-unconfirmed-user-popup-tpl').html()),
			events: {
				"click .delete-unconfirmed-user.popup-confirmation-confirm" : 'deleteUnconfirmedUser',
			},
			initialize: function( options ) {
				this.nonce = options.nonce;
				this.formId = options.formId;
				this.referrer = options.referrer;
				this.content = options.content || Forminator.l10n.popup.cannot_be_reverted ;
				this.activationKey = options.activationKey;
				this.entryId = options.entryId;
			},

			render: function () {
				this.$el.html(this.popupTpl({
					nonce: this.nonce,
					formId: this.formId,
					referrer: this.referrer,
					content: this.content,
					activationKey: this.activationKey,
					entryId: this.entryId,
				}));
			},

			submitForm: function( $form, nonce, activationKey, formId, entryId ) {
				var data = {
					action: 'forminator_delete_unconfirmed_user_popup',
					_ajax_nonce: nonce,
					activation_key: activationKey,
					form_id: formId,
					entry_id: entryId,
				};

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
							window.location.reload();
						} else {
							Forminator.Notification.open( 'error', result.data, 4000 );
						}
					},
					error: function ( error ) {
						Forminator.Notification.open( 'error', error.data, 4000 );
					}
				}).always(function(){
					$form.find('.sui-button').removeClass('sui-button-onload');
				});
			},

			deleteUnconfirmedUser: function(e) {
				e.preventDefault();

				var $target = $(e.target);
				$target.addClass('sui-button-onload');

				var popup   = this.$el.find('.form-delete-unconfirmed-user');
				var form    = popup.find('form');

				this.submitForm( form, this.nonce, this.activationKey, this.formId, this.entryId );

				return false;
			}
		});
	});
})(jQuery);
