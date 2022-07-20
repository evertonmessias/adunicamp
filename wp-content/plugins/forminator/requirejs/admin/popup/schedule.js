(function ($) {
	formintorjs.define([
		'text!tpl/dashboard.html',
	], function (popupTpl) {
		return Backbone.View.extend({

			popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-exports-schedule-popup-tpl').html()),

			events: {
				'change select[name="interval"]': "on_change_interval",
				// 'change #forminator-enable-scheduled-exports': 'on_change_enabled',
				'click .sui-toggle-label': 'click_label',
				'click .tab-labels .sui-tab-item': 'click_tab_label',
				'click .wpmudev-action-done': 'submit_schedule',
			},

			render: function () {

				this.$el.html(this.popupTpl({}));

				// Delegate SUI events
				Forminator.Utils.sui_delegate_events();

				var data = forminatorl10n.exporter;

				this.$el.find('input[name="if_new"]').prop('checked', data.if_new);

				this.set_enabled(data.enabled);

				this.$el.find('select[name="interval"]').change();
				if (data.email === null) {
					return;
				}
				this.$el.find('select[name="interval"]').val(data.interval);
				this.$el.find('select[name="day"]').val(data.day);
				this.$el.find('select[name="month_day"]').val( (data.month_day ? data.month_day : 1) );
				this.$el.find('select[name="hour"]').val(data.hour);

				if(data.interval === 'weekly') {
					this.$el.find('select[name="day"]').closest('.sui-form-field').show();
				} else if(data.interval === 'monthly') {
					this.$el.find('select[name="month_day"]').closest('.sui-form-field').show();
				}

			},

			set_enabled: function(enabled) {
				if (enabled) {
					this.$el.find('input[name="enabled"][value="true"]').prop('checked', true);
					this.$el.find('input[name="enabled"][value="false"]').prop('checked', false);


					this.$el.find('.tab-label-disable').removeClass('active');
					this.$el.find('.tab-label-enable').addClass('active');

					this.$el.find('.schedule-enabled').show();
					this.$el.find('input[name="email"]').prop('required', true);
				} else {
					this.$el.find('input[name="enabled"][value="false"]').prop('checked', true);
					this.$el.find('input[name="enabled"][value="true"]').prop('checked', false);

					this.$el.find('.tab-label-disable').addClass('active');
					this.$el.find('.tab-label-enable').removeClass('active');

					this.$el.find('.schedule-enabled').hide();
				}

			},

			load_select: function() {
				var data = forminatorl10n.exporter,
					options = {
						tags: true,
						tokenSeparators: [ ',', ' ' ],
						language: {
							searching: function() {
								return data.searching;
							},
							noResults: function() {
								return data.noResults;
							},
						},
						ajax: {
							url: forminatorData.ajaxUrl,
							type: 'POST',
							delay: 350,
							data: function( params ) {
								return {
									action: 'forminator_builder_search_emails',
									_wpnonce: forminatorData.searchNonce,
									q: params.term,
								};
							},
							processResults: function( data ) {
								return {
									results: data.data,
								};
							},
							cache: true,
						},
						createTag: function( params ) {
							const term = params.term.trim();
							if ( ! Forminator.Utils.is_email_wp( term ) ) {
								return null;
							}
							return {
								id: term,
								text: term,
							};
						},
						insertTag: function( data, tag ) {
							// Insert the tag at the end of the results
							data.push( tag );
						},

					};

				Forminator.Utils.forminator_select2_tags( this.$el, options );
			},

			on_change_interval: function(e) {
				//hide column
				this.$el.find('select[name="day"]').closest('.sui-form-field').hide();
				this.$el.find('select[name="month_day"]').closest('.sui-form-field').hide();
				if(e.target.value === 'weekly') {
					this.$el.find('select[name="month-day"]').closest('.sui-form-field').hide();
					this.$el.find('select[name="day"]').closest('.sui-form-field').show();
				} else if(e.target.value === 'monthly') {
					this.$el.find('select[name="month_day"]').closest('.sui-form-field').show();
					this.$el.find('select[name="day"]').closest('.sui-form-field').hide();
				}

			},

			click_label: function(e){
				e.preventDefault();

				// Simulate label click
				this.$el.closest('.sui-form-field').find( '.sui-toggle input' ).click();
			},

			click_tab_label: function (e) {
				var $target = $(e.target);

				if ($target.closest('.sui-tab-item').hasClass('tab-label-disable')) {
					this.set_enabled(false);
				} else if ($target.closest('.sui-tab-item').hasClass('tab-label-enable')) {
					this.load_select();
					this.set_enabled(true);
				}
			},

			submit_schedule: function (e) {
				this.$el.find('form.schedule-action').trigger('submit');
			},

		});
	});
})(jQuery);
