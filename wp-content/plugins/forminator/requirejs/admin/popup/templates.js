(function ($) {
	formintorjs.define([
		'text!tpl/dashboard.html',
	], function( popupTpl ) {
		return Backbone.View.extend({
			className: 'forminator-popup-create--cform',

			step: '1',

			template: 'blank',

			events: {
				"click .select-quiz-template": "selectTemplate",
				"click .forminator-popup-close": "close",
				"change .forminator-new-form-type": "clickTemplate",
				"click #forminator-build-your-form": "handleMouseClick",
				"keyup": "handleKeyClick"
			},

			popupTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-form-popup-tpl' ).html()),

			newFormTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-form-tpl' ).html()),

			newFormContent: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-form-content-tpl' ).html() ),

			render: function() {
				var $popup = jQuery( '#forminator-popup');

				if( this.step === '1' ) {
					this.$el.html( this.popupTpl({
						templates: Forminator.Data.modules.custom_form.templates
					}) );

					this.$el.find( '.select-quiz-template' ).prop( "disabled", false );

					$popup.closest( '.sui-modal' ).removeClass( "sui-modal-sm" );
				}

				if( this.step === '2' ) {
					// Add name field
					this.$el.html( this.newFormTpl() );
					this.$el.find('.sui-box-body').html( this.newFormContent() );
					if( this.template === 'registration' ) {
						this.$el.find('#forminator-template-register-notice').show();
						this.$el.find('#forminator-form-name').val( Forminator.l10n.popup.registration_name );
					}
					if( this.template === 'login' ) {
						this.$el.find('#forminator-template-login-notice').show();
						this.$el.find('#forminator-form-name').val( Forminator.l10n.popup.login_name );
					}

					$popup.closest( '.sui-modal' ).addClass( 'sui-modal-sm' );
				}
			},

			close: function( e ) {
				e.preventDefault();

				Forminator.Popup.close();
			},

			clickTemplate: function( e ) {
				this.$el.find( '.select-quiz-template' ).prop( "disabled", false );
			},

			selectTemplate: function( e ) {
				e.preventDefault();

				var template = this.$el.find( 'input[name=forminator-form-template]:checked' ).val();

				this.template = template;

				this.step = '2';
				this.render();
			},

			handleMouseClick: function( e ) {
				this.createQuiz( e );
			},

			handleKeyClick: function( e ) {
				e.preventDefault();

				// If enter create form
				if( e.which === 13 ) {
					this.createQuiz( e );
				}
			},

			createQuiz: function( e ) {
				var $form_name = $( e.target ).closest( '.sui-box' ).find( '#forminator-form-name' );

				if( $form_name.val().trim() === "" ) {
					$( e.target ).closest( '.sui-box' ).find( '.sui-error-message' ).show();
				}  else {
					var url = Forminator.Data.modules.custom_form.new_form_url

					$( e.target ).closest( '.sui-box' ).find( '.sui-error-message' ).hide();

					form_url = url + '&name=' + $form_name.val();

					form_url = form_url + '&template=' + this.template;

					window.location.href = form_url;
				}
			}
		});
	});
})(jQuery);
