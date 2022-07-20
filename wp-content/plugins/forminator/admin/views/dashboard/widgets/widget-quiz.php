<?php
$module_slug        = 'quiz';
$title              = __( 'Quizzes', 'forminator' );
$description        = __( 'Create fun or challenging quizzes for your visitors to take and share on social media.', 'forminator' );
$icon               = 'sui-icon-academy';
$preview_title      = __( 'Preview Quiz', 'forminator' );
$delete_title       = __( 'Delete Quiz', 'forminator' );
$delete_description = __( 'Are you sure you wish to permanently delete this quiz?', 'forminator' );
$view_all           = __( 'View all quizzes', 'forminator' );
$total              = forminator_quizzes_total();

require forminator_plugin_dir() . 'admin/views/common/dashboard/widget.php';
