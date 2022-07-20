(function ($) {
	formintorjs.define([
		'admin/views',
	], function ( Views )  {
		_.extend(Forminator, Views);

		var Application = new ( Backbone.Router.extend({
			app: false,
			data: false,
			layout: false,
			module_id: null,

			routes: {
				""              : "run",
				"*path"         : "run"
			},

			events: {},

			init: function () {
				// Load Forminator Data only first time
				if( ! this.data ) {
					this.app = Forminator.Data.application || false;

					// Retrieve current data
					this.data = {};

					return false;
				}
			},

			run: function (id) {

				this.init();

				this.module_id = id;
			},
		}));

		return Application;
	});

})(jQuery);
