<?php
/**
 * Template view helper.
 *
 * The object a template's view.php receives as $view. Every part of the page
 * that the editor binds to is emitted through this class, so a template
 * controls arrangement and styling while the binding contract - the
 * data-mm-suc-p-part attributes, the caps, the escaping - stays with the
 * plugin and cannot be broken by a template author.
 *
 * @package wp-maintenance-mode-site-under-construction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the contract parts of the maintenance document.
 */
class MM_SUC_P_Template_View {

	/**
	 * Current configuration.
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * The template being rendered.
	 *
	 * @var MM_SUC_P_Template|null
	 */
	protected $template = null;

	/**
	 * 'page' for the public document, 'editor' for the live preview.
	 *
	 * @var string
	 */
	protected $context = 'page';

	/**
	 * Resolved logo.
	 *
	 * @var array
	 */
	protected $logo = array();

	/**
	 * Constructor.
	 *
	 * @param array                  $options  Configuration.
	 * @param MM_SUC_P_Template|null $template Active template.
	 * @param string                 $context  'page' or 'editor'.
	 */
	public function __construct( $options, $template, $context = 'page' ) {
		$this->options  = $options;
		$this->template = $template;
		$this->context  = ( 'editor' === $context ) ? 'editor' : 'page';
		$this->logo     = mm_suc_p_resolve_logo( $options );
	}

	/**
	 * The editable parts, with the caps the sanitizer enforces.
	 *
	 * @return array
	 */
	public static function parts() {
		return array(
			'eyebrow'        => array(
				'tag'       => 'p',
				'class'     => 'mm-suc-p-page-eyebrow',
				'max'       => 80,
				'multiline' => false,
			),
			'headline'       => array(
				'tag'       => 'h1',
				'class'     => 'mm-suc-p-page-headline',
				'max'       => 120,
				'multiline' => false,
			),
			'message'        => array(
				'tag'       => 'p',
				'class'     => 'mm-suc-p-page-message',
				'max'       => 600,
				'multiline' => true,
			),
			'contact_button' => array(
				'tag'       => 'span',
				'class'     => 'mm-suc-p-page-contact',
				'max'       => 60,
				'multiline' => false,
			),
		);
	}

	/**
	 * Accessible names for the editable parts.
	 *
	 * @param string $field Part name.
	 * @return string
	 */
	protected function part_label( $field ) {
		$labels = array(
			'eyebrow'        => __( 'Eyebrow', 'wp-maintenance-mode-site-under-construction' ),
			'headline'       => __( 'Headline', 'wp-maintenance-mode-site-under-construction' ),
			'message'        => __( 'Message', 'wp-maintenance-mode-site-under-construction' ),
			'contact_button' => __( 'Contact button label', 'wp-maintenance-mode-site-under-construction' ),
		);

		return isset( $labels[ $field ] ) ? $labels[ $field ] : $field;
	}

	/**
	 * Whether this render is the editor preview.
	 *
	 * @return bool
	 */
	public function is_editor() {
		return 'editor' === $this->context;
	}

	/**
	 * A configuration value.
	 *
	 * @param string $key     Field name.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public function option( $key, $default = '' ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
	}

	/**
	 * A value from the active template's palette.
	 *
	 * @param string $key Palette key.
	 * @return string
	 */
	public function palette( $key ) {
		$palette = $this->template ? $this->template->get_palette() : MM_SUC_P_Template::default_palette();

		return isset( $palette[ $key ] ) ? (string) $palette[ $key ] : '';
	}

	/**
	 * Whether the countdown will render.
	 *
	 * Off, or with no end time, means the element is absent - never hidden.
	 *
	 * @return bool
	 */
	public function has_countdown() {
		if ( empty( $this->options['countdown'] ) ) {
			return false;
		}

		if ( $this->template && ! $this->template->supports( 'countdown' ) ) {
			return false;
		}

		return '' !== mm_suc_p_end_iso( $this->options );
	}

