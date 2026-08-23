<?php
/**
 * Plugin Name: WP Maintenance Mode & Site Under Construction
 * Plugin URI: https://wordpress.org/plugins/wp-maintenance-mode-site-under-construction
 * Description: A lightweight visual maintenance-mode editor with live preview, countdown, contact form, responsive layouts, and secure access rules.
 * Version: 5.1
 * Author: wp-buy
 * Author URI: https://www.wp-buy.com/
 * Text Domain: wp-maintenance-mode-site-under-construction
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 *
 * @package wp-maintenance-mode-site-under-construction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Constants
 * ---------------------------------------------------------------------- */

define( 'MM_SUC_P_VERSION', '5.1' );
define( 'MM_SUC_P_FILE', __FILE__ );
define( 'MM_SUC_P_DIR', plugin_dir_path( __FILE__ ) );
define( 'MM_SUC_P_URL', plugin_dir_url( __FILE__ ) );

/** Stored option key. Fixed by live data - never rename. */
define( 'MM_SUC_P_OPTION', 'MM_And_SUC_Free_options' );

/** Settings page slug and its legacy alias. Fixed by existing links. */
define( 'MM_SUC_P_PAGE', 'mm-suc-settings' );
define( 'MM_SUC_P_PAGE_LEGACY', 'MM_And_SUC_Free_Settings' );

/** Messages list page slug. */
define( 'MM_SUC_P_PAGE_MESSAGES', 'mm-suc-messages' );

/** Where drop-in templates live. A folder here is a template; deleting it removes it. */
define( 'MM_SUC_P_TEMPLATES_DIR', MM_SUC_P_DIR . 'templates/' );
define( 'MM_SUC_P_TEMPLATES_URL', MM_SUC_P_URL . 'templates/' );

/** The folder name presets are saved into, inside the uploads directory. */
define( 'MM_SUC_P_PRESET_FOLDER', 'mm-suc-p-templates' );

/** The folder name contact messages are saved into, inside the uploads directory. */
define( 'MM_SUC_P_MESSAGES_FOLDER', 'mm-suc-p-messages' );

/** Length caps. The same numbers are enforced in JS and in the sanitizer. */
define( 'MM_SUC_P_CAP_NAME', 100 );
define( 'MM_SUC_P_CAP_EMAIL', 190 );
define( 'MM_SUC_P_CAP_MESSAGE', 3000 );

require_once MM_SUC_P_DIR . 'includes/class-mm-suc-p-template.php';
require_once MM_SUC_P_DIR . 'includes/class-mm-suc-p-template-registry.php';
require_once MM_SUC_P_DIR . 'includes/class-mm-suc-p-template-view.php';
require_once MM_SUC_P_DIR . 'includes/class-mm-suc-p-template-controller.php';
require_once MM_SUC_P_DIR . 'includes/class-mm-suc-p-messages.php';
require_once MM_SUC_P_DIR . 'template.php';

if ( is_admin() ) {
	require_once MM_SUC_P_DIR . 'admin/settings.php';
}

/* -------------------------------------------------------------------------
 * Template roots
 * ---------------------------------------------------------------------- */

/**
 * Where the registry looks for template folders.
 *
 * Two roots, in priority order:
 *
 * 1. The plugin's own templates/ - the bundled designs, replaced on update.
 * 2. uploads/mm-suc-p-templates/ - presets saved from the editor.
 *
 * Presets live in uploads deliberately. A folder inside the plugin would be
 * deleted the next time WordPress updates it, and plenty of hosts keep the
 * plugin directory read-only. Same folder shape either way, so a preset is a
 * template in every other respect: copyable, deletable, drop-in.
 *
 * @return array List of arrays with dir, url and origin.
 */
