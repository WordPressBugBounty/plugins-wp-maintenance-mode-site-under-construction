<?php
/**
 * Template controller.
 *
 * Resolves the active template, prepares the view data, injects the style
 * contract and renders the document body. It is the only place that includes a
 * template's view file, and it verifies the result before returning it: a
 * broken third-party template degrades to the built-in view instead of
 * breaking the page or the editor.
 *
 * @package wp-maintenance-mode-site-under-construction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the maintenance document for a template.
 */
class MM_SUC_P_Template_Controller {

	/**
	 * Render the complete .mm-suc-p-page element.
	 *
	 * @param array  $options Configuration.
	 * @param string $context 'page' or 'editor'.
	 * @return string
	 */
	public static function render( $options, $context = 'page' ) {
		$template = MM_SUC_P_Template_Registry::resolve( isset( $options['template'] ) ? $options['template'] : '' );
		$view     = new MM_SUC_P_Template_View( $options, $template, $context );

		$classes = array(
			'mm-suc-p-page',
			'mm-suc-p-page--' . sanitize_html_class( self::layout( $options ) ),
		);

		if ( $template ) {
			$classes[] = 'mm-suc-p-page--tpl-' . sanitize_html_class( $template->get_slug() );
		}

		if ( 'editor' === $context ) {
			$classes[] = 'mm-suc-p-page--editing';
		}

		$html  = sprintf(
			'<div class="%1$s" style="%2$s" data-mm-suc-p-template="%3$s">',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( self::inline_style( $options, $template ) ),
			esc_attr( $template ? $template->get_slug() : '' )
		);
		$html .= self::body( $view, $template );
		$html .= '</div>';

		return $html;
	}

	/**
	 * The stored layout modifier, whitelisted.
	 *
	 * @param array $options Configuration.
	 * @return string
	 */
	public static function layout( $options ) {
		$allowed = array( 'centered', 'left', 'minimal', 'split' );
		$layout  = isset( $options['layout'] ) ? sanitize_key( $options['layout'] ) : 'centered';

		return in_array( $layout, $allowed, true ) ? $layout : 'centered';
	}

	/**
	 * Render the template view, falling back when it does not honour the contract.
	 *
	 * @param MM_SUC_P_Template_View $view     View helper.
	 * @param MM_SUC_P_Template|null $template Active template.
	 * @return string
	 */
	protected static function body( $view, $template ) {
		$html = '';

		if ( $template ) {
			$path = $template->get_view_path();

			if ( is_readable( $path ) ) {
				ob_start();
				include $path;
				$html = (string) ob_get_clean();
			}
		}

		if ( ! self::honours_contract( $html, $view ) ) {
			ob_start();
			include MM_SUC_P_DIR . 'includes/view-default.php';
			$html = (string) ob_get_clean();
		}

		return $html;
	}

	/**
	 * Does the rendered markup carry every part the editor binds to.
	 *
	 * @param string                 $html Rendered markup.
	 * @param MM_SUC_P_Template_View $view View helper.
	 * @return bool
	 */
	protected static function honours_contract( $html, $view ) {
		if ( '' === trim( $html ) ) {
			return false;
		}

		$required = array( 'eyebrow', 'headline', 'message' );

		if ( $view->has_contact() ) {
			$required[] = 'contact_button';
		}

		foreach ( $required as $field ) {
			if ( false === strpos( $html, 'data-mm-suc-p-part="' . $field . '"' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * The style contract, as an inline custom-property list.
	 *
	 * The values are built by mm_suc_p_inline_style() in template.php, which
	 * owns the injector; the controller only decides which template's palette
	 * feeds it.
	 *
	 * @param array                  $options  Configuration.
	 * @param MM_SUC_P_Template|null $template Active template.
	 * @return string
	 */
	public static function inline_style( $options, $template ) {
		return mm_suc_p_inline_style( $options, $template );
	}

	/**
	 * The background image URL for the current configuration.
	 *
	 * @param array                  $options  Configuration.
	 * @param MM_SUC_P_Template|null $template Active template.
	 * @return string
	 */
	public static function background_url( $options, $template ) {
		$source = isset( $options['background'] ) ? sanitize_key( $options['background'] ) : 'template';

		if ( 'custom' === $source && ! empty( $options['custom_background_id'] ) ) {
			$url = wp_get_attachment_image_url( (int) $options['custom_background_id'], 'full' );

			if ( $url ) {
				return $url;
			}
		}

		if ( 'none' === $source ) {
			return '';
		}

		return $template ? $template->get_background_url() : '';
	}

	/**
	 * A CSS rgba() string built from a validated hex colour and a percentage.
	 *
	 * @param string $hex   Hex colour.
	 * @param int    $alpha 0-100.
	 * @return string
	 */
	public static function rgba( $hex, $alpha ) {
		$hex = sanitize_hex_color( (string) $hex );

		if ( ! $hex ) {
			$hex = '#000000';
		}

		$hex = ltrim( $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		$alpha = max( 0, min( 100, (int) $alpha ) );

		return sprintf(
			'rgba(%d,%d,%d,%s)',
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
			number_format( $alpha / 100, 2, '.', '' )
		);
	}

	/**
	 * Register the shared page stylesheet and the active template's stylesheet.
	 *
	 * @param MM_SUC_P_Template|null $template Active template.
	 * @param bool                   $enqueue  Whether to enqueue as well as register.
	 * @return void
	 */
	public static function register_styles( $template, $enqueue = true ) {
		wp_register_style(
			'mm-suc-p-frontend',
			MM_SUC_P_URL . 'assets/css/frontend.css',
			array(),
			MM_SUC_P_VERSION
		);

		if ( $enqueue ) {
			wp_enqueue_style( 'mm-suc-p-frontend' );
		}

		if ( ! $template || '' === $template->get_style_url() ) {
			return;
		}

		wp_register_style(
			'mm-suc-p-template-' . $template->get_slug(),
			$template->get_style_url(),
			array( 'mm-suc-p-frontend' ),
			$template->get_style_version()
		);

		if ( $enqueue ) {
			wp_enqueue_style( 'mm-suc-p-template-' . $template->get_slug() );
		}
	}
}
