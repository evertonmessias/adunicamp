(function ($) {
	formintorjs.define([
		'text!tpl/dashboard.html',
	], function( popupTpl ) {
		return Backbone.View.extend({
			className: 'forminator-popup-create--quiz',

			step: 1,
			pagination: 1,

			type: 'knowledge',

			events: {
				"click .select-quiz-template": "selectTemplate",
				"click .select-quiz-pagination": "selectPagination",
				"click .forminator-popup-back": "goBack",
				"click .forminator-popup-close": "close",
				"change .forminator-new-quiz-type": "clickTemplate",
				"click #forminator-build-your-form": "handleMouseClick",
				"click #forminator-new-quiz-leads": "handleToggle",
				"keyup": "handleKeyClick"
			},

			popupTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-quizzes-popup-tpl' ).html()),

			newFormTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-quiz-tpl' ).html()),

			paginationTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-quiz-pagination-tpl' ).html()),

			newFormContent: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-quiz-content-tpl' ).html() ),

			render: function() {
				var $popup = jQuery( '#forminator-popup');
				$popup.removeClass( "sui-dialog-sm forminator-create-quiz-second-step forminator-create-quiz-pagination-step" );

				if( this.step === 1 ) {
					this.$el.html( this.popupTpl() );

					if ( this.name ) {
						this.$el.find( '#forminator-form-name' ).val( this.name );
						this.$el.find( '#forminator-new-quiz--' + this.type ).prop('checked',true);
					}

					this.$el.find( '.select-quiz-template' ).prop( "disabled", false );

				}

				if( this.step === 2 ) {
					this.$el.html( this.paginationTpl() );
					if ( ! this.pagination ) {
						this.$el.find( '[name="forminator-quiz-pagination"]' ).eq(1).prop('checked',true);
					}
					$popup.addClass( "forminator-create-quiz-pagination-step" );
				}

				if( this.step === 3 ) {
					// Add name field
					this.$el.html( this.newFormTpl() );
					this.$el.find('.sui-box-body').html( this.newFormContent() );

					$popup.addClass( "sui-dialog-sm forminator-create-quiz-second-step" );
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

				var type = this.$el.find( 'input[name=forminator-new-quiz]:checked' ).val();
				var form_name = this.$el.find( '#forminator-form-name' ).val();

				if( form_name.trim() === "" ) {
					$( e.target ).closest( '.sui-box' ).find( '#sui-quiz-name-error' ).show();
				} else {
					this.type = type;
					this.name = form_name;

					this.step = 2;
					this.render();
				}
			},

			goBack: function( e ) {
				e.preventDefault();

				this.step--;
				this.render();
			},

			selectPagination: function( e ) {
				e.preventDefault();

				var pagination = this.$el.find( 'input[name="forminator-quiz-pagination"]:checked' ).val();

				this.pagination = pagination;

				this.step = 3;
				this.render();
			},

			handleMouseClick: function( e ) {
				this.createQuiz( e );
			},

			handleKeyClick: function( e ) {
				e.preventDefault();

				// If enter create form
				if( e.which === 13 ) {
					if( this.step === 1 ) {
						this.selectTemplate( e );
					} else {
						this.createQuiz( e );
					}
				}
			},

			handleToggle: function( e ) {
				var leads = $( e.target ).is(':checked');
				var $notice = $( e.target ).closest( '.sui-box' ).find( '#sui-quiz-leads-description' );

				if ( leads ) {
					$notice.show();
				} else {
					$notice.hide();
				}
			},

			createQuiz: function( e ) {
				var leads = $( e.target ).closest( '.sui-box' ).find( '#forminator-new-quiz-leads' ).is(':checked');

				var url = Forminator.Data.modules.quizzes.knowledge_url;

				if( this.type === "nowrong" ) {
					url = Forminator.Data.modules.quizzes.nowrong_url;
				}

				form_url = url + '&name=' + this.name;

				if ( this.pagination ) {
					form_url = form_url + '&pagination=true';
				}

				if ( leads ) {
					form_url = form_url + '&leads=true';
				}

				window.location.href = form_url;
			}
		});
	});
})(jQuery);
