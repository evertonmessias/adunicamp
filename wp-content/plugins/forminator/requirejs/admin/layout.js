(function( $, doc ) {
    "use strict";

    (function () {

        $( function () {
        	if (typeof window.Forminator === 'object' && typeof window.Forminator.Utils === 'object') {
        		Forminator.Utils.sui_delegate_events();
	        }

	        /**
	         * ======START Entries Page=====
	         */
	        // filter entries toggle
	        $('.forminator-toggle-entries-filter').on("click", function (e) {
		        $(this).toggleClass('sui-active');
		        $(this).closest('.sui-box-body').find('.sui-pagination-filter').toggleClass('sui-open');
		        return false
	        });


	        //Datepicker
	        if (typeof $.fn.daterangepicker !== 'undefined') {
		        var entries_date_picker_range = {};
		        if (typeof window.forminator_entries_datepicker_ranges !== 'undefined') {
			        entries_date_picker_range = window.forminator_entries_datepicker_ranges;
		        }
		        $('input.forminator-entries-filter-date').daterangepicker({
			        autoUpdateInput: false,
			        autoApply: true,
			        alwaysShowCalendars: true,
			        ranges: entries_date_picker_range,
			        locale: forminatorl10n.daterangepicker
		        });
		        $('input.forminator-entries-filter-date').on('apply.daterangepicker', function (ev, picker) {
			        $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
		        });
	        }

	        // before filter submit
	        // remove nonce and http referer
	        $('form.forminator-entries-actions').on('submit', function () {
		        if ($(this).find('select[name=entries-action]').val() === '' && $(this).find('select[name=entries-action-bottom]').val() === '') {
			        $(this).find('fieldset.forminator-entries-nonce').attr('disabled', 'disabled');
		        } else {
			        $(this).find('fieldset.forminator-entries-nonce').removeAttr('disabled')
		        }
		        return true;
	        });

	        // on clear filters
	        $('.forminator-entries-clear-filter').on("click", function () {
		        $(this).closest('.sui-pagination-filter').find('input[name=date_range]').val('').trigger('change');
		        $(this).closest('.sui-pagination-filter').find('input[name=search]').val('').trigger('change');
		        $(this).closest('.sui-pagination-filter').find('input[name=min_id]').val('').trigger('change');
		        $(this).closest('.sui-pagination-filter').find('input[name=max_id]').val('').trigger('change');

		        $(this).closest('.sui-pagination-filter').find('select[name=order_by] option').removeAttr('selected');
		        $(this).closest('.sui-pagination-filter').find('select[name=order_by]').val('').trigger('change');

		        $(this).closest('.sui-pagination-filter').find('select[name=order_by] option').removeAttr('selected');
		        $(this).closest('.sui-pagination-filter').find('select[name=order_by]').val('').trigger('change');

		        $(this).closest('.sui-pagination-filter').find('select[name=order] option').removeAttr('selected');
		        $(this).closest('.sui-pagination-filter').find('select[name=order]').val('').trigger('change');

		        $(this).closest('.sui-pagination-filter').find('.forminator-field-select-tab .sui-tabs-menu label[data-tab-index=1]').trigger('click');

		        $(this).closest('.sui-pagination-filter').find('fieldset.forminator-entries-fields-filter').attr('disabled', 'disabled');

		        return false;

	        });

	        // Display fields tabs
	        $('.forminator-field-select-tab .sui-tabs-menu label').on("click", function () {
		        var tab_index = $(this).data('tab-index');
		        tab_index     = +tab_index;

		        $(this).closest('.sui-side-tabs').find('.sui-tabs-menu label').removeClass('active');
		        $(this).addClass('active');

		        $(this).closest('.sui-side-tabs').find('.sui-tabs-content .sui-tab-content').removeClass('active');
		        $(this).closest('.sui-side-tabs').find('.sui-tabs-content .sui-tab-content[data-tab-index=' + tab_index + ']').addClass('active');


		        if (tab_index === 1) {
			        $(this).closest('.sui-side-tabs').find('fieldset.forminator-entries-fields-filter').attr('disabled', 'disabled');
		        } else {
			        $(this).closest('.sui-side-tabs').find('fieldset.forminator-entries-fields-filter').removeAttr('disabled');
		        }

	        });

            $( "#wpf-cform-check_all" ).on( "click", function ( e ) {
				var checked = this.checked;
				var table = $(this).closest('table');
				$(table).find( ".sui-checkbox input" ).each( function () {
					this.checked = checked;
				});
			});

			// Resend Activation link
			$( document ).on( 'click', '.resend-activation-btn', function(e){
				e.preventDefault();
				const $btn = $( e.currentTarget );
				$btn.prop( 'disabled', true );

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: {
						action: 'forminator_resend_activation_link',
						key: $btn.data( 'activation-key' ),
						_ajax_nonce: $btn.data( 'nonce' )
					},
					success: function( result ){
						var status = 'success';

						if ( ! result.success ) {
							status = 'error';
						}

						Forminator.Notification.open( status, result.data, 4000 );
					}
				}).always(function () {
					$btn.prop( 'disabled', false );
				});
			});

			// Resend Notification Email.
			$( document ).on( 'click', '.forminator-resend-notification-email', function(e){
				e.preventDefault();
				const $btn = $( e.currentTarget );
				$btn.prop( 'disabled', true );

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: {
						action: 'forminator_resend_notification_email',
						entry_id: $btn.data( 'entry-id' ),
						_ajax_nonce: $btn.data( 'nonce' )
					},
					success: function( result ){
						var status = 'success';

						if ( ! result.success ) {
							status = 'error';
						}

						Forminator.Notification.open( status, result.data, 4000 );
					}
				}).always(function () {
					$btn.prop( 'disabled', false );
				});
			});

            /** ====== END Entries Page===== **/


            //cform,poll and quiz all check
            $('#forminator-check-all-modules').on("click", function () {
	            var checked = this.checked;
	            if ($('#forminator-modules-list').length) {
		            //Selects elements that have the specified attribute with a value
		            // either equal to a given string or starting with that string followed by a hyphen (-).
		            $('#forminator-modules-list').find('.sui-checkbox input[id|="wpf-module"]').each(function () {
			            this.checked = checked;
		            });

		            if ($('form[name="bulk-action-form"] input[name="ids"]').length) {
			            var ids = $('#forminator-modules-list').find('.sui-checkbox input[id|="wpf-module"]:checked').map(function () {
					            if (parseFloat(this.value)) return this.value;
				            }
			            ).get().join(',');
			            $('form[name="bulk-action-form"] input[name="ids"]').val(ids);
		            }
	            }
            });

			//cform,poll and quiz single check
			if ( 0 !== $( 'form[name="bulk-action-form"]' ).length ) {
				$( document ).on( "click", ".sui-checkbox input", function(){
					if ( $( 'form[name="bulk-action-form"] input[name="ids"]' ).length ) {
						var ids = $( ".sui-checkbox input:checked" ).map( function() { if ( parseFloat( this.value ) ) return this.value; } ).get().join( ',' );
						$( 'form[name="bulk-action-form"] input[name="ids"]' ).val( ids );
					}
					if( $(this).attr('id') !== 'forminator-check-all-modules') {
						$('#forminator-check-all-modules').prop("checked", false);
					}
				});
			}

			// ACTION minimize
			$( function () {
				var $this = $( ".wpmudev-can--hide" ),
					$button = $this.find( ".wpmudev-box-header" )
				;

				$button.on( "click", function () {
					var $parent = $( this ).closest( ".wpmudev-can--hide" );
					$parent.toggleClass( "wpmudev-is--hidden" );
				});
			});

			// ACTION open entries
			$(document).on('click', '.wpmudev-open-entry', function(e){

				if ($(e.target).attr('type') === 'checkbox' || $(e.target).hasClass('wpdui-icon-check') ) {
					return;
				}
				e.preventDefault();
				e.stopPropagation();

				var $this = $(this),
					$entry_id = $this.data('entry'),
					$entry = $("#forminator-" + $entry_id),
					$open = true;

				if ( $entry.hasClass( 'wpmudev-is_open' ) ) {
					$open = false;
				}
				$('.wpmudev-entries--result').removeClass('wpmudev-is_open');
				if ( $open ) {
					$entry.toggleClass('wpmudev-is_open');
				}
			});

			// OPEN control menu
			$( function () {
				var $this = $( ".wpmudev-result--menu" ),
					$button = $this.find( ".wpmudev-button-action" );

				$button.on( "click", function () {
					var $menu = $( this ).next( ".wpmudev-menu" );

					// Close all already opened menus
					$( ".wpmudev-result--menu.wpmudev-active" ).removeClass( "wpmudev-active" );
					$( ".wpmudev-button-action.wpmudev-active" ).not( $( this ) ).removeClass( "wpmudev-active" );
					$( ".wpmudev-menu" ).not( $menu ).addClass( "wpmudev-hidden" );

					$( this ).toggleClass( "wpmudev-active" );
					$menu.toggleClass( "wpmudev-hidden" );
				});

			});

			// ITEMS position
			$( function () {

				var $this   = $( ".wpmudev-list" ),
					$table  = $this.find( ".wpmudev-list-table" ),
					$item   = $table.find( ".wpmudev-table-body tr" )
				;

				var $totalItems = $item.length,
					$itemCount  = $totalItems
				;

				$item.each(function(){
					$( this ).find( '.wpmudev-body-menu' ).css( 'z-index', $itemCount );
					$itemCount--;
				});

			});

	        // insert text
	        $( function () {
		        $('body').on('change', '.sui-insert-variables select', function (e) {
			        var $this = $(e.target);

			        var textarea_id = $this.data('textarea-id');
			        if (textarea_id) {
				        e.preventDefault();
				        if ($('#' + textarea_id).length > 0) {
					        var textarea     = $('input#' + textarea_id + ',textarea#' + textarea_id);
					        var textarea_val = textarea.val();
					        textarea.val(textarea_val + ' ' + $this.val());
					        textarea.trigger('change', textarea.val());
				        }
				        return false;
			        }

		        });

				$( document ).on( "click", '.copy-clipboard', function ( e ) {
					e.preventDefault();

					copyToClipboard( $( this ).data( 'shortcode' ) );

					Forminator.Notification.open( 'success', Forminator.l10n.options.shortcode_copied, 4000 );
				});


				$('body').on('click', '.delete-poll-submission', function (e) {
					var $target = $(e.target);
					var new_request = {
						action: 'forminator_delete_poll_submissions',
						id: $target.data('id'),
						_ajax_nonce: $target.data('nonce'),
					};
					$target.addClass('sui-button-onload');

					$.post({
						url: Forminator.Data.ajaxUrl,
						type: 'post',
						data: new_request
					})
						.done(function (result) {
							// Update screen
							if( result.success ) {
								jQuery('.sui-poll-submission').addClass('sui-message').html('').html(result.data.html);
							}

							Forminator.Popup.close();

							// Handle notifications
							if (!_.isUndefined(result.data.notification) &&
								!_.isUndefined(result.data.notification.type) &&
								!_.isUndefined(result.data.notification.text) &&
								!_.isUndefined(result.data.notification.duration)
							) {

								Forminator.Notification.open(
									result.data.notification.type,
									result.data.notification.text,
									result.data.notification.duration
								)
									.done(function () {});

							}
						});

				});

				// Dismiss admin notice.
				$('.forminator-grouped-notice .notice-dismiss').on('click', function(e){
					e.preventDefault();
					const $notice = $(e.currentTarget).closest('.forminator-grouped-notice');

					jQuery.post(
						ajaxurl,
						{
							action: 'forminator_dismiss_notice',
							slug: $notice.data('notice-slug'),
							_ajax_nonce: $notice.data('nonce')
						}
					).always(function () {
						$notice.hide();
					});
				});
			});

			/*
			 * Ajax module Search
			 */
			if ( 0 !== $( '#forminator-search-modules' ).length ) {

				var $searchForm   = $( '#forminator-search-modules' );
				var $pageParam 	  = $searchForm.find( 'input[name="page"]' ).val();
				var $resetUrl 	  = forminatorData.adminUrl + 'admin.php?page=' + $pageParam;
				var $searchInput  = $searchForm.find( 'input[name="search"]' );
				var $searchKey 	  = $searchInput.val();
				var $modulesList  = $( '#forminator-modules-list' );
				var $searchLoader = $( '#search_loader' );

				$searchForm.on( 'submit', function ( e ) {
					e.preventDefault();

					// Redefine here to get the right value
					$searchInput = $( this ).find( 'input[name="search"]' );
					$searchKey 	 = $searchInput.val();

					// if submitted without search key, reload to original state
					if ( 0 === $searchKey.length ) {

						if ( 'true' === $( this ).data( 'searched' ) ) {
							window.location.href = $resetUrl;
						}

						return;
					}

					// Set searched data to true to check if page needs to be reloaded if search key is empty
					$( this ).data( 'searched', 'true' );

					$.ajax({
						url: Forminator.Data.ajaxUrl,
						type: "POST",
						data: {
							action				: "forminator_module_search",
							_ajax_nonce			: $( this ).find( '#forminator-nonce-search-module' ).val(),
							search_keyword		: $searchKey,
							modules				: $( this ).find( 'input[name="modules"]' ).val(),
							module_slug			: $( this ).find( 'input[name="module_slug"]' ).val(),
							preview_title		: $( this ).find( 'input[name="preview_title"]' ).val(),
							sql_month_start_date: $( this ).find( 'input[name="sql_month_start_date"]' ).val(),
							wizard_page			: $( this ).find( 'input[name="wizard_page"]' ).val(),
							preview_dialog		: $( this ).find( 'input[name="preview_dialog"]' ).val(),
							export_dialog		: $( this ).find( 'input[name="export_dialog"]' ).val(),
							post_type			: $( this ).find( 'input[name="post_type"]' ).val(),
							page				: $pageParam
						},
						beforeSend: function () {
							$modulesList.empty();
							$searchLoader.show();
							$searchInput.prop( 'disabled', true );
							$( '.sui-pagination' ).remove();
							$( '.sui-pagination-results' ).html('');
							$( '#forminator-search-modules .search-reset' ).hide();
						},
						success: function( result ){
							$searchLoader.hide();
							$modulesList.html( result.data );
							$( 'html' ).animate( { scrollTop: $modulesList.offset().top - 150 }, 300);
							$( 'form[name="bulk-action-form"]' ).find( 'input[name="msearch"]' ).val( $searchKey );
							$modulesList.find( '.module-actions input[name="msearch"]' ).val( $searchKey );
							$searchInput.prop( 'disabled', false );
							$( '.sui-pagination-results' ).html( window.singularPluralText(
																	$modulesList.find( '.sui-accordion-item' ).length,
																	Forminator.l10n.form.result,
																	Forminator.l10n.form.results
																 ) );
							$( '#forminator-search-modules .search-reset' ).show();
						}
					});
				});

				// Auto submit search after page reload from module actions (publish, duplicate, etc)
				if ( 0 !== $searchInput.length && 0 !== $searchKey.length ) {
					$searchForm.submit();
				}

				$( document ).on( 'click', '#forminator-search-modules .search-reset', function( e ) {
					e.preventDefault();
					window.location.href = $resetUrl;
				});
			}

			// Open popup for Apply Appearance Preset action.
			$( document ).on( 'click', '.sui-box-search .sui-button', function( e ) {
				var action = $( 'select[name="forminator_action"]' ).val();
				if ( 'apply-preset-forms' === action ) {
					e.preventDefault();
					$( '#forminator_bulk_apply_preset' ).trigger( 'click' );
				}
			});

    	});

        $( window ).on( 'load', function () {
            // On page load, trigger show submissions
            if (
				typeof window.Forminator === 'object' &&
                typeof window.Forminator.Utils === 'object' &&
                 'forminator-entries' === Forminator.Utils.get_url_param( 'page' ) &&
                 false === Forminator.Utils.get_url_param( 'form_type' ) &&
                 false === Forminator.Utils.get_url_param( 'form_id' )
               ) {
                $( '.show-submissions' ).trigger( 'click' );
            }
        });

        /*
         * Refresh page when back button is used
         * @url https://stackoverflow.com/questions/43043113/how-to-force-reloading-a-page-when-using-browser-back-button
         */
        $( window ).on( 'pageshow', function ( event ) {
            if ( event.persisted ||
                 ( typeof window.performance != "undefined" &&
                 window.performance.getEntriesByType("navigation")[0].type === "back_forward" )
               ) {
                window.location.reload();
            }
        });

    }());
}( jQuery, document ));

function copyToClipboard( data ) {
    var $temp = jQuery( '<input />' );
    jQuery( "body" ).append( $temp );
    $temp.val( data ).select();
    document.execCommand( "copy" );
    $temp.remove();
}

function copyToClipboardModal( el ) {
    el.trigger( 'select' );
    document.execCommand( "copy" );
}

// noinspection JSUnusedGlobalSymbols
var forminator_render_captcha = function () {
	// TODO: avoid conflict with another plugins that provide recaptcha
	//  notify forminator front that grecaptcha loaded. anc can be used
	jQuery('.forminator-g-recaptcha').each(function () {
		var size = jQuery( this ).data('size'),
			data = {
				sitekey: jQuery( this ).data('sitekey'),
				theme: jQuery( this ).data('theme'),
				badge: jQuery( this ).data('badge'),
				size: size
			};

		if (data.sitekey !== "") {
			// noinspection Annotator
			var widget = window.grecaptcha.render( jQuery(this)[0], data );
		}
	});
};
