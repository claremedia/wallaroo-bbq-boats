<?php
/**
 * WBB_GV_PDF — renders a branded gift voucher PDF using bundled FPDF.
 *
 * A5 landscape (210 × 148 mm). FPDF core fonts use Latin-1, so text is
 * transcoded from UTF-8. The site logo (alpha PNG) is flattened to a temp
 * JPEG via GD before embedding; falls back to a text title if that fails.
 */

defined( 'ABSPATH' ) || exit;

class WBB_GV_PDF {

	public static function stream( $v, $disposition = 'inline' ) {
		require_once WBB_GV_DIR . 'lib/fpdf.php';

		$currency = wbb_gv_setting( 'currency_symbol', '$' );

		$pdf = new FPDF( 'L', 'mm', 'A5' ); // 210 x 148 mm
		$pdf->SetAutoPageBreak( false );
		$pdf->AddPage();

		// Outer border.
		$pdf->SetDrawColor( 10, 42, 94 );
		$pdf->SetLineWidth( 1.2 );
		$pdf->Rect( 6, 6, 198, 136 );
		$pdf->SetLineWidth( 0.3 );
		$pdf->Rect( 8, 8, 194, 132 );

		// Logo (flattened) or text title.
		$logo = self::logo_image_path();
		if ( $logo ) {
			// Keep within ~46mm wide, centred horizontally.
			$pdf->Image( $logo, 82, 12, 46 );
			@unlink( $logo );
		} else {
			$pdf->SetFont( 'Helvetica', 'B', 18 );
			$pdf->SetTextColor( 10, 42, 94 );
			$pdf->SetXY( 0, 16 );
			$pdf->Cell( 210, 10, self::t( 'WALLAROO BBQ BOATS' ), 0, 1, 'C' );
		}

		// Title.
		$pdf->SetFont( 'Helvetica', 'B', 28 );
		$pdf->SetTextColor( 211, 32, 39 );
		$pdf->SetXY( 0, 44 );
		$pdf->Cell( 210, 12, self::t( 'GIFT VOUCHER' ), 0, 1, 'C' );

		// Amount.
		$pdf->SetFont( 'Helvetica', 'B', 40 );
		$pdf->SetTextColor( 10, 42, 94 );
		$pdf->SetXY( 0, 58 );
		$pdf->Cell( 210, 18, self::t( $currency . number_format( (float) $v->amount, 2 ) ), 0, 1, 'C' );

		// To / From.
		$pdf->SetFont( 'Helvetica', '', 12 );
		$pdf->SetTextColor( 60, 60, 60 );
		$pdf->SetXY( 20, 84 );
		$pdf->Cell( 0, 7, self::t( 'To:   ' . $v->recipient_name ), 0, 1 );
		$pdf->SetXY( 20, 92 );
		$pdf->Cell( 0, 7, self::t( 'From: ' . $v->purchaser_name ), 0, 1 );

		if ( ! empty( $v->recipient_message ) ) {
			$pdf->SetFont( 'Helvetica', 'I', 11 );
			$pdf->SetTextColor( 90, 90, 90 );
			$pdf->SetXY( 20, 101 );
			$pdf->MultiCell( 170, 5.5, self::t( '"' . $v->recipient_message . '"' ) );
		}

		// Code.
		$pdf->SetFont( 'Helvetica', 'B', 14 );
		$pdf->SetTextColor( 10, 42, 94 );
		$pdf->SetXY( 0, 118 );
		$pdf->Cell( 210, 7, self::t( 'Code: ' . $v->voucher_code ), 0, 1, 'C' );

		// Footer: expiry + terms.
		$pdf->SetFont( 'Helvetica', '', 8.5 );
		$pdf->SetTextColor( 120, 120, 120 );
		$expiry = ! empty( $v->expiry_date ) ? date_i18n( 'j F Y', strtotime( $v->expiry_date ) ) : '';
		$footer = ( $expiry ? 'Valid until ' . $expiry . '   ' : '' ) . 'Copper Cove Marina, Wallaroo SA';
		$pdf->SetXY( 0, 127 );
		$pdf->Cell( 210, 5, self::t( $footer ), 0, 1, 'C' );

		$terms = wbb_gv_setting( 'terms_text', '' );
		if ( $terms ) {
			$pdf->SetXY( 20, 132 );
			$pdf->MultiCell( 170, 4, self::t( $terms ), 0, 'C' );
		}

		$filename = 'gift-voucher-' . $v->voucher_code . '.pdf';
		$pdf->Output( 'download' === $disposition ? 'D' : 'I', $filename );
		exit;
	}

	/** Transcode UTF-8 → Windows-1252 for FPDF core fonts. */
	private static function t( $s ) {
		$s = (string) $s;
		if ( function_exists( 'iconv' ) ) {
			$out = @iconv( 'UTF-8', 'Windows-1252//TRANSLIT', $s );
			if ( false !== $out ) {
				return $out;
			}
		}
		if ( function_exists( 'mb_convert_encoding' ) ) {
			return mb_convert_encoding( $s, 'Windows-1252', 'UTF-8' );
		}
		return $s;
	}

	/**
	 * Flatten the site logo (alpha PNG) onto white → temp JPEG path FPDF can embed.
	 * Returns '' if no logo or GD unavailable (caller falls back to a text title).
	 */
	private static function logo_image_path() {
		$logo_id = (int) get_option( 'wallaroo_logo_id', 0 );
		if ( ! $logo_id ) {
			return '';
		}
		$file = get_attached_file( $logo_id );
		if ( ! $file || ! file_exists( $file ) ) {
			return '';
		}
		$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );

		if ( in_array( $ext, array( 'jpg', 'jpeg' ), true ) ) {
			return $file; // already safe for FPDF
		}
		if ( 'png' !== $ext || ! function_exists( 'imagecreatefrompng' ) ) {
			return '';
		}

		$src = @imagecreatefrompng( $file );
		if ( ! $src ) {
			return '';
		}
		$sw = imagesx( $src );
		$sh = imagesy( $src );

		// Downscale to a sensible print width so the embedded JPEG stays small.
		$max = 600;
		if ( $sw > $max ) {
			$dw = $max;
			$dh = (int) round( $sh * ( $max / $sw ) );
		} else {
			$dw = $sw;
			$dh = $sh;
		}

		$bg    = imagecreatetruecolor( $dw, $dh );
		$white = imagecolorallocate( $bg, 255, 255, 255 );
		imagefilledrectangle( $bg, 0, 0, $dw, $dh, $white );
		imagecopyresampled( $bg, $src, 0, 0, 0, 0, $dw, $dh, $sw, $sh );

		$tmp = wp_tempnam( 'wbb-gv-logo' ) . '.jpg';
		$ok  = imagejpeg( $bg, $tmp, 88 );
		imagedestroy( $src );
		imagedestroy( $bg );

		return $ok ? $tmp : '';
	}
}
