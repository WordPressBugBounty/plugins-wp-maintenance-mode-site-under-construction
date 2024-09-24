<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if( ! class_exists( 'MM_And_SUC_Free_admin_setting' ) ) {
	class MM_And_SUC_Free_admin_setting{
        public $before_section;
        public $before_section_right;
        public $after_section;
        public $after_section_right;
		public function __construct() {
			add_action( 'admin_menu', array($this,'MM_And_SUC_Free_options_page') );
			add_action( 'admin_init', array($this,'MM_And_SUC_Free_settings_init') );
			add_action( 'admin_footer', array( $this, 'add_script' ) );
			$this->before_section = '<div class="col-md-12 col-sm-12 MM_And_SUC_Free_admin_card"><div class="card">';
			$this->before_section_right = '<div class="col-md-12 col-sm-12 MM_And_SUC_Free_admin_card"><div class="card">';
			$this->after_section = '</div></div>';
			$this->after_section_right = '</div></div>';
		}
		public function MM_And_SUC_Free_settings_init() {
			register_setting( 'MM_And_SUC_Free_Settings', 'MM_And_SUC_Free_options');

            add_settings_section(
                'MM_And_SUC_Free_section_Status_developers',
                __( 'Status:', 'wp-maintenance-mode-site-under-construction' ),
                array($this,'MM_And_SUC_Free_section_developers_dev_cb'),
                'MM_And_SUC_Free_Settings',
                array(
                        'before_section'=>$this->before_section,
                        'after_section'=>$this->after_section,
                        'section_class'=>'section_class'
                )
            );

            add_settings_section(
                'MM_And_SUC_Free_section_role_developers',
                __( 'role:', 'wp-maintenance-mode-site-under-construction' ),
                array($this,'MM_And_SUC_Free_section_developers_dev_cb'),
                'MM_And_SUC_Free_Settings',
                array(
                    'before_section'=>$this->before_section_right,
                    'after_section'=>$this->after_section_right,
                    'section_class'=>'section_class'
                )
            );
            add_settings_section(
                'MM_And_SUC_Free_section_massage_developers',
                __( 'massage:', 'wp-maintenance-mode-site-under-construction' ),
                array($this,'MM_And_SUC_Free_section_developers_dev_cb'),
                'MM_And_SUC_Free_Settings',
                array(
                    'before_section'=>$this->before_section,
                    'after_section'=>$this->after_section,
                    'section_class'=>'section_class'
                )
            );


            add_settings_section(
                'MM_And_SUC_Free_section_Background_developers',
                __( 'Background:', 'wp-maintenance-mode-site-under-construction' ),
                array($this,'MM_And_SUC_Free_section_developers_dev_cb'),
                'MM_And_SUC_Free_Settings',
                array(
                    'before_section'=>$this->before_section_right,
                    'after_section'=>$this->after_section_right,
                    'section_class'=>'section_class'
                )
            );
            add_settings_field(
                'MM_And_SUC_Free_status',
                __( 'Status', 'wp-maintenance-mode-site-under-construction' ),
                array($this,'MM_And_SUC_Free_field_type_checkbox'),
                'MM_And_SUC_Free_Settings',
                'MM_And_SUC_Free_section_Status_developers',
                [
                    'label_for' => 'MM_And_SUC_Free_status',
                    'class' => 'MM_And_SUC_Free_row',
                    'MM_And_SUC_Free_custom_data' => 'custom',
                    'description' => 'Enable/Disable plugin functionality', 'wp-maintenance-mode-site-under-construction',
                ]
            );
			add_settings_field(
				'MM_And_SUC_Free_full_screen_mode', 
				__( 'Full screen mode', 'wp-maintenance-mode-site-under-construction' ),
				array($this,'MM_And_SUC_Free_field_type_checkbox'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_Status_developers',
				[
					'label_for' => 'MM_And_SUC_Free_full_screen_mode',
					'class' => 'MM_And_SUC_Free_row',
					'MM_And_SUC_Free_custom_data' => 'custom',
					'description' => 'No contact form shown with this mode', 'wp-maintenance-mode-site-under-construction',
				]
			); 
			add_settings_field(
				'MM_And_SUC_Free_title', 
				__( 'Title', 'wp-maintenance-mode-site-under-construction' ),
				array($this,'MM_And_SUC_Free_field_type_text'),
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
				__( 'Description', 'wp-maintenance-mode-site-under-construction' ),
				array($this,'MM_And_SUC_Free_field_type_textarea'),
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
				__( 'Email', 'wp-maintenance-mode-site-under-construction' ),
				array($this,'MM_And_SUC_Free_field_type_email'),
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
				__( 'Date', 'wp-maintenance-mode-site-under-construction' ),
				array($this,'MM_And_SUC_Free_field_type_date_time'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_Status_developers',
				[
					'label_for' => 'MM_And_SUC_Free_date',
					'class' => 'MM_And_SUC_Free_row',
				]
			);
			add_settings_field(
				'MM_And_SUC_Free_role', 
				__( 'Apply on', 'wp-maintenance-mode-site-under-construction' ),
				array($this,'MM_And_SUC_Free_field_type_roles'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_role_developers',
				[
					'label_for' => 'MM_And_SUC_Free_role',
					'class' => 'MM_And_SUC_Free_row',
					'MM_And_SUC_Free_custom_data' => array(),
				]
			);
			add_settings_field(
				'MM_And_SUC_Free_select_type_of_bg',
				__( 'Background Type', 'wp-maintenance-mode-site-under-construction' ),
				array($this,'MM_And_SUC_Free_select_type_of_bg'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_Background_developers',
				[
					'label_for' => 'MM_And_SUC_Free_option_type_of_bg',
					'class' => 'MM_And_SUC_Free_row',
					'id' => 'showcase-taxonomy-select-id',
					'MM_And_SUC_Free_custom_data' => 'custom',
				]
			);
			add_settings_field(
				'MM_And_SUC_Free_image', 
				__( 'Background image', 'wp-maintenance-mode-site-under-construction' ),
				array($this,'MM_And_SUC_Free_field_type_image'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_Background_developers',
				[
					'label_for' => 'MM_And_SUC_Free_image',
					'class' => 'MM_And_SUC_Free_row image_upload',
					'id' => 'showcase-taxonomy-image-id',
					'MM_And_SUC_Free_custom_data' => 'custom',
				]
			);
			add_settings_field(
				'MM_And_SUC_Free_textures', 
				__( 'Background textures', 'wp-maintenance-mode-site-under-construction' ),
				array($this,'MM_And_SUC_Free_field_type_textures'),
				'MM_And_SUC_Free_Settings',
				'MM_And_SUC_Free_section_Background_developers',
				[
					'label_for' => 'MM_And_SUC_Free_textures',
					'class' => 'MM_And_SUC_Free_row image_textures',
					'id' => 'showcase-taxonomy-textures-id',
					'MM_And_SUC_Free_custom_data' => 'custom',
				]
			);
		}
        public function section_header(){
            return '<div class="col-md-6 col-sm-12">';
        }
        public function section_footer(){
            return '</div>';
        }
		public function MM_And_SUC_Free_field_type_textures( $args ) {
			$options = get_option( 'MM_And_SUC_Free_options' );
			$directory = MM_And_SUC_Free_PLUGIN_DIR.'/textures/';
			$directory_seperator = "/";
			$allimages = MM_And_SUC_Free_getAllImgs(MM_And_SUC_Free_getAllDirs($directory, $directory_seperator));
			 ?>
			<div id="conte_textures">
			<?php 
			foreach($allimages as $row){
				$text_path = pathinfo($row);
			?>
			<label class="tooltip">
			  <input type="radio" name="MM_And_SUC_Free_options[<?php echo esc_attr( $args['label_for'] ); ?>]" <?php isset($options[ $args['label_for']])?checked($options[ $args['label_for']], $row):''; ?> value="<?php echo esc_attr($row);?>" >
			  <img src="<?php esc_attr_e(MM_And_SUC_Free_PLUGIN_URL.'textures/'.$row);?>">
			  <span class="tooltiptext tooltip-top">
				<?php
				$link = MM_And_SUC_Free_PLUGIN_DIR.'textures/'.$text_path['dirname'].'/'.$text_path['filename'].'.txt';
				if(file_exists($link)){
					$myfile = fopen($link, "r") or die("Unable to open file!");
					esc_html_e(fread($myfile,filesize($link)));
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
		public function MM_And_SUC_Free_select_type_of_bg( $args ) {
			$options = get_option( 'MM_And_SUC_Free_options' );
			
			 ?>
			 <select class="form-control" name="MM_And_SUC_Free_options[<?php echo esc_attr( $args['label_for'] ); ?>]" id="<?php echo esc_attr( $args['label_for'] ); ?>" >
				<option value="1" <?php isset($options[ $args['label_for']])?selected($options[ $args['label_for']], 1):''; ?>>image</option>
				<option value="2" <?php isset($options[ $args['label_for']])?selected($options[ $args['label_for']], 2):''; ?>>textures</option>
			 </select>
			 
			 <?php
		}
		public function MM_And_SUC_Free_section_developers_cb( $args ) {
			/*
			<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'sssssss', 'wp-maintenance-mode-site-under-construction' ); ?></p>
			*/
		}
		public function MM_And_SUC_Free_section_developers_dev_cb( $args ) {
            /*
            <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'sssssss', 'wp-maintenance-mode-site-under-construction' ); ?></p>
            */
		}
		public function MM_And_SUC_Free_field_type_image( $args ) {
			$options = get_option( 'MM_And_SUC_Free_options' );
			
			 ?>
			 <?php $image_id = isset($options[ $args['label_for']])?$options[ $args['label_for']]:''; ?>
			  <input type="hidden" id="<?php echo esc_attr( $args['id'] ); ?>" name="MM_And_SUC_Free_options[<?php echo esc_attr( $args['label_for'] ); ?>]" value="<?php echo esc_attr( $image_id ); ?>">
			  <div id="category-image-wrapper">
				<?php if( $image_id!='' ) { ?>
				  <?php
                    echo wp_get_attachment_image( $image_id, 'full',false, array( "class" => "custom_media_image" )  );
                    ?>
				<?php } ?>
			  </div>
			  <p>
				<input type="button" style="opacity: 1 !important;" class="button button-secondary showcase_tax_media_button" id="showcase_tax_media_button" name="showcase_tax_media_button" value="<?php echo esc_attr__( 'Add Image', 'codepressfaq' ); ?>" />
				<input type="button" style="opacity: 1 !important;" class="button button-secondary showcase_tax_media_remove" id="showcase_tax_media_remove" name="showcase_tax_media_remove" value="<?php echo esc_attr__( 'Remove Image', 'codepressfaq' ); ?>" />
			  </p>
			 
			 <p class="description"><?php esc_html_e( 'Try to choose a large image for a good view', 'wp-maintenance-mode-site-under-construction' ); ?></p>
			 
			 <?php
		}
		public function MM_And_SUC_Free_field_type_date_time( $args ) {
			$options = get_option( 'MM_And_SUC_Free_options' );
			
			 ?>
			 <input type="text" readonly style="opacity: 1 !important;" name="MM_And_SUC_Free_options[<?php echo esc_attr( $args['label_for'] ); ?>]" class="datetime-field form-control"  data-datetime="{'position':'bottom','dateFormat':'dd-mm-YYYY '}" id="<?php echo esc_attr( $args['label_for'] ); ?>"  data-tail-datetime="tail-11" data-value="<?php echo isset($options[ $args['label_for']])?esc_attr(strtotime($options[ $args['label_for']])):'';?>" value="<?php echo isset($options[ $args['label_for']])?esc_attr($options[ $args['label_for']]):'';?>">
			 <p class="description">
			 <?php esc_html_e( 'Example: 22-09-2020  18:07:00', 'wp-maintenance-mode-site-under-construction' ); ?>
			 </p>
			 
			 <?php
		}
		public function MM_And_SUC_Free_field_type_roles( $args ) {
			$options = get_option( 'MM_And_SUC_Free_options' );
			$value = isset($options[ $args['label_for']])?$options[ $args['label_for']]:array();
			global $wp_roles;
			$wp_roles = new WP_Roles();
			echo '<div class="row">';
			foreach($wp_roles->get_names() as $name){
			 ?>
                    <div class="wp_roles_list col-lg-12 col-xl-6" style="margin-bottom: 7px;">
                        <label class="switch">
                            <input type="checkbox" id="<?php echo esc_attr($name);?>"   name="MM_And_SUC_Free_options[<?php echo esc_attr( $args['label_for'] ); ?>]['<?php echo esc_attr($name);?>']" value="<?php echo esc_attr($name);?>"  <?php if(isset($value["'".$name."'"])){?> checked<?php };?>>
                            <span class="slider round"></span>
                        </label><?php esc_html_e($name);?>
                    </div>

			 <?php
			}
			echo '</div>';
			?>
            <div>
                <label class="switch">
                    <input type="checkbox" id="Un_Registered_Visitor"   name="Un_Registered_Visitor" value="Un_Registered_Visitor" disabled checked="">
                    <span class="slider round"></span>
                </label>Guests
            </div>

            <p class="description">Note: Guests (Un-Registered Visitors) will always be under maintenance mode when the status is (ON)</p>

			<?php
		}
		
		public function MM_And_SUC_Free_field_type_text( $args ) {
			$options = get_option( 'MM_And_SUC_Free_options' );
			
			 ?>
			 <input type="text" class="form-control" style="opacity: 1 !important;" value="<?php echo isset($options[ $args['label_for']])?esc_attr($options[ $args['label_for']]):'';?>" id="<?php echo esc_attr( $args['label_for'] ); ?>" data-custom="<?php echo esc_attr( $args['MM_And_SUC_Free_custom_data'] ); ?>" name="MM_And_SUC_Free_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
			 <p class="description"></p>
			 
			 <?php
		}
		public function MM_And_SUC_Free_field_type_email( $args ) {
			$options = get_option( 'MM_And_SUC_Free_options' );
			
			 ?>
			 <input type="email" style="opacity: 1 !important;" class="form-control" value="<?php echo isset($options[ $args['label_for']])?esc_attr($options[ $args['label_for']]):'';?>" id="<?php echo esc_attr( $args['label_for'] ); ?>" data-custom="<?php echo esc_attr( $args['MM_And_SUC_Free_custom_data'] ); ?>" name="MM_And_SUC_Free_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
			 <p class="description">
			 <?php esc_html_e( 'Emails wil be sent to site admin if you leave this field blank', 'wp-maintenance-mode-site-under-construction' ); ?>
			 </p>
			 
			 <?php
		}
		public function MM_And_SUC_Free_field_type_textarea( $args ) {
			$options = get_option( 'MM_And_SUC_Free_options' );
			
			 ?>
			 <textarea id="<?php echo esc_attr( $args['label_for'] ); ?>" class="form-control" data-custom="<?php echo esc_attr( $args['MM_And_SUC_Free_custom_data'] ); ?>" name="MM_And_SUC_Free_options[<?php echo esc_attr( $args['label_for'] ); ?>]" class="mceEditor" rows="3" style="width:100%;" autocomplete="off" ><?php echo isset($options[ $args['label_for']])?esc_attr($options[ $args['label_for']]):'';?></textarea>
			 <p class="description"></p>
			 
			 <?php
		}
		public function MM_And_SUC_Free_field_type_checkbox( $args ) {
			$options = get_option( 'MM_And_SUC_Free_options' );
			
			?>
            <div>
                <span class="on_off off"><?php esc_html_e( 'OFF' ); ?></span>
                <label class="switch">
                    <input type="checkbox" id="<?php esc_attr_e( $args['label_for'] ); ?>"   name="MM_And_SUC_Free_options[<?php echo esc_attr( $args['label_for'] ); ?>]" value="1"  <?php echo isset( $options[ $args['label_for']] ) ? ( checked( $options[ $args['label_for'] ], '1', false ) ) : ( '' ); ?>>
                    <span class="slider round"></span>
                </label>
                <span class="on_off on"><?php esc_html_e( 'ON' ); ?></span>
                <p class="description">
                    <?php esc_html_e( $args['description'] ); ?>
                </p>
            </div>

			<?php
		}
		 

		public function MM_And_SUC_Free_options_page() {
			 add_menu_page(
				 'WP Maintenance Mode & Site Under Construction Settings:',
				 'Maintenance Mode',
				 'manage_options',
				 'MM_And_SUC_Free_Settings',
				 array($this,'MM_And_SUC_Free_options_page_html'),
                 'dashicons-admin-tools'
			 );
		}
		
		 
		public function MM_And_SUC_Free_options_page_html() {
		 
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
		 
			if ( isset( $_GET['settings-updated'] ) ) {
				add_settings_error( 'MM_And_SUC_Free_messages', 'MM_And_SUC_Free_message', __( 'Settings Saved', 'wp-maintenance-mode-site-under-construction' ), 'updated' );
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
        <div class="col-lg-12 col-xl-10 col-xxl-8">
			<h3><?php _e( 'WP Maintenance Mode & Site Under Construction.', 'wp-maintenance-mode-site-under-construction' );?></h3>
			<div id="">
                <?php settings_errors( 'MM_And_SUC_Free_messages' );?>
				<div id="dashboard-widgets" class="metabox-holder">
					<div id="" class="">
						<div id="side-sortables" class="meta-box-sortables ui-sortable">
							<div id="dashboard_quick_press" >
								<h2 class="hndle ui-sortable-handle">
									<span>
										<span class="hide-if-no-js"><?php esc_html_e( get_admin_page_title() ); ?></span> 
										<span class="hide-if-js"><?php esc_html_e( get_admin_page_title() ); ?></span>
									</span>
								</h2>
								<div class="inside">
									<form action="options.php" method="post">
										<div class="input-text-wrap row" id="title-wrap">
                                            <div class="col-md-8 col-sm-12">
                                                <?php
                                                    settings_fields( 'MM_And_SUC_Free_Settings' );
                                                    do_settings_sections( 'MM_And_SUC_Free_Settings' );
                                                ?>
                                            </div>
                                            <div class="col-md-4 col-sm-12  " >
                                                <div class="col-md-12 col-sm-12  MM_And_SUC_Free_admin_card" >
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
												submit_button( 'Save Settings' );
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
		
		public function add_script() {
			if(isset($_GET['page']) && $_GET['page'] == 'MM_And_SUC_Free_Settings'){
			 ?>
			 <script> 
			 "use strict";
			 jQuery(document).ready( function($) {
				 "use strict";
			   _wpMediaViewsL10n.insertIntoPost = '<?php echo esc_js( "Insert" ); ?>';
			   function ct_media_upload(button_class) {
				 var _custom_media = true, _orig_send_attachment = wp.media.editor.send.attachment;
				 $('body').on('click', button_class, function(e) {
				   var button_id = '#'+$(this).attr('id');
				   var send_attachment_bkp = wp.media.editor.send.attachment;
				   var button = $(button_id);
				   _custom_media = true;
				   wp.media.editor.send.attachment = function(props, attachment){
					 if( _custom_media ) {
					   $('#showcase-taxonomy-image-id').val(attachment.id);
					   $('#category-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
					   $( '#category-image-wrapper .custom_media_image' ).attr( 'src',attachment.url ).css( 'display','block' );
					 } else {
					   return _orig_send_attachment.apply( button_id, [props, attachment] );
					 }
				   }
				   wp.media.editor.open(button); return false;
				 });
			   }
			   ct_media_upload('.showcase_tax_media_button.button');
			   $('body').on('click','.showcase_tax_media_remove',function(){
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
				
				if($("#MM_And_SUC_Free_option_type_of_bg").val()==1){
					$('.image_upload').show();
					$('.image_textures').hide();					
				}else if($("#MM_And_SUC_Free_option_type_of_bg").val()==2){
					$('.image_textures').show();
					$('.image_upload').hide();	
				}else{
					$('.image_textures').hide();	
					$('.image_upload').hide();	
				}
				$("#MM_And_SUC_Free_option_type_of_bg").change(function(){
					if($(this).val()==1){
						$('.image_upload').show();
						$('.image_textures').hide();					
					}else if($(this).val()==2){
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

function MM_And_SUC_Free_hkdc_admin_styles($page) {
	if(isset($_GET['page']) && $_GET['page'] == 'MM_And_SUC_Free_Settings'){
		wp_enqueue_style( 'tail-datetime-red' , MM_And_SUC_Free_PLUGIN_URL.'/assets/css/tail.datetime-default-red.css');
		wp_enqueue_style( 'admin-css' , MM_And_SUC_Free_PLUGIN_URL.'/assets/css/admin-css.css?r=2');
		wp_enqueue_style( 'bootstrap' , MM_And_SUC_Free_PLUGIN_URL.'/assets/css/bootstrap.min.css');
	}
}
add_action('admin_print_styles', 'MM_And_SUC_Free_hkdc_admin_styles');
function MM_And_SUC_Free_hkdc_admin_scripts() {
	if(isset($_GET['page']) && $_GET['page'] == 'MM_And_SUC_Free_Settings'){
		wp_enqueue_script( 'tail-datetime', MM_And_SUC_Free_PLUGIN_URL . '/assets/js/js/tail.datetime.js', array('jquery'), '4.0', true );
		wp_enqueue_script( 'tail-datetime-all', MM_And_SUC_Free_PLUGIN_URL . '/assets/js/langs/tail.datetime-all.js', array('jquery'), '4.0', true );
		wp_enqueue_script( 'wp-jquery-date-picker', MM_And_SUC_Free_PLUGIN_URL . '/assets/js/custom.js', array('jquery'), '4.0', true );
		wp_enqueue_media();
	}
}
add_action('admin_enqueue_scripts', 'MM_And_SUC_Free_hkdc_admin_scripts');

function MM_And_SUC_Free_getAllDirs($directory, $directory_seperator)
{

	$dirs = array_map(function ($item) use ($directory_seperator) {
		return $item . $directory_seperator;
	}, array_filter(glob($directory . '*'), 'is_dir'));

	foreach ($dirs AS $dir) {
		$dirs = array_merge($dirs, MM_And_SUC_Free_getAllDirs($dir, $directory_seperator));
	}
	return $dirs;
}

function MM_And_SUC_Free_getAllImgs($directory)
{
	$resizedFilePath = array();
	foreach ($directory AS $dir) {
		foreach (glob($dir . '*.{jpg,JPG,jpeg,JPEG,png,PNG}',GLOB_BRACE) as $filename) {
			$filename_big = pathinfo($filename);
			if ( !strstr( $filename_big['filename'], 'big' ) ) {
				array_push($resizedFilePath, explode( '/textures/', $filename )[1]);
			}
			
		}
	}
  return $resizedFilePath;
}



