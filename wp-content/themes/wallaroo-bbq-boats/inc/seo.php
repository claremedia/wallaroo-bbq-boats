<?php
/**
 * Wallaroo BBQ Boats — SEO meta output
 *
 * Hooked to wp_head at priority 1 (before most other output).
 * Outputs:
 *   - <meta name="description">
 *   - <link rel="canonical">
 *   - Open Graph tags (og:type, og:title, og:description, og:url, og:image)
 *   - Twitter Card tags
 *   - JSON-LD LocalBusiness structured data
 *
 * Meta description lookup order:
 *   1. ACF field "seo_meta_description" on the current page (editable per page)
 *   2. Per-template hardcoded fallback (sensible defaults for each page)
 *   3. Site-wide default from Site Settings → SEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Per-template fallback descriptions.
 * Keyed by the template filename as returned by get_page_template_slug().
 */
function wallaroo_seo_template_descs(): array {
    return [
        'page-book-now.php'       => 'Book a self-drive BBQ boat at Wallaroo Marina. Check availability online or call us to lock in your date. No licence needed.',
        'page-whats-on-board.php' => 'Everything included on your BBQ boat hire at Wallaroo Marina. Gas BBQ, plates, cutlery, life jackets and cold drinks on board. 2 to 6 people per boat.',
        'page-groups.php'         => 'Book one or more self-drive BBQ boats for your workplace day, birthday or group event at Wallaroo Marina. 2 to 6 people per boat, multiple boats available.',
        'page-gift-vouchers.php'  => 'Give the gift of a day on the water. Wallaroo BBQ Boats gift vouchers available in any dollar amount. Digital delivery within one business day.',
        'page-faq.php'            => 'Common questions about Wallaroo BBQ Boats. No licence needed, 2 to 6 people per boat, BYO food welcome. Copper Cove Marina, Wallaroo SA.',
        'page-find-us.php'        => 'Find Wallaroo BBQ Boats at Copper Cove Marina, Wallaroo SA. Directions from Adelaide, Kadina and the Copper Coast, plus parking and opening times.',
    ];
}

