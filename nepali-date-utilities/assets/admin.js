(function () {
	'use strict';

	if (typeof nduAdmin === 'undefined') {
		return;
	}

	function debounce(fn, wait) {
		var t;
		return function () {
			var ctx = this;
			var args = arguments;
			clearTimeout(t);
			t = setTimeout(function () {
				fn.apply(ctx, args);
			}, wait);
		};
	}

	function postForm(action, data) {
		var body = new URLSearchParams();
		body.set('action', action);
		body.set('nonce', nduAdmin.nonce);
		Object.keys(data).forEach(function (key) {
			body.set(key, data[key]);
		});
		return fetch(nduAdmin.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		}).then(function (res) {
			return res.json();
		});
	}

	function refreshPreview(input) {
		var previewId = input.getAttribute('data-preview');
		var previewEl = previewId ? document.getElementById(previewId) : null;
		if (!previewEl) {
			return;
		}

		var displayMode = document.getElementById('ndu_display_mode');
		var numerals = document.getElementById('ndu_numerals');

		postForm('ndu_preview_format', {
			format: input.value,
			display_mode: displayMode ? displayMode.value : 'nepali',
			numerals: numerals ? numerals.value : 'nepali'
		}).then(function (json) {
			if (json && json.success && json.data && json.data.preview) {
				previewEl.textContent = json.data.preview;
			}
		}).catch(function () {
			/* keep previous preview */
		});
	}

	var debouncedPreview = debounce(function (input) {
		refreshPreview(input);
	}, 300);

	document.querySelectorAll('.ndu-format-input').forEach(function (input) {
		input.addEventListener('input', function () {
			debouncedPreview(input);
		});
	});

	['ndu_display_mode', 'ndu_numerals'].forEach(function (id) {
		var el = document.getElementById(id);
		if (!el) {
			return;
		}
		el.addEventListener('change', function () {
			document.querySelectorAll('.ndu-format-input').forEach(refreshPreview);
		});
	});

	document.querySelectorAll('.ndu-presets').forEach(function (group) {
		var targetId = group.getAttribute('data-target');
		var target = targetId ? document.getElementById(targetId) : null;
		if (!target) {
			return;
		}
		group.querySelectorAll('.ndu-preset').forEach(function (btn) {
			btn.addEventListener('click', function () {
				target.value = btn.getAttribute('data-format') || '';
				target.dispatchEvent(new Event('input', { bubbles: true }));
			});
		});
	});

	document.querySelectorAll('.ndu-converter').forEach(function (box) {
		var btn = box.querySelector('.ndu-convert-btn');
		var resultWrap = box.querySelector('.ndu-converter-result');
		var resultText = box.querySelector('.ndu-result-text');
		var copyBtn = box.querySelector('.ndu-copy-btn');
		if (!btn || !resultText) {
			return;
		}

		btn.addEventListener('click', function () {
			var year = box.querySelector('.ndu-year');
			var month = box.querySelector('.ndu-month');
			var day = box.querySelector('.ndu-day');
			resultWrap.classList.remove('is-error');
			resultText.textContent = nduAdmin.i18n.converting;
			if (copyBtn) {
				copyBtn.hidden = true;
			}

			postForm('ndu_convert_date', {
				direction: box.getAttribute('data-direction') || 'eng_to_nep',
				year: year ? year.value : '',
				month: month ? month.value : '',
				day: day ? day.value : ''
			}).then(function (json) {
				if (json && json.success && json.data && json.data.result) {
					resultText.textContent = json.data.result;
					if (copyBtn) {
						copyBtn.hidden = false;
					}
				} else {
					resultWrap.classList.add('is-error');
					resultText.textContent =
						(json && json.data && json.data.message) || nduAdmin.i18n.error;
				}
			}).catch(function () {
				resultWrap.classList.add('is-error');
				resultText.textContent = nduAdmin.i18n.error;
			});
		});

		if (copyBtn) {
			copyBtn.addEventListener('click', function () {
				var text = resultText.textContent || '';
				if (!text || !navigator.clipboard) {
					return;
				}
				navigator.clipboard.writeText(text).then(function () {
					var original = copyBtn.textContent;
					copyBtn.textContent = nduAdmin.i18n.copied;
					setTimeout(function () {
						copyBtn.textContent = original;
					}, 1200);
				});
			});
		}
	});
})();
