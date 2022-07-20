<?php
$module_slug        = 'poll';
$title              = __( 'Polls', 'forminator' );
$description        = __( 'Create interactive polls to collect users\' opinions, with lots of dynamic options and settings.', 'forminator' );
$icon               = 'sui-icon-graph-bar';
$preview_title      = __( 'Preview Poll', 'forminator' );
$delete_title       = __( 'Delete Poll', 'forminator' );
$delete_description = __( 'Are you sure you wish to permanently delete this poll?', 'forminator' );
$view_all           = __( 'View all polls', 'forminator' );
$total              = forminator_polls_total();

require forminator_plugin_dir() . 'admin/views/common/dashboard/widget.php';
