(function ($) {
	formintorjs.define([
		'text!tpl/dashboard.html',
	], function (popupTpl) {
		return Backbone.View.extend({
			className: 'wpmudev-section--popup',

			popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-apply-appearance-preset-tpl').html()),
			events: {
				"click #forminator-apply-preset" : 'applyPreset',
			},
			initialize: function( options ) {
				this.$target = options.$target;
			},

			render: function () {
				this.$el.html(this.popupTpl({
					description: Forminator.Data.modules.ApplyPreset.description,
					notice: Forminator.Data.modules.ApplyPreset.notice,
					button: Forminator.Data.modules.ApplyPreset.button,
					selectbox: Forminator.Data.modules.ApplyPreset.selectbox,
				}));
			},

			applyPreset: function(e) {
				e.preventDefault();
				e.stopImmediatePropagation();

				var $target = $(e.target);
				$target.addClass('sui-button-onload-text');

				var	id = this.$target.data('form-id'),
					ids = [],
					presetId = this.$el.find('select[name="appearance_preset"]').val();

				if ( id ) {
					ids = [ id ];
				} else {
					ids = $('#forminator_bulk_ids').val().split(',');
				}

				var data = {
					action: 'forminator_apply_appearance_preset',
					_ajax_nonce: Forminator.Data.modules.ApplyPreset.nonce,
					preset_id: presetId,
					ids: ids,
				};

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: data,
					success: function( result ) {
						if (result && result.success) {
							Forminator.Notification.open( 'success', result.data, 4000 );
							Forminator.Popup.close();
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
