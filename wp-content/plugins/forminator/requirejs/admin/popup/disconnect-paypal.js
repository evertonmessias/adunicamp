(function ($) {
    formintorjs.define([
        'text!tpl/dashboard.html',
    ], function (popupTpl) {
        return Backbone.View.extend({
            className: 'wpmudev-section--popup',

            popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-disconnect-paypal-popup-tpl').html()),

			initialize: function( options ) {
				this.nonce = options.nonce;
				this.referrer = options.referrer;
				this.content = options.content || Forminator.l10n.popup.cannot_be_reverted ;
			},

            render: function () {
                this.$el.html(this.popupTpl({
					nonce: this.nonce,
					id: this.id,
					referrer: this.referrer,
	                content: this.content,
				}));
            },
        });
    });
})(jQuery);
