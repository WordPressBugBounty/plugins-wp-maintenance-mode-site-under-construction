<?php
/**
 * Template model.
 *
 * One instance describes one folder under templates/. The model owns parsing
 * and validation; it renders nothing and knows nothing about WordPress hooks.
 *
 * @package wp-maintenance-mode-site-under-construction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A drop-in maintenance page template.
 */
class MM_SUC_P_Template {

	/**
	 * Folder name, used as the stored value.
	 *
	 * @var string
	 */
	protected $slug = '';

	/**
	 * Absolute path to the template folder, with a trailing slash.
	 *
	 * @var string
	 */
	protected $dir = '';

	/**
	 * Public URL of the template folder, with a trailing slash.
	 *
	 * @var string
	 */
	protected $url = '';

	/**
	 * Parsed and validated manifest.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Which root this template came from: bundled, or a saved preset.
	 *
	 * @var string
	 */
	protected $origin = 'bundled';

	/**
	 * Build a model from an already-validated manifest.
	 *
	 * @param string $slug Folder name.
	 * @param string $dir  Absolute folder path with trailing slash.
	 * @param string $url  Folder URL with trailing slash.
	 * @param array  $data   Validated manifest.
	 * @param string $origin bundled or preset.
	 */
	public function __construct( $slug, $dir, $url, $data, $origin = 'bundled' ) {
		$this->slug   = $slug;
		$this->dir    = $dir;
		$this->url    = $url;
		$this->data   = $data;
		$this->origin = ( 'preset' === $origin ) ? 'preset' : 'bundled';
	}

	/**
	 * The palette shape a template must fill, with the values used when it does not.
	 *
	 * @return array
	 */
	public static function default_palette() {
		return array(
			'accent'      => '#4f46e5',
			'ink'         => '#ffffff',
			'on_accent'   => '#ffffff',
			'veil'        => '#0f172a',
			'card'        => '#0f172a',
			'card_alpha'  => 55,
			'line_alpha'  => 22,
			'overlay'     => 55,
			'blur'        => 14,
		);
	}

	/**
	 * Parse a template folder into a model.
	 *
	 * Returns null for anything that is not a complete, readable template, so a
	 * half-copied folder is ignored rather than fatal.
	 *
	 * @param string $dir    Absolute path to the folder, no trailing slash.
	 * @param string $url    Public URL of the folder's parent, with a trailing slash.
	 * @param string $origin bundled or preset.
	 * @return MM_SUC_P_Template|null
	 */
	public static function from_dir( $dir, $url = '', $origin = 'bundled' ) {
		$slug = sanitize_key( basename( $dir ) );

		if ( '' === $slug ) {
			return null;
		}

		$dir      = trailingslashit( $dir );
		$manifest = $dir . 'template.json';

		if ( ! is_readable( $manifest ) ) {
			return null;
		}

		$raw = file_get_contents( $manifest ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local bundled manifest.

		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return null;
		}

		$json = json_decode( $raw, true );

		if ( ! is_array( $json ) ) {
			return null;
		}

		$data = self::validate( $json, $dir );

		if ( null === $data ) {
			return null;
		}

		if ( '' === $url ) {
			$url = MM_SUC_P_TEMPLATES_URL;
		}

		return new self( $slug, $dir, trailingslashit( $url . $slug ), $data, $origin );
	}

