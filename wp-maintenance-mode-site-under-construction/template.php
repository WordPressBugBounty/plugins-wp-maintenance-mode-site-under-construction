<?php
/**
 * Frontend engine.
 *
 * Request interception, the standalone maintenance document, and the public
 * contact endpoint.
 *
 * @package wp-maintenance-mode-site-under-construction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Interception
 * ---------------------------------------------------------------------- */

/**
 * Decide whether this request sees the site or the maintenance page.
 *
 * Runs at priority 0 on template_redirect so no theme template is loaded when
 * maintenance is served.
 *
 * @return void
 */
function mm_suc_p_template_redirect() {
	if ( mm_suc_p_is_infrastructure_request() ) {
		return;
	}

	$options = mm_suc_p_get_options();

	// A signed preview renders the page for an administrator, whatever the switch says.
	if ( mm_suc_p_is_preview_request() ) {
		nocache_headers();
		mm_suc_p_render_document( $options, true );

		return;
	}

	if ( empty( $options['enabled'] ) ) {
		return;
	}

	// Expired, with auto-disable on: turn it off and let the request through.
	$end = mm_suc_p_end_timestamp( $options );

	if ( $end && ! empty( $options['auto_disable'] ) && time() >= $end ) {
		$options['enabled'] = 0;
		mm_suc_p_update_options( $options );

		return;
	}

	if ( mm_suc_p_user_can_bypass( $options ) ) {
		return;
	}

	mm_suc_p_render_document( $options, false );
}
add_action( 'template_redirect', 'mm_suc_p_template_redirect', 0 );

/**
 * Requests that must never be intercepted.
 *
 * @return bool
 */
