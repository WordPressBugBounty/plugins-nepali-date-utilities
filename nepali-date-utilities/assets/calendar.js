(function () {
	'use strict';

	if (typeof nduCalendar === 'undefined') {
		return;
	}

	function closestCalendar(el) {
		return el && el.closest ? el.closest('.ndu-post-calendar') : null;
	}

	function replaceCalendar(oldCal, html) {
		var wrap = document.createElement('div');
		wrap.innerHTML = html.trim();
		var next = wrap.firstElementChild;
		if (!next || !oldCal.parentNode) {
			return null;
		}
		oldCal.parentNode.replaceChild(next, oldCal);
		return next;
	}

	function loadMonth(cal, delta) {
		if (!cal || cal.classList.contains('is-loading')) {
			return;
		}
		cal.classList.add('is-loading');

		var body = new URLSearchParams();
		body.set('action', 'ndu_calendar_month');
		body.set('nonce', nduCalendar.nonce);
		body.set('year', cal.getAttribute('data-year') || '');
		body.set('month', cal.getAttribute('data-month') || '');
		body.set('delta', String(delta));
		body.set('lang', cal.getAttribute('data-lang') || 'nepali');
		body.set('show_post_links', cal.getAttribute('data-show-post-links') || '1');
		body.set('accent', cal.getAttribute('data-accent') || '#c62828');

		fetch(nduCalendar.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		})
			.then(function (res) {
				return res.json();
			})
			.then(function (json) {
				if (json && json.success && json.data && json.data.html) {
					replaceCalendar(cal, json.data.html);
				} else {
					cal.classList.remove('is-loading');
				}
			})
			.catch(function () {
				cal.classList.remove('is-loading');
			});
	}

	document.addEventListener('click', function (event) {
		var prev = event.target.closest && event.target.closest('.ndu-cal-prev');
		var next = event.target.closest && event.target.closest('.ndu-cal-next');
		if (!prev && !next) {
			return;
		}
		event.preventDefault();
		var cal = closestCalendar(prev || next);
		if (!cal) {
			return;
		}
		loadMonth(cal, prev ? -1 : 1);
	});
})();