function mm_suc_p_template_roots() {
	$roots = array(
		array(
			'dir'    => MM_SUC_P_TEMPLATES_DIR,
			'url'    => MM_SUC_P_TEMPLATES_URL,
			'origin' => 'bundled',
		),
	);

	$uploads = wp_upload_dir();

	if ( empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) ) {
		$roots[] = array(
			'dir'    => trailingslashit( $uploads['basedir'] ) . MM_SUC_P_PRESET_FOLDER . '/',
			'url'    => trailingslashit( $uploads['baseurl'] ) . MM_SUC_P_PRESET_FOLDER . '/',
			'origin' => 'preset',
		);
	}

	/**
	 * Filter the roots the template registry scans.
	 *
	 * @param array $roots Each with dir, url and origin.
	 */
	return apply_filters( 'mm_suc_p_template_roots', $roots );
}

/**
 * The root presets are written to, or null when uploads is unavailable.
 *
 * @return array|null
 */
function mm_suc_p_preset_root() {
	foreach ( mm_suc_p_template_roots() as $root ) {
		if ( 'preset' === $root['origin'] ) {
			return $root;
		}
	}

	return null;
}

/* -------------------------------------------------------------------------
 * Options
 * ---------------------------------------------------------------------- */

/**
 * The documented default configuration.
 *
 * Field names are stored data: add, never rename. The four palette fields carry
 * the defaults recorded in design-system/tokens/page.tokens.json; on a fresh
 * install they are re-seeded from the active template's palette.
 *
 * @return array
 */
function mm_suc_p_default_options() {
	return array(
		'schema_version'       => 2,
		'enabled'              => 0,
		'end_datetime'         => '',
		'auto_disable'         => 1,
		'template'             => '',
		'layout'               => 'centered',
		'background'           => 'template',
		'custom_background_id' => 0,
		'logo_id'              => 0,
		'eyebrow'              => __( 'Scheduled maintenance', 'wp-maintenance-mode-site-under-construction' ),
		'headline'             => __( 'We will be back soon', 'wp-maintenance-mode-site-under-construction' ),
		'message'              => __( 'We are making a few improvements behind the scenes. Thanks for your patience - the site will be back shortly.', 'wp-maintenance-mode-site-under-construction' ),
		'countdown'            => 1,
		'contact_enabled'      => 1,
		'contact_button'       => __( 'Get in touch', 'wp-maintenance-mode-site-under-construction' ),
		'contact_email'        => '',
		'accent_color'         => '#4f46e5',
		'text_color'           => '#ffffff',
		'overlay_opacity'      => 55,
		'glass_strength'       => 14,
		'bypass_roles'         => array( 'administrator' ),
	);
}

/**
 * Defaults with the active template's palette applied.
 *
 * Used when no configuration has ever been stored, so a fresh install looks
 * like the template it ships with rather than like an unstyled fallback.
 *
 * @return array
 */
function mm_suc_p_seed_options() {
	$options  = mm_suc_p_default_options();
	$template = MM_SUC_P_Template_Registry::resolve( '' );

	if ( $template ) {
		$options['template'] = $template->get_slug();
		$options             = array_merge( $options, $template->get_palette_options() );
	}

	return $options;
}

/**
 * The current configuration, merged over the defaults.
 *
 * Legacy v3 keys are mapped in memory. Nothing is written here - only a real
 * save writes the modern schema, and no legacy key is ever deleted.
 *
 * @param bool $force Re-read from the database instead of the request cache.
 * @return array
 */
function mm_suc_p_get_options( $force = false ) {
	static $cache = null;

	if ( null !== $cache && ! $force ) {
		return $cache;
	}

	$stored = get_option( MM_SUC_P_OPTION, null );

	if ( ! is_array( $stored ) ) {
		$options = array_merge( mm_suc_p_seed_options(), mm_suc_p_migrate_legacy() );
	} else {
		$options = array_merge( mm_suc_p_seed_options(), mm_suc_p_migrate_legacy(), $stored );
	}

	if ( ! is_array( $options['bypass_roles'] ) ) {
		$options['bypass_roles'] = array();
	}

	// The administrator can never be removed from the bypass list.
	if ( ! in_array( 'administrator', $options['bypass_roles'], true ) ) {
		$options['bypass_roles'][] = 'administrator';
	}

	$cache = $options;

	return $cache;
}