	/**
	 * Validate and normalise a manifest against the folder it came from.
	 *
	 * Every file reference is reduced to a basename before it is used, so a
	 * manifest can never point outside its own folder.
	 *
	 * @param array  $json Raw manifest.
	 * @param string $dir  Folder path with trailing slash.
	 * @return array|null
	 */
	protected static function validate( $json, $dir ) {
		$view = isset( $json['view'] ) ? basename( (string) $json['view'] ) : 'view.php';
		$base = isset( $json['base'] ) ? sanitize_key( (string) $json['base'] ) : '';

		if ( ! preg_match( '/^[a-z0-9._-]+\.php$/i', $view ) || ! is_readable( $dir . $view ) ) {
			// A template with no view of its own is valid only if it names the
			// template whose arrangement it reuses. A preset does exactly that,
			// which is why no PHP file is ever written into the uploads folder.
			if ( '' === $base ) {
				return null;
			}

			$view = '';
		}

		$data = array(
			'base'        => $base,
			'name'        => isset( $json['name'] ) ? sanitize_text_field( (string) $json['name'] ) : '',
			'description' => isset( $json['description'] ) ? sanitize_text_field( (string) $json['description'] ) : '',
			'author'      => isset( $json['author'] ) ? sanitize_text_field( (string) $json['author'] ) : '',
			'version'     => isset( $json['version'] ) ? sanitize_text_field( (string) $json['version'] ) : '',
			'order'       => isset( $json['order'] ) ? (int) $json['order'] : 500,
			'layout'      => '',
			'view'        => $view,
			'style'       => '',
			'background'  => '',
			'thumbnail'   => '',
			'palette'     => self::default_palette(),
			'supports'    => array(
				'countdown' => true,
				'contact'   => true,
				'logo'      => true,
			),
		);

		if ( '' === $data['name'] ) {
			$data['name'] = ucwords( str_replace( array( '-', '_' ), ' ', basename( untrailingslashit( $dir ) ) ) );
		}

		foreach ( array( 'style' => 'css', 'background' => 'jpg|jpeg|png|webp|avif', 'thumbnail' => 'jpg|jpeg|png|webp|avif' ) as $key => $ext ) {
			if ( empty( $json[ $key ] ) ) {
				continue;
			}

			$file = basename( (string) $json[ $key ] );

			if ( preg_match( '/^[a-z0-9._-]+\.(' . $ext . ')$/i', $file ) && is_readable( $dir . $file ) ) {
				$data[ $key ] = $file;
			}
		}

		if ( '' === $data['thumbnail'] && '' !== $data['background'] ) {
			$data['thumbnail'] = $data['background'];
		}

		$layouts = array( 'centered', 'left', 'minimal', 'split' );

		if ( isset( $json['layout'] ) && in_array( sanitize_key( (string) $json['layout'] ), $layouts, true ) ) {
			$data['layout'] = sanitize_key( (string) $json['layout'] );
		}

		if ( isset( $json['palette'] ) && is_array( $json['palette'] ) ) {
			$data['palette'] = self::validate_palette( $json['palette'] );
		}

		if ( isset( $json['supports'] ) && is_array( $json['supports'] ) ) {
			foreach ( array_keys( $data['supports'] ) as $feature ) {
				if ( isset( $json['supports'][ $feature ] ) ) {
					$data['supports'][ $feature ] = (bool) $json['supports'][ $feature ];
				}
			}
		}

		return $data;
	}

	/**
	 * Coerce a manifest palette into valid colours and ranges.
	 *
	 * @param array $palette Raw palette.
	 * @return array
	 */
	protected static function validate_palette( $palette ) {
		$out = self::default_palette();

		foreach ( array( 'accent', 'ink', 'on_accent', 'veil', 'card' ) as $key ) {
			if ( empty( $palette[ $key ] ) ) {
				continue;
			}

			$hex = sanitize_hex_color( (string) $palette[ $key ] );

			if ( $hex ) {
				$out[ $key ] = $hex;
			}
		}

		foreach ( array( 'card_alpha', 'line_alpha', 'overlay' ) as $key ) {
			if ( isset( $palette[ $key ] ) ) {
				$out[ $key ] = max( 0, min( 100, (int) $palette[ $key ] ) );
			}
		}

		if ( isset( $palette['blur'] ) ) {
			$out['blur'] = max( 0, min( 40, (int) $palette['blur'] ) );
		}

		return $out;
	}

