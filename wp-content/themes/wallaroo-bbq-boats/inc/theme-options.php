<?php
/**
 * Wallaroo BBQ Boats — Theme Options
 *
 * Registers a top-level settings page that appears first in the WordPress
 * admin menu. Stores site-wide options (logo, phone, email, address,
 * booking URL) in wp_options via the Settings API.
 *
 * Usage in templates:
 *   wallaroo_option( 'phone' )       → value or fallback
 *   wallaroo_option( 'email' )
 *   wallaroo_option( 'address_line1' )
 *   wallaroo_option( 'address_line2' )
 *   wallaroo_option( 'footer_tagline' )
 *   wallaroo_option( 'booking_url' )
 *   wallaroo_logo_html( $attrs )     → <img> tag or false
 */

defined( 'ABSPATH' ) || exit;

// ============================================================
// DEFAULTS — used both in templates and in the settings form
// ============================================================
define( 'WALLAROO_DEFAULTS', [
    'phone'            => '0416 106 041',
    'email'            => 'hello@wallaroobbqboats.com.au',
    'address_line1'    => 'Copper Cove Marina',
    'address_line2'    => 'Wallaroo, South Australia',
    'footer_tagline'   => 'Self-drive BBQ boat hire at Copper Cove Marina, Wallaroo, South Australia. Launching September 2026.',
    'booking_url'      => '',
    'facebook_url'     => '',
    'instagram_url'    => '',
    'logo_id'          => 0,
    'meta_description' => 'Self-drive BBQ boat hire at Copper Cove Marina, Wallaroo SA. 2 to 6 people per boat. No licence needed. Book your session online.',
] );

/**
 * Get a theme option value, falling back to the defined default.
 */
function wallaroo_option( string $key ): string {
    $default = WALLAROO_DEFAULTS[ $key ] ?? '';
    $value   = get_option( 'wallaroo_' . $key, $default );
    return $value !== '' ? $value : $default;
}

/**
 * Return an <img> tag for the site logo, or false if none uploaded.
 *
 * @param array $attrs Extra HTML attributes merged onto the <img>.
 * @return string|false
 */
function wallaroo_logo_html( array $attrs = [] ): string|false {
    $logo_id = (int) get_option( 'wallaroo_logo_id', 0 );
    if ( ! $logo_id ) {
        return false;
    }
    $defaults = [
        'class' => 'wallaroo-logo',
        'alt'   => get_bloginfo( 'name' ),
    ];
    return wp_get_attachment_image( $logo_id, 'full', false, array_merge( $defaults, $attrs ) );
}

/**
 * Return an <img> tag for the footer logo, or false if none uploaded.
 *
 * Falls back to the main site logo when no dedicated footer logo is set.
 *
 * @param array $attrs Extra HTML attributes merged onto the <img>.
 * @return string|false
 */
function wallaroo_footer_logo_html( array $attrs = [] ): string|false {
    $logo_id = (int) get_option( 'wallaroo_footer_logo_id', 0 );
    if ( ! $logo_id ) {
        // Fall back to the main logo.
        return wallaroo_logo_html( $attrs );
    }
    $defaults = [
        'class' => 'wallaroo-logo',
        'alt'   => get_bloginfo( 'name' ),
    ];
    return wp_get_attachment_image( $logo_id, 'full', false, array_merge( $defaults, $attrs ) );
}

// ============================================================
// ADMIN MENU
// ============================================================
add_action( 'admin_menu', function () {
    add_menu_page(
        'Wallaroo BBQ Boats — Site Settings',
        'Site Settings',
        'manage_options',
        'wallaroo-settings',
        'wallaroo_render_settings_page',
        'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a7aaad" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>' ),
        1 // First in menu, above Dashboard
    );
} );

// ============================================================
// REGISTER SETTINGS
// ============================================================
add_action( 'admin_init', function () {
    $text    = [ 'sanitize_callback' => 'sanitize_text_field' ];
    $email   = [ 'sanitize_callback' => 'sanitize_email' ];
    $url     = [ 'sanitize_callback' => 'esc_url_raw' ];
    $int     = [ 'sanitize_callback' => 'absint' ];
    $area    = [ 'sanitize_callback' => 'sanitize_textarea_field' ];

    register_setting( 'wallaroo_options_group', 'wallaroo_logo_id',        $int  );
    register_setting( 'wallaroo_options_group', 'wallaroo_footer_logo_id', $int  );
    register_setting( 'wallaroo_options_group', 'wallaroo_phone',          $text );
    register_setting( 'wallaroo_options_group', 'wallaroo_email',          $email );
    register_setting( 'wallaroo_options_group', 'wallaroo_address_line1',  $text );
    register_setting( 'wallaroo_options_group', 'wallaroo_address_line2',  $text );
    register_setting( 'wallaroo_options_group', 'wallaroo_footer_tagline',   $area );
    register_setting( 'wallaroo_options_group', 'wallaroo_booking_url',     $url  );
    register_setting( 'wallaroo_options_group', 'wallaroo_facebook_url',    $url  );
    register_setting( 'wallaroo_options_group', 'wallaroo_instagram_url',   $url  );
    register_setting( 'wallaroo_options_group', 'wallaroo_meta_description', $area );
} );

