formintorjs.define( 'jquery', [], function () {
	return jQuery;
});

formintorjs.define( 'forminator_global_data', function() {
   var data = forminatorData;
	return data;
});

formintorjs.define( 'forminator_language', function() {
   var l10n = forminatorl10n;
	return l10n;
});

var Forminator = window.Forminator || {};
Forminator.Events = {};
Forminator.Data = {};
Forminator.l10n = {};
Forminator.openPreset = function( presetId, notice ) {
	// replace preset param to the new one.
	var regEx = /([?&]preset)=([^&]*)/g,
		newUrl = window.location.href.replace( regEx, '$1=' + presetId );

	// if it didn't have preset param - add it.
	if ( newUrl === window.location.href ) {
		newUrl += '&preset=' + presetId;
	}

	if ( notice ) {
		newUrl += '&forminator_notice=' + notice;
	}

	window.location.href = newUrl;
};

formintorjs.require.config({
	baseUrl: ".",
	paths: {
		"js": ".",
		"admin": "admin",
	},
	shim: {
		'backbone': {
			//These script dependencies should be loaded before loading
			//backbone.js
			deps: [ 'underscore', 'jquery', 'forminator_global_data', 'forminator_language' ],
			//Once loaded, use the global 'Backbone' as the
			//module value.
			exports: 'Backbone'
		},
		'underscore': {
			exports: '_'
		}
	},
	"waitSeconds": 60,
});

formintorjs.require([  'admin/utils' ], function ( Utils ) {
	// Fix Underscore templating to Mustache style
	_.templateSettings = {
		evaluate : /\{\[([\s\S]+?)\]\}/g,
		interpolate : /\{\{([\s\S]+?)\}\}/g
	};

	_.extend( Forminator.Data, forminatorData );
	_.extend( Forminator.l10n, forminatorl10n );
	_.extend( Forminator, Utils );
	_.extend(Forminator.Events, Backbone.Events);

	formintorjs.require([ 'admin/application' ], function ( Application ) {
		jQuery( function() {
			_.extend(Forminator, Application);

			Forminator.Events.trigger("application:booted");
			Backbone.history.start();
		});
	});
});
