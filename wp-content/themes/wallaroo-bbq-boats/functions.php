<?php
/**
 * Wallaroo BBQ Boats — functions.php
 *
 * Theme setup, asset enqueuing, WordPress bloat removal,
 * and ACF local field group registration.
 */

defined( 'ABSPATH' ) || exit;

// ============================================================
// 1. THEME SETUP
// ============================================================
add_action( 'after_setup_theme', function () {
    load_theme_textdomain( 'wallaroo-bbq-boats', get_template_directory() . '/languages' );

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script',
    ] );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-styles' );

    // Navigation menus
    register_nav_menus( [
        'primary' => __( 'Primary Navigation', 'wallaroo-bbq-boats' ),
        'footer'  => __( 'Footer Navigation',  'wallaroo-bbq-boats' ),
    ] );
} );

// ============================================================
// 2. ENQUEUE STYLES & SCRIPTS
// ============================================================
add_action( 'wp_enqueue_scripts', function () {
    $css_file = get_template_directory() . '/assets/css/app.css';
    $js_file  = get_template_directory() . '/assets/js/main.js';

    // Use file modification time as version — auto cache-busts on every rebuild
    $css_ver  = file_exists( $css_file ) ? filemtime( $css_file ) : wp_get_theme()->get( 'Version' );
    $js_ver   = file_exists( $js_file )  ? filemtime( $js_file )  : wp_get_theme()->get( 'Version' );

    // Compiled Tailwind CSS — small after purge, safe in <head>
    wp_enqueue_style(
        'wallaroo-app',
        get_template_directory_uri() . '/assets/css/app.css',
        [],
        $css_ver
    );

    // Main JS — vanilla, deferred
    wp_enqueue_script(
        'wallaroo-main',
        get_template_directory_uri() . '/assets/js/main.js',
        [],
        $js_ver,
        true // load in footer
    );
} );

// Add defer attribute to our script via filter
add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
    if ( 'wallaroo-main' === $handle ) {
        return '<script src="' . esc_url( $src ) . '" defer></script>' . "\n";
    }
    return $tag;
}, 10, 3 );

// ============================================================
// 3. REMOVE WORDPRESS BLOAT
// ============================================================
add_action( 'init', function () {
    // Emoji
    remove_action( 'wp_head',             'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles',     'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles',  'print_emoji_styles' );
    remove_filter( 'the_content_feed',    'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss',    'wp_staticize_emoji' );
    remove_filter( 'wp_mail',             'wp_staticize_emoji_for_email' );

    // oEmbed
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );

    // RSS links
    remove_action( 'wp_head', 'feed_links',       2 );
    remove_action( 'wp_head', 'feed_links_extra',  3 );

    // Windows Live Writer manifest
    remove_action( 'wp_head', 'wlwmanifest_link' );

    // Shortlink
    remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );

    // REST API link header
    remove_action( 'wp_head', 'rest_output_link_wp_head' );

    // Generator tag
    remove_action( 'wp_head', 'wp_generator' );

    // Adjacent post links (unused on homepage)
    remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
} );

// Dequeue jQuery Migrate
add_action( 'wp_default_scripts', function ( $scripts ) {
    if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
        $script = $scripts->registered['jquery'];
        if ( $script->deps ) {
            $script->deps = array_diff( $script->deps, [ 'jquery-migrate' ] );
        }
    }
} );

// Disable ACF front-end CSS (ACF Free loads nothing on front by default; guard anyway)
add_filter( 'acf/settings/load_json', '__return_false' );

// ============================================================
// 4. NAV WALKER
// ============================================================
class Wallaroo_Nav_Walker extends Walker_Nav_Menu {
    public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
        $item       = $data_object;
        $classes    = empty( $item->classes ) ? [] : (array) $item->classes;
        $is_active  = in_array( 'current-menu-item', $classes ) || in_array( 'current-page-ancestor', $classes );
        $link_class = ! empty( $args->link_class ) ? $args->link_class : 'nav-link';
        if ( $is_active ) {
            $link_class .= ' nav-link-active';
        }
        $output .= '<li>';
        $output .= sprintf(
            '<a href="%s" class="%s"%s>%s</a>',
            esc_url( $item->url ),
            esc_attr( $link_class ),
            $item->target ? ' target="' . esc_attr( $item->target ) . '"' : '',
            esc_html( $item->title )
        );
        $output .= '</li>';
    }
}

// Fallback for primary nav (no menu assigned yet)
function wallaroo_nav_fallback( $args ) {
    $booking_url = wallaroo_option( 'booking_url' ) ?: home_url( '/book-now/' );
    $links = [
        'Home'              => home_url( '/' ),
        'Book Now'          => $booking_url,
        "What's On Board"   => home_url( '/whats-on-board/' ),
        'Groups'            => home_url( '/groups-and-workplaces/' ),
        'Gift Vouchers'     => home_url( '/gift-vouchers/' ),
        'FAQ'               => home_url( '/faq/' ),
        'Find Us'           => home_url( '/find-us/' ),
    ];
    $lc = isset( $args['link_class'] ) ? $args['link_class'] : 'nav-link';
    $iw = isset( $args['items_wrap'] ) ? $args['items_wrap'] : '<ul>%3$s</ul>';
    $items = '';
    foreach ( $links as $label => $url ) {
        $items .= '<li><a href="' . esc_url( $url ) . '" class="' . esc_attr( $lc ) . '">' . esc_html( $label ) . '</a></li>';
    }
    echo str_replace( [ '%1$s', '%2$s', '%3$s' ], [ '', '', $items ], $iw );
}

// Fallback for footer nav
function wallaroo_footer_nav_fallback( $args ) {
    $booking_url = wallaroo_option( 'booking_url' ) ?: home_url( '/book-now/' );
    $links = [
        'Home'              => home_url( '/' ),
        'Book Now'          => $booking_url,
        "What's On Board"   => home_url( '/whats-on-board/' ),
        'Groups'            => home_url( '/groups-and-workplaces/' ),
        'Gift Vouchers'     => home_url( '/gift-vouchers/' ),
        'FAQ'               => home_url( '/faq/' ),
        'Find Us'           => home_url( '/find-us/' ),
    ];
    $lc = isset( $args['link_class'] ) ? $args['link_class'] : 'font-body text-sm text-blue-100 hover:text-white transition-colors';
    echo '<ul class="flex flex-col gap-2 list-none m-0 p-0" role="list">';
    foreach ( $links as $label => $url ) {
        echo '<li><a href="' . esc_url( $url ) . '" class="' . esc_attr( $lc ) . '">' . esc_html( $label ) . '</a></li>';
    }
    echo '</ul>';
}

// ============================================================
// 5. INCLUDES
// ============================================================
require_once get_template_directory() . '/inc/theme-options.php';
require_once get_template_directory() . '/inc/cpt-reviews.php';
require_once get_template_directory() . '/inc/cpt-faq.php';
require_once get_template_directory() . '/inc/acf-fields.php';
require_once get_template_directory() . '/inc/seo.php';
