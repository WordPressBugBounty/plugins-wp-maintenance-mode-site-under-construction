/**
 * Maintenance mode editor.
 *
 * Two-way binding between the settings sidebar and the live preview, inline
 * editing, the template library, the media pickers, the notice quarantine tray
 * and the asynchronous save. No dependencies beyond wp.media, which is core.
 */
(function () {
	'use strict';

	var data = window.mmSucPData || {};
	var root = document.getElementById('mm-suc-p-admin');

	if (!root) {
		return;
	}

	/**
	 * The page style contract, client side.
	 *
	 * The same five properties are declared in assets/css/frontend.css, injected
	 * by template.php and defaulted in maintenance.php. The background is the
	 * one property the browser cannot resolve on its own - it needs an
	 * attachment or template URL - so changing it asks the server for a fresh
	 * render instead of being written here.
	 */
	var VARS = {
		accent_color: '--mm-suc-p-page-accent',
		text_color: '--mm-suc-p-page-ink',
		overlay_opacity: '--mm-suc-p-page-overlay',
		glass_strength: '--mm-suc-p-page-blur',
		background: '--mm-suc-p-page-bg-image'
	};

	/** Fields whose change alters the markup, so the server re-renders. */
	var SERVER_FIELDS = ['template', 'background', 'custom_background_id', 'logo_id', 'countdown', 'contact_enabled', 'end_datetime'];

	/**
	 * Where each preview part is edited.
	 *
	 * `panel` is a sidebar accordion; `tool` is a group on the toolbar above the
	 * preview. Every entry has to resolve to a real control - a dead
	 * click-to-navigate target teaches the owner the feature does not work.
	 */
	var NAV_OF_PART = {
		logo: { panel: 'content' },
		eyebrow: { panel: 'content' },
		headline: { panel: 'content' },
		message: { panel: 'content' },
		countdown: { panel: 'countdown' },
		contact_button: { panel: 'contact' },
		background: { tool: 'background' }
	};

	var preview = root.querySelector('[data-mm-suc-p-preview]');
	var saveBtn = document.getElementById('mm-suc-p-save');
	var pill = document.getElementById('mm-suc-p-pill');
	var toasts = document.getElementById('mm-suc-p-toasts');

	var savedEnabled = !!(data.options && data.options.enabled);
	var renderTimer = null;
	var countdownTimer = null;

	initTray();
	initRatingBanner();

	if (preview) {
		initPanels();
		initToolbarGroups();
		initFields();
		initInlineEditing();
		initLibrary();
		initPreset();
		initMedia();
		initViewports();
		initPalette();
		initSheetPreview();
		initSave();
		refreshDerived();
		startPreviewCountdown();
	} else {
		initMessagesScreen();
	}

	/* ------------------------------------------------------------------ helpers */

	/**
	 * A localised string.
	 *
	 * @param {string} key      String key.
	 * @param {string} fallback Default text.
	 * @return {string} The string.
	 */
	function text(key, fallback) {
		return (data.strings && data.strings[key]) ? data.strings[key] : fallback;
	}

	/**
	 * Replace the first %s placeholder.
	 *
	 * @param {string} template Format string.
	 * @param {*}      value    Replacement.
	 * @return {string} Formatted string.
	 */
	function format(template, value) {
		return String(template).replace('%s', String(value));
	}

	/**
	 * Every control bound to an option field.
	 *
	 * @return {NodeList} The controls.
	 */
	function controls() {
		return root.querySelectorAll('[data-mm-suc-p-field]');
	}

	/**
	 * The current sidebar values, as the shape the server sanitizes.
	 *
	 * @return {Object} Settings.
	 */
	function collect() {
		var out = { bypass_roles: [] };

		Array.prototype.forEach.call(controls(), function (node) {
			var field = node.getAttribute('data-mm-suc-p-field');

			if (field === 'bypass_roles') {
				if (node.checked) {
					out.bypass_roles.push(node.value);
				}
				return;
			}

			if (node.type === 'checkbox') {
				out[field] = node.checked ? 1 : 0;
				return;
			}

			if (node.type === 'radio') {
				if (node.checked) {
					out[field] = node.value;
				}
				return;
			}

			out[field] = node.value;
		});

		return out;
	}

	/**
	 * The control bound to a field, preferring a checked radio.
	 *
	 * @param {string} field Field name.
	 * @return {Element|null} The control.
	 */
	function controlFor(field) {
		var nodes = root.querySelectorAll('[data-mm-suc-p-field="' + field + '"]');

		if (!nodes.length) {
			return null;
		}

		for (var i = 0; i < nodes.length; i++) {
			if (nodes[i].type === 'radio' && nodes[i].checked) {
				return nodes[i];
			}
		}

		return nodes[0];
	}

	/**
	 * The page root inside the preview.
	 *
	 * @return {Element|null} The page element.
	 */
	function pageRoot() {
		return preview ? preview.querySelector('.mm-suc-p-page') : null;
	}

	/* ------------------------------------------------------------------ panels */

	/**
	 * Accordion panels. Independent, per session, never stored.
	 *
	 * @return {void}
	 */
	function initPanels() {
		var toggles = root.querySelectorAll('.mm-suc-p-panel-toggle');

		Array.prototype.forEach.call(toggles, function (toggle) {
			toggle.addEventListener('click', function () {
				var open = toggle.getAttribute('aria-expanded') === 'true';
				setPanel(toggle, !open);
			});
		});
	}

	/**
	 * Open or close one panel.
	 *
	 * @param {Element} toggle The panel header control.
	 * @param {boolean} open   Desired state.
	 * @return {void}
	 */
	function setPanel(toggle, open) {
		var body = document.getElementById(toggle.getAttribute('aria-controls'));

		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

		if (body) {
			body.hidden = !open;
		}
	}

	/**
	 * Open a panel by name and optionally focus its first control.
	 *
	 * Scroll, expand, then focus - a focus call during a smooth scroll cancels
	 * the scroll in most browsers.
	 *
	 * @param {string}  name       Panel name.
	 * @param {boolean} focusFirst Whether to move focus into the panel.
	 * @return {void}
	 */
	function openPanel(name, focusFirst) {
		var panel = root.querySelector('[data-mm-suc-p-panel="' + name + '"]');

		if (!panel) {
			return;
		}

		var toggle = panel.querySelector('.mm-suc-p-panel-toggle');

		if (!toggle) {
			return;
		}

		panel.scrollIntoView({ block: 'nearest' });
		setPanel(toggle, true);

		if (!focusFirst) {
			return;
		}

		window.setTimeout(function () {
			var body = document.getElementById(toggle.getAttribute('aria-controls'));
			var first = body ? body.querySelector('input:not([type="hidden"]):not([disabled]), textarea, select, button') : null;

			if (first) {
				first.focus();
			}
		}, 120);
	}

	/* ------------------------------------------------------------------ toolbar groups */

	/**
	 * Horizontally collapsible toolbar groups.
	 *
	 * Single-expanded: opening one group collapses the others.
	 *
	 * @return {void}
	 */
	function initToolbarGroups() {
		var groups = root.querySelectorAll('.mm-suc-p-toolbar-group');

		groups.forEach(function (group) {
			var toggle = group.querySelector('.mm-suc-p-toolbar-toggle');

			if (!toggle) {
				return;
			}

			toggle.addEventListener('click', function () {
				setToolbarGroup(group, true);
			});
		});
	}

	/**
	 * Open a toolbar group and collapse all other toolbar groups.
	 *
	 * @param {Element} targetGroup The .mm-suc-p-toolbar-group to open.
	 * @param {boolean} open        Whether to open.
	 * @return {void}
	 */
	function setToolbarGroup(targetGroup, open) {
		var groups = root.querySelectorAll('.mm-suc-p-toolbar-group');

		groups.forEach(function (group) {
			var toggle = group.querySelector('.mm-suc-p-toolbar-toggle');
			var pane = group.querySelector('.mm-suc-p-toolbar-pane');
			var isTarget = (group === targetGroup);
			var shouldBeOpen = isTarget ? !!open : false;

			if (shouldBeOpen) {
				group.classList.add('is-open');
				if (toggle) {
					toggle.setAttribute('aria-expanded', 'true');
				}
				if (pane) {
					pane.removeAttribute('hidden');
					pane.setAttribute('aria-hidden', 'false');
				}
			} else {
				group.classList.remove('is-open');
				if (toggle) {
					toggle.setAttribute('aria-expanded', 'false');
				}
				if (pane) {
					pane.setAttribute('aria-hidden', 'true');
					window.setTimeout(function () {
						if (!group.classList.contains('is-open')) {
							pane.setAttribute('hidden', '');
						}
					}, 1000);
				}
			}
		});
	}

	/**
	 * Bring a toolbar control into view, and optionally focus it.
	 *
	 * @param {string}  name       Value of data-mm-suc-p-tool.
	 * @param {boolean} focusFirst Whether to move focus into the group.
	 * @return {void}
	 */
	function focusTool(name, focusFirst) {
		var tool = root.querySelector('[data-mm-suc-p-tool="' + name + '"]');

		if (!tool) {
			return;
		}

		var group = tool.closest('.mm-suc-p-toolbar-group');
		if (group && !group.classList.contains('is-open')) {
			setToolbarGroup(group, true);
		}

		tool.scrollIntoView({ block: 'nearest' });

		if (!focusFirst) {
			return;
		}

		window.setTimeout(function () {
			var first = tool.querySelector('input:not([type="hidden"]):not([disabled]), select, textarea, button');

			if (first) {
				first.focus();
			}
		}, 120);
	}

	/* ------------------------------------------------------------------ notice tray */

	/**
	 * Move foreign admin notices into the quarantine tray.
	 *
	 * Nodes are moved, never copied and never removed, so their own dismiss
	 * controls and scripts keep working.
	 *
	 * @return {void}
	 */
	function initTray() {
		var tray = document.getElementById('mm-suc-p-tray');

		if (!tray) {
			return;
		}

		var body = document.getElementById('mm-suc-p-tray-body');
		var counter = tray.querySelector('[data-mm-suc-p-tray-count]');
		var toggle = tray.querySelector('.mm-suc-p-tray-toggle');
		var target = document.getElementById('wpbody-content') || document.body;
		var observer = null;
		var scanning = false;

		toggle.addEventListener('click', function () {
			var open = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
			body.hidden = open;
		});

		sweep();

		if (window.MutationObserver) {
			observer = new window.MutationObserver(function () {
				sweep();
			});

			watch();
		}

		function watch() {
			if (observer) {
				observer.observe(target, { childList: true, subtree: true });
			}
		}

		/**
		 * Move any foreign notice into the tray.
		 *
		 * The observer is detached for the duration: every write below is itself
		 * a mutation of the node being watched, and re-entering here would loop
		 * forever.
		 */
		function sweep() {
			if (scanning) {
				return;
			}

			scanning = true;

			if (observer) {
				observer.disconnect();
			}

			var found = document.querySelectorAll('#wpbody-content .notice, #wpbody-content .updated, #wpbody-content .error');

			Array.prototype.forEach.call(found, function (node) {
				if (root.contains(node) || body.contains(node)) {
					return;
				}

				body.appendChild(node);
			});

			var count = body.children.length;

			if (counter && counter.textContent !== String(count)) {
				counter.textContent = String(count);
			}

			if (tray.hidden !== (count === 0)) {
				tray.hidden = count === 0;
			}

			watch();
			scanning = false;
		}
	}

	/* ------------------------------------------------------------------ binding */

	/**
	 * Bind every sidebar control to the preview.
	 *
	 * @return {void}
	 */
	function initFields() {
		Array.prototype.forEach.call(controls(), bindField);

		// Colour swatch and hex text field, each updating the other.
		var hexes = root.querySelectorAll('[data-mm-suc-p-hex]');

		Array.prototype.forEach.call(hexes, function (hex) {
			var field = hex.getAttribute('data-mm-suc-p-hex');
			var swatch = controlFor(field);

			hex.addEventListener('input', function () {
				var value = hex.value.trim();

				if (/^#[0-9a-fA-F]{6}$/.test(value) && swatch) {
					swatch.value = value;
					onFieldChange(field, swatch);
				}
			});

			if (swatch) {
				swatch.addEventListener('input', function () {
					hex.value = swatch.value;
				});
			}
		});

		// Range outputs.
		var outputs = root.querySelectorAll('[data-mm-suc-p-output]');

		Array.prototype.forEach.call(outputs, function (output) {
			var field = output.getAttribute('data-mm-suc-p-output');
			var input = controlFor(field);

			if (!input) {
				return;
			}

			input.addEventListener('input', function () {
				output.textContent = input.value;
			});
		});
	}

	/**
	 * Bind one control to the preview.
	 *
	 * Separate from initFields() because a control can arrive later: saving a
	 * preset adds a template option to a list that was bound on load.
	 *
	 * @param {Element} node The control.
	 * @return {void}
	 */
	function bindField(node) {
		var field = node.getAttribute('data-mm-suc-p-field');
		var event = (node.type === 'checkbox' || node.type === 'radio' || node.tagName === 'SELECT') ? 'change' : 'input';

		node.addEventListener(event, function () {
			onFieldChange(field, node);
		});
	}

	/**
	 * React to one field changing.
	 *
	 * @param {string}  field Field name.
	 * @param {Element} node  The control.
	 * @return {void}
	 */
	function onFieldChange(field, node) {
		markDirty();

		if (field === 'enabled' || field === 'auto_disable') {
			updatePill();
			updateConsequence();
			updateDuration();
			return;
		}

		if (field === 'layout') {
			applyLayout(node.value);
			return;
		}

		if (VARS[field] && field !== 'background') {
			applyVars();

			if (field === 'text_color' || field === 'accent_color') {
				updateContrast();
			}

			return;
		}

		if (SERVER_FIELDS.indexOf(field) !== -1) {
			if (field === 'end_datetime') {
				refreshDerived();
			}

			requestRender();
			return;
		}

		// A plain editable part: write it straight into the preview.
		writePart(field, node.value);
	}

	/**
	 * Write the style contract onto the preview page root.
	 *
	 * @return {void}
	 */
	function applyVars() {
		var page = pageRoot();

		if (!page) {
			return;
		}

		var values = collect();

		Object.keys(VARS).forEach(function (field) {
			if (field === 'background') {
				return;
			}

			var value = values[field];

			if (typeof value === 'undefined') {
				return;
			}

			if (field === 'overlay_opacity') {
				value = (parseInt(value, 10) || 0) / 100;
			}

			if (field === 'glass_strength') {
				value = (parseInt(value, 10) || 0) + 'px';
			}

			page.style.setProperty(VARS[field], String(value));
		});
	}

	/**
	 * Swap the layout modifier class. Layouts are CSS; the markup never changes.
	 *
	 * @param {string} layout Layout key.
	 * @return {void}
	 */
	function applyLayout(layout) {
		var page = pageRoot();

		if (!page) {
			return;
		}

		['centered', 'left', 'minimal', 'split'].forEach(function (key) {
			page.classList.toggle('mm-suc-p-page--' + key, key === layout);
		});
	}

	/**
	 * Write a value into its preview part, without disturbing the caret.
	 *
	 * @param {string} field Field name.
	 * @param {string} value New value.
	 * @return {void}
	 */
	function writePart(field, value) {
		var page = pageRoot();

		if (!page) {
			return;
		}

		var part = page.querySelector('[data-mm-suc-p-part="' + field + '"]');

		if (!part || part === document.activeElement) {
			return;
		}

		if (part.textContent !== value) {
			part.textContent = value;
		}
	}

	/* ------------------------------------------------------------------ inline editing */

	/**
	 * Bind the contenteditable parts inside the preview.
	 *
	 * @return {void}
	 */
	function initInlineEditing() {
		var page = pageRoot();

		if (!page) {
			return;
		}

		var parts = page.querySelectorAll('[data-mm-suc-p-part]');

		Array.prototype.forEach.call(parts, function (part) {
			var field = part.getAttribute('data-mm-suc-p-part');
			var input = controlFor(field);
			var editable = part.getAttribute('contenteditable') !== null;

			part.addEventListener('click', function () {
				var nav = NAV_OF_PART[field];

				if (!nav) {
					return;
				}

				// Editable parts keep focus in the preview; the target just opens.
				if (nav.panel) {
					openPanel(nav.panel, !editable);
				} else if (nav.tool) {
					focusTool(nav.tool, !editable);
				}
			});

			if (!editable || !input) {
				if (!editable) {
					part.addEventListener('keydown', function (event) {
						if (event.key === 'Enter' || event.key === ' ') {
							event.preventDefault();
							part.click();
						}
					});
				}

				return;
			}

			var max = parseInt(part.getAttribute('data-mm-suc-p-max') || '0', 10);
			var multiline = part.getAttribute('aria-multiline') === 'true';

			part.addEventListener('input', function () {
				var value = part.textContent || '';

				if (max && value.length > max) {
					value = value.slice(0, max);
					part.textContent = value;
					placeCaretAtEnd(part);
				}

				if (input.value !== value) {
					input.value = value;
					markDirty();
				}
			});

			part.addEventListener('keydown', function (event) {
				if (event.key === 'Escape') {
					event.preventDefault();
					part.textContent = input.value;
					part.blur();
					return;
				}

				if (event.key === 'Enter' && !multiline) {
					event.preventDefault();
					part.blur();
				}
			});

			part.addEventListener('paste', function (event) {
				event.preventDefault();

				var clipboard = event.clipboardData || window.clipboardData;
				var plain = clipboard ? clipboard.getData('text/plain') : '';

				if (!multiline) {
					plain = plain.replace(/[\r\n]+/g, ' ');
				}

				document.execCommand('insertText', false, plain);
			});

			part.addEventListener('blur', function () {
				input.value = part.textContent || '';
			});
		});
	}

	/**
	 * Put the caret at the end of an element after a programmatic write.
	 *
	 * @param {Element} node The editable element.
	 * @return {void}
	 */
	function placeCaretAtEnd(node) {
		var range = document.createRange();
		var selection = window.getSelection();

		range.selectNodeContents(node);
		range.collapse(false);
		selection.removeAllRanges();
		selection.addRange(range);
	}

	/* ------------------------------------------------------------------ library */

	/**
	 * The template library: a vertical list of radio options.
	 *
	 * The list needs no scroll controls of its own - a column scrolls with the
	 * wheel, and arrow keys move between native radios and bring the focused one
	 * into view. All this adds is bringing the stored choice into view on load
	 * and handing the chosen template's palette to the colour controls.
	 *
	 * @return {void}
	 */
	function initLibrary() {
		var wrap = root.querySelector('[data-mm-suc-p-library]');

		if (!wrap) {
			return;
		}

		var radios = wrap.querySelectorAll('[data-mm-suc-p-field="template"]');

		Array.prototype.forEach.call(radios, function (radio) {
			bindTemplateRadio(radio);

			if (radio.checked) {
				window.setTimeout(function () {
					scrollTemplateIntoView(radio);
				}, 0);
			}
		});
	}

	/**
	 * Bind one template option.
	 *
	 * @param {Element} radio The radio input.
	 * @return {void}
	 */
	function bindTemplateRadio(radio) {
		radio.addEventListener('change', function () {
			if (radio.checked) {
				applyTemplatePalette(radio.value);
			}
		});
	}

	/**
	 * Bring a template row into view inside the list.
	 *
	 * @param {Element} radio The radio input.
	 * @return {void}
	 */
	function scrollTemplateIntoView(radio) {
		var rail = root.querySelector('[data-mm-suc-p-rail-track]');
		var row = radio.closest ? radio.closest('.mm-suc-p-tpl') : null;

		if (rail && row) {
			rail.scrollTop = Math.max(0, row.offsetTop - rail.offsetTop);
		}
	}

	/**
	 * Seed the four palette controls from the chosen template.
	 *
	 * The template proposes; the site owner disposes - every value stays
	 * editable afterwards.
	 *
	 * @param {string} slug Template slug.
	 * @return {void}
	 */
	function applyTemplatePalette(slug) {
		var list = data.templates || [];
		var found = null;

		list.forEach(function (item) {
			if (item.slug === slug) {
				found = item;
			}
		});

		if (!found || !found.palette) {
			return;
		}

		if (found.layout) {
			// A preset records the layout it was saved with.
			var layoutInput = root.querySelector('[data-mm-suc-p-field="layout"][value="' + found.layout + '"]');

			if (layoutInput && !layoutInput.checked) {
				layoutInput.checked = true;
				applyLayout(found.layout);
			}
		}

		Object.keys(found.palette).forEach(function (field) {
			var input = controlFor(field);

			if (!input) {
				return;
			}

			input.value = found.palette[field];

			var hex = root.querySelector('[data-mm-suc-p-hex="' + field + '"]');

			if (hex) {
				hex.value = found.palette[field];
			}

			var output = root.querySelector('[data-mm-suc-p-output="' + field + '"]');

			if (output) {
				output.textContent = found.palette[field];
			}
		});

		applyVars();
		updateContrast();
		toast('info', text('templateApply', 'Template applied.'), true);
	}

	/* ------------------------------------------------------------------ presets */

	/**
	 * Save the current design as a new template.
	 *
	 * The server writes a real template folder and hands back both the payload
	 * the editor needs and the markup for its row, so the list gains the preset
	 * without a reload and without the row being built twice in two languages.
	 *
	 * @return void
	 */
	function initPreset() {
		var button = document.getElementById('mm-suc-p-save-preset');
		var field = document.getElementById('mm-suc-p-preset-name');

		if (!button) {
			return;
		}

		button.addEventListener('click', function () {
			var label = button.querySelector('.mm-suc-p-preset-label');
			var original = label ? label.textContent : '';

			button.disabled = true;
			button.setAttribute('aria-busy', 'true');

			if (label) {
				label.textContent = text('presetSaving', 'Saving…');
			}

			var body = new window.URLSearchParams();
			body.append('action', data.presetAction);
			body.append('nonce', data.nonce || '');
			body.append('name', field ? field.value : '');
			body.append('settings', JSON.stringify(collect()));

			window.fetch(data.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			}).then(function (response) {
				return response.json();
			}).then(function (result) {
				restore();

				if (!result || !result.success) {
					toast('error', (result && result.data && result.data.message) || text('presetFailed', 'Could not save the preset.'), false);
					return;
				}

				addPreset(result.data);

				if (field) {
					field.value = '';
				}

				toast('success', result.data.message, true);
			}).catch(function () {
				restore();
				toast('error', text('presetFailed', 'Could not save the preset. Check your connection and try again.'), false);
			});

			function restore() {
				button.disabled = false;
				button.removeAttribute('aria-busy');

				if (label) {
					label.textContent = original || text('presetSave', 'Save as preset');
				}
			}
		});
	}

	/**
	 * Put a newly saved preset into the library and select it.
	 *
	 * @param {Object} payload The endpoint's data.
	 * @return {void}
	 */
	function addPreset(payload) {
		var rail = root.querySelector('[data-mm-suc-p-rail-track]');

		if (!rail || !payload.row) {
			return;
		}

		data.templates = (data.templates || []).concat([payload.template]);

		rail.insertAdjacentHTML('beforeend', payload.row);

		var rows = rail.querySelectorAll('.mm-suc-p-tpl');
		var row = rows[rows.length - 1];
		var radio = row.querySelector('[data-mm-suc-p-field="template"]');

		if (!radio) {
			return;
		}

		Array.prototype.forEach.call(root.querySelectorAll('[data-mm-suc-p-field="template"]'), function (other) {
			if (other !== radio) {
				other.checked = false;
			}
		});

		radio.checked = true;
		bindField(radio);
		bindTemplateRadio(radio);
		scrollTemplateIntoView(radio);
		markDirty();
		requestRender();
	}

	/* ------------------------------------------------------------------ server render */

	/**
	 * Ask the server for a fresh preview, debounced.
	 *
	 * @return {void}
	 */
	function requestRender() {
		window.clearTimeout(renderTimer);
		renderTimer = window.setTimeout(render, 250);
	}

	/**
	 * Re-render the preview with the current, unsaved settings.
	 *
	 * @return {void}
	 */
	function render() {
		if (!preview) {
			return;
		}

		post(data.viewAction, collect()).then(function (result) {
			if (!result || !result.success || !result.data || !result.data.html) {
				return;
			}

			ensureTemplateStyle(result.data.styleId, result.data.styleUrl);

			preview.innerHTML = result.data.html;

			applyVars();
			applyLayout((collect().layout) || 'centered');
			initInlineEditing();
			startPreviewCountdown();
			syncSheetPreview();
			updateContrast();

			// The preview markup was replaced: every node the map measured and
			// every node it outlined is detached.
			refreshPalette();
		}).catch(function () {
			// A failed preview never blocks editing; the sidebar is still the truth.
		});
	}

	/**
	 * Make sure the active template's stylesheet is present.
	 *
	 * @param {string} id  Stylesheet element id.
	 * @param {string} url Stylesheet URL.
	 * @return {void}
	 */
	function ensureTemplateStyle(id, url) {
		if (!id || !url || document.getElementById(id)) {
			return;
		}

		var link = document.createElement('link');
		link.rel = 'stylesheet';
		link.id = id;
		link.href = url;
		document.head.appendChild(link);
	}

	/* ------------------------------------------------------------------ media */

	/**
	 * Wire the logo and background media pickers to core's media modal.
	 *
	 * @return {void}
	 */
	function initMedia() {
		var groups = root.querySelectorAll('[data-mm-suc-p-media]');

		Array.prototype.forEach.call(groups, function (group) {
			var kind = group.getAttribute('data-mm-suc-p-media');
			var choose = group.querySelector('[data-mm-suc-p-media-choose]');
			var clear = group.querySelector('[data-mm-suc-p-media-clear]');
			var value = group.querySelector('[data-mm-suc-p-media-value]');
			var thumb = group.querySelector('[data-mm-suc-p-media-thumb]');
			var frame = null;

			if (choose) {
				choose.addEventListener('click', function () {
					if (!window.wp || !window.wp.media) {
						return;
					}

					if (!frame) {
						frame = window.wp.media({
							title: kind === 'logo' ? text('chooseLogo', 'Choose a logo') : text('chooseImage', 'Choose a background image'),
							button: { text: text('use', 'Use this image') },
							library: { type: 'image' },
							multiple: false
						});

						frame.on('select', function () {
							var attachment = frame.state().get('selection').first().toJSON();

							value.value = attachment.id;

							if (thumb) {
								thumb.textContent = '';
								var img = document.createElement('img');
								img.src = (attachment.sizes && attachment.sizes.medium) ? attachment.sizes.medium.url : attachment.url;
								img.alt = '';
								thumb.appendChild(img);
							}

							if (kind === 'background') {
								var custom = root.querySelector('[data-mm-suc-p-field="background"][value="custom"]');

								if (custom) {
									custom.checked = true;
								}
							}

							markDirty();
							requestRender();
							choose.focus();
						});
					}

					frame.open();
				});
			}

			if (clear) {
				clear.addEventListener('click', function () {
					value.value = '0';

					if (kind === 'background') {
						var fromTemplate = root.querySelector('[data-mm-suc-p-field="background"][value="template"]');

						if (fromTemplate) {
							fromTemplate.checked = true;
						}
					}

					markDirty();
					requestRender();
				});
			}
		});
	}

	/* ------------------------------------------------------------------ viewports */

	/**
	 * The device switcher sets the frame width in CSS pixels, never a transform.
	 *
	 * @return {void}
	 */
	function initViewports() {
		var radios = root.querySelectorAll('[data-mm-suc-p-viewport]');
		var readout = root.querySelector('[data-mm-suc-p-width]');

		Array.prototype.forEach.call(radios, function (radio) {
			radio.addEventListener('change', function () {
				if (radio.checked) {
					apply(radio.value);
				}
			});

			if (radio.checked) {
				apply(radio.value);
			}
		});

		function apply(width) {
			if (!preview) {
				return;
			}

			preview.style.maxWidth = width;

			if (readout) {
				readout.textContent = width === '100%'
					? Math.round(preview.clientWidth) + 'px'
					: width;
			}
		}
	}

	/* ------------------------------------------------------------------ sheet preview */

	/**
	 * A preview aid: show the contact sheet without saving anything.
	 *
	 * @return {void}
	 */
	function initSheetPreview() {
		var toggle = root.querySelector('[data-mm-suc-p-sheet-preview]');

		if (!toggle) {
			return;
		}

		toggle.addEventListener('change', syncSheetPreview);
	}

	/**
	 * Apply the sheet preview toggle to the current preview markup.
	 *
	 * @return {void}
	 */
	function syncSheetPreview() {
		var toggle = root.querySelector('[data-mm-suc-p-sheet-preview]');
		var page = pageRoot();

		if (!toggle || !page) {
			return;
		}

		var sheet = page.querySelector('.mm-suc-p-page-sheet');

		if (sheet) {
			sheet.hidden = !toggle.checked;
		}
	}

	/* ------------------------------------------------------------------ derived readouts */

	/**
	 * Recompute the schedule prose, contrast note, and master consequence description.
	 *
	 * @return {void}
	 */
	function refreshDerived() {
		updateDuration();
		updateContrast();
		updateConsequence();
	}

	/**
	 * Say, in prose, how far away the end time is.
	 *
	 * @return {void}
	 */
	function updateDuration() {
		var slot = root.querySelector('[data-mm-suc-p-duration]');
		var input = controlFor('end_datetime');
		var enabledInput = controlFor('enabled');
		var isEnabled = enabledInput ? enabledInput.checked : savedEnabled;

		if (!slot || !input) {
			return;
		}

		if (!input.value) {
			slot.textContent = text('durationNone', 'No end time set, so no countdown is shown.');
			return;
		}

		var target = Date.parse(input.value);

		if (isNaN(target)) {
			slot.textContent = '';
			return;
		}

		var seconds = Math.floor((target - Date.now()) / 1000);

		if (seconds <= 0) {
			slot.textContent = isEnabled
				? text('durationPastWarning', 'That time has passed. Maintenance mode will not run and your site will remain live.')
				: text('durationPast', 'That time has passed - the countdown reads zero.');
			return;
		}

		var days = Math.floor(seconds / 86400);
		var hours = Math.floor((seconds % 86400) / 3600);
		var minutes = Math.floor((seconds % 3600) / 60);
		var parts = [];

		if (days) {
			parts.push(format(text(days === 1 ? 'day' : 'days', '%s days'), days));
		}

		if (hours) {
			parts.push(format(text(hours === 1 ? 'hour' : 'hours', '%s hours'), hours));
		}

		if (!days) {
			parts.push(format(text(minutes === 1 ? 'minute' : 'minutes', '%s minutes'), minutes));
		}

		slot.textContent = format(text('durationFrom', '%s from now'), parts.join(', '));
	}

	/**
	 * Keep the consequence description under the master switch accurate.
	 *
	 * @return {void}
	 */
	function updateConsequence() {
		var consequence = document.getElementById('mm-suc-p-enabled-consequence');
		var enabledInput = controlFor('enabled');
		var endInput = controlFor('end_datetime');

		if (!consequence) {
			return;
		}

		var isEnabled = enabledInput ? enabledInput.checked : savedEnabled;
		var endVal = endInput ? endInput.value : '';
		var isPast = false;

		if (endVal) {
			var target = Date.parse(endVal);
			if (!isNaN(target) && target <= Date.now()) {
				isPast = true;
			}
		}

		if (isEnabled && isPast) {
			consequence.textContent = text('consequencePastDate', 'Maintenance mode will not run and your site is still live because your chosen date is older than now.');
			consequence.classList.add('mm-suc-p-consequence--warning');
		} else if (isEnabled) {
			consequence.textContent = text('consequenceMaintenance', 'Visitors see the maintenance page. You and other administrators still see the site.');
			consequence.classList.remove('mm-suc-p-consequence--warning');
		} else {
			consequence.textContent = text('consequenceLive', 'Site is live. Visitors see the website normally.');
			consequence.classList.remove('mm-suc-p-consequence--warning');
		}
	}

	/**
	 * Warn, in place, when the chosen text colour is hard to read.
	 *
	 * The comparison is against the shaded card surface the template produces,
	 * not the raw photograph. It warns; it never corrects.
	 *
	 * @return {void}
	 */
	function updateContrast() {
		var slot = root.querySelector('[data-mm-suc-p-contrast]');
		var page = pageRoot();
		var input = controlFor('text_color');

		if (!slot || !page || !input) {
			return;
		}

		var styles = window.getComputedStyle(page);
		var veil = parseColor(styles.getPropertyValue('--mm-suc-p-page-veil'));
		var card = parseColor(styles.getPropertyValue('--mm-suc-p-page-card'));
		var ink = parseColor(input.value);

		if (!veil || !ink) {
			slot.textContent = '';
			return;
		}

		var surface = card ? blend(card, veil) : veil;
		var ratio = Math.round(contrast(ink, surface) * 10) / 10;

		if (ratio < 4.5) {
			slot.textContent = format(text('contrastLow', 'Low contrast (%s:1). Some visitors will not be able to read this.'), ratio);
			slot.classList.add('mm-suc-p-help--warning');
			return;
		}

		slot.textContent = format(text('contrastOk', 'Contrast %s:1 against this background.'), ratio);
		slot.classList.remove('mm-suc-p-help--warning');
	}

	/**
	 * Parse a hex or rgb/rgba colour into channels plus alpha.
	 *
	 * @param {string} value CSS colour.
	 * @return {Object|null} { r, g, b, a }
	 */
	function parseColor(value) {
		if (!value) {
			return null;
		}

		value = String(value).trim();

		var hex = value.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);

		if (hex) {
			var digits = hex[1];

			if (digits.length === 3) {
				digits = digits[0] + digits[0] + digits[1] + digits[1] + digits[2] + digits[2];
			}

			return {
				r: parseInt(digits.slice(0, 2), 16),
				g: parseInt(digits.slice(2, 4), 16),
				b: parseInt(digits.slice(4, 6), 16),
				a: 1
			};
		}

		var rgb = value.match(/rgba?\(\s*([0-9.]+)[,\s]+([0-9.]+)[,\s]+([0-9.]+)(?:[,/\s]+([0-9.]+))?\s*\)/i);

		if (rgb) {
			return {
				r: parseFloat(rgb[1]),
				g: parseFloat(rgb[2]),
				b: parseFloat(rgb[3]),
				a: typeof rgb[4] === 'undefined' ? 1 : parseFloat(rgb[4])
			};
		}

		return null;
	}

	/**
	 * Composite a translucent colour over an opaque one.
	 *
	 * @param {Object} top    Foreground with alpha.
	 * @param {Object} bottom Opaque background.
	 * @return {Object} The composite.
	 */
	function blend(top, bottom) {
		var a = typeof top.a === 'number' ? top.a : 1;

		return {
			r: Math.round(top.r * a + bottom.r * (1 - a)),
			g: Math.round(top.g * a + bottom.g * (1 - a)),
			b: Math.round(top.b * a + bottom.b * (1 - a)),
			a: 1
		};
	}

	/**
	 * WCAG relative luminance.
	 *
	 * @param {Object} color Opaque colour.
	 * @return {number} Luminance.
	 */
	function luminance(color) {
		var channels = [color.r, color.g, color.b].map(function (value) {
			var scaled = value / 255;
			return scaled <= 0.03928 ? scaled / 12.92 : Math.pow((scaled + 0.055) / 1.055, 2.4);
		});

		return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
	}

	/**
	 * WCAG contrast ratio between two opaque colours.
	 *
	 * @param {Object} one Colour.
	 * @param {Object} two Colour.
	 * @return {number} Ratio.
	 */
	function contrast(one, two) {
		var a = luminance(one);
		var b = luminance(two);
		var high = Math.max(a, b);
		var low = Math.min(a, b);

		return (high + 0.05) / (low + 0.05);
	}

	/* ------------------------------------------------------------------ preview countdown */

	/**
	 * Tick the preview countdown against the value being edited.
	 *
	 * @return {void}
	 */
	function startPreviewCountdown() {
		window.clearInterval(countdownTimer);

		var page = pageRoot();

		if (!page) {
			return;
		}

		var grid = page.querySelector('[data-mm-suc-p-part="countdown"]');

		if (!grid) {
			return;
		}

		countdownTimer = window.setInterval(tick, 1000);
		tick();

		function tick() {
			var input = controlFor('end_datetime');
			var target = input && input.value ? Date.parse(input.value) : NaN;

			if (isNaN(target)) {
				return;
			}

			var remaining = Math.max(0, Math.floor((target - Date.now()) / 1000));
			var units = {
				days: Math.floor(remaining / 86400),
				hours: Math.floor((remaining % 86400) / 3600),
				minutes: Math.floor((remaining % 3600) / 60),
				seconds: remaining % 60
			};

			Object.keys(units).forEach(function (unit) {
				var node = grid.querySelector('[data-unit="' + unit + '"]');

				if (!node) {
					return;
				}

				var value = units[unit] < 10 ? '0' + units[unit] : String(units[unit]);

				if (node.textContent !== value) {
					node.textContent = value;
				}
			});
		}
	}

	/* ------------------------------------------------------------------ colour map */

	/**
	 * The colour map: point at anything in the preview and change its colour.
	 *
	 * Moving the pointer over an element resolves what paints it, shows a small
	 * panel beside that element with the one control that governs it, and
	 * outlines it. Text elements resolve to the colour of their text; a rule or
	 * a button resolves to the colour of its fill. Pointing at open space falls
	 * back to the full list of the colours this design uses.
	 *
	 * The rules it follows, and why:
	 *
	 * - A gesture never paints the preview. It writes the value into the real
	 *   toolbar control and dispatches `input`, so validation, the dirty flag and
	 *   every other consumer stay correct for free.
	 * - A row exists only if this design renders that block AND paints it from
	 *   that field. Both are measured; see measurePaint().
	 * - A row is named after the field it writes, never after one of its effects.
	 *   The panel heading names the element; the row names the field; the caption
	 *   says what else that one colour reaches.
	 * - The panel is a sibling of the preview positioned in viewport
	 *   coordinates, never a descendant of it.
	 * - The chips are pointer-only accelerators: aria-hidden, tabindex -1, no
	 *   name attribute. The keyboard route is the full-size control on the
	 *   toolbar, which is where the value actually lives.
	 *
	 * See design-system/components/color-map.md.
	 */

	/** Fields the map offers, ordered by how much of the page each one reaches. */
	var PAINT_FIELDS = ['text_color', 'accent_color'];

	/**
	 * Candidates: blocks a field is known to paint.
	 *
	 * `box` locates them, `label` is the wording used in the caption, `title`
	 * names the element in the panel heading, `kind` records whether the field
	 * reaches the block's text or its fill, and `rank` orders the caption.
	 */
	var PAINT_PARTS = [
		{ key: 'text_color', label: 'the headline', title: 'Headline', kind: 'text', box: '.mm-suc-p-page-headline', rank: 1 },
		{ key: 'text_color', label: 'the message', title: 'Message', kind: 'text', box: '.mm-suc-p-page-message', rank: 2 },
		{ key: 'text_color', label: 'the brand line', title: 'Brand line', kind: 'text', box: '.mm-suc-p-page-logo--text', rank: 3 },
		{ key: 'text_color', label: 'the countdown labels', title: 'Countdown label', kind: 'text', box: '.mm-suc-p-page-unit-label', rank: 4 },
		{ key: 'text_color', label: 'the site address', title: 'Site address', kind: 'text', box: '.mm-suc-p-page-host', rank: 5 },
		{ key: 'text_color', label: 'the message form', title: 'Message form', kind: 'text', box: '.mm-suc-p-page-sheet-title', rank: 6 },
		{ key: 'accent_color', label: 'the contact action', title: 'Contact action', kind: 'fill', box: '.mm-suc-p-page-contact', rank: 1 },
		{ key: 'accent_color', label: 'the countdown digits', title: 'Countdown digits', kind: 'text', box: '.mm-suc-p-page-unit-value', rank: 2 },
		{ key: 'accent_color', label: 'the separator', title: 'Separator', kind: 'fill', box: '.mm-suc-p-page-rule', rank: 3 },
		{ key: 'accent_color', label: 'the eyebrow', title: 'Eyebrow', kind: 'text', box: '.mm-suc-p-page-eyebrow', rank: 4 },
		{ key: 'accent_color', label: 'the send action', title: 'Send action', kind: 'fill', box: '.mm-suc-p-page-send', rank: 5 },
		{ key: 'accent_color', label: 'the card edge', title: 'Card edge', kind: 'fill', box: '.mm-suc-p-page-card', rank: 6 }
	];

	/** Properties a colour can reach. Read on the element and both pseudo-elements. */
	var PROBE_PROPS = ['color', 'background-color', 'background-image', 'box-shadow', 'fill', 'outline-color', 'text-decoration-color'];
	var PROBE_SIDES = ['border-top', 'border-right', 'border-bottom', 'border-left'];
	var PROBE_PSEUDO = [null, '::before', '::after'];
	var SENTINELS = ['rgb(255, 0, 0)', 'rgb(0, 255, 0)'];

	var palette = null;
	var paletteEnabled = true;
	var paletteMemo = { signature: '', result: null };
	var paletteFrame = null;
	var paletteAnchor = null;
	var paletteSuppressed = false;
	var palettePointer = { x: -1, y: -1 };

	/**
	 * Wire the toggle, the preview and the ways out.
	 *
	 * @return {void}
	 */
	function initPalette() {
		var toggle = document.getElementById('mm-suc-p-palette-toggle');

		if (toggle) {
			toggle.addEventListener('click', function () {
				paletteEnabled = toggle.getAttribute('aria-pressed') !== 'true';
				toggle.setAttribute('aria-pressed', paletteEnabled ? 'true' : 'false');

				if (!paletteEnabled) {
					hidePalette(true);
				}
			});
		}

		if (preview) {
			// pointerover fires when the element under the pointer changes, which
			// is exactly when the panel has something new to say.
			preview.addEventListener('pointerover', function (event) {
				onPreviewPoint(event.target);
			});

			// pointermove exists only to tell a real movement from the pointerover
			// a scroll fires by moving the page under a stationary pointer.
			preview.addEventListener('pointermove', function (event) {
				if (event.clientX === palettePointer.x && event.clientY === palettePointer.y) {
					return;
				}

				palettePointer = { x: event.clientX, y: event.clientY };

				if (paletteSuppressed) {
					paletteSuppressed = false;
					onPreviewPoint(event.target);
				}
			});
		}

		// Every way out the request asks for: a scroll, a click or a tap outside,
		// and Escape. A stale panel beside an element that has moved is worse
		// than no panel - and it stays gone until the pointer really moves, so a
		// scroll cannot immediately re-open it under a pointer that never left.
		window.addEventListener('scroll', function () {
			paletteSuppressed = true;
			hidePalette(true);
		}, true);

		window.addEventListener('resize', function () {
			hidePalette(true);
		});

		document.addEventListener('pointerdown', onOutside, true);
		document.addEventListener('touchstart', onOutside, true);

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				paletteSuppressed = true;
				hidePalette(true);
			}
		});
	}

	/**
	 * A press outside the panel closes the map.
	 *
	 * @param {Event} event Pointer or touch event.
	 * @return {void}
	 */
	function onOutside(event) {
		if (!palette || palette.layer.hidden) {
			return;
		}

		var target = event.target;

		if (palette.list.contains(target)) {
			return;
		}

		paletteSuppressed = true;
		hidePalette(true);
	}

	/**
	 * The pointer moved onto something inside the preview.
	 *
	 * @param {Element} target Node under the pointer.
	 * @return {void}
	 */
	function onPreviewPoint(target) {
		console.log("im inside onpreviewpoint");
		if (!paletteEnabled || paletteSuppressed || !pageRoot()) {
			return;
		}

		if (palette && !palette.layer.hidden) {
			return;
		}

		var resolved = resolveTarget(target);

		buildPalette();

		if (resolved) {
			showForElement(resolved);
			return;
		}

		showList();
	}

	/**
	 * Walk up from the pointed node to the first block a field really paints.
	 *
	 * Depth does the disambiguating: a digit resolves to the digit, not to the
	 * cell around it. A block this design hard-codes is stepped over, because
	 * offering a control for it would be offering a control that does nothing.
	 *
	 * @param {Element} node Node under the pointer.
	 * @return {Object|null} { part, node } or null for open space.
	 */
	function resolveTarget(node) {
		var page = pageRoot();
		var result = measurePaint();

		while (node && node !== page && node.nodeType === 1) {
			for (var i = 0; i < PAINT_PARTS.length; i++) {
				var part = PAINT_PARTS[i];

				if (!node.matches(part.box)) {
					continue;
				}

				var eligible = (result[part.key] || []).some(function (entry) {
					return entry.nodes.indexOf(node) !== -1;
				});

				if (eligible) {
					return { part: part, node: node };
				}
			}

			node = node.parentNode;
		}

		return null;
	}

	/**
	 * Show the panel beside one element, with the single control that governs it.
	 *
	 * @param {Object} resolved From resolveTarget().
	 * @return {void}
	 */
	function showForElement(resolved) {
		var field = resolved.part.key;

		paletteAnchor = resolved.node;
		palette.layer.hidden = false;

		palette.heading.textContent = resolved.part.title;

		PAINT_FIELDS.forEach(function (key) {
			palette.rows[key].hidden = key !== field;
			syncChip(key);
		});

		var others = (measurePaint()[field] || []).filter(function (entry) {
			return entry.part !== resolved.part;
		});

		setPaletteNote(others.length ? format(text('colourMapAlso', 'The same colour also sets %s.'), listOf(others)) : '');

		clearLights();
		resolved.node.setAttribute('data-mm-suc-p-paint-lit', '');
		hideWire();
		positionPalette(resolved.node.getBoundingClientRect());
	}

	/**
	 * Show the full list, anchored to the preview, for open space.
	 *
	 * @return {void}
	 */
	function showList() {
		return;
		var page = pageRoot();
		var result = measurePaint();
		var shown = 0;

		paletteAnchor = null;
		palette.layer.hidden = false;
		palette.heading.textContent = text('colourMap', 'Colours on this design');

		PAINT_FIELDS.forEach(function (field) {
			var eligible = result[field] && result[field].length;

			palette.rows[field].hidden = !eligible;
			syncChip(field);

			if (eligible) {
				shown++;
			}
		});

		setPaletteNote(shown ? '' : text('colourMapEmpty', 'This design paints its own colours, so none of these apply to it.'));

		clearLights();
		hideWire();
		positionPalette(page.getBoundingClientRect());
	}

	/**
	 * Keep a chip showing the value its field currently holds.
	 *
	 * @param {string} field Field name.
	 * @return {void}
	 */
	function syncChip(field) {
		var chip = palette.rows[field].querySelector('[data-mm-suc-p-paint]');
		var source = controlFor(field);

		if (chip && source && chip.value !== source.value) {
			chip.value = source.value;
		}
	}

	/**
	 * Hide the panel.
	 *
	 * @param {boolean} force Hide even while a chip holds focus.
	 * @return {void}
	 */
	function hidePalette(force) {
		if (!palette || palette.layer.hidden) {
			return;
		}

		if (!force && palette.layer.contains(document.activeElement)) {
			// A colour dialog is open on a chip. Hiding now would strand it.
			palette.deferred = true;
			return;
		}

		if (force && palette.layer.contains(document.activeElement)) {
			palette.deferred = true;
			return;
		}

		paletteAnchor = null;
		clearLights();
		hideWire();
		setPaletteNote('');
		palette.layer.hidden = true;
	}

	/**
	 * Build the layer once, lazily.
	 *
	 * @return {void}
	 */
	function buildPalette() {
		if (palette) {
			return;
		}

		var layer = document.createElement('div');
		layer.className = 'mm-suc-p-palette';
		layer.setAttribute('aria-hidden', 'true');
		layer.hidden = true;

		var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
		svg.setAttribute('class', 'mm-suc-p-palette-wire');
		svg.setAttribute('focusable', 'false');

		var line = document.createElementNS('http://www.w3.org/2000/svg', 'path');
		line.setAttribute('class', 'mm-suc-p-palette-line');

		var head = document.createElementNS('http://www.w3.org/2000/svg', 'path');
		head.setAttribute('class', 'mm-suc-p-palette-head');

		svg.appendChild(line);
		svg.appendChild(head);

		var list = document.createElement('div');
		list.className = 'mm-suc-p-palette-list';

		var heading = document.createElement('p');
		heading.className = 'mm-suc-p-palette-head-row';
		heading.textContent = text('colourMap', 'Colours on this design');
		list.appendChild(heading);

		var rows = {};

		PAINT_FIELDS.forEach(function (field) {
			var row = document.createElement('span');
			row.className = 'mm-suc-p-palette-row';
			row.setAttribute('data-mm-suc-p-paint-row', field);
			row.hidden = true;

			var chip = document.createElement('input');
			chip.type = 'color';
			chip.className = 'mm-suc-p-palette-swatch';
			chip.setAttribute('data-mm-suc-p-paint', field);
			chip.tabIndex = -1;

			var source = controlFor(field);
			chip.value = source ? source.value : '#000000';

			var name = document.createElement('span');
			name.className = 'mm-suc-p-palette-name';
			name.textContent = paintLabel(field);

			row.appendChild(chip);
			row.appendChild(name);
			list.appendChild(row);
			rows[field] = row;

			row.addEventListener('pointerenter', function () {
				lightField(field, chip);
			});

			row.addEventListener('pointerleave', function () {
				if (document.activeElement !== chip) {
					restoreAnchorLight();
				}
			});

			chip.addEventListener('focus', function () {
				lightField(field, chip);
			});

			chip.addEventListener('blur', function () {
				restoreAnchorLight();

				if (palette.deferred) {
					palette.deferred = false;
					hidePalette(true);
				}
			});

			// A drag inside the OS dialog reports every pointer move as `input`.
			chip.addEventListener('input', function () {
				pushPaint(field, chip.value, false);
			});

			chip.addEventListener('change', function () {
				pushPaint(field, chip.value, true);
			});
		});

		var note = document.createElement('p');
		note.className = 'mm-suc-p-palette-note';
		note.hidden = true;
		list.appendChild(note);

		layer.appendChild(svg);
		layer.appendChild(list);
		root.appendChild(layer);

		palette = {
			layer: layer,
			list: list,
			svg: svg,
			line: line,
			head: head,
			heading: heading,
			rows: rows,
			note: note,
			deferred: false
		};
	}

	/**
	 * The user-facing name of a field, taken from its toolbar control so the two
	 * can never drift apart.
	 *
	 * @param {string} field Field name.
	 * @return {string} Label.
	 */
	function paintLabel(field) {
		var tool = root.querySelector('[data-mm-suc-p-tool="' + field + '"] .mm-suc-p-tool-label');

		if (tool && tool.textContent) {
			return tool.textContent.trim();
		}

		return field;
	}

	/**
	 * Recompute after the preview markup was replaced.
	 *
	 * @return {void}
	 */
	function refreshPalette() {
		paletteMemo = { signature: '', result: null };
		hidePalette(true);
	}

	/**
	 * Which candidates this design both renders and paints from the field.
	 *
	 * Rendering a block is not the same as painting it from the variable: a
	 * template is free to hard-code a colour, and then the control does nothing.
	 * So the design is asked rather than assumed - set the property to one
	 * sentinel, read every colour-bearing property off the block, set it to a
	 * second sentinel, read again. Identical readings mean the block does not
	 * come from that field.
	 *
	 * Memoised on a signature that deliberately excludes the colour values: a
	 * colour cannot change which rules read the variable, and a drag inside the
	 * picker would otherwise remeasure on every frame.
	 *
	 * @return {Object} field name to its eligible candidates.
	 */
	function measurePaint() {
		var page = pageRoot();

		if (!page) {
			return {};
		}

		var signature = paintSignature(page);

		if (paletteMemo.signature === signature && paletteMemo.result) {
			return paletteMemo.result;
		}

		var result = {};

		PAINT_FIELDS.forEach(function (field) {
			var property = VARS[field];
			var parts = PAINT_PARTS.filter(function (part) {
				return part.key === field;
			}).map(function (part) {
				return { part: part, nodes: renderedNodes(page, part.box) };
			}).filter(function (entry) {
				return entry.nodes.length > 0;
			});

			if (!parts.length) {
				result[field] = [];
				return;
			}

			var saved = page.style.getPropertyValue(property);
			var readings = SENTINELS.map(function (colour) {
				// Both writes and both reads happen in one task, so no
				// intermediate state is ever painted to the screen.
				page.style.setProperty(property, colour);

				return parts.map(function (entry) {
					return readProbe(entry.nodes);
				});
			});

			if (saved) {
				page.style.setProperty(property, saved);
			} else {
				page.style.removeProperty(property);
			}

			result[field] = parts.filter(function (entry, index) {
				return readings[0][index] !== readings[1][index];
			});
		});

		paletteMemo = { signature: signature, result: result };

		return result;
	}

	/**
	 * The nodes a selector matches that this design actually draws.
	 *
	 * A template is free to hide a block it does not want - the separator is
	 * display:none in two of them. A hidden block still answers the sentinels,
	 * so without this filter the caption would name a part nobody can see.
	 *
	 * @param {Element} page     Preview root.
	 * @param {string}  selector Candidate selector.
	 * @return {Array} Rendered nodes.
	 */
	function renderedNodes(page, selector) {
		return Array.prototype.filter.call(
			page.querySelectorAll(selector),
			function (node) {
				return node.getClientRects().length > 0;
			}
		);
	}

	/**
	 * A signature of what this design renders, with no colour in it.
	 *
	 * @param {Element} page Preview root.
	 * @return {string} Signature.
	 */
	function paintSignature(page) {
		return page.className + '::' + PAINT_PARTS.map(function (part) {
			return renderedNodes(page, part.box).length;
		}).join('-');
	}

	/**
	 * Every colour-bearing value on a set of nodes, as one comparable string.
	 *
	 * @param {Array} nodes The candidate's nodes.
	 * @return {string} Reading.
	 */
	function readProbe(nodes) {
		var out = [];

		nodes.forEach(function (node) {
			PROBE_PSEUDO.forEach(function (pseudo) {
				var styles = window.getComputedStyle(node, pseudo);

				PROBE_PROPS.forEach(function (property) {
					out.push(styles.getPropertyValue(property));
				});

				PROBE_SIDES.forEach(function (side) {
					// A colour on a zero-width border answers the sentinels
					// exactly like a drawn one. Only a painted side counts.
					var width = parseFloat(styles.getPropertyValue(side + '-width'));
					out.push(width > 0 ? styles.getPropertyValue(side + '-color') : '');
				});
			});
		});

		return out.join('|');
	}

	/**
	 * Outline everything this field reaches, draw the wire and set the caption.
	 *
	 * @param {string}  field Field name.
	 * @param {Element} chip  The row's colour input.
	 * @return {void}
	 */
	function lightField(field, chip) {
		var parts = measurePaint()[field] || [];
		var lit = [];

		clearLights();

		parts.forEach(function (entry) {
			entry.nodes.forEach(function (node) {
				node.setAttribute('data-mm-suc-p-paint-lit', '');
				lit.push(node);
			});
		});

		// The wire lands on one of the outlined nodes by construction: it picks
		// from the very list the outlines were drawn from.
		drawWire(chip, lit);

		if (parts.length > 1) {
			setPaletteNote(format(text('colourMapReach', 'Sets %s on this design - they all use this one colour.'), listOf(parts)));
		}
	}

	/**
	 * Go back to outlining just the element the panel is anchored to.
	 *
	 * @return {void}
	 */
	function restoreAnchorLight() {
		clearLights();
		hideWire();

		if (paletteAnchor) {
			paletteAnchor.setAttribute('data-mm-suc-p-paint-lit', '');
		}
	}

	/**
	 * "the headline, the message and the site address"
	 *
	 * @param {Array} parts Eligible candidates.
	 * @return {string} Prose list.
	 */
	function listOf(parts) {
		var names = parts.slice().sort(function (a, b) {
			return a.part.rank - b.part.rank;
		}).map(function (entry) {
			return entry.part.label;
		});

		var last = names.pop();

		return names.length ? names.join(', ') + ' ' + text('and', 'and') + ' ' + last : last;
	}

	/**
	 * Remove every outline.
	 *
	 * @return {void}
	 */
	function clearLights() {
		var lit = root.querySelectorAll('[data-mm-suc-p-paint-lit]');

		Array.prototype.forEach.call(lit, function (node) {
			node.removeAttribute('data-mm-suc-p-paint-lit');
		});
	}

	/**
	 * Show or clear the caption.
	 *
	 * @param {string} message Caption, or an empty string to hide it.
	 * @return {void}
	 */
	function setPaletteNote(message) {
		if (!palette) {
			return;
		}

		palette.note.textContent = message;
		palette.note.hidden = !message;
	}

	/**
	 * Place the panel beside a rectangle, without covering it.
	 *
	 * Four candidate positions are scored: how much of the preview each one
	 * covers first, then how far it sits from the anchor. Beside the preview
	 * therefore wins whenever there is room for it, and beside the element wins
	 * when there is not - which is the point of pointing at an element.
	 *
	 * @param {DOMRect} anchor The rectangle to sit beside.
	 * @return {void}
	 */
	function positionPalette(anchor) {
		if (!palette || palette.layer.hidden) {
			return;
		}

		var width = palette.list.offsetWidth;
		var height = palette.list.offsetHeight;
		var viewportWidth = document.documentElement.clientWidth;
		var viewportHeight = document.documentElement.clientHeight;
		var frame = preview ? preview.getBoundingClientRect() : null;
		var gap = 12;
		var edge = 8;

		var centreY = anchor.top + ((anchor.height - height) / 2);
		var centreX = anchor.left + ((anchor.width - width) / 2);

		var options = [
			{ x: anchor.right + gap, y: centreY },
			{ x: anchor.left - gap - width, y: centreY },
			{ x: centreX, y: anchor.bottom + gap },
			{ x: centreX, y: anchor.top - gap - height }
		];

		var best = null;

		options.forEach(function (option, index) {
			var x = Math.max(edge, Math.min(option.x, viewportWidth - width - edge));
			var y = Math.max(edge, Math.min(option.y, viewportHeight - height - edge));
			var rect = { left: x, top: y, right: x + width, bottom: y + height };
			var score = overlap(rect, anchor) * 1000;

			// Anything covering the element it describes is disqualified first;
			// after that, prefer the placement that covers least of the preview.
			score += frame ? overlap(rect, frame) : 0;
			score += index;

			if (!best || score < best.score) {
				best = { x: x, y: y, score: score };
			}
		});

		palette.list.style.insetInlineStart = Math.round(best.x) + 'px';
		palette.list.style.insetBlockStart = Math.round(best.y) + 'px';
	}

	/**
	 * Area two rectangles share.
	 *
	 * @param {Object} a First.
	 * @param {Object} b Second.
	 * @return {number} Overlapping area in square pixels.
	 */
	function overlap(a, b) {
		var x = Math.max(0, Math.min(a.right, b.right) - Math.max(a.left, b.left));
		var y = Math.max(0, Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top));

		return x * y;
	}

	/**
	 * Draw one straight segment from the chip to the nearest outlined block.
	 *
	 * @param {Element} chip    The row's colour input.
	 * @param {Array}   targets Outlined nodes.
	 * @return {void}
	 */
	function drawWire(chip, targets) {
		if (!palette || !targets.length) {
			hideWire();
			return;
		}

		var chipRect = chip.getBoundingClientRect();
		var cx = chipRect.left + (chipRect.width / 2);
		var cy = chipRect.top + (chipRect.height / 2);
		var best = null;
		var bestDistance = Infinity;

		targets.forEach(function (node) {
			var rect = node.getBoundingClientRect();

			if (!rect.width && !rect.height) {
				return;
			}

			// Nearest point of the target's own box, so the head lands on its
			// near edge rather than on top of its text.
			var px = Math.max(rect.left, Math.min(cx, rect.right));
			var py = Math.max(rect.top, Math.min(cy, rect.bottom));
			var distance = ((px - cx) * (px - cx)) + ((py - cy) * (py - cy));

			if (distance < bestDistance) {
				bestDistance = distance;
				best = { x: px, y: py };
			}
		});

		if (!best || bestDistance < 400) {
			// Already beside it: an arrow this short is noise, not a pointer.
			hideWire();
			return;
		}

		var sx = best.x < cx ? chipRect.left : chipRect.right;
		var sy = cy;
		var dx = best.x - sx;
		var dy = best.y - sy;
		var length = Math.sqrt((dx * dx) + (dy * dy)) || 1;
		var ux = dx / length;
		var uy = dy / length;
		var headLength = 9;
		var headWidth = 4.5;
		var ex = best.x - (ux * headLength);
		var ey = best.y - (uy * headLength);

		palette.line.setAttribute('d', 'M' + round(sx) + ' ' + round(sy) + 'L' + round(ex) + ' ' + round(ey));
		palette.head.setAttribute(
			'd',
			'M' + round(best.x) + ' ' + round(best.y) +
			'L' + round(ex + (-uy * headWidth)) + ' ' + round(ey + (ux * headWidth)) +
			'L' + round(ex - (-uy * headWidth)) + ' ' + round(ey - (ux * headWidth)) + 'Z'
		);
		palette.svg.setAttribute('data-shown', 'true');
	}

	/**
	 * One decimal place is plenty for a path.
	 *
	 * @param {number} value Coordinate.
	 * @return {number} Rounded.
	 */
	function round(value) {
		return Math.round(value * 10) / 10;
	}

	/**
	 * Hide the wire.
	 *
	 * @return {void}
	 */
	function hideWire() {
		if (palette) {
			palette.svg.removeAttribute('data-shown');
		}
	}

	/**
	 * Write a colour into the field it belongs to.
	 *
	 * The map never paints the preview itself: it sets the toolbar control and
	 * dispatches the event that control already answers, so every other consumer
	 * of the value stays in step.
	 *
	 * @param {string}  field     Field name.
	 * @param {string}  value     Hex colour.
	 * @param {boolean} committed Whether this is the dialog's final value.
	 * @return {void}
	 */
	function pushPaint(field, value, committed) {
		var input = controlFor(field);

		if (!input) {
			return;
		}

		if (input.value !== value) {
			input.value = value;
			input.dispatchEvent(new window.Event('input', { bubbles: true }));
		} else if (!committed) {
			// A drag reports the same colour many times before it moves.
			return;
		}

		var hex = root.querySelector('[data-mm-suc-p-hex="' + field + '"]');

		if (hex && hex.value !== value) {
			hex.value = value;
		}

		var chips = root.querySelectorAll('[data-mm-suc-p-paint="' + field + '"]');

		Array.prototype.forEach.call(chips, function (chip) {
			if (chip.value !== value) {
				chip.value = value;
			}
		});

		if (committed) {
			input.dispatchEvent(new window.Event('change', { bubbles: true }));
		}
	}

	/* ------------------------------------------------------------------ save */

	/**
	 * Wire the save control.
	 *
	 * @return {void}
	 */
	function initSave() {
		if (!saveBtn) {
			return;
		}

		saveBtn.addEventListener('click', function () {
			save();
		});
	}

	/**
	 * Send the settings and narrate the outcome.
	 *
	 * @return {void}
	 */
	function save() {
		var label = saveBtn.querySelector('.mm-suc-p-save-label');
		var original = label ? label.textContent : '';

		saveBtn.disabled = true;
		saveBtn.setAttribute('aria-busy', 'true');

		if (label) {
			label.textContent = text('saving', 'Saving…');
		}

		post(data.saveAction, collect()).then(function (result) {
			restore();

			if (result && result.success) {
				savedEnabled = !!(result.data && result.data.options && result.data.options.enabled);
				clearDirty();
				updatePill();
				updateConsequence();
				updateDuration();

				var isWarning = !!(result.data && result.data.warning);
				var toastKind = isWarning ? 'warning' : 'success';
				var toastMsg = (result.data && result.data.message) || text('saved', 'Settings saved.');

				toast(toastKind, toastMsg, !isWarning);
				return;
			}

			var message = (result && result.data && result.data.message) || text('saveFailed', 'Could not save.');
			toast('error', message, false);
		}).catch(function () {
			restore();
			toast('error', text('saveFailed', 'Could not save. Check your connection and try again.'), false);
		});

		function restore() {
			saveBtn.disabled = false;
			saveBtn.removeAttribute('aria-busy');

			if (label) {
				label.textContent = original || text('save', 'Save changes');
			}
		}
	}

	/**
	 * POST a settings payload to an admin-ajax action.
	 *
	 * @param {string} action   Action name.
	 * @param {Object} settings Settings payload.
	 * @return {Promise} Resolves with the decoded response.
	 */
	function post(action, settings) {
		var body = new window.URLSearchParams();

		body.append('action', action);
		body.append('nonce', data.nonce || '');
		body.append('settings', JSON.stringify(settings));

		return window.fetch(data.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		}).then(function (response) {
			return response.json();
		});
	}

	/* ------------------------------------------------------------------ status */

	/**
	 * Note that the form no longer matches what is stored.
	 *
	 * @return {void}
	 */
	function markDirty() {
		root.setAttribute('data-mm-suc-p-dirty', 'true');
		updatePill();
	}

	/**
	 * Note that the form matches what is stored.
	 *
	 * @return {void}
	 */
	function clearDirty() {
		root.removeAttribute('data-mm-suc-p-dirty');
	}

	/**
	 * Keep the status pill showing the saved state, and flag unsaved changes.
	 *
	 * @return {void}
	 */
	function updatePill() {
		if (!pill) {
			return;
		}

		var label = pill.querySelector('.mm-suc-p-pill-text');

		if (label) {
			label.textContent = savedEnabled
				? text('maintenance', 'Maintenance mode is on')
				: text('live', 'Site is live');
		}

		pill.classList.toggle('mm-suc-p-pill--maintenance', savedEnabled);
		pill.classList.toggle('mm-suc-p-pill--live', !savedEnabled);

		var note = pill.querySelector('.mm-suc-p-pill-note');
		var dirty = root.getAttribute('data-mm-suc-p-dirty') === 'true';

		if (dirty && !note) {
			note = document.createElement('span');
			note.className = 'mm-suc-p-pill-note';
			note.textContent = text('unsaved', 'Unsaved change');
			pill.appendChild(note);
		}

		if (!dirty && note) {
			note.parentNode.removeChild(note);
		}
	}

	/* ------------------------------------------------------------------ toasts */

	/**
	 * Show a toast. Success dismisses itself; an error waits to be read.
	 *
	 * @param {string}  kind    success, error or info.
	 * @param {string}  message The message.
	 * @param {boolean} auto    Whether to dismiss automatically.
	 * @return {void}
	 */
	function toast(kind, message, auto) {
		if (!toasts) {
			return;
		}

		while (toasts.children.length > 2) {
			toasts.removeChild(toasts.firstChild);
		}

		var node = document.createElement('div');
		node.className = 'mm-suc-p-toast mm-suc-p-toast--' + kind;
		node.setAttribute('role', (kind === 'error' || kind === 'warning') ? 'alert' : 'status');

		var icons = data.icons || {};
		var glyph = (kind === 'error' || kind === 'warning') ? icons.warning : icons.check;

		if (glyph) {
			var holder = document.createElement('span');
			holder.className = 'mm-suc-p-toast-icon';
			holder.innerHTML = glyph;
			node.appendChild(holder);
		}

		var copy = document.createElement('p');
		copy.textContent = message;
		node.appendChild(copy);

		var dismiss = document.createElement('button');
		dismiss.type = 'button';
		dismiss.className = 'mm-suc-p-btn mm-suc-p-btn--icon';
		dismiss.setAttribute('aria-label', text('dismiss', 'Dismiss'));

		if (icons.close) {
			dismiss.innerHTML = icons.close;
		} else {
			dismiss.textContent = 'x';
		}

		dismiss.addEventListener('click', function () {
			remove();
		});

		node.appendChild(dismiss);
		toasts.appendChild(node);

		if (auto) {
			window.setTimeout(remove, 4000);
		}

		function remove() {
			if (!node.parentNode) {
				return;
			}

			node.setAttribute('data-state', 'leaving');

			window.setTimeout(function () {
				if (node.parentNode) {
					node.parentNode.removeChild(node);
				}
			}, 200);
		}
	}

	/* ------------------------------------------------------------------ messages */

	/**
	 * Wire interactions on the Messages List screen.
	 *
	 * @return {void}
	 */
	function initMessagesScreen() {
		var modal = document.getElementById('mm-suc-p-msg-modal');
		var modalClose = document.getElementById('mm-suc-p-modal-close');
		var modalCancel = document.getElementById('mm-suc-p-modal-cancel-btn');
		var modalReply = document.getElementById('mm-suc-p-modal-reply-btn');
		var modalDelete = document.getElementById('mm-suc-p-modal-delete-btn');
		var modalSenderName = document.getElementById('mm-suc-p-modal-sender-name');
		var modalSenderEmail = document.getElementById('mm-suc-p-modal-sender-email');
		var modalDate = document.getElementById('mm-suc-p-modal-date');
		var modalText = document.getElementById('mm-suc-p-modal-message-text');

		var clearAllBtn = document.getElementById('mm-suc-p-clear-all-messages');
		var tableWrapper = document.getElementById('mm-suc-p-messages-list-wrapper');
		var retentionCheckbox = document.getElementById('mm-suc-p-delete-messages-uninstall');

		var currentMsgId = null;
		var lastActiveElement = null;

		root.addEventListener('click', function (event) {
			var deleteBtn = event.target.closest('.mm-suc-p-delete-msg');
			if (deleteBtn) {
				event.preventDefault();
				event.stopPropagation();
				var row = deleteBtn.closest('.mm-suc-p-message-row');
				var msgId = deleteBtn.getAttribute('data-mm-suc-p-del-id') || (row ? row.getAttribute('data-mm-suc-p-msg-id') : null);
				if (msgId) {
					deleteMessage(msgId, false);
				}
				return;
			}

			var openBtn = event.target.closest('.mm-suc-p-open-msg');
			var msgRow = event.target.closest('.mm-suc-p-message-row');
			if (openBtn || (msgRow && !event.target.closest('a, button, input, select, textarea'))) {
				var targetRow = openBtn ? openBtn.closest('.mm-suc-p-message-row') : msgRow;
				if (targetRow) {
					lastActiveElement = openBtn || event.target;
					openMessageModal(targetRow);
				}
				return;
			}
		});

		if (modalClose) {
			modalClose.addEventListener('click', closeModal);
		}

		if (modalCancel) {
			modalCancel.addEventListener('click', closeModal);
		}

		if (modal) {
			modal.addEventListener('click', function (event) {
				if (event.target === modal) {
					closeModal();
				}
			});

			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && !modal.hidden) {
					closeModal();
				}
			});
		}

		if (modalDelete) {
			modalDelete.addEventListener('click', function () {
				if (currentMsgId) {
					deleteMessage(currentMsgId, true);
				}
			});
		}

		if (clearAllBtn) {
			clearAllBtn.addEventListener('click', function () {
				if (window.confirm(text('clearAllConfirm', 'Are you sure you want to delete all messages? This cannot be undone.'))) {
					clearAllMessages();
				}
			});
		}

		if (retentionCheckbox) {
			retentionCheckbox.addEventListener('change', function () {
				toggleRetentionSetting(retentionCheckbox.checked);
			});
		}

		function openMessageModal(row) {
			if (!modal) {
				return;
			}

			currentMsgId = row.getAttribute('data-mm-suc-p-msg-id');
			var name = row.getAttribute('data-mm-suc-p-msg-name') || '';
			var email = row.getAttribute('data-mm-suc-p-msg-email') || '';
			var date = row.getAttribute('data-mm-suc-p-msg-date') || '';
			var content = row.getAttribute('data-mm-suc-p-msg-content') || '';
			var isUnread = row.getAttribute('data-mm-suc-p-msg-read') === '0';

			if (modalSenderName) modalSenderName.textContent = name;
			if (modalSenderEmail) {
				modalSenderEmail.textContent = email;
				modalSenderEmail.href = 'mailto:' + encodeURIComponent(email);
			}
			if (modalReply) {
				modalReply.href = 'mailto:' + encodeURIComponent(email);
			}
			if (modalDate) modalDate.textContent = date;
			if (modalText) modalText.textContent = content;

			modal.hidden = false;
			if (modalClose) {
				modalClose.focus();
			}

			if (isUnread && currentMsgId) {
				markMessageAsRead(currentMsgId, row);
			}
		}

		function closeModal() {
			if (modal) {
				modal.hidden = true;
			}
			currentMsgId = null;
			if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
				lastActiveElement.focus();
				lastActiveElement = null;
			}
		}

		function markMessageAsRead(id, row) {
			var body = new window.URLSearchParams();
			body.append('action', data.markReadAction || 'mm_suc_mark_read');
			body.append('nonce', data.nonce || '');
			body.append('id', id);

			window.fetch(data.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			}).then(function (response) {
				return response.json();
			}).then(function (result) {
				if (result && result.success) {
					row.classList.remove('mm-suc-p-message-row--unread');
					row.setAttribute('data-mm-suc-p-msg-read', '1');

					var badge = row.querySelector('.mm-suc-p-msg-badge');
					if (badge) {
						badge.className = 'mm-suc-p-msg-badge mm-suc-p-msg-badge--read';
					}

					if (result.data && result.data.counts) {
						updateCountsPill(result.data.counts);
					}
				}
			}).catch(function () {});
		}

		function deleteMessage(id, fromModal) {
			if (!window.confirm(text('deleteMessageConfirm', 'Are you sure you want to delete this message?'))) {
				return;
			}

			var body = new window.URLSearchParams();
			body.append('action', data.deleteMessageAction || 'mm_suc_delete_message');
			body.append('nonce', data.nonce || '');
			body.append('id', id);

			window.fetch(data.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			}).then(function (response) {
				return response.json();
			}).then(function (result) {
				if (result && result.success) {
					if (fromModal) {
						closeModal();
					}

					var row = document.getElementById('mm-suc-p-row-' + id);
					if (row && row.parentNode) {
						row.parentNode.removeChild(row);
					}

					if (result.data && result.data.counts) {
						updateCountsPill(result.data.counts);
						if (result.data.counts.total === 0) {
							renderEmptyState();
						}
					}

					toast('success', text('messageDeleted', 'Message deleted.'), true);
				} else {
					var err = (result && result.data && result.data.message) ? result.data.message : text('deleteFailed', 'Could not delete the message. Try again.');
					toast('error', err, false);
				}
			}).catch(function () {
				toast('error', text('deleteFailed', 'Could not delete the message. Try again.'), false);
			});
		}

		function clearAllMessages() {
			var body = new window.URLSearchParams();
			body.append('action', data.clearMessagesAction || 'mm_suc_clear_messages');
			body.append('nonce', data.nonce || '');

			window.fetch(data.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			}).then(function (response) {
				return response.json();
			}).then(function (result) {
				if (result && result.success) {
					renderEmptyState();
					updateCountsPill({ total: 0, unread: 0 });
					toast('success', text('messagesCleared', 'All messages cleared.'), true);
				} else {
					var err = (result && result.data && result.data.message) ? result.data.message : text('clearFailed', 'Could not clear messages. Try again.');
					toast('error', err, false);
				}
			}).catch(function () {
				toast('error', text('clearFailed', 'Could not clear messages. Try again.'), false);
			});
		}

		function toggleRetentionSetting(checked) {
			var body = new window.URLSearchParams();
			body.append('action', data.toggleUninstallCleanupAction || 'mm_suc_toggle_uninstall_cleanup');
			body.append('nonce', data.nonce || '');
			body.append('delete_messages_on_uninstall', checked ? '1' : '0');

			window.fetch(data.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			}).then(function (response) {
				return response.json();
			}).then(function (result) {
				if (result && result.success) {
					toast('success', text('saved', 'Settings saved.'), true);
				} else {
					toast('error', text('saveFailed', 'Could not save. Try again.'), false);
				}
			}).catch(function () {
				toast('error', text('saveFailed', 'Could not save. Try again.'), false);
			});
		}

		function updateCountsPill(counts) {
			var pillText = root.querySelector('#mm-suc-p-pill .mm-suc-p-pill-text');
			if (pillText && counts) {
				pillText.textContent = counts.total + ' ' + (counts.total === 1 ? text('messageSingle', 'Message') : text('messagePlural', 'Messages')) + ' (' + counts.unread + ' ' + text('unreadCount', 'unread') + ')';
			}
		}

		function renderEmptyState() {
			if (clearAllBtn && clearAllBtn.parentNode) {
				clearAllBtn.parentNode.removeChild(clearAllBtn);
			}

			if (tableWrapper) {
				var icon = (data.icons && data.icons['contact-mail']) ? data.icons['contact-mail'] : '';
				tableWrapper.innerHTML = '<div class="mm-suc-p-messages-empty" id="mm-suc-p-messages-empty">'
					+ '<div class="mm-suc-p-empty-icon" aria-hidden="true">' + icon + '</div>'
					+ '<h3>' + text('noMessagesYet', 'No messages yet') + '</h3>'
					+ '<p>' + text('noMessagesDesc', 'When visitors send inquiries through the contact form on your maintenance page, they will appear here.') + '</p>'
					+ '</div>';
			}
		}
	}

	/* ------------------------------------------------------------------ rating banner */

	/**
	 * Wire interactions and persistent dismissal on the rating banner.
	 *
	 * @return {void}
	 */
	function initRatingBanner() {
		var banner = document.getElementById('mm-suc-p-rating-banner');

		if (!banner) {
			return;
		}

		banner.addEventListener('click', function (event) {
			var dismissBtn = event.target.closest('[data-mm-suc-p-rating-action="dismiss"]');
			var rateBtn = event.target.closest('[data-mm-suc-p-rating-action="rate"]');

			if (dismissBtn) {
				event.preventDefault();
				dismiss();
			} else if (rateBtn) {
				dismiss();
			}
		});

		function dismiss() {
			banner.setAttribute('data-state', 'dismissed');

			window.setTimeout(function () {
				if (banner.parentNode) {
					banner.parentNode.removeChild(banner);
				}
			}, 200);

			var body = new window.URLSearchParams();
			body.append('action', data.dismissRatingAction || 'mm_suc_dismiss_rating');
			body.append('nonce', data.nonce || '');

			window.fetch(data.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			}).catch(function () {});
		}
	}
}());

