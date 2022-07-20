require({
	baseUrl: ".",
	appDir: ".",
	dir: "../../requirejs",
	optimizeCss: "standard",
	namespace: "formintorjs",
	paths: {
		"js": ".",
		"scripts": "admin",
		"tpl": "admin/templates",
		"modules": "../../library/modules",
		"backbone": "../../../../../wp-includes/js/backbone.min",
		"underscore": "../../../../../wp-includes/js/underscore.min",
		"tinymce": "../../../../../wp-includes/js/tinymce/tinymce.min",
		"jquery.ui.widget": "../../../../../wp-includes/js/jquery/ui/widget.min",
		requireLib: 'library/require'
	},
	shim: {
		'underscore': {
			exports: '_'
		},
		'backbone': {
			deps: ['underscore'],
			exports: 'Backbone'
		},
		'tinymce': {
			exports: 'tinymce'
		},
	},
	//optimize: "none", // in case you want to debug something uncomment this for unoptimized output.
	fileExclusionRegExp: /test|admin-react|front|shared-ui*/,
	removeCombined: false, // this affects build dir, it makes clearer what is in built main
	findNestedDependencies: true, // we need this since we have nested require calls
	modules: [
		{
			name: "main",
			include: ["requireLib", "admin/setup"],
			create: true,
		}
	]
});