function mm_suc_p_is_infrastructure_request() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return true;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return true;
	}

	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		return true;
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return true;
	}

	$self = isset( $_SERVER['PHP_SELF'] ) ? sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) ) : '';

	if ( '' !== $self && false !== strpos( $self, 'wp-login.php' ) ) {
		return true;
	}

	$script = isset( $_SERVER['SCRIPT_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : '';

	if ( '' !== $script && false !== strpos( $script, 'wp-login.php' ) ) {
		return true;
	}

	return false;
}

/**
 * Is this a signed preview request from someone who may manage the site.
 *
 * @return bool
 */
function mm_suc_p_is_preview_request() {
	if ( empty( $_GET['mm_suc_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the nonce is verified immediately below.
		return false;
	}

	$nonce = isset( $_GET['mm_suc_preview_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['mm_suc_preview_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'mm_suc_preview' ) ) {
		return false;
	}

	return current_user_can( 'manage_options' );
}

/* -------------------------------------------------------------------------
 * The standalone document
 * ---------------------------------------------------------------------- */

/**
 * Print the maintenance document and end the request.
 *
 * @param array $options    Configuration.
 * @param bool  $is_preview Whether this is an administrator preview.
 * @return void
 */
function mm_suc_p_render_document( $options, $is_preview ) {
	$template = MM_SUC_P_Template_Registry::resolve( isset( $options['template'] ) ? $options['template'] : '' );

	if ( ! $is_preview ) {
		status_header( 503 );
		header( 'Retry-After: 3600' );
		nocache_headers();
	}

	$title = wp_strip_all_tags( (string) mm_suc_p_option_value( $options, 'headline' ) );

	if ( '' === $title ) {
		$title = get_bloginfo( 'name' );
	}

	$style_url    = MM_SUC_P_URL . 'assets/css/frontend.css';
	$template_css = $template ? $template->get_style_url() : '';
	$script_url   = MM_SUC_P_URL . 'assets/js/frontend.js';

	$config = array(
		'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
		'action'   => 'mm_suc_contact',
		'nonce'    => wp_create_nonce( 'mm_suc_contact' ),
		'caps'     => array(
			'name'    => (int) MM_SUC_P_CAP_NAME,
			'email'   => (int) MM_SUC_P_CAP_EMAIL,
			'message' => (int) MM_SUC_P_CAP_MESSAGE,
		),
		'preview'  => (bool) $is_preview,
		'strings'  => mm_suc_p_frontend_strings(),
	);

	header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

	echo '<!DOCTYPE html>';
	echo '<html ' . get_language_attributes() . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-generated attributes.
	echo '<head>';
	echo '<meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '" />';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
	echo '<meta name="robots" content="noindex, nofollow" />';
	echo '<title>' . esc_html( $title ) . '</title>';
	echo '<link rel="stylesheet" href="' . esc_url( $style_url . '?ver=' . MM_SUC_P_VERSION ) . '" />'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Standalone maintenance document, wp_head() is intentionally bypassed.

	if ( '' !== $template_css ) {
		echo '<link rel="stylesheet" href="' . esc_url( $template_css . '?ver=' . $template->get_style_version() ) . '" />'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Standalone maintenance document, wp_head() is intentionally bypassed.
	}

	echo '<style>' . mm_suc_p_document_style() . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static stylesheet text.
	echo '</head>';
	echo '<body class="mm-suc-p-page-doc">';

	if ( $is_preview ) {
		mm_suc_p_preview_banner();
	}

	echo MM_SUC_P_Template_Controller::render( $options, 'page' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the controller escapes every value it emits.

	echo '<script id="mm-suc-p-config" type="application/json">' . wp_json_encode( $config ) . '</script>';
	echo '<script src="' . esc_url( $script_url . '?ver=' . MM_SUC_P_VERSION ) . '" defer></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Standalone maintenance document, wp_footer() is intentionally bypassed.
	echo '</body></html>';

	exit;
}

/**
 * Read an option value with the default as a fallback.
 *
 * @param array  $options Configuration.
 * @param string $key     Field name.
 * @return mixed
 */
function mm_suc_p_option_value( $options, $key ) {
	return isset( $options[ $key ] ) ? $options[ $key ] : '';
}

/**
 * The document-level rules the shared stylesheet may not contain.
 *
 * assets/css/frontend.css is loaded inside wp-admin for the live preview, so it
 * never styles a bare element. The standalone document needs those rules, and
 * this is where they live - printed only when the page owns <body>.
 *
 * @return string
 */
function mm_suc_p_document_style() {
	return 'html{box-sizing:border-box}'
		. 'body.mm-suc-p-page-doc{margin:0;padding:0;min-height:100vh;background:#0b1220;'
		. '-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility}'
		. 'body.mm-suc-p-page-doc .mm-suc-p-page{min-height:100vh}';
}

/**
 * The floating banner shown to an administrator previewing the page.
 *
 * @return void
 */
function mm_suc_p_preview_banner() {
	$live = ! mm_suc_p_is_enabled();

	$message = $live
		? __( 'Preview - maintenance mode is off, so visitors are seeing your site as usual.', 'wp-maintenance-mode-site-under-construction' )
		: __( 'Preview - this is what visitors are seeing right now.', 'wp-maintenance-mode-site-under-construction' );

	echo '<style>'
		. '.mm-suc-p-preview-banner{position:fixed;inset-block-start:0;inset-inline:0;z-index:1000;display:flex;gap:8px;'
		. 'align-items:center;justify-content:center;padding:10px 16px;background:#fef3c7;color:#7c4a06;'
		. 'font:600 13px/1.4 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;text-align:center}'
		. '.mm-suc-p-preview-banner a{color:inherit}'
		. '</style>';

	printf(
		'<div class="mm-suc-p-preview-banner" role="status">%1$s <a href="%2$s">%3$s</a></div>',
		esc_html( $message ),
		esc_url( mm_suc_p_settings_url() ),
		esc_html__( 'Back to the editor', 'wp-maintenance-mode-site-under-construction' )
	);
}

/**
 * Translatable strings the public script needs.
 *
 * @return array
 */
function mm_suc_p_frontend_strings() {
	return array(
		'required'   => __( 'Fill this in so we can reply.', 'wp-maintenance-mode-site-under-construction' ),
		'email'      => __( 'Enter an email address like name@example.com.', 'wp-maintenance-mode-site-under-construction' ),
		'sent'       => __( 'Thanks - your message is on its way.', 'wp-maintenance-mode-site-under-construction' ),
		'failed'     => __( 'We could not send that. Try again shortly.', 'wp-maintenance-mode-site-under-construction' ),
		'network'    => __( 'That did not reach us. Check your connection and try again.', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: time duration remaining until launch, for example "3 days, 4 hours". */
		'remaining'  => __( '%s remaining', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: number of days. */
		'day'        => __( '%s day', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: number of days. */
		'days'       => __( '%s days', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: number of hours. */
		'hour'       => __( '%s hour', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: number of hours. */
		'hours'      => __( '%s hours', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: number of minutes. */
		'minute'     => __( '%s minute', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: number of minutes. */
		'minutes'    => __( '%s minutes', 'wp-maintenance-mode-site-under-construction' ),
		'finished'   => __( 'The wait is over.', 'wp-maintenance-mode-site-under-construction' ),
	);
}

/**
 * Build the inline style attribute for the page root.
 *
 * Five properties come from the site owner's saved settings; the rest come
 * from the active template's palette. Every value is validated before it
 * reaches CSS - no option value is ever interpolated raw, and the background
 * is the only one that becomes a url(), so it is escaped as one.
 *
 * @param array                  $options  Configuration.
 * @param MM_SUC_P_Template|null $template Active template.
 * @return string
 */
function mm_suc_p_inline_style( $options, $template ) {
	$palette = $template ? $template->get_palette() : MM_SUC_P_Template::default_palette();

	$accent = sanitize_hex_color( isset( $options['accent_color'] ) ? $options['accent_color'] : '' );
	$ink    = sanitize_hex_color( isset( $options['text_color'] ) ? $options['text_color'] : '' );

	if ( ! $accent ) {
		$accent = $palette['accent'];
	}

	if ( ! $ink ) {
		$ink = $palette['ink'];
	}

	$overlay = isset( $options['overlay_opacity'] ) ? min( 100, absint( $options['overlay_opacity'] ) ) : (int) $palette['overlay'];
	$blur    = isset( $options['glass_strength'] ) ? min( 40, absint( $options['glass_strength'] ) ) : (int) $palette['blur'];
	$image   = MM_SUC_P_Template_Controller::background_url( $options, $template );

	$values = array(
		'--mm-suc-p-page-accent'    => $accent,
		'--mm-suc-p-page-ink'       => $ink,
		'--mm-suc-p-page-overlay'   => number_format( $overlay / 100, 2, '.', '' ),
		'--mm-suc-p-page-blur'      => $blur . 'px',
		'--mm-suc-p-page-bg-image'  => '' === $image ? 'none' : 'url(' . esc_url( $image ) . ')',
		'--mm-suc-p-page-veil'      => $palette['veil'],
		'--mm-suc-p-page-card'      => MM_SUC_P_Template_Controller::rgba( $palette['card'], $palette['card_alpha'] ),
		'--mm-suc-p-page-line'      => MM_SUC_P_Template_Controller::rgba( $palette['ink'], $palette['line_alpha'] ),
		'--mm-suc-p-page-on-accent' => $palette['on_accent'],
	);

	$out = '';

	foreach ( mm_suc_p_style_contract() as $property => $spec ) {
		if ( isset( $values[ $property ] ) ) {
			$out .= $property . ':' . $values[ $property ] . ';';
			unset( $values[ $property ] );
		}
	}

	foreach ( $values as $property => $value ) {
		$out .= $property . ':' . $value . ';';
	}

	return $out;
}

/* -------------------------------------------------------------------------
 * Contact endpoint
 * ---------------------------------------------------------------------- */

/**
 * Handle a public contact submission.
 *
 * Nonce, honeypot, rate limit, validation, mail - in that order.
 *
 * @return void
 */
function mm_suc_p_contact() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'mm_suc_contact' ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Your session expired. Reload the page and try again.', 'wp-maintenance-mode-site-under-construction' ) ),
			403
		);
	}

	// Honeypot: answer exactly as a successful send would, and send nothing.
	$trap = isset( $_POST['website'] ) ? trim( (string) wp_unslash( $_POST['website'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only its emptiness is used.

	if ( '' !== $trap ) {
		wp_send_json_success(
			array( 'message' => __( 'Thanks - your message is on its way.', 'wp-maintenance-mode-site-under-construction' ) )
		);
	}

	if ( ! mm_suc_p_rate_limit_ok() ) {
		wp_send_json_error(
			array( 'message' => __( 'Too many messages. Try again in 15 minutes.', 'wp-maintenance-mode-site-under-construction' ) ),
			429
		);
	}

	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	$name    = mm_suc_p_truncate( $name, MM_SUC_P_CAP_NAME );
	$email   = mm_suc_p_truncate( $email, MM_SUC_P_CAP_EMAIL );
	$message = mm_suc_p_truncate( $message, MM_SUC_P_CAP_MESSAGE );

	$errors = array();

	if ( '' === $name ) {
		$errors['name'] = __( 'Fill this in so we can reply.', 'wp-maintenance-mode-site-under-construction' );
	}

	if ( '' === $email || ! is_email( $email ) ) {
		$errors['email'] = __( 'Enter an email address like name@example.com.', 'wp-maintenance-mode-site-under-construction' );
	}

	if ( '' === $message ) {
		$errors['message'] = __( 'Tell us what you need and we will get back to you.', 'wp-maintenance-mode-site-under-construction' );
	}

	if ( $errors ) {
		wp_send_json_error(
			array(
				'message' => __( 'Check the highlighted fields and send again.', 'wp-maintenance-mode-site-under-construction' ),
				'fields'  => $errors,
			),
			400
		);
	}

	$options   = mm_suc_p_get_options();
	$recipient = isset( $options['contact_email'] ) ? sanitize_email( $options['contact_email'] ) : '';

	if ( ! $recipient || ! is_email( $recipient ) ) {
		$recipient = get_option( 'admin_email' );
	}

	// Store first in Zero-DB file system JSON storage.
	$record = array(
		'id'        => 'msg_' . wp_generate_uuid4(),
		'timestamp' => time(),
		'date'      => current_time( 'mysql' ),
		'name'      => $name,
		'email'     => $email,
		'message'   => $message,
		'read'      => false,
	);
	mm_suc_p_save_message( $record );

	// Attempt email dispatch if mail/SMTP is configured.
	$subject = sprintf(
		/* translators: %s: site name. */
		__( '[%s] Message from your maintenance page', 'wp-maintenance-mode-site-under-construction' ),
		wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
	);

	$body = sprintf(
		/* translators: 1: sender name, 2: sender email, 3: message body. */
		__( "Name: %1\$s\nEmail: %2\$s\n\n%3\$s", 'wp-maintenance-mode-site-under-construction' ),
		$name,
		$email,
		$message
	);

	$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );

	wp_mail( $recipient, $subject, $body, $headers );

	wp_send_json_success(
		array( 'message' => __( 'Thanks - your message is on its way.', 'wp-maintenance-mode-site-under-construction' ) )
	);
}
add_action( 'wp_ajax_mm_suc_contact', 'mm_suc_p_contact' );
add_action( 'wp_ajax_nopriv_mm_suc_contact', 'mm_suc_p_contact' );

/**
 * Truncate to a cap, multibyte-safe.
 *
 * @param string $value Input.
 * @param int    $cap   Maximum characters.
 * @return string
 */
function mm_suc_p_truncate( $value, $cap ) {
	$value = trim( (string) $value );

	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $value, 0, (int) $cap );
	}

	return substr( $value, 0, (int) $cap );
}

/**
 * Four submissions per fifteen minutes, per hashed IP.
 *
 * The address is never stored: the transient key is an HMAC of it.
 *
 * @return bool True while the caller is inside the limit.
 */
function mm_suc_p_rate_limit_ok() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	if ( '' === $ip ) {
		return true;
	}

	$key   = 'mm_suc_p_rl_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
	$count = (int) get_transient( $key );

	if ( $count >= 4 ) {
		return false;
	}

	set_transient( $key, $count + 1, 15 * MINUTE_IN_SECONDS );

	return true;
}