/**
 * Forget the request-level options cache.
 *
 * @return void
 */
function mm_suc_p_flush_options_cache() {
	mm_suc_p_get_options( true );
}

/**
 * Read legacy v3 options into the modern shape, without touching them.
 *
 * @return array Only the keys a legacy install actually provides.
 */
function mm_suc_p_migrate_legacy() {
	$map = array(
		'MM_And_SUC_Free_status'      => 'enabled',
		'MM_And_SUC_Free_title'       => 'headline',
		'MM_And_SUC_Free_description' => 'message',
		'MM_And_SUC_Free_subtitle'    => 'eyebrow',
		'MM_And_SUC_Free_date'        => 'end_datetime',
		'MM_And_SUC_Free_txtcolor'    => 'text_color',
		'MM_And_SUC_Free_accentcolor' => 'accent_color',
		'MM_And_SUC_Free_email'       => 'contact_email',
	);

	/**
	 * Filter the legacy key map, for installs that stored extra v3 keys.
	 *
	 * @param array $map Legacy option name => modern field name.
	 */
	$map = apply_filters( 'mm_suc_p_legacy_option_map', $map );

	$out = array();

	foreach ( $map as $legacy => $field ) {
		$value = get_option( $legacy, null );

		if ( null === $value || '' === $value ) {
			continue;
		}

		switch ( $field ) {
			case 'enabled':
				$out[ $field ] = ( '1' === (string) $value || 'yes' === $value || 'on' === $value ) ? 1 : 0;
				break;
			case 'text_color':
			case 'accent_color':
				$hex = sanitize_hex_color( '#' . ltrim( (string) $value, '#' ) );
				if ( $hex ) {
					$out[ $field ] = $hex;
				}
				break;
			case 'contact_email':
				$email = sanitize_email( (string) $value );
				if ( $email ) {
					$out[ $field ] = $email;
				}
				break;
			default:
				$out[ $field ] = (string) $value;
		}
	}

	return $out;
}

/**
 * Persist a full configuration array.
 *
 * @param array $options Sanitized configuration.
 * @return bool
 */
function mm_suc_p_update_options( $options ) {
	$saved = update_option( MM_SUC_P_OPTION, $options, false );
	mm_suc_p_flush_options_cache();

	return $saved;
}

/**
 * Is maintenance mode switched on in the database.
 *
 * @return bool
 */
function mm_suc_p_is_enabled() {
	$options = mm_suc_p_get_options();

	return ! empty( $options['enabled'] );
}

/**
 * The page style contract: the custom properties the server injects on the page
 * root, the option field behind each one, and the documented default.
 *
 * This map is the single source shared by the injector in template.php, the
 * declarations in assets/css/frontend.css and the vars map in
 * assets/js/admin.js. All four have to agree, and the design-system validator
 * checks that they do.
 *
 * @return array
 */
function mm_suc_p_style_contract() {
	$defaults = mm_suc_p_default_options();

	return array(
		'--mm-suc-p-page-accent'   => array(
			'option'  => 'accent_color',
			'default' => $defaults['accent_color'],
		),
		'--mm-suc-p-page-ink'      => array(
			'option'  => 'text_color',
			'default' => $defaults['text_color'],
		),
		'--mm-suc-p-page-overlay'  => array(
			'option'  => 'overlay_opacity',
			'default' => $defaults['overlay_opacity'],
		),
		'--mm-suc-p-page-blur'     => array(
			'option'  => 'glass_strength',
			'default' => $defaults['glass_strength'],
		),
		'--mm-suc-p-page-bg-image' => array(
			'option'  => 'background',
			'default' => 'none',
		),
	);
}

