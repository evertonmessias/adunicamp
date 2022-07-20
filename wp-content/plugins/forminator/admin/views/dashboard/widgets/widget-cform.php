<?php
$module_slug        = 'form';
$title              = __( 'Forms', 'forminator' );
$description        = __( 'Create any type of form from one of our pre-made templates, or build your own from scratch.', 'forminator' );
$icon               = 'sui-icon-clipboard-notes';
$preview_title      = __( 'Preview Custom Form', 'forminator' );
$delete_title       = __( 'Delete Form', 'forminator' );
$delete_description = __( 'Are you sure you wish to permanently delete this form?', 'forminator' );
$view_all           = __( 'View all forms', 'forminator' );
$total              = forminator_cforms_total();

require forminator_plugin_dir() . 'admin/views/common/dashboard/widget.php';
