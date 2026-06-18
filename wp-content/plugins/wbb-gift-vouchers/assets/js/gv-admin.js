/* WBB Gift Vouchers — admin (jQuery) */
(function ($) {
	'use strict';
	var cfg = window.wbbGVAdmin || {};
	var ajaxUrl = cfg.ajaxUrl || ajaxurl; // eslint-disable-line no-undef
	var nonce = cfg.nonce || '';
	var str = cfg.strings || {};

	function esc(s) {
		return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

	function updateStatus(id, status) {
		$.post(ajaxUrl, { action: 'wbb_gv_admin_update_status', nonce: nonce, voucher_id: id, status: status }, function (res) {
			if (res.success) { window.location.reload(); }
			else { alert((res.data && res.data.message) || str.error); }
		});
	}

	function renderDetail($panel, v) {
		var cur = str.currency || '$';
		var rows = [
			['Code', v.voucher_code],
			['Status', v.status ? v.status.charAt(0).toUpperCase() + v.status.slice(1) : ''],
			['Amount', cur + parseFloat(v.amount || 0).toFixed(2)],
			['Balance', cur + parseFloat(v.balance || 0).toFixed(2)],
			['Purchaser', v.purchaser_name],
			['Purchaser email', v.purchaser_email],
			['Purchaser phone', v.purchaser_phone || '—'],
			['Recipient', v.recipient_name],
			['Recipient email', v.recipient_email || '—'],
			['Message', v.recipient_message || '—'],
			['Expiry', v.expiry_date || '—'],
			['Created', v.created_at]
		];
		var grid = '<dl class="wbb-gv-detail-grid">';
		rows.forEach(function (r) { grid += '<dt>' + esc(r[0]) + '</dt><dd>' + esc(r[1]) + '</dd>'; });
		grid += '</dl>';

		var pdf = v.pdf_url ? '<a class="button" href="' + esc(v.pdf_url) + '" target="_blank" rel="noopener">Download PDF</a>' : '';

		var notes = '<div class="wbb-gv-staff">'
			+ '<label style="font-weight:600;display:block;margin-bottom:4px;">Staff notes</label>'
			+ '<textarea class="wbb-gv-notes" rows="3" data-id="' + esc(v.id) + '">' + esc(v.staff_notes || '') + '</textarea>'
			+ '<span class="wbb-gv-status-msg"></span></div>';

		$panel.html(grid + pdf + notes);
	}

	$(function () {
		if (!$('.wbb-gv-adminwrap').length) return;

		$(document).on('click', '.wbb-gv-view', function (e) {
			e.preventDefault();
			var id = $(this).data('id');
			var $row = $('#wbb-gv-detail-' + id);
			if (!$row.hasClass('wbb-gv-hidden')) { $row.addClass('wbb-gv-hidden'); return; }
			$('.wbb-gv-detailrow').not($row).addClass('wbb-gv-hidden');
			$row.removeClass('wbb-gv-hidden');
			var $panel = $row.find('.wbb-gv-detail');
			$panel.html('<em>Loading…</em>');
			$.post(ajaxUrl, { action: 'wbb_gv_admin_get_voucher', nonce: nonce, voucher_id: id }, function (res) {
				if (res.success) { renderDetail($panel, res.data); }
				else { $panel.html('<span class="wbb-gv-status-msg is-error">Failed to load.</span>'); }
			});
		});

		$(document).on('click', '.wbb-gv-issue', function (e) {
			e.preventDefault();
			if (!window.confirm(str.confirmIssue || 'Mark issued?')) return;
			updateStatus($(this).data('id'), 'issued');
		});

		$(document).on('click', '.wbb-gv-cancel', function (e) {
			e.preventDefault();
			if (!window.confirm(str.confirmCancel || 'Cancel?')) return;
			updateStatus($(this).data('id'), 'cancelled');
		});

		$(document).on('blur', '.wbb-gv-notes', function () {
			var $el = $(this), id = $el.data('id'), $msg = $el.siblings('.wbb-gv-status-msg');
			$msg.text(str.saving || 'Saving…').removeClass('is-error');
			$.post(ajaxUrl, { action: 'wbb_gv_admin_save_notes', nonce: nonce, voucher_id: id, staff_notes: $el.val() }, function (res) {
				$msg.text(res.success ? (str.saved || 'Saved.') : (str.error || 'Error'));
				if (!res.success) $msg.addClass('is-error');
				setTimeout(function () { $msg.text(''); }, 3000);
			});
		});
	});
}(jQuery));
