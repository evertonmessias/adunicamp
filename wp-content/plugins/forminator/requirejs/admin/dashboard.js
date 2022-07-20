(function ($) {
	formintorjs.define([
	], function( TemplatesPopup ) {
		var Dashboard = Backbone.View.extend({
			el: '.wpmudev-dashboard-section',
			events: {
				"click .wpmudev-action-close": "dismiss_welcome"
			},
			initialize: function () {
				var notification = Forminator.Utils.get_url_param( 'notification' ),
					form_title = Forminator.Utils.get_url_param( 'title' ),
					create = Forminator.Utils.get_url_param( 'createnew' )
				;

				setTimeout( function() {
					if ( $( '.forminator-scroll-to' ).length ) {
						$('html, body').animate({
							scrollTop: $( '.forminator-scroll-to' ).offset().top - 50
						}, 'slow');
					}
				}, 100 );

				if( notification ) {
					var markup = _.template( '<strong>{{ formName }}</strong> {{ Forminator.l10n.options.been_published }}' );

					Forminator.Notification.open( 'success', markup({
						formName: Forminator.Utils.sanitize_uri_string( form_title )
					}), 4000 );
				}

				if ( create ) {
					setTimeout( function() {
						jQuery( '.forminator-create-' + create ).click();
					}, 200 );
				}

				return this.render();
			},

			dismiss_welcome: function( e ) {
				e.preventDefault();

				var $container = $( e.target ).closest( '.sui-box' ),
					$nonce = $( e.target ).data( "nonce" )
				;

				$container.slideToggle( 300, function() {
					$.ajax({
						url: Forminator.Data.ajaxUrl,
						type: "POST",
						data: {
							action: "forminator_dismiss_welcome",
							_ajax_nonce: $nonce
						},
						complete: function( result ){
							$container.remove();
						}
					});
				});
			},

			render: function() {

				if ( $( '#forminator-new-feature' ).length > 0 ) {

					setTimeout( function () {
						SUI.openModal(
							'forminator-new-feature',
							'wpbody-content'
						);
					}, 300 );
				}
			}
		});

		var DashView = new Dashboard();

		return DashView;
	});
})(jQuery);
