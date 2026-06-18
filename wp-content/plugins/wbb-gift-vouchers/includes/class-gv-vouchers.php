<?php
/**
 * WBB_GV_Vouchers — front-end submit, admin actions, PDF + CSV endpoints.
 */

defined( 'ABSPATH' ) || exit;

class WBB_GV_Vouchers {

	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'wbb_gift_vouchers';
	}

	// ── AJAX registration ──────────────────────────────────────────────────
	public static function register_ajax() {
		add_action( 'wp_ajax_nopriv_wbb_gv_submit', array( __CLASS__, 'ajax_submit' ) );
		add_action( 'wp_ajax_wbb_gv_submit',        array( __CLASS__, 'ajax_submit' ) );

		add_action( 'wp_ajax_wbb_gv_admin_update_status', array( __CLASS__, 'ajax_update_status' ) );
		add_action( 'wp_ajax_wbb_gv_admin_save_notes',    array( __CLASS__, 'ajax_save_notes' ) );
		add_action( 'wp_ajax_wbb_gv_admin_get_voucher',   array( __CLASS__, 'ajax_get_voucher' ) );
	}

	// ── Front-end: create a voucher ────────────────────────────────────────
	public static function ajax_submit() {
		check_ajax_referer( 'wbb_gv_nonce', 'nonce' );

		if ( ! self::check_rate_limit() ) {
			wp_send_json_error( array(
				'message' => __( 'Too many requests from your connection. Please try again later or contact us.', 'wbb-gift-vouchers' ),
			) );
		}

		$amount  = isset( $_POST['amount'] ) ? round( (float) $_POST['amount'], 2 ) : 0.0;
		$p_name  = sanitize_text_field( $_POST['purchaser_name']  ?? '' );
		$p_email = sanitize_email( $_POST['purchaser_email'] ?? '' );
		$p_phone = sanitize_text_field( $_POST['purchaser_phone'] ?? '' );
		$r_name  = sanitize_text_field( $_POST['recipient_name']  ?? '' );
		$r_email = sanitize_email( $_POST['recipient_email'] ?? '' );
		$r_msg   = sanitize_textarea_field( $_POST['recipient_message'] ?? '' );

		$min = (float) wbb_gv_setting( 'min_amount', 25 );

		if ( ! $p_name || ! $p_email || ! $r_name ) {
			wp_send_json_error( array( 'code' => 'validation', 'message' => __( 'Please fill in all required fields.', 'wbb-gift-vouchers' ) ) );
		}
		if ( ! is_email( $p_email ) ) {
			wp_send_json_error( array( 'code' => 'validation', 'message' => __( 'Please enter a valid email address.', 'wbb-gift-vouchers' ) ) );
		}
		if ( $r_email && ! is_email( $r_email ) ) {
			wp_send_json_error( array( 'code' => 'validation', 'message' => __( 'Please enter a valid recipient email address.', 'wbb-gift-vouchers' ) ) );
		}
		if ( $amount < $min ) {
			wp_send_json_error( array(
				'code'    => 'amount',
				'message' => sprintf(
					/* translators: %s formatted minimum amount */
					__( 'The minimum voucher amount is %s.', 'wbb-gift-vouchers' ),
					wbb_gv_setting( 'currency_symbol', '$' ) . number_format( $min, 2 )
				),
			) );
		}

		$code   = self::generate_code();
		$token  = wp_generate_password( 32, false, false );
		$months = (int) wbb_gv_setting( 'expiry_months', 36 );
		$expiry = $months > 0 ? date( 'Y-m-d', strtotime( "+{$months} months" ) ) : null;
		$now    = current_time( 'mysql' );

		global $wpdb;
		$inserted = $wpdb->insert(
			self::table(),
			array(
				'voucher_code'      => $code,
				'amount'            => $amount,
				'balance'           => $amount,
				'purchaser_name'    => $p_name,
				'purchaser_email'   => $p_email,
				'purchaser_phone'   => $p_phone,
				'recipient_name'    => $r_name,
				'recipient_email'   => $r_email,
				'recipient_message' => $r_msg,
				'status'            => 'pending',
				'download_token'    => $token,
				'expiry_date'       => $expiry,
				'created_at'        => $now,
				'updated_at'        => $now,
			),
			array( '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			wp_send_json_error( array( 'message' => __( 'Something went wrong. Please try again.', 'wbb-gift-vouchers' ) ) );
		}

		$id      = $wpdb->insert_id;
		$voucher = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );

		if ( wbb_gv_setting( 'email_admin_on_create', '1' ) ) {
			self::notify_admin( $voucher );
		}

		$pdf_url = add_query_arg(
			array( 'action' => 'wbb_gv_pdf', 'id' => $id, 'token' => $token ),
			admin_url( 'admin-post.php' )
		);

		wp_send_json_success( array(
			'voucher_code' => $code,
			'pdf_url'      => $pdf_url,
			'message'      => self::merge( $voucher, wbb_gv_setting( 'success_message' ) ),
		) );
	}

	// ── Public PDF (success-screen download; token-gated) ──────────────────
	public static function public_pdf() {
		$id    = absint( $_GET['id'] ?? 0 );
		$token = sanitize_text_field( $_GET['token'] ?? '' );
		global $wpdb;
		$v = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
		if ( ! $v || ! $token || ! hash_equals( (string) $v->download_token, $token ) ) {
			wp_die( esc_html__( 'Invalid or expired voucher link.', 'wbb-gift-vouchers' ) );
		}
		WBB_GV_PDF::stream( $v, 'inline' );
	}

	// ── Admin PDF (capability-gated) ───────────────────────────────────────
	public static function admin_pdf() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorised' );
		}
		check_admin_referer( 'wbb_gv_admin_pdf' );
		$id = absint( $_GET['id'] ?? 0 );
		global $wpdb;
		$v = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
		if ( ! $v ) {
			wp_die( 'Not found' );
		}
		WBB_GV_PDF::stream( $v, 'inline' );
	}

	// ── Admin: update status ───────────────────────────────────────────────
	public static function ajax_update_status() {
		check_ajax_referer( 'wbb_gv_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorised.' ) );
		}
		$id     = absint( $_POST['voucher_id'] ?? 0 );
		$status = sanitize_text_field( $_POST['status'] ?? '' );
		if ( ! $id || ! in_array( $status, array( 'pending', 'issued', 'redeemed', 'cancelled' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid request.' ) );
		}
		global $wpdb;
		$wpdb->update( self::table(), array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id ), array( '%s', '%s' ), array( '%d' ) );
		wp_send_json_success( array( 'status' => $status ) );
	}

	// ── Admin: save staff notes ────────────────────────────────────────────
	public static function ajax_save_notes() {
		check_ajax_referer( 'wbb_gv_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorised.' ) );
		}
		$id    = absint( $_POST['voucher_id'] ?? 0 );
		$notes = sanitize_textarea_field( $_POST['staff_notes'] ?? '' );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'Invalid voucher ID.' ) );
		}
		global $wpdb;
		$wpdb->update( self::table(), array( 'staff_notes' => $notes, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id ), array( '%s', '%s' ), array( '%d' ) );
		wp_send_json_success( array( 'message' => 'Notes saved.' ) );
	}

	// ── Admin: get single voucher ──────────────────────────────────────────
	public static function ajax_get_voucher() {
		check_ajax_referer( 'wbb_gv_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorised.' ) );
		}
		$id = absint( $_POST['voucher_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'Invalid voucher ID.' ) );
		}
		global $wpdb;
		$v = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ), ARRAY_A );
		if ( ! $v ) {
			wp_send_json_error( array( 'message' => 'Not found.' ) );
		}
		// Build raw (un-escaped) so the JS can safely escape it once into the href.
		// wp_nonce_url() HTML-escapes the URL, which would double-encode here.
		$v['pdf_url'] = add_query_arg(
			array(
				'action'   => 'wbb_gv_admin_pdf',
				'id'       => $id,
				'_wpnonce' => wp_create_nonce( 'wbb_gv_admin_pdf' ),
			),
			admin_url( 'admin-post.php' )
		);
		wp_send_json_success( $v );
	}

	// ── CSV export ─────────────────────────────────────────────────────────
	public static function export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorised' );
		}
		check_admin_referer( 'wbb_gv_export' );

		global $wpdb;
		$where  = '1=1';
		$params = array();

		$status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
		if ( $status && in_array( $status, array( 'pending', 'issued', 'redeemed', 'cancelled' ), true ) ) {
			$where   .= ' AND status = %s';
			$params[] = $status;
		}
		$search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
		if ( $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND (purchaser_name LIKE %s OR purchaser_email LIKE %s OR recipient_name LIKE %s OR voucher_code LIKE %s)';
			$params   = array_merge( $params, array( $like, $like, $like, $like ) );
		}

		$sql = 'SELECT * FROM ' . self::table() . " WHERE {$where} ORDER BY created_at DESC"; // phpcs:ignore
		$rows = $params ? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=wbb-gift-vouchers-' . date( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'Code', 'Status', 'Amount', 'Balance', 'Purchaser', 'Purchaser Email', 'Purchaser Phone', 'Recipient', 'Recipient Email', 'Message', 'Expiry', 'Created', 'Staff Notes' ) );
		foreach ( (array) $rows as $r ) {
			fputcsv( $out, array(
				$r['voucher_code'], $r['status'], $r['amount'], $r['balance'],
				$r['purchaser_name'], $r['purchaser_email'], $r['purchaser_phone'],
				$r['recipient_name'], $r['recipient_email'], $r['recipient_message'],
				$r['expiry_date'], $r['created_at'], $r['staff_notes'],
			) );
		}
		fclose( $out );
		exit;
	}

	// ── Helpers ─────────────────────────────────────────────────────────────
	public static function merge( $v, $template ) {
		$currency = wbb_gv_setting( 'currency_symbol', '$' );
		$tags = array(
			'{purchaser_name}' => $v->purchaser_name ?? '',
			'{recipient_name}' => $v->recipient_name ?? '',
			'{voucher_code}'   => $v->voucher_code ?? '',
			'{amount}'         => $currency . number_format( (float) ( $v->amount ?? 0 ), 2 ),
			'{expiry}'         => ! empty( $v->expiry_date ) ? date_i18n( 'j F Y', strtotime( $v->expiry_date ) ) : '',
		);
		return str_replace( array_keys( $tags ), array_values( $tags ), (string) $template );
	}

	private static function generate_code() {
		global $wpdb;
		$prefix = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', wbb_gv_setting( 'code_prefix', 'WBB' ) ) );
		do {
			$rand = strtoupper( wp_generate_password( 8, false, false ) );
			$code = $prefix . '-' . substr( $rand, 0, 4 ) . '-' . substr( $rand, 4, 4 );
			$exists = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE voucher_code = %s', $code ) );
		} while ( $exists );
		return $code;
	}

	private static function check_rate_limit() {
		$ip    = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
		$key   = 'wbb_gv_rate_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= 5 ) {
			return false;
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}

	private static function notify_admin( $voucher ) {
		$to = wbb_gv_setting( 'admin_notification_email', get_bloginfo( 'admin_email' ) );
		if ( ! $to ) {
			return;
		}
		$currency = wbb_gv_setting( 'currency_symbol', '$' );
		$subject  = sprintf( __( 'New gift voucher created — %s', 'wbb-gift-vouchers' ), $voucher->voucher_code );
		$body     = sprintf(
			"A new gift voucher has been created.\n\nCode: %s\nAmount: %s\nStatus: %s (payment handled separately)\n\nPurchaser: %s\nEmail: %s\nPhone: %s\n\nRecipient: %s\nRecipient email: %s\nMessage: %s\n\nExpires: %s\n",
			$voucher->voucher_code,
			$currency . number_format( (float) $voucher->amount, 2 ),
			$voucher->status,
			$voucher->purchaser_name,
			$voucher->purchaser_email,
			$voucher->purchaser_phone,
			$voucher->recipient_name,
			$voucher->recipient_email,
			$voucher->recipient_message,
			$voucher->expiry_date
		);

		$from_name  = wbb_gv_setting( 'from_name', 'Wallaroo BBQ Boats' );
		$from_email = wbb_gv_setting( 'from_email', get_bloginfo( 'admin_email' ) );
		$nf = function () use ( $from_name ) { return $from_name; };
		$ef = function () use ( $from_email ) { return $from_email; };
		add_filter( 'wp_mail_from_name', $nf, 99 );
		add_filter( 'wp_mail_from', $ef, 99 );
		wp_mail( sanitize_email( $to ), $subject, $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );
		remove_filter( 'wp_mail_from_name', $nf, 99 );
		remove_filter( 'wp_mail_from', $ef, 99 );
	}
}
