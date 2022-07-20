(function ($, doc) {
	"use strict";

	(function () {
		$(function () {
			Forminator_Shortcode_Generator.init();
		});

	}());

	var Forminator_Shortcode_Generator = {

		init: function () {

			// Add proper class to body
			$( 'body' ).addClass( 'sui-forminator-scgen ' + forminatorScgenData.suiVersion );

			// Init tabs
			SUI.suiTabs();

			// Init SUI Select2.
			this.init_select();

			// Load modal
			$( '#forminator-scgen-modal' ).show();

			// Handle modal open click
			$(document).on("click", "#forminator-generate-shortcode", this.open_modal );

			// Handle modal close click
			$(document).on("click", ".sui-dialog .sui-dialog-close", this.close_modal );
			$(document).on("click", ".sui-dialog-overlay", this.close_modal);

			// Handle modal custom form insert
			$(document).on("click", ".wpmudev-insert-cform", this.insert_form );

			// Handle modal poll insert
			$(document).on("click", ".wpmudev-insert-poll", this.insert_poll );

			// Handle modal quiz insert
			$(document).on("click", ".wpmudev-insert-quiz", this.insert_quiz );
		},

		init_select: function () {

			setTimeout( function(){
				SUI.select.init( $( '#forminator-scgen-modal .sui-select' ) );
			}, 10 );
		},

		open_modal: function( e ) {

			SUI.openModal(
				'forminator-popup',
				e.target,
				undefined,
				false,
				true
			);
		},

		close_modal: function() {

			// Close dialog
			SUI.closeModal();

			setTimeout( function() {

				// Hide error on fields
				$( '#forminator-popup' ).find( '.sui-tabs .sui-form-field' ).removeClass( 'sui-form-field-error' );
				$( '#forminator-popup' ).find( '.sui-tabs .sui-form-field .sui-error-message' ).hide();
			}, 1000 );
		},

		insert_form: function( e ) {

			var button   = $( this ),
				curTab   = button.closest( '.fui-simulate-footer' ).parent( 'div' ),
				curForm  = curTab.find( '.sui-form-field' ),
				allForms = button.closest( '.sui-tabs' ).find( '.sui-form-field' ),
				moduleId = $( '.forminator-custom-form-list' ).val()
				;

			button.addClass( 'sui-button-onload' );

			setTimeout( function() {

				button.removeClass( 'sui-button-onload' );

				if ( moduleId ) {
					allForms.removeClass( 'sui-form-field-error' );
					allForms.find( '.sui-error-message' ).hide();
					Forminator_Shortcode_Generator.insert_shortcode( 'forminator_form', moduleId );
				} else {
					curForm.addClass( 'sui-form-field-error' );
					curForm.find( '.sui-error-message' ).show();
				}

			}, 500 );

			e.preventDefault();
			e.stopPropagation();

		},

		insert_poll: function( e ) {

			var button   = $( this ),
				curTab   = button.closest( '.fui-simulate-footer' ).parent( 'div' ),
				curForm  = curTab.find( '.sui-form-field' ),
				allForms = button.closest( '.sui-tabs' ).find( '.sui-form-field' ),
				moduleId = $( '.forminator-insert-poll' ).val()
				;

			button.addClass( 'sui-button-onload' );

			setTimeout( function() {

				button.removeClass( 'sui-button-onload' );

				if ( moduleId ) {
					allForms.removeClass( 'sui-form-field-error' );
					allForms.find( '.sui-error-message' ).hide();
					Forminator_Shortcode_Generator.insert_shortcode( 'forminator_poll', moduleId );
				} else {
					curForm.addClass( 'sui-form-field-error' );
					curForm.find( '.sui-error-message' ).show();
				}

			}, 500 );

			e.preventDefault();
			e.stopPropagation();

		},

		insert_quiz: function( e ) {

			var button   = $( this ),
				curTab   = button.closest( '.fui-simulate-footer' ).parent( 'div' ),
				curForm  = curTab.find( '.sui-form-field' ),
				allForms = button.closest( '.sui-tabs' ).find( '.sui-form-field' ),
				moduleId = $( '.forminator-quiz-list' ).val()
				;

			button.addClass( 'sui-button-onload' );

			setTimeout( function() {

				button.removeClass( 'sui-button-onload' );

				if ( moduleId ) {
					allForms.removeClass( 'sui-form-field-error' );
					allForms.find( '.sui-error-message' ).hide();
					Forminator_Shortcode_Generator.insert_shortcode( 'forminator_quiz', moduleId );
				} else {
					curForm.addClass( 'sui-form-field-error' );
					curForm.find( '.sui-error-message' ).show();
				}

			}, 500 );

			e.preventDefault();
			e.stopPropagation();

		},

		insert_shortcode: function (module, id) {

			var shortcode = '[' + module + ' id="' + id + '"]';
			window.parent.send_to_editor( shortcode );

			SUI.closeModal();
		}
	};
}(jQuery, document));
