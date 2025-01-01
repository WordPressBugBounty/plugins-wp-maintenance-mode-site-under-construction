<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly
// Add default values on initialization
add_action('admin_init', function () {
	if (!isset($_GET['page']) || $_GET['page'] !== 'MM_And_SUC_Free_Settings') {
		return; // Exit if not on the settings page
	}

	$options = get_option('MM_And_SUC_Free_options');
	global $wp_roles;
	$wp_roles = new WP_Roles();

	// Set default roles if not set
	if (!isset($options['MM_And_SUC_Free_role']) || empty($options['MM_And_SUC_Free_role'])) {
		$options['MM_And_SUC_Free_role'] = array();
		foreach ($wp_roles->get_names() as $role_name) {
			$options['MM_And_SUC_Free_role']["'" . $role_name . "'"] = $role_name; // Initialize all roles as checked
		}
	}
	if (!isset($options['MM_And_SUC_Free_cta_bg_color']) || empty($options['MM_And_SUC_Free_cta_bg_color'])) {
		$options['MM_And_SUC_Free_cta_bg_color'] = '#007BFF'; // Default blue
	}

	// Set default text color for CTA buttons
	if (!isset($options['MM_And_SUC_Free_cta_text_color']) || empty($options['MM_And_SUC_Free_cta_text_color'])) {
		$options['MM_And_SUC_Free_cta_text_color'] = '#FFFFFF'; // Default white
	}

	if (!isset($options['MM_And_SUC_Free_colored_background_color']) || empty($options['MM_And_SUC_Free_colored_background_color'])) {
		$options['MM_And_SUC_Free_colored_background_color'] = '#ffffff'; // Default to white
	}

	// Set default value for Countdown Title if not set
	if (!isset($options['MM_And_SUC_Free_title']) || empty($options['MM_And_SUC_Free_title'])) {
		$options['MM_And_SUC_Free_title'] = __('We’ll Be Back Soon!', 'wp-maintenance-mode-site-under-construction');
	}
	// Set default value for Timer Mode if not set
	if (!isset($options['MM_And_SUC_Free_timer_mode']) || empty($options['MM_And_SUC_Free_timer_mode'])) {
		$options['MM_And_SUC_Free_timer_mode'] = 'timer_with_contact'; // Default to "Timer with Contact Form"
	}
	if (!isset($options['MM_And_SUC_Free_timer_style']) || empty($options['MM_And_SUC_Free_timer_style'])) {
		$options['MM_And_SUC_Free_timer_style'] = 'colored_background'; // Default to Colored Background
	}
	// Set default value for Countdown Description if not set
	if (!isset($options['MM_And_SUC_Free_description']) || empty($options['MM_And_SUC_Free_description'])) {
		$options['MM_And_SUC_Free_description'] = __('We’re working hard to improve your experience. Stay tuned as we count down to launch day!', 'wp-maintenance-mode-site-under-construction');
	}

	// Set default value for Email if not set
	if (!isset($options['MM_And_SUC_Free_email']) || empty($options['MM_And_SUC_Free_email'])) {
		$options['MM_And_SUC_Free_email'] = get_option('admin_email'); // Get the administrator email
	}

	// Set default value for Date if not set
	if (!isset($options['MM_And_SUC_Free_date']) || empty($options['MM_And_SUC_Free_date'])) {
		$tomorrow = date('d-m-Y 00:00:00', strtotime('+1 day')); // Get tomorrow's date in the required format
		$options['MM_And_SUC_Free_date'] = $tomorrow;
	}
	if (!isset($options['MM_And_SUC_Free_slim_title']) || empty($options['MM_And_SUC_Free_slim_title'])) {
		$options['MM_And_SUC_Free_slim_title'] = __('We’ll Be Back Soon!', 'wp-maintenance-mode-site-under-construction');
	}

	// Default Slim Mode Description
	if (!isset($options['MM_And_SUC_Free_slim_text']) || empty($options['MM_And_SUC_Free_slim_text'])) {
		$options['MM_And_SUC_Free_slim_text'] = __('We’re working hard to improve your experience.', 'wp-maintenance-mode-site-under-construction');
	}

	update_option('MM_And_SUC_Free_options', $options);
});
// Enqueue Color Picker Script and Styles
add_action('admin_enqueue_scripts', function () {
	wp_enqueue_style('wp-color-picker');
	wp_enqueue_script('wp-color-picker');
});
add_action('admin_footer', function () {
	if (isset($_GET['page']) && $_GET['page'] === 'MM_And_SUC_Free_Settings') {
?>
		<script>
			jQuery(document).ready(function($) {
				function updateFields() {
					// Timer Style Logic
					const selectedStyle = $('#MM_And_SUC_Free_timer_style').val();
					if (selectedStyle === 'colored_background') {
						$('#MM_And_SUC_Free_colored_background_color').closest('.MM_And_SUC_Free_row').show();
						$('#MM_And_SUC_Free_option_type_of_bg').closest('.MM_And_SUC_Free_row').hide();
						$('.image_upload').hide();
						$('.image_textures').hide();
					} else if (selectedStyle === 'background_image') {
						$('#MM_And_SUC_Free_colored_background_color').closest('.MM_And_SUC_Free_row').hide();
						$('#MM_And_SUC_Free_option_type_of_bg').closest('.MM_And_SUC_Free_row').show();
						updateBackgroundType();
					}

					// Background Type Logic
					function updateBackgroundType() {
						const selectedBgType = $('#MM_And_SUC_Free_option_type_of_bg').val();
						if (selectedBgType === '1') {
							$('.image_upload').show();
							$('.image_textures').hide();
						} else if (selectedBgType === '2') {
							$('.image_upload').hide();
							$('.image_textures').show();
						}
					}

					// Slim Mode Logic
					if ($('#MM_And_SUC_Free_slim_mode').is(':checked')) {
						$('#MM_And_SUC_Free_slim_title').closest('.MM_And_SUC_Free_row').show();
						$('#MM_And_SUC_Free_slim_text').closest('.MM_And_SUC_Free_row').show();
					} else {
						$('#MM_And_SUC_Free_slim_title').closest('.MM_And_SUC_Free_row').hide();
						$('#MM_And_SUC_Free_slim_text').closest('.MM_And_SUC_Free_row').hide();
					}

					// CTA Logic
					if ($('#MM_And_SUC_Free_enable_cta').is(':checked')) {
						$('.cta-input').closest('.MM_And_SUC_Free_row').show();
					} else {
						$('.cta-input').closest('.MM_And_SUC_Free_row').hide();
					}

					// Timer Mode Logic
					const selectedMode = $('#MM_And_SUC_Free_timer_mode').val();
					$('div[id^="MM_And_SUC_Free_section_"]').hide(); // Hide all sections
					if (selectedMode === 'timer_with_contact') {
						$('#MM_And_SUC_Free_title').closest('.MM_And_SUC_Free_row').show();
						$('#MM_And_SUC_Free_description').closest('.MM_And_SUC_Free_row').show();
						$('#MM_And_SUC_Free_email').closest('.MM_And_SUC_Free_row').show();
						$('div[id^="MM_And_SUC_Free_section_"]').show();
					} else if (selectedMode === 'timer_without_contact') {
						$('#MM_And_SUC_Free_title').closest('.MM_And_SUC_Free_row').show();
						$('#MM_And_SUC_Free_description').closest('.MM_And_SUC_Free_row').show();
						$('div[id^="MM_And_SUC_Free_section_"]').show();
					} else if (selectedMode === 'slim_mode') {
						$('#MM_And_SUC_Free_slim_title').closest('.MM_And_SUC_Free_row').show();
						$('#MM_And_SUC_Free_slim_text').closest('.MM_And_SUC_Free_row').show();
					}
				}

				// Initial Trigger
				updateFields();

				// Event Listeners
				$('#MM_And_SUC_Free_timer_style').on('change', updateFields);
				$('#MM_And_SUC_Free_option_type_of_bg').on('change', updateFields);
				$('#MM_And_SUC_Free_slim_mode').on('change', updateFields);
				$('#MM_And_SUC_Free_enable_cta').on('change', updateFields);
				$('#MM_And_SUC_Free_timer_mode').on('change', updateFields);
			});
			jQuery(document).ready(function($) {
				function updateFields() {
					// Get the selected mode
					const selectedMode = $('#MM_And_SUC_Free_timer_mode').val();

					if (selectedMode === 'slim_mode') {
						// Hide all sections by default
						$('.MM_And_SUC_Free_admin_card').hide();

						// Show only the "Mode Settings" and "Access Control" sections
						$('.MM_And_SUC_Free_admin_card:has(h2:contains("Mode Settings"))').show();
						$('.MM_And_SUC_Free_admin_card:has(h2:contains("Access Control"))').show();
					} else {
						// Show all sections when not in slim mode
						$('.MM_And_SUC_Free_admin_card').show();
					}
				}

				// Trigger the function on page load and when the dropdown changes
				updateFields();
				$('#MM_And_SUC_Free_timer_mode').on('change', updateFields);
			});
			jQuery(document).ready(function($) {
				function updateFields() {
					// Get the selected Timer Style
					const selectedStyle = $('#MM_And_SUC_Free_timer_style').val();

					if (selectedStyle === 'colored_background') {
						// Show Colored Background settings and hide Background Image/Texture options
						$('#MM_And_SUC_Free_colored_background_color').closest('.MM_And_SUC_Free_row').show();
						$('#MM_And_SUC_Free_option_type_of_bg').closest('.MM_And_SUC_Free_row').hide();
						$('.image_upload').hide();
						$('.image_textures').hide();
					} else if (selectedStyle === 'background_image') {
						// Hide Colored Background settings and show Background Type options
						$('#MM_And_SUC_Free_colored_background_color').closest('.MM_And_SUC_Free_row').hide();
						$('#MM_And_SUC_Free_option_type_of_bg').closest('.MM_And_SUC_Free_row').show();
						updateBackgroundType(); // Call to handle background type visibility
					}
				}

				function updateBackgroundType() {
					// Get the selected Background Type
					const selectedBgType = $('#MM_And_SUC_Free_option_type_of_bg').val();

					if (selectedBgType === '1') {
						// Show Image Upload and hide Textures
						$('.image_upload').show();
						$('.image_textures').hide();
					} else if (selectedBgType === '2') {
						// Show Textures and hide Image Upload
						$('.image_upload').hide();
						$('.image_textures').show();
					}
				}

				// Trigger the functions on page load
				$(window).on('load', function() {
					updateFields(); // Ensure fields are updated based on saved values
					updateBackgroundType(); // Ensure background type logic is applied initially
				});

				// Event Listeners for Dropdown Changes
				$('#MM_And_SUC_Free_timer_style').on('change', updateFields);
				$('#MM_And_SUC_Free_option_type_of_bg').on('change', updateBackgroundType);
			});
		</script>
		<?php
	}
});


