( function( $ ) {
	formintorjs.define([
		'text!tpl/dashboard.html',
	], function( popupTpl ) {

		return Backbone.View.extend({

			className: 'wpmudev-popup-templates',

			newFormTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-form-tpl' ).html()),
			newPollContent: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-poll-content-tpl' ).html() ),

			events: {
				'click #forminator-build-your-form': 'handleMouseClick',
				'keyup': 'handleKeyClick'
			},

			initialize: function( options ) {
				this.options = options;
			},

			render: function() {
				this.$el.html( this.newFormTpl() );
				this.$el.find('.sui-box-body').html( this.newPollContent() );
			},

			handleMouseClick: function( e ) {
				this.create_poll( e );
			},

			handleKeyClick: function( e ) {
				e.preventDefault();

				// If enter create form
				if( e.which === 13 ) {
					this.create_poll( e );
				}
			},

			create_poll: function( e ) {
				e.preventDefault();

				var $form_name = $( e.target ).closest( '.sui-box' ).find( '#forminator-form-name' );

				if( $form_name.val().trim() === "" ) {
					$( e.target ).closest( '.sui-box' ).find( '.sui-error-message' ).show();
				}  else {
					var form_url = Forminator.Data.modules.polls.new_form_url;

					$( e.target ).closest( '.sui-box' ).find( '.sui-error-message' ).hide();

					form_url = form_url + '&name=' + $form_name.val();
					window.location.href = form_url;
				}
			},

		});
	});
}( jQuery ) );