	/**
	 * Stored slug.
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Human name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->data['name'];
	}

	/**
	 * One-line description.
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->data['description'];
	}

	/**
	 * Sort order.
	 *
	 * @return int
	 */
	public function get_order() {
		return $this->data['order'];
	}

	/**
	 * Absolute path of the view file.
	 *
	 * @return string
	 */
	public function get_view_path() {
		if ( '' !== $this->data['view'] && is_readable( $this->dir . $this->data['view'] ) ) {
			return $this->dir . $this->data['view'];
		}

		if ( '' === $this->data['base'] || $this->data['base'] === $this->slug ) {
			return '';
		}

		$base = MM_SUC_P_Template_Registry::get( $this->data['base'] );

		return $base ? $base->get_view_path() : '';
	}

	/**
	 * The template whose view this one reuses, if any.
	 *
	 * @return string
	 */
	public function get_base() {
		return $this->data['base'];
	}

	/**
	 * Where this template came from: bundled or preset.
	 *
	 * @return string
	 */
	public function get_origin() {
		return $this->origin;
	}

	/**
	 * The layout this template was saved with, if it records one.
	 *
	 * @return string
	 */
	public function get_layout() {
		return $this->data['layout'];
	}

	/**
	 * URL of the template stylesheet, or an empty string when it ships none.
	 *
	 * @return string
	 */
	public function get_style_url() {
		return '' === $this->data['style'] ? '' : $this->url . $this->data['style'];
	}

	/**
	 * Absolute path of a file this template owns, or an empty string.
	 *
	 * @param string $key style, background or thumbnail.
	 * @return string
	 */
	public function get_file_path( $key ) {
		if ( empty( $this->data[ $key ] ) ) {
			return '';
		}

		$path = $this->dir . $this->data[ $key ];

		return is_readable( $path ) ? $path : '';
	}

	/**
	 * Modification time of the stylesheet, for cache busting.
	 *
	 * @return string
	 */
	public function get_style_version() {
		if ( '' === $this->data['style'] ) {
			return MM_SUC_P_VERSION;
		}

		$time = @filemtime( $this->dir . $this->data['style'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a missing file simply falls back.

		return $time ? (string) $time : MM_SUC_P_VERSION;
	}

	/**
	 * URL of the bundled background image.
	 *
	 * @return string
	 */
	public function get_background_url() {
		return '' === $this->data['background'] ? '' : $this->url . $this->data['background'];
	}

	/**
	 * URL of the library thumbnail.
	 *
	 * @return string
	 */
	public function get_thumbnail_url() {
		return '' === $this->data['thumbnail'] ? '' : $this->url . $this->data['thumbnail'];
	}

	/**
	 * Whether the template renders a feature.
	 *
	 * @param string $feature countdown, contact or logo.
	 * @return bool
	 */
	public function supports( $feature ) {
		return ! empty( $this->data['supports'][ $feature ] );
	}

	/**
	 * The full validated palette.
	 *
	 * @return array
	 */
	public function get_palette() {
		return $this->data['palette'];
	}

	/**
	 * The palette expressed as option values.
	 *
	 * These four fields belong to the site owner: choosing a template seeds
	 * them, and the owner may change any of them afterwards.
	 *
	 * @return array
	 */
	public function get_palette_options() {
		$palette = $this->get_palette();

		return array(
			'accent_color'    => $palette['accent'],
			'text_color'      => $palette['ink'],
			'overlay_opacity' => $palette['overlay'],
			'glass_strength'  => $palette['blur'],
		);
	}

	/**
	 * The shape the editor needs to describe this template in the library.
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'slug'        => $this->slug,
			'name'        => $this->get_name(),
			'description' => $this->get_description(),
			'thumbnail'   => $this->get_thumbnail_url(),
			'style'       => $this->get_style_url(),
			'layout'      => $this->get_layout(),
			'origin'      => $this->origin,
			'palette'     => $this->get_palette_options(),
			'supports'    => $this->data['supports'],
		);
	}
}
