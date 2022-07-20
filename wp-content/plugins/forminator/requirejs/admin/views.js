(function ($) {
	formintorjs.define([
		'admin/dashboard',
		'admin/settings-page',
		'admin/popups',
		'admin/addons/addons',
		'admin/addons-page',
	], function( Dashboard, SettingsPage, Popups, Addons, AddonsPage ) {
		return {
			"Views": {
				"Dashboard": Dashboard,
				"SettingsPage": SettingsPage,
				"Popups": Popups,
				"AddonsPage": AddonsPage,
			}
		}
	});
})(jQuery);
