<?php
/**
 * Template registry.
 *
 * Scans templates/ and returns models. Adding a template means copying a folder
 * in; removing one means deleting the folder. Neither requires a code change,
 * so nothing in the plugin may hardcode a template slug.
 *
 * @package wp-maintenance-mode-site-under-construction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discovers and caches the installed templates.
 */
class MM_SUC_P_Template_Registry {

	/**
	 * Transient holding the discovered slugs and the folder fingerprint.
	 */
	const CACHE_KEY = 'mm_suc_p_templates';

	/**
	 * Per-request model cache.
	 *
	 * @var array|null
	 */
	protected static $models = null;

	/**
	 * Every installed template, ordered.
	 *
	 * @return MM_SUC_P_Template[] Keyed by slug.
	 */
	public static function all() {
		if ( null !== self::$models ) {
			return self::$models;
		}

		$models = array();

		foreach ( self::slugs() as $slug => $root ) {
			$template = MM_SUC_P_Template::from_dir( $root['dir'] . $slug, $root['url'], $root['origin'] );

			if ( $template ) {
				$models[ $template->get_slug() ] = $template;
			}
		}

		uasort( $models, array( __CLASS__, 'compare' ) );

		/**
		 * Filter the installed templates.
		 *
		 * @param MM_SUC_P_Template[] $models Keyed by slug.
		 */
		self::$models = apply_filters( 'mm_suc_p_templates', $models );

		return self::$models;
	}

	/**
	 * Order by declared order, then by name.
	 *
	 * @param MM_SUC_P_Template $a First.
	 * @param MM_SUC_P_Template $b Second.
	 * @return int
	 */
	public static function compare( $a, $b ) {
		if ( $a->get_order() === $b->get_order() ) {
			return strcasecmp( $a->get_name(), $b->get_name() );
		}

		return $a->get_order() < $b->get_order() ? -1 : 1;
	}

	/**
	 * Candidate folder names, from the cache when the folder has not changed.
	 *
	 * The fingerprint is every root's modification time, which the filesystem
	 * updates when a folder is added or deleted. That is what makes a drop-in
	 * install take effect without any administrative action.
	 *
	 * @return array Slug to the root it was found in.
	 */
	protected static function slugs() {
		$roots = mm_suc_p_template_roots();
		$fingerprint = MM_SUC_P_VERSION;

		foreach ( $roots as $root ) {
			$fingerprint .= '|' . $root['dir'] . ':' . (string) @filemtime( $root['dir'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a missing root simply contributes nothing.
		}

		$cached = get_transient( self::CACHE_KEY );

		if ( is_array( $cached ) && isset( $cached['fingerprint'], $cached['slugs'] ) && $cached['fingerprint'] === $fingerprint ) {
			$found = array();

			foreach ( (array) $cached['slugs'] as $slug => $index ) {
				if ( isset( $roots[ $index ] ) ) {
					$found[ $slug ] = $roots[ $index ];
				}
			}

			return $found;
		}

		$found = array();
		$store = array();

		foreach ( $roots as $index => $root ) {
			if ( ! is_dir( $root['dir'] ) ) {
				continue;
			}

			$entries = @scandir( $root['dir'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- handled below.

			if ( ! is_array( $entries ) ) {
				continue;
			}

			foreach ( $entries as $entry ) {
				if ( '.' === $entry || '..' === $entry ) {
					continue;
				}

				if ( ! is_dir( $root['dir'] . $entry ) ) {
					continue;
				}

				if ( ! is_readable( $root['dir'] . $entry . '/template.json' ) ) {
					continue;
				}

				$slug = sanitize_key( $entry );

				// The first root wins: a preset can never shadow a bundled design.
				if ( '' === $slug || $slug !== $entry || isset( $found[ $slug ] ) ) {
					continue;
				}

				$found[ $slug ] = $root;
				$store[ $slug ] = $index;
			}
		}

		set_transient( self::CACHE_KEY, array( 'fingerprint' => $fingerprint, 'slugs' => $store ), DAY_IN_SECONDS );

		return $found;
	}

	/**
	 * One template by slug.
	 *
	 * @param string $slug Stored slug.
	 * @return MM_SUC_P_Template|null
	 */
	public static function get( $slug ) {
		$all  = self::all();
		$slug = sanitize_key( (string) $slug );

		return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
	}

	/**
	 * The slug used when none is stored, or when the stored one is gone.
	 *
	 * @return string
	 */
	public static function default_slug() {
		$all = self::all();

		if ( empty( $all ) ) {
			return '';
		}

		$slugs = array_keys( $all );

		return (string) reset( $slugs );
	}

	/**
	 * Resolve a stored slug to a usable template.
	 *
	 * A slug whose folder has been deleted resolves to the first installed
	 * template instead of breaking the page.
	 *
	 * @param string $slug Stored slug, possibly stale or empty.
	 * @return MM_SUC_P_Template|null Null only when no template is installed at all.
	 */
	public static function resolve( $slug ) {
		$template = self::get( $slug );

		if ( $template ) {
			return $template;
		}

		return self::get( self::default_slug() );
	}

	/**
	 * Whether a stored slug still resolves to the folder it names.
	 *
	 * @param string $slug Stored slug.
	 * @return bool
	 */
	public static function is_installed( $slug ) {
		return null !== self::get( $slug );
	}

	/**
	 * The library payload the editor renders and the preview repaints from.
	 *
	 * @return array
	 */
	public static function to_array() {
		$out = array();

		foreach ( self::all() as $template ) {
			$out[] = $template->to_array();
		}

		return $out;
	}

	/**
	 * Drop both caches. Called on activation, deactivation and after a save.
	 *
	 * @return void
	 */
	public static function flush() {
		self::$models = null;
		delete_transient( self::CACHE_KEY );
	}
}
