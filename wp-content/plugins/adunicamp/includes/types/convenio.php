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
		'supports'              => ['title' , 'thumbnail'/*,'editor', 'author', 'excerpt'*/],
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

function create_convenio_taxonomies() {
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
    register_taxonomy( 'convenio_categories','convenio', $args );
}	
add_action('init', 'create_convenio_taxonomies');


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


// POSTMETA ************************************************

// Informações Principais **********************************

function field_box_convenio_informacoes1()
{
    add_meta_box('convenio_informacoes1_id', 'Informações Principais', 'field_convenio_informacoes1', 'convenio','convenio_informacoes1','high',null);
}
add_action('add_meta_boxes', 'field_box_convenio_informacoes1');

function field_convenio_informacoes1($post)
{
    $value1 = get_post_meta($post->ID, 'convenio_informacoes1', true);
	wp_editor($value1, 'convenio_informacoes1', array('textarea_name' => 'convenio_informacoes1'));
}

function move_postmeta_to_top_informacoes1() {
    global $post, $wp_meta_boxes;
    do_meta_boxes( get_current_screen(), 'convenio_informacoes1', $post );
    unset($wp_meta_boxes['post']['convenio_informacoes1']);
}
add_action('edit_form_after_title', 'move_postmeta_to_top_informacoes1');


// Informações Telefones **********************************

function field_box_convenio_telefones()
{
    add_meta_box('convenio_telefones_id', 'Informações Telefones', 'field_convenio_telefones', 'convenio','convenio_telefones','high',null);
}
add_action('add_meta_boxes', 'field_box_convenio_telefones');

function field_convenio_telefones($post)
{
    $value = get_post_meta($post->ID, 'convenio_telefones', true);
?>
    <input class="postmeta-convenio" type="text" name="convenio_telefones" value="<?php echo $value; ?>">
<?php
}

function move_postmeta_to_top_telefones() {
    global $post, $wp_meta_boxes;
    do_meta_boxes( get_current_screen(), 'convenio_telefones', $post );
    unset($wp_meta_boxes['post']['convenio_telefones']);
}
add_action('edit_form_after_title', 'move_postmeta_to_top_telefones');


// Informações Email **********************************

function field_box_convenio_email()
{
    add_meta_box('convenio_email_id', 'Informações Email', 'field_convenio_email', 'convenio','convenio_email','high',null);
}
add_action('add_meta_boxes', 'field_box_convenio_email');

function field_convenio_email($post)
{
    $value = get_post_meta($post->ID, 'convenio_email', true);
?>
    <input class="postmeta-convenio" type="text" name="convenio_email" value="<?php echo $value; ?>">
<?php
}

function move_postmeta_to_top_email() {
    global $post, $wp_meta_boxes;
    do_meta_boxes( get_current_screen(), 'convenio_email', $post );
    unset($wp_meta_boxes['post']['convenio_email']);
}
add_action('edit_form_after_title', 'move_postmeta_to_top_email');


// Informações Secundárias 1 **********************************

function field_box_convenio_informacoes2()
{
    add_meta_box('convenio_informacoes2_id', 'Informações Secundárias 1', 'field_convenio_informacoes2', 'convenio','convenio_informacoes2','high',null);
}
add_action('add_meta_boxes', 'field_box_convenio_informacoes2');

function field_convenio_informacoes2($post)
{
    $value1 = get_post_meta($post->ID, 'convenio_informacoes2', true);
	wp_editor($value1, 'convenio_informacoes2', array('textarea_name' => 'convenio_informacoes2'));
}

function move_postmeta_to_top_informacoes2() {
    global $post, $wp_meta_boxes;
    do_meta_boxes( get_current_screen(), 'convenio_informacoes2', $post );
    unset($wp_meta_boxes['post']['convenio_informacoes2']);
}
add_action('edit_form_after_title', 'move_postmeta_to_top_informacoes2');


// Informações Secundárias 2 **********************************

