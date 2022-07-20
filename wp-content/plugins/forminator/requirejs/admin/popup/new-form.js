(function ($) {
	formintorjs.define([
		'text!tpl/dashboard.html',
	], function( popupTpl ) {
		return Backbone.View.extend({
			className: 'wpmudev-section--popup',

			popupTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-form-popup-tpl' ).html()),

			initialize: function ( options ) {
				this.title = options.title;
				this.title = Forminator.Utils.sanitize_uri_string( this.title );
			},

			render: function() {
				this.$el.html( this.popupTpl({
					title: this.title
				}));
			},
		});
	});
})(jQuery);
