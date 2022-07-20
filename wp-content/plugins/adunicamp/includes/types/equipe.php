<?php

function create_custom_post_type_equipe()
{
	$labels = [
		'name' => _x('Equipe', 'post type general name'),
		'singular_name' => _x('Equipe', 'post type singular name'),
		'add_new' => _x('Adicionar', 'Equipe'),
		'add_new_item' => __('Adicionar novo Equipe'),
		'edit_item' => __('Editar Equipe'),
		'new_item' => __('Nova Equipe'),
		'view_item' => __('View Equipe'),
		'search_items' => __('Search Equipe'),
		'not_found' =>  __('Nothing found'),
		'not_found_in_trash' => __('Nothing found in Trash'),
		'parent_item_colon' => ''
	];
	$args = [
		'labels'				=> $labels,
		'supports'              => ['title', 'thumbnail' /*,'editor', 'author', 'excerpt'*/],
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
		'menu_icon'             => 'dashicons-businessman',
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'     	=> array('post', 'equipe'),
		'map_meta_cap'        => true,
	];
	register_post_type('equipe', $args);
}
add_action('init', 'create_custom_post_type_equipe');

function create_equipe_taxonomies() {
    $labels = array(
        'name'              => _x( 'Categories', 'taxonomy general name' ),
        'singular_name'     => _x( 'Category', 'taxonomy singular name' ),
        'search_items'      => __( 'Search Categories' ),
        'all_items'         => __( 'All Categories' ),
        'parent_item'       => __( 'Parent Category' ),
        'parent_item_colon' => __( 'Parent Category:' ),
        'edit_item'         => __( 'Edit Category' ),
        'update_item'       => __( 'Update Category' ),
        'add_new_item'      => __( 'Add New Category' ),
        'new_item_name'     => __( 'New Category Name' ),
        'menu_name'         => __( 'Categories' ),
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,			
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true
    );	
    register_taxonomy( 'equipe_categories','equipe', $args );
}	
add_action('init', 'create_equipe_taxonomies');


//Roles for Admin, Editor
function role_caps_equipe()
{
	$roles = array('editor', 'administrator');
	foreach ($roles as $the_role) {
		$role = get_role($the_role);
		$role->add_cap('read_equipe');
		$role->add_cap('read_private_equipe');
		$role->add_cap('edit_equipe');
		$role->add_cap('edit_others_equipe');
		$role->add_cap('edit_published_equipe');
		$role->add_cap('publish_equipe');
		$role->add_cap('delete_others_equipe');
		$role->add_cap('delete_private_equipe');
		$role->add_cap('delete_published_equipe');
	}
}
add_action('admin_init', 'role_caps_equipe', 999);


// POSTMETA ************************************************

// Função **********************************

function field_box_equipe_funcao()
{
    add_meta_box('equipe_funcao_id', 'Função', 'field_equipe_funcao', 'equipe','equipe_funcao','high',null);
}
add_action('add_meta_boxes', 'field_box_equipe_funcao');

function field_equipe_funcao($post)
{
    $value = get_post_meta($post->ID, 'equipe_funcao', true);
?>
    <input class="postmeta-equipe" type="text" name="equipe_funcao" value="<?php echo $value; ?>">
<?php
}

function move_postmeta_to_top_funcao() {
    global $post, $wp_meta_boxes;
    do_meta_boxes( get_current_screen(), 'equipe_funcao', $post );
    unset($wp_meta_boxes['post']['equipe_funcao']);
}
add_action('edit_form_after_title', 'move_postmeta_to_top_funcao');


// Local **********************************

function field_box_equipe_local()
{
    add_meta_box('equipe_local_id', 'Local', 'field_equipe_local', 'equipe','equipe_local','high',null);
}
add_action('add_meta_boxes', 'field_box_equipe_local');

function field_equipe_local($post)
{
    $value = get_post_meta($post->ID, 'equipe_local', true);
?>
    <input class="postmeta-equipe" type="text" name="equipe_local" value="<?php echo $value; ?>">
<?php
}

function move_postmeta_to_top_local() {
    global $post, $wp_meta_boxes;
    do_meta_boxes( get_current_screen(), 'equipe_local', $post );
    unset($wp_meta_boxes['post']['equipe_local']);
}
add_action('edit_form_after_title', 'move_postmeta_to_top_local');


// Contato **********************************

function field_box_equipe_contato()
{
    add_meta_box('equipe_contato_id', 'Contato', 'field_equipe_contato', 'equipe','equipe_contato','high',null);
}
add_action('add_meta_boxes', 'field_box_equipe_contato');

function field_equipe_contato($post)
{
    $value = get_post_meta($post->ID, 'equipe_contato', true);
?>
    <input class="postmeta-equipe" type="text" name="equipe_contato" value="<?php echo $value; ?>">
<?php
}

function move_postmeta_to_top_contato() {
    global $post, $wp_meta_boxes;
    do_meta_boxes( get_current_screen(), 'equipe_contato', $post );
    unset($wp_meta_boxes['post']['equipe_contato']);
}
add_action('edit_form_after_title', 'move_postmeta_to_top_contato');


// SAVE ALL **********************************

function save_postmeta_equipe($post_id)
{
    if (isset($_POST['equipe_funcao'])) {
        $equipe_funcao = sanitize_text_field($_POST['equipe_funcao']);
        update_post_meta($post_id, 'equipe_funcao', $equipe_funcao);
    } 
    if (isset($_POST['equipe_local'])) {
        $equipe_local = sanitize_text_field($_POST['equipe_local']);
        update_post_meta($post_id, 'equipe_local', $equipe_local);
    }  
	if (isset($_POST['equipe_contato'])) {
        $equipe_contato = sanitize_text_field($_POST['equipe_contato']);
        update_post_meta($post_id, 'equipe_contato', $equipe_contato);
    }     
}
add_action('save_post', 'save_postmeta_equipe');
