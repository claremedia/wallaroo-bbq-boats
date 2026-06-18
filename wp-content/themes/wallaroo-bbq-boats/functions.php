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
        // Per-item icon — only when the menu opts in via 'show_icons' (primary nav,
        // desktop + mobile). Footer menu leaves it off.
        $icon_html = '';
        if ( $args && ! empty( $args->show_icons ) ) {
            $icon_key = get_post_meta( $item->ID, '_wbb_menu_icon', true );
            $icon_svg = $icon_key ? wallaroo_menu_icon_svg( $icon_key ) : '';
            if ( $icon_svg ) {
                $icon_html = '<span class="nav-link__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $icon_svg . '</svg></span>';
            }
        }

        $output .= '<li>';
        $output .= sprintf(
            '<a href="%s" class="%s"%s>%s%s</a>',
            esc_url( $item->url ),
            esc_attr( $link_class ),
            $item->target ? ' target="' . esc_attr( $item->target ) . '"' : '',
            $icon_html,
            esc_html( $item->title )
        );
        $output .= '</li>';
    }
}

// ============================================================
// 4b. PER-MENU-ITEM HOVER ICONS
//     A curated set of Lucide icons, selectable per menu item
//     under Appearance → Menus. Shown on hover in the pill nav.
// ============================================================

/** Available icon choices: key => admin label. */
function wallaroo_menu_icon_choices() {
    return [
        ''            => '— No icon —',
        'home'        => 'Home',
        'anchor'      => 'Anchor',
        'ship-wheel'  => "Ship's wheel",
        'sailboat'    => 'Sailboat',
        'waves'       => 'Waves',
        'utensils'    => 'Food / utensils',
        'gift'        => 'Gift',
        'ticket'      => 'Voucher / ticket',
        'users'       => 'Groups / people',
        'calendar'    => 'Calendar / booking',
        'map-pin'     => 'Location / map pin',
        'help-circle' => 'FAQ / help',
        'life-buoy'   => 'Life buoy / safety',
    ];
}

/** Inner SVG markup (Lucide) for an icon key, or '' if unknown. */
function wallaroo_menu_icon_svg( $key ) {
    $icons = [
        'home'        => '<path d="M3 9.5 12 3l9 6.5V20a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M9 22V12h6v10"/>',
        'anchor'      => '<path d="M12 22V8"/><path d="M5 12H2a10 10 0 0 0 20 0h-3"/><circle cx="12" cy="5" r="3"/>',
        'ship-wheel'  => '<circle cx="12" cy="12" r="8"/><path d="M12 2v7.5"/><path d="m19 5-5.23 5.23"/><path d="M22 12h-7.5"/><path d="m19 19-5.23-5.23"/><path d="M12 14.5V22"/><path d="M10.23 13.77 5 19"/><path d="M9.5 12H2"/><path d="M10.23 10.23 5 5"/><circle cx="12" cy="12" r="2.5"/>',
        'sailboat'    => '<path d="M22 18H2a4 4 0 0 0 4 4h12a4 4 0 0 0 4-4Z"/><path d="M21 14 10 2 3 14Z"/><path d="M10 2v16"/>',
        'waves'       => '<path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>',
        'utensils'    => '<path d="M3 2v7c0 1.1.9 2 2 2h0a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>',
        'gift'        => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/>',
        'ticket'      => '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/>',
        'users'       => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'calendar'    => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
        'map-pin'     => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'help-circle' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
        'life-buoy'   => '<circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/><circle cx="12" cy="12" r="4"/>',
    ];
    return isset( $icons[ $key ] ) ? $icons[ $key ] : '';
}

// Render the icon picker on each menu item in Appearance → Menus (WP 5.4+).
add_action( 'wp_nav_menu_item_custom_fields', function ( $item_id, $item ) {
    $current = get_post_meta( $item_id, '_wbb_menu_icon', true );
    ?>
    <p class="field-wbb-icon description description-wide">
        <label for="wbb-menu-icon-<?php echo esc_attr( $item_id ); ?>">
            <?php esc_html_e( 'Hover icon', 'wallaroo-bbq-boats' ); ?><br>
            <select id="wbb-menu-icon-<?php echo esc_attr( $item_id ); ?>" name="wbb_menu_icon[<?php echo esc_attr( $item_id ); ?>]" class="widefat">
                <?php foreach ( wallaroo_menu_icon_choices() as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>
    <?php
}, 10, 2 );

// Save the chosen icon when the menu is saved.
add_action( 'wp_update_nav_menu_item', function ( $menu_id, $menu_item_db_id ) {
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        return;
    }
    if ( ! isset( $_POST['wbb_menu_icon'][ $menu_item_db_id ] ) ) {
        return;
    }
    $val     = sanitize_key( wp_unslash( $_POST['wbb_menu_icon'][ $menu_item_db_id ] ) );
    $choices = wallaroo_menu_icon_choices();
    if ( $val && isset( $choices[ $val ] ) ) {
        update_post_meta( $menu_item_db_id, '_wbb_menu_icon', $val );
    } else {
        delete_post_meta( $menu_item_db_id, '_wbb_menu_icon' );
    }
}, 10, 2 );

// Fallback for primary nav (no menu assigned yet)
function wallaroo_nav_fallback( $args ) {
    // Note: "Book Now" is intentionally omitted — the header button and on-page
    // CTAs already cover it.
    $links = [
        'Home'              => home_url( '/' ),
        "What's On Board"   => home_url( '/whats-on-board/' ),
        'Food & Drink'      => home_url( '/food-drink/' ),
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
        'Food & Drink'      => home_url( '/food-drink/' ),
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
