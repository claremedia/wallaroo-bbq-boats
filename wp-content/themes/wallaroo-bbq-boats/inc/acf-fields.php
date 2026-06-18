<?php
/**
 * Wallaroo BBQ Boats — ACF Local Field Groups
 *
 * Uses individual fields only (no repeaters) so ACF Free is sufficient.
 * Icons for the trust strip are hardcoded SVGs in front-page.php.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', function () {

    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    // --------------------------------------------------------
    // Homepage Hero
    // --------------------------------------------------------
    acf_add_local_field_group( [
        'key'    => 'group_homepage_hero',
        'title'  => 'Homepage — Hero',
        'fields' => [
            [
                'key'           => 'field_hero_image',
                'label'         => 'Hero Background Image',
                'name'          => 'hero_image',
                'type'          => 'image',
                'return_format' => 'array',
                'preview_size'  => 'medium',
                'instructions'  => 'Recommended: landscape, at least 1600×900px.',
            ],
            [
                'key'           => 'field_hero_headline',
                'label'         => 'Headline',
                'name'          => 'hero_headline',
                'type'          => 'text',
                'default_value' => 'SELF-DRIVE BBQ BOATS. WALLAROO MARINA.',
            ],
            [
                'key'           => 'field_hero_subheading',
                'label'         => 'Subheading',
                'name'          => 'hero_subheading',
                'type'          => 'text',
                'default_value' => 'Hire a boat, fire up the BBQ, and spend the day on the water.',
            ],
        ],
        'location'   => [ [ [ 'param' => 'page_type', 'operator' => '==', 'value' => 'front_page' ] ] ],
        'menu_order' => 0,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // Images
    // --------------------------------------------------------
    acf_add_local_field_group( [
        'key'    => 'group_homepage_images',
        'title'  => 'Homepage — Images',
        'fields' => [
            [
                'key'           => 'field_who_image',
                'label'         => '"Who It\'s For" Section Image',
                'name'          => 'who_image',
                'type'          => 'image',
                'return_format' => 'array',
                'preview_size'  => 'medium',
                'instructions'  => 'Square crop works best. Shown on the right side of the "Who It\'s For" section on desktop.',
            ],
        ],
        'location'   => [ [ [ 'param' => 'page_type', 'operator' => '==', 'value' => 'front_page' ] ] ],
        'menu_order' => 5,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // What's On Board — image/icon + text per row
    // ACF Free has no Repeater, so we expose a fixed set of
    // rows (image + text). Blank rows are skipped on the front
    // end, so this behaves like a repeater for the editor.
    // --------------------------------------------------------
    $board_defaults = [
        'Gas BBQ and all cooking gear',
        '2 to 6 people per boat',
        'Cold drinks on board (no BYO alcohol)',
        'BYO food or order a platter',
        'Life jackets and safety briefing included',
    ];
    $board_fields = [];
    for ( $i = 1; $i <= 8; $i++ ) {
        $board_fields[] = [
            'key'           => 'field_board_item_' . $i . '_icon',
            'label'         => 'Item ' . $i . ' — Image / Icon',
            'name'          => 'board_item_' . $i . '_icon',
            'type'          => 'image',
            'return_format' => 'array',
            'preview_size'  => 'thumbnail',
            'instructions'  => 1 === $i ? 'Optional small image or icon shown beside the text. Leave the text blank to hide a row.' : '',
        ];
        $board_fields[] = [
            'key'           => 'field_board_item_' . $i . '_text',
            'label'         => 'Item ' . $i . ' — Text',
            'name'          => 'board_item_' . $i . '_text',
            'type'          => 'text',
            'default_value' => $board_defaults[ $i - 1 ] ?? '',
        ];
    }
    acf_add_local_field_group( [
        'key'        => 'group_homepage_board',
        'title'      => "Homepage — What's On Board",
        'fields'     => $board_fields,
        'location'   => [ [ [ 'param' => 'page_type', 'operator' => '==', 'value' => 'front_page' ] ] ],
        'menu_order' => 6,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // Trust Strip — 4 individual labels (icons are hardcoded)
    // --------------------------------------------------------
    acf_add_local_field_group( [
        'key'    => 'group_homepage_trust',
        'title'  => 'Homepage — Trust Strip',
        'fields' => [
            [
                'key'           => 'field_trust_label_1',
                'label'         => 'Item 1 Label',
                'name'          => 'trust_label_1',
                'type'          => 'text',
                'default_value' => 'No licence needed',
            ],
            [
                'key'           => 'field_trust_label_2',
                'label'         => 'Item 2 Label',
                'name'          => 'trust_label_2',
                'type'          => 'text',
                'default_value' => '4 to 16 people',
            ],
            [
                'key'           => 'field_trust_label_3',
                'label'         => 'Item 3 Label',
                'name'          => 'trust_label_3',
                'type'          => 'text',
                'default_value' => 'BYO food welcome',
            ],
            [
                'key'           => 'field_trust_label_4',
                'label'         => 'Item 4 Label',
                'name'          => 'trust_label_4',
                'type'          => 'text',
                'default_value' => 'Down at the marina',
            ],
            [
                'key'           => 'field_trust_label_5',
                'label'         => 'Item 5 Label',
                'name'          => 'trust_label_5',
                'type'          => 'text',
                'default_value' => '100% locally owned & operated',
            ],
        ],
        'location'   => [ [ [ 'param' => 'page_type', 'operator' => '==', 'value' => 'front_page' ] ] ],
        'menu_order' => 10,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // How It Works — 3 steps, individual fields each
    // --------------------------------------------------------
    acf_add_local_field_group( [
        'key'    => 'group_homepage_how',
        'title'  => 'Homepage — How It Works',
        'fields' => [
            // Step 1
            [
                'key'           => 'field_step_1_number',
                'label'         => 'Step 1 — Number',
                'name'          => 'step_1_number',
                'type'          => 'text',
                'default_value' => '01',
            ],
            [
                'key'           => 'field_step_1_heading',
                'label'         => 'Step 1 — Heading',
                'name'          => 'step_1_heading',
                'type'          => 'text',
                'default_value' => 'Book online',
            ],
            [
                'key'           => 'field_step_1_body',
                'label'         => 'Step 1 — Body',
                'name'          => 'step_1_body',
                'type'          => 'textarea',
                'rows'          => 2,
                'default_value' => 'Pick your date and group size. Confirm your booking in minutes.',
            ],
            // Step 2
            [
                'key'           => 'field_step_2_number',
                'label'         => 'Step 2 — Number',
                'name'          => 'step_2_number',
                'type'          => 'text',
                'default_value' => '02',
            ],
            [
                'key'           => 'field_step_2_heading',
                'label'         => 'Step 2 — Heading',
                'name'          => 'step_2_heading',
                'type'          => 'text',
                'default_value' => 'Turn up 15 minutes early',
            ],
            [
                'key'           => 'field_step_2_body',
                'label'         => 'Step 2 — Body',
                'name'          => 'step_2_body',
                'type'          => 'textarea',
                'rows'          => 2,
                'default_value' => 'We\'ll run you through the quick safety briefing and show you the ropes.',
            ],
            // Step 3
            [
                'key'           => 'field_step_3_number',
                'label'         => 'Step 3 — Number',
                'name'          => 'step_3_number',
                'type'          => 'text',
                'default_value' => '03',
            ],
            [
                'key'           => 'field_step_3_heading',
                'label'         => 'Step 3 — Heading',
                'name'          => 'step_3_heading',
                'type'          => 'text',
                'default_value' => 'We hand you the keys — sort yourselves out',
            ],
            [
                'key'           => 'field_step_3_body',
                'label'         => 'Step 3 — Body',
                'name'          => 'step_3_body',
                'type'          => 'textarea',
                'rows'          => 2,
                'default_value' => 'Head out onto Spencer Gulf, fire up the BBQ, and enjoy your session on the water.',
            ],
        ],
        'location'   => [ [ [ 'param' => 'page_type', 'operator' => '==', 'value' => 'front_page' ] ] ],
        'menu_order' => 20,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // Reviews CPT — rating field only (name = title, quote = content)
    // --------------------------------------------------------
    acf_add_local_field_group( [
        'key'    => 'group_review_fields',
        'title'  => 'Review Details',
        'fields' => [
            [
                'key'           => 'field_review_rating',
                'label'         => 'Rating',
                'name'          => 'review_rating',
                'type'          => 'number',
                'min'           => 1,
                'max'           => 5,
                'default_value' => 5,
                'instructions'  => 'Score out of 5.',
            ],
        ],
        'location'   => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'wbb_review' ] ] ],
        'menu_order' => 0,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // Page: Book Now
    // --------------------------------------------------------
    acf_add_local_field_group( [
        'key'    => 'group_page_book_now',
        'title'  => 'Book Now — Content',
        'fields' => [
            [
                'key'           => 'field_bn_hero_headline',
                'label'         => 'Hero Headline',
                'name'          => 'bn_hero_headline',
                'type'          => 'text',
                'default_value' => 'BOOK YOUR BOAT',
            ],
            [
                'key'           => 'field_bn_hero_subheading',
                'label'         => 'Hero Subheading',
                'name'          => 'bn_hero_subheading',
                'type'          => 'text',
                'default_value' => 'Pick a date, grab the crew, and head down the marina.',
            ],
            [
                'key'           => 'field_bn_placeholder_message',
                'label'         => 'Booking Placeholder Message',
                'name'          => 'bn_placeholder_message',
                'type'          => 'textarea',
                'rows'          => 3,
                'default_value' => 'Online booking coming soon. Call us or email to check availability and lock in your date.',
                'instructions'  => 'Update this when the online booking system goes live.',
            ],
        ],
        'location'   => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-book-now.php' ] ] ],
        'menu_order' => 0,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // Page: What's On Board
    // --------------------------------------------------------
    acf_add_local_field_group( [
        'key'    => 'group_page_whats_on_board',
        'title'  => "What's On Board — Content",
        'fields' => [
            [
                'key'           => 'field_wob_hero_headline',
                'label'         => 'Hero Headline',
                'name'          => 'wob_hero_headline',
                'type'          => 'text',
                'default_value' => "WHAT'S ON BOARD",
            ],
            [
                'key'           => 'field_wob_hero_subheading',
                'label'         => 'Hero Subheading',
                'name'          => 'wob_hero_subheading',
                'type'          => 'text',
                'default_value' => 'Everything you need for a great day on the water.',
            ],
            [
                'key'           => 'field_wob_hero_image',
                'label'         => 'Hero Background Image',
                'name'          => 'wob_hero_image',
                'type'          => 'image',
                'return_format' => 'array',
                'preview_size'  => 'medium',
                'instructions'  => 'Full-bleed hero image. Landscape, at least 1400×600px. Leave blank for a plain colour hero.',
            ],
            [
                'key'           => 'field_wob_boat_image',
                'label'         => 'Boat / Features Image',
                'name'          => 'wob_boat_image',
                'type'          => 'image',
                'return_format' => 'array',
                'preview_size'  => 'medium',
                'instructions'  => 'Shown alongside the features list. Landscape or portrait both work.',
            ],
            [
                'key'           => 'field_wob_food_copy',
                'label'         => 'Bring Your Own Food — Copy',
                'name'          => 'wob_food_copy',
                'type'          => 'textarea',
                'rows'          => 2,
                'default_value' => 'Bring a packed lunch, a snag pack, or order a grazing platter. The BBQ is fired up and ready to go.',
            ],
            [
                'key'           => 'field_wob_drinks_copy',
                'label'         => 'Drinks On Board — Copy',
                'name'          => 'wob_drinks_copy',
                'type'          => 'textarea',
                'rows'          => 2,
                'default_value' => 'Cold drinks available to purchase on board. No BYO alcohol — keep it safe and legal on the water.',
            ],
        ],
        'location'   => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-whats-on-board.php' ] ] ],
        'menu_order' => 0,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // Page: Groups & Workplaces
    // --------------------------------------------------------
    acf_add_local_field_group( [
        'key'    => 'group_page_groups',
        'title'  => 'Groups — Content',
        'fields' => [
            [
                'key'           => 'field_grp_hero_headline',
                'label'         => 'Hero Headline',
                'name'          => 'grp_hero_headline',
                'type'          => 'text',
                'default_value' => 'BOOK THE WHOLE FLEET',
            ],
            [
                'key'           => 'field_grp_hero_subheading',
                'label'         => 'Hero Subheading',
                'name'          => 'grp_hero_subheading',
                'type'          => 'text',
                'default_value' => 'Christmas parties, team days, birthdays, bucks, hens. Private hire from 4 to 16.',
            ],
            [
                'key'           => 'field_grp_hire_headline',
                'label'         => 'Private Hire Headline',
                'name'          => 'grp_hire_headline',
                'type'          => 'text',
                'default_value' => 'THE WHOLE BOAT IS YOURS',
            ],
            [
                'key'           => 'field_grp_hire_body',
                'label'         => 'Private Hire Body Copy',
                'name'          => 'grp_hire_body',
                'type'          => 'textarea',
                'rows'          => 3,
                'default_value' => 'Every booking is a private hire. There\'s no sharing with strangers — the boat is yours for the session. Bring whoever you like, eat whatever you want, and enjoy the water on your terms.',
            ],
            [
                'key'           => 'field_grp_hero_image',
                'label'         => 'Hero Background Image',
                'name'          => 'grp_hero_image',
                'type'          => 'image',
                'return_format' => 'array',
                'preview_size'  => 'medium',
                'instructions'  => 'Full-bleed hero image. Landscape, at least 1400×600px. Leave blank for a plain colour hero.',
            ],
            [
                'key'           => 'field_grp_hire_image',
                'label'         => 'Private Hire Section Image',
                'name'          => 'grp_hire_image',
                'type'          => 'image',
                'return_format' => 'array',
                'preview_size'  => 'medium',
                'instructions'  => 'Shown alongside the private hire callout. Portrait or square works best. Leave blank to show text only.',
            ],
        ],
        'location'   => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-groups.php' ] ] ],
        'menu_order' => 0,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // Page: Gift Vouchers
    // --------------------------------------------------------
    acf_add_local_field_group( [
        'key'    => 'group_page_gift_vouchers',
        'title'  => 'Gift Vouchers — Content',
        'fields' => [
            [
                'key'           => 'field_gv_hero_headline',
                'label'         => 'Hero Headline',
                'name'          => 'gv_hero_headline',
                'type'          => 'text',
                'default_value' => 'GIVE THEM A DAY ON THE WATER',
            ],
            [
                'key'           => 'field_gv_hero_subheading',
                'label'         => 'Hero Subheading',
                'name'          => 'gv_hero_subheading',
                'type'          => 'text',
                'default_value' => 'Gift vouchers for any occasion.',
            ],
            [
                'key'           => 'field_gv_voucher_tagline',
                'label'         => 'Voucher Tagline',
                'name'          => 'gv_voucher_tagline',
                'type'          => 'text',
                'default_value' => 'Available in any amount. Redeemable online.',
            ],
        ],
        'location'   => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-gift-vouchers.php' ] ] ],
        'menu_order' => 0,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // Page: FAQ
    // --------------------------------------------------------
    acf_add_local_field_group( [
        'key'    => 'group_page_faq',
        'title'  => 'FAQ — Content',
        'fields' => [
            [
                'key'           => 'field_faq_hero_headline',
                'label'         => 'Hero Headline',
                'name'          => 'faq_hero_headline',
                'type'          => 'text',
                'default_value' => 'QUESTIONS ANSWERED',
            ],
            [
                'key'           => 'field_faq_hero_subheading',
                'label'         => 'Hero Subheading',
                'name'          => 'faq_hero_subheading',
                'type'          => 'text',
                'default_value' => 'Everything you need to know before you head down the marina.',
            ],
        ],
        'location'   => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-faq.php' ] ] ],
        'menu_order' => 0,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // Page: Find Us
    // --------------------------------------------------------
    acf_add_local_field_group( [
        'key'    => 'group_page_find_us',
        'title'  => 'Find Us — Content',
        'fields' => [
            [
                'key'           => 'field_fu_hero_headline',
                'label'         => 'Hero Headline',
                'name'          => 'fu_hero_headline',
                'type'          => 'text',
                'default_value' => 'FIND US DOWN THE MARINA',
            ],
            [
                'key'           => 'field_fu_hero_subheading',
                'label'         => 'Hero Subheading',
                'name'          => 'fu_hero_subheading',
                'type'          => 'text',
                'default_value' => 'Copper Cove Marina, Wallaroo SA.',
            ],
            [
                'key'           => 'field_fu_directions_kadina',
                'label'         => 'Directions — From Kadina',
                'name'          => 'fu_directions_kadina',
                'type'          => 'textarea',
                'rows'          => 2,
                'default_value' => 'Head north on the Copper Coast Highway toward Wallaroo. Follow signs to the marina.',
            ],
            [
                'key'           => 'field_fu_directions_adelaide',
                'label'         => 'Directions — From Adelaide',
                'name'          => 'fu_directions_adelaide',
                'type'          => 'textarea',
                'rows'          => 2,
                'default_value' => 'Take the Yorke Highway north. Turn off at Kadina and follow signs to Wallaroo marina.',
            ],
            [
                'key'           => 'field_fu_directions_copper_coast',
                'label'         => 'Directions — From Copper Coast',
                'name'          => 'fu_directions_copper_coast',
                'type'          => 'textarea',
                'rows'          => 2,
                'default_value' => 'Head into Wallaroo town centre and follow the signs down to Copper Cove Marina.',
            ],
            [
                'key'           => 'field_fu_opening_times',
                'label'         => 'Opening Times',
                'name'          => 'fu_opening_times',
                'type'          => 'textarea',
                'rows'          => 3,
                'default_value' => "Monday – Friday: by appointment\nSaturday – Sunday: 8am – 6pm\nPublic Holidays: 9am – 5pm",
                'instructions'  => 'One line per day/range. Plain text only.',
            ],
            [
                'key'           => 'field_fu_parking_note',
                'label'         => 'Parking Note',
                'name'          => 'fu_parking_note',
                'type'          => 'text',
                'default_value' => 'Free parking is available at the marina. Look for the Copper Cove Marina signs.',
            ],
        ],
        'location'   => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-find-us.php' ] ] ],
        'menu_order' => 0,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // What's On Board — Included / Not Included lists (editable rows)
    // Up to 8 rows each; blank rows are skipped on the front end.
    // --------------------------------------------------------
    $wob_included_defaults = [
        'Use of your own BBQ Boat for 2 hours, self-driven.',
        'Maximum capacity is 6 participants per boat. Bookings of 7 to 12 people will need to hire two BBQ Boats.',
        'A Bluetooth speaker is available.',
        'Esky and ice provided at no charge.',
        'No BYO alcohol — feel free to bring your own soft drinks or water, or purchase them from us.',
        'Cutlery, plates, cups, napkins etc. provided.',
        'BBQ provided if requested, or keep it as a table for platters or other food.',
    ];
    $wob_notincluded_defaults = [
        'We can provide platters if requested.',
        'We prefer that you BYO your own meat for the BBQ. We have limited food options, but you can order these from us with plenty of notice from our price list.',
        'Ask us about our Spencer Gulf King prawn and wine deal.',
        'Drinks — BYO alcohol is not permitted. You may order drinks through the price list on our website; this is a liquor licensing obligation.',
        'You are encouraged to pre-order your drinks from Wallaroo Marina BBQ Boats up to 48 hours before your trip. Please reach out if you have difficulty and we can help with the purchase — ordering on the day will reduce your cruise time.',
        'We will assist you wherever we can to provide you with what you want for your own personalised trip.',
    ];
    $wob_feature_fields = [];
    for ( $i = 1; $i <= 8; $i++ ) {
        $wob_feature_fields[] = [
            'key'           => 'field_wob_included_' . $i,
            'label'         => 'Included — Item ' . $i,
            'name'          => 'wob_included_' . $i,
            'type'          => 'textarea',
            'rows'          => 2,
            'default_value' => $wob_included_defaults[ $i - 1 ] ?? '',
        ];
    }
    for ( $i = 1; $i <= 8; $i++ ) {
        $wob_feature_fields[] = [
            'key'           => 'field_wob_notincluded_' . $i,
            'label'         => 'Not Included — Item ' . $i,
            'name'          => 'wob_notincluded_' . $i,
            'type'          => 'textarea',
            'rows'          => 2,
            'default_value' => $wob_notincluded_defaults[ $i - 1 ] ?? '',
        ];
    }

    $wob_stat_defaults = [
        1 => [ 'number' => '16',  'label' => 'People max per boat' ],
        2 => [ 'number' => 'GAS', 'label' => 'BBQ + gas cooker'    ],
        3 => [ 'number' => '0',   'label' => 'Licence required'    ],
    ];
    foreach ( $wob_stat_defaults as $n => $stat ) {
        $wob_feature_fields[] = [
            'key'           => 'field_wob_stat_' . $n . '_number',
            'label'         => 'Stat ' . $n . ' — Number / Value',
            'name'          => 'wob_stat_' . $n . '_number',
            'type'          => 'text',
            'default_value' => $stat['number'],
        ];
        $wob_feature_fields[] = [
            'key'           => 'field_wob_stat_' . $n . '_label',
            'label'         => 'Stat ' . $n . ' — Label',
            'name'          => 'wob_stat_' . $n . '_label',
            'type'          => 'text',
            'default_value' => $stat['label'],
        ];
    }

    acf_add_local_field_group( [
        'key'        => 'group_wob_features',
        'title'      => "What's On Board — Included / Not Included & Stats",
        'fields'     => $wob_feature_fields,
        'location'   => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-whats-on-board.php' ] ] ],
        'menu_order' => 10,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // Groups — occasion tiles + inclusions list
    // --------------------------------------------------------
    $grp_occasion_defaults = [
        1 => [ 'heading' => 'Workmates', 'body' => "Team days, end-of-year lunches, and Friday arvo knockoffs. Get off-site, relax, and actually enjoy each other's company." ],
        2 => [ 'heading' => 'Mates',     'body' => "Bucks, hens, birthdays, or just a good Saturday. Grab the crew, load up the esky, and get out on the water." ],
        3 => [ 'heading' => 'Family',    'body' => "Kids love it, adults love it more. Life jackets in all sizes, easy to drive, and there's a BBQ. What more could you want." ],
        4 => [ 'heading' => 'Visitors',  'body' => "Showing people around the Copper Coast? Take them to Wallaroo and put them on the water. They'll remember it." ],
    ];
    $grp_fields = [];
    foreach ( $grp_occasion_defaults as $n => $occ ) {
        $grp_fields[] = [
            'key'           => 'field_grp_occasion_' . $n . '_heading',
            'label'         => 'Occasion ' . $n . ' — Heading',
            'name'          => 'grp_occasion_' . $n . '_heading',
            'type'          => 'text',
            'default_value' => $occ['heading'],
        ];
        $grp_fields[] = [
            'key'           => 'field_grp_occasion_' . $n . '_body',
            'label'         => 'Occasion ' . $n . ' — Body',
            'name'          => 'grp_occasion_' . $n . '_body',
            'type'          => 'textarea',
            'rows'          => 2,
            'default_value' => $occ['body'],
        ];
    }

    $grp_inclusion_defaults = [
        'Full private boat hire',
        'Gas BBQ set up and ready',
        'Plates, cutlery and cooking gear',
        'Safety briefing before departure',
        'Life jackets for all passengers',
        'Cold drinks available on board',
    ];
    for ( $i = 1; $i <= 6; $i++ ) {
        $grp_fields[] = [
            'key'           => 'field_grp_inclusion_' . $i,
            'label'         => 'Inclusion ' . $i,
            'name'          => 'grp_inclusion_' . $i,
            'type'          => 'text',
            'default_value' => $grp_inclusion_defaults[ $i - 1 ],
        ];
    }

    acf_add_local_field_group( [
        'key'        => 'group_grp_occasions',
        'title'      => 'Groups — Occasions & Inclusions',
        'fields'     => $grp_fields,
        'location'   => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-groups.php' ] ] ],
        'menu_order' => 10,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // Gift Vouchers — how-to-order steps
    // --------------------------------------------------------
    $gv_step_defaults = [
        1 => [ 'heading' => 'Choose your amount',      'body' => 'Any dollar value — you decide what works.' ],
        2 => [ 'heading' => 'We send you the voucher', 'body' => 'Digital voucher sent straight to your inbox.' ],
        3 => [ 'heading' => 'They book when ready',    'body' => 'Recipient books a session at a time that suits them.' ],
    ];
    $gv_step_fields = [];
    foreach ( $gv_step_defaults as $n => $step ) {
        $gv_step_fields[] = [
            'key'           => 'field_gv_step_' . $n . '_heading',
            'label'         => 'Step ' . $n . ' — Heading',
            'name'          => 'gv_step_' . $n . '_heading',
            'type'          => 'text',
            'default_value' => $step['heading'],
        ];
        $gv_step_fields[] = [
            'key'           => 'field_gv_step_' . $n . '_body',
            'label'         => 'Step ' . $n . ' — Body',
            'name'          => 'gv_step_' . $n . '_body',
            'type'          => 'textarea',
            'rows'          => 2,
            'default_value' => $step['body'],
        ];
    }

    acf_add_local_field_group( [
        'key'        => 'group_gv_steps',
        'title'      => 'Gift Vouchers — How to Order Steps',
        'fields'     => $gv_step_fields,
        'location'   => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-gift-vouchers.php' ] ] ],
        'menu_order' => 10,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // Page: Food & Drink
    // --------------------------------------------------------
    acf_add_local_field_group( [
        'key'    => 'group_page_food_drink',
        'title'  => 'Food & Drink — Content',
        'fields' => [
            [
                'key'           => 'field_fd_hero_headline',
                'label'         => 'Hero Headline',
                'name'          => 'fd_hero_headline',
                'type'          => 'text',
                'default_value' => 'FOOD & DRINK',
            ],
            [
                'key'           => 'field_fd_hero_subheading',
                'label'         => 'Hero Subheading',
                'name'          => 'fd_hero_subheading',
                'type'          => 'text',
                'default_value' => 'Sort the food before you get on the water. Add it to your booking or grab it on the day.',
            ],
            [
                'key'           => 'field_fd_hero_image',
                'label'         => 'Hero Background Image',
                'name'          => 'fd_hero_image',
                'type'          => 'image',
                'return_format' => 'array',
                'preview_size'  => 'medium',
                'instructions'  => 'Full-bleed hero image. Landscape, at least 1400×600px. Leave blank for a plain colour hero.',
            ],
        ],
        'location'   => [ [ [ 'param' => 'page_template', 'operator' => '==', 'value' => 'page-food-drink.php' ] ] ],
        'menu_order' => 0,
        'active'     => true,
    ] );

    // --------------------------------------------------------
    // SEO — appears on every page, editable per page
    // --------------------------------------------------------
    acf_add_local_field_group( [
        'key'    => 'group_page_seo',
        'title'  => 'SEO',
        'fields' => [
            [
                'key'          => 'field_seo_meta_description',
                'label'        => 'Meta Description',
                'name'         => 'seo_meta_description',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'Shown in Google search results under the page title. Aim for 120–155 characters. Leave blank to use the built-in default for this page.',
                'placeholder'  => 'Write a concise summary of this page for search engines...',
            ],
            [
                'key'           => 'field_seo_og_image',
                'label'         => 'Social Sharing Image',
                'name'          => 'seo_og_image',
                'type'          => 'image',
                'return_format' => 'array',
                'preview_size'  => 'medium',
                'instructions'  => 'Image shown when this page is shared on Facebook, X/Twitter, etc. Recommended: 1200×630px. Falls back to the site logo if not set.',
            ],
        ],
        'location'       => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'page' ] ] ],
        'menu_order'     => 100,
        'position'       => 'side',
        'active'         => true,
        'hide_on_screen' => [],
    ] );

} );
