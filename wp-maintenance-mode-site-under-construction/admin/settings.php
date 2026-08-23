<?php
/**
 * Admin controller.
 *
 * Menu registration, asset enqueueing, the editor screen, sanitization, and the
 * asynchronous save and preview endpoints.
 *
 * @package wp-maintenance-mode-site-under-construction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Menu
 * ---------------------------------------------------------------------- */

/**
 * Register the editor screen and messages list screen.
 *
 * @return void
 */
function mm_suc_p_admin_menu() {
	add_menu_page(
		__( 'Maintenance Mode', 'wp-maintenance-mode-site-under-construction' ),
		__( 'Maintenance', 'wp-maintenance-mode-site-under-construction' ),
		'manage_options',
		MM_SUC_P_PAGE,
		'mm_suc_p_render_editor',
		'dashicons-hammer',
		81
	);

	add_submenu_page(
		MM_SUC_P_PAGE,
		__( 'Maintenance Settings', 'wp-maintenance-mode-site-under-construction' ),
		__( 'Settings', 'wp-maintenance-mode-site-under-construction' ),
		'manage_options',
		MM_SUC_P_PAGE,
		'mm_suc_p_render_editor'
	);

	add_submenu_page(
		MM_SUC_P_PAGE,
		__( 'Messages list', 'wp-maintenance-mode-site-under-construction' ),
		__( 'Messages list', 'wp-maintenance-mode-site-under-construction' ),
		'manage_options',
		MM_SUC_P_PAGE_MESSAGES,
		'mm_suc_p_render_messages'
	);
}
add_action( 'admin_menu', 'mm_suc_p_admin_menu' );

/**
 * Keep bookmarks and third-party links to the v3 slug working.
 *
 * @return void
 */
