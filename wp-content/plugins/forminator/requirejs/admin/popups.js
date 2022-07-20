(function ($) {
	formintorjs.define([
		'admin/popup/templates',
		'admin/popup/login',
		'admin/popup/quizzes',
		'admin/popup/schedule',
		'admin/popup/new-form',
		'admin/popup/polls',
		'admin/popup/ajax',
		'admin/popup/delete',
		'admin/popup/preview',
		'admin/popup/reset-plugin-settings',
		'admin/popup/disconnect-stripe',
		'admin/popup/disconnect-paypal',
		'admin/popup/approve-user',
		'admin/popup/delete-unconfirmed-user',
		'admin/popup/create-appearance-preset',
		'admin/popup/apply-appearance-preset',
		'admin/popup/confirm',
		'admin/popup/addons-actions'
	], function(
		TemplatesPopup,
		LoginPopup,
		QuizzesPopup,
		SchedulePopup,
		NewFormPopup,
		PollsPopup,
		AjaxPopup,
		DeletePopup,
		PreviewPopup,
		ResetPluginSettingsPopup,
		DisconnectStripePopup,
		DisconnectPaypalPopup,
		ApproveUserPopup,
		DeleteUnconfirmedPopup,
		CreateAppearancePresetPopup,
		ApplyAppearancePresetPopup,
		confirmationPopup,
		AddonsActions
	) {
		var Popups = Backbone.View.extend({
			el: 'main.sui-wrap',

			events: {
				"click .wpmudev-open-modal": "open_modal",
				"click .wpmudev-button-open-modal": "open_modal"
			},

			initialize: function () {
				var new_form = Forminator.Utils.get_url_param( 'new' ),
					form_title = Forminator.Utils.get_url_param( 'title' )
				;

				if( new_form ) {
					var newForm = new NewFormPopup({
						title: form_title
					});
					newForm.render();

					this.open_popup( newForm, Forminator.l10n.popup.congratulations );
				}

				this.open_export();
				this.open_delete();

				this.maybeShowNotice();

				return this.render();
			},

			render: function() {
				return this;
			},

			maybeShowNotice: function() {
				var notices = Forminator.l10n.notices;
				if ( notices ) {
					$.each(notices, function(i, message) {
						var delay = 4000;
						if ( 'custom_notice' === i ) {
							delay = undefined;
						}
						Forminator.Notification.open( 'success', message, delay );
					});
				}
			},

			open_delete: function() {
				var has_delete = Forminator.Utils.get_url_param( 'delete' ),
					id = Forminator.Utils.get_url_param( 'module_id' ),
					nonce = Forminator.Utils.get_url_param( 'nonce' ),
					type = Forminator.Utils.get_url_param( 'module_type'),
					title = Forminator.l10n.popup.delete_form,
					desc = Forminator.l10n.popup.are_you_sure_form,
					self = this
				;

				if ( type === 'poll' ) {
					title = Forminator.l10n.popup.delete_poll;
					desc = Forminator.l10n.popup.are_you_sure_poll;
				}

				if ( type === 'quiz' ) {
					title = Forminator.l10n.popup.delete_quiz;
					desc = Forminator.l10n.popup.are_you_sure_quiz;
				}

				if ( has_delete ) {
					setTimeout( function() {
						self.open_delete_popup( '', id, nonce, title, desc );
					}, 100 );
				}
			},

			open_export: function() {
				var has_export = Forminator.Utils.get_url_param( 'export' ),
					id = Forminator.Utils.get_url_param( 'module_id' ),
					nonce = Forminator.Utils.get_url_param( 'exportnonce' ),
					type = Forminator.Utils.get_url_param( 'module_type'),
					self = this
				;

				if ( has_export ) {
					setTimeout( function() {
						self.open_export_module_modal( type, nonce, id, Forminator.l10n.popup.export_form, false, true, 'wpmudev-ajax-popup' );
					}, 100 );
				}
			},

			open_modal: function( e ) {
				e.preventDefault();

				var $target = $( e.target ),
					$container = $( e.target ).closest( '.wpmudev-split--item' );

				if( ! $target.hasClass( 'wpmudev-open-modal' ) && ! $target.hasClass( 'wpmudev-button-open-modal' ) ) {
					$target = $target.closest( '.wpmudev-open-modal,.wpmudev-button-open-modal' );
				}

				var $module = $target.data( 'modal' ),
					nonce = $target.data( 'nonce' ),
					id = $target.data( 'form-id' ),
					action = $target.data( 'action' ),
					has_leads = $target.data( 'has-leads' ),
					leads_id = $target.data( 'leads-id' ),
					title = $target.data( 'modal-title' ),
				   content = $target.data('modal-content'),
					button = $target.data('button-text'),
					preview_nonce = $target.data('nonce-preview')
				;

				// Open appropriate popup
				switch ( $module ) {
					case 'custom_forms':
						this.open_cform_popup();
						break;
					case 'login_registration_forms':
						this.open_login_popup();
						break;
					case 'polls':
						this.open_polls_popup();
						break;
					case 'quizzes':
						this.open_quizzes_popup();
						break;
					case 'exports':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.your_exports );
						break;
					case 'exports-schedule':
						this.open_exports_schedule_popup();
						break;
					case 'delete-module':
						this.open_delete_popup( '', id, nonce, title, content, action, button );
						break;
					case 'delete-poll-submission':
						this.open_delete_popup( 'poll', id, nonce, title, content );
						break;
					case 'paypal':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.paypal_settings );
						break;
					case 'preview_cforms':
						if (_.isUndefined(title)) {
							title = Forminator.l10n.popup.preview_cforms
						}
						this.open_preview_popup( id, title, 'forminator_load_form', 'forminator_forms', preview_nonce );
						break;
					case 'preview_polls':
						if (_.isUndefined(title)) {
							title = Forminator.l10n.popup.preview_polls
						}
						this.open_preview_popup( id, title, 'forminator_load_poll', 'forminator_polls', preview_nonce );
						break;
					case 'preview_quizzes':
						if (_.isUndefined(title)) {
							title = Forminator.l10n.popup.preview_quizzes
						}
						this.open_quiz_preview_popup( id, title, 'forminator_load_quiz', 'forminator_quizzes', has_leads, leads_id, preview_nonce );
						break;
					case 'captcha':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.captcha_settings, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'currency':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.currency_settings, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'pagination_entries':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.pagination_entries, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'pagination_listings':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.pagination_listings, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'email_settings':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.email_settings, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'uninstall_settings':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.uninstall_settings, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'privacy_settings':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.privacy_settings, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'create_preset':
						this.create_appearance_preset_modal( nonce, title, content, $target );
						break;
					case 'apply_preset':
						this.apply_appearance_preset_modal( $target );
						break;
					case 'delete_preset':
						this.delete_preset_modal( title, content );
						break;
					case 'export_form':
						this.open_export_module_modal( 'form', nonce, id, Forminator.l10n.popup.export_form, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'export_poll':
						this.open_export_module_modal( 'poll', nonce, id, Forminator.l10n.popup.export_poll, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'export_quiz':
						this.open_export_module_modal( 'quiz', nonce, id, Forminator.l10n.popup.export_quiz, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'import_form':
						this.open_import_module_modal( 'form', nonce, id, Forminator.l10n.popup.import_form, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'import_form_cf7':
						this.open_import_module_modal( 'form_cf7', nonce, id, Forminator.l10n.popup.import_form_cf7, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'import_form_ninja':
						this.open_import_module_modal( 'form_ninja', nonce, id, Forminator.l10n.popup.import_form_ninja, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'import_form_gravity':
						this.open_import_module_modal( 'form_gravity', nonce, id, Forminator.l10n.popup.import_form_gravity, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'import_poll':
						this.open_import_module_modal( 'poll', nonce, id, Forminator.l10n.popup.import_poll, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'import_quiz':
						this.open_import_module_modal( 'quiz', nonce, id, Forminator.l10n.popup.import_quiz, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'reset-plugin-settings':
						this.open_reset_plugin_settings_popup( nonce, title, content );
						break;
					case 'disconnect-stripe':
						this.open_disconnect_stripe_popup( nonce, title, content );
						break;
					case 'disconnect-paypal':
						this.open_disconnect_paypal_popup( nonce, title, content );
						break;
					case 'approve-user-module':
						var activationKey = $target.data('activation-key');
						this.open_approve_user_popup( nonce, title, content, activationKey );
						break;
					case 'delete-unconfirmed-user-module':
						this.open_unconfirmed_user_popup( $target.data( 'form-id' ), nonce, title, content, $target.data('activation-key'), $target.data( 'entry-id' ) );
						break;
					case 'addons_page_details':
						this.open_addons_page_modal( $module, nonce, id, title, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'addons-deactivate':
						this.open_addons_actions_popup( $module, $target.data( 'addon' ), nonce, title, content, $target.data( 'addon-slug' ) );
						break;
				}
			},

			open_popup: function ( view, title, has_custom_box, action_text, action_css_class, action_callback, rendered_call_back, modalSize, modalTitle ) {
				if( _.isUndefined( title ) ) {
					title = Forminator.l10n.custom_form.popup_label;
				}

				var popup_options = {
					title: title
				};
				if (!_.isUndefined(has_custom_box)) {
					popup_options.has_custom_box = has_custom_box;
				}
				if (!_.isUndefined(action_text)) {
					popup_options.action_text = action_text;
				}
				if (!_.isUndefined(action_css_class)) {
					popup_options.action_css_class = action_css_class;
				}
				if (!_.isUndefined(action_callback)) {
					popup_options.action_callback = action_callback;
				}

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}

					if (typeof rendered_call_back === 'function') {
						rendered_call_back.apply(this);
					}
				}, popup_options, modalSize, modalTitle );
			},

			open_ajax_popup: function( action, nonce, id, title, enable_loader, has_custom_box, ajax_div_class_name, modalSize, modalTitle) {
				if( _.isUndefined( title ) ) {
					title = Forminator.l10n.custom_form.popup_label;
				}
				if( _.isUndefined( enable_loader ) ) {
					enable_loader = true;
				}
				if( _.isUndefined( has_custom_box ) ) {
					has_custom_box = false;
				}

				if( _.isUndefined( ajax_div_class_name ) ) {
					ajax_div_class_name = 'sui-box-body';
				}

				var view = new AjaxPopup({
					action: action,
					nonce: nonce,
					id: id,
					enable_loader: true,
					className: ajax_div_class_name,
				});

				var popup_options = {
					title         : title,
					has_custom_box: has_custom_box
				};

				Forminator.Popup.open(function () {
					$(this).append(view.el);
				}, popup_options, modalSize, modalTitle );
			},

			// MODAL: Delete.
			open_delete_popup: function ( module, id, nonce, title, content, action, button) {
				action = action || 'delete';
				var newForm = new DeletePopup({
					module: module,
					id: id,
					action: action,
					nonce: nonce,
					referrer: window.location.pathname + window.location.search,
					button: button,
					content: content
				});
				newForm.render();

				var view = newForm;

				var popup_options = {
					title: title,
					has_custom_box: true
				}

				var modalSize = 'sm';

				var modalTitle = 'center';

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize, modalTitle );
			},

			// MODAL: Create Form.
			open_cform_popup: function () {
				var newForm = new TemplatesPopup({
					type: 'form'
				});
				newForm.render();

				var view = newForm;

				var modalSize = 'lg';

				var popup_options = {
					title: '',
					has_custom_box: true
				};

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize );

			},

			// MODAL: Create Poll.
			open_polls_popup: function() {
				var newForm = new PollsPopup();
				newForm.render();

				var view = newForm;

				var popup_options = {
					title: '',
					has_custom_box: true
				};

				var modalSize = 'sm';

				Forminator.Popup.open( function() {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize );
			},

			// MODAL: Create Quiz.
			open_quizzes_popup: function () {

				var self = this,
					newForm = new QuizzesPopup();

				newForm.render();

				var view = newForm;

				var popup_options = {
					title: Forminator.l10n.quiz.choose_quiz_title,
					has_custom_box: true
				}

				var modalSize = 'lg';

				Forminator.Popup.open( function () {

					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize );

				//this.open_popup( newForm, Forminator.l10n.quiz.choose_quiz_type, true );
			},

			// MODAL: Import.
			open_import_module_modal: function(module, nonce, id, label, enable_loader, has_custom_box, ajax_div_class_name ) {
				var action = '';
				switch(module){
					case 'form':
					case 'form_cf7':
					case 'form_ninja':
					case 'form_gravity':
					case 'poll':
					case 'quiz':
						action = 'import_' + module;
						break;
				}
				this.open_ajax_popup(
					action,
					nonce,
					id,
					label,
					enable_loader,
					has_custom_box,
					ajax_div_class_name,
					'sm',
					'center'
				);
			},

			// MODAL: Export (on Submissions page).
			open_exports_schedule_popup: function () {
				var newForm = new SchedulePopup();
				newForm.render();

				this.open_popup(
					newForm,
					Forminator.l10n.popup.edit_scheduled_export,
					true,
					undefined,
					undefined,
					undefined,
					undefined,
					'md',
					'inline'
				);
			},

			// MODAL: Reset Plugin (on Settings page).
			open_reset_plugin_settings_popup: function (nonce, title, content) {
				var self = this,
					newForm = new ResetPluginSettingsPopup({
						nonce: nonce,
						referrer: window.location.pathname + window.location.search,
						content: content
					});
				newForm.render();

				var view = newForm;

				var popup_options = {
					title: title,
					has_custom_box: true
				}

				var modalSize = 'sm';

				var modalTitle = 'center';

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize, modalTitle );
			},

			// MODAL: Disconnect PayPal (on Settings page).
			open_addons_actions_popup: function ( module, id, nonce, title, content, slug ) {
				var self = this,
					newForm = new AddonsActions({
						module: module,
						id: id,
						nonce: nonce,
						referrer: window.location.pathname + window.location.search,
						content: content,
						forms: 'stripe' === slug ? forminatorData.stripeForms : forminatorData.paypalForms
					});

				newForm.render();
				var view = newForm;

				var popup_options = {
					title: title,
					has_custom_box: true
				}

				var modalSize = 'sm';

				var modalTitle = 'center';

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize, modalTitle );
			},

			open_login_popup: function () {
				var newForm = new LoginPopup();
				newForm.render();

				this.open_popup(
					newForm,
					Forminator.l10n.popup.edit_login_form,
					undefined,
					undefined,
					undefined,
					undefined,
					undefined,
					'md',
					'inline'
				);
			},

			open_settings_modal: function ( type, nonce, id, label, enable_loader, has_custom_box, ajax_div_class_name ) {
				this.open_ajax_popup(
					type,
					nonce,
					id,
					label,
					enable_loader,
					has_custom_box,
					ajax_div_class_name,
					'md',
					'inline'
				);
			},

			open_addons_page_modal: function ( type, nonce, id, label, enable_loader, has_custom_box, ajax_div_class_name ) {
				this.open_ajax_popup(
					type,
					nonce,
					id,
					label,
					enable_loader,
					has_custom_box,
					ajax_div_class_name,
					'md',
					'inline'
				);
			},

			open_export_module_modal: function(module, nonce, id, label, enable_loader, has_custom_box, ajax_div_class_name ) {
				var action = '';
				switch(module){
					case 'form':
					case 'poll':
					case 'quiz':
						action = 'export_' + module;
						break;
				}
				this.open_ajax_popup(
					action,
					nonce,
					id,
					label,
					enable_loader,
					has_custom_box,
					ajax_div_class_name,
					'md',
					'inline'
				);
			},

			open_preview_popup: function( id, title, action, type, nonce ) {
				if( _.isUndefined( title ) ) {
					title = Forminator.l10n.custom_form.popup_label;
				}

				var view = new PreviewPopup( {
					action: action,
					type: type,
					nonce: nonce,
					id: id,
					enable_loader: true,
					className: 'sui-box-body',
				} );

				var popup_options = {
					title         : title,
					has_custom_box: true
				};

				var modalSize = 'lg';

				var modalTitle = 'inline';

				Forminator.Popup.open( function () {
					$( this ).append( view.el );
				}, popup_options, modalSize, modalTitle );
			},

			open_quiz_preview_popup: function( id, title, action, type, has_leads, leads_id, nonce ) {
				if( _.isUndefined( title ) ) {
					title = Forminator.l10n.custom_form.popup_label;
				}

				var view = new PreviewPopup( {
					action: action,
					type: type,
					id: id,
					enable_loader: true,
					className: 'sui-box-body',
					has_lead: has_leads,
					leads_id: leads_id,
					nonce: nonce,
				} );

				var popup_options = {
					title         : title,
					has_custom_box: true
				};

				var modalSize = 'lg';

				var modalTitle = 'inline';

				Forminator.Popup.open(function () {
					$(this).append(view.el);

				}, popup_options, modalSize, modalTitle );
			},

			apply_appearance_preset_modal: function ($target) {
				var newForm = new ApplyAppearancePresetPopup({
						$target: $target
					});
				newForm.render();

				var view = newForm;

				Forminator.Popup.open( function () {
					$( this ).append( view.el );
				}, {
					title: Forminator.Data.modules.ApplyPreset.title,
					has_custom_box: true,
				}, 'sm', 'center' );
			},

			delete_preset_modal: function (title, content) {
				var newForm = new confirmationPopup({
						confirmation_message: content,
						confirm_callback: function () {
							var deletePreset = new Event('deletePreset');
							window.dispatchEvent( deletePreset );
						},
					});
				newForm.render();

				var view = newForm;

				Forminator.Popup.open( function () {
					$( this ).append( view.el );
				}, {
					title: title,
					has_custom_box: true,
				}, 'sm', 'center' );
			},

			create_appearance_preset_modal: function (nonce, title, content, $target) {
				var newForm = new CreateAppearancePresetPopup({
					nonce: nonce,
					$target: $target,
					title: title,
					content: content
				});
				newForm.render();

				var view = newForm;

				Forminator.Popup.open( function () {
					$( this ).append( view.el );
				}, {
					title: title,
					has_custom_box: true,
				}, 'sm', 'center' );
			},

			open_disconnect_stripe_popup: function (nonce, title, content) {
				var self = this;
				var newForm = new DisconnectStripePopup({
					nonce: nonce,
					referrer: window.location.pathname + window.location.search,
					content: content
				});
				newForm.render();

				var view = newForm;

				var popup_options = {
					title: title,
					has_custom_box: true
				}

				var modalSize = 'sm';

				var modalTitle = 'center';

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize, modalTitle );
			},

			open_disconnect_paypal_popup: function (nonce, title, content) {
				var self = this;
				var newForm = new DisconnectPaypalPopup({
					nonce: nonce,
					referrer: window.location.pathname + window.location.search,
					content: content
				});
				newForm.render();

				var view = newForm;

				var popup_options = {
					title: title,
					has_custom_box: true
				}

				var modalSize = 'sm';

				var modalTitle = 'center';

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize, modalTitle );
			},

			open_approve_user_popup: function (nonce, title, content, activationKey) {
				var self = this;
				var newForm = new ApproveUserPopup({
					nonce: nonce,
					referrer: window.location.pathname + window.location.search,
					content: content,
					activationKey: activationKey
				});
				newForm.render();

				var view = newForm;

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, {
					title: title,
					has_custom_box: true,
				}, 'md', 'inline' );
			},

			open_unconfirmed_user_popup: function ( formId, nonce, title, content, activationKey, entryId ) {
				var newForm = new DeleteUnconfirmedPopup({
					formId: formId,
					nonce: nonce,
					referrer: window.location.pathname + window.location.search,
					content: content,
					activationKey: activationKey,
					entryId: entryId
				});
				newForm.render();

				var view = newForm;

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, {
					title: title,
					has_custom_box: true,
				}, 'md', 'inline');
			},
		});

		//init after jquery ready
		jQuery( function() {
			new Popups();
		});
	});
})(jQuery);
