(function ($) {
	formintorjs.define([
		'text!tpl/dashboard.html',
	], function( popupTpl ) {
		return Backbone.View.extend({
			className: 'sui-box-body',

			events: {
				"click .wpmudev-action-done": "save",
				"click .wpmudev-action-ajax-done": "ajax_save",
				"click .wpmudev-action-ajax-cf7-import": "ajax_cf7_import",
				"click .wpmudev-button-clear-exports": "clear_exports",
				// Add poll funcitonality so the custom answer input shows up on preview
				"click .forminator-radio--field": "show_poll_custom_input",
				"click .forminator-popup-close": "close_popup",
				"click .forminator-retry-import": "ajax_cf7_import",
				"change #forminator-choose-import-form": "import_form_action",
				"change .forminator-import-forms": "import_form_action",
			},

			initialize: function( options ) {
				options            = _.extend({
					action       : '',
					nonce        : '',
					data         : '',
					id           : '',
					enable_loader: true
				}, options);

				this.action        = options.action;
				this.nonce         = options.nonce;
				this.data          = options.data;
				this.id            = options.id;
				this.enable_loader = options.enable_loader;

				return this.render();
			},

			render: function() {
				var self = this,
					tpl = false,
					data = {}
				;

				data.action = 'forminator_load_' + this.action + '_popup';
				data._ajax_nonce = this.nonce;
				data.data = this.data;

				if( this.id ) {
					data.id = this.id;
				}

				if (this.enable_loader) {
					var div_preloader = '';
					if ('sui-box-body' !== this.className) {
						div_preloader += '<div class="sui-box-body">';
					}
					div_preloader +=
						'<p class="fui-loading-dialog" aria-label="Loading content">' +
							'<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>' +
						'</p>'
					;
					if ('sui-box-body' !== this.className) {
						div_preloader += '</div>';
					}

					self.$el.html(div_preloader);
				}

				// make slightly bigger

				var ajax = $.post({
					url: Forminator.Data.ajaxUrl,
					type: 'post',
					data: data
				})
				.done(function (result) {
					if (result && result.success) {
						// Append & Show content
						self.$el.html(result.data);
						self.$el.find('.wpmudev-hidden-popup').show(400);

						// Delegate SUI events
						Forminator.Utils.sui_delegate_events();

						// Init Pagination on custom form if exist
						var custom_form = self.$el.find('.forminator-custom-form');

						// Delegate events
						self.delegateEvents();
					}
				});

				//remove the preloader
				ajax.always(function () {
					self.$el.find(".fui-loading-dialog").remove();
				});
			},

			save: function ( e ) {
				e.preventDefault();
				var data = {},
					nonce = $( e.target ).data( "nonce" )
				;

				data.action = 'forminator_save_' + this.action + '_popup';
				data._ajax_nonce = nonce;

				// Retieve fields
				$('.wpmudev-popup-form input, .wpmudev-popup-form select').each( function () {
					var field = $( this );
					data[ field.attr('name') ] = field.val();
				});

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: data,
					success: function( result ) {
						Forminator.Popup.close( false, function() {
							window.location.reload();
						});
					}
				});
			},
			ajax_save: function ( e ) {
				var self = this;
				// display error response if avail
				// redirect to url on response if avail
				e.preventDefault();
				var data = {},
				    nonce = $( e.target ).data( "nonce" )
				;

				data.action = 'forminator_save_' + this.action + '_popup';
				data._ajax_nonce = nonce;

				// Retieve fields
				$('.wpmudev-popup-form input, .wpmudev-popup-form select, .wpmudev-popup-form textarea').each( function () {
					var field = $( this );
					data[ field.attr('name') ] = field.val();
				});

				this.$el.find(".sui-button:not(.disable-loader)").addClass("sui-button-onload");

				var ajax = $.ajax({
					url    : Forminator.Data.ajaxUrl,
					type   : "POST",
					data   : data,
					success: function (result) {
						if (true === result.success) {
							var redirect = false;
							if (!_.isUndefined(result.data.url)) {
								redirect = result.data.url;
							}
							Forminator.Popup.close(false, function () {
								if (redirect) {
									location.href = redirect;
								}
							});
						} else {
							const noticeId = 'wpmudev-ajax-error-placeholder';
							const noticeMessage = '<p>' + result.data + '</p>';
							const noticeOption = {
								type: 'error',
								autoclose: {
									timeout: 8000
								}
							};

							if (!_.isUndefined(result.data)) {
								SUI.openNotice( noticeId, noticeMessage, noticeOption );
							}
						}
					}
				});
				ajax.always(function () {
					self.$el.find(".sui-button:not(.disable-loader)").removeClass("sui-button-onload");
				})
			},

			clear_exports: function ( e ) {
				e.preventDefault();
				var data = {},
					self = this,
					nonce = $( e.target ).data( "nonce" ),
					form_id = $( e.target ).data( "form-id" )
				;

				data.action = 'forminator_clear_' + this.action + '_popup';
				data._ajax_nonce = nonce;
				data.id = form_id;

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: data,
					success: function() {
						self.render();
					}
				});
			},
			show_poll_custom_input: function (e) {
				var self = this,
					$input = this.$el.find('.forminator-input'),
					checked = e.target.checked,
					$id = $(e.target).attr('id');

				$input.hide();
				if (self.$el.find('.forminator-input#' + $id + '-extra').length) {
					var $extra = self.$el.find('.forminator-input#' + $id + '-extra');
					if (checked) {
						$extra.show();
					} else {
						$extra.hide();
					}
				}
			},
			ajax_cf7_import: function ( e ) {
				var self = this,
					data = self.$el.find('form').serializeArray();
				// display error response if avail
				// redirect to url on response if avail
				e.preventDefault();

				this.$el.find(".sui-button:not(.disable-loader)").addClass("sui-button-onload");
				this.$el.find('.wpmudev-ajax-error-placeholder').addClass('sui-hidden');
				this.$el.find(".forminator-cf7-imported-fail").addClass("sui-hidden");

				var ajax = $.ajax({
					url    : Forminator.Data.ajaxUrl,
					type   : "POST",
					data   : data,
					xhr: function () {
						var xhr = new window.XMLHttpRequest();
						xhr.upload.addEventListener("progress", function (evt) {
							if ( evt.lengthComputable ) {
								var percentComplete = evt.loaded / evt.total;
								percentComplete = parseInt(percentComplete * 100);
								self.$el.find(".forminator-cf7-importing .sui-progress-text").html( percentComplete + '%');
								self.$el.find(".forminator-cf7-importing .sui-progress-bar span").css( 'width', percentComplete + '%');
							}
						}, false);
						return xhr;
					},
					success: function (result) {
						if (true === result.success) {
							setTimeout(function(){
								self.$el.find(".forminator-cf7-importing").addClass("sui-hidden");
								self.$el.find(".forminator-cf7-imported").removeClass("sui-hidden");
							}, 1000);
						} else {
							if (!_.isUndefined(result.data)) {
								setTimeout(function(){
										self.$el.find(".forminator-cf7-importing").addClass("sui-hidden");
										self.$el.find(".forminator-cf7-imported-fail").removeClass("sui-hidden");
									}, 1000);
								self.$el.find('.wpmudev-ajax-error-placeholder').removeClass('sui-hidden').find('p').text(result.data);
							}
						}
					}
				});
				ajax.always(function (e) {
					self.$el.find(".sui-button:not(.disable-loader)").removeClass("sui-button-onload");
					self.$el.find(".forminator-cf7-import").addClass("sui-hidden");
					self.$el.find(".forminator-cf7-importing").removeClass("sui-hidden");
				});
			},
			close_popup: function() {
				Forminator.Popup.close();
			},

			import_form_action: function(e) {
				e.preventDefault();
				var target = $(e.target),
					value = target.val(),
					btn_action = false;
				if( 'specific' === value ) {
					btn_action = true;
				}
				if( value == null || ( Array.isArray( value ) && value.length < 1 ) ) {
					btn_action = true;
				}
				this.$el.find('.wpmudev-action-ajax-cf7-import').prop( "disabled", btn_action );
			},
		});
	});
})(jQuery);
