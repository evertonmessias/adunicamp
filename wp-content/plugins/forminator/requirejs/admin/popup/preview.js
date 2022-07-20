(function ($) {
	formintorjs.define([
		'text!tpl/dashboard.html',
	], function( popupTpl ) {
		return Backbone.View.extend({
			className: 'sui-box-body',

			initialize: function( options ) {
				var self = this;
				var args = {
					action       : '',
					type         : '',
					id           : '',
					preview_data : {},
					enable_loader: true
				};
				if ( 'forminator_quizzes' === options.type ) {
					args.has_lead = options.has_lead;
					args.leads_id  = options.leads_id;
				}
				options            = _.extend( args, options );

				this.action        = options.action;
				this.type          = options.type;
				this.nonce         = options.nonce;
				this.id            = options.id;
				this.render_id     = 0;
				this.preview_data  = options.preview_data;
				this.enable_loader = options.enable_loader;

				if ( 'forminator_quizzes' === options.type ) {
					this.has_lead = options.has_lead;
					this.leads_id  = options.leads_id;
				}

				$(document).off('after.load.forminator');
				$(document).on('after.load.forminator', function(e){
					self.after_load();
				});

				return this.render();
			},

			render: function () {
				var self = this,
				    tpl  = false,
				    data = {}
				;

				data.action           = this.action;
				data.type             = this.type;
				data.id               = this.id;
				data.render_id        = this.render_id;
				data.nonce				 = this.nonce;
				data.is_preview       = 1;
				data.preview_data     = this.preview_data;
				data.last_submit_data = {};

				if ( 'forminator_quizzes' === this.type ) {
					data.has_lead  = this.has_lead;
					data.leads_id  = this.leads_id;
				}

				if (this.enable_loader) {
					var div_preloader = '';
					if ('sui-box-body' !== this.className) {
						div_preloader += '<div class="sui-box-body">';
					}
					div_preloader +=
						'<div class="fui-loading-dialog">' +
							'<p style="margin: 0; text-align: center;" aria-hidden="true"><span class="sui-icon-loader sui-md sui-loading"></span></p>' +
							'<p class="sui-screen-reader-text">Loading content...</p>' +
						'</div>';
					;
					if ('sui-box-body' !== this.className) {
						div_preloader += '</div>';
					}

					self.$el.html(div_preloader);
				}

				var dummyForm = $('<form id="forminator-module-' + this.id + '" data-forminator-render="' + this.render_id+ '" style="display:none"></form>');
				self.$el.append(dummyForm);

				$(self.$el.find('#forminator-module-' + this.id +'[data-forminator-render="' + this.render_id+ '"]').get(0)).forminatorLoader(data);

			},

			after_load: function() {
				var self = this;
				self.$el.find('div[data-form="forminator-module-' + this.id + '"]').remove();
				self.$el.find(".fui-loading-dialog").remove();
			}
		});
	});
})(jQuery);
