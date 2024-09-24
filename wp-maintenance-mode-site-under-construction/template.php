<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if( ! class_exists( 'MM_And_SUC_Free_page_template' ) ) {
    class MM_And_SUC_Free_page_template{

        public function __construct() {
            add_action( 'init', array($this,'template') );
            add_action( 'wp_ajax_contactform_action', array($this,'ajax_contactform_action_callback') );
            add_action( 'wp_ajax_nopriv_contactform_action', array($this,'ajax_contactform_action_callback') );
        }

        public function template() {
            $options = get_option( 'MM_And_SUC_Free_options' );

            if($this->UserConditional($options)){
                return;
            }
            $this->remove_header_tage_run_hook();

            add_action('wp_enqueue_scripts',array($this,'inc_js_file'));

            add_action( 'wp_enqueue_scripts', array($this,'inc_css_file' ));
            ?>

            <!DOCTYPE HTML>
            <html lang="en">
            <head>
                <title><?php echo esc_html(get_option('blogname')); ?></title>
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <meta charset="UTF-8">

                <?php wp_head();?>
            </head>
            <body>
            <div class="main-area">
                <div class="container-fluid full-height position-static">
                    <?php

                    $show_contact_form = true;
                    if(array_key_exists('MM_And_SUC_Free_full_screen_mode',$options))
                        if(isset($options['MM_And_SUC_Free_full_screen_mode']) && $options['MM_And_SUC_Free_full_screen_mode'] == 1) $show_contact_form = false;
                    if ($show_contact_form)
                    {
                        ?>
                        <section class="left-section col full-height">
                            <div class="display-table">
                                <div>
                                    <div class="main-content">

                                        <?php
                                        $custom_logo_id = absint(get_theme_mod( 'custom_logo' ));
                                        $image = wp_get_attachment_image_src( $custom_logo_id , 'full' );
                                        if(isset($image[0]) && $image[0] !=''){
                                            ?>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <img src="<?php esc_attr_e( $image[0] ); ?>" title="<?php esc_attr_e( $options['MM_And_SUC_Free_title'] ); ?>">
                                                </div>

                                            </div>
                                        <?php } ?>
                                        <h3 class="title"><b><?php _e('Contact us:','wp-maintenance-mode-site-under-construction');?></b></h3>
                                        <p><?php _e('We will be back soon! For more information, feel free to contact us anytime','wp-maintenance-mode-site-under-construction');?></p>

                                        <div class="email-input-area">

                                            <form class="contact2-form validate-form" id="contactform">
                                                <div class="wrap-input2 validate-input" data-validate="Name is required">
                                                    <input class="input2" id="name"  type="text" name="name">
                                                    <span class="focus-input2" data-placeholder="<?php esc_attr_e('NAME','wp-maintenance-mode-site-under-construction');?>"></span>
                                                </div>
                                                <div class="wrap-input2 validate-input" data-validate="Valid email is required: ex@abc.xyz">
                                                    <input class="input2" id="email"  type="text" name="email">
                                                    <span class="focus-input2" data-placeholder="<?php esc_attr_e('E-mail','wp-maintenance-mode-site-under-construction');?>"></span>
                                                </div>

                                                <div class="wrap-input2 validate-input" data-validate = "Message is required">
                                                    <textarea class="input2" name="message" id="message"></textarea>
                                                    <span class="focus-input2" data-placeholder="<?php esc_attr_e('MESSAGE','wp-maintenance-mode-site-under-construction');?>"></span>
                                                </div>

                                                <div id="contact-msg">   </div>
                                                <div class="container-contact2-form-btn">

                                                    <div class="wrap-contact2-form-btn">
                                                        <div class="contact2-form-bgbtn"></div>
                                                        <button class="contact2-form-btn" id="contactbutton">
                                                            <?php esc_html_e( 'Contact webmaster', 'wp-maintenance-mode-site-under-construction' ); ?>
                                                        </button>

                                                    </div>
                                                </div>
                                                <?php echo wp_nonce_field('contactform_action', '_acf_nonce', true, false);?>
                                                <input type="hidden" name="action" value="contactform_action" />
                                            </form>

                                        </div><!-- email-input-area -->

                                    </div><!-- main-content -->
                                </div><!-- display-table-cell -->
                            </div><!-- display-table -->

                        </section><!-- left-section -->
                    <?php } ?>

                    <?php
                    $width_class = "full_screen_mode";
                    if ($show_contact_form){$width_class = "half_width_mode";}
                    ?>
                    <section class="right-section <?php echo esc_attr($width_class);?>"
                        <?php if($options['MM_And_SUC_Free_option_type_of_bg'] == 2){
                            $filename_big = pathinfo(MM_And_SUC_Free_PLUGIN_URL.'textures/'.$options['MM_And_SUC_Free_textures']);
                            $file_big_name = $filename_big['dirname'].'/'.$filename_big['filename'].'-big.'.$filename_big['extension'];
                            ?>
                            style="background-image: url(<?php if(isset($options['MM_And_SUC_Free_textures']) && $options['MM_And_SUC_Free_textures'] !=""){esc_attr_e($file_big_name);}?>)"
                        <?php }else{ ?>
                            style="background-image: url(<?php if(isset($options['MM_And_SUC_Free_image']) && $options['MM_And_SUC_Free_image'] !=""){ echo esc_attr(wp_get_attachment_image_src( $options['MM_And_SUC_Free_image'], 'full' )['0']);}else{ echo esc_attr(MM_And_SUC_Free_PLUGIN_URL);?>/assets/images/countdown-1-1000x1000.jpg)<?php } ?>"
                        <?php } ?>
                    >

                        <div class="display-table center-text">
                            <div class="display-table-cell">

                                <div id="rounded-countdown">
                                    <h3 class="title responsive_title"><b><?php esc_html_e($options['MM_And_SUC_Free_title']);?></b></h3>
                                    <?php
                                    $date_time_wp = (array)current_datetime();

                                    $date = new DateTime($date_time_wp['date']);
                                    $date2 = new DateTime( $options['MM_And_SUC_Free_date'] );
                                    $diff = $date2->getTimestamp() - $date->getTimestamp();
                                    ?>
                                    <div class="countdown" data-remaining-sec="<?php esc_attr_e($diff);?>"></div>
                                    <p class="des responsive_des"><?php  esc_html_e($options['MM_And_SUC_Free_description']);?></p>
                                </div>

                            </div><!-- display-table-cell -->
                        </div><!-- display-table -->

                    </section><!-- right-section -->

                </div><!-- container -->
            </div><!-- main-area -->

            <!-- SCIPTS -->
            <?php do_action( 'MM_And_SUC_Free_footer');?>
            <?php wp_footer(); ?>

            </body>
            </html>

            <?php
            exit;
        }
        function ajax_contactform_action_callback() {
            $error = '';
            $status = 'error';

            if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['message'])) {
                $error = 'All fields are required to enter.';
            } else {
                if (!wp_verify_nonce($_POST['_acf_nonce'], $_POST['action'])) {
                    $error = esc_html_('Verification error, try again.','wp-maintenance-mode-site-under-construction');
                } else {

                    $options = get_option( 'MM_And_SUC_Free_options' );
                    $name = sanitize_text_field($_POST['name']);
                    $email = sanitize_email($_POST['email']);
                    $subject = sanitize_text_field($_POST['name']);
                    $message = sanitize_text_field($_POST['message']);
                    $message .= 'IP address: '.sanitize_text_field($_SERVER['REMOTE_ADDR']);

                    $message .= 'Sender\'s name: '.$name;
                    $message .= 'E-mail address: '.$email;

                    $sendmsg =  __('Thanks, for the message. We will respond as soon as possible.','wp-maintenance-mode-site-under-construction');

                    $to = $options['MM_And_SUC_Free_email']?sanitize_email($options['MM_And_SUC_Free_email']):sanitize_email(get_option('admin_email'));

                    $header = 'From: '.get_option('blogname').' <'.$to.'>'.PHP_EOL;
                    $header .= 'Reply-To: '.$email.PHP_EOL;
                    if ( wp_mail($to, $subject, $message, $header) ) {
                        $status = 'success';
                        $error = $sendmsg;
                    } else {
                        $error = esc_html_('Some errors occurred.','wp-maintenance-mode-site-under-construction');
                    }
                }
            }
            $resp = array('status' => $status, 'errmessage' => $error);
            header( "Content-Type: application/json" );
            echo json_encode($resp);
            die();
        }
        public function remove_header_tage_run_hook(){
            remove_action('wp_head', 'rsd_link');
            remove_action('wp_head', 'wp_generator');
            remove_action('wp_head', 'feed_links', 2);
            remove_action('wp_head', 'feed_links_extra', 3);
            remove_action('wp_head', 'index_rel_link');
            remove_action('wp_head', 'wlwmanifest_link');
            remove_action('wp_head', 'start_post_rel_link', 10, 0);
            remove_action('wp_head', 'parent_post_rel_link', 10, 0);
            remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
            remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
            remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_head', 'rel_canonical');
            remove_action('wp_head', 'rel_alternate');
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            remove_action('wp_head', 'wp_oembed_add_host_js');
            remove_action('wp_head', 'rest_output_link_wp_head');
            remove_action('rest_api_init', 'wp_oembed_register_route');
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
            remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
            add_filter('embed_oembed_discover', '__return_false');
            remove_action('wp_head', 'wp_generator');
        }
        public function inc_js_file() {
            wp_register_script('tether-min', plugins_url( 'assets/js/tether.min.js', __FILE__ ),array( 'jquery' ), NULL, true);
            wp_enqueue_script( 'tether-min' );
            wp_register_script('jquery-classycountdown', plugins_url( 'assets/js/jquery.classycountdown.js', __FILE__ ), array( 'jquery' ), NULL, true );
            wp_enqueue_script( 'jquery-classycountdown' );
            wp_register_script('jquery-knob', plugins_url( 'assets/js/jquery.knob.js', __FILE__ ), array( 'jquery' ), NULL, true);
            wp_enqueue_script( 'jquery-knob' );
            wp_register_script('jquery-throttle', plugins_url( 'assets/js/jquery.throttle.js', __FILE__ ),array( 'jquery' ), NULL, true );
            wp_enqueue_script( 'jquery-throttle' );
            wp_register_script('scripts', plugins_url( 'assets/js/scripts.js', __FILE__ ), array( 'jquery' ), NULL, true);
            wp_enqueue_script( 'scripts' );

            wp_enqueue_script( 'contactform-script', plugins_url( 'assets/js/contactform.js', __FILE__ ), array('jquery') );
            wp_localize_script( 'contactform-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
        }
        public static function inc_css_file() {

            global $wp_styles;
            $wp_styles->registered = array();
            wp_enqueue_style( 'fonts-googleapis-Open-Sans', '//fonts.googleapis.com/css?family=Open+Sans:400,700%7CPoppins:400,500', false, NULL, 'all' );
            wp_enqueue_style( 'font-awesome', plugins_url( 'assets/css/font-awesome-4.7.0/css/font-awesome.min.css', __FILE__ ), false, NULL, 'all' );

            wp_enqueue_style( 'bootstrap-min', plugins_url( 'assets/css/bootstrap.min.css?r=1', __FILE__ ), false, NULL, 'all' );

            wp_enqueue_style( 'jquery-classycountdown', plugins_url( 'assets/css/jquery.classycountdown.css', __FILE__ ), false, NULL, 'all' );
            wp_enqueue_style( 'styles', plugins_url( 'assets/css/styles.css?x=4', __FILE__ ), false, NULL, 'all'  );
            wp_enqueue_style( 'responsive', plugins_url( 'assets/css/responsive.css?x=5', __FILE__ ) , false, NULL, 'all' );
            $options = get_option( 'MM_And_SUC_Free_options' );
            if(isset($options['MM_And_SUC_Free_option_type_of_bg']) && $options['MM_And_SUC_Free_option_type_of_bg'] == 2){

                $text_path = pathinfo($options['MM_And_SUC_Free_textures']);

                wp_enqueue_style( 'MM_And_SUC_Free_textures', plugins_url( 'textures/'.$text_path['dirname'].'/'.$text_path['filename'].'.css', __FILE__ ) , false, NULL, 'all' );
            }
        }

        public function UserConditional($options = array()){

            if(empty($options)){
                return true; //Site opened
            }
            if(!isset($options['MM_And_SUC_Free_status']) || $options['MM_And_SUC_Free_status'] == 0){
                return true; //Site opened
            }
            if(is_admin()){
                return true; //Site opened
            }
            if ( $GLOBALS['pagenow'] === 'wp-login.php'  ) {
                return true; //Site opened
            }
            if(is_user_logged_in()){

                //No user rule selected, but the user is logged in
                if(!isset($options['MM_And_SUC_Free_role']) || (isset($options['MM_And_SUC_Free_role']) && empty($options['MM_And_SUC_Free_role']))){
                    return true; //Site opened
                }

                //Some rules selected
                if(isset($options['MM_And_SUC_Free_role']) && !empty($options['MM_And_SUC_Free_role'])){
                    $user = wp_get_current_user();
                    $in_role = false;
                    foreach($options['MM_And_SUC_Free_role'] as $name){
                        if ( in_array( strtolower($name), (array) $user->roles ) ) {
                            $in_role = true; //Site opened
                        }
                    }
                    if(!$in_role){
                        return true; //Site opened
                    }
                }
            }

            return false; //Site closed
        }
    }
    new MM_And_SUC_Free_page_template();
}