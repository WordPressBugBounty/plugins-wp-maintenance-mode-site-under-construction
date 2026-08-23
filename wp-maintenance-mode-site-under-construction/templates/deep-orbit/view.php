<?php
/**
 * View: Deep Orbit.
 *
 * Every part the editor binds to is emitted through $view, so this file decides
 * arrangement and nothing else. Copy this folder, rename it, edit template.json
 * and this file - the plugin discovers it with no code change.
 *
 * @var MM_SUC_P_Template_View $view     Part emitters and configuration access.
 * @var MM_SUC_P_Template|null $template The active template.
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
