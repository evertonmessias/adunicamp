(function ($) {
    formintorjs.define([
        'text!tpl/dashboard.html',
    ], function (popupTpl) {
        return Backbone.View.extend({
            className: 'wpmudev-section--popup',

            popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-delete-popup-tpl').html()),
            popupPollTpl: Forminator.Utils.template($(popupTpl).find('#forminator-delete-poll-popup-tpl').html()),

			initialize: function( options ) {
				this.module = options.module;
				this.nonce = options.nonce;
				this.id = options.id;
				this.action = options.action;
				this.referrer = options.referrer;
				this.button = options.button || Forminator.l10n.popup.delete;
				this.content = options.content || Forminator.l10n.popup.cannot_be_reverted ;
			},

            render: function () {
            	if( 'poll' === this.module ) {
					this.$el.html(this.popupPollTpl({
						nonce: this.nonce,
						id: this.id,
						referrer: this.referrer,
						content: this.content,
					}));
				} else {
					this.$el.html(this.popupTpl({
						nonce: this.nonce,
						id: this.id,
						action: this.action,
						referrer: this.referrer,
						button: this.button,
						content: this.content,
					}));
				}
            },
        });
    });
})(jQuery);
