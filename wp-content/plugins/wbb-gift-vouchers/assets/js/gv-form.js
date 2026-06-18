/**
 * WBB Gift Vouchers — front-end form (vanilla JS).
 */
(function () {
	'use strict';

	var cfg     = window.wbbGV || {};
	var strings = cfg.strings || {};
	var minAmt  = parseFloat(cfg.minAmount) || 0;

	var wrap, form;

	function qs(s, c) { return (c || document).querySelector(s); }

	function setError(field, msg) {
		var el = wrap.querySelector('[data-error-for="' + field + '"]');
		if (el) el.textContent = msg || '';
	}

	function isEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }

	function val(id) { var el = qs(id); return el ? el.value.trim() : ''; }

	function validate(showAll) {
		var ok = true;

		var amount = parseFloat(val('#wbb-gv-amount'));
		if (isNaN(amount) || amount < minAmt) {
			if (showAll) setError('amount', strings.amountMin || 'Amount too low.');
			ok = false;
		} else { setError('amount', ''); }

		if (!val('#wbb-gv-pname')) {
			if (showAll) setError('pname', strings.nameRequired || 'Required.');
			ok = false;
		} else { setError('pname', ''); }

		var pemail = val('#wbb-gv-pemail');
		if (!pemail || !isEmail(pemail)) {
			if (showAll) setError('pemail', strings.emailInvalid || 'Invalid email.');
			ok = false;
		} else { setError('pemail', ''); }

		if (!val('#wbb-gv-rname')) {
			if (showAll) setError('rname', strings.rnameRequired || 'Required.');
			ok = false;
		} else { setError('rname', ''); }

		var remail = val('#wbb-gv-remail');
		if (remail && !isEmail(remail)) {
			if (showAll) setError('remail', strings.remailInvalid || 'Invalid email.');
			ok = false;
		} else { setError('remail', ''); }

		return ok;
	}

	function refreshSubmitState() {
		var confirmed = qs('#wbb-gv-confirm') && qs('#wbb-gv-confirm').checked;
		var btn = qs('.wbb-gv-submit');
		if (btn) btn.disabled = !(confirmed && validate(false));
	}

	function submit(e) {
		e.preventDefault();
		if (!validate(true)) { refreshSubmitState(); return; }

		var btn = qs('.wbb-gv-submit');
		var errEl = qs('.wbb-gv-submit-error');
		if (errEl) errEl.textContent = '';
		if (btn) { btn.disabled = true; btn.textContent = strings.submitting || 'Creating…'; }

		var params = new URLSearchParams();
		params.append('action', 'wbb_gv_submit');
		params.append('nonce', cfg.nonce || '');
		params.append('amount', val('#wbb-gv-amount'));
		params.append('purchaser_name', val('#wbb-gv-pname'));
		params.append('purchaser_email', val('#wbb-gv-pemail'));
		params.append('purchaser_phone', val('#wbb-gv-pphone'));
		params.append('recipient_name', val('#wbb-gv-rname'));
		params.append('recipient_email', val('#wbb-gv-remail'));
		params.append('recipient_message', val('#wbb-gv-rmsg'));

		fetch(cfg.ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: params.toString()
		})
		.then(function (r) { return r.json(); })
		.then(function (res) {
			if (res.success) {
				showSuccess(res.data);
			} else {
				var msg = (res.data && res.data.message) ? res.data.message : (strings.serverError || 'Error.');
				if (errEl) errEl.textContent = msg;
				if (btn) { btn.disabled = false; btn.textContent = strings.submit || 'Create Gift Voucher'; }
			}
		})
		.catch(function () {
			if (errEl) errEl.textContent = strings.serverError || 'Error.';
			if (btn) { btn.disabled = false; btn.textContent = strings.submit || 'Create Gift Voucher'; }
		});
	}

	function escHtml(s) {
		return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

	function showSuccess(data) {
		if (form) form.style.display = 'none';
		var panel = qs('.wbb-gv-success');
		if (!panel) return;
		var dl = data.pdf_url
			? '<a class="wbb-gv-download" href="' + escHtml(data.pdf_url) + '" target="_blank" rel="noopener">' + (strings.downloadPdf || 'Download voucher (PDF)') + '</a>'
			: '';
		panel.innerHTML =
			'<span class="wbb-gv-success__icon" aria-hidden="true">&#10003;</span>'
			+ '<h2>' + (strings.successTitle || 'Voucher created!') + '</h2>'
			+ '<p>' + escHtml(data.message || '') + '</p>'
			+ '<div class="wbb-gv-code">' + escHtml(data.voucher_code || '') + '</div><br>'
			+ dl;
		panel.classList.remove('wbb-gv-hidden');
		panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
	}

	function init() {
		wrap = qs('#wbb-gv-form');
		if (!wrap) return;
		form = qs('.wbb-gv-card', wrap);
		if (form) form.addEventListener('submit', submit);

		['#wbb-gv-amount', '#wbb-gv-pname', '#wbb-gv-pemail', '#wbb-gv-rname', '#wbb-gv-remail'].forEach(function (id) {
			var el = qs(id);
			if (el) {
				el.addEventListener('input', refreshSubmitState);
				el.addEventListener('blur', function () { validate(true); });
			}
		});
		var cb = qs('#wbb-gv-confirm');
		if (cb) cb.addEventListener('change', refreshSubmitState);

		refreshSubmitState();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
