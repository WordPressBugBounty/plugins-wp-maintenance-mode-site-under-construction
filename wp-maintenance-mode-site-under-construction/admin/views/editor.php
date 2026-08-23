<?php

/**
 * The editor screen.
 *
 * @var array               $options   Current configuration.
 * @var MM_SUC_P_Template[] $templates Installed templates, ordered.
 * @var bool                $enabled   Whether maintenance mode is on.
 *
 * @package wp-maintenance-mode-site-under-construction
 */

if (! defined('ABSPATH')) {
	exit;
}

$mm_suc_p_layouts = array(
	'centered' => __('Centered', 'wp-maintenance-mode-site-under-construction'),
	'left'     => __('Left focus', 'wp-maintenance-mode-site-under-construction'),
	'minimal'  => __('Minimal', 'wp-maintenance-mode-site-under-construction'),
	'split'    => __('Split', 'wp-maintenance-mode-site-under-construction'),
);

$mm_suc_p_logo   = mm_suc_p_resolve_logo($options);
$mm_suc_p_custom = ! empty($options['custom_background_id']) ? wp_get_attachment_image_url((int) $options['custom_background_id'], 'medium') : '';
$mm_suc_p_roles  = get_editable_roles();
?>
<div class="mm-suc-p-admin" id="mm-suc-p-admin">

	<h1 class="mm-suc-p-sr"><?php esc_html_e('Maintenance mode editor', 'wp-maintenance-mode-site-under-construction'); ?></h1>

	<?php
	/*
	 * The first tab stop of the screen, deliberately. The master switch hides an
	 * entire site and must never be one careless Tab-Space away; see
	 * design-system/components/selection-controls.md.
	 */
	?>
	<a class="mm-suc-p-skip" href="#mm-suc-p-panel-schedule"><?php esc_html_e('Skip to the maintenance settings', 'wp-maintenance-mode-site-under-construction'); ?></a>

	<section class="mm-suc-p-tray" id="mm-suc-p-tray" aria-labelledby="mm-suc-p-tray-head" hidden>
		<h2 class="mm-suc-p-tray-head" id="mm-suc-p-tray-head">
			<button type="button" class="mm-suc-p-tray-toggle" aria-expanded="false" aria-controls="mm-suc-p-tray-body">
				<?php echo mm_suc_p_icon('warning'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
				?>
				<span><?php esc_html_e('Notices from other plugins', 'wp-maintenance-mode-site-under-construction'); ?></span>
				<span class="mm-suc-p-tray-count" data-mm-suc-p-tray-count>0</span>
				<?php echo mm_suc_p_icon('chevron-down', 'mm-suc-p-panel-chevron'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
				?>
			</button>
		</h2>
		<div class="mm-suc-p-tray-body" id="mm-suc-p-tray-body" hidden></div>
	</section>

	<header class="mm-suc-p-header">
		<div class="mm-suc-p-header-start">
			<span class="mm-suc-p-pill mm-suc-p-pill--<?php echo $enabled ? 'maintenance' : 'live'; ?>" id="mm-suc-p-pill">
				<?php echo $enabled ? mm_suc_p_icon('maintenance') : mm_suc_p_icon('site-live'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
				?>
				<span class="mm-suc-p-pill-text">
					<?php
					echo $enabled
						? esc_html__('Maintenance mode is on', 'wp-maintenance-mode-site-under-construction')
						: esc_html__('Site is live', 'wp-maintenance-mode-site-under-construction');
					?>
				</span>
			</span>

			<label class="mm-suc-p-switch">
				<input type="checkbox" role="switch" name="enabled" value="1"
					data-mm-suc-p-field="enabled"
					aria-describedby="mm-suc-p-enabled-consequence"
					<?php checked($enabled); ?> />
				<span class="mm-suc-p-switch-track" aria-hidden="true"></span>
				<span class="mm-suc-p-switch-label"><?php esc_html_e('Maintenance mode', 'wp-maintenance-mode-site-under-construction'); ?></span>
			</label>
		</div>

		<div class="mm-suc-p-header-end">
			<a class="mm-suc-p-btn mm-suc-p-btn--ghost" href="<?php echo esc_url(mm_suc_p_preview_url()); ?>" target="_blank" rel="noopener">
				<?php echo mm_suc_p_icon('external-link'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
				?>
				<?php esc_html_e('Open preview', 'wp-maintenance-mode-site-under-construction'); ?>
				<span class="mm-suc-p-sr"><?php esc_html_e('(opens in a new tab)', 'wp-maintenance-mode-site-under-construction'); ?></span>
			</a>
			<button type="button" class="mm-suc-p-btn mm-suc-p-btn--primary" id="mm-suc-p-save">
				<?php echo mm_suc_p_icon('save'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
				?>
				<span class="mm-suc-p-save-label"><?php esc_html_e('Save changes', 'wp-maintenance-mode-site-under-construction'); ?></span>
			</button>
		</div>
	</header>

	<p class="mm-suc-p-consequence" id="mm-suc-p-enabled-consequence">
		<?php esc_html_e('Visitors see the maintenance page. You and other administrators still see the site.', 'wp-maintenance-mode-site-under-construction'); ?>
	</p>

	<div class="mm-suc-p-workspace">

		<div class="mm-suc-p-sidebar" role="region" aria-label="<?php esc_attr_e('Maintenance page settings', 'wp-maintenance-mode-site-under-construction'); ?>">

			<?php /* ---------------------------------------------- Mode & Schedule */ ?>
			<section class="mm-suc-p-panel" data-mm-suc-p-panel="schedule">
				<?php mm_suc_p_panel_head('schedule', mm_suc_p_icon('clock'), __('Mode & Schedule', 'wp-maintenance-mode-site-under-construction'), true); ?>
				<div class="mm-suc-p-panel-body" id="mm-suc-p-panel-schedule">
					<div class="mm-suc-p-fields">

						<div class="mm-suc-p-field">
							<label class="mm-suc-p-label" for="mm-suc-p-end"><?php esc_html_e('Back online at', 'wp-maintenance-mode-site-under-construction'); ?></label>
							<input class="mm-suc-p-input" type="datetime-local" id="mm-suc-p-end" name="end_datetime"
								data-mm-suc-p-field="end_datetime"
								value="<?php echo esc_attr($options['end_datetime']); ?>"
								aria-describedby="mm-suc-p-end-help mm-suc-p-end-duration" />
							<p class="mm-suc-p-help" id="mm-suc-p-end-help">
								<?php
								printf(
									/* translators: %s: the site timezone, for example UTC+03:00. */
									esc_html__('Site time (%s). Leave empty to show no countdown.', 'wp-maintenance-mode-site-under-construction'),
									esc_html(mm_suc_p_timezone_label())
								);
								?>
							</p>
							<p class="mm-suc-p-help mm-suc-p-help--strong" id="mm-suc-p-end-duration" data-mm-suc-p-duration></p>
						</div>

						<div class="mm-suc-p-field">
							<label class="mm-suc-p-check">
								<input type="checkbox" name="auto_disable" value="1" data-mm-suc-p-field="auto_disable" <?php checked(! empty($options['auto_disable'])); ?> />
								<span class="mm-suc-p-check-box" aria-hidden="true"><?php echo mm_suc_p_icon('check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
																					?></span>
								<span class="mm-suc-p-check-label"><?php esc_html_e('Go live automatically when the time is reached', 'wp-maintenance-mode-site-under-construction'); ?></span>
							</label>
							<p class="mm-suc-p-help"><?php esc_html_e('Off means the site stays hidden until you turn maintenance mode off yourself.', 'wp-maintenance-mode-site-under-construction'); ?></p>
						</div>

					</div>
				</div>
			</section>

			<?php /* ---------------------------------------------- Content */ ?>
			<section class="mm-suc-p-panel" data-mm-suc-p-panel="content">
				<?php mm_suc_p_panel_head('content', mm_suc_p_icon('image'), __('Content', 'wp-maintenance-mode-site-under-construction'), false); ?>
				<div class="mm-suc-p-panel-body" id="mm-suc-p-panel-content" hidden>
					<div class="mm-suc-p-fields">

						<div class="mm-suc-p-field" data-mm-suc-p-media="logo">
							<span class="mm-suc-p-label" id="mm-suc-p-logo-label"><?php esc_html_e('Logo', 'wp-maintenance-mode-site-under-construction'); ?></span>
							<div class="mm-suc-p-media">
								<span class="mm-suc-p-media-thumb" data-mm-suc-p-media-thumb>
									<?php if ('image' === $mm_suc_p_logo['type']) : ?>
										<img src="<?php echo esc_url($mm_suc_p_logo['url']); ?>" alt="" />
									<?php else : ?>
										<?php echo mm_suc_p_icon('image'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
										?>
									<?php endif; ?>
								</span>
								<span class="mm-suc-p-media-actions">
									<button type="button" class="mm-suc-p-btn mm-suc-p-btn--secondary mm-suc-p-btn--sm" data-mm-suc-p-media-choose aria-describedby="mm-suc-p-logo-help">
										<?php esc_html_e('Choose image', 'wp-maintenance-mode-site-under-construction'); ?>
									</button>
									<button type="button" class="mm-suc-p-btn mm-suc-p-btn--ghost mm-suc-p-btn--sm" data-mm-suc-p-media-clear>
										<?php echo mm_suc_p_icon('trash'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
										?>
										<?php esc_html_e('Use site logo', 'wp-maintenance-mode-site-under-construction'); ?>
									</button>
								</span>
							</div>
							<input type="hidden" name="logo_id" data-mm-suc-p-field="logo_id" data-mm-suc-p-media-value value="<?php echo esc_attr((int) $options['logo_id']); ?>" />
							<p class="mm-suc-p-help" id="mm-suc-p-logo-help">
								<?php
								if ('custom' === $mm_suc_p_logo['source']) {
									esc_html_e('A logo chosen for maintenance mode. Use site logo goes back to the one your theme uses.', 'wp-maintenance-mode-site-under-construction');
								} elseif ('site-title' === $mm_suc_p_logo['source']) {
									esc_html_e('No logo is set anywhere, so your site title is shown as text.', 'wp-maintenance-mode-site-under-construction');
								} else {
									esc_html_e('Using your site logo. Choosing an image here overrides it for maintenance mode only.', 'wp-maintenance-mode-site-under-construction');
								}
								?>
							</p>
						</div>

						<div class="mm-suc-p-field">
							<label class="mm-suc-p-label" for="mm-suc-p-eyebrow"><?php esc_html_e('Eyebrow', 'wp-maintenance-mode-site-under-construction'); ?></label>
							<input class="mm-suc-p-input" type="text" id="mm-suc-p-eyebrow" name="eyebrow" maxlength="80"
								data-mm-suc-p-field="eyebrow" value="<?php echo esc_attr($options['eyebrow']); ?>"
								aria-describedby="mm-suc-p-eyebrow-help" />
							<p class="mm-suc-p-help" id="mm-suc-p-eyebrow-help"><?php esc_html_e('The small line above the headline. Leave empty to hide it.', 'wp-maintenance-mode-site-under-construction'); ?></p>
						</div>

						<div class="mm-suc-p-field">
							<label class="mm-suc-p-label" for="mm-suc-p-headline"><?php esc_html_e('Headline', 'wp-maintenance-mode-site-under-construction'); ?></label>
							<input class="mm-suc-p-input" type="text" id="mm-suc-p-headline" name="headline" maxlength="120"
								data-mm-suc-p-field="headline" value="<?php echo esc_attr($options['headline']); ?>"
								aria-describedby="mm-suc-p-headline-help" />
							<p class="mm-suc-p-help" id="mm-suc-p-headline-help"><?php esc_html_e('The page heading. You can also edit it directly in the preview.', 'wp-maintenance-mode-site-under-construction'); ?></p>
						</div>

						<div class="mm-suc-p-field">
							<label class="mm-suc-p-label" for="mm-suc-p-message"><?php esc_html_e('Message', 'wp-maintenance-mode-site-under-construction'); ?></label>
							<textarea class="mm-suc-p-input mm-suc-p-textarea" id="mm-suc-p-message" name="message" rows="4" maxlength="600"
								data-mm-suc-p-field="message" aria-describedby="mm-suc-p-message-help"><?php echo esc_textarea($options['message']); ?></textarea>
							<p class="mm-suc-p-help" id="mm-suc-p-message-help"><?php esc_html_e('Explain what is happening and when you expect to be back.', 'wp-maintenance-mode-site-under-construction'); ?></p>
						</div>

					</div>
				</div>
			</section>

			<?php /* ---------------------------------------------- Design & Styling */ ?>
			<section class="mm-suc-p-panel" data-mm-suc-p-panel="design">
				<?php mm_suc_p_panel_head('design', mm_suc_p_icon('palette'), __('Design & Styling', 'wp-maintenance-mode-site-under-construction'), false); ?>
				<div class="mm-suc-p-panel-body" id="mm-suc-p-panel-design" hidden>
					<div class="mm-suc-p-fields">

						<?php /* The template library: a vertical list, one row per installed template. */ ?>
						<fieldset class="mm-suc-p-picker mm-suc-p-picker--library" data-mm-suc-p-library>
							<legend class="mm-suc-p-label"><?php esc_html_e('Template', 'wp-maintenance-mode-site-under-construction'); ?></legend>

							<?php if (empty($templates)) : ?>
								<p class="mm-suc-p-empty">
									<?php echo mm_suc_p_icon('warning'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
									?>
									<?php esc_html_e('No templates are installed. Copy a template folder into the plugin\'s templates directory and it will appear here.', 'wp-maintenance-mode-site-under-construction'); ?>
								</p>
							<?php else : ?>
								<div class="mm-suc-p-rail" data-mm-suc-p-rail-track>
									<?php
									foreach ($templates as $mm_suc_p_template) {
										echo mm_suc_p_library_row( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the helper escapes every value it emits.
											$mm_suc_p_template,
											$options['template'] === $mm_suc_p_template->get_slug()
										);
									}
									?>
								</div>
								<p class="mm-suc-p-help" data-mm-suc-p-template-note>
									<?php esc_html_e('Each template brings its own background and colours. Layout, background and colours sit on the toolbar above the preview.', 'wp-maintenance-mode-site-under-construction'); ?>
								</p>
							<?php endif; ?>
						</fieldset>

						<div class="mm-suc-p-field Save_as_preset_div" data-mm-suc-p-preset>
							<label class="mm-suc-p-label" for="mm-suc-p-preset-name"><?php esc_html_e('Save as preset', 'wp-maintenance-mode-site-under-construction'); ?></label>
							<div class="mm-suc-p-preset-row">
								<input class="mm-suc-p-input" type="text" id="mm-suc-p-preset-name" maxlength="60"
									placeholder="<?php esc_attr_e('Name this design', 'wp-maintenance-mode-site-under-construction'); ?>"
									aria-describedby="mm-suc-p-preset-help" />
								<button type="button" class="mm-suc-p-btn mm-suc-p-btn--secondary mm-suc-p-btn--sm" id="mm-suc-p-save-preset">
									<?php echo mm_suc_p_icon('save'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
									?>
									<span class="mm-suc-p-preset-label"><?php esc_html_e('Save as preset', 'wp-maintenance-mode-site-under-construction'); ?></span>
								</button>
							</div>
							<p class="mm-suc-p-help" id="mm-suc-p-preset-help">
								<?php esc_html_e('Keeps the current layout, background and colours as a new template in this list. It is written as a folder you can copy to another site, or delete to remove it.', 'wp-maintenance-mode-site-under-construction'); ?>
							</p>
						</div>

					</div>
				</div>
			</section>

			<?php /* ---------------------------------------------- Countdown */ ?>
			<section class="mm-suc-p-panel" data-mm-suc-p-panel="countdown">
				<?php mm_suc_p_panel_head('countdown', mm_suc_p_icon('clock'), __('Countdown', 'wp-maintenance-mode-site-under-construction'), false); ?>
				<div class="mm-suc-p-panel-body" id="mm-suc-p-panel-countdown" hidden>
					<div class="mm-suc-p-fields">
						<div class="mm-suc-p-field">
							<label class="mm-suc-p-check">
								<input type="checkbox" name="countdown" value="1" data-mm-suc-p-field="countdown" <?php checked(! empty($options['countdown'])); ?> />
								<span class="mm-suc-p-check-box" aria-hidden="true"><?php echo mm_suc_p_icon('check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
																					?></span>
								<span class="mm-suc-p-check-label"><?php esc_html_e('Show the countdown', 'wp-maintenance-mode-site-under-construction'); ?></span>
							</label>
							<p class="mm-suc-p-help"><?php esc_html_e('The countdown needs an end time. Without one nothing is shown, even when this is on.', 'wp-maintenance-mode-site-under-construction'); ?></p>
						</div>
					</div>
				</div>
			</section>

			<?php /* ---------------------------------------------- Contact */ ?>
			<section class="mm-suc-p-panel" data-mm-suc-p-panel="contact">
				<?php mm_suc_p_panel_head('contact', mm_suc_p_icon('contact-mail'), __('Contact', 'wp-maintenance-mode-site-under-construction'), false); ?>
				<div class="mm-suc-p-panel-body" id="mm-suc-p-panel-contact" hidden>
					<div class="mm-suc-p-fields">

						<div class="mm-suc-p-field">
							<label class="mm-suc-p-check">
								<input type="checkbox" name="contact_enabled" value="1" data-mm-suc-p-field="contact_enabled" <?php checked(! empty($options['contact_enabled'])); ?> />
								<span class="mm-suc-p-check-box" aria-hidden="true"><?php echo mm_suc_p_icon('check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
																					?></span>
								<span class="mm-suc-p-check-label"><?php esc_html_e('Let visitors send a message', 'wp-maintenance-mode-site-under-construction'); ?></span>
							</label>
						</div>

						<div class="mm-suc-p-field">
							<label class="mm-suc-p-label" for="mm-suc-p-contact-button"><?php esc_html_e('Button label', 'wp-maintenance-mode-site-under-construction'); ?></label>
							<input class="mm-suc-p-input" type="text" id="mm-suc-p-contact-button" name="contact_button" maxlength="60"
								data-mm-suc-p-field="contact_button" value="<?php echo esc_attr($options['contact_button']); ?>" />
						</div>

						<div class="mm-suc-p-field">
							<label class="mm-suc-p-label" for="mm-suc-p-contact-email"><?php esc_html_e('Send messages to', 'wp-maintenance-mode-site-under-construction'); ?></label>
							<input class="mm-suc-p-input" type="email" id="mm-suc-p-contact-email" name="contact_email" maxlength="190"
								inputmode="email" autocomplete="off" placeholder="name@example.com"
								data-mm-suc-p-field="contact_email" value="<?php echo esc_attr($options['contact_email']); ?>"
								aria-describedby="mm-suc-p-contact-email-help" />
							<p class="mm-suc-p-help" id="mm-suc-p-contact-email-help">
								<?php
								printf(
									/* translators: %s: the site admin email address. */
									esc_html__('Messages go to %s if this is empty.', 'wp-maintenance-mode-site-under-construction'),
									esc_html(get_option('admin_email'))
								);
								?>
							</p>
						</div>

						<div class="mm-suc-p-field">
							<label class="mm-suc-p-check">
								<input type="checkbox" data-mm-suc-p-sheet-preview />
								<span class="mm-suc-p-check-box" aria-hidden="true"><?php echo mm_suc_p_icon('check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
																					?></span>
								<span class="mm-suc-p-check-label"><?php esc_html_e('Open the message panel in the preview', 'wp-maintenance-mode-site-under-construction'); ?></span>
							</label>
							<p class="mm-suc-p-help"><?php esc_html_e('A preview aid only. It is not saved and visitors always start with it closed.', 'wp-maintenance-mode-site-under-construction'); ?></p>
						</div>

					</div>
				</div>
			</section>

			<?php /* ---------------------------------------------- Access */ ?>
			<section class="mm-suc-p-panel" data-mm-suc-p-panel="access">
				<?php mm_suc_p_panel_head('access', mm_suc_p_icon('shield'), __('Access', 'wp-maintenance-mode-site-under-construction'), false); ?>
				<div class="mm-suc-p-panel-body" id="mm-suc-p-panel-access" hidden>
					<div class="mm-suc-p-fields">
						<fieldset class="mm-suc-p-field">
							<legend class="mm-suc-p-label"><?php esc_html_e('Who still sees the site', 'wp-maintenance-mode-site-under-construction'); ?></legend>
							<div class="mm-suc-p-checks">
								<?php foreach ($mm_suc_p_roles as $mm_suc_p_role => $mm_suc_p_details) : ?>
									<?php $mm_suc_p_locked = ('administrator' === $mm_suc_p_role); ?>
									<label class="mm-suc-p-check<?php echo $mm_suc_p_locked ? ' mm-suc-p-check--locked' : ''; ?>">
										<input type="checkbox" name="bypass_roles[]" value="<?php echo esc_attr($mm_suc_p_role); ?>"
											data-mm-suc-p-field="bypass_roles"
											<?php checked($mm_suc_p_locked || in_array($mm_suc_p_role, (array) $options['bypass_roles'], true)); ?>
											<?php disabled($mm_suc_p_locked); ?> />
										<span class="mm-suc-p-check-box" aria-hidden="true"><?php echo mm_suc_p_icon('check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
																							?></span>
										<span class="mm-suc-p-check-label"><?php echo esc_html(translate_user_role($mm_suc_p_details['name'])); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
							<p class="mm-suc-p-help mm-suc-p-help--strong">
								<?php echo mm_suc_p_icon('shield'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
								?>
								<?php esc_html_e('Administrators always see the site. This cannot be turned off, so you can never lock yourself out.', 'wp-maintenance-mode-site-under-construction'); ?>
							</p>
						</fieldset>
					</div>
				</div>
			</section>

		</div><!-- /.mm-suc-p-sidebar -->

		<div class="mm-suc-p-preview-col">
			<div class="mm-suc-p-viewports">
				<fieldset class="mm-suc-p-segmented">
					<legend class="mm-suc-p-sr"><?php esc_html_e('Preview width', 'wp-maintenance-mode-site-under-construction'); ?></legend>
					<?php
					$mm_suc_p_viewports = array(
						'desktop' => array(__('Desktop', 'wp-maintenance-mode-site-under-construction'), '100%'),
						'tablet'  => array(__('Tablet', 'wp-maintenance-mode-site-under-construction'), '768px'),
						'mobile'  => array(__('Mobile', 'wp-maintenance-mode-site-under-construction'), '390px'),
					);
					foreach ($mm_suc_p_viewports as $mm_suc_p_key => $mm_suc_p_view) :
					?>
						<label class="mm-suc-p-segment">
							<input type="radio" name="mm-suc-p-viewport" value="<?php echo esc_attr($mm_suc_p_view[1]); ?>"
								data-mm-suc-p-viewport <?php checked('desktop', $mm_suc_p_key); ?> />
							<span class="mm-suc-p-segment-label"><?php echo esc_html($mm_suc_p_view[0]); ?></span>
						</label>
					<?php endforeach; ?>
				</fieldset>
				<div class="mm-suc-p-viewports-end">
					<p class="mm-suc-p-viewport-width" data-mm-suc-p-width aria-live="polite"></p>
				</div>
			</div>

			<p class="mm-suc-p-sr" id="mm-suc-p-palette-hint"><?php esc_html_e('Point at anything in the preview to change its colour. Press to turn that off. Every colour is also a control on this toolbar.', 'wp-maintenance-mode-site-under-construction'); ?></p>

			<?php /* ---------------------------------------------- Design toolbar
			 * The layout, background and colour controls sit here, above the thing
			 * they change, rather than in the sidebar. See
			 * design-system/components/navigation.md.
			 */ ?>
			<div class="mm-suc-p-toolbar" role="group" aria-label="<?php esc_attr_e('Design controls', 'wp-maintenance-mode-site-under-construction'); ?>">

				<!-- Group 1: Layout & Background -->
				<div class="mm-suc-p-toolbar-group is-open" data-mm-suc-p-toolbar-group="layout-bg">
					<button type="button" class="mm-suc-p-toolbar-toggle" aria-expanded="true" aria-controls="mm-suc-p-toolbar-pane-layout-bg" title="<?php esc_attr_e('Layout & Background', 'wp-maintenance-mode-site-under-construction'); ?>">
						<span class="mm-suc-p-toolbar-toggle-title"><?php esc_html_e('Layout & Background', 'wp-maintenance-mode-site-under-construction'); ?></span>
					</button>
					<div class="mm-suc-p-toolbar-pane" id="mm-suc-p-toolbar-pane-layout-bg">
						<div class="mm-suc-p-toolbar-pane-inner">
							<fieldset class="mm-suc-p-tool mm-suc-p-tool--layout" data-mm-suc-p-tool="layout">
								<legend class="mm-suc-p-tool-label"><?php esc_html_e('Layout', 'wp-maintenance-mode-site-under-construction'); ?></legend>
								<div class="mm-suc-p-picker-grid mm-suc-p-picker-grid--row">
									<?php foreach ($mm_suc_p_layouts as $mm_suc_p_key => $mm_suc_p_name) : ?>
										<label class="mm-suc-p-picker-option">
											<input type="radio" name="layout" value="<?php echo esc_attr($mm_suc_p_key); ?>"
												data-mm-suc-p-field="layout" <?php checked($options['layout'], $mm_suc_p_key); ?> />
											<span class="mm-suc-p-picker-visual" aria-hidden="true">
												<?php echo mm_suc_p_layout_diagram($mm_suc_p_key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. 
												?>
												<span class="mm-suc-p-picker-check">✓</span>
											</span>
										</label>
									<?php endforeach; ?>
								</div>
							</fieldset>

							<fieldset class="mm-suc-p-tool" data-mm-suc-p-tool="background" data-mm-suc-p-media="background">
								<legend class="mm-suc-p-tool-label"><?php esc_html_e('Background', 'wp-maintenance-mode-site-under-construction'); ?></legend>
								<div class="mm-suc-p-choices">
									<?php
									$mm_suc_p_sources = array(
										'template' => __('From the template', 'wp-maintenance-mode-site-under-construction'),
										'custom'   => __('My own image', 'wp-maintenance-mode-site-under-construction'),
										'none'     => __('No image', 'wp-maintenance-mode-site-under-construction'),
									);
									foreach ($mm_suc_p_sources as $mm_suc_p_key => $mm_suc_p_name) :
									?>
										<label class="mm-suc-p-radio">
											<input type="radio" name="background" value="<?php echo esc_attr($mm_suc_p_key); ?>"
												data-mm-suc-p-field="background" <?php checked($options['background'], $mm_suc_p_key); ?> />
											<span class="mm-suc-p-radio-dot" aria-hidden="true"></span>
											<span class="mm-suc-p-radio-label"><?php echo esc_html($mm_suc_p_name); ?></span>
										</label>
									<?php endforeach; ?>
								</div>

								<div class="mm-suc-p-media">
									<span class="mm-suc-p-media-thumb" data-mm-suc-p-media-thumb>
										<?php if ($mm_suc_p_custom) : ?>
											<img src="<?php echo esc_url($mm_suc_p_custom); ?>" alt="" />
										<?php else : ?>
											<?php echo mm_suc_p_icon('image'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
											?>
										<?php endif; ?>
									</span>
									<span class="mm-suc-p-media-actions">
										<button type="button" class="mm-suc-p-btn mm-suc-p-btn--secondary mm-suc-p-btn--sm" data-mm-suc-p-media-choose>
											<?php esc_html_e('Choose image', 'wp-maintenance-mode-site-under-construction'); ?>
										</button>
										<button type="button" class="mm-suc-p-btn mm-suc-p-btn--ghost mm-suc-p-btn--sm" data-mm-suc-p-media-clear>
											<?php echo mm_suc_p_icon('trash'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. 
											?>
											<?php esc_html_e('Remove', 'wp-maintenance-mode-site-under-construction'); ?>
										</button>
									</span>
								</div>
								<input type="hidden" name="custom_background_id" data-mm-suc-p-field="custom_background_id" data-mm-suc-p-media-value value="<?php echo esc_attr((int) $options['custom_background_id']); ?>" />
							</fieldset>
						</div>
					</div>
				</div>

				<!-- Group 2: Accent & Text Colour -->
				<div class="mm-suc-p-toolbar-group" data-mm-suc-p-toolbar-group="colors">
					<button type="button" class="mm-suc-p-toolbar-toggle" aria-expanded="false" aria-controls="mm-suc-p-toolbar-pane-colors" title="<?php esc_attr_e('Accent & Text Colour', 'wp-maintenance-mode-site-under-construction'); ?>">
						<span class="mm-suc-p-toolbar-toggle-title"><?php esc_html_e('Accent & Text Colour', 'wp-maintenance-mode-site-under-construction'); ?></span>
					</button>
					<div class="mm-suc-p-toolbar-pane" id="mm-suc-p-toolbar-pane-colors" hidden>
						<div class="mm-suc-p-toolbar-pane-inner">
							<div class="mm-suc-p-tool" data-mm-suc-p-tool="accent_color">
								<label class="mm-suc-p-tool-label" for="mm-suc-p-accent"><?php esc_html_e('Accent colour', 'wp-maintenance-mode-site-under-construction'); ?></label>
								<div class="mm-suc-p-color">
									<input type="color" id="mm-suc-p-accent" name="accent_color" data-mm-suc-p-field="accent_color" value="<?php echo esc_attr($options['accent_color']); ?>" />
									<input type="text" class="mm-suc-p-input" data-mm-suc-p-hex="accent_color" pattern="#[0-9a-fA-F]{6}" maxlength="7"
										aria-label="<?php esc_attr_e('Accent colour hex value', 'wp-maintenance-mode-site-under-construction'); ?>"
										value="<?php echo esc_attr($options['accent_color']); ?>" />
								</div>
								<p class="mm-suc-p-help"><?php esc_html_e('Used for the countdown, the contact action and links.', 'wp-maintenance-mode-site-under-construction'); ?></p>
							</div>

							<div class="mm-suc-p-tool" data-mm-suc-p-tool="text_color">
								<label class="mm-suc-p-tool-label" for="mm-suc-p-ink"><?php esc_html_e('Text colour', 'wp-maintenance-mode-site-under-construction'); ?></label>
								<div class="mm-suc-p-color">
									<input type="color" id="mm-suc-p-ink" name="text_color" data-mm-suc-p-field="text_color" value="<?php echo esc_attr($options['text_color']); ?>"
										aria-describedby="mm-suc-p-contrast" />
									<input type="text" class="mm-suc-p-input" data-mm-suc-p-hex="text_color" pattern="#[0-9a-fA-F]{6}" maxlength="7"
										aria-label="<?php esc_attr_e('Text colour hex value', 'wp-maintenance-mode-site-under-construction'); ?>"
										value="<?php echo esc_attr($options['text_color']); ?>" />
								</div>
								<p class="mm-suc-p-help" id="mm-suc-p-contrast" data-mm-suc-p-contrast></p>
							</div>
						</div>
					</div>
				</div>

				<!-- Group 3: Shading & Glass Blur -->
				<div class="mm-suc-p-toolbar-group" data-mm-suc-p-toolbar-group="effects">
					<button type="button" class="mm-suc-p-toolbar-toggle" aria-expanded="false" aria-controls="mm-suc-p-toolbar-pane-effects" title="<?php esc_attr_e('Shading & Glass Blur', 'wp-maintenance-mode-site-under-construction'); ?>">
						<span class="mm-suc-p-toolbar-toggle-title"><?php esc_html_e('Shading & Glass Blur', 'wp-maintenance-mode-site-under-construction'); ?></span>
					</button>
					<div class="mm-suc-p-toolbar-pane" id="mm-suc-p-toolbar-pane-effects" hidden>
						<div class="mm-suc-p-toolbar-pane-inner">
							<div class="mm-suc-p-tool" data-mm-suc-p-tool="overlay_opacity">
								<label class="mm-suc-p-tool-label" for="mm-suc-p-overlay"><?php esc_html_e('Background shading', 'wp-maintenance-mode-site-under-construction'); ?></label>
								<div class="mm-suc-p-range">
									<input type="range" id="mm-suc-p-overlay" name="overlay_opacity" min="0" max="100" step="1"
										data-mm-suc-p-field="overlay_opacity" value="<?php echo esc_attr((int) $options['overlay_opacity']); ?>" />
									<output data-mm-suc-p-output="overlay_opacity"><?php echo esc_html((int) $options['overlay_opacity']); ?></output>
								</div>
								<p class="mm-suc-p-help"><?php esc_html_e('How strongly the template shades its background image behind your text.', 'wp-maintenance-mode-site-under-construction'); ?></p>
							</div>

							<div class="mm-suc-p-tool" data-mm-suc-p-tool="glass_strength">
								<label class="mm-suc-p-tool-label" for="mm-suc-p-blur"><?php esc_html_e('Glass blur', 'wp-maintenance-mode-site-under-construction'); ?></label>
								<div class="mm-suc-p-range">
									<input type="range" id="mm-suc-p-blur" name="glass_strength" min="0" max="40" step="1"
										data-mm-suc-p-field="glass_strength" value="<?php echo esc_attr((int) $options['glass_strength']); ?>" />
									<output data-mm-suc-p-output="glass_strength"><?php echo esc_html((int) $options['glass_strength']); ?></output>
								</div>
								<p class="mm-suc-p-help"><?php esc_html_e('An enhancement only: the card stays readable at zero, and some browsers ignore it.', 'wp-maintenance-mode-site-under-construction'); ?></p>
							</div>
						</div>
					</div>
				</div>

			</div>

			<div class="mm-suc-p-preview" role="region" aria-label="<?php esc_attr_e('Live preview of the maintenance page', 'wp-maintenance-mode-site-under-construction'); ?>">
				<div class="mm-suc-p-preview-frame" id="mm-suc-p-preview" data-mm-suc-p-preview>
					<?php echo MM_SUC_P_Template_Controller::render($options, 'editor'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the controller escapes every value it emits. 
					?>
				</div>
			</div>
		</div>

	</div><!-- /.mm-suc-p-workspace -->

	<div class="mm-suc-p-toasts" id="mm-suc-p-toasts"></div>
</div>