add_action('admin_enqueue_scripts', function ($hook_suffix) {
	if (isset($_GET['page']) && $_GET['page'] === 'MM_And_SUC_Free_Settings') {
		wp_enqueue_style('wp-color-picker'); // Enqueue WordPress color picker styles
		wp_enqueue_script('wp-color-picker'); // Enqueue WordPress color picker script
	}
});

add_action('admin_bar_menu', function ($wp_admin_bar) {
	$options = get_option('MM_And_SUC_Free_options', []);
	$status = isset($options['MM_And_SUC_Free_status']) && $options['MM_And_SUC_Free_status'] === '1';
	$status_text = $status ? __('ON', 'wp-maintenance-mode-site-under-construction') : __('OFF', 'wp-maintenance-mode-site-under-construction');
	$button_text = $status ? __('Deactivate Maintenance Mode', 'wp-maintenance-mode-site-under-construction') : __('Activate Maintenance Mode', 'wp-maintenance-mode-site-under-construction');
	$icon = $status ? '&#x2714;' : '&#x2716;';
	$status_color = $status ? '#28a745' : '#dc3545'; // Green for ON, Red for OFF

	// Add the top-level menu
	$wp_admin_bar->add_node([
		'id'    => 'maintenance_mode',
		'title' => '<div style="display: flex; align-items: center; gap: 8px; padding: 0px 8px; background-color: ' . $status_color . '; color: white; border-radius: 3px;">' .
			'<span>' . $icon . '</span>' .
			'<span>' . __('Maintenance Mode', 'wp-maintenance-mode-site-under-construction') . '</span>' .
			'</div>',
		'href'  => admin_url('options-general.php?page=MM_And_SUC_Free_Settings'),
	]);

	// Add the "Status" submenu as a toggle button
	$wp_admin_bar->add_node([
		'id'     => 'maintenance_mode_status',
		'parent' => 'maintenance_mode',
		'title'  => '<button id="toggle-maintenance-mode" style="background: none; border: none; color: inherit; font: inherit; cursor: pointer;">' .
			$button_text . '</button>',
		'href'   => '#',
	]);

	// Add the "Settings" submenu
	$wp_admin_bar->add_node([
		'id'     => 'maintenance_mode_settings',
		'parent' => 'maintenance_mode',
		'title'  => __('Settings', 'wp-maintenance-mode-site-under-construction'),
		'href'   => admin_url('options-general.php?page=MM_And_SUC_Free_Settings'),
	]);
}, 100);

// AJAX Handler for Frontend and Admin
add_action('wp_ajax_toggle_maintenance_mode', 'toggle_maintenance_mode');
add_action('wp_ajax_nopriv_toggle_maintenance_mode', 'toggle_maintenance_mode');