/* -------------------------------------------------------------------------
 * Time
 * ---------------------------------------------------------------------- */

/**
 * The site timezone as a string suitable for display.
 *
 * @return string
 */
function mm_suc_p_timezone_label() {
	$tz     = wp_timezone();
	$offset = $tz->getOffset( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) ) / 3600;
	$sign   = $offset < 0 ? '-' : '+';
	$abs    = abs( $offset );
	$hours  = (int) $abs;
	$mins   = (int) round( ( $abs - $hours ) * 60 );

	return sprintf( 'UTC%s%02d:%02d', $sign, $hours, $mins );
}

/**
 * The configured end time as a full ISO 8601 string with an offset.
 *
 * The countdown needs the offset: the visitor's clock is not the site's.
 *
 * @param array $options Configuration.
 * @return string Empty string when no end time is set.
 */
function mm_suc_p_end_iso( $options ) {
	if ( empty( $options['end_datetime'] ) ) {
		return '';
	}

	try {
		$date = new DateTime( $options['end_datetime'], wp_timezone() );
	} catch ( Exception $e ) {
		return '';
	}

	return $date->format( 'c' );
}

/**
 * The configured end time as a UNIX timestamp.
 *
 * @param array $options Configuration.
 * @return int Zero when no end time is set.
 */
function mm_suc_p_end_timestamp( $options ) {
	$iso = mm_suc_p_end_iso( $options );

	if ( '' === $iso ) {
		return 0;
	}

	return (int) strtotime( $iso );
}

/* -------------------------------------------------------------------------
 * Access
 * ---------------------------------------------------------------------- */

/**
 * May the current user see the real site while maintenance is on.
 *
 * The administrator role and any user holding manage_options bypass
 * unconditionally. This is hardcoded lockout protection and is not
 * configurable.
 *
 * @param array $options Configuration.
 * @return bool
 */
function mm_suc_p_user_can_bypass( $options ) {
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	if ( ! is_user_logged_in() ) {
		return false;
	}

	$user = wp_get_current_user();

	if ( ! $user || empty( $user->roles ) ) {
		return false;
	}

	if ( in_array( 'administrator', (array) $user->roles, true ) ) {
		return true;
	}

	$allowed = isset( $options['bypass_roles'] ) ? (array) $options['bypass_roles'] : array();

	foreach ( (array) $user->roles as $role ) {
		if ( in_array( $role, $allowed, true ) ) {
			return true;
		}
	}

	return false;
}

/* -------------------------------------------------------------------------
 * Logo resolution
 * ---------------------------------------------------------------------- */

/**
 * Resolve the logo, in the documented fallback order.
 *
 * Custom plugin logo, then the classic theme logo, then the block theme site
 * logo, then the site title as text.
 *
 * @param array $options Configuration.
 * @return array {
 *     @type string $type   'image' or 'text'.
 *     @type string $url    Image source when type is image.
 *     @type string $alt    Alternative text.
 *     @type string $text   Site title when type is text.
 *     @type string $source Which step in the hierarchy resolved it.
 * }
 */
function mm_suc_p_resolve_logo( $options ) {
	$candidates = array(
		'custom'      => isset( $options['logo_id'] ) ? (int) $options['logo_id'] : 0,
		'theme-mod'   => (int) get_theme_mod( 'custom_logo' ),
		'site-option' => (int) get_option( 'site_logo' ),
	);

	foreach ( $candidates as $source => $attachment_id ) {
		if ( $attachment_id < 1 ) {
			continue;
		}

		$url = wp_get_attachment_image_url( $attachment_id, 'medium' );

		if ( $url ) {
			return array(
				'type'   => 'image',
				'url'    => $url,
				'alt'    => get_bloginfo( 'name' ),
				'text'   => get_bloginfo( 'name' ),
				'source' => $source,
			);
		}
	}

	return array(
		'type'   => 'text',
		'url'    => '',
		'alt'    => '',
		'text'   => get_bloginfo( 'name' ),
		'source' => 'site-title',
	);
}

