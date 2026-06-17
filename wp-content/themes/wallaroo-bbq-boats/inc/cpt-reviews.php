<?php
/**
 * Wallaroo BBQ Boats — Reviews Custom Post Type
 *
 * Each review uses:
 *   - Post Title   → Reviewer name (e.g. "Sarah M., Adelaide")
 *   - Post Content → The review quote
 *   - ACF field    → Rating (1–5)
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {

    register_post_type( 'wbb_review', [
        'labels' => [
            'name'               => 'Reviews',
            'singular_name'      => 'Review',
            'add_new'            => 'Add New Review',
            'add_new_item'       => 'Add New Review',
            'edit_item'          => 'Edit Review',
            'new_item'           => 'New Review',
            'view_item'          => 'View Review',
            'search_items'       => 'Search Reviews',
            'not_found'          => 'No reviews found',
            'not_found_in_trash' => 'No reviews found in trash',
            'menu_name'          => 'Reviews',
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => false,
        'show_in_rest'        => true,
        'menu_icon'           => 'dashicons-star-filled',
        'menu_position'       => 25,
        'supports'            => [ 'title', 'editor', 'page-attributes' ],
        'rewrite'             => false,
        'has_archive'         => false,
    ] );

} );