function toggle_maintenance_mode()
{
	if (!is_admin() || current_user_can('manage_options')) {
		$options = get_option('MM_And_SUC_Free_options', []);
		$current_status = isset($options['MM_And_SUC_Free_status']) && $options['MM_And_SUC_Free_status'] === '1';
		$new_status = $current_status ? '0' : '1';

		$options['MM_And_SUC_Free_status'] = $new_status;
		update_option('MM_And_SUC_Free_options', $options);

		wp_send_json_success([
			'status' => $new_status,
			'message' => $new_status === '1' ? __('Maintenance Mode is now ON', 'wp-maintenance-mode-site-under-construction') : __('Maintenance Mode is now OFF', 'wp-maintenance-mode-site-under-construction'),
		]);
	} else {
		wp_send_json_error(__('Unauthorized access', 'wp-maintenance-mode-site-under-construction'));
	}
}




if (! class_exists('MM_And_SUC_Free_admin_setting')) {
	class MM_And_SUC_Free_admin_setting
	{
		public $before_section;
		public $before_section_right;
		public $after_section;
		public $after_section_right;
		public function __construct()
		{

			add_action('admin_menu', array($this, 'MM_And_SUC_Free_options_page'));
			add_action('admin_init', array($this, 'MM_And_SUC_Free_settings_init'));
			add_action('admin_footer', array($this, 'add_script'));
			// Inline JavaScript for Frontend and Backend
			add_action('admin_footer', array($this, 'maintenance_mode_inline_script'));
			add_action('wp_footer', array($this, 'maintenance_mode_inline_script'));


			$this->before_section = '<div class="col-md-12 col-sm-12 MM_And_SUC_Free_admin_card"><div class="card">';
			$this->before_section_right = '<div class="col-md-12 col-sm-12 MM_And_SUC_Free_admin_card"><div class="card">';
			$this->after_section = '</div></div>';
			$this->after_section_right = '</div></div>';
		}


		public function maintenance_mode_inline_script()
		{
		?>
			<script>
				jQuery(document).ready(function($) {
					$('#toggle-maintenance-mode').on('click', function(e) {
						e.preventDefault();

						$.ajax({
							url: '<?php echo admin_url('admin-ajax.php'); ?>',
							method: 'POST',
							data: {
								action: 'toggle_maintenance_mode',
								_ajax_nonce: '<?php echo wp_create_nonce('toggle-maintenance-mode'); ?>',
							},
							success: function(response) {
								if (response.success) {
									// Display the message
									const message = $('<div>', {
										text: response.data.message,
										css: {
											position: 'fixed',
											top: '50%',
											left: '50%',
											transform: 'translate(-50%, -50%)',
											background: '#323232',
											color: '#fff',
											padding: '20px 40px',
											fontSize: '16px',
											fontWeight: 'bold',
											textAlign: 'center',
											borderRadius: '8px',
											boxShadow: '0px 4px 6px rgba(0,0,0,0.3)',
											zIndex: 9999,
										},
									}).appendTo('body');

									setTimeout(function() {
										message.fadeOut(500, function() {
											$(this).remove();
											location.reload(); // Reload the page after the message fades out
										});
									}, 3000);
								} else {
									alert(response.data.message || 'An error occurred.');
								}
							},
							error: function() {
								alert('An unexpected error occurred.');
							},
						});
					});
				});
			</script>
		<?php
		}

		public function MM_And_SUC_Free_field_type_color_picker2($args)
		{
			$options = get_option('MM_And_SUC_Free_options');
			$value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '#ffffff'; // Default color is white
		?>
			<input type="text"
				id="<?php echo esc_attr($args['label_for']); ?>"
				name="MM_And_SUC_Free_options[<?php echo esc_attr($args['label_for']); ?>]"
				value="<?php echo esc_attr($value); ?>"
				class="color-field" />
			<p class="description"><?php echo esc_html($args['description']); ?></p>
			<script>
				jQuery(document).ready(function($) {
					$('.color-field').wpColorPicker(); // Initialize WordPress Color Picker
				});
			</script>
		<?php
		}


		public function MM_And_SUC_Free_settings_init()
		{
			register_setting('MM_And_SUC_Free_Settings', 'MM_And_SUC_Free_options');

			add_settings_section(
				'MM_And_SUC_Free_section_Status_developers',
				__('Mode Settings:', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_section_developers_dev_cb'),
				'MM_And_SUC_Free_Settings',
				array(
					'before_section' => $this->before_section,
					'after_section' => $this->after_section,
					'section_class' => 'section_class'
				)
			);
			// Add Timer Type Section
			add_settings_section(
				'MM_And_SUC_Free_section_timer_type',
				__('Countdown Timer Style', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_section_developers_dev_cb'),
				'MM_And_SUC_Free_Settings',
				array(
					'before_section' => $this->before_section_right,
					'after_section' => $this->after_section_right,
					'section_class' => 'section_class'
				)
			);

			// Add Timer Style Radio Buttons
			add_settings_field(
				'MM_And_SUC_Free_timer_style',
				__('Timer Style', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_dropdown2'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_timer_type',
				[
					'label_for' => 'MM_And_SUC_Free_timer_style',
					'class' => 'MM_And_SUC_Free_row',
					'description' => 'Choose the timer style you want for the maintenance page.',
					'options' => [
						'colored_background' => __('Colored Background (Default)', 'wp-maintenance-mode-site-under-construction'),
						'background_image' => __('Background Image', 'wp-maintenance-mode-site-under-construction'),
					]
				]
			);
			// Add Background Type Field (Dropdown)
			add_settings_field(
				'MM_And_SUC_Free_select_type_of_bg',
				__('Background Type', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_select_type_of_bg'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_timer_type', // Update the section to Timer Mode
				[
					'label_for' => 'MM_And_SUC_Free_option_type_of_bg',
					'class' => 'MM_And_SUC_Free_row',
					'id' => 'showcase-taxonomy-select-id',
					'MM_And_SUC_Free_custom_data' => 'custom',
				]
			);

			// Add Background Image Upload Field
			add_settings_field(
				'MM_And_SUC_Free_image',
				__('Background Image', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_image'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_timer_type', // Update the section to Timer Mode
				[
					'label_for' => 'MM_And_SUC_Free_image',
					'class' => 'MM_And_SUC_Free_row image_upload',
					'id' => 'showcase-taxonomy-image-id',
					'MM_And_SUC_Free_custom_data' => 'custom',
				]
			);

			// Add Background Textures Field
			add_settings_field(
				'MM_And_SUC_Free_textures',
				__('Background Textures', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_textures'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_timer_type', // Update the section to Timer Mode
				[
					'label_for' => 'MM_And_SUC_Free_textures',
					'class' => 'MM_And_SUC_Free_row image_textures',
					'id' => 'showcase-taxonomy-textures-id',
					'MM_And_SUC_Free_custom_data' => 'custom',
				]
			);

			add_settings_section(
				'MM_And_SUC_Free_section_role_developers',
				__('Access Control:', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_section_developers_dev_cb'),
				'MM_And_SUC_Free_Settings',
				array(
					'before_section' => $this->before_section_right,
					'after_section' => $this->after_section_right,
					'section_class' => 'section_class'
				)
			);
			add_settings_section(
				'MM_And_SUC_Free_section_massage_developers',
				__('Custom Maintenance Message:', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_section_developers_dev_cb'),
				'MM_And_SUC_Free_Settings',
				array(
					'before_section' => $this->before_section,
					'after_section' => $this->after_section,
					'section_class' => 'section_class'
				)
			);
			add_settings_section(
				'MM_And_SUC_Free_section_CTA_buttons',
				__('Call-to-Action Buttons:', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_section_developers_dev_cb'),
				'MM_And_SUC_Free_Settings',
				array(
					'before_section' => $this->before_section,
					'after_section' => $this->after_section,
					'section_class' => 'section_class'
				)
			);

			add_settings_field(
				'MM_And_SUC_Free_enable_cta',
				__('Enable Call-to-Action Buttons', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_checkbox'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_CTA_buttons',
				[
					'label_for' => 'MM_And_SUC_Free_enable_cta',
					'class' => 'MM_And_SUC_Free_row',
					'MM_And_SUC_Free_custom_data' => 'custom',

					'description' => __('Enable or disable Call-to-Action buttons.', 'wp-maintenance-mode-site-under-construction'),
				]
			);
			add_settings_field(
				'MM_And_SUC_Free_cta_bg_color',
				__('CTA Buttons Background Color', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_color_picker2'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_CTA_buttons',
				[
					'label_for' => 'MM_And_SUC_Free_cta_bg_color',
					'class' => 'MM_And_SUC_Free_row cta-input',
					'description' => __('Select the background color for Call-to-Action buttons.', 'wp-maintenance-mode-site-under-construction'),
					'MM_And_SUC_Free_custom_data' => 'custom',
				]
			);

			add_settings_field(
				'MM_And_SUC_Free_cta_text_color',
				__('CTA Buttons Text Color', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_color_picker2'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_CTA_buttons',
				[
					'label_for' => 'MM_And_SUC_Free_cta_text_color',
					'class' => 'MM_And_SUC_Free_row cta-input',
					'description' => __('Select the text color for Call-to-Action buttons.', 'wp-maintenance-mode-site-under-construction'),
					'MM_And_SUC_Free_custom_data' => 'custom',
				]
			);

			for ($i = 1; $i <= 4; $i++) {
				// Add Slim Mode Title Field

				// Add Label Field
				add_settings_field(
					"MM_And_SUC_Free_cta_label_$i",
					__("Button $i Label", 'wp-maintenance-mode-site-under-construction'),
					array($this, 'MM_And_SUC_Free_field_type_text'),
					'MM_And_SUC_Free_Settings',
					'MM_And_SUC_Free_section_CTA_buttons',
					[
						'label_for' => "MM_And_SUC_Free_cta_label_$i",
						'class' => 'MM_And_SUC_Free_row cta-input',
						'description' => __("Enter the label for Button $i.", 'wp-maintenance-mode-site-under-construction'),
						'MM_And_SUC_Free_custom_data' => 'custom',

					]
				);

				// Add URL Field
				add_settings_field(
					"MM_And_SUC_Free_cta_url_$i",
					__("Button $i URL", 'wp-maintenance-mode-site-under-construction'),
					array($this, 'MM_And_SUC_Free_field_type_text'),
					'MM_And_SUC_Free_Settings',
					'MM_And_SUC_Free_section_CTA_buttons',
					[
						'label_for' => "MM_And_SUC_Free_cta_url_$i",
						'class' => 'MM_And_SUC_Free_row cta-input',
						'description' => __("Enter the URL for Button $i.", 'wp-maintenance-mode-site-under-construction'),
						'MM_And_SUC_Free_custom_data' => 'custom',

					]
				);
			}



			add_settings_field(
				'MM_And_SUC_Free_status',
				__('Status', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_checkbox'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_Status_developers',
				[
					'label_for' => 'MM_And_SUC_Free_status',
					'class' => 'MM_And_SUC_Free_row',
					'MM_And_SUC_Free_custom_data' => 'custom',
					'description' => 'Enable/Disable plugin functionality',
					'wp-maintenance-mode-site-under-construction',
				]
			);
			// Add Timer Mode Dropdown
			add_settings_field(
				'MM_And_SUC_Free_timer_mode',
				__('Mode', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_dropdown'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_Status_developers',
				[
					'label_for' => 'MM_And_SUC_Free_timer_mode',
					'class' => 'MM_And_SUC_Free_row',
					'description' => 'Select the desired mode for the maintenance page.',
				]
			);


			// Add Slim Mode Title Field
			add_settings_field(
				'MM_And_SUC_Free_slim_title',
				__('Slim Mode Title', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_text'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_Status_developers',
				[
					'label_for' => 'MM_And_SUC_Free_slim_title',
					'class' => 'MM_And_SUC_Free_row',
					'description' => 'Title displayed when Slim Mode is active. This field is only visible when Slim Mode is selected in the Timer Mode dropdown.',
					'MM_And_SUC_Free_custom_data' => '', // Add this key with a default value
				]
			);


			// Add Slim Mode Description Field
			add_settings_field(
				'MM_And_SUC_Free_slim_text',
				__('Slim Mode Description', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_textarea'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_Status_developers',
				[
					'label_for' => 'MM_And_SUC_Free_slim_text',
					'class' => 'MM_And_SUC_Free_row',
					'description' => 'Description displayed when Slim Mode is active. This field is only visible when Slim Mode is selected in the Timer Mode dropdown.',
					'MM_And_SUC_Free_custom_data' => '', // Add this key with a default value
				]
			);
			add_settings_field(
				'MM_And_SUC_Free_colored_background_color',
				__('Background Color', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_color_picker'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_timer_type', // Add under the Timer Type section
				[
					'label_for' => 'MM_And_SUC_Free_colored_background_color',
					'class' => 'MM_And_SUC_Free_row',
					'description' => 'Choose a background color for the colored style.',
				]
			);

			add_settings_field(
				'MM_And_SUC_Free_title',
				__('Countdown Title', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_text'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_massage_developers',
				[
					'label_for' => 'MM_And_SUC_Free_title',
					'class' => 'MM_And_SUC_Free_row',
					'MM_And_SUC_Free_custom_data' => 'custom',
				]
			);
			add_settings_field(
				'MM_And_SUC_Free_description',
				__('Countdown Description', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_textarea'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_massage_developers',
				[
					'label_for' => 'MM_And_SUC_Free_description',
					'class' => 'MM_And_SUC_Free_row',
					'MM_And_SUC_Free_custom_data' => 'custom',
				]
			);
			add_settings_field(
				'MM_And_SUC_Free_email',
				__('Email', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_email'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_massage_developers',
				[
					'label_for' => 'MM_And_SUC_Free_email',
					'class' => 'MM_And_SUC_Free_row',
					'MM_And_SUC_Free_custom_data' => 'custom',
				]
			);
			add_settings_field(
				'MM_And_SUC_Free_date',
				__('Countdown End Time', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_date_time'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_timer_type', // Update the section to Timer Type
				[
					'label_for' => 'MM_And_SUC_Free_date',
					'class' => 'MM_And_SUC_Free_row',
				]
			);

			add_settings_field(
				'MM_And_SUC_Free_role',
				__('Apply on', 'wp-maintenance-mode-site-under-construction'),
				array($this, 'MM_And_SUC_Free_field_type_roles'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_role_developers',
				[
					'label_for' => 'MM_And_SUC_Free_role',
					'class' => 'MM_And_SUC_Free_row',
					'MM_And_SUC_Free_custom_data' => array(),
				]
			);
		}
		public function MM_And_SUC_Free_field_type_color_picker($args)
		{
			$options = get_option('MM_And_SUC_Free_options');
			$value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '#ffffff'; // Default color is white
		?>
			<input type="text"
				id="<?php echo esc_attr($args['label_for']); ?>"
				name="MM_And_SUC_Free_options[<?php echo esc_attr($args['label_for']); ?>]"
				value="<?php echo esc_attr($value); ?>"
				class="color-field" />
			<p class="description"><?php echo esc_html($args['description']); ?></p>
			<script>
				jQuery(document).ready(function($) {
					$('.color-field').wpColorPicker(); // Initialize WordPress Color Picker
				});
			</script>
		<?php
		}

		public function MM_And_SUC_Free_field_type_dropdown2($args)
		{
			$options = get_option('MM_And_SUC_Free_options');
			$value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : 'colored_background'; // Default value

			// Define the dropdown options
			$dropdown_options = [
				'colored_background' => __('Colored Background', 'wp-maintenance-mode-site-under-construction'),
				'background_image' => __('Background Image', 'wp-maintenance-mode-site-under-construction'),
			];
		?>
			<select
				class="form-control"
				name="MM_And_SUC_Free_options[<?php echo esc_attr($args['label_for']); ?>]"
				id="<?php echo esc_attr($args['label_for']); ?>">
				<?php foreach ($dropdown_options as $key => $label): ?>
					<option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
						<?php echo esc_html($label); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description">
				<?php echo esc_html($args['description']); ?>
			</p>
		<?php
		}


		public function MM_And_SUC_Free_field_type_dropdown($args)
		{
			$options = get_option('MM_And_SUC_Free_options');
			$value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : 'timer_with_contact'; // Default value

			// Define the dropdown options
			$dropdown_options = [
				'timer_with_contact' => __('Timer with Contact Form', 'wp-maintenance-mode-site-under-construction'),
				'timer_without_contact' => __('Timer without Contact Form', 'wp-maintenance-mode-site-under-construction'),
				'slim_mode' => __('Slim Mode', 'wp-maintenance-mode-site-under-construction'),
			];
		?>
			<select
				class="form-control"
				name="MM_And_SUC_Free_options[<?php echo esc_attr($args['label_for']); ?>]"
				id="<?php echo esc_attr($args['label_for']); ?>">
				<?php foreach ($dropdown_options as $key => $label): ?>
					<option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
						<?php echo esc_html($label); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description">
				<?php echo esc_html($args['description']); ?>
			</p>
		<?php
		}




		public function section_header()
		{
			return '<div class="col-md-6 col-sm-12">';
		}
		public function section_footer()
		{
			return '</div>';
		}
		public function MM_And_SUC_Free_field_type_textures($args)
		{
			$options = get_option('MM_And_SUC_Free_options');
			$directory = MM_And_SUC_Free_PLUGIN_DIR . '/textures/';
			$directory_seperator = "/";
			$allimages = MM_And_SUC_Free_getAllImgs(MM_And_SUC_Free_getAllDirs($directory, $directory_seperator));
		?>
			<div id="conte_textures">
				<?php
				foreach ($allimages as $row) {
					$text_path = pathinfo($row);
				?>
					<label class="tooltip">
						<input type="radio" name="MM_And_SUC_Free_options[<?php echo esc_attr($args['label_for']); ?>]" <?php isset($options[$args['label_for']]) ? checked($options[$args['label_for']], $row) : ''; ?> value="<?php echo esc_attr($row); ?>">
						<img src="<?php esc_attr_e(MM_And_SUC_Free_PLUGIN_URL . 'textures/' . $row); ?>">
						<span class="tooltiptext tooltip-top">
							<?php
							$link = MM_And_SUC_Free_PLUGIN_DIR . 'textures/' . $text_path['dirname'] . '/' . $text_path['filename'] . '.txt';
							if (file_exists($link)) {
								$myfile = fopen($link, "r") or die("Unable to open file!");
								esc_html_e(fread($myfile, filesize($link)));
								fclose($myfile);
							}
							?>
					</label>
				<?php
				}
				?>
			</div>


		<?php
		}
		public function MM_And_SUC_Free_select_type_of_bg($args)
		{
			$options = get_option('MM_And_SUC_Free_options');

		?>
			<select class="form-control" name="MM_And_SUC_Free_options[<?php echo esc_attr($args['label_for']); ?>]" id="<?php echo esc_attr($args['label_for']); ?>">
				<option value="1" <?php isset($options[$args['label_for']]) ? selected($options[$args['label_for']], 1) : ''; ?>>image</option>
				<option value="2" <?php isset($options[$args['label_for']]) ? selected($options[$args['label_for']], 2) : ''; ?>>textures</option>
			</select>

		<?php
		}
		public function MM_And_SUC_Free_section_developers_cb($args)
		{
			/*
			<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'sssssss', 'wp-maintenance-mode-site-under-construction' ); ?></p>
			*/
		}
		public function MM_And_SUC_Free_section_developers_dev_cb($args)
		{
			/*
            <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'sssssss', 'wp-maintenance-mode-site-under-construction' ); ?></p>
            */
		}
		public function MM_And_SUC_Free_field_type_image($args)
		{
			$options = get_option('MM_And_SUC_Free_options');

		?>
			<?php $image_id = isset($options[$args['label_for']]) ? $options[$args['label_for']] : ''; ?>
			<input type="hidden" id="<?php echo esc_attr($args['id']); ?>" name="MM_And_SUC_Free_options[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo esc_attr($image_id); ?>">
			<div id="category-image-wrapper">
				<?php if ($image_id != '') { ?>
					<?php
					echo wp_get_attachment_image($image_id, 'full', false, array("class" => "custom_media_image"));
					?>
				<?php } ?>
			</div>
			<p>
				<input type="button" style="opacity: 1 !important;" class="button button-secondary showcase_tax_media_button" id="showcase_tax_media_button" name="showcase_tax_media_button" value="<?php echo esc_attr__('Add Image', 'codepressfaq'); ?>" />
				<input type="button" style="opacity: 1 !important;" class="button button-secondary showcase_tax_media_remove" id="showcase_tax_media_remove" name="showcase_tax_media_remove" value="<?php echo esc_attr__('Remove Image', 'codepressfaq'); ?>" />
			</p>

			<p class="description"><?php esc_html_e('Try to choose a large image for a good view', 'wp-maintenance-mode-site-under-construction'); ?></p>

		<?php
		}
		public function MM_And_SUC_Free_field_type_date_time($args)
		{
			$options = get_option('MM_And_SUC_Free_options');

		?>
			<input type="text" readonly style="opacity: 1 !important;" name="MM_And_SUC_Free_options[<?php echo esc_attr($args['label_for']); ?>]" class="datetime-field form-control" data-datetime="{'position':'bottom','dateFormat':'dd-mm-YYYY '}" id="<?php echo esc_attr($args['label_for']); ?>" data-tail-datetime="tail-11" data-value="<?php echo isset($options[$args['label_for']]) ? esc_attr(strtotime($options[$args['label_for']])) : ''; ?>" value="<?php echo isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : ''; ?>">
			<p class="description">
				<?php esc_html_e('Example: 22-09-2020  18:07:00', 'wp-maintenance-mode-site-under-construction'); ?>
			</p>

			<?php
		}
		public function MM_And_SUC_Free_field_type_roles($args)
		{
			// Fetch the saved options
			$options = get_option('MM_And_SUC_Free_options');
			// Retrieve the saved value or default to an empty array
			$value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : array();
			global $wp_roles;
			$wp_roles = new WP_Roles(); // Get all roles

			// If no values are saved, initialize all roles as checked
			if (empty($value)) {
				foreach ($wp_roles->get_names() as $name) {
					$value["'" . $name . "'"] = $name;
				}
			}

			echo '<div class="row">';
			foreach ($wp_roles->get_names() as $name) {
				// Set checkbox as checked if the role is in the saved values
				$is_checked = isset($value["'" . $name . "'"]) ? 'checked' : '';
			?>
				<div class="wp_roles_list col-lg-12 col-xl-6" style="margin-bottom: 7px;">
					<label class="switch">
						<input type="checkbox"
							id="<?php echo esc_attr($name); ?>"
							name="MM_And_SUC_Free_options[<?php echo esc_attr($args['label_for']); ?>]['<?php echo esc_attr($name); ?>']"
							value="<?php echo esc_attr($name); ?>"
							<?php echo $is_checked; ?>>
						<span class="slider round"></span>
					</label>
					<?php esc_html_e($name); ?>
				</div>
			<?php
			}
			echo '</div>';
			?>
		<?php
		}

		public function MM_And_SUC_Free_field_type_text($args)
		{
			$options = get_option('MM_And_SUC_Free_options');

		?>
			<input type="text" class="form-control" style="opacity: 1 !important;" value="<?php echo isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : ''; ?>" id="<?php echo esc_attr($args['label_for']); ?>" data-custom="<?php echo esc_attr($args['MM_And_SUC_Free_custom_data']); ?>" name="MM_And_SUC_Free_options[<?php echo esc_attr($args['label_for']); ?>]">
			<p class="description"></p>

		<?php
		}
		public function MM_And_SUC_Free_field_type_email($args)
		{
			$options = get_option('MM_And_SUC_Free_options');

		?>
			<input type="email" style="opacity: 1 !important;" class="form-control" value="<?php echo isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : ''; ?>" id="<?php echo esc_attr($args['label_for']); ?>" data-custom="<?php echo esc_attr($args['MM_And_SUC_Free_custom_data']); ?>" name="MM_And_SUC_Free_options[<?php echo esc_attr($args['label_for']); ?>]">
			<p class="description">
				<?php esc_html_e('Emails wil be sent to site admin if you leave this field blank', 'wp-maintenance-mode-site-under-construction'); ?>
			</p>

		<?php
		}
		public function MM_And_SUC_Free_field_type_textarea($args)
		{
			$options = get_option('MM_And_SUC_Free_options');

		?>
			<textarea id="<?php echo esc_attr($args['label_for']); ?>" class="form-control" data-custom="<?php echo esc_attr($args['MM_And_SUC_Free_custom_data']); ?>" name="MM_And_SUC_Free_options[<?php echo esc_attr($args['label_for']); ?>]" class="mceEditor" rows="3" style="width:100%;" autocomplete="off"><?php echo isset($options[$args['label_for']]) ? esc_attr($options[$args['label_for']]) : ''; ?></textarea>
			<p class="description"></p>

		<?php
		}
		public function MM_And_SUC_Free_field_type_checkbox($args)
		{
			$options = get_option('MM_And_SUC_Free_options');

		?>
			<div>
				<span class="on_off off"><?php esc_html_e('OFF'); ?></span>
				<label class="switch">
					<input type="checkbox" id="<?php esc_attr_e($args['label_for']); ?>" name="MM_And_SUC_Free_options[<?php echo esc_attr($args['label_for']); ?>]" value="1" <?php echo isset($options[$args['label_for']]) ? (checked($options[$args['label_for']], '1', false)) : (''); ?>>
					<span class="slider round"></span>
				</label>
				<span class="on_off on"><?php esc_html_e('ON'); ?></span>
				<p class="description">
					<?php esc_html_e($args['description']); ?>
				</p>
			</div>

		<?php
		}


		// Callback for the toggle button page
		function maintenance_mode_toggle_callback()
		{
			// Check if the mode is enabled
			$maintenance_mode = get_option('maintenance_mode_enabled', false);
			$toggle_status = $maintenance_mode ? 'ON' : 'OFF';
			$toggle_color = $maintenance_mode ? 'green' : 'red';

			// Toggle form
		?>
			<div class="wrap">
				<h1><?php _e('Toggle Maintenance Mode', 'your-textdomain'); ?></h1>
				<form method="post">
					<?php wp_nonce_field('toggle_maintenance_mode'); ?>
					<p>
						<strong>Status:</strong>
						<span style="color: <?php echo $toggle_color; ?>; font-weight: bold;"><?php echo $toggle_status; ?></span>
					</p>
					<button name="toggle_maintenance_mode" class="button button-primary" type="submit">
						<?php echo $maintenance_mode ? __('Turn OFF', 'your-textdomain') : __('Turn ON', 'your-textdomain'); ?>
					</button>
				</form>
			</div>
			<?php

			// Handle toggle form submission
			if (isset($_POST['toggle_maintenance_mode']) && check_admin_referer('toggle_maintenance_mode')) {
				$new_status = !$maintenance_mode;
				update_option('maintenance_mode_enabled', $new_status);
				wp_redirect(menu_page_url('maintenance-mode-toggle', false));
				exit;
			}
		}

		// Callback for the settings page
		public function maintenance_mode_settings_callback()
		{
			?>
			<div class="wrap">
				<h1><?php _e('Maintenance Mode Settings', 'your-textdomain'); ?></h1>
				<p><?php _e('Settings for the maintenance mode plugin can be configured here.', 'your-textdomain'); ?></p>
				<a href="<?php echo admin_url('options-general.php?page=MM_And_SUC_Free_Settings'); ?>" class="button button-primary">
					<?php _e('Go to Settings', 'your-textdomain'); ?>
				</a>
			</div>
		<?php
		}


		public function MM_And_SUC_Free_options_page()
		{
			add_menu_page(
				'WP Maintenance Mode & Site Under Construction Settings:',
				'Maintenance Mode',
				'manage_options',
				'MM_And_SUC_Free_Settings',
				array($this, 'MM_And_SUC_Free_options_page_html'),
				'dashicons-admin-tools'
			);
		}


		public function MM_And_SUC_Free_options_page_html()
		{

			if (! current_user_can('manage_options')) {
				return;
			}

			if (isset($_GET['settings-updated'])) {
				add_settings_error('MM_And_SUC_Free_messages', 'MM_And_SUC_Free_message', __('Settings Saved', 'wp-maintenance-mode-site-under-construction'), 'updated');
			}

			$network_dir_append = "";

			if (is_multisite()) $network_dir_append = "network/";

			$admin_url = admin_url($network_dir_append . 'plugin-install.php');

		?>
			<script>
				document.addEventListener("DOMContentLoaded", function() {
					// Select the <ul> element using its class
					const ulElement = document.querySelector('.other_plugins_rotator_ul');

					function showRandomElements() {
						// Hide all list items
						const listItems = ulElement.querySelectorAll('li');
						listItems.forEach((li) => {
							li.style.display = 'none';
						});

						// Generate three random indices
						const numItems = listItems.length;
						const randomIndices = [];
						while (randomIndices.length < 5) {
							const randomIndex = Math.floor(Math.random() * numItems);
							if (!randomIndices.includes(randomIndex)) {
								randomIndices.push(randomIndex);
							}
						}

						// Show the randomly selected list items
						randomIndices.forEach((index) => {
							listItems[index].style.display = 'block';
						});
					}

					// Show initial random elements
					showRandomElements();

					// Set interval to change elements every 15 seconds
					setInterval(showRandomElements, 15000);
				});
			</script>
			<div class="col-lg-12 col-xl-12 col-xxl-12">
				<h3><?php _e('WP Maintenance Mode & Site Under Construction', 'wp-maintenance-mode-site-under-construction'); ?></h3>
				<div id="">
					<?php settings_errors('MM_And_SUC_Free_messages'); ?>
					<div id="dashboard-widgets" class="metabox-holder">
						<div id="" class="">
							<div id="side-sortables" class="meta-box-sortables ui-sortable">
								<div id="dashboard_quick_press">
									<h2 class="hndle ui-sortable-handle">
										<span>
											<span class="hide-if-no-js"><?php esc_html_e(get_admin_page_title()); ?></span>
											<span class="hide-if-js"><?php esc_html_e(get_admin_page_title()); ?></span>
										</span>
									</h2>
									<div class="inside">
										<form action="options.php" method="post">
											<div class="input-text-wrap row" id="title-wrap">
												<div class="col-md-8 col-sm-12">
													<?php
													settings_fields('MM_And_SUC_Free_Settings');
													do_settings_sections('MM_And_SUC_Free_Settings');
													?>
												</div>
												<div class="col-md-4 col-sm-12  ">
													<div class="col-md-12 col-sm-12  MM_And_SUC_Free_admin_card">
														<div class="card">
															<h2 style="background-color: hsl(275.56deg 49.69% 31.96%); color: white; margin-bottom: 20px">Other products</h2>

															<ul class="other_plugins_rotator_ul">
																<li>
																	<div class="plugin-info-container">
																		<h3><a target="blank" href="<?php echo $admin_url; ?>?s=404 Image Redirection Replace Broken Images&tab=search&type=term">404 Image Redirection (Replace Broken Images)</a></h3>
																		<div class="wporg-ratings" title="5 out of 5 stars" style="color:#ffb900;"></div>
																		<p class="active_installs">Active Installs: 600+</p>
																	</div>
																</li>
																<li>
																	<div class="plugin-info-container">
																		<h3><a target="blank" href="<?php echo $admin_url; ?>?s=Advanced FAQ QA Creator by Category&tab=search&type=term">Advanced FAQ QA Creator by Category</a></h3>
																		<p class="active_installs">Active Installs: Less than 10</p>
																	</div>
																</li>
																<li>
																	<div class="plugin-info-container">
																		<h3><a target="blank" href="<?php echo $admin_url; ?>?s=All 404 Redirect to Homepage&tab=search&type=term">All 404 Redirect to Homepage</a></h3>
																		<div class="wporg-ratings" title="4 out of 5 stars" style="color:#ffb900;"></div>
																		<p class="active_installs">Active Installs: 200,000+</p>
																	</div>
																</li>
																<li>
																	<div class="plugin-info-container">
																		<h3><a target="blank" href="<?php echo $admin_url; ?>?s=Captchinoo, admin login page protection with Google recaptcha&tab=search&type=term">Captchinoo, admin login page protection with Google recaptcha</a></h3>
																		<div class="wporg-ratings" title="5 out of 5 stars" style="color:#ffb900;"></div>
																		<p class="active_installs">Active Installs: 300+</p>
																	</div>
																</li>
																<li>
																	<div class="plugin-info-container">
																		<h3><a target="blank" href="<?php echo $admin_url; ?>?s=Easy Popup Maker&tab=search&type=term">Easy Popup Maker</a></h3>
																		<p class="active_installs">Active Installs: 10+</p>
																	</div>
																</li>
																<li>
																	<div class="plugin-info-container">
																		<h3><a target="blank" href="<?php echo $admin_url; ?>?s=Limit Login Attempts Spam Protection&tab=search&type=term">Limit Login Attempts (Spam Protection)</a></h3>
																		<div class="wporg-ratings" title="3 out of 5 stars" style="color:#ffb900;"></div>
																		<p class="active_installs">Active Installs: 200+</p>
																	</div>
																</li>
																<li>
																	<div class="plugin-info-container">
																		<h3><a target="blank" href="<?php echo $admin_url; ?>?s=Login as User or Customer&tab=search&type=term">Login as User or Customer</a></h3>
																		<div class="wporg-ratings" title="3 out of 5 stars" style="color:#ffb900;"></div>
																		<p class="active_installs">Active Installs: 400+</p>
																	</div>
																</li>
																<li>
																	<div class="plugin-info-container">
																		<h3><a target="blank" href="<?php echo $admin_url; ?>?s=SEO Redirection Plugin - 301 Redirect Manager&tab=search&type=term">SEO Redirection Plugin - 301 Redirect Manager</a></h3>
																		<div class="wporg-ratings" title="4 out of 5 stars" style="color:#ffb900;"></div>
																		<p class="active_installs">Active Installs: 20,000+</p>
																	</div>
																</li>
																<li>
																	<div class="plugin-info-container">
																		<h3><a target="blank" href="<?php echo $admin_url; ?>?s=Visitor Traffic Real Time Statistics&tab=search&type=term">Visitor Traffic Real Time Statistics</a></h3>
																		<div class="wporg-ratings" title="4 out of 5 stars" style="color:#ffb900;"></div>
																		<p class="active_installs">Active Installs: 50,000+</p>
																	</div>
																</li>
																<li>
																	<div class="plugin-info-container">
																		<h3><a target="blank" href="<?php echo $admin_url; ?>?s=WooCommerce Email Marketing Cart Abandonment Recovery&tab=search&type=term">WooCommerce Email Marketing & Cart Abandonment Recovery</a></h3>
																		<p class="active_installs">Active Installs: 10+</p>
																	</div>
																</li>
																<li>
																	<div class="plugin-info-container">
																		<h3><a target="blank" href="<?php echo $admin_url; ?>?s=WP Category Post List wp-buy&tab=search&type=term">WP Category Post List</a></h3>
																		<div class="wporg-ratings" title="5 out of 5 stars" style="color:#ffb900;"></div>
																		<p class="active_installs">Active Installs: 900+</p>
																	</div>
																</li>
																<li>
																	<div class="plugin-info-container">
																		<h3><a target="blank" href="<?php echo $admin_url; ?>?s=WP Content Copy Protection No Right Click&tab=search&type=term">WP Content Copy Protection & No Right Click</a></h3>
																		<div class="wporg-ratings" title="4 out of 5 stars" style="color:#ffb900;"></div>
																		<p class="active_installs">Active Installs: 100,000+</p>
																	</div>
																</li>
																<li>
																	<div class="plugin-info-container">
																		<h3><a target="blank" href="<?php echo $admin_url; ?>?s=WP Maintenance Mode Site Under Construction&tab=search&type=term">WP Maintenance Mode & Site Under Construction</a></h3>
																		<div class="wporg-ratings" title="4 out of 5 stars" style="color:#ffb900;"></div>
																		<p class="active_installs">Active Installs: 1,000+</p>
																	</div>
																</li>
															</ul>
														</div>
													</div>


												</div>
											</div>
											<p class="submit">
												<?php
												submit_button('Apply Changes');
												?>
												<br class="clear">
											</p>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		public function add_script()
		{
			if (isset($_GET['page']) && $_GET['page'] == 'MM_And_SUC_Free_Settings') {
			?>
				<script>
					"use strict";
					jQuery(document).ready(function($) {
						"use strict";
						_wpMediaViewsL10n.insertIntoPost = '<?php echo esc_js("Insert"); ?>';

						function ct_media_upload(button_class) {
							var _custom_media = true,
								_orig_send_attachment = wp.media.editor.send.attachment;
							$('body').on('click', button_class, function(e) {
								var button_id = '#' + $(this).attr('id');
								var send_attachment_bkp = wp.media.editor.send.attachment;
								var button = $(button_id);
								_custom_media = true;
								wp.media.editor.send.attachment = function(props, attachment) {
									if (_custom_media) {
										$('#showcase-taxonomy-image-id').val(attachment.id);
										$('#category-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
										$('#category-image-wrapper .custom_media_image').attr('src', attachment.url).css('display', 'block');
									} else {
										return _orig_send_attachment.apply(button_id, [props, attachment]);
									}
								}
								wp.media.editor.open(button);
								return false;
							});
						}
						ct_media_upload('.showcase_tax_media_button.button');
						$('body').on('click', '.showcase_tax_media_remove', function() {
							$('#showcase-taxonomy-image-id').val('');
							$('#category-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
						});

						$(document).ajaxComplete(function(event, xhr, settings) {
							if (settings.data && typeof settings.data === 'string') {
								var queryStringArr = settings.data.split('&');
								if ($.inArray('action=add-tag', queryStringArr) !== -1) {
									var xml = xhr.responseXML;
									$response = $(xml).find('term_id').text();
									if ($response != "") {
										// Clear the thumb image
										$('#category-image-wrapper').html('');
									}
								}
							}
						});

						if ($("#MM_And_SUC_Free_option_type_of_bg").val() == 1) {
							$('.image_upload').show();
							$('.image_textures').hide();
						} else if ($("#MM_And_SUC_Free_option_type_of_bg").val() == 2) {
							$('.image_textures').show();
							$('.image_upload').hide();
						} else {
							$('.image_textures').hide();
							$('.image_upload').hide();
						}
						$("#MM_And_SUC_Free_option_type_of_bg").change(function() {
							if ($(this).val() == 1) {
								$('.image_upload').show();
								$('.image_textures').hide();
							} else if ($(this).val() == 2) {
								$('.image_textures').show();
								$('.image_upload').hide();
							}
						});
					});
				</script>

<?php }
		}
	}
	new MM_And_SUC_Free_admin_setting();
}

function MM_And_SUC_Free_hkdc_admin_styles($page)
{
	if (isset($_GET['page']) && $_GET['page'] == 'MM_And_SUC_Free_Settings') {
		wp_enqueue_style('tail-datetime-red', MM_And_SUC_Free_PLUGIN_URL . '/assets/css/tail.datetime-default-red.css');
		wp_enqueue_style('admin-css', MM_And_SUC_Free_PLUGIN_URL . '/assets/css/admin-css.css?r=2');
		wp_enqueue_style('bootstrap', MM_And_SUC_Free_PLUGIN_URL . '/assets/css/bootstrap.min.css');
	}
}
add_action('admin_print_styles', 'MM_And_SUC_Free_hkdc_admin_styles');
function MM_And_SUC_Free_hkdc_admin_scripts()
{
	if (isset($_GET['page']) && $_GET['page'] == 'MM_And_SUC_Free_Settings') {
		wp_enqueue_script('tail-datetime', MM_And_SUC_Free_PLUGIN_URL . '/assets/js/js/tail.datetime.js', array('jquery'), '4.0', true);
		wp_enqueue_script('tail-datetime-all', MM_And_SUC_Free_PLUGIN_URL . '/assets/js/langs/tail.datetime-all.js', array('jquery'), '4.0', true);
		wp_enqueue_script('wp-jquery-date-picker', MM_And_SUC_Free_PLUGIN_URL . '/assets/js/custom.js', array('jquery'), '4.0', true);
		wp_enqueue_media();
	}
}
add_action('admin_enqueue_scripts', 'MM_And_SUC_Free_hkdc_admin_scripts');

function MM_And_SUC_Free_getAllDirs($directory, $directory_seperator)
{

	$dirs = array_map(function ($item) use ($directory_seperator) {
		return $item . $directory_seperator;
	}, array_filter(glob($directory . '*'), 'is_dir'));

	foreach ($dirs as $dir) {
		$dirs = array_merge($dirs, MM_And_SUC_Free_getAllDirs($dir, $directory_seperator));
	}
	return $dirs;
}

function MM_And_SUC_Free_getAllImgs($directory)
{
	$resizedFilePath = array();
	foreach ($directory as $dir) {
		foreach (glob($dir . '*.{jpg,JPG,jpeg,JPEG,png,PNG}', GLOB_BRACE) as $filename) {
			$filename_big = pathinfo($filename);
			if (!strstr($filename_big['filename'], 'big')) {
				array_push($resizedFilePath, explode('/textures/', $filename)[1]);
			}
		}
	}
	return $resizedFilePath;
}
