<?php

function create_custom_post_type_agenda()
{
	$labels = [
		'name' => _x('Agenda', 'post type general name'),
		'singular_name' => _x('Agenda', 'post type singular name'),
		'add_new' => _x('Adicionar', 'Agenda'),
		'add_new_item' => __('Adicionar nova Agenda'),
		'edit_item' => __('Editar Agenda'),
		'new_item' => __('Nova Agenda'),
		'view_item' => __('View Agenda'),
		'search_items' => __('Search Agenda'),
		'not_found' =>  __('Nothing found'),
		'not_found_in_trash' => __('Nothing found in Trash'),
		'parent_item_colon' => ''
	];
	$args = [
		'labels'				=> $labels,
		'supports'              => ['title', 'editor', 'thumbnail'/*, 'author', 'excerpt'*/],
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'query_var' 			=> true,
		'menu_position'         => 2,
		'show_in_admin_bar'     => true,
		'rewrite' 				=> true,
		'show_in_nav_menus'     => true,
		'can_export'			=> true,
		'menu_icon'             => 'dashicons-calendar-alt',
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'     	=> array('post', 'agenda'),
		'map_meta_cap'        => true,
	];
	register_post_type('agenda', $args);
}
add_action('init', 'create_custom_post_type_agenda');


//Roles for Admin, Editor
function role_caps_agenda()
{
	$roles = array('editor', 'administrator');
	foreach ($roles as $the_role) {
		$role = get_role($the_role);
		$role->add_cap('read_agenda');
		$role->add_cap('read_private_agenda');
		$role->add_cap('edit_agenda');
		$role->add_cap('edit_others_agenda');
		$role->add_cap('edit_published_agenda');
		$role->add_cap('publish_agenda');
		$role->add_cap('delete_others_agenda');
		$role->add_cap('delete_private_agenda');
		$role->add_cap('delete_published_agenda');
	}
}
add_action('admin_init', 'role_caps_agenda', 999);


// POSTMETA ************************************************

// Inicio **********************************

function field_box_agenda_data_inicio()
{
	add_meta_box('agenda_data_inicio_id', 'Data Início', 'field_agenda_data_inicio', 'agenda', 'agenda_data_inicio', 'high', null);
}
add_action('add_meta_boxes', 'field_box_agenda_data_inicio');

function field_agenda_data_inicio($post)
{
	$value = get_post_meta($post->ID, 'agenda_data_inicio', true);
?>
	<input class="postmeta-agenda" type="datetime-local" name="agenda_data_inicio" value="<?php echo $value; ?>">
<?php
}

function move_postmeta_to_top_agenda()
{
	global $post, $wp_meta_boxes;
	do_meta_boxes(get_current_screen(), 'agenda_data_inicio', $post);
	unset($wp_meta_boxes['post']['agenda_data_inicio']);
}
add_action('edit_form_after_title', 'move_postmeta_to_top_agenda');


// Fim **********************************

function field_box_agenda_data_fim()
{
	add_meta_box('agenda_data_fim_id', 'Data Fim', 'field_agenda_data_fim', 'agenda', 'agenda_data_fim', 'high', null);
}
add_action('add_meta_boxes', 'field_box_agenda_data_fim');

function field_agenda_data_fim($post)
{
	$value = get_post_meta($post->ID, 'agenda_data_fim', true);
?>
	<input class="postmeta-agenda" type="datetime-local" name="agenda_data_fim" value="<?php echo $value; ?>">
<?php
}

function move_postmeta_to_top_data_fim()
{
	global $post, $wp_meta_boxes;
	do_meta_boxes(get_current_screen(), 'agenda_data_fim', $post);
	unset($wp_meta_boxes['post']['agenda_data_fim']);
}
add_action('edit_form_after_title', 'move_postmeta_to_top_data_fim');


// SAVE ALL **********************************

function save_postmeta_agenda($post_id)
{
	if (isset($_POST['agenda_data_inicio'])) {
		$agenda_data_inicio = sanitize_text_field($_POST['agenda_data_inicio']);
		update_post_meta($post_id, 'agenda_data_inicio', $agenda_data_inicio);
	}
	if (isset($_POST['agenda_data_fim'])) {
		$agenda_data_fim = sanitize_text_field($_POST['agenda_data_fim']);
		update_post_meta($post_id, 'agenda_data_fim', $agenda_data_fim);
	}
}
add_action('save_post', 'save_postmeta_agenda');
