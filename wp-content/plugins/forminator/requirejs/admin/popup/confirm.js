(function ($) {
	formintorjs.define([
		'text!tpl/dashboard.html',
	], function (popupTpl) {
		return Backbone.View.extend({
			className: 'wpmudev-section--popup',

			popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-confirmation-popup-tpl').html()),

			default_options: {
				confirmation_message: Forminator.l10n.popup.confirm_action,
				confirmation_title: Forminator.l10n.popup.confirm_title,
				confirm_callback: function () {
					this.close();
				},
				cancel_callback: function () {
					this.close();
				}
			},
			confirm_options: {},
			events: {
				"click .popup-confirmation-confirm": "confirm_action",
				"click .popup-confirmation-cancel": "cancel_action"
			},

			initialize: function (options) {
				this.confirm_options = _.defaults(options, this.default_options);
			},

			render: function () {
				this.$el.html(this.popupTpl(this.confirm_options));
				return this;
			},

			confirm_action: function(){
				this.confirm_options.confirm_callback.apply(this, []);
			},

			cancel_action: function(){
				this.confirm_options.cancel_callback.apply(this, []);
			},

			close: function () {
				Forminator.Popup.close();
			}
		});
	});
})(jQuery);