/* -------------------------------------------------------------------------
 * Icons
 * ---------------------------------------------------------------------- */

/**
 * The icon registry: the only slugs that may reach the filesystem.
 *
 * @return array
 */
function mm_suc_p_icons() {
	return array(
		'check',
		'chevron-down',
		'clock',
		'close',
		'contact-mail',
		'external-link',
		'image',
		'maintenance',
		'palette',
		'save',
		'shield',
		'site-live',
		'trash',
		'warning',
	);
}

/**
 * Inline SVG markup for a registered icon slug.
 *
 * Unknown slugs return an empty string: a typo degrades to a missing icon and
 * never reaches the filesystem.
 *
 * @param string $slug  Registered slug.
 * @param string $class Extra class names.
 * @return string
 */
function mm_suc_p_icon( $slug, $class = '' ) {
	static $cache = array();

	$slug = sanitize_key( $slug );

	if ( ! in_array( $slug, mm_suc_p_icons(), true ) ) {
		return '';
	}

	if ( ! isset( $cache[ $slug ] ) ) {
		$path = MM_SUC_P_DIR . 'assets/icons/' . $slug . '.svg';

		if ( ! is_readable( $path ) ) {
			$cache[ $slug ] = '';
		} else {
			$svg = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- bundled asset, no remote access.
			$cache[ $slug ] = is_string( $svg ) ? trim( $svg ) : '';
		}
	}

	if ( '' === $cache[ $slug ] ) {
		return '';
	}

	$classes = trim( 'mm-suc-p-icon ' . $class );

	return str_replace(
		'<svg ',
		'<svg class="' . esc_attr( $classes ) . '" aria-hidden="true" focusable="false" ',
		$cache[ $slug ]
	);
}

/* -------------------------------------------------------------------------
 * URLs
 * ---------------------------------------------------------------------- */

/**
 * The settings screen URL.
 *
 * @return string
 */
function mm_suc_p_settings_url() {
	return admin_url( 'admin.php?page=' . MM_SUC_P_PAGE );
}

/**
 * The messages screen URL.
 *
 * @return string
 */
function mm_suc_p_messages_url() {
	return admin_url( 'admin.php?page=' . MM_SUC_P_PAGE_MESSAGES );
}

/**
 * The standalone preview URL, carrying its own nonce.
 *
 * @return string
 */
function mm_suc_p_preview_url() {
	return add_query_arg(
		array(
			'mm_suc_preview'       => '1',
			'mm_suc_preview_nonce' => wp_create_nonce( 'mm_suc_preview' ),
		),
		home_url( '/' )
	);
}

/* -------------------------------------------------------------------------
 * Admin bar
 * ---------------------------------------------------------------------- */

/**
 * Add the status node and its one-click toggle to the admin bar.
 *
 * @param WP_Admin_Bar $bar Admin bar instance.
 * @return void
 */
