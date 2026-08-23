/**
 * Public maintenance page.
 *
 * Countdown, the sliding contact sheet, and the contact submission. No
 * dependencies, no external requests.
 */
( function () {
	'use strict';

	var config = readConfig();
	var page = document.querySelector( '.mm-suc-p-page' );

	if ( ! page ) {
		return;
	}

	initCountdown( page );
	initSheet( page );

	/**
	 * Read the inline configuration block.
	 *
	 * @return {Object} Configuration, or a safe empty shape.
	 */
	function readConfig() {
		var node = document.getElementById( 'mm-suc-p-config' );
		var fallback = { ajaxUrl: '', action: '', nonce: '', caps: {}, strings: {}, preview: false };

		if ( ! node ) {
			return fallback;
		}

		try {
			var parsed = JSON.parse( node.textContent || '{}' );
			return parsed && typeof parsed === 'object' ? parsed : fallback;
		} catch ( error ) {
			return fallback;
		}
	}

	/**
	 * A translated string, with the default as a fallback.
	 *
	 * @param {string} key      String key.
	 * @param {string} fallback Default text.
	 * @return {string} The string.
	 */
	function text( key, fallback ) {
		return ( config.strings && config.strings[ key ] ) ? config.strings[ key ] : fallback;
	}

	/* ------------------------------------------------------------------ countdown */

	/**
	 * Start the countdown.
	 *
	 * The remaining time is recomputed from the clock on every tick, so a
	 * throttled background tab and a sleeping laptop both correct themselves.
	 *
	 * @param {Element} root Page root.
	 * @return {void}
	 */
	function initCountdown( root ) {
		var grid = root.querySelector( '[data-mm-suc-p-part="countdown"]' );

		if ( ! grid ) {
			return;
		}

		var end = Date.parse( grid.getAttribute( 'data-end' ) || '' );

		if ( isNaN( end ) ) {
			return;
		}

		var live = grid.querySelector( '[data-mm-suc-p-live="countdown"]' );
		var values = {
			days: grid.querySelector( '[data-unit="days"]' ),
			hours: grid.querySelector( '[data-unit="hours"]' ),
			minutes: grid.querySelector( '[data-unit="minutes"]' ),
			seconds: grid.querySelector( '[data-unit="seconds"]' )
		};
		var announced = null;
		var finished = false;

		tick();
		var timer = window.setInterval( tick, 1000 );

		function tick() {
			var remaining = Math.max( 0, Math.floor( ( end - Date.now() ) / 1000 ) );
			var parts = split( remaining );

			write( values.days, parts.days );
			write( values.hours, parts.hours );
			write( values.minutes, parts.minutes );
			write( values.seconds, parts.seconds );

			// Announce on the minute, not per second: a per-second live region is unusable.
			var minuteStamp = Math.floor( remaining / 60 );

			if ( live && minuteStamp !== announced ) {
				announced = minuteStamp;
				live.textContent = remaining > 0
					? sprintf( text( 'remaining', '%s remaining' ), humanise( parts ) )
					: text( 'finished', 'The wait is over.' );
			}

			if ( remaining <= 0 && ! finished ) {
				finished = true;
				window.clearInterval( timer );
				reloadOnce();
			}
		}

		function write( node, value ) {
			if ( ! node ) {
				return;
			}

			var padded = value < 10 ? '0' + value : String( value );

			if ( node.textContent !== padded ) {
				node.textContent = padded;
			}
		}
	}

	/**
	 * Split seconds into days, hours, minutes and seconds.
	 *
	 * @param {number} total Seconds remaining.
	 * @return {Object} The four units.
	 */
	function split( total ) {
		return {
			days: Math.floor( total / 86400 ),
			hours: Math.floor( ( total % 86400 ) / 3600 ),
			minutes: Math.floor( ( total % 3600 ) / 60 ),
			seconds: total % 60
		};
	}

	/**
	 * Human-readable remaining time, in prose, for the live region.
	 *
	 * @param {Object} parts Units from split().
	 * @return {string} For example "3 days, 4 hours".
	 */
	function humanise( parts ) {
		var out = [];

		if ( parts.days ) {
			out.push( sprintf( text( parts.days === 1 ? 'day' : 'days', '%s days' ), parts.days ) );
		}

		if ( parts.hours ) {
			out.push( sprintf( text( parts.hours === 1 ? 'hour' : 'hours', '%s hours' ), parts.hours ) );
		}

		if ( ! parts.days ) {
			out.push( sprintf( text( parts.minutes === 1 ? 'minute' : 'minutes', '%s minutes' ), parts.minutes ) );
		}

		return out.join( ', ' );
	}

	/**
	 * Replace the first %s placeholder.
	 *
	 * @param {string} template Format string.
	 * @param {*}      value    Replacement.
	 * @return {string} Formatted string.
	 */
	function sprintf( template, value ) {
		return String( template ).replace( '%s', String( value ) );
	}

	/**
	 * Reload at most once per visit, so a page that is still under maintenance
	 * never enters a reload loop.
	 *
	 * @return {void}
	 */
	function reloadOnce() {
		if ( config.preview ) {
			return;
		}

		var key = 'mmSucPReloaded';

		try {
			var last = window.sessionStorage.getItem( key );

			if ( last && ( Date.now() - parseInt( last, 10 ) ) < 120000 ) {
				return;
			}

			window.sessionStorage.setItem( key, String( Date.now() ) );
		} catch ( error ) {
			// Storage can be unavailable; one reload is still safe.
		}

		window.setTimeout( function () {
			window.location.reload();
		}, 1500 );
	}

	/* ------------------------------------------------------------------ sheet */

	/**
	 * Wire the contact trigger, the sheet and the form.
	 *
	 * @param {Element} root Page root.
	 * @return {void}
	 */
	function initSheet( root ) {
		var trigger = root.querySelector( 'button.mm-suc-p-page-contact' );
		var sheet = root.querySelector( '.mm-suc-p-page-sheet' );

		if ( ! trigger || ! sheet ) {
			return;
		}

		var form = sheet.querySelector( '.mm-suc-p-page-form' );
		var closers = sheet.querySelectorAll( '.mm-suc-p-page-sheet-close, .mm-suc-p-page-scrim' );

		trigger.addEventListener( 'click', function () {
			open();
		} );

		Array.prototype.forEach.call( closers, function ( node ) {
			node.addEventListener( 'click', function () {
				close();
			} );
		} );

		sheet.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' ) {
				event.stopPropagation();
				close();
				return;
			}

			if ( event.key === 'Tab' ) {
				trapFocus( event, sheet );
			}
		} );

		if ( form ) {
			form.addEventListener( 'submit', function ( event ) {
				event.preventDefault();
				submit( form );
			} );
		}

		function open() {
			sheet.hidden = false;
			sheet.removeAttribute( 'data-state' );
			trigger.setAttribute( 'aria-expanded', 'true' );

			var first = sheet.querySelector( 'input, textarea' );

			if ( first ) {
				first.focus();
			}
		}

		function close() {
			sheet.hidden = true;
			trigger.setAttribute( 'aria-expanded', 'false' );
			trigger.focus();
		}
	}

	/**
	 * Keep Tab inside an open dialog.
	 *
	 * @param {KeyboardEvent} event     The keydown.
	 * @param {Element}       container The dialog.
	 * @return {void}
	 */
	function trapFocus( event, container ) {
		var focusable = container.querySelectorAll( 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])' );

		if ( ! focusable.length ) {
			return;
		}

		var first = focusable[ 0 ];
		var last = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	/* ------------------------------------------------------------------ submit */

	/**
	 * Validate and send the contact form.
	 *
	 * @param {HTMLFormElement} form The form.
	 * @return {void}
	 */
	function submit( form ) {
		var status = form.querySelector( '.mm-suc-p-page-status' );
		var send = form.querySelector( '.mm-suc-p-page-send' );
		var fields = {
			name: form.querySelector( '[name="name"]' ),
			email: form.querySelector( '[name="email"]' ),
			message: form.querySelector( '[name="message"]' )
		};

		clearErrors( form );

		var errors = {};

		if ( ! fields.name || ! fields.name.value.trim() ) {
			errors.name = text( 'required', 'Fill this in so we can reply.' );
		}

		if ( ! fields.email || ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( fields.email.value.trim() ) ) {
			errors.email = text( 'email', 'Enter an email address like name@example.com.' );
		}

		if ( ! fields.message || ! fields.message.value.trim() ) {
			errors.message = text( 'required', 'Fill this in so we can reply.' );
		}

		if ( Object.keys( errors ).length ) {
			showErrors( form, errors );
			return;
		}

		var body = new window.URLSearchParams();
		body.append( 'action', config.action || 'mm_suc_contact' );
		body.append( 'nonce', config.nonce || '' );
		body.append( 'name', fields.name.value.trim() );
		body.append( 'email', fields.email.value.trim() );
		body.append( 'message', fields.message.value.trim() );

		var trap = form.querySelector( '[name="website"]' );
		body.append( 'website', trap ? trap.value : '' );

		busy( send, true );

		if ( status ) {
			status.textContent = '';
		}

		window.fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} ).then( function ( response ) {
			return response.json().then( function ( payload ) {
				return { ok: response.ok, payload: payload };
			} );
		} ).then( function ( result ) {
			busy( send, false );

			var data = ( result.payload && result.payload.data ) ? result.payload.data : {};

			if ( result.payload && result.payload.success ) {
				form.reset();

				if ( status ) {
					status.textContent = data.message || text( 'sent', 'Thanks - your message is on its way.' );
				}

				return;
			}

			if ( data.fields ) {
				showErrors( form, data.fields );
			}

			if ( status ) {
				status.textContent = data.message || text( 'failed', 'We could not send that. Try again shortly.' );
			}
		} ).catch( function () {
			busy( send, false );

			if ( status ) {
				status.textContent = text( 'network', 'That did not reach us. Check your connection and try again.' );
			}
		} );
	}

	/**
	 * Toggle the submit control's busy state.
	 *
	 * @param {Element} node    The submit control.
	 * @param {boolean} pending Whether a request is in flight.
	 * @return {void}
	 */
	function busy( node, pending ) {
		if ( ! node ) {
			return;
		}

		if ( pending ) {
			node.setAttribute( 'data-label', node.textContent );
			node.textContent = node.getAttribute( 'data-sending' ) || node.textContent;
			node.setAttribute( 'aria-busy', 'true' );
			node.disabled = true;
			return;
		}

		if ( node.getAttribute( 'data-label' ) ) {
			node.textContent = node.getAttribute( 'data-label' );
		}

		node.removeAttribute( 'aria-busy' );
		node.disabled = false;
	}

	/**
	 * Remove every field error.
	 *
	 * @param {HTMLFormElement} form The form.
	 * @return {void}
	 */
	function clearErrors( form ) {
		var nodes = form.querySelectorAll( '.mm-suc-p-page-error' );

		Array.prototype.forEach.call( nodes, function ( node ) {
			node.textContent = '';
			node.removeAttribute( 'data-shown' );
		} );

		var inputs = form.querySelectorAll( '[aria-invalid]' );

		Array.prototype.forEach.call( inputs, function ( node ) {
			node.removeAttribute( 'aria-invalid' );
			node.removeAttribute( 'aria-describedby' );
		} );
	}

	/**
	 * Show field errors as instructions, linked to their inputs.
	 *
	 * @param {HTMLFormElement} form   The form.
	 * @param {Object}          errors Field name to message.
	 * @return {void}
	 */
	function showErrors( form, errors ) {
		var firstInvalid = null;

		Object.keys( errors ).forEach( function ( field ) {
			var input = form.querySelector( '[name="' + field + '"]' );
			var slot = form.querySelector( '#mm-suc-p-' + field + '-error' );

			if ( slot ) {
				slot.textContent = errors[ field ];
				slot.setAttribute( 'data-shown', 'true' );
			}

			if ( input ) {
				input.setAttribute( 'aria-invalid', 'true' );

				if ( slot && slot.id ) {
					input.setAttribute( 'aria-describedby', slot.id );
				}

				if ( ! firstInvalid ) {
					firstInvalid = input;
				}
			}
		} );

		if ( firstInvalid ) {
			firstInvalid.focus();
		}
	}
}() );
