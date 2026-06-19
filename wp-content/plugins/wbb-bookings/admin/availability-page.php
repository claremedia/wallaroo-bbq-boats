<?php
/**
 * Renders the WBB Availability admin page.
 *
 * Sections:
 *   1. Fleet Management  -- add/edit/delete boats
 *   2. Weekly Schedule   -- per-boat, per-day-of-week session grid
 *   3. Availability Calendar -- per-boat tabbed month calendar with
 *      inline block/unblock on each time slot
 */

defined( 'ABSPATH' ) || exit;

function wbb_render_availability_page() {
	if ( ! current_user_can( 'wbb_manage' ) ) {
		return;
	}
	?>
	<div class="wrap wbb-av-wrap">

		<h1 class="wbb-page-title">
			<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
			<?php esc_html_e( 'Availability', 'wbb-bookings' ); ?>
		</h1>

		<!-- 1. Fleet Management -->
		<div class="wbb-card" id="wbb-fleet-card">
			<h2><?php esc_html_e( 'Fleet Management', 'wbb-bookings' ); ?></h2>
			<p style="color:#646970;font-size:13px;margin-top:0;">
				<?php esc_html_e( 'Define the boats that make up your fleet. Each boat can have its own weekly schedule.', 'wbb-bookings' ); ?>
			</p>

			<div id="wbb-fleet-wrap">
				<p class="wbb-loading-msg"><?php esc_html_e( 'Loading fleet&hellip;', 'wbb-bookings' ); ?></p>
			</div>

			<!-- Inline add/edit form (hidden by default) -->
			<div id="wbb-boat-form-wrap" class="wbb-hidden" style="margin-top:12px;padding:14px 16px;background:#f8f9fa;border:1px solid #e5e7eb;border-radius:4px;max-width:520px;">
				<strong id="wbb-boat-form-title" style="display:block;margin-bottom:12px;"><?php esc_html_e( 'Add Boat', 'wbb-bookings' ); ?></strong>
				<input type="hidden" id="wbb-boat-id" value="0">
				<div class="wbb-row wbb-row--gap" style="flex-wrap:nowrap;align-items:flex-end;">
					<div class="wbb-col" style="flex:1;">
						<label class="wbb-form-label" for="wbb-boat-name"><?php esc_html_e( 'Boat name', 'wbb-bookings' ); ?></label>
						<input type="text" id="wbb-boat-name" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Boat 1', 'wbb-bookings' ); ?>">
					</div>
					<div class="wbb-col">
						<label class="wbb-form-label" for="wbb-boat-sort"><?php esc_html_e( 'Sort', 'wbb-bookings' ); ?></label>
						<input type="number" id="wbb-boat-sort" min="0" value="0" style="width:60px;">
					</div>
					<div class="wbb-col" style="justify-content:flex-end;padding-bottom:2px;">
						<label>
							<input type="checkbox" id="wbb-boat-active" checked>
							<?php esc_html_e( 'Active', 'wbb-bookings' ); ?>
						</label>
					</div>
				</div>
				<div style="margin-top:10px;">
					<button type="button" class="button button-primary" id="wbb-save-boat"><?php esc_html_e( 'Save Boat', 'wbb-bookings' ); ?></button>
					<button type="button" class="button" id="wbb-cancel-boat" style="margin-left:6px;"><?php esc_html_e( 'Cancel', 'wbb-bookings' ); ?></button>
					<span class="wbb-status-msg" id="wbb-boat-status"></span>
				</div>
			</div>

			<div style="margin-top:12px;">
				<button type="button" class="button" id="wbb-add-boat">+ <?php esc_html_e( 'Add Boat', 'wbb-bookings' ); ?></button>
			</div>
		</div>

		<!-- 2. Weekly Schedule -->
		<div class="wbb-card" id="wbb-schedule-card">
			<h2><?php esc_html_e( 'Weekly Schedule', 'wbb-bookings' ); ?></h2>
			<p style="color:#646970;font-size:13px;margin-top:0;">
				<?php esc_html_e( 'Set recurring sessions for each boat by day of week. Sessions automatically appear as availability on the booking form during the operating season.', 'wbb-bookings' ); ?>
			</p>

			<div style="margin-bottom:16px;">
				<label class="wbb-form-label" for="wbb-schedule-boat-select"><?php esc_html_e( 'Select boat', 'wbb-bookings' ); ?></label>
				<select id="wbb-schedule-boat-select">
					<option value=""><?php esc_html_e( '&mdash; Choose a boat &mdash;', 'wbb-bookings' ); ?></option>
				</select>
			</div>

			<div id="wbb-schedule-grid-wrap">
				<p style="color:#646970;font-size:13px;"><?php esc_html_e( 'Select a boat above to view and edit its weekly schedule.', 'wbb-bookings' ); ?></p>
			</div>
		</div>

		<!-- 3. Availability Calendar (per-boat tabs) -->
		<div class="wbb-card" id="wbb-calendar-card">
			<h2><?php esc_html_e( 'Availability Calendar', 'wbb-bookings' ); ?></h2>
			<p style="color:#646970;font-size:13px;margin-top:0;">
				<?php esc_html_e( 'View each boat\'s sessions by month. Click Block or Unblock on any session to override the weekly schedule for that date.', 'wbb-bookings' ); ?>
			</p>

			<!-- Boat tabs (populated by JS once fleet loads) -->
			<div id="wbb-boat-tabs-nav" class="wbb-boat-tabs-nav">
				<p class="wbb-loading-msg"><?php esc_html_e( 'Loading boats&hellip;', 'wbb-bookings' ); ?></p>
			</div>

			<!-- Month navigation (hidden until a tab is active) -->
			<div id="wbb-boat-cal-nav" class="wbb-cal-header wbb-hidden">
				<button type="button" class="wbb-cal-nav" id="wbb-bcal-prev" aria-label="<?php esc_attr_e( 'Previous month', 'wbb-bookings' ); ?>">&laquo;</button>
				<h3 class="wbb-cal-title" id="wbb-bcal-title"><?php esc_html_e( 'Loading&hellip;', 'wbb-bookings' ); ?></h3>
				<button type="button" class="wbb-cal-nav" id="wbb-bcal-next" aria-label="<?php esc_attr_e( 'Next month', 'wbb-bookings' ); ?>">&raquo;</button>
			</div>

			<!-- Calendar grid (populated by JS) -->
			<div id="wbb-boat-calendar-grid"></div>

		</div>

	</div><!-- /.wrap -->
	<?php
}