function mm_suc_p_legacy_slug_redirect() {
	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.

	if ( MM_SUC_P_PAGE_LEGACY !== $page ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_safe_redirect( mm_suc_p_settings_url() );
	exit;
}
add_action( 'admin_init', 'mm_suc_p_legacy_slug_redirect' );

/* -------------------------------------------------------------------------
 * Assets
 * ---------------------------------------------------------------------- */

/**
 * Enqueue the workspace assets on the editor and messages screens.
 *
 * frontend.css is enqueued here too: the live preview renders the real public
 * document inside the workspace.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function mm_suc_p_admin_assets( $hook ) {
	$page             = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check.
	$is_plugin_screen = in_array( $page, array( MM_SUC_P_PAGE, MM_SUC_P_PAGE_MESSAGES ), true );

	if ( ! $is_plugin_screen && 'toplevel_page_' . MM_SUC_P_PAGE !== $hook && strpos( (string) $hook, MM_SUC_P_PAGE_MESSAGES ) === false ) {
		return;
	}

	$options  = mm_suc_p_get_options();
	$template = MM_SUC_P_Template_Registry::resolve( $options['template'] );

	wp_enqueue_media();

	MM_SUC_P_Template_Controller::register_styles( $template, true );

	wp_enqueue_style(
		'mm-suc-p-admin',
		MM_SUC_P_URL . 'assets/css/admin.css',
		array( 'mm-suc-p-frontend' ),
		MM_SUC_P_VERSION
	);

	wp_enqueue_script(
		'mm-suc-p-admin',
		MM_SUC_P_URL . 'assets/js/admin.js',
		array(),
		MM_SUC_P_VERSION,
		true
	);

	$contract = array();

	foreach ( mm_suc_p_style_contract() as $property => $spec ) {
		$contract[ $spec['option'] ] = $property;
	}

	wp_localize_script(
		'mm-suc-p-admin',
		'mmSucPData',
		array(
			'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
			'nonce'               => wp_create_nonce( 'mm_suc_admin' ),
			'saveAction'          => 'mm_suc_save_settings',
			'viewAction'          => 'mm_suc_render_preview',
			'presetAction'                 => 'mm_suc_save_preset',
			'deleteMessageAction'          => 'mm_suc_delete_message',
			'clearMessagesAction'          => 'mm_suc_clear_messages',
			'markReadAction'               => 'mm_suc_mark_read',
			'toggleUninstallCleanupAction' => 'mm_suc_toggle_uninstall_cleanup',
			'dismissRatingAction'          => 'mm_suc_dismiss_rating',
			'contract'                     => $contract,
			'templates'           => MM_SUC_P_Template_Registry::to_array(),
			'options'             => mm_suc_p_client_options( $options ),
			'parts'               => MM_SUC_P_Template_View::parts(),
			'icons'               => array(
				'check'        => mm_suc_p_icon( 'check' ),
				'close'        => mm_suc_p_icon( 'close' ),
				'warning'      => mm_suc_p_icon( 'warning' ),
				'contact-mail' => mm_suc_p_icon( 'contact-mail' ),
				'star'         => mm_suc_p_icon( 'star' ),
				'trash'        => mm_suc_p_icon( 'trash' ),
			),
			'strings'             => mm_suc_p_admin_strings(),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'mm_suc_p_admin_assets' );

/**
 * The configuration the editor script needs, with nothing sensitive in it.
 *
 * @param array $options Configuration.
 * @return array
 */
function mm_suc_p_client_options( $options ) {
	return array(
		'enabled'         => (int) $options['enabled'],
		'template'        => (string) $options['template'],
		'layout'          => (string) $options['layout'],
		'countdown'       => (int) $options['countdown'],
		'contact_enabled' => (int) $options['contact_enabled'],
		'end_datetime'    => (string) $options['end_datetime'],
		'endIso'          => mm_suc_p_end_iso( $options ),
		'timezone'        => mm_suc_p_timezone_label(),
	);
}

/**
 * Translatable strings the editor script needs.
 *
 * @return array
 */
function mm_suc_p_admin_strings() {
	return array(
		'saving'        => __( 'Saving…', 'wp-maintenance-mode-site-under-construction' ),
		'save'          => __( 'Save changes', 'wp-maintenance-mode-site-under-construction' ),
		'saved'         => __( 'Settings saved.', 'wp-maintenance-mode-site-under-construction' ),
		'saveFailed'    => __( 'Could not save. Check your connection and try again.', 'wp-maintenance-mode-site-under-construction' ),
		'expired'       => __( 'Could not save - your session expired. Reload the page and try again.', 'wp-maintenance-mode-site-under-construction' ),
		'dismiss'       => __( 'Dismiss', 'wp-maintenance-mode-site-under-construction' ),
		'noticeTray'    => __( 'Notices from other plugins', 'wp-maintenance-mode-site-under-construction' ),
		'chooseLogo'    => __( 'Choose a logo', 'wp-maintenance-mode-site-under-construction' ),
		'chooseImage'   => __( 'Choose a background image', 'wp-maintenance-mode-site-under-construction' ),
		'use'           => __( 'Use this image', 'wp-maintenance-mode-site-under-construction' ),
		'unsaved'       => __( 'Unsaved change', 'wp-maintenance-mode-site-under-construction' ),
		'live'                   => __( 'Site is live', 'wp-maintenance-mode-site-under-construction' ),
		'maintenance'            => __( 'Maintenance mode is on', 'wp-maintenance-mode-site-under-construction' ),
		'consequenceLive'        => __( 'Site is live. Visitors see the website normally.', 'wp-maintenance-mode-site-under-construction' ),
		'consequenceMaintenance' => __( 'Visitors see the maintenance page. You and other administrators still see the site.', 'wp-maintenance-mode-site-under-construction' ),
		'consequencePastDate'    => __( 'Maintenance mode will not run and your site is still live because your chosen date is older than now.', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: contrast ratio number against background, for example 2.4. */
		'contrastLow'            => __( 'Low contrast (%s:1). Some visitors will not be able to read this.', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: contrast ratio number against background, for example 4.8. */
		'contrastOk'             => __( 'Contrast %s:1 against this background.', 'wp-maintenance-mode-site-under-construction' ),
		'durationPast'           => __( 'That time has passed - the countdown reads zero.', 'wp-maintenance-mode-site-under-construction' ),
		'durationPastWarning'    => __( 'That time has passed. Maintenance mode will not run and your site will remain live.', 'wp-maintenance-mode-site-under-construction' ),
		'durationNone'           => __( 'No end time set, so no countdown is shown.', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: time duration until launch, for example "3 days, 4 hours". */
		'durationFrom'  => __( '%s from now', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: number of days. */
		'day'           => __( '%s day', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: number of days. */
		'days'          => __( '%s days', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: number of hours. */
		'hour'          => __( '%s hour', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: number of hours. */
		'hours'         => __( '%s hours', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: number of minutes. */
		'minute'        => __( '%s minute', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: number of minutes. */
		'minutes'       => __( '%s minutes', 'wp-maintenance-mode-site-under-construction' ),
		'templateApply' => __( 'Template applied. Its colours are now yours to adjust.', 'wp-maintenance-mode-site-under-construction' ),
		'colourMap'     => __( 'Colours on this design', 'wp-maintenance-mode-site-under-construction' ),
		'colourMapEmpty' => __( 'This design paints its own colours, so none of these apply to it.', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: a list of the parts a colour reaches, for example "the headline, the message and the site address". */
		'colourMapReach' => __( 'Sets %s on this design - they all use this one colour.', 'wp-maintenance-mode-site-under-construction' ),
		'and'           => __( 'and', 'wp-maintenance-mode-site-under-construction' ),
		/* translators: %s: a list of the other parts a colour reaches. */
		'colourMapAlso' => __( 'The same colour also sets %s.', 'wp-maintenance-mode-site-under-construction' ),
		'presetSaving'         => __( 'Saving…', 'wp-maintenance-mode-site-under-construction' ),
		'presetSave'           => __( 'Save as preset', 'wp-maintenance-mode-site-under-construction' ),
		'presetFailed'         => __( 'Could not save the preset. Check your connection and try again.', 'wp-maintenance-mode-site-under-construction' ),
		'deleteMessageConfirm' => __( 'Are you sure you want to delete this message?', 'wp-maintenance-mode-site-under-construction' ),
		'clearAllConfirm'      => __( 'Are you sure you want to delete all messages? This cannot be undone.', 'wp-maintenance-mode-site-under-construction' ),
		'messageDeleted'       => __( 'Message deleted.', 'wp-maintenance-mode-site-under-construction' ),
		'messagesCleared'      => __( 'All messages cleared.', 'wp-maintenance-mode-site-under-construction' ),
		'deleteFailed'         => __( 'Could not delete the message. Try again.', 'wp-maintenance-mode-site-under-construction' ),
		'clearFailed'          => __( 'Could not clear messages. Try again.', 'wp-maintenance-mode-site-under-construction' ),
		'messageSingle'        => __( 'Message', 'wp-maintenance-mode-site-under-construction' ),
		'messagePlural'        => __( 'Messages', 'wp-maintenance-mode-site-under-construction' ),
		'unreadCount'          => __( 'unread', 'wp-maintenance-mode-site-under-construction' ),
	);
}

/* -------------------------------------------------------------------------
 * Sanitization
 * ---------------------------------------------------------------------- */

/**
 * Sanitize a submitted configuration.
 *
 * A field that is not handled here does not exist. Enums are whitelisted,
 * strings are capped, colours go through sanitize_hex_color().
 *
 * The editable-part caps are eyebrow 80, headline 120, message 600 and
 * contact_button 60 - the same numbers the inline editor truncates at and the
 * same numbers recorded in design-system/tokens/page.tokens.json.
 *
 * @param array $raw     Untrusted input.
 * @param array $current The configuration being replaced.
 * @return array
 */
function mm_suc_p_sanitize_settings( $raw, $current ) {
	$out = $current;

	$out['schema_version']       = 2;
	$out['enabled']              = empty( $raw['enabled'] ) ? 0 : 1;
	$out['auto_disable']         = empty( $raw['auto_disable'] ) ? 0 : 1;
	$out['countdown']            = empty( $raw['countdown'] ) ? 0 : 1;
	$out['contact_enabled']      = empty( $raw['contact_enabled'] ) ? 0 : 1;

	if ( isset( $raw['delete_messages_on_uninstall'] ) ) {
		$out['delete_messages_on_uninstall'] = empty( $raw['delete_messages_on_uninstall'] ) ? 0 : 1;
	}

	// Schedule: a datetime-local value, stored as given, in site time.
	$end = isset( $raw['end_datetime'] ) ? sanitize_text_field( $raw['end_datetime'] ) : '';

	if ( '' === $end || preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/', $end ) ) {
		$out['end_datetime'] = $end;
	}

	// Template: only a slug that is actually installed.
	$template = isset( $raw['template'] ) ? sanitize_key( $raw['template'] ) : '';

	if ( MM_SUC_P_Template_Registry::is_installed( $template ) ) {
		$out['template'] = $template;
	} elseif ( ! MM_SUC_P_Template_Registry::is_installed( $out['template'] ) ) {
		$out['template'] = MM_SUC_P_Template_Registry::default_slug();
	}

	/*
	 * A template proposes a palette. When the template changes and the owner is
	 * still on the previous template's colours, adopt the new ones; anything the
	 * owner has actually changed is kept. The editor does the same thing live -
	 * this is what keeps a save without JavaScript coherent.
	 */
	if ( $out['template'] !== $current['template'] ) {
		$previous = MM_SUC_P_Template_Registry::get( $current['template'] );
		$next     = MM_SUC_P_Template_Registry::get( $out['template'] );

		if ( $next ) {
			$before = $previous ? $previous->get_palette_options() : array();
			$after  = $next->get_palette_options();

			foreach ( $after as $field => $value ) {
				$submitted = isset( $raw[ $field ] ) ? $raw[ $field ] : null;
				$untouched = ( null === $submitted )
					|| ( isset( $before[ $field ] ) && (string) $submitted === (string) $before[ $field ] );

				if ( $untouched ) {
					$raw[ $field ] = $value;
				}
			}
		}
	}

	// Layout: stored enum, whitelisted.
	$layouts = array( 'centered', 'left', 'minimal', 'split' );
	$layout  = isset( $raw['layout'] ) ? sanitize_key( $raw['layout'] ) : '';

	if ( in_array( $layout, $layouts, true ) ) {
		$out['layout'] = $layout;
	} elseif ( $out['template'] !== $current['template'] ) {
		// A preset records the layout it was saved with. Adopt it when the
		// caller did not state one, the way the editor does live.
		$next = MM_SUC_P_Template_Registry::get( $out['template'] );

		if ( $next && $next->get_layout() ) {
			$out['layout'] = $next->get_layout();
		}
	}

	// Background source: stored enum, whitelisted.
	$sources    = array( 'template', 'custom', 'none' );
	$background = isset( $raw['background'] ) ? sanitize_key( $raw['background'] ) : '';

	if ( in_array( $background, $sources, true ) ) {
		$out['background'] = $background;
	}

	$out['custom_background_id'] = isset( $raw['custom_background_id'] ) ? absint( $raw['custom_background_id'] ) : 0;
	$out['logo_id']              = isset( $raw['logo_id'] ) ? absint( $raw['logo_id'] ) : 0;

	// Editable parts. The caps match the inline editor exactly.
	$caps = array(
		'eyebrow'        => 80,
		'headline'       => 120,
		'message'        => 600,
		'contact_button' => 60,
	);

	foreach ( $caps as $field => $cap ) {
		if ( ! isset( $raw[ $field ] ) ) {
			continue;
		}

		$value = ( 'message' === $field )
			? sanitize_textarea_field( $raw[ $field ] )
			: sanitize_text_field( $raw[ $field ] );

		$out[ $field ] = mm_suc_p_truncate( $value, $cap );
	}

	// Contact address: empty means the site admin address.
	$email = isset( $raw['contact_email'] ) ? sanitize_email( $raw['contact_email'] ) : '';

	if ( '' === $email || is_email( $email ) ) {
		$out['contact_email'] = mm_suc_p_truncate( $email, MM_SUC_P_CAP_EMAIL );
	}

	// Palette.
	$accent = isset( $raw['accent_color'] ) ? sanitize_hex_color( $raw['accent_color'] ) : '';
	$ink    = isset( $raw['text_color'] ) ? sanitize_hex_color( $raw['text_color'] ) : '';

	if ( $accent ) {
		$out['accent_color'] = $accent;
	}

	if ( $ink ) {
		$out['text_color'] = $ink;
	}

	if ( isset( $raw['overlay_opacity'] ) ) {
		$out['overlay_opacity'] = min( 100, absint( $raw['overlay_opacity'] ) );
	}

	if ( isset( $raw['glass_strength'] ) ) {
		$out['glass_strength'] = min( 40, absint( $raw['glass_strength'] ) );
	}

	// Roles: only roles that exist, and the administrator is added back whatever was posted.
	$editable = array_keys( get_editable_roles() );
	$roles    = isset( $raw['bypass_roles'] ) ? (array) $raw['bypass_roles'] : array();
	$clean    = array();

	foreach ( $roles as $role ) {
		$role = sanitize_key( $role );

		if ( in_array( $role, $editable, true ) ) {
			$clean[] = $role;
		}
	}

	if ( ! in_array( 'administrator', $clean, true ) ) {
		$clean[] = 'administrator';
	}

	$out['bypass_roles'] = array_values( array_unique( $clean ) );

	return $out;
}

/* -------------------------------------------------------------------------
 * AJAX
 * ---------------------------------------------------------------------- */

/**
 * Save the configuration.
 *
 * @return void
 */
function mm_suc_p_ajax_save() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'mm_suc_admin' ) ) {
		wp_send_json_error( array( 'message' => __( 'Could not save - your session expired. Reload the page and try again.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to change these settings.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	$raw = isset( $_POST['settings'] ) ? json_decode( wp_unslash( $_POST['settings'] ), true ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded then sanitized field by field below.

	if ( ! is_array( $raw ) ) {
		wp_send_json_error( array( 'message' => __( 'Could not save - the settings did not arrive intact. Try again.', 'wp-maintenance-mode-site-under-construction' ) ), 400 );
	}

	$current = mm_suc_p_get_options();
	$clean   = mm_suc_p_sanitize_settings( $raw, $current );

	mm_suc_p_update_options( $clean );

	$end     = mm_suc_p_end_timestamp( $clean );
	$is_past = ( $end && time() >= $end );

	$message = __( 'Settings saved.', 'wp-maintenance-mode-site-under-construction' );
	$warning = false;

	if ( ! empty( $clean['enabled'] ) && $is_past ) {
		$message = __( 'Settings saved. Maintenance mode will not run and your site is still live because your chosen date is older than now.', 'wp-maintenance-mode-site-under-construction' );
		$warning = true;
	}

	wp_send_json_success(
		array(
			'message' => $message,
			'warning' => $warning,
			'options' => mm_suc_p_client_options( $clean ),
		)
	);
}
add_action( 'wp_ajax_mm_suc_save_settings', 'mm_suc_p_ajax_save' );

/**
 * Render the preview for a configuration that has not been saved yet.
 *
 * The preview is the real template view, rendered by the same controller the
 * public page uses - that is what makes it trustworthy when the template
 * changes.
 *
 * @return void
 */
function mm_suc_p_ajax_preview() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'mm_suc_admin' ) ) {
		wp_send_json_error( array( 'message' => __( 'Your session expired. Reload the page.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to preview this.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	$raw = isset( $_POST['settings'] ) ? json_decode( wp_unslash( $_POST['settings'] ), true ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded then sanitized field by field below.

	if ( ! is_array( $raw ) ) {
		$raw = array();
	}

	$options  = mm_suc_p_sanitize_settings( $raw, mm_suc_p_get_options() );
	$template = MM_SUC_P_Template_Registry::resolve( $options['template'] );

	wp_send_json_success(
		array(
			'html'     => MM_SUC_P_Template_Controller::render( $options, 'editor' ),
			'styleUrl' => $template ? $template->get_style_url() : '',
			'styleId'  => $template ? 'mm-suc-p-template-' . $template->get_slug() . '-css' : '',
			'template' => $template ? $template->get_slug() : '',
		)
	);
}
add_action( 'wp_ajax_mm_suc_render_preview', 'mm_suc_p_ajax_preview' );


/**
 * Save the current design as a new template folder.
 *
 * This is the template generator: it writes a real template - manifest,
 * stylesheet and background - into uploads/mm-suc-p-templates/<slug>/, where
 * the registry finds it exactly like a bundled one. The folder can be copied to
 * another site, or deleted to remove the preset.
 *
 * No PHP is written: the manifest names the bundled template whose arrangement
 * it reuses, so nothing executable is ever created in the uploads directory.
 *
 * @return void
 */
function mm_suc_p_ajax_save_preset() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'mm_suc_admin' ) ) {
		wp_send_json_error( array( 'message' => __( 'Your session expired. Reload the page and try again.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to save a preset.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	$root = mm_suc_p_preset_root();

	if ( ! $root ) {
		wp_send_json_error( array( 'message' => __( 'Presets need the uploads folder, and WordPress could not find it.', 'wp-maintenance-mode-site-under-construction' ) ), 500 );
	}

	$raw = isset( $_POST['settings'] ) ? json_decode( wp_unslash( $_POST['settings'] ), true ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded then sanitized field by field.

	if ( ! is_array( $raw ) ) {
		$raw = array();
	}

	$options = mm_suc_p_sanitize_settings( $raw, mm_suc_p_get_options() );
	$source  = MM_SUC_P_Template_Registry::resolve( $options['template'] );
	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$name    = mm_suc_p_truncate( $name, 60 );

	if ( '' === $name ) {
		$name = $source
			/* translators: %s: the name of the template the preset was made from. */
			? sprintf( __( '%s copy', 'wp-maintenance-mode-site-under-construction' ), $source->get_name() )
			: __( 'Saved preset', 'wp-maintenance-mode-site-under-construction' );
	}

	$slug = mm_suc_p_unique_preset_slug( $name, $root['dir'] );
	$dir  = trailingslashit( $root['dir'] . $slug );

	if ( ! wp_mkdir_p( $dir ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Could not write to the uploads folder. Check its permissions and try again.', 'wp-maintenance-mode-site-under-construction' ) ),
			500
		);
	}

	$manifest = array(
		'name'        => $name,
		'description' => $source
			/* translators: %s: the name of the template the preset was made from. */
			? sprintf( __( 'Saved from %s, with your own colours.', 'wp-maintenance-mode-site-under-construction' ), $source->get_name() )
			: __( 'A design saved from the editor.', 'wp-maintenance-mode-site-under-construction' ),
		'version'     => MM_SUC_P_VERSION,
		'author'      => get_bloginfo( 'name' ),
		'order'       => 900,
		'layout'      => $options['layout'],
		'style'       => '',
		'background'  => '',
		'thumbnail'   => '',
		'supports'    => array( 'countdown' => true, 'contact' => true, 'logo' => true ),
	);

	$palette = $source ? $source->get_palette() : MM_SUC_P_Template::default_palette();

	$palette['accent']    = $options['accent_color'];
	$palette['ink']       = $options['text_color'];
	$palette['overlay']   = (int) $options['overlay_opacity'];
	$palette['blur']      = (int) $options['glass_strength'];
	$palette['on_accent'] = mm_suc_p_readable_on( $options['accent_color'] );

	$manifest['palette'] = $palette;

	if ( $source ) {
		$manifest['base']     = $source->get_slug();
		$manifest['supports'] = array(
			'countdown' => $source->supports( 'countdown' ),
			'contact'   => $source->supports( 'contact' ),
			'logo'      => $source->supports( 'logo' ),
		);

		// The stylesheet, rescoped so the copy styles itself and nothing else.
		$style = $source->get_file_path( 'style' );

		if ( $style ) {
			$css = file_get_contents( $style ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- bundled asset.

			if ( is_string( $css ) ) {
				$css = str_replace(
					'.mm-suc-p-page--tpl-' . $source->get_slug(),
					'.mm-suc-p-page--tpl-' . $slug,
					$css
				);

				if ( false !== file_put_contents( $dir . 'style.css', $css ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_put_contents -- writing inside our own uploads folder.
					$manifest['style'] = 'style.css';
				}
			}
		}
	}

	$image = mm_suc_p_preset_background( $options, $source, $dir );

	if ( $image ) {
		$manifest['background'] = $image;
	}

	$written = file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_put_contents -- writing inside our own uploads folder.
		$dir . 'template.json',
		wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n"
	);

	if ( false === $written ) {
		wp_send_json_error(
			array( 'message' => __( 'Could not write the preset file. Check the uploads folder permissions.', 'wp-maintenance-mode-site-under-construction' ) ),
			500
		);
	}

	MM_SUC_P_Template_Registry::flush();

	$template = MM_SUC_P_Template_Registry::get( $slug );

	if ( ! $template ) {
		wp_send_json_error(
			array( 'message' => __( 'The preset was written but could not be read back. Check the uploads folder.', 'wp-maintenance-mode-site-under-construction' ) ),
			500
		);
	}

	wp_send_json_success(
		array(
			'message'  => __( 'Preset saved. It is in your template list now - save changes to keep using it.', 'wp-maintenance-mode-site-under-construction' ),
			'template' => $template->to_array(),
			'row'      => mm_suc_p_library_row( $template, true ),
		)
	);
}
add_action( 'wp_ajax_mm_suc_save_preset', 'mm_suc_p_ajax_save_preset' );

/**
 * Copy the background a preset should carry into its own folder.
 *
 * The owner's own upload when they chose one, the source template's image
 * otherwise, and nothing at all when the design uses no image. Only image
 * extensions are accepted and only from a path WordPress resolved for us.
 *
 * @param array                  $options Sanitized configuration.
 * @param MM_SUC_P_Template|null $source  The template being copied.
 * @param string                 $dir     The preset folder, with a trailing slash.
 * @return string The file name written, or an empty string.
 */
function mm_suc_p_preset_background( $options, $source, $dir ) {
	if ( 'none' === $options['background'] ) {
		return '';
	}

	if ( 'custom' === $options['background'] && ! empty( $options['custom_background_id'] ) ) {
		$file = get_attached_file( (int) $options['custom_background_id'] );

		if ( $file && is_readable( $file ) ) {
			$extension = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
			$size      = filesize( $file );

			if ( in_array( $extension, array( 'jpg', 'jpeg', 'png', 'webp', 'avif' ), true ) && $size && $size <= 4 * MB_IN_BYTES ) {
				if ( copy( $file, $dir . 'background.' . $extension ) ) {
					return 'background.' . $extension;
				}
			}
		}
	}

	if ( ! $source ) {
		return '';
	}

	$background = $source->get_file_path( 'background' );

	if ( ! $background ) {
		return '';
	}

	$extension = strtolower( pathinfo( $background, PATHINFO_EXTENSION ) );

	if ( ! in_array( $extension, array( 'jpg', 'jpeg', 'png', 'webp', 'avif' ), true ) ) {
		return '';
	}

	return copy( $background, $dir . 'background.' . $extension ) ? 'background.' . $extension : '';
}

/* -------------------------------------------------------------------------
 * The editor screen
 * ---------------------------------------------------------------------- */

/**
 * Print a field wrapper opening tag.
 *
 * @param string $extra Extra class names.
 * @return void
 */
function mm_suc_p_field_open( $extra = '' ) {
	echo '<div class="mm-suc-p-field' . ( $extra ? ' ' . esc_attr( $extra ) : '' ) . '">';
}

/**
 * Print an accordion panel header.
 *
 * The icon arrives as markup rather than a slug so every icon this plugin uses
 * is a literal mm_suc_p_icon() call that tooling can find.
 *
 * @param string $id       Panel id.
 * @param string $icon     Icon markup from mm_suc_p_icon().
 * @param string $title    Panel title.
 * @param bool   $expanded Whether it starts open.
 * @return void
 */
function mm_suc_p_panel_head( $id, $icon, $title, $expanded ) {
	printf(
		'<h2 class="mm-suc-p-panel-head"><button type="button" class="mm-suc-p-panel-toggle" aria-expanded="%1$s" aria-controls="mm-suc-p-panel-%2$s">%3$s<span class="mm-suc-p-panel-title">%4$s</span>%5$s</button></h2>',
		$expanded ? 'true' : 'false',
		esc_attr( $id ),
		$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG.
		esc_html( $title ),
		mm_suc_p_icon( 'chevron-down', 'mm-suc-p-panel-chevron' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG.
	);
}

/**
 * One row of the template library.
 *
 * Shared by the editor screen and the save-preset endpoint, so a preset added
 * without a reload is the same markup as one rendered on load.
 *
 * @param MM_SUC_P_Template $template The template.
 * @param bool              $checked  Whether it is the current choice.
 * @return string
 */
function mm_suc_p_library_row( $template, $checked ) {
	$slug = $template->get_slug();

	$visual = '';

	if ( $template->get_thumbnail_url() ) {
		$visual = sprintf(
			'<img src="%s" alt="" loading="lazy" decoding="async" />',
			esc_url( $template->get_thumbnail_url() )
		);
	}

	$badge = '';

	if ( 'preset' === $template->get_origin() ) {
		$badge = sprintf(
			'<span class="mm-suc-p-tpl-badge">%s</span>',
			esc_html__( 'Preset', 'wp-maintenance-mode-site-under-construction' )
		);
	}

	return sprintf(
		'<label class="mm-suc-p-tpl"><input type="radio" name="template" value="%1$s" data-mm-suc-p-field="template"%2$s />'
		. '<span class="mm-suc-p-tpl-visual" aria-hidden="true">%3$s</span>'
		. '<span class="mm-suc-p-tpl-body"><span class="mm-suc-p-tpl-name">%4$s%5$s</span><span class="mm-suc-p-tpl-desc">%6$s</span></span>'
		. '<span class="mm-suc-p-tpl-check" aria-hidden="true">%7$s</span></label>',
		esc_attr( $slug ),
		$checked ? ' checked' : '',
		$visual, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped above.
		esc_html( $template->get_name() ),
		$badge, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped above.
		esc_html( $template->get_description() ),
		mm_suc_p_icon( 'check' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG.
	);
}

/**
 * A foreground that stays readable on a given fill.
 *
 * The page never assumes white on an accent: an owner who picks a pale accent
 * would get a button nobody can read.
 *
 * @param string $hex Background colour.
 * @return string Hex colour.
 */
function mm_suc_p_readable_on( $hex ) {
	$hex = sanitize_hex_color( (string) $hex );

	if ( ! $hex ) {
		return '#ffffff';
	}

	$hex = ltrim( $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	$channels = array(
		hexdec( substr( $hex, 0, 2 ) ) / 255,
		hexdec( substr( $hex, 2, 2 ) ) / 255,
		hexdec( substr( $hex, 4, 2 ) ) / 255,
	);

	foreach ( $channels as $index => $value ) {
		$channels[ $index ] = ( $value <= 0.03928 ) ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );
	}

	$luminance = ( 0.2126 * $channels[0] ) + ( 0.7152 * $channels[1] ) + ( 0.0722 * $channels[2] );

	$onWhite = 1.05 / ( $luminance + 0.05 );
	$onDark  = ( $luminance + 0.05 ) / 0.10;

	return ( $onDark >= $onWhite ) ? '#111111' : '#ffffff';
}

/**
 * A folder name for a preset that no installed template already uses.
 *
 * @param string $name Human name.
 * @param string $dir  The preset root.
 * @return string
 */
function mm_suc_p_unique_preset_slug( $name, $dir ) {
	$base = sanitize_key( sanitize_title( $name ) );

	if ( '' === $base ) {
		$base = 'preset';
	}

	$base = mm_suc_p_truncate( $base, 48 );
	$slug = $base;
	$n    = 1;

	while ( MM_SUC_P_Template_Registry::is_installed( $slug ) || is_dir( $dir . $slug ) ) {
		$n++;
		$slug = $base . '-' . $n;
	}

	return $slug;
}

/**
 * Render the editor.
 *
 * @return void
 */
function mm_suc_p_render_editor() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this screen.', 'wp-maintenance-mode-site-under-construction' ), 403 );
	}

	$options   = mm_suc_p_get_options();
	$templates = MM_SUC_P_Template_Registry::all();
	$enabled   = ! empty( $options['enabled'] );

	require MM_SUC_P_DIR . 'admin/views/editor.php';
}

/**
 * Render the messages list screen.
 *
 * @return void
 */
function mm_suc_p_render_messages() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this screen.', 'wp-maintenance-mode-site-under-construction' ), 403 );
	}

	$options  = mm_suc_p_get_options();
	$messages = mm_suc_p_get_messages();
	$counts   = mm_suc_p_get_messages_count();

	require MM_SUC_P_DIR . 'admin/views/messages.php';
}

/**
 * AJAX handler to delete a single message.
 *
 * @return void
 */
function mm_suc_p_ajax_delete_message() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'mm_suc_admin' ) ) {
		wp_send_json_error( array( 'message' => __( 'Your session expired. Reload the page.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to delete this message.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';

	if ( '' === $id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid message ID.', 'wp-maintenance-mode-site-under-construction' ) ), 400 );
	}

	$deleted = mm_suc_p_delete_message( $id );
	$counts  = mm_suc_p_get_messages_count();

	if ( ! $deleted ) {
		wp_send_json_error( array( 'message' => __( 'Could not find or delete that message.', 'wp-maintenance-mode-site-under-construction' ) ), 404 );
	}

	wp_send_json_success(
		array(
			'message' => __( 'Message deleted.', 'wp-maintenance-mode-site-under-construction' ),
			'counts'  => $counts,
		)
	);
}
add_action( 'wp_ajax_mm_suc_delete_message', 'mm_suc_p_ajax_delete_message' );

/**
 * AJAX handler to delete all messages.
 *
 * @return void
 */
function mm_suc_p_ajax_clear_messages() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'mm_suc_admin' ) ) {
		wp_send_json_error( array( 'message' => __( 'Your session expired. Reload the page.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to clear messages.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	mm_suc_p_clear_all_messages();

	wp_send_json_success(
		array(
			'message' => __( 'All messages cleared.', 'wp-maintenance-mode-site-under-construction' ),
			'counts'  => array(
				'total'  => 0,
				'unread' => 0,
			),
		)
	);
}
add_action( 'wp_ajax_mm_suc_clear_messages', 'mm_suc_p_ajax_clear_messages' );

/**
 * AJAX handler to mark a message as read.
 *
 * @return void
 */
function mm_suc_p_ajax_mark_read() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'mm_suc_admin' ) ) {
		wp_send_json_error( array( 'message' => __( 'Your session expired. Reload the page.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to update this message.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';

	if ( '' !== $id ) {
		mm_suc_p_mark_message_read( $id );
	}

	$counts = mm_suc_p_get_messages_count();

	wp_send_json_success(
		array(
			'counts' => $counts,
		)
	);
}
add_action( 'wp_ajax_mm_suc_mark_read', 'mm_suc_p_ajax_mark_read' );

/**
 * AJAX handler to toggle the uninstall database table cleanup option.
 *
 * @return void
 */
function mm_suc_p_ajax_toggle_uninstall_cleanup() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'mm_suc_admin' ) ) {
		wp_send_json_error( array( 'message' => __( 'Your session expired. Reload the page.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to update this setting.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	$options                                 = mm_suc_p_get_options();
	$options['delete_messages_on_uninstall'] = ! empty( $_POST['delete_messages_on_uninstall'] ) ? 1 : 0;

	mm_suc_p_update_options( $options );

	wp_send_json_success(
		array(
			'message'                      => __( 'Settings saved.', 'wp-maintenance-mode-site-under-construction' ),
			'delete_messages_on_uninstall' => $options['delete_messages_on_uninstall'],
		)
	);
}
add_action( 'wp_ajax_mm_suc_toggle_uninstall_cleanup', 'mm_suc_p_ajax_toggle_uninstall_cleanup' );

/**
 * A line diagram standing in for a layout preset.
 *
 * Diagrams rather than screenshots: they show the arrangement, survive any
 * colour scheme, weigh nothing and never go stale when a template changes.
 *
 * @param string $key Layout key.
 * @return string Inline SVG markup.
 */
function mm_suc_p_layout_diagram( $key ) {
	$shapes = array(
		'centered' => '<rect x="14" y="10" width="28" height="4" rx="2"/><rect x="8" y="18" width="40" height="6" rx="3"/><rect x="16" y="28" width="24" height="3" rx="1.5"/><rect x="20" y="35" width="16" height="4" rx="2"/>',
		'left'     => '<rect x="6" y="10" width="20" height="4" rx="2"/><rect x="6" y="18" width="28" height="6" rx="3"/><rect x="6" y="28" width="22" height="3" rx="1.5"/><rect x="6" y="35" width="14" height="4" rx="2"/>',
		'minimal'  => '<rect x="12" y="16" width="32" height="5" rx="2.5"/><rect x="16" y="25" width="24" height="3" rx="1.5"/>',
		'split'    => '<rect x="4" y="8" width="24" height="32" rx="3" opacity=".35"/><rect x="32" y="12" width="20" height="4" rx="2"/><rect x="32" y="20" width="20" height="5" rx="2.5"/><rect x="32" y="29" width="14" height="3" rx="1.5"/>',
	);

	if ( ! isset( $shapes[ $key ] ) ) {
		return '';
	}

	return '<svg viewBox="0 0 56 48" width="56" height="48" fill="currentColor" aria-hidden="true" focusable="false">' . $shapes[ $key ] . '</svg>';
}

/**
 * Render the rating & review invitation banner.
 *
 * Appears across plugin admin pages to ask site owners for a 5-star rating on
 * WordPress.org. Hidden if previously dismissed by the current user.
 *
 * @return void
 */
function mm_suc_p_render_rating_banner() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$user_id = get_current_user_id();

	if ( $user_id && get_user_meta( $user_id, 'mm_suc_p_dismiss_rating', true ) ) {
		return;
	}

	$review_url = 'https://wordpress.org/support/plugin/wp-maintenance-mode-site-under-construction/reviews/';
	?>
	<section class="mm-suc-p-rating-banner" id="mm-suc-p-rating-banner" aria-label="<?php esc_attr_e( 'Rate this plugin', 'wp-maintenance-mode-site-under-construction' ); ?>">
		<div class="mm-suc-p-rating-glow" aria-hidden="true"></div>
		<div class="mm-suc-p-rating-main">
			<div class="mm-suc-p-rating-badge" aria-hidden="true">
				<div class="mm-suc-p-rating-stars">
					<?php
					for ( $i = 0; $i < 5; $i++ ) {
						echo mm_suc_p_icon( 'star', 'mm-suc-p-star-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG.
					}
					?>
				</div>
			</div>
			<div class="mm-suc-p-rating-body">
				<h3 class="mm-suc-p-rating-title">
					<?php esc_html_e( 'Enjoying WP Maintenance Mode & Site Under Construction?', 'wp-maintenance-mode-site-under-construction' ); ?>
				</h3>
				<p class="mm-suc-p-rating-desc">
					<?php esc_html_e( 'If this plugin helps you build and manage maintenance pages, please consider taking a moment to rate us 5 stars on WordPress.org. Your positive review directly supports future development and free updates!', 'wp-maintenance-mode-site-under-construction' ); ?>
				</p>
			</div>
		</div>
		<div class="mm-suc-p-rating-actions">
			<a class="mm-suc-p-btn mm-suc-p-btn--primary mm-suc-p-rating-btn-rate" href="<?php echo esc_url( $review_url ); ?>" target="_blank" rel="noopener noreferrer" data-mm-suc-p-rating-action="rate">
				<?php echo mm_suc_p_icon( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?>
				<span><?php esc_html_e( 'Rate 5 Stars', 'wp-maintenance-mode-site-under-construction' ); ?></span>
				<?php echo mm_suc_p_icon( 'external-link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?>
				<span class="mm-suc-p-sr"><?php esc_html_e( '(opens in a new tab)', 'wp-maintenance-mode-site-under-construction' ); ?></span>
			</a>
			<button type="button" class="mm-suc-p-btn mm-suc-p-btn--ghost mm-suc-p-rating-btn-dismiss" data-mm-suc-p-rating-action="dismiss">
				<span><?php esc_html_e( 'I already rated', 'wp-maintenance-mode-site-under-construction' ); ?></span>
			</button>
			<button type="button" class="mm-suc-p-btn mm-suc-p-btn--icon mm-suc-p-btn--ghost mm-suc-p-rating-close" data-mm-suc-p-rating-action="dismiss" aria-label="<?php esc_attr_e( 'Dismiss rating prompt', 'wp-maintenance-mode-site-under-construction' ); ?>">
				<?php echo mm_suc_p_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?>
			</button>
		</div>
	</section>
	<?php
}

/**
 * AJAX handler to dismiss the rating notice permanently for the current user.
 *
 * @return void
 */
function mm_suc_p_ajax_dismiss_rating() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'mm_suc_admin' ) ) {
		wp_send_json_error( array( 'message' => __( 'Your session expired. Reload the page.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-maintenance-mode-site-under-construction' ) ), 403 );
	}

	$user_id = get_current_user_id();

	if ( $user_id ) {
		update_user_meta( $user_id, 'mm_suc_p_dismiss_rating', 1 );
	}

	wp_send_json_success( array( 'dismissed' => true ) );
}
add_action( 'wp_ajax_mm_suc_dismiss_rating', 'mm_suc_p_ajax_dismiss_rating' );