function mm_suc_p_admin_bar( $bar ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$enabled = mm_suc_p_is_enabled();

	$title = $enabled
		? __( 'Maintenance mode is on', 'wp-maintenance-mode-site-under-construction' )
		: __( 'Site is live', 'wp-maintenance-mode-site-under-construction' );

	$bar->add_node(
		array(
			'id'    => 'mm-suc-p',
			'title' => '<span class="mm-suc-p-bar-dot mm-suc-p-bar-dot--' . ( $enabled ? 'maintenance' : 'live' ) . '" aria-hidden="true"></span>' . esc_html( $title ),
			'href'  => mm_suc_p_settings_url(),
			'meta'  => array( 'title' => $title ),
		)
	);

	$toggle_label = $enabled
		? __( 'Turn maintenance mode off', 'wp-maintenance-mode-site-under-construction' )
		: __( 'Turn maintenance mode on', 'wp-maintenance-mode-site-under-construction' );

	$bar->add_node(
		array(
			'parent' => 'mm-suc-p',
			'id'     => 'mm-suc-p-toggle',
			'title'  => esc_html( $toggle_label ),
			'href'   => wp_nonce_url(
				admin_url( 'admin-post.php?action=mm_suc_toggle_mode' ),
				'mm_suc_toggle_mode_' . get_current_user_id()
			),
		)
	);

	$bar->add_node(
		array(
			'parent' => 'mm-suc-p',
			'id'     => 'mm-suc-p-settings',
			'title'  => esc_html__( 'Maintenance settings', 'wp-maintenance-mode-site-under-construction' ),
			'href'   => mm_suc_p_settings_url(),
		)
	);

	$bar->add_node(
		array(
			'parent' => 'mm-suc-p',
			'id'     => 'mm-suc-p-messages',
			'title'  => esc_html__( 'Messages list', 'wp-maintenance-mode-site-under-construction' ),
			'href'   => mm_suc_p_messages_url(),
		)
	);
}
add_action( 'admin_bar_menu', 'mm_suc_p_admin_bar', 90 );

/**
 * Minimal styling for the admin-bar status dot, on every screen.
 *
 * @return void
 */
function mm_suc_p_admin_bar_styles() {
	if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<style id="mm-suc-p-adminbar">'
		. '#wpadminbar .mm-suc-p-bar-dot{display:inline-block;width:8px;height:8px;border-radius:999px;margin-inline-end:6px;vertical-align:middle}'
		. '#wpadminbar .mm-suc-p-bar-dot--live{background:#15803d}'
		. '#wpadminbar .mm-suc-p-bar-dot--maintenance{background:#b45309}'
		. '</style>';
}
add_action( 'wp_head', 'mm_suc_p_admin_bar_styles' );
add_action( 'admin_head', 'mm_suc_p_admin_bar_styles' );

/**
 * Handle the admin-bar toggle.
 *
 * Nonce first, then capability, then input.
 *
 * @return void
 */
function mm_suc_p_handle_toggle() {
	$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'mm_suc_toggle_mode_' . get_current_user_id() ) ) {
		wp_die( esc_html__( 'That link has expired. Go back and try again.', 'wp-maintenance-mode-site-under-construction' ), 403 );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to change maintenance mode.', 'wp-maintenance-mode-site-under-construction' ), 403 );
	}

	$options            = mm_suc_p_get_options();
	$options['enabled'] = empty( $options['enabled'] ) ? 1 : 0;

	mm_suc_p_update_options( $options );

	$referer = wp_get_referer();

	wp_safe_redirect( $referer ? $referer : mm_suc_p_settings_url() );
	exit;
}
add_action( 'admin_post_mm_suc_toggle_mode', 'mm_suc_p_handle_toggle' );

/* -------------------------------------------------------------------------
 * Activation
 * ---------------------------------------------------------------------- */

/**
 * Seed the configuration on first activation.
 *
 * An existing configuration is never overwritten, and no legacy key is touched.
 *
 * @return void
 */
function mm_suc_p_activate() {
	MM_SUC_P_Template_Registry::flush();

	if ( ! is_array( get_option( MM_SUC_P_OPTION, null ) ) ) {
		add_option( MM_SUC_P_OPTION, array_merge( mm_suc_p_seed_options(), mm_suc_p_migrate_legacy() ), '', false );
	}
}
register_activation_hook( __FILE__, 'mm_suc_p_activate' );

/**
 * Drop the template cache when the plugin is updated.
 *
 * @return void
 */
function mm_suc_p_deactivate() {
	MM_SUC_P_Template_Registry::flush();
}
register_deactivation_hook( __FILE__, 'mm_suc_p_deactivate' );