function field_box_convenio_informacoes3()
{
    add_meta_box('convenio_informacoes3_id', 'Informações Secundárias 2', 'field_convenio_informacoes3', 'convenio','convenio_informacoes3','high',null);
}
add_action('add_meta_boxes', 'field_box_convenio_informacoes3');

function field_convenio_informacoes3($post)
{
    $value1 = get_post_meta($post->ID, 'convenio_informacoes3', true);
	wp_editor($value1, 'convenio_informacoes3', array('textarea_name' => 'convenio_informacoes3'));
}

function move_postmeta_to_top_informacoes3() {
    global $post, $wp_meta_boxes;
    do_meta_boxes( get_current_screen(), 'convenio_informacoes3', $post );
    unset($wp_meta_boxes['post']['convenio_informacoes3']);
}
add_action('edit_form_after_title', 'move_postmeta_to_top_informacoes3');


// Informações Secundárias 3 **********************************

function field_box_convenio_informacoes4()
{
    add_meta_box('convenio_informacoes4_id', 'Informações Secundárias 3', 'field_convenio_informacoes4', 'convenio','convenio_informacoes4','high',null);
}
add_action('add_meta_boxes', 'field_box_convenio_informacoes4');

function field_convenio_informacoes4($post)
{
    $value1 = get_post_meta($post->ID, 'convenio_informacoes4', true);
	wp_editor($value1, 'convenio_informacoes4', array('textarea_name' => 'convenio_informacoes4'));
}

function move_postmeta_to_top_informacoes4() {
    global $post, $wp_meta_boxes;
    do_meta_boxes( get_current_screen(), 'convenio_informacoes4', $post );
    unset($wp_meta_boxes['post']['convenio_informacoes4']);
}
add_action('edit_form_after_title', 'move_postmeta_to_top_informacoes4');


// Informações Secundárias 4 **********************************

function field_box_convenio_informacoes5()
{
    add_meta_box('convenio_informacoes5_id', 'Informações Secundárias 4', 'field_convenio_informacoes5', 'convenio','convenio_informacoes5','high',null);
}
add_action('add_meta_boxes', 'field_box_convenio_informacoes5');

function field_convenio_informacoes5($post)
{
    $value1 = get_post_meta($post->ID, 'convenio_informacoes5', true);
	wp_editor($value1, 'convenio_informacoes5', array('textarea_name' => 'convenio_informacoes5'));
}

function move_postmeta_to_top_informacoes5() {
    global $post, $wp_meta_boxes;
    do_meta_boxes( get_current_screen(), 'convenio_informacoes5', $post );
    unset($wp_meta_boxes['post']['convenio_informacoes5']);
}
add_action('edit_form_after_title', 'move_postmeta_to_top_informacoes5');


// SAVE ALL **********************************

function save_postmeta_convenio($post_id)
{
	if (isset($_POST['convenio_informacoes1'])) {
        
        update_post_meta($post_id, 'convenio_informacoes1', $_POST['convenio_informacoes1']);
    } 
	if (isset($_POST['convenio_informacoes2'])) {
        
        update_post_meta($post_id, 'convenio_informacoes2', $_POST['convenio_informacoes2']);
    }
	if (isset($_POST['convenio_informacoes3'])) {
        
        update_post_meta($post_id, 'convenio_informacoes3', $_POST['convenio_informacoes3']);
    }
	if (isset($_POST['convenio_informacoes4'])) {
        
        update_post_meta($post_id, 'convenio_informacoes4', $_POST['convenio_informacoes4']);
    }
	if (isset($_POST['convenio_informacoes5'])) {
        
        update_post_meta($post_id, 'convenio_informacoes5', $_POST['convenio_informacoes5']);
    }
    if (isset($_POST['convenio_telefones'])) {
        
        update_post_meta($post_id, 'convenio_telefones', $_POST['convenio_telefones']);
    } 
    if (isset($_POST['convenio_email'])) {
        
        update_post_meta($post_id, 'convenio_email', $_POST['convenio_email']);
    }      
}
add_action('save_post', 'save_postmeta_convenio');
