<?php

function create_custom_post_type_album()
{
	$labels = [
		'name' => _x('Álbum', 'post type general name'),
		'singular_name' => _x('Álbum', 'post type singular name'),
		'add_new' => _x('Adicionar', 'Álbum'),
		'add_new_item' => __('Adicionar nova Álbum'),
		'edit_item' => __('Editar Álbum'),
		'new_item' => __('Nova Álbum'),
		'view_item' => __('View Álbum'),
		'search_items' => __('Search Álbum'),
		'not_found' =>  __('Nothing found'),
		'not_found_in_trash' => __('Nothing found in Trash'),
		'parent_item_colon' => ''
	];
	$args = [
		'labels'				=> $labels,
		'supports'              => ['title' /*, 'editor', 'thumbnail', 'author', 'excerpt'*/],
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'query_var' 			=> true,
		'menu_position'         => 3,
		'show_in_admin_bar'     => true,
		'rewrite' 				=> true,
		'show_in_nav_menus'     => true,
		'can_export'			=> true,
		'menu_icon'             => 'dashicons-images-alt2',
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'     	=> array('post', 'album'),
		'map_meta_cap'        => true,
	];
	register_post_type('album', $args);
}
add_action('init', 'create_custom_post_type_album');


//Roles for Admin, Editor
function role_caps_album()
{
	$roles = array('editor', 'administrator');
	foreach ($roles as $the_role) {
		$role = get_role($the_role);
		$role->add_cap('read_album');
		$role->add_cap('read_private_album');
		$role->add_cap('edit_album');
		$role->add_cap('edit_others_album');
		$role->add_cap('edit_published_album');
		$role->add_cap('publish_album');
		$role->add_cap('delete_others_album');
		$role->add_cap('delete_private_album');
		$role->add_cap('delete_published_album');
	}
}
add_action('admin_init', 'role_caps_album', 999);


function images_grid($post)
{
	if ($post->post_type == 'album') {
?>
		<div id="post_upload_id_0">
			<h3>Imagens</h3>

			<div id="imageListId" class="row"></div>
			
			<div class="form-add">

			<hr>
				<?php
				$post_upload_0 = get_post_meta($post->ID, 'post_upload_0', true);
				?>
				<input type="hidden" name="post_upload_0[]" id="post_upload_0" value="<?php echo $post_upload_0; ?>" />
				<a href="#" onclick="upload_image(2,0);" class="button button-secondary"><?php _e('Upload Image'); ?></a>
			</div>

		</div>
<?php
	}
}
add_action('edit_form_after_title', 'images_grid');


/* SAVE ALL ******************************************************************************************* */

function save_postmeta_congrega($post_id)
{
	if (isset($_POST['post_upload_0'])) {
		$post_upload_0 = implode(',', $_POST['post_upload_0']);
		update_post_meta($post_id, 'post_upload_0', $post_upload_0);
	}
}
add_action('save_post', 'save_postmeta_congrega');
