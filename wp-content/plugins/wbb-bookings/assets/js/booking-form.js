/**
 * WBB Booking Form — vanilla JS, no jQuery dependency.
 * Controls the 5-step booking form rendered by [wbb_booking_form].
 */
(function () {
	'use strict';

	/* ── Config from server ──────────────────────────────────────────────── */
	var cfg = window.wbbData || {};
	var ajaxUrl     = cfg.ajaxUrl      || '';
	var nonce       = cfg.nonce        || '';
	var bookNonce   = cfg.bookingNonce || '';
	var minGroup    = cfg.minGroup     || 2;
	var maxPerBoat  = cfg.maxPerBoat   || 6;
	var strings     = cfg.strings      || {};
	var currency    = cfg.currency     || '$';
	var hasExtras   = !!cfg.hasExtras;
	var stepDetails = cfg.stepDetails  || 4;
	var stepReview  = cfg.stepReview   || 5;

	/* ── State ───────────────────────────────────────────────────────────── */
	var state = {
		step:            1,
		availableDates:  null,   // array of 'YYYY-MM-DD' strings
		calYear:         null,
		calMonth:        null,
		selectedDate:    null,
		selectedSlot:    null,   // full slot object
		groupSize:       minGroup,
		boatsNeeded:     1,
		customerName:    '',
		customerEmail:   '',
		customerPhone:   '',
		customerNotes:   '',
		inclusions:      [],   // [{id, title, qty, unit_price}]
		inclusionsTotal: 0,
	};

	/* ── DOM refs ────────────────────────────────────────────────────────── */
	var formWrap;

	/* ── Utility ─────────────────────────────────────────────────────────── */
	function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
	function show(el) { if (el) el.classList.remove('wbb-hidden'); }
	function hide(el) { if (el) el.classList.add('wbb-hidden'); }
	function setText(el, t) { if (el) el.textContent = t; }
	function padZero(n) { return n < 10 ? '0' + n : String(n); }

	function ajax(action, data, method) {
		var params = new URLSearchParams();
		params.append('action', action);
		params.append('nonce', nonce);
		for (var k in data) {
			if (Object.prototype.hasOwnProperty.call(data, k)) {
				if (Array.isArray(data[k])) {
					data[k].forEach(function(v) { params.append(k + '[]', v); });
				} else {
					params.append(k, data[k]);
				}
			}
		}
		return fetch(ajaxUrl, {
			method: method || 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: params.toString(),
		}).then(function(r) { return r.json(); });
	}

	/* ── Month/day helpers ───────────────────────────────────────────────── */
	var MONTH_NAMES = [
		'January','February','March','April','May','June',
		'July','August','September','October','November','December'
	];
	var DOW_LABELS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

	function dateStr(y, m, d) {
		return y + '-' + padZero(m) + '-' + padZero(d);
	}

	function isAvailable(dateS) {
		return state.availableDates && state.availableDates.indexOf(dateS) !== -1;
	}

	/* ── Step navigation ─────────────────────────────────────────────────── */
	function goToStep(n) {
		var current = qs('#wbb-panel-' + state.step);
		var next    = qs('#wbb-panel-' + n);
		if (current) hide(current);
		if (next)    show(next);
		updateStepIndicator(n);
		state.step = n;
		// Smooth scroll to top of form
		if (formWrap) {
			formWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	}

	function updateStepIndicator(activeStep) {
		var steps = formWrap ? formWrap.querySelectorAll('.wbb-step') : [];
		steps.forEach(function(el) {
			var s = parseInt(el.getAttribute('data-step'), 10);
			el.classList.remove('wbb-step--active', 'wbb-step--completed', 'wbb-step--upcoming');
			if (s < activeStep) {
				el.classList.add('wbb-step--completed');
			} else if (s === activeStep) {
				el.classList.add('wbb-step--active');
				el.setAttribute('aria-current', 'step');
			} else {
				el.classList.add('wbb-step--upcoming');
				el.removeAttribute('aria-current');
			}
		});
	}

	/* ── Step 1: Date picker calendar ───────────────────────────────────── */
	function initDatePicker() {
		var container = qs('#wbb-calendar-container');
		if (!container) return;

		var today = new Date();
		state.calYear  = today.getFullYear();
		state.calMonth = today.getMonth() + 1; // 1-based

		// Show loading message
		show(qs('#wbb-dates-loading'));

		ajax('wbb_get_available_dates', {}).then(function(res) {
			hide(qs('#wbb-dates-loading'));
			if (res.success) {
				state.availableDates = res.data.available || [];
				renderCalendar(container);
			} else {
				container.innerHTML = '<p class="wbb-error">Could not load available dates. Please refresh the page.</p>';
			}
		}).catch(function() {
			hide(qs('#wbb-dates-loading'));
			container.innerHTML = '<p class="wbb-error">Connection error. Please refresh the page.</p>';
		});
	}

	function renderCalendar(container) {
		var y = state.calYear;
		var m = state.calMonth;
		var today = new Date();
		today.setHours(0, 0, 0, 0);

		// First day of month (0=Sun … 6=Sat)
		var firstDay = new Date(y, m - 1, 1).getDay();
		// Days in month
		var daysInMonth = new Date(y, m, 0).getDate();
		// Previous / next month availability for nav arrows
		var now     = new Date();
		var minYear = now.getFullYear();
		var minMon  = now.getMonth() + 1;

		var html = '<div class="wbb-cal">';

		// Nav row
		var prevDisabled = (y < minYear || (y === minYear && m <= minMon)) ? 'disabled' : '';
		html += '<div class="wbb-cal__nav">'
			+ '<button type="button" class="wbb-cal__arrow" id="wbb-cal-prev" ' + prevDisabled + ' aria-label="Previous month">&#8592;</button>'
			+ '<span class="wbb-cal__month">' + MONTH_NAMES[m - 1] + ' ' + y + '</span>'
			+ '<button type="button" class="wbb-cal__arrow" id="wbb-cal-next" aria-label="Next month">&#8594;</button>'
			+ '</div>';

		// DOW headers
		html += '<div class="wbb-cal__grid">';
		DOW_LABELS.forEach(function(d) {
			html += '<div class="wbb-cal__dow">' + d + '</div>';
		});

		// Empty filler cells
		for (var i = 0; i < firstDay; i++) {
			html += '<div class="wbb-cal__day wbb-cal__day--empty" aria-hidden="true"></div>';
		}

		// Day cells
		for (var day = 1; day <= daysInMonth; day++) {
			var ds      = dateStr(y, m, day);
			var cellDate = new Date(y, m - 1, day);
			var avail   = isAvailable(ds);
			var isToday = cellDate.getTime() === today.getTime();
			var isPast  = cellDate < today;

			var cls = 'wbb-cal__day';
			if (isToday) cls += ' wbb-cal__day--today';

			if (avail && !isPast) {
				cls += ' wbb-cal__day--available';
			} else {
				cls += ' wbb-cal__day--unavailable';
			}
			if (ds === state.selectedDate) {
				cls += ' wbb-cal__day--selected';
			}
			var clickable = (avail && !isPast) ? 'data-date="' + ds + '" tabindex="0" role="button" aria-label="' + MONTH_NAMES[m-1] + ' ' + day + '"' : '';
			html += '<div class="' + cls + '" ' + clickable + '>' + day + '</div>';
		}

		html += '</div></div>'; // .wbb-cal__grid + .wbb-cal
		container.innerHTML = html;

		// Bind navigation
		var prevBtn = qs('#wbb-cal-prev', container);
		var nextBtn = qs('#wbb-cal-next', container);

		if (prevBtn) prevBtn.addEventListener('click', function() {
			state.calMonth--;
			if (state.calMonth < 1) { state.calMonth = 12; state.calYear--; }
			renderCalendar(container);
		});
		if (nextBtn) nextBtn.addEventListener('click', function() {
			state.calMonth++;
			if (state.calMonth > 12) { state.calMonth = 1; state.calYear++; }
			renderCalendar(container);
		});

		// Bind date clicks
		container.querySelectorAll('.wbb-cal__day--available').forEach(function(cell) {
			cell.addEventListener('click', function() { selectDate(cell.getAttribute('data-date')); });
			cell.addEventListener('keydown', function(e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					selectDate(cell.getAttribute('data-date'));
				}
			});
		});
	}

	function selectDate(ds) {
		state.selectedDate = ds;
		// Re-render calendar to show selected state then proceed to step 2
		var container = qs('#wbb-calendar-container');
		if (container) renderCalendar(container);
		loadSlots(ds);
		goToStep(2);
	}

	/* ── Step 2: Time slots ──────────────────────────────────────────────── */
	function loadSlots(ds) {
		var dateDisplay = qs('#wbb-date-display');
		var slotsCont   = qs('#wbb-slots-container');
		var loading     = qs('#wbb-slots-loading');
		var errEl       = qs('#wbb-slots-error');

		// Format date nicely
		if (dateDisplay && ds) {
			var d = new Date(ds + 'T00:00:00');
			dateDisplay.textContent = d.toLocaleDateString('en-AU', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
		}

		if (slotsCont) slotsCont.innerHTML = '';
		if (errEl) { hide(errEl); errEl.textContent = ''; }
		if (loading) show(loading);

		ajax('wbb_get_time_slots', { date: ds }).then(function(res) {
			if (loading) hide(loading);
			if (!res.success) {
				if (errEl) { errEl.textContent = res.data && res.data.message ? res.data.message : 'Error loading slots.'; show(errEl); }
				return;
			}
			renderSlots(res.data.slots || []);
		}).catch(function() {
			if (loading) hide(loading);
			if (errEl) { errEl.textContent = strings.serverError || 'Something went wrong.'; show(errEl); }
		});
	}

	function renderSlots(slots) {
		var container = qs('#wbb-slots-container');
		if (!container) return;

		if (!slots.length) {
			container.innerHTML = '<p class="wbb-error">' + (strings.noSlots || 'No slots available.') + '</p>';
			return;
		}

		var html = '<div class="wbb-slots-grid">';
		slots.forEach(function(s) {
			var cls = 'wbb-slot-card';
			if (s.is_full) cls += ' wbb-slot-card--full';

			var durLabel = s.duration_hours === 1 ? '1 hour' : s.duration_hours + ' hours';
			var availLabel = s.is_full
				? '<span class="wbb-slot__full-tag">' + (strings.boatsFull || 'Fully booked') + '</span>'
				: s.boats_remaining + ' ' + (s.boats_remaining === 1 ? (strings.boatRemain || 'boat available') : (strings.boatsRemain || 'boats available'));

			var attrs = s.is_full ? '' : 'data-slot-ts="' + escHtml(s.time_slot) + '" tabindex="0" role="button"';

			html += '<div class="' + cls + '" ' + attrs + '>'
				+ '<div class="wbb-slot__time">' + escHtml(s.time_slot) + '</div>'
				+ '<div class="wbb-slot__meta">' + escHtml(durLabel) + ' &bull; ' + availLabel + '</div>'
				+ '</div>';
		});
		html += '</div>';
		container.innerHTML = html;

		// Store full slot objects for later lookup
		container._slotData = slots;

		// Bind slot click
		container.querySelectorAll('.wbb-slot-card:not(.wbb-slot-card--full)').forEach(function(card) {
			card.addEventListener('click', function() {
				var ts   = card.getAttribute('data-slot-ts');
				var slot = slots.find(function(s) { return s.time_slot === ts; });
				if (slot) selectSlot(slot, card);
			});
			card.addEventListener('keydown', function(e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					card.click();
				}
			});
		});

		// Auto-select if only one slot
		var available = container.querySelectorAll('.wbb-slot-card:not(.wbb-slot-card--full)');
		if (available.length === 1) {
			available[0].click();
		}
	}

	function selectSlot(slot, cardEl) {
		state.selectedSlot = slot;
		// Visual selection
		var container = qs('#wbb-slots-container');
		if (container) {
			container.querySelectorAll('.wbb-slot-card').forEach(function(c) { c.classList.remove('wbb-slot-card--selected'); });
		}
		if (cardEl) cardEl.classList.add('wbb-slot-card--selected');

		// Move to step 3 after short delay so user sees selection
		setTimeout(function() { goToStep(3); updateBoatCalc(); }, 280);
	}

	/* ── Step 3: Group size ──────────────────────────────────────────────── */
	function updateBoatCalc() {
		var groupInput = qs('#wbb-group-size');
		if (!groupInput) return;

		var groupSize    = parseInt(groupInput.value, 10) || minGroup;
		var boatsNeeded  = Math.ceil(groupSize / maxPerBoat);
		state.groupSize  = groupSize;
		state.boatsNeeded = boatsNeeded;

		var boatsAvail   = state.selectedSlot ? state.selectedSlot.boats_remaining : 0;
		var nextBtn      = qs('#wbb-next-3');
		var errEl        = qs('#wbb-boats-error');
		var calcDiv      = qs('#wbb-boat-calc');
		var neededEl     = qs('#wbb-boats-needed');
		var availEl      = qs('#wbb-boats-avail-count');

		if (calcDiv) show(calcDiv);
		if (neededEl) neededEl.textContent = boatsNeeded;
		if (availEl)  availEl.textContent  = boatsAvail;


		var hasError = boatsNeeded > boatsAvail;
		if (errEl) {
			if (hasError) {
				errEl.textContent = strings.notEnoughBoats || 'Sorry, not enough boats available for your group size.';
				show(errEl);
			} else {
				errEl.textContent = '';
				hide(errEl);
			}
		}
		if (nextBtn) nextBtn.disabled = hasError || groupSize < minGroup;
	}

	/* ── Step 4: Extras (Food & Drink) ───────────────────────────────────── */
	function fmtMoney(n) {
		return currency + (Math.round(n * 100) / 100).toFixed(2);
	}

	function updateExtras() {
		var rows = document.querySelectorAll('.wbb-extra-row');
		var total = 0;
		var chosen = [];
		rows.forEach(function (row) {
			var qtyInput = qs('.wbb-extra-qty', row);
			var qty = parseInt(qtyInput && qtyInput.value, 10) || 0;
			if (qty < 0) { qty = 0; if (qtyInput) qtyInput.value = 0; }
			var price = parseFloat(row.getAttribute('data-price')) || 0;
			if (qty > 0) {
				total += qty * price;
				chosen.push({
					id:         parseInt(row.getAttribute('data-id'), 10),
					title:      row.getAttribute('data-title') || '',
					qty:        qty,
					unit_price: price,
				});
			}
		});
		state.inclusions = chosen;
		state.inclusionsTotal = total;

		var totalEl = qs('#wbb-extras-total');
		if (totalEl) totalEl.textContent = fmtMoney(total);
	}

	/* ── Step 4: Validation ──────────────────────────────────────────────── */
	function validateField(inputEl, errorEl, rules) {
		var val = inputEl.value.trim();
		var msg = '';
		if (rules.required && !val) {
			msg = rules.requiredMsg || 'This field is required.';
		} else if (rules.email && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
			msg = rules.emailMsg || 'Please enter a valid email address.';
		}
		if (errorEl) {
			errorEl.textContent = msg;
		}
		return !msg;
	}

	function validateStep4() {
		var nameEl   = qs('#wbb-name');
		var emailEl  = qs('#wbb-email');
		var phoneEl  = qs('#wbb-phone');

		var v1 = validateField(nameEl,  qs('#wbb-name-error'),  { required: true, requiredMsg: strings.nameRequired });
		var v2 = validateField(emailEl, qs('#wbb-email-error'), { required: true, email: true, emailMsg: strings.emailInvalid, requiredMsg: strings.emailInvalid });
		var v3 = validateField(phoneEl, qs('#wbb-phone-error'), { required: true, requiredMsg: strings.phoneRequired });

		if (v1 && v2 && v3) {
			state.customerName  = nameEl.value.trim();
			state.customerEmail = emailEl.value.trim();
			state.customerPhone = phoneEl.value.trim();
			state.customerNotes = (qs('#wbb-notes') || {}).value || '';
			return true;
		}
		return false;
	}

	/* ── Step 5: Summary ─────────────────────────────────────────────────── */
	function renderSummary() {
		var container = qs('#wbb-summary-card');
		if (!container || !state.selectedSlot) return;

		var slot      = state.selectedSlot;
		var d         = state.selectedDate ? new Date(state.selectedDate + 'T00:00:00') : null;
		var dateLabel = d ? d.toLocaleDateString('en-AU', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : '';
		var durLabel  = slot.duration_hours === 1 ? '1 hour' : slot.duration_hours + ' hours';

		var rows = [
			['Date',       dateLabel],
			['Time',       slot.time_slot],
			['Duration',   durLabel],
			['Group size', state.groupSize + ' people'],
			['Boats',      state.boatsNeeded],
			['Name',       state.customerName],
			['Email',      state.customerEmail],
			['Phone',      state.customerPhone],
		];

		if (state.inclusions && state.inclusions.length) {
			state.inclusions.forEach(function(it) {
				rows.push([
					it.title + ' × ' + it.qty,
					fmtMoney(it.qty * it.unit_price),
				]);
			});
			rows.push(['Extras total', fmtMoney(state.inclusionsTotal)]);
		}

		if (state.customerNotes) {
			rows.push(['Notes', state.customerNotes]);
		}

		var html = rows.map(function(r) {
			return '<div class="wbb-summary-row">'
				+ '<span class="wbb-summary-row__label">' + escHtml(r[0]) + '</span>'
				+ '<span class="wbb-summary-row__value">' + escHtml(String(r[1])) + '</span>'
				+ '</div>';
		}).join('');


		container.innerHTML = html;
	}

	/* ── Submit ──────────────────────────────────────────────────────────── */
	function submitBooking() {
		var submitBtn = qs('#wbb-submit-btn');
		var errEl     = qs('#wbb-submit-error');

		if (submitBtn) {
			submitBtn.disabled = true;
			submitBtn.textContent = strings.submitting || 'Sending…';
		}
		if (errEl) { hide(errEl); errEl.textContent = ''; }

		if (!state.selectedSlot) {
			showSubmitError(strings.serverError || 'Something went wrong. Please try again.');
			return;
		}

		var params = new URLSearchParams();
		params.append('action', 'wbb_submit_booking');
		params.append('nonce', bookNonce);
		params.append('booking_date',     state.selectedDate);
		params.append('time_slot',        state.selectedSlot.time_slot);
		params.append('group_size',       state.groupSize);
		params.append('boats_requested',  state.boatsNeeded);
		params.append('customer_name',    state.customerName);
		params.append('customer_email',   state.customerEmail);
		params.append('customer_phone',   state.customerPhone);
		params.append('notes',            state.customerNotes);
		params.append('inclusions',       JSON.stringify(state.inclusions || []));
		params.append('inclusions_total', state.inclusionsTotal || 0);

		fetch(ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: params.toString(),
		})
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (res.success) {
				showSuccess(res.data.message || 'Your request has been received!');
			} else {
				var code = res.data && res.data.code;
				var msg  = res.data && res.data.message ? res.data.message : (strings.serverError || 'Something went wrong.');
				if (code === 'taken') {
					showSubmitError(msg);
				} else {
					showSubmitError(msg);
				}
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent = 'Send Booking Request';
				}
			}
		})
		.catch(function() {
			showSubmitError(strings.serverError || 'Something went wrong. Please try again.');
			if (submitBtn) {
				submitBtn.disabled = false;
				submitBtn.textContent = 'Send Booking Request';
			}
		});
	}

	function showSubmitError(msg) {
		var errEl = qs('#wbb-submit-error');
		if (errEl) { errEl.textContent = msg; show(errEl); }
	}

	function showSuccess(msg) {
		// Hide the form panels & step indicator
		var stepNav = formWrap ? formWrap.querySelector('.wbb-steps-nav') : null;
		if (stepNav) hide(stepNav);
		for (var i = 1; i <= 6; i++) {
			var p = qs('#wbb-panel-' + i);
			if (p) hide(p);
		}

		var panel = qs('#wbb-success-panel');
		if (panel) {
			panel.innerHTML =
				'<span class="wbb-success-panel__icon" aria-hidden="true">&#10003;</span>'
				+ '<h2>Request Sent!</h2>'
				+ '<p>' + msg.replace(/\n/g, '<br>') + '</p>';
			show(panel);
		}
	}

	/* ── Escape helper ───────────────────────────────────────────────────── */
	function escHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	/* ── Wire up event listeners ─────────────────────────────────────────── */
	function bindEvents() {
		// Back buttons (all steps)
		document.querySelectorAll('.wbb-back-btn').forEach(function(btn) {
			btn.addEventListener('click', function() {
				var target = parseInt(btn.getAttribute('data-go-to'), 10);
				goToStep(target);
			});
		});

		// Step 3: group size inputs
		var groupInput = qs('#wbb-group-size');
		var minusBtn   = qs('#wbb-group-minus');
		var plusBtn    = qs('#wbb-group-plus');
		var nextBtn3   = qs('#wbb-next-3');

		if (groupInput) {
			groupInput.addEventListener('input', updateBoatCalc);
			groupInput.value = minGroup;
		}
		if (minusBtn) minusBtn.addEventListener('click', function() {
			var v = parseInt(groupInput.value, 10) || minGroup;
			if (v > minGroup) { groupInput.value = v - 1; updateBoatCalc(); }
		});
		if (plusBtn) plusBtn.addEventListener('click', function() {
			var v = parseInt(groupInput.value, 10) || minGroup;
			groupInput.value = v + 1; updateBoatCalc();
		});
		if (nextBtn3) nextBtn3.addEventListener('click', function() {
			goToStep(4); // step 4 is Extras when present, otherwise Details
		});

		// Step 4: Extras — quantity steppers + Next
		var extrasContainer = qs('#wbb-panel-4');
		if (extrasContainer && hasExtras) {
			extrasContainer.addEventListener('click', function(e) {
				var row = e.target.closest ? e.target.closest('.wbb-extra-row') : null;
				if (!row) return;
				var input = qs('.wbb-extra-qty', row);
				if (!input) return;
				if (e.target.classList.contains('wbb-extra-plus')) {
					input.value = (parseInt(input.value, 10) || 0) + 1;
					updateExtras();
				} else if (e.target.classList.contains('wbb-extra-minus')) {
					input.value = Math.max(0, (parseInt(input.value, 10) || 0) - 1);
					updateExtras();
				}
			});
			extrasContainer.addEventListener('input', function(e) {
				if (e.target.classList.contains('wbb-extra-qty')) updateExtras();
			});
		}
		var nextExtras = qs('#wbb-next-extras');
		if (nextExtras) nextExtras.addEventListener('click', function() {
			updateExtras();
			goToStep(stepDetails);
		});

		// Step 4: blur validation
		var fields4 = [
			{ id: '#wbb-name',  errId: '#wbb-name-error',  rules: { required: true, requiredMsg: strings.nameRequired } },
			{ id: '#wbb-email', errId: '#wbb-email-error', rules: { required: true, email: true, emailMsg: strings.emailInvalid, requiredMsg: strings.emailInvalid } },
			{ id: '#wbb-phone', errId: '#wbb-phone-error', rules: { required: true, requiredMsg: strings.phoneRequired } },
		];
		fields4.forEach(function(f) {
			var el  = qs(f.id);
			var err = qs(f.errId);
			if (el) el.addEventListener('blur', function() { validateField(el, err, f.rules); });
		});

		var nextBtnDetails = qs('#wbb-next-details');
		if (nextBtnDetails) nextBtnDetails.addEventListener('click', function() {
			if (validateStep4()) {
				renderSummary();
				goToStep(stepReview);
			}
		});

		// Step 5: confirm checkbox enables submit
		var confirmCb  = qs('#wbb-confirm-cb');
		var submitBtn  = qs('#wbb-submit-btn');
		if (confirmCb && submitBtn) {
			confirmCb.addEventListener('change', function() {
				submitBtn.disabled = !confirmCb.checked;
			});
		}

		if (submitBtn) {
			submitBtn.addEventListener('click', function() {
				if (!submitBtn.disabled) submitBooking();
			});
		}
	}

	/* ── Init ────────────────────────────────────────────────────────────── */
	function init() {
		formWrap = qs('#wbb-booking-form');
		if (!formWrap) return;

		bindEvents();
		initDatePicker();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
