<?php
/**
 * Wallaroo BBQ Boats — FAQ Custom Post Type
 *
 * Each FAQ item:
 *   - Post Title   → Question
 *   - Post Content → Answer
 *   - Menu Order   → Display order (lower = first)
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {

    register_post_type( 'wbb_faq', [
        'labels' => [
            'name'               => 'FAQ',
            'singular_name'      => 'FAQ Item',
            'add_new'            => 'Add New Question',
            'add_new_item'       => 'Add New FAQ Item',
            'edit_item'          => 'Edit FAQ Item',
            'new_item'           => 'New FAQ Item',
            'view_item'          => 'View FAQ Item',
            'search_items'       => 'Search FAQ',
            'not_found'          => 'No FAQ items found',
            'not_found_in_trash' => 'No FAQ items found in trash',
            'menu_name'          => 'FAQ',
        ],
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_rest'      => true,
        'menu_icon'         => 'dashicons-editor-help',
        'menu_position'     => 26,
        'supports'          => [ 'title', 'editor', 'page-attributes' ],
        'rewrite'           => false,
        'has_archive'       => false,
    ] );

} );
