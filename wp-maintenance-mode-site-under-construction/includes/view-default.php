<?php
/**
 * Built-in fallback view.
 *
 * Used when no template is installed, or when a template's own view does not
 * emit the editable parts the editor binds to. It is also the reference a new
 * template's view.php is copied from.
 *
 * Available in scope:
 *
 * @var MM_SUC_P_Template_View $view     Part emitters and configuration access.
 * @var MM_SUC_P_Template|null $template The active template, or null.
 *
 * @package wp-maintenance-mode-site-under-construction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$view->background();
?>
<div class="mm-suc-p-page-shell">
	<div class="mm-suc-p-page-card">
		<?php
		$view->logo();
		$view->eyebrow();
		$view->headline();
		$view->rule();
		$view->message();
		$view->countdown();
		$view->contact();
		$view->host();
		?>
	</div>
</div>
<?php
$view->sheet();
