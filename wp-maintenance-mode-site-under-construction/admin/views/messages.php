<?php
/**
 * The Messages List admin screen.
 *
 * @var array $options  Current configuration.
 * @var array $messages List of stored message associative arrays.
 * @var array $counts   Array with 'total' and 'unread' counts.
 *
 * @package wp-maintenance-mode-site-under-construction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mm_suc_p_total  = isset( $counts['total'] ) ? (int) $counts['total'] : count( $messages );
$mm_suc_p_unread = isset( $counts['unread'] ) ? (int) $counts['unread'] : 0;
$mm_suc_p_email  = ! empty( $options['contact_email'] ) ? sanitize_email( $options['contact_email'] ) : get_option( 'admin_email' );
?>
<div class="mm-suc-p-admin mm-suc-p-admin--messages" id="mm-suc-p-admin">

	<h1 class="mm-suc-p-sr"><?php esc_html_e( 'Messages list', 'wp-maintenance-mode-site-under-construction' ); ?></h1>

	<header class="mm-suc-p-header">
		<div class="mm-suc-p-header-start">
			<span class="mm-suc-p-pill" id="mm-suc-p-pill">
				<?php echo mm_suc_p_icon( 'contact-mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?>
				<span class="mm-suc-p-pill-text">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: total messages count, 2: unread messages count */
							__( '%1$d Messages (%2$d unread)', 'wp-maintenance-mode-site-under-construction' ),
							(int) $mm_suc_p_total,
							(int) $mm_suc_p_unread
						)
					);
					?>
				</span>
			</span>
		</div>

		<div class="mm-suc-p-header-end">
			<a class="mm-suc-p-btn mm-suc-p-btn--secondary" href="<?php echo esc_url( mm_suc_p_settings_url() ); ?>">
				<?php echo mm_suc_p_icon( 'maintenance' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?>
				<?php esc_html_e( 'Maintenance settings', 'wp-maintenance-mode-site-under-construction' ); ?>
			</a>

			<?php if ( $mm_suc_p_total > 0 ) : ?>
				<button type="button" class="mm-suc-p-btn mm-suc-p-btn--ghost mm-suc-p-btn--danger" id="mm-suc-p-clear-all-messages">
					<?php echo mm_suc_p_icon( 'trash' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?>
					<span><?php esc_html_e( 'Clear all messages', 'wp-maintenance-mode-site-under-construction' ); ?></span>
				</button>
			<?php endif; ?>
		</div>
	</header>

	<div class="mm-suc-p-messages-layout">

		<?php mm_suc_p_render_rating_banner(); ?>

		<!-- 1. Contact form job & How it works (Hint) -->
		<section class="mm-suc-p-hint-card" aria-labelledby="mm-suc-p-hint-title">
			<div class="mm-suc-p-hint-header">
				<div class="mm-suc-p-hint-icon" aria-hidden="true">
					<?php echo mm_suc_p_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?>
				</div>
				<div>
					<h2 class="mm-suc-p-hint-title" id="mm-suc-p-hint-title">
						<?php esc_html_e( 'How the Maintenance Contact Form Works', 'wp-maintenance-mode-site-under-construction' ); ?>
					</h2>
					<p class="mm-suc-p-hint-subtitle">
						<?php esc_html_e( 'Inquiries sent by visitors while your site is under construction or in maintenance mode.', 'wp-maintenance-mode-site-under-construction' ); ?>
					</p>
				</div>
			</div>

			<div class="mm-suc-p-hint-grid">
				<div class="mm-suc-p-hint-item">
					<div class="mm-suc-p-hint-bullet" aria-hidden="true"><?php echo mm_suc_p_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?></div>
					<div class="mm-suc-p-hint-content">
						<strong><?php esc_html_e( 'Dedicated Database Storage', 'wp-maintenance-mode-site-under-construction' ); ?></strong>
						<p><?php esc_html_e( 'Messages are stored securely in a dedicated WordPress database table with indexing, privacy protection, and fast lookup.', 'wp-maintenance-mode-site-under-construction' ); ?></p>
					</div>
				</div>

				<div class="mm-suc-p-hint-item">
					<div class="mm-suc-p-hint-bullet" aria-hidden="true"><?php echo mm_suc_p_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?></div>
					<div class="mm-suc-p-hint-content">
						<strong><?php esc_html_e( 'Email Notification & SMTP Fallback', 'wp-maintenance-mode-site-under-construction' ); ?></strong>
						<p>
							<?php
							printf(
								/* translators: %s: recipient email address */
								esc_html__( 'When a message arrives, an email is dispatched to %s. If mail/SMTP is not set up on your host, messages are never lost — they remain safely stored here.', 'wp-maintenance-mode-site-under-construction' ),
								'<code>' . esc_html( $mm_suc_p_email ) . '</code>'
							);
							?>
						</p>
					</div>
				</div>

				<div class="mm-suc-p-hint-item">
					<div class="mm-suc-p-hint-bullet" aria-hidden="true"><?php echo mm_suc_p_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?></div>
					<div class="mm-suc-p-hint-content">
						<strong><?php esc_html_e( 'Automatic Bot & Spam Protection', 'wp-maintenance-mode-site-under-construction' ); ?></strong>
						<p><?php esc_html_e( 'Built-in honeypot spam traps and per-IP rate limiting (maximum 4 messages per 15 minutes) stop automated bots without disrupting legitimate visitors.', 'wp-maintenance-mode-site-under-construction' ); ?></p>
					</div>
				</div>
			</div>
		</section>

		<!-- 2. Messages List -->
		<section class="mm-suc-p-panel mm-suc-p-messages-card" aria-labelledby="mm-suc-p-messages-title">
			<div class="mm-suc-p-messages-header">
				<h2 class="mm-suc-p-messages-title" id="mm-suc-p-messages-title">
					<?php esc_html_e( 'Received Messages', 'wp-maintenance-mode-site-under-construction' ); ?>
				</h2>
			</div>

			<div class="mm-suc-p-messages-body" id="mm-suc-p-messages-list-wrapper">
				<?php if ( empty( $messages ) ) : ?>
					<div class="mm-suc-p-messages-empty" id="mm-suc-p-messages-empty">
						<div class="mm-suc-p-empty-icon" aria-hidden="true">
							<?php echo mm_suc_p_icon( 'contact-mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?>
						</div>
						<h3><?php esc_html_e( 'No messages yet', 'wp-maintenance-mode-site-under-construction' ); ?></h3>
						<p><?php esc_html_e( 'When visitors send inquiries through the contact form on your maintenance page, they will appear here.', 'wp-maintenance-mode-site-under-construction' ); ?></p>
					</div>
				<?php else : ?>
					<div class="mm-suc-p-table-responsive">
						<table class="mm-suc-p-table" id="mm-suc-p-messages-table">
							<thead>
								<tr>
									<th scope="col" class="mm-suc-p-th-status"><span class="mm-suc-p-sr"><?php esc_html_e( 'Status', 'wp-maintenance-mode-site-under-construction' ); ?></span></th>
									<th scope="col"><?php esc_html_e( 'Sender', 'wp-maintenance-mode-site-under-construction' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Message snippet', 'wp-maintenance-mode-site-under-construction' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Date & Time', 'wp-maintenance-mode-site-under-construction' ); ?></th>
									<th scope="col" class="mm-suc-p-th-actions"><?php esc_html_e( 'Actions', 'wp-maintenance-mode-site-under-construction' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $messages as $msg ) : ?>
									<?php
									$msg_id      = isset( $msg['id'] ) ? (string) $msg['id'] : '';
									$msg_name    = isset( $msg['name'] ) ? (string) $msg['name'] : '';
									$msg_email   = isset( $msg['email'] ) ? (string) $msg['email'] : '';
									$msg_date    = isset( $msg['date'] ) ? (string) $msg['date'] : '';
									$msg_raw     = isset( $msg['message'] ) ? (string) $msg['message'] : '';
									$msg_snippet = mm_suc_p_truncate( $msg_raw, 80 );
									$is_unread   = empty( $msg['read'] );
									?>
									<tr class="mm-suc-p-message-row <?php echo $is_unread ? 'mm-suc-p-message-row--unread' : ''; ?>"
										id="mm-suc-p-row-<?php echo esc_attr( $msg_id ); ?>"
										data-mm-suc-p-msg-id="<?php echo esc_attr( $msg_id ); ?>"
										data-mm-suc-p-msg-name="<?php echo esc_attr( $msg_name ); ?>"
										data-mm-suc-p-msg-email="<?php echo esc_attr( $msg_email ); ?>"
										data-mm-suc-p-msg-date="<?php echo esc_attr( $msg_date ); ?>"
										data-mm-suc-p-msg-content="<?php echo esc_attr( $msg_raw ); ?>"
										data-mm-suc-p-msg-read="<?php echo $is_unread ? '0' : '1'; ?>">
										<td class="mm-suc-p-td-status">
											<span class="mm-suc-p-msg-badge <?php echo $is_unread ? 'mm-suc-p-msg-badge--unread' : 'mm-suc-p-msg-badge--read'; ?>"
												title="<?php echo $is_unread ? esc_attr__( 'Unread message', 'wp-maintenance-mode-site-under-construction' ) : esc_attr__( 'Read message', 'wp-maintenance-mode-site-under-construction' ); ?>">
												<span class="mm-suc-p-sr"><?php echo $is_unread ? esc_html__( 'Unread', 'wp-maintenance-mode-site-under-construction' ) : esc_html__( 'Read', 'wp-maintenance-mode-site-under-construction' ); ?></span>
											</span>
										</td>
										<td class="mm-suc-p-td-sender">
											<strong class="mm-suc-p-msg-sender-name"><?php echo esc_html( $msg_name ); ?></strong>
											<span class="mm-suc-p-msg-sender-email"><?php echo esc_html( $msg_email ); ?></span>
										</td>
										<td class="mm-suc-p-td-snippet">
											<span class="mm-suc-p-msg-text-snippet"><?php echo esc_html( $msg_snippet ); ?></span>
										</td>
										<td class="mm-suc-p-td-date">
											<time datetime="<?php echo esc_attr( $msg_date ); ?>"><?php echo esc_html( $msg_date ); ?></time>
										</td>
										<td class="mm-suc-p-td-actions">
											<button type="button" class="mm-suc-p-btn mm-suc-p-btn--secondary mm-suc-p-btn--sm mm-suc-p-open-msg"
												data-mm-suc-p-open-id="<?php echo esc_attr( $msg_id ); ?>"
												aria-haspopup="dialog">
												<?php esc_html_e( 'View', 'wp-maintenance-mode-site-under-construction' ); ?>
											</button>
											<button type="button" class="mm-suc-p-btn mm-suc-p-btn--ghost mm-suc-p-btn--icon mm-suc-p-delete-msg"
												data-mm-suc-p-del-id="<?php echo esc_attr( $msg_id ); ?>"
												aria-label="<?php esc_attr_e( 'Delete message', 'wp-maintenance-mode-site-under-construction' ); ?>">
												<?php echo mm_suc_p_icon( 'trash' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?>
											</button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</section>

		<!-- 3. Data Retention & Cleanup Setting -->
		<section class="mm-suc-p-panel mm-suc-p-retention-card" aria-labelledby="mm-suc-p-retention-title">
			<div class="mm-suc-p-messages-header">
				<h2 class="mm-suc-p-messages-title" id="mm-suc-p-retention-title">
					<?php esc_html_e( 'Data Retention & Cleanup', 'wp-maintenance-mode-site-under-construction' ); ?>
				</h2>
			</div>
			<div class="mm-suc-p-panel-body" style="padding: 16px 20px;">
				<div class="mm-suc-p-fields">
					<div class="mm-suc-p-field">
						<label class="mm-suc-p-check">
							<input type="checkbox" id="mm-suc-p-delete-messages-uninstall" name="delete_messages_on_uninstall" value="1" <?php checked( ! empty( $options['delete_messages_on_uninstall'] ) ); ?> />
							<span class="mm-suc-p-check-box" aria-hidden="true"><?php echo mm_suc_p_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?></span>
							<span class="mm-suc-p-check-label"><?php esc_html_e( 'Delete messages database table when plugin is uninstalled', 'wp-maintenance-mode-site-under-construction' ); ?></span>
						</label>
						<p class="mm-suc-p-help">
							<?php esc_html_e( 'When checked, deleting this plugin from WordPress will permanently remove the messages database table and purge all saved inquiries.', 'wp-maintenance-mode-site-under-construction' ); ?>
						</p>
					</div>
				</div>
			</div>
		</section>

	</div>

	<!-- 4. Accessible Message Modal Dialog -->
	<div class="mm-suc-p-modal-backdrop" id="mm-suc-p-msg-modal" role="dialog" aria-modal="true" aria-labelledby="mm-suc-p-modal-title" hidden>
		<div class="mm-suc-p-modal-dialog">
			<header class="mm-suc-p-modal-header">
				<div class="mm-suc-p-modal-title-wrap">
					<?php echo mm_suc_p_icon( 'contact-mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?>
					<h3 class="mm-suc-p-modal-title" id="mm-suc-p-modal-title"><?php esc_html_e( 'Message Details', 'wp-maintenance-mode-site-under-construction' ); ?></h3>
				</div>
				<button type="button" class="mm-suc-p-btn mm-suc-p-btn--ghost mm-suc-p-btn--icon mm-suc-p-modal-close" id="mm-suc-p-modal-close" aria-label="<?php esc_attr_e( 'Close message dialog', 'wp-maintenance-mode-site-under-construction' ); ?>">
					<?php echo mm_suc_p_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?>
				</button>
			</header>

			<div class="mm-suc-p-modal-body">
				<div class="mm-suc-p-modal-meta">
					<div class="mm-suc-p-modal-meta-row">
						<span class="mm-suc-p-meta-label"><?php esc_html_e( 'From:', 'wp-maintenance-mode-site-under-construction' ); ?></span>
						<strong id="mm-suc-p-modal-sender-name"></strong>
						<span class="mm-suc-p-meta-email">(&lsaquo;<a id="mm-suc-p-modal-sender-email" href=""></a>&rsaquo;)</span>
					</div>
					<div class="mm-suc-p-modal-meta-row">
						<span class="mm-suc-p-meta-label"><?php esc_html_e( 'Received:', 'wp-maintenance-mode-site-under-construction' ); ?></span>
						<span id="mm-suc-p-modal-date"></span>
					</div>
				</div>

				<div class="mm-suc-p-modal-content-box">
					<div class="mm-suc-p-modal-message-text" id="mm-suc-p-modal-message-text"></div>
				</div>
			</div>

			<footer class="mm-suc-p-modal-footer">
				<a class="mm-suc-p-btn mm-suc-p-btn--primary" id="mm-suc-p-modal-reply-btn" href="" target="_blank" rel="noopener">
					<?php echo mm_suc_p_icon( 'external-link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?>
					<span><?php esc_html_e( 'Reply via email', 'wp-maintenance-mode-site-under-construction' ); ?></span>
				</a>
				<button type="button" class="mm-suc-p-btn mm-suc-p-btn--secondary mm-suc-p-btn--danger" id="mm-suc-p-modal-delete-btn">
					<?php echo mm_suc_p_icon( 'trash' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- registry-controlled inline SVG. ?>
					<span><?php esc_html_e( 'Delete message', 'wp-maintenance-mode-site-under-construction' ); ?></span>
				</button>
				<button type="button" class="mm-suc-p-btn mm-suc-p-btn--secondary" id="mm-suc-p-modal-cancel-btn">
					<?php esc_html_e( 'Close', 'wp-maintenance-mode-site-under-construction' ); ?>
				</button>
			</footer>
		</div>
	</div>

	<!-- 5. Toast Notifications -->
	<div class="mm-suc-p-toasts" id="mm-suc-p-toasts"></div>

</div>
