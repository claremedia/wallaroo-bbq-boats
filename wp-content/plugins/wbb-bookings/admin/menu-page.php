<?php
/**
 * Renders the WBB Food & Drink admin page.
 *
 * Tabbed by category (Food / Drinks / Platters). Each tab lists that category's
 * items as drag-to-reorder rows, each inline-editable (title, description, price,
 * image). All CRUD is AJAX (see WBB_Menu + assets/js/admin.js).
 */

defined( 'ABSPATH' ) || exit;

function wbb_render_menu_page() {
	if ( ! current_user_can( 'wbb_manage' ) ) {
		return;
	}

	$categories = WBB_Menu::CATEGORIES;
	?>
	<div class="wrap wbb-menu-wrap">

		<h1 class="wbb-page-title">
			<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
			<?php esc_html_e( 'Food &amp; Drink', 'wbb-bookings' ); ?>
		</h1>
		<p style="color:#646970;font-size:13px;max-width:760px;">
			<?php esc_html_e( 'Manage the items shown on the Food & Drink page and offered as extras during booking. Drag rows to reorder. Items live under one of three categories.', 'wbb-bookings' ); ?>
		</p>

		<div class="wbb-card">

			<!-- Category tabs -->
			<nav class="wbb-menu-tabs" id="wbb-menu-tabs">
				<?php foreach ( $categories as $i => $cat ) : ?>
				<button type="button"
					class="wbb-menu-tab<?php echo 0 === $i ? ' wbb-menu-tab--active' : ''; ?>"
					data-category="<?php echo esc_attr( $cat ); ?>">
					<?php echo esc_html( WBB_Menu::category_label( $cat ) ); ?>
				</button>
				<?php endforeach; ?>
			</nav>

			<!-- Items for the active category (rendered by JS) -->
			<div id="wbb-menu-items-wrap">
				<p class="wbb-loading-msg"><?php esc_html_e( 'Loading items&hellip;', 'wbb-bookings' ); ?></p>
			</div>

			<div style="margin-top:14px;">
				<button type="button" class="button" id="wbb-add-item">+ <?php esc_html_e( 'Add Item', 'wbb-bookings' ); ?></button>
			</div>

			<!-- Inline add/edit form (hidden by default) -->
			<div id="wbb-item-form-wrap" class="wbb-hidden" style="margin-top:16px;padding:16px 18px;background:#f8f9fa;border:1px solid #e5e7eb;border-radius:4px;max-width:620px;">
				<strong id="wbb-item-form-title" style="display:block;margin-bottom:12px;"><?php esc_html_e( 'Add Item', 'wbb-bookings' ); ?></strong>

				<input type="hidden" id="wbb-item-id" value="0">
				<input type="hidden" id="wbb-item-category" value="food">
				<input type="hidden" id="wbb-item-image-id" value="0">

				<div style="margin-bottom:12px;">
					<label class="wbb-form-label" for="wbb-item-title"><?php esc_html_e( 'Title', 'wbb-bookings' ); ?></label>
					<input type="text" id="wbb-item-title" class="regular-text" style="width:100%;max-width:100%;" placeholder="<?php esc_attr_e( 'e.g. Grazing platter', 'wbb-bookings' ); ?>">
				</div>

				<div style="margin-bottom:12px;">
					<label class="wbb-form-label" for="wbb-item-description"><?php esc_html_e( 'Description', 'wbb-bookings' ); ?></label>
					<textarea id="wbb-item-description" class="large-text" rows="3" placeholder="<?php esc_attr_e( 'Short description shown on the menu.', 'wbb-bookings' ); ?>"></textarea>
				</div>

				<div class="wbb-row wbb-row--gap" style="align-items:flex-end;gap:24px;">
					<div>
						<label class="wbb-form-label" for="wbb-item-price"><?php esc_html_e( 'Price', 'wbb-bookings' ); ?></label>
						<input type="number" id="wbb-item-price" min="0" step="0.01" value="0" style="width:120px;">
					</div>
					<div style="padding-bottom:2px;">
						<label>
							<input type="checkbox" id="wbb-item-active" checked>
							<?php esc_html_e( 'Active (shown on site)', 'wbb-bookings' ); ?>
						</label>
					</div>
				</div>

				<div style="margin-top:14px;">
					<label class="wbb-form-label" style="display:block;margin-bottom:6px;"><?php esc_html_e( 'Image', 'wbb-bookings' ); ?></label>
					<div id="wbb-item-image-preview" style="margin-bottom:8px;display:none;">
						<img src="" alt="" style="max-height:90px;max-width:160px;display:block;border:1px solid #ddd;border-radius:4px;padding:3px;background:#fff;">
					</div>
					<button type="button" class="button" id="wbb-item-image-select"><?php esc_html_e( 'Select image', 'wbb-bookings' ); ?></button>
					<button type="button" class="button-link-delete" id="wbb-item-image-remove" style="margin-left:8px;display:none;"><?php esc_html_e( 'Remove', 'wbb-bookings' ); ?></button>
				</div>

				<div style="margin-top:16px;">
					<button type="button" class="button button-primary" id="wbb-save-item"><?php esc_html_e( 'Save Item', 'wbb-bookings' ); ?></button>
					<button type="button" class="button" id="wbb-cancel-item" style="margin-left:6px;"><?php esc_html_e( 'Cancel', 'wbb-bookings' ); ?></button>
					<span class="wbb-status-msg" id="wbb-item-status"></span>
				</div>
			</div>

		</div><!-- /.wbb-card -->

	</div><!-- /.wrap -->
	<?php
}
