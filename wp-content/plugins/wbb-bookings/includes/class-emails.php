<?php
/**
 * WBB_Emails — sends all plugin email notifications.
 *
 * From/From-Name filters are scoped: added before each send, removed after,
 * so they never bleed into other WordPress emails.
 */

defined( 'ABSPATH' ) || exit;

class WBB_Emails {

	// ── Merge-tag processing ───────────────────────────────────────────────
	public function process_merge_tags( $template, $booking ) {
		// Site contact details — pull from theme function if available.
		$site_phone = function_exists( 'wallaroo_option' )
			? wallaroo_option( 'phone' )
			: get_bloginfo( 'admin_email' );
		$site_email = function_exists( 'wallaroo_option' )
			? wallaroo_option( 'email' )
			: get_bloginfo( 'admin_email' );

		$date_display     = ! empty( $booking->booking_date )
			? date_i18n( 'l, j F Y', strtotime( $booking->booking_date ) )
			: '';
		$duration_display = (float) $booking->duration_hours;

		$tags = array(
			'{customer_name}'   => $booking->customer_name ?? '',
			'{booking_ref}'     => $booking->booking_ref ?? '',
			'{date}'            => $date_display,
			'{time}'            => $booking->time_slot ?? '',
			'{duration}'        => $duration_display,
			'{group_size}'      => $booking->group_size ?? '',
			'{boats}'           => $booking->boats_requested ?? '',
			'{customer_email}'  => $booking->customer_email ?? '',
			'{customer_phone}'  => $booking->customer_phone ?? '',
			'{notes}'           => $booking->notes ?? '',
			'{site_phone}'      => $site_phone,
			'{site_email}'      => $site_email,
		);

		return str_replace( array_keys( $tags ), array_values( $tags ), $template );
	}

	// ── Customer: request received ─────────────────────────────────────────
	public function send_customer_request_received( $booking ) {
		if ( ! wbb_setting( 'email_customer_on_request', '1' ) ) {
			return;
		}
		$template = wbb_setting( 'template_customer_request' );
		$body     = $this->process_merge_tags( $template, $booking );
		$subject  = sprintf(
			__( 'Booking request received — %s', 'wbb-bookings' ),
			$booking->booking_ref
		);
		$this->send_email( $booking->customer_email, $subject, $body );
	}

	// ── Admin: new booking notification ───────────────────────────────────
	public function send_admin_notification( $booking ) {
		if ( ! wbb_setting( 'email_admin_on_request', '1' ) ) {
			return;
		}
		$template = wbb_setting( 'template_admin_notification' );
		$body     = $this->process_merge_tags( $template, $booking );
		$subject  = sprintf(
			__( 'New booking request — %s', 'wbb-bookings' ),
			$booking->booking_ref
		);
		$to = wbb_setting( 'admin_notification_email', get_bloginfo( 'admin_email' ) );
		$this->send_email( $to, $subject, $body );
	}

	// ── Customer: booking confirmed ────────────────────────────────────────
	public function send_booking_confirmed( $booking ) {
		if ( ! wbb_setting( 'email_customer_on_confirm', '1' ) ) {
			return;
		}
		$template = wbb_setting( 'template_confirmed' );
		$body     = $this->process_merge_tags( $template, $booking );
		$subject  = sprintf(
			__( 'Your booking is confirmed — %s', 'wbb-bookings' ),
			$booking->booking_ref
		);
		$this->send_email( $booking->customer_email, $subject, $body );
	}

	// ── Customer: booking cancelled ────────────────────────────────────────
	public function send_booking_cancelled( $booking ) {
		if ( ! wbb_setting( 'email_customer_on_cancel', '1' ) ) {
			return;
		}
		$template = wbb_setting( 'template_cancelled' );
		$body     = $this->process_merge_tags( $template, $booking );
		$subject  = sprintf(
			__( 'Your booking has been cancelled — %s', 'wbb-bookings' ),
			$booking->booking_ref
		);
		$this->send_email( $booking->customer_email, $subject, $body );
	}

	// ── Internal: send email with scoped from filters ──────────────────────
	private function send_email( $to, $subject, $message ) {
		if ( empty( $to ) ) {
			return;
		}

		$from_name  = wbb_setting( 'from_name',  'Wallaroo BBQ Boats' );
		$from_email = wbb_setting( 'from_email',  get_bloginfo( 'admin_email' ) );
		$reply_to   = wbb_setting( 'reply_to_email', $from_email );

		// Scope the From filters to this send only.
		$name_filter  = function () use ( $from_name )  { return $from_name; };
		$email_filter = function () use ( $from_email ) { return $from_email; };

		add_filter( 'wp_mail_from_name', $name_filter,  99 );
		add_filter( 'wp_mail_from',      $email_filter, 99 );

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: ' . sanitize_email( $reply_to ),
		);

		wp_mail( sanitize_email( $to ), $subject, $message, $headers );

		remove_filter( 'wp_mail_from_name', $name_filter,  99 );
		remove_filter( 'wp_mail_from',      $email_filter, 99 );
	}
}
