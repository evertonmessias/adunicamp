(function ($) {
	formintorjs.define([
		'text!tpl/dashboard.html',
	], function (popupTpl) {
		return Backbone.View.extend({
			className: 'wpmudev-section--popup',

			popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-create-appearance-preset-tpl').html()),
			events: {
				"click #forminator-create-preset" : 'createPreset',
				"keydown #forminator-preset-name" : 'toggleButton',
			},
			initialize: function( options ) {
				this.nonce = options.nonce;
				this.title = options.title;
				this.content = options.content;
				this.$target = options.$target;
			},

			render: function () {
				var	formLabel= this.$target.data('modal-preset-form-label'),
					loadingText= this.$target.data('modal-preset-loading-text'),
					nameLabel= this.$target.data('modal-preset-name-label'),
					namePlaceholder= this.$target.data('modal-preset-name-placeholder');

				this.$el.html(this.popupTpl({
					title: this.title,
					content: this.content,
					formLabel: formLabel,
					loadingText: loadingText,
					nameLabel: nameLabel,
					namePlaceholder: namePlaceholder,
				}));
			},

			toggleButton: function(e) {
				setTimeout( function(){
					var val = $(e.currentTarget).val().trim();
					$('#forminator-create-preset').prop( 'disabled', !val );
				}, 300 );
			},

			createPreset: function(e) {
				e.preventDefault();
				e.stopImmediatePropagation();

				var $target = $(e.target);
				$target.addClass('sui-button-onload-text');

				var formId = this.$el.find('select[name="form_id"]').val();
				var name   = this.$el.find('#forminator-preset-name').val();

				var data = {
					action: 'forminator_create_appearance_preset',
					_ajax_nonce: this.nonce,
					form_id: formId,
					name: name,
				};

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: data,
					success: function( result ) {
						if (result && result.success) {
							Forminator.openPreset( result.data );
						} else {
							Forminator.Notification.open( 'error', result.data, 4000 );
							$target.removeClass('sui-button-onload-text');
						}
					},
					error: function ( error ) {
						Forminator.Notification.open( 'error', error.data, 4000 );
						$target.removeClass('sui-button-onload-text');
					}
				});

				return false;
			}
		});
	});
})(jQuery);