// ============================================================
// ENQUEUE ADMIN ASSETS (settings page only)
// ============================================================
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( 'toplevel_page_wallaroo-settings' !== $hook ) {
        return;
    }
    wp_enqueue_media(); // WordPress media library
    wp_enqueue_script(
        'wallaroo-admin',
        get_template_directory_uri() . '/assets/js/admin.js',
        [ 'jquery' ],
        wp_get_theme()->get( 'Version' ),
        true
    );
} );

// ============================================================
// SETTINGS PAGE RENDER
// ============================================================
function wallaroo_render_settings_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Read current values (with fallbacks)
    $logo_id         = (int) get_option( 'wallaroo_logo_id', 0 );
    $logo_url        = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
    $footer_logo_id  = (int) get_option( 'wallaroo_footer_logo_id', 0 );
    $footer_logo_url = $footer_logo_id ? wp_get_attachment_image_url( $footer_logo_id, 'medium' ) : '';
    ?>
    <div class="wrap" id="wallaroo-settings-wrap">

        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;padding-top:16px;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0A2A5E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
            <h1 style="margin:0;font-size:1.5rem;">Wallaroo BBQ Boats — Site Settings</h1>
        </div>
        <p style="color:#646970;margin-top:0;margin-bottom:24px;">
            These values populate the header, footer, and booking buttons across the entire site.
            Changes take effect immediately on save — no rebuild required.
        </p>

        <?php settings_errors( 'wallaroo_options_group' ); ?>

        <form method="post" action="options.php" enctype="multipart/form-data">
            <?php settings_fields( 'wallaroo_options_group' ); ?>

            <!-- ── BRAND & LOGO ──────────────────────────────── -->
            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:24px;margin-bottom:20px;max-width:760px;">
                <h2 style="margin-top:0;padding-bottom:12px;border-bottom:1px solid #f0f0f1;font-size:1rem;">
                    Brand &amp; Logo
                </h2>

                <table class="form-table" role="presentation" style="margin-top:0;">
                    <tr>
                        <th scope="row" style="width:200px;">
                            <label>Site Logo</label>
                        </th>
                        <td class="wallaroo-logo-uploader">
                            <div class="wallaroo-logo-preview" style="margin-bottom:10px;<?php echo $logo_url ? '' : 'display:none;'; ?>">
                                <img
                                    src="<?php echo esc_url( $logo_url ); ?>"
                                    class="wallaroo-logo-img"
                                    style="max-height:80px;max-width:300px;display:block;border:1px solid #ddd;border-radius:4px;padding:4px;background:#f6f7f7;"
                                    alt="Current logo"
                                >
                            </div>
                            <input type="hidden" name="wallaroo_logo_id" class="wallaroo-logo-id" value="<?php echo esc_attr( $logo_id ); ?>">
                            <button type="button" class="wallaroo-upload-logo button button-secondary" data-upload-label="Upload Logo" data-replace-label="Replace Logo">
                                <?php echo $logo_url ? 'Replace Logo' : 'Upload Logo'; ?>
                            </button>
                            <?php if ( $logo_url ) : ?>
                            <button type="button" class="wallaroo-remove-logo button button-link-delete" style="margin-left:8px;">
                                Remove
                            </button>
                            <?php endif; ?>
                            <p class="description" style="margin-top:8px;">
                                Upload a PNG or SVG. Recommended height: 40–60px. Used in the header, and in the footer if no separate footer logo is set.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" style="width:200px;">
                            <label>Footer Logo</label>
                        </th>
                        <td class="wallaroo-logo-uploader">
                            <div class="wallaroo-logo-preview" style="margin-bottom:10px;background:#0A2A5E;border-radius:4px;padding:8px;<?php echo $footer_logo_url ? '' : 'display:none;'; ?>">
                                <img
                                    src="<?php echo esc_url( $footer_logo_url ); ?>"
                                    class="wallaroo-logo-img"
                                    style="max-height:80px;max-width:300px;display:block;"
                                    alt="Current footer logo"
                                >
                            </div>
                            <input type="hidden" name="wallaroo_footer_logo_id" class="wallaroo-logo-id" value="<?php echo esc_attr( $footer_logo_id ); ?>">
                            <button type="button" class="wallaroo-upload-logo button button-secondary" data-upload-label="Upload Footer Logo" data-replace-label="Replace Footer Logo">
                                <?php echo $footer_logo_url ? 'Replace Footer Logo' : 'Upload Footer Logo'; ?>
                            </button>
                            <?php if ( $footer_logo_url ) : ?>
                            <button type="button" class="wallaroo-remove-logo button button-link-delete" style="margin-left:8px;">
                                Remove
                            </button>
                            <?php endif; ?>
                            <p class="description" style="margin-top:8px;">
                                Optional. The footer sits on a dark navy background, so upload a light/white version of your logo here. Leave blank to use the main site logo as-is. (Preview above shown on the footer's background colour.)
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ── CONTACT DETAILS ───────────────────────────── -->
            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:24px;margin-bottom:20px;max-width:760px;">
                <h2 style="margin-top:0;padding-bottom:12px;border-bottom:1px solid #f0f0f1;font-size:1rem;">
                    Contact Details
                </h2>
                <p style="color:#646970;font-size:13px;margin-top:0;">
                    Appears in the footer, the sticky header CTA, and mobile booking buttons.
                </p>

                <table class="form-table" role="presentation" style="margin-top:0;">
                    <tr>
                        <th scope="row" style="width:200px;">
                            <label for="wallaroo_phone">Phone Number</label>
                        </th>
                        <td>
                            <input
                                type="tel"
                                name="wallaroo_phone"
                                id="wallaroo_phone"
                                value="<?php echo esc_attr( wallaroo_option( 'phone' ) ); ?>"
                                class="regular-text"
                                placeholder="0416 106 041"
                            >
                            <p class="description">Used in <code>tel:</code> links — include spaces for display, they are stripped automatically for the link.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wallaroo_email">Email Address</label>
                        </th>
                        <td>
                            <input
                                type="email"
                                name="wallaroo_email"
                                id="wallaroo_email"
                                value="<?php echo esc_attr( wallaroo_option( 'email' ) ); ?>"
                                class="regular-text"
                                placeholder="hello@wallaroobbqboats.com.au"
                            >
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ── LOCATION ──────────────────────────────────── -->
            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:24px;margin-bottom:20px;max-width:760px;">
                <h2 style="margin-top:0;padding-bottom:12px;border-bottom:1px solid #f0f0f1;font-size:1rem;">
                    Location
                </h2>

                <table class="form-table" role="presentation" style="margin-top:0;">
                    <tr>
                        <th scope="row" style="width:200px;">
                            <label for="wallaroo_address_line1">Address — Line 1</label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="wallaroo_address_line1"
                                id="wallaroo_address_line1"
                                value="<?php echo esc_attr( wallaroo_option( 'address_line1' ) ); ?>"
                                class="regular-text"
                                placeholder="Copper Cove Marina"
                            >
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wallaroo_address_line2">Address — Line 2</label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="wallaroo_address_line2"
                                id="wallaroo_address_line2"
                                value="<?php echo esc_attr( wallaroo_option( 'address_line2' ) ); ?>"
                                class="regular-text"
                                placeholder="Wallaroo, South Australia"
                            >
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ── FOOTER ────────────────────────────────────── -->
            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:24px;margin-bottom:20px;max-width:760px;">
                <h2 style="margin-top:0;padding-bottom:12px;border-bottom:1px solid #f0f0f1;font-size:1rem;">
                    Footer
                </h2>

                <table class="form-table" role="presentation" style="margin-top:0;">
                    <tr>
                        <th scope="row" style="width:200px;">
                            <label for="wallaroo_footer_tagline">Tagline / Description</label>
                        </th>
                        <td>
                            <textarea
                                name="wallaroo_footer_tagline"
                                id="wallaroo_footer_tagline"
                                rows="3"
                                class="large-text"
                                placeholder="Self-drive BBQ boat hire at Copper Cove Marina, Wallaroo, South Australia."
                            ><?php echo esc_textarea( wallaroo_option( 'footer_tagline' ) ); ?></textarea>
                            <p class="description">Short descriptor shown under the logo in the footer.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ── BOOKING ───────────────────────────────────── -->
            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:24px;margin-bottom:24px;max-width:760px;">
                <h2 style="margin-top:0;padding-bottom:12px;border-bottom:1px solid #f0f0f1;font-size:1rem;">
                    Booking
                </h2>
                <p style="color:#646970;font-size:13px;margin-top:0;">
                    When you connect a booking system (e.g. Rezdy, FareHarbor), paste the URL here.
                    All "Book Now" buttons site-wide will link to it instead of the on-page card.
                </p>

                <table class="form-table" role="presentation" style="margin-top:0;">
                    <tr>
                        <th scope="row" style="width:200px;">
                            <label for="wallaroo_booking_url">Booking URL</label>
                        </th>
                        <td>
                            <input
                                type="url"
                                name="wallaroo_booking_url"
                                id="wallaroo_booking_url"
                                value="<?php echo esc_url( wallaroo_option( 'booking_url' ) ); ?>"
                                class="large-text"
                                placeholder="https://book.wallaroobbqboats.com.au"
                            >
                            <p class="description">Leave blank to send "Book Now" buttons to the Book Now page. Enter an external URL (e.g. Rezdy, FareHarbor) to override.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ── SOCIAL ────────────────────────────────────── -->
            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:24px;margin-bottom:20px;max-width:760px;">
                <h2 style="margin-top:0;padding-bottom:12px;border-bottom:1px solid #f0f0f1;font-size:1rem;">
                    Social Media
                </h2>
                <p style="color:#646970;font-size:13px;margin-top:0;">
                    Paste the full URL to each profile. Icons appear in the footer automatically — only the ones you fill in are shown.
                </p>

                <table class="form-table" role="presentation" style="margin-top:0;">
                    <tr>
                        <th scope="row" style="width:200px;">
                            <label for="wallaroo_facebook_url">Facebook URL</label>
                        </th>
                        <td>
                            <input
                                type="url"
                                name="wallaroo_facebook_url"
                                id="wallaroo_facebook_url"
                                value="<?php echo esc_url( wallaroo_option( 'facebook_url' ) ); ?>"
                                class="large-text"
                                placeholder="https://www.facebook.com/wallaroobbqboats"
                            >
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wallaroo_instagram_url">Instagram URL</label>
                        </th>
                        <td>
                            <input
                                type="url"
                                name="wallaroo_instagram_url"
                                id="wallaroo_instagram_url"
                                value="<?php echo esc_url( wallaroo_option( 'instagram_url' ) ); ?>"
                                class="large-text"
                                placeholder="https://www.instagram.com/wallaroobbqboats"
                            >
                            <p class="description">Optional — leave blank for now if there's no Instagram account yet.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ── SEO ───────────────────────────────────────── -->
            <div style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:24px;margin-bottom:24px;max-width:760px;">
                <h2 style="margin-top:0;padding-bottom:12px;border-bottom:1px solid #f0f0f1;font-size:1rem;">
                    SEO
                </h2>
                <p style="color:#646970;font-size:13px;margin-top:0;">
                    Site-wide default meta description used as a fallback when a page has no individual description set.
                    You can override this per-page in the page editor sidebar under the <strong>SEO</strong> panel.
                </p>

                <table class="form-table" role="presentation" style="margin-top:0;">
                    <tr>
                        <th scope="row" style="width:200px;">
                            <label for="wallaroo_meta_description">Default Meta Description</label>
                        </th>
                        <td>
                            <textarea
                                name="wallaroo_meta_description"
                                id="wallaroo_meta_description"
                                rows="3"
                                class="large-text"
                                placeholder="Self-drive BBQ boat hire at Copper Cove Marina, Wallaroo SA. No licence needed. Book your session online."
                            ><?php echo esc_textarea( wallaroo_option( 'meta_description' ) ); ?></textarea>
                            <p class="description">Aim for 120–155 characters. Currently: <span id="wbb-meta-desc-count">0</span> characters.
                                <script>
                                (function(){
                                    var ta = document.getElementById('wallaroo_meta_description');
                                    var ct = document.getElementById('wbb-meta-desc-count');
                                    function update(){ ct.textContent = ta.value.length; ct.style.color = ta.value.length > 155 ? '#d63638' : '#1d2327'; }
                                    ta.addEventListener('input', update);
                                    update();
                                })();
                                </script>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button( 'Save Settings', 'primary large' ); ?>

        </form>
    </div>
    <?php
}