add_action( 'wp_head', function () {
    global $post;

    $site_name = get_bloginfo( 'name' );
    $phone     = wallaroo_option( 'phone' );
    $email     = wallaroo_option( 'email' );
    $addr1     = wallaroo_option( 'address_line1' );

    // ── Meta description ──────────────────────────────────────────────────────

    $meta_desc = '';

    // 1. ACF field on current page/post
    if ( function_exists( 'get_field' ) && ! empty( $post->ID ) ) {
        $acf_desc = get_field( 'seo_meta_description', $post->ID );
        if ( ! empty( $acf_desc ) ) {
            $meta_desc = $acf_desc;
        }
    }

    // 2. Per-template fallback
    if ( empty( $meta_desc ) ) {
        $tpl       = get_page_template_slug();
        $fallbacks = wallaroo_seo_template_descs();
        if ( ! empty( $tpl ) && isset( $fallbacks[ $tpl ] ) ) {
            $meta_desc = $fallbacks[ $tpl ];
        }
    }

    // 3. Front page fallback
    if ( empty( $meta_desc ) && ( is_front_page() || is_home() ) ) {
        $meta_desc = 'Self-drive BBQ boat hire at Copper Cove Marina, Wallaroo SA. 2 to 6 people per boat. No licence needed. Book your session online.';
    }

    // 4. Site-wide default from admin settings
    if ( empty( $meta_desc ) ) {
        $meta_desc = wallaroo_option( 'meta_description' );
    }

    $meta_desc = wp_strip_all_tags( $meta_desc );

    // ── Canonical URL ─────────────────────────────────────────────────────────

    $canonical = ! empty( $post->ID )
        ? get_permalink( $post->ID )
        : home_url( '/' );

    // ── OG image ──────────────────────────────────────────────────────────────

    $og_image_url    = '';
    $og_image_width  = 0;
    $og_image_height = 0;

    // 1. ACF seo_og_image on current page
    if ( function_exists( 'get_field' ) && ! empty( $post->ID ) ) {
        $og_img = get_field( 'seo_og_image', $post->ID );
        if ( ! empty( $og_img['url'] ) ) {
            $og_image_url    = $og_img['url'];
            $og_image_width  = (int) ( $og_img['width']  ?? 0 );
            $og_image_height = (int) ( $og_img['height'] ?? 0 );
        }
    }

    // 2. Site logo fallback
    if ( empty( $og_image_url ) ) {
        $logo_id = (int) get_option( 'wallaroo_logo_id', 0 );
        if ( $logo_id ) {
            $logo_src = wp_get_attachment_image_src( $logo_id, 'large' );
            if ( ! empty( $logo_src[0] ) ) {
                $og_image_url    = $logo_src[0];
                $og_image_width  = (int) ( $logo_src[1] ?? 0 );
                $og_image_height = (int) ( $logo_src[2] ?? 0 );
            }
        }
    }

    // ── Page / OG title ───────────────────────────────────────────────────────

    if ( is_front_page() || is_home() ) {
        $og_title = $site_name . ' — Self-drive BBQ boat hire, Wallaroo Marina';
    } else {
        $page_title = ! empty( $post->ID ) ? get_the_title( $post->ID ) : $site_name;
        $og_title   = $page_title . ' — ' . $site_name;
    }

    // ── Output ────────────────────────────────────────────────────────────────

    echo "\n<!-- SEO meta — wallaroo-bbq-boats theme -->\n";

    // Meta description
    if ( ! empty( $meta_desc ) ) {
        echo '<meta name="description" content="' . esc_attr( $meta_desc ) . '">' . "\n";
    }

    // Canonical
    echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";

    // Open Graph
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url( $canonical ) . '">' . "\n";
    if ( ! empty( $meta_desc ) ) {
        echo '<meta property="og:description" content="' . esc_attr( $meta_desc ) . '">' . "\n";
    }
    if ( ! empty( $og_image_url ) ) {
        echo '<meta property="og:image" content="' . esc_url( $og_image_url ) . '">' . "\n";
        if ( $og_image_width > 0 ) {
            echo '<meta property="og:image:width" content="' . esc_attr( (string) $og_image_width ) . '">' . "\n";
        }
        if ( $og_image_height > 0 ) {
            echo '<meta property="og:image:height" content="' . esc_attr( (string) $og_image_height ) . '">' . "\n";
        }
        echo '<meta property="og:image:alt" content="' . esc_attr( $site_name ) . '">' . "\n";
    }

    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $og_title ) . '">' . "\n";
    if ( ! empty( $meta_desc ) ) {
        echo '<meta name="twitter:description" content="' . esc_attr( $meta_desc ) . '">' . "\n";
    }
    if ( ! empty( $og_image_url ) ) {
        echo '<meta name="twitter:image" content="' . esc_url( $og_image_url ) . '">' . "\n";
    }

    // ── JSON-LD — LocalBusiness ───────────────────────────────────────────────

    // Normalise phone to E.164 (+61...)
    $tel_clean = preg_replace( '/[^\d+]/', '', $phone );
    if ( str_starts_with( $tel_clean, '0' ) ) {
        $tel_e164 = '+61' . substr( $tel_clean, 1 );
    } elseif ( ! str_starts_with( $tel_clean, '+' ) ) {
        $tel_e164 = '+61' . $tel_clean;
    } else {
        $tel_e164 = $tel_clean;
    }

    $ld = [
        '@context'    => 'https://schema.org',
        '@type'       => 'LocalBusiness',
        'name'        => $site_name,
        'url'         => home_url( '/' ),
        'telephone'   => $tel_e164,
        'email'       => $email,
        'description' => 'Self-drive BBQ boat hire at Copper Cove Marina, Wallaroo SA. 2 to 6 people per boat. No licence needed. BYO food welcome.',
        'address'     => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $addr1,
            'addressLocality' => 'Wallaroo',
            'addressRegion'   => 'SA',
            'postalCode'      => '5556',
            'addressCountry'  => 'AU',
        ],
        'geo'         => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => -33.9349,
            'longitude' => 137.6219,
        ],
        'priceRange'  => '$$',
        'areaServed'  => [
            '@type' => 'State',
            'name'  => 'South Australia',
        ],
    ];

    if ( ! empty( $og_image_url ) ) {
        $ld['image'] = $og_image_url;
    }

    echo '<script type="application/ld+json">' . "\n";
    echo wp_json_encode( $ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
    echo "\n" . '</script>' . "\n";
    echo "<!-- /SEO meta -->\n\n";

}, 1 );