	/**
	 * Whether the contact trigger and sheet will render.
	 *
	 * @return bool
	 */
	public function has_contact() {
		if ( empty( $this->options['contact_enabled'] ) ) {
			return false;
		}

		if ( $this->template && ! $this->template->supports( 'contact' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Whether a logo or brand line will render.
	 *
	 * @return bool
	 */
	public function has_logo() {
		if ( $this->template && ! $this->template->supports( 'logo' ) ) {
			return false;
		}

		return ! empty( $this->logo['type'] );
	}

	/* --------------------------------------------------------------------
	 * Emitters
	 * ----------------------------------------------------------------- */

	/**
	 * The decorative background layer.
	 *
	 * @return void
	 */
	public function background() {
		printf(
			'<div class="mm-suc-p-page-bg" data-mm-suc-p-part="background" aria-hidden="true"><span class="mm-suc-p-page-bg-image"></span><span class="mm-suc-p-page-bg-veil"></span></div>'
		);
	}

	/**
	 * The logo, or the site title when no image resolves.
	 *
	 * @return void
	 */
	public function logo() {
		if ( ! $this->has_logo() ) {
			return;
		}

		$nav = $this->is_editor() ? ' data-mm-suc-p-part="logo" tabindex="0" role="button"' : '';

		if ( 'image' === $this->logo['type'] ) {
			printf(
				'<div class="mm-suc-p-page-logo"%1$s><img src="%2$s" alt="%3$s" decoding="async" /></div>',
				$nav, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup built above.
				esc_url( $this->logo['url'] ),
				esc_attr( $this->logo['alt'] )
			);

			return;
		}

		printf(
			'<div class="mm-suc-p-page-logo mm-suc-p-page-logo--text"%1$s>%2$s</div>',
			$nav, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup built above.
			esc_html( $this->logo['text'] )
		);
	}

	/**
	 * An editable part.
	 *
	 * @param string $field One of the keys in parts().
	 * @param array  $args  Optional. 'class' adds extra classes.
	 * @return void
	 */
	public function part( $field, $args = array() ) {
		$parts = self::parts();

		if ( ! isset( $parts[ $field ] ) ) {
			return;
		}

		$spec  = $parts[ $field ];
		$value = (string) $this->option( $field, '' );

		// Translate default strings dynamically if they match default values.
		$default_strings = array(
			'eyebrow'        => __( 'Scheduled maintenance', 'wp-maintenance-mode-site-under-construction' ),
			'headline'       => __( 'We will be back soon', 'wp-maintenance-mode-site-under-construction' ),
			'message'        => __( 'We are making a few improvements behind the scenes. Thanks for your patience - the site will be back shortly.', 'wp-maintenance-mode-site-under-construction' ),
			'contact_button' => __( 'Get in touch', 'wp-maintenance-mode-site-under-construction' ),
		);

		$raw_defaults = array(
			'eyebrow'        => 'Scheduled maintenance',
			'headline'       => 'We will be back soon',
			'message'        => 'We are making a few improvements behind the scenes. Thanks for your patience - the site will be back shortly.',
			'contact_button' => 'Get in touch',
		);

		if ( isset( $raw_defaults[ $field ] ) && $value === $raw_defaults[ $field ] ) {
			$value = $default_strings[ $field ];
		}

		// Multilingual plugin integration (WPML / Polylang).
		$value = apply_filters( 'wpml_translate_single_string', $value, 'wp-maintenance-mode-site-under-construction', 'mm_suc_p_' . $field );

		if ( function_exists( 'pll__' ) ) {
			$value = pll__( $value );
		}

		/**
		 * Filter the rendered part text value.
		 *
		 * @param string                 $value    Text value.
		 * @param string                 $field    Part field key.
		 * @param array                  $options  Current options.
		 * @param MM_SUC_P_Template|null $template Active template.
		 */
		$value = apply_filters( 'mm_suc_p_part_text', $value, $field, $this->options, $this->template );

		// An empty part still renders in the editor: it has to stay editable.
		if ( '' === trim( $value ) && ! $this->is_editor() ) {
			return;
		}

		$class = $spec['class'];

		if ( ! empty( $args['class'] ) ) {
			$class .= ' ' . sanitize_html_class( $args['class'] );
		}

		$attributes = sprintf(
			' class="%1$s" data-mm-suc-p-part="%2$s"',
			esc_attr( $class ),
			esc_attr( $field )
		);

		if ( $this->is_editor() ) {
			$attributes .= sprintf(
				' contenteditable="plaintext-only" role="textbox" tabindex="0" aria-label="%1$s" data-mm-suc-p-max="%2$d"%3$s',
				esc_attr( $this->part_label( $field ) ),
				(int) $spec['max'],
				$spec['multiline'] ? ' aria-multiline="true"' : ''
			);
		}

		printf(
			'<%1$s%2$s>%3$s</%1$s>',
			tag_escape( $spec['tag'] ),
			$attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every attribute escaped above.
			esc_html( $value )
		);
	}

	/**
	 * Eyebrow shorthand.
	 *
	 * @param array $args See part().
	 * @return void
	 */
	public function eyebrow( $args = array() ) {
		$this->part( 'eyebrow', $args );
	}

	/**
	 * Headline shorthand. Exactly one per document.
	 *
	 * @param array $args See part().
	 * @return void
	 */
	public function headline( $args = array() ) {
		$this->part( 'headline', $args );
	}

	/**
	 * Message shorthand.
	 *
	 * @param array $args See part().
	 * @return void
	 */
	public function message( $args = array() ) {
		$this->part( 'message', $args );
	}

	/**
	 * The decorative separator.
	 *
	 * @return void
	 */
	public function rule() {
		echo '<div class="mm-suc-p-page-rule" aria-hidden="true"></div>';
	}

	/**
	 * The countdown grid, or nothing at all when it is off.
	 *
	 * @return void
	 */
	public function countdown() {
		if ( ! $this->has_countdown() ) {
			return;
		}

		$iso       = mm_suc_p_end_iso( $this->options );
		$remaining = max( 0, mm_suc_p_end_timestamp( $this->options ) - time() );

		$units = array(
			'days'    => array( (int) floor( $remaining / DAY_IN_SECONDS ), __( 'Days', 'wp-maintenance-mode-site-under-construction' ) ),
			'hours'   => array( (int) floor( ( $remaining % DAY_IN_SECONDS ) / HOUR_IN_SECONDS ), __( 'Hours', 'wp-maintenance-mode-site-under-construction' ) ),
			'minutes' => array( (int) floor( ( $remaining % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS ), __( 'Minutes', 'wp-maintenance-mode-site-under-construction' ) ),
			'seconds' => array( (int) ( $remaining % MINUTE_IN_SECONDS ), __( 'Seconds', 'wp-maintenance-mode-site-under-construction' ) ),
		);

		$nav = $this->is_editor() ? ' tabindex="0" role="button"' : ' role="group"';

		printf(
			'<div class="mm-suc-p-page-countdown" data-mm-suc-p-part="countdown" data-end="%1$s" aria-labelledby="mm-suc-p-cd-label"%2$s>',
			esc_attr( $iso ),
			$nav // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup built above.
		);

		printf(
			'<p id="mm-suc-p-cd-label" class="mm-suc-p-page-sr">%s</p>',
			esc_html__( 'Time until we are back', 'wp-maintenance-mode-site-under-construction' )
		);

		foreach ( $units as $key => $unit ) {
			printf(
				'<div class="mm-suc-p-page-unit"><span class="mm-suc-p-page-unit-value" data-unit="%1$s">%2$s</span><span class="mm-suc-p-page-unit-label">%3$s</span></div>',
				esc_attr( $key ),
				esc_html( str_pad( (string) $unit[0], 2, '0', STR_PAD_LEFT ) ),
				esc_html( $unit[1] )
			);
		}

		echo '<p class="mm-suc-p-page-sr" data-mm-suc-p-live="countdown" aria-live="polite" aria-atomic="true"></p>';
		echo '</div>';
	}

	/**
	 * The contact trigger.
	 *
	 * A button on the public page. In the editor the same element is the
	 * editable label instead, because a contenteditable inside a button is not
	 * reliably editable across browsers - the sidebar keeps a real text control
	 * bound to the same field either way.
	 *
	 * @return void
	 */
	public function contact() {
		if ( ! $this->has_contact() ) {
			return;
		}

		if ( $this->is_editor() ) {
			$this->part( 'contact_button' );

			return;
		}

		printf(
			'<button type="button" class="mm-suc-p-page-contact" data-mm-suc-p-part="contact_button" aria-expanded="false" aria-controls="mm-suc-p-page-sheet">%s</button>',
			esc_html( (string) $this->option( 'contact_button', '' ) )
		);
	}

	/**
	 * The site host footer line.
	 *
	 * @return void
	 */
	public function host() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! $host ) {
			return;
		}

		printf( '<p class="mm-suc-p-page-host">%s</p>', esc_html( $host ) );
	}

	/**
	 * The sliding contact sheet and its form.
	 *
	 * @return void
	 */
	public function sheet() {
		if ( ! $this->has_contact() ) {
			return;
		}

		$sending = esc_attr__( 'Sending…', 'wp-maintenance-mode-site-under-construction' );

		echo '<div class="mm-suc-p-page-sheet" id="mm-suc-p-page-sheet" role="dialog" aria-modal="true" aria-labelledby="mm-suc-p-sheet-title" hidden>';
		echo '<div class="mm-suc-p-page-sheet-inner">';

		printf(
			'<h2 class="mm-suc-p-page-sheet-title" id="mm-suc-p-sheet-title">%s</h2>',
			esc_html__( 'Send us a message', 'wp-maintenance-mode-site-under-construction' )
		);

		printf(
			'<button type="button" class="mm-suc-p-page-sheet-close" aria-label="%s"><span aria-hidden="true">&times;</span></button>',
			esc_attr__( 'Close', 'wp-maintenance-mode-site-under-construction' )
		);

		echo '<form class="mm-suc-p-page-form" novalidate>';

		printf(
			'<p class="mm-suc-p-page-row"><label for="mm-suc-p-name">%1$s</label><input type="text" id="mm-suc-p-name" name="name" maxlength="%2$d" autocomplete="name" required /><span class="mm-suc-p-page-error" id="mm-suc-p-name-error"></span></p>',
			esc_html__( 'Your name', 'wp-maintenance-mode-site-under-construction' ),
			(int) MM_SUC_P_CAP_NAME
		);

		printf(
			'<p class="mm-suc-p-page-row"><label for="mm-suc-p-email">%1$s</label><input type="email" id="mm-suc-p-email" name="email" maxlength="%2$d" inputmode="email" autocomplete="email" placeholder="name@example.com" required /><span class="mm-suc-p-page-error" id="mm-suc-p-email-error"></span></p>',
			esc_html__( 'Your email', 'wp-maintenance-mode-site-under-construction' ),
			(int) MM_SUC_P_CAP_EMAIL
		);

		printf(
			'<p class="mm-suc-p-page-row"><label for="mm-suc-p-message">%1$s</label><textarea id="mm-suc-p-message" name="message" rows="4" maxlength="%2$d" required></textarea><span class="mm-suc-p-page-error" id="mm-suc-p-message-error"></span></p>',
			esc_html__( 'Message', 'wp-maintenance-mode-site-under-construction' ),
			(int) MM_SUC_P_CAP_MESSAGE
		);

		// Honeypot: off-screen rather than display:none, and hidden from assistive technology.
		printf(
			'<p class="mm-suc-p-page-trap" aria-hidden="true"><label for="mm-suc-p-website">%1$s</label><input type="text" id="mm-suc-p-website" name="website" tabindex="-1" autocomplete="off" /></p>',
			esc_html__( 'Leave this field empty', 'wp-maintenance-mode-site-under-construction' )
		);

		printf(
			'<p class="mm-suc-p-page-actions"><button type="submit" class="mm-suc-p-page-send" data-sending="%1$s">%2$s</button></p>',
			$sending, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above with esc_attr__.
			esc_html__( 'Send message', 'wp-maintenance-mode-site-under-construction' )
		);

		echo '<p class="mm-suc-p-page-status" role="status" aria-live="polite"></p>';
		echo '</form>';
		echo '</div>';
		echo '<button type="button" class="mm-suc-p-page-scrim" tabindex="-1" aria-hidden="true"></button>';
		echo '</div>';
	}
}
