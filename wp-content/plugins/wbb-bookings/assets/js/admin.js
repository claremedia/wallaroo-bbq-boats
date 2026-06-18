/**
 * WBB Bookings — Admin JS
 * Handles: settings, fleet management, weekly schedule editor,
 *          session blocking, availability calendar, bookings table.
 * Uses jQuery (available in WP admin).
 */
(function ($) {
	'use strict';

	var cfg     = window.wbbAdmin || {};
	var ajaxUrl = cfg.ajaxUrl || ajaxurl; // eslint-disable-line no-undef
	var nonce   = cfg.nonce   || '';
	var str     = cfg.strings || {};
	var days    = str.days    || ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

	/* ────────────────────────────────────────────────────────────────────────
	 * SETTINGS PAGE
	 * ──────────────────────────────────────────────────────────────────────── */
	function initSettings() {
		$('#wbb-reset-defaults').on('click', function () {
			if (!window.confirm(str.confirmReset || 'Reset all settings to defaults?')) return;

			$.post(ajaxUrl, {
				action: 'wbb_admin_reset_settings',
				nonce:  nonce,
			}, function (res) {
				if (res.success) {
					window.location.reload();
				} else {
					alert(str.errorGeneric || 'Something went wrong.');
				}
			});
		});
	}

	/* ────────────────────────────────────────────────────────────────────────
	 * AVAILABILITY PAGE
	 * ──────────────────────────────────────────────────────────────────────── */

	var boatCalYear, boatCalMonth, activeBoatId;

	function initAvailability() {
		if (!$('.wbb-av-wrap').length) return;

		initFleet();

		// Schedule boat selector
		$('#wbb-schedule-boat-select').on('change', function () {
			var boatId = $(this).val();
			if (boatId) {
				loadBoatSchedule(boatId);
			} else {
				$('#wbb-schedule-grid-wrap').html('<p style="color:#646970;font-size:13px;">' + escHtml(str.selectBoatFirst || 'Select a boat above.') + '</p>');
			}
		});
	}

	/* ── Fleet Management ─────────────────────────────────────────────────── */

	function initFleet() {
		loadFleet();

		$(document).on('click', '#wbb-add-boat', function () {
			showBoatForm(null);
		});

		$(document).on('click', '#wbb-cancel-boat', function () {
			hideBoatForm();
		});

		$(document).on('click', '#wbb-save-boat', function () {
			saveBoat();
		});

		$(document).on('click', '.wbb-boat-edit-btn', function (e) {
			e.preventDefault();
			var $row = $(this).closest('tr');
			showBoatForm({
				id:         $row.data('id'),
				name:       $row.data('name'),
				active:     $row.data('active'),
				sort_order: $row.data('sort'),
			});
		});

		$(document).on('click', '.wbb-boat-delete-btn', function (e) {
			e.preventDefault();
			var id   = $(this).data('id');
			var name = $(this).data('name');
			if (!window.confirm(str.confirmDeleteBoat || 'Delete this boat and all its schedule data? This cannot be undone.')) return;
			deleteBoat(id);
		});
	}

	function loadFleet() {
		$('#wbb-fleet-wrap').html('<p class="wbb-loading-msg">Loading fleet\u2026</p>');

		$.post(ajaxUrl, { action: 'wbb_admin_get_boats', nonce: nonce }, function (res) {
			if (!res.success) {
				$('#wbb-fleet-wrap').html('<p class="wbb-status-msg is-error">Failed to load fleet.</p>');
				return;
			}
			renderFleet(res.data.boats || []);
			populateBoatSelector(res.data.boats || []);
			populateBoatCalendarTabs(res.data.boats || []);
		});
	}

	function renderFleet(boats) {
		if (!boats.length) {
			$('#wbb-fleet-wrap').html('<p style="color:#646970;font-size:13px;">No boats yet. Add your first boat below.</p>');
			return;
		}

		var html = '<table class="wp-list-table widefat fixed striped" style="max-width:600px;">'
			+ '<thead><tr>'
			+ '<th>Boat</th>'
			+ '<th style="width:80px;">Active</th>'
			+ '<th style="width:70px;">Sort</th>'
			+ '<th style="width:150px;">Actions</th>'
			+ '</tr></thead><tbody>';

		boats.forEach(function (b) {
			html += '<tr'
				+ ' data-id="' + b.id + '"'
				+ ' data-name="' + escHtml(b.name) + '"'
				+ ' data-active="' + b.active + '"'
				+ ' data-sort="' + b.sort_order + '"'
				+ '>'
				+ '<td><strong>' + escHtml(b.name) + '</strong></td>'
				+ '<td>' + (b.active == 1 ? '&#10003;' : '&#8212;') + '</td>'
				+ '<td>' + escHtml(String(b.sort_order)) + '</td>'
				+ '<td>'
				+ '<a href="#" class="wbb-view-btn wbb-boat-edit-btn" data-id="' + b.id + '">Edit</a>'
				+ '<span class="wbb-action-sep"> | </span>'
				+ '<a href="#" class="wbb-cancel-btn wbb-boat-delete-btn" data-id="' + b.id + '" data-name="' + escHtml(b.name) + '">Delete</a>'
				+ '</td>'
				+ '</tr>';
		});

		html += '</tbody></table>';
		$('#wbb-fleet-wrap').html(html);
	}

	function populateBoatSelector(boats) {
		var $sel     = $('#wbb-schedule-boat-select');
		var current  = $sel.val();
		$sel.find('option:not(:first)').remove();
		boats.forEach(function (b) {
			if (b.active == 1) {
				$sel.append('<option value="' + b.id + '">' + escHtml(b.name) + '</option>');
			}
		});
		if (current) $sel.val(current);
	}

	function showBoatForm(boat) {
		var $wrap = $('#wbb-boat-form-wrap');

		if (boat) {
			$('#wbb-boat-form-title').text('Edit Boat');
			$('#wbb-boat-id').val(boat.id);
			$('#wbb-boat-name').val(boat.name);
			$('#wbb-boat-sort').val(boat.sort_order);
			$('#wbb-boat-active').prop('checked', boat.active == 1);
		} else {
			$('#wbb-boat-form-title').text('Add Boat');
			$('#wbb-boat-id').val(0);
			$('#wbb-boat-name').val('');
			$('#wbb-boat-sort').val(0);
			$('#wbb-boat-active').prop('checked', true);
		}

		$('#wbb-boat-status').text('').removeClass('is-error');
		$wrap.removeClass('wbb-hidden');
		$('#wbb-boat-name').focus();
	}

	function hideBoatForm() {
		$('#wbb-boat-form-wrap').addClass('wbb-hidden');
	}

	function saveBoat() {
		var id     = $('#wbb-boat-id').val();
		var name   = $('#wbb-boat-name').val().trim();
		var sort   = $('#wbb-boat-sort').val();
		var active = $('#wbb-boat-active').is(':checked') ? 1 : 0;

		if (!name) { alert('Please enter a boat name.'); return; }

		var $btn    = $('#wbb-save-boat').prop('disabled', true).text(str.saving || 'Saving\u2026');
		var $status = $('#wbb-boat-status').text('').removeClass('is-error');

		$.post(ajaxUrl, {
			action:     'wbb_admin_save_boat',
			nonce:      nonce,
			boat_id:    id,
			name:       name,
			active:     active,
			sort_order: sort,
		}, function (res) {
			$btn.prop('disabled', false).text('Save Boat');
			if (res.success) {
				$status.text(str.saved || 'Saved.');
				renderFleet(res.data.boats || []);
				populateBoatSelector(res.data.boats || []);
				populateBoatCalendarTabs(res.data.boats || []);
				hideBoatForm();
			} else {
				$status.addClass('is-error').text(res.data && res.data.message ? res.data.message : str.errorGeneric);
			}
		});
	}

	function deleteBoat(id) {
		$.post(ajaxUrl, {
			action:  'wbb_admin_delete_boat',
			nonce:   nonce,
			boat_id: id,
		}, function (res) {
			if (res.success) {
				renderFleet(res.data.boats || []);
				populateBoatSelector(res.data.boats || []);
				populateBoatCalendarTabs(res.data.boats || []);
			} else {
				alert(res.data && res.data.message ? res.data.message : str.errorGeneric);
			}
		});
	}

	/* ── Weekly Schedule Editor ───────────────────────────────────────────── */

	function loadBoatSchedule(boatId) {
		var $wrap = $('#wbb-schedule-grid-wrap');
		$wrap.html('<p class="wbb-loading-msg">Loading schedule\u2026</p>');

		$.post(ajaxUrl, {
			action:  'wbb_admin_get_boat_schedule',
			nonce:   nonce,
			boat_id: boatId,
		}, function (res) {
			if (!res.success) {
				$wrap.html('<p class="wbb-status-msg is-error">Failed to load schedule.</p>');
				return;
			}
			renderScheduleEditor(boatId, res.data.schedule || []);
		});
	}

	function renderScheduleEditor(boatId, sessions) {
		// Group sessions by day_of_week
		var byDay = {};
		for (var d = 0; d < 7; d++) { byDay[d] = []; }
		sessions.forEach(function (s) {
			var dow = parseInt(s.day_of_week, 10);
			if (!isNaN(dow) && dow >= 0 && dow <= 6) {
				byDay[dow].push(s);
			}
		});

		var durations = str.durations || ['2', '2.5', '3'];

		var html = '<div class="wbb-schedule-editor">';

		for (var dow = 0; dow < 7; dow++) {
			var dayName     = days[dow] || 'Day ' + dow;
			var daySessions = byDay[dow];

			html += '<div class="wbb-schedule-day" data-dow="' + dow + '">'
				+ '<div class="wbb-schedule-day__header">'
				+ '<strong class="wbb-schedule-day__name">' + escHtml(dayName) + '</strong>'
				+ '<button type="button" class="button button-small wbb-add-day-session" data-dow="' + dow + '" style="margin-left:auto;">+ Add session</button>'
				+ '</div>'
				+ '<div class="wbb-schedule-day__sessions">';

			if (daySessions.length) {
				daySessions.forEach(function (s) {
					html += buildSessionRow(s, durations);
				});
			} else {
				html += '<p class="wbb-schedule-empty-day" style="color:#9ca3af;font-size:13px;margin:6px 0 0;">No sessions</p>';
			}

			html += '</div></div>'; // .sessions + .day
		}

		html += '</div>' // .schedule-editor
			+ '<div style="margin-top:16px;">'
			+ '<button type="button" class="button button-primary" id="wbb-save-schedule" data-boat-id="' + boatId + '">Save Schedule</button>'
			+ ' <span class="wbb-status-msg" id="wbb-schedule-status"></span>'
			+ '</div>';

		var $wrap = $('#wbb-schedule-grid-wrap');
		$wrap.html(html);

		// Add session row
		$wrap.off('click', '.wbb-add-day-session').on('click', '.wbb-add-day-session', function () {
			var dow  = parseInt($(this).data('dow'), 10);
			var $day = $wrap.find('.wbb-schedule-day[data-dow="' + dow + '"] .wbb-schedule-day__sessions');
			$day.find('.wbb-schedule-empty-day').remove();
			$day.append(buildSessionRow({}, durations));
		});

		// Remove session row
		$wrap.off('click', '.wbb-remove-session').on('click', '.wbb-remove-session', function () {
			var $row = $(this).closest('.wbb-schedule-session-row');
			var $day = $row.closest('.wbb-schedule-day__sessions');
			$row.remove();
			if (!$day.find('.wbb-schedule-session-row').length) {
				$day.html('<p class="wbb-schedule-empty-day" style="color:#9ca3af;font-size:13px;margin:6px 0 0;">No sessions</p>');
			}
		});

		// Save schedule
		$wrap.off('click', '#wbb-save-schedule').on('click', '#wbb-save-schedule', function () {
			saveSchedule(boatId);
		});
	}

	function buildSessionRow(s, durations) {
		var time     = s.time_slot      ? formatTimeForInput(s.time_slot)     : '09:00';
		var duration = s.duration_hours ? String(parseFloat(s.duration_hours)) : '2';

		var durOptions = '';
		(durations || ['2', '2.5', '3']).forEach(function (v) {
			var sel = (String(parseFloat(v)) === String(parseFloat(duration))) ? ' selected' : '';
			durOptions += '<option value="' + v + '"' + sel + '>' + v + ' hrs</option>';
		});

		return '<div class="wbb-schedule-session-row wbb-edit-slot-row">'
			+ '<div><label>Time</label><input type="time" class="wbb-session-time" value="' + time + '"></div>'
			+ '<div><label>Duration</label><select class="wbb-session-duration">' + durOptions + '</select></div>'
			+ '<div style="align-self:flex-end;"><button type="button" class="button wbb-remove-session" title="Remove session">&#10005;</button></div>'
			+ '</div>';
	}

	function saveSchedule(boatId) {
		var $btn    = $('#wbb-save-schedule').prop('disabled', true).text(str.saving || 'Saving\u2026');
		var $status = $('#wbb-schedule-status').text('').removeClass('is-error');

		var sessions = [];
		$('#wbb-schedule-grid-wrap .wbb-schedule-day').each(function () {
			var dow = parseInt($(this).data('dow'), 10);
			$(this).find('.wbb-schedule-session-row').each(function () {
				var time = $(this).find('.wbb-session-time').val();
				if (!time) return;
				sessions.push({
					day_of_week:    dow,
					time_slot:      formatTimeDisplay(time),
					duration_hours: $(this).find('.wbb-session-duration').val(),
					active:         1,
				});
			});
		});

		$.post(ajaxUrl, {
			action:   'wbb_admin_save_boat_schedule',
			nonce:    nonce,
			boat_id:  boatId,
			sessions: sessions,
		}, function (res) {
			$btn.prop('disabled', false).text('Save Schedule');
			if (res.success) {
				$status.text(str.scheduleSaved || 'Schedule saved.');
				if (activeBoatId) loadBoatCalMonth(activeBoatId, boatCalYear, boatCalMonth);
			} else {
				$status.addClass('is-error').text(res.data && res.data.message ? res.data.message : str.errorGeneric);
			}
		});
	}


	/* ── Per-boat Availability Calendar ─────────────────────────────────────── */

	function populateBoatCalendarTabs(boats) {
		var $nav        = $('#wbb-boat-tabs-nav');
		var activeBoats = boats.filter(function (b) { return b.active == 1; });

		if (!activeBoats.length) {
			$nav.html('<p style="color:#646970;font-size:13px;">No active boats. Add boats in Fleet Management above.</p>');
			$('#wbb-boat-cal-nav').addClass('wbb-hidden');
			$('#wbb-boat-calendar-grid').html('');
			return;
		}

		var html = '';
		activeBoats.forEach(function (b) {
			html += '<button type="button" class="wbb-boat-tab" data-boat-id="' + b.id + '">' + escHtml(b.name) + '</button>';
		});
		$nav.html(html);

		$nav.off('click', '.wbb-boat-tab').on('click', '.wbb-boat-tab', function () {
			activateBoatTab(parseInt($(this).data('boat-id'), 10));
		});

		// Activate first tab
		activateBoatTab(parseInt(activeBoats[0].id, 10));
	}

	function activateBoatTab(boatId) {
		activeBoatId = boatId;

		var now = new Date();
		if (!boatCalYear)  { boatCalYear  = now.getFullYear(); }
		if (!boatCalMonth) { boatCalMonth = now.getMonth() + 1; }

		// Update tab states
		$('.wbb-boat-tab').removeClass('wbb-boat-tab--active');
		$('.wbb-boat-tab[data-boat-id="' + boatId + '"]').addClass('wbb-boat-tab--active');

		// Show month nav and wire its buttons (rebind each time to avoid stale closure)
		$('#wbb-boat-cal-nav').removeClass('wbb-hidden');

		$('#wbb-bcal-prev').off('click').on('click', function () {
			boatCalMonth--;
			if (boatCalMonth < 1) { boatCalMonth = 12; boatCalYear--; }
			loadBoatCalMonth(activeBoatId, boatCalYear, boatCalMonth);
		});
		$('#wbb-bcal-next').off('click').on('click', function () {
			boatCalMonth++;
			if (boatCalMonth > 12) { boatCalMonth = 1; boatCalYear++; }
			loadBoatCalMonth(activeBoatId, boatCalYear, boatCalMonth);
		});

		loadBoatCalMonth(boatId, boatCalYear, boatCalMonth);
	}

	function loadBoatCalMonth(boatId, y, m) {
		var $grid  = $('#wbb-boat-calendar-grid');
		var months = ['January','February','March','April','May','June',
		              'July','August','September','October','November','December'];

		if (!$grid.length) return;

		$('#wbb-bcal-title').text(months[m - 1] + ' ' + y);
		$grid.html('<p class="wbb-loading-msg">Loading\u2026</p>');

		$.post(ajaxUrl, {
			action:  'wbb_admin_get_boat_month',
			nonce:   nonce,
			boat_id: boatId,
			year:    y,
			month:   m,
		}, function (res) {
			if (!res.success) {
				$grid.html('<p class="wbb-status-msg is-error">Failed to load calendar.</p>');
				return;
			}
			renderBoatCalGrid($grid, boatId, res.data);
		});
	}

	function renderBoatCalGrid($grid, boatId, data) {
		var y         = data.year;
		var m         = data.month;
		var days_data = data.days || {};

		var dowLabels   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
		var firstDay    = new Date(y, m - 1, 1).getDay();
		var daysInMonth = new Date(y, m, 0).getDate();
		var pad         = function (n) { return n < 10 ? '0' + n : String(n); };

		var html = '<div class="wbb-bcal">';

		dowLabels.forEach(function (d) {
			html += '<div class="wbb-bcal-dow">' + d + '</div>';
		});

		for (var i = 0; i < firstDay; i++) {
			html += '<div class="wbb-bcal-cell wbb-bcal-cell--empty"></div>';
		}

		for (var day = 1; day <= daysInMonth; day++) {
			var ds      = y + '-' + pad(m) + '-' + pad(day);
			var dayData = days_data[ds] || { in_season: false, slots: [] };
			var slots   = dayData.slots || [];

			var cls = 'wbb-bcal-cell';
			if (!dayData.in_season)  cls += ' wbb-bcal-cell--off-season';
			else if (!slots.length)  cls += ' wbb-bcal-cell--no-slots';
			else                     cls += ' wbb-bcal-cell--has-slots';

			var slotsHtml = '';
			slots.forEach(function (s) {
				var slotCls = s.is_blocked
					? 'wbb-bcal-slot wbb-bcal-slot--blocked'
					: 'wbb-bcal-slot wbb-bcal-slot--open';
				var statusLabel = s.is_blocked
					? 'Blocked'
					: (s.boats_booked > 0 ? s.boats_booked + ' booked' : 'Open');
				var actionLink = s.is_blocked
					? '<a href="#" class="wbb-bcal-unblock"'
						+ ' data-boat-id="' + boatId + '"'
						+ ' data-date="' + escHtml(ds) + '"'
						+ ' data-slot="' + escHtml(s.time_slot) + '"'
						+ '>Unblock</a>'
					: '<a href="#" class="wbb-bcal-block"'
						+ ' data-boat-id="' + boatId + '"'
						+ ' data-date="' + escHtml(ds) + '"'
						+ ' data-slot="' + escHtml(s.time_slot) + '"'
						+ '>Block</a>';

				slotsHtml += '<div class="' + slotCls + '">'
					+ '<span class="wbb-bcal-slot__time">' + escHtml(s.time_slot) + '</span>'
					+ '<span class="wbb-bcal-slot__status">' + escHtml(statusLabel) + '</span>'
					+ actionLink
					+ '</div>';
			});

			html += '<div class="' + cls + '" data-date="' + ds + '">'
				+ '<div class="wbb-bcal-cell__day">' + day + '</div>'
				+ (slotsHtml ? '<div class="wbb-bcal-slots">' + slotsHtml + '</div>' : '')
				+ '</div>';
		}

		html += '</div>'; // .wbb-bcal
		$grid.html(html);

		// Block a session
		$grid.off('click', '.wbb-bcal-block').on('click', '.wbb-bcal-block', function (e) {
			e.preventDefault();
			var $a  = $(this);
			var bid = $a.data('boat-id');
			var dt  = $a.data('date');
			var sl  = $a.data('slot');

			$.post(ajaxUrl, {
				action:    'wbb_admin_add_exception',
				nonce:     nonce,
				boat_id:   bid,
				date:      dt,
				time_slot: sl,
			}, function (res) {
				if (res.success) {
					loadBoatCalMonth(bid, boatCalYear, boatCalMonth);
				} else {
					alert(res.data && res.data.message ? res.data.message : str.errorGeneric);
				}
			});
		});

		// Unblock a session
		$grid.off('click', '.wbb-bcal-unblock').on('click', '.wbb-bcal-unblock', function (e) {
			e.preventDefault();
			var $a  = $(this);
			var bid = $a.data('boat-id');
			var dt  = $a.data('date');
			var sl  = $a.data('slot');

			$.post(ajaxUrl, {
				action:    'wbb_admin_remove_exception',
				nonce:     nonce,
				boat_id:   bid,
				date:      dt,
				time_slot: sl,
			}, function (res) {
				if (res.success) {
					loadBoatCalMonth(bid, boatCalYear, boatCalMonth);
				} else {
					alert(res.data && res.data.message ? res.data.message : str.errorGeneric);
				}
			});
		});
	}


	/* ────────────────────────────────────────────────────────────────────────
	 * BOOKINGS PAGE
	 * ──────────────────────────────────────────────────────────────────────── */

	function initBookings() {
		if (!$('.wbb-bk-wrap').length) return;

		// Confirm booking — [data-id] guard avoids triggering on availability page links
		$(document).on('click', '.wbb-confirm-btn[data-id]', function (e) {
			e.preventDefault();
			var id = $(this).data('id');
			if (!window.confirm(str.confirmConfirm || 'Confirm this booking?')) return;
			updateBookingStatus(id, 'confirmed');
		});

		// Cancel booking
		$(document).on('click', '.wbb-cancel-btn[data-id]', function (e) {
			e.preventDefault();
			var id = $(this).data('id');
			if (!window.confirm(str.confirmCancel || 'Cancel this booking?')) return;
			updateBookingStatus(id, 'cancelled');
		});

		// View / collapse detail panel
		$(document).on('click', '.wbb-view-btn', function (e) {
			e.preventDefault();
			var id      = $(this).data('id');
			var $row    = $('#wbb-detail-' + id);
			var $panel  = $row.find('.wbb-detail-panel');

			if (!$row.hasClass('wbb-hidden')) {
				$row.addClass('wbb-hidden');
				return;
			}

			// Hide all other open panels
			$('.wbb-detail-row').not($row).addClass('wbb-hidden');

			$row.removeClass('wbb-hidden');
			loadBookingDetail(id, $panel);
		});

		// Staff notes autosave on blur
		$(document).on('blur', '.wbb-staff-notes', function () {
			var $el     = $(this);
			var id      = $el.data('id');
			var notes   = $el.val();
			var $status = $el.siblings('.wbb-notes-status');

			$status.text(str.saving || 'Saving\u2026');

			$.post(ajaxUrl, {
				action:      'wbb_admin_save_booking_notes',
				nonce:       nonce,
				booking_id:  id,
				staff_notes: notes,
			}, function (res) {
				if (res.success) {
					$status.text(str.notesSaved || 'Notes saved.');
				} else {
					$status.text(str.errorGeneric || 'Error saving.');
				}
				setTimeout(function () { $status.text(''); }, 3000);
			});
		});
	}

	function updateBookingStatus(id, status) {
		$.post(ajaxUrl, {
			action:     'wbb_admin_update_booking_status',
			nonce:      nonce,
			booking_id: id,
			status:     status,
		}, function (res) {
			if (res.success) {
				window.location.reload();
			} else {
				alert(res.data && res.data.message ? res.data.message : (str.errorGeneric || 'Something went wrong.'));
			}
		});
	}

	function loadBookingDetail(id, $panel) {
		$panel.html('<p class="wbb-loading-msg">Loading\u2026</p>');

		$.post(ajaxUrl, {
			action:     'wbb_admin_get_booking',
			nonce:      nonce,
			booking_id: id,
		}, function (res) {
			if (!res.success) {
				$panel.html('<p class="wbb-status-msg is-error">Failed to load booking.</p>');
				return;
			}
			renderBookingDetail($panel, res.data);
		});
	}

	function formatInclusions(json) {
		if (!json) return '';
		var items;
		try { items = JSON.parse(json); } catch (e) { return ''; }
		if (!items || !items.length) return '';
		// Returned raw \u2014 renderBookingDetail escapes all values before output.
		return items.map(function (it) {
			return it.title + ' \u00d7' + (parseInt(it.qty, 10) || 0);
		}).join(', ');
	}

	function renderBookingDetail($panel, b) {
		var extrasText = formatInclusions(b.inclusions);
		var cur = (str.currency || '$');

		var fields = [
			['Booking ref',    b.booking_ref],
			['Status',         b.status ? b.status.charAt(0).toUpperCase() + b.status.slice(1) : ''],
			['Date',           formatDate(b.booking_date)],
			['Time',           b.time_slot],
			['Duration',       b.duration_hours + ' hours'],
			['Group size',     b.group_size],
			['Boats',          b.boats_requested],
			['Customer name',  b.customer_name],
			['Email',          b.customer_email],
			['Phone',          b.customer_phone],
			['Food & drink',   extrasText || '\u2014'],
			['Extras total',   cur + parseFloat(b.inclusions_total || 0).toFixed(2)],
			['Customer notes', b.notes || '\u2014'],
			['Submitted',      b.created_at],
		];

		var gridHtml = '<div class="wbb-detail-grid">'
			+ fields.map(function (f) {
				return '<div class="wbb-detail-row">'
					+ '<span class="wbb-detail-label">' + escHtml(String(f[0])) + '</span>'
					+ '<span class="wbb-detail-value">' + escHtml(String(f[1] || '\u2014')) + '</span>'
					+ '</div>';
			}).join('')
			+ '</div>';

		var notesHtml = '<div style="margin-top:14px;">'
			+ '<label class="wbb-form-label" style="margin-bottom:4px;display:block;">Staff notes</label>'
			+ '<textarea class="wbb-staff-notes large-text" rows="3" data-id="' + escHtml(String(b.id)) + '">'
			+ escHtml(b.staff_notes || '')
			+ '</textarea>'
			+ '<span class="wbb-notes-status" style="font-size:12px;color:#646970;margin-left:8px;"></span>'
			+ '</div>';

		$panel.html(gridHtml + notesHtml);
	}

	/* ────────────────────────────────────────────────────────────────────────
	 * FOOD & DRINK (MENU) PAGE
	 * ──────────────────────────────────────────────────────────────────────── */

	var menuItems       = [];          // all items (active + inactive)
	var menuActiveCat   = 'food';      // currently shown tab
	var menuMediaFrame;                // wp.media instance (lazy)

	function initMenu() {
		if (!$('.wbb-menu-wrap').length) return;

		loadMenuItems();

		// Tab switching
		$('#wbb-menu-tabs').on('click', '.wbb-menu-tab', function () {
			menuActiveCat = $(this).data('category');
			$('.wbb-menu-tab').removeClass('wbb-menu-tab--active');
			$(this).addClass('wbb-menu-tab--active');
			hideItemForm();
			renderMenuItems();
		});

		// Add / edit / cancel / save / delete
		$('#wbb-add-item').on('click', function () { showItemForm(null); });
		$('#wbb-cancel-item').on('click', hideItemForm);
		$('#wbb-save-item').on('click', saveMenuItem);

		$('#wbb-menu-items-wrap').on('click', '.wbb-item-edit', function (e) {
			e.preventDefault();
			var id = $(this).data('id');
			var item = menuItems.filter(function (it) { return String(it.id) === String(id); })[0];
			if (item) showItemForm(item);
		});

		$('#wbb-menu-items-wrap').on('click', '.wbb-item-delete', function (e) {
			e.preventDefault();
			var id = $(this).data('id');
			if (!window.confirm(str.confirmDeleteItem || 'Delete this item?')) return;
			deleteMenuItem(id);
		});

		// Image picker (WP media)
		$('#wbb-item-image-select').on('click', function (e) {
			e.preventDefault();
			openMenuMedia();
		});
		$('#wbb-item-image-remove').on('click', function (e) {
			e.preventDefault();
			setItemImage(0, '');
		});
	}

	function loadMenuItems() {
		$('#wbb-menu-items-wrap').html('<p class="wbb-loading-msg">Loading items…</p>');
		$.post(ajaxUrl, { action: 'wbb_menu_get_items', nonce: nonce }, function (res) {
			if (!res.success) {
				$('#wbb-menu-items-wrap').html('<p class="wbb-status-msg is-error">Failed to load items.</p>');
				return;
			}
			menuItems = res.data.items || [];
			renderMenuItems();
		});
	}

	function renderMenuItems() {
		var $wrap = $('#wbb-menu-items-wrap');
		var items = menuItems.filter(function (it) { return it.category === menuActiveCat; });

		if (!items.length) {
			$wrap.html('<p style="color:#646970;font-size:13px;">No items in this category yet. Click "Add Item" below.</p>');
			return;
		}

		var cur = str.currency || '$';
		var html = '<ul class="wbb-menu-list" id="wbb-menu-sortable">';
		items.forEach(function (it) {
			var img = it.image_id && it.image_url
				? '<img src="' + escHtml(it.image_url) + '" alt="" class="wbb-menu-list__thumb">'
				: '<span class="wbb-menu-list__thumb wbb-menu-list__thumb--empty" aria-hidden="true"></span>';
			var price = cur + parseFloat(it.price || 0).toFixed(2);
			var inactive = it.active == 1 ? '' : ' <span class="wbb-menu-inactive">(hidden)</span>';
			html += '<li class="wbb-menu-list__row" data-id="' + escHtml(String(it.id)) + '">'
				+ '<span class="wbb-menu-list__handle dashicons dashicons-menu" title="Drag to reorder" aria-hidden="true"></span>'
				+ img
				+ '<span class="wbb-menu-list__body">'
				+ '<strong>' + escHtml(it.title) + '</strong>' + inactive
				+ (it.description ? '<span class="wbb-menu-list__desc">' + escHtml(it.description) + '</span>' : '')
				+ '</span>'
				+ '<span class="wbb-menu-list__price">' + escHtml(price) + '</span>'
				+ '<span class="wbb-menu-list__actions">'
				+ '<a href="#" class="wbb-item-edit" data-id="' + escHtml(String(it.id)) + '">Edit</a>'
				+ ' <span class="wbb-action-sep">|</span> '
				+ '<a href="#" class="wbb-item-delete" data-id="' + escHtml(String(it.id)) + '">Delete</a>'
				+ '</span>'
				+ '</li>';
		});
		html += '</ul>';
		$wrap.html(html);

		// Make sortable
		if ($.fn.sortable) {
			$('#wbb-menu-sortable').sortable({
				handle: '.wbb-menu-list__handle',
				axis: 'y',
				update: function () {
					var ids = $('#wbb-menu-sortable .wbb-menu-list__row').map(function () {
						return $(this).data('id');
					}).get();
					$.post(ajaxUrl, {
						action: 'wbb_menu_reorder',
						nonce:  nonce,
						ordered_ids: ids,
					}, function (res) {
						if (res.success) { menuItems = res.data.items || menuItems; }
					});
				},
			});
		}
	}

	function showItemForm(item) {
		if (item) {
			$('#wbb-item-form-title').text('Edit Item');
			$('#wbb-item-id').val(item.id);
			$('#wbb-item-category').val(item.category);
			$('#wbb-item-title').val(item.title);
			$('#wbb-item-description').val(item.description || '');
			$('#wbb-item-price').val(parseFloat(item.price || 0).toFixed(2));
			$('#wbb-item-active').prop('checked', item.active == 1);
			setItemImage(item.image_id || 0, item.image_url || '');
		} else {
			$('#wbb-item-form-title').text('Add Item');
			$('#wbb-item-id').val(0);
			$('#wbb-item-category').val(menuActiveCat);
			$('#wbb-item-title').val('');
			$('#wbb-item-description').val('');
			$('#wbb-item-price').val('0');
			$('#wbb-item-active').prop('checked', true);
			setItemImage(0, '');
		}
		$('#wbb-item-status').text('').removeClass('is-error');
		$('#wbb-item-form-wrap').removeClass('wbb-hidden');
		$('#wbb-item-title').focus();
	}

	function hideItemForm() {
		$('#wbb-item-form-wrap').addClass('wbb-hidden');
	}

	function setItemImage(id, url) {
		$('#wbb-item-image-id').val(id || 0);
		var $preview = $('#wbb-item-image-preview');
		if (id && url) {
			$preview.find('img').attr('src', url);
			$preview.show();
			$('#wbb-item-image-remove').show();
		} else {
			$preview.hide();
			$('#wbb-item-image-remove').hide();
		}
	}

	function openMenuMedia() {
		if (menuMediaFrame) {
			menuMediaFrame.open();
			return;
		}
		menuMediaFrame = wp.media({
			title: str.selectImage || 'Select image',
			button: { text: str.useImage || 'Use this image' },
			multiple: false,
		});
		menuMediaFrame.on('select', function () {
			var att = menuMediaFrame.state().get('selection').first().toJSON();
			var url = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
			setItemImage(att.id, url);
		});
		menuMediaFrame.open();
	}

	function saveMenuItem() {
		var title = $('#wbb-item-title').val().trim();
		if (!title) { alert('Please enter a title.'); return; }

		var $btn    = $('#wbb-save-item').prop('disabled', true).text(str.saving || 'Saving…');
		var $status = $('#wbb-item-status').text('').removeClass('is-error');

		$.post(ajaxUrl, {
			action:      'wbb_menu_save_item',
			nonce:       nonce,
			item_id:     $('#wbb-item-id').val(),
			category:    $('#wbb-item-category').val(),
			title:       title,
			description: $('#wbb-item-description').val(),
			price:       $('#wbb-item-price').val(),
			image_id:    $('#wbb-item-image-id').val(),
			active:      $('#wbb-item-active').is(':checked') ? 1 : 0,
		}, function (res) {
			$btn.prop('disabled', false).text('Save Item');
			if (res.success) {
				menuItems = res.data.items || [];
				hideItemForm();
				renderMenuItems();
			} else {
				$status.addClass('is-error').text(res.data && res.data.message ? res.data.message : str.errorGeneric);
			}
		});
	}

	function deleteMenuItem(id) {
		$.post(ajaxUrl, {
			action:  'wbb_menu_delete_item',
			nonce:   nonce,
			item_id: id,
		}, function (res) {
			if (res.success) {
				menuItems = res.data.items || [];
				renderMenuItems();
			} else {
				alert(res.data && res.data.message ? res.data.message : str.errorGeneric);
			}
		});
	}

	/* ────────────────────────────────────────────────────────────────────────
	 * UTILITY HELPERS
	 * ──────────────────────────────────────────────────────────────────────── */

	var MONTH_NAMES_LONG = ['January','February','March','April','May','June',
	                        'July','August','September','October','November','December'];

	function formatDate(ds) {
		if (!ds) return '';
		var parts = ds.split('-');
		if (parts.length < 3) return ds;
		var d = parseInt(parts[2], 10);
		var m = parseInt(parts[1], 10) - 1;
		var y = parseInt(parts[0], 10);
		return d + ' ' + (MONTH_NAMES_LONG[m] || parts[1]) + ' ' + y;
	}

	// "9:00 AM" -> "09:00"
	function formatTimeForInput(ts) {
		if (!ts) return '09:00';
		var match = ts.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
		if (!match) return ts;
		var h   = parseInt(match[1], 10);
		var min = match[2];
		var ampm = match[3].toUpperCase();
		if (ampm === 'PM' && h !== 12) h += 12;
		if (ampm === 'AM' && h === 12) h = 0;
		return (h < 10 ? '0' + h : h) + ':' + min;
	}

	// "09:00" -> "9:00 AM"
	function formatTimeDisplay(t) {
		if (!t) return '';
		var parts = t.split(':');
		var h   = parseInt(parts[0], 10);
		var min = parts[1] || '00';
		var ampm = h < 12 ? 'AM' : 'PM';
		if (h === 0)       h = 12;
		else if (h > 12)   h -= 12;
		return h + ':' + min + ' ' + ampm;
	}

	function escHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	/* ────────────────────────────────────────────────────────────────────────
	 * INIT
	 * ──────────────────────────────────────────────────────────────────────── */

	$(document).ready(function () {
		initSettings();
		initAvailability();
		initBookings();
		initMenu();
	});

}(jQuery));
