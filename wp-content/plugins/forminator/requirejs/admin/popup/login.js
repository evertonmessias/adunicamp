(function ($) {
	formintorjs.define([
		'text!tpl/dashboard.html',
	], function( popupTpl ) {
		return Backbone.View.extend({
			className: 'wpmudev-section--popup',

			popupTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-login-popup-tpl' ).html()),

			render: function() {
				this.$el.html( this.popupTpl({
					loginUrl: Forminator.Data.modules.login.login_url,
					registerUrl: Forminator.Data.modules.login.register_url
				}));
			},
		});
	});
})(jQuery);
