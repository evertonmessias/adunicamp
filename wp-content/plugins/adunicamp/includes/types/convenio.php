<?php

function create_custom_post_type_convenio()
{
	$labels = [
		'name' => _x('Convênio', 'post type general name'),
		'singular_name' => _x('Convênio', 'post type singular name'),
		'add_new' => _x('Adicionar', 'Convênio'),
		'add_new_item' => __('Adicionar novo Convênio'),
		'edit_item' => __('Editar Convênio'),
		'new_item' => __('Novo Convênio'),
		'view_item' => __('View Convênio'),
		'search_items' => __('Search Convênio'),
		'not_found' =>  __('Nothing found'),
		'not_found_in_trash' => __('Nothing found in Trash'),
		'parent_item_colon' => ''
	];
	$args = [
		'labels'				=> $labels,
		'supports'              => ['title' , 'editor', 'thumbnail'/*, 'author', 'excerpt'*/],
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'query_var' 			=> true,
		'menu_position'         => 4,
		'show_in_admin_bar'     => true,
		'rewrite' 				=> true,
		'show_in_nav_menus'     => true,
		'can_export'			=> true,
		'menu_icon'             => 'dashicons-table-row-after',
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'     	=> array('post', 'convenio'),
		'map_meta_cap'        => true,
	];
	register_post_type('convenio', $args);
}
add_action('init', 'create_custom_post_type_convenio');


//Roles for Admin, Editor
function role_caps_convenio()
{
	$roles = array('editor', 'administrator');
	foreach ($roles as $the_role) {
		$role = get_role($the_role);
		$role->add_cap('read_convenio');
		$role->add_cap('read_private_convenio');
		$role->add_cap('edit_convenio');
		$role->add_cap('edit_others_convenio');
		$role->add_cap('edit_published_convenio');
		$role->add_cap('publish_convenio');
		$role->add_cap('delete_others_convenio');
		$role->add_cap('delete_private_convenio');
		$role->add_cap('delete_published_convenio');
	}
}
add_action('admin_init', 'role_caps_convenio', 999);