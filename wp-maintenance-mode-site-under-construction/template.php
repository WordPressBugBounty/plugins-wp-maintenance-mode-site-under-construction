<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly


if (! class_exists('MM_And_SUC_Free_page_template')) {
    class MM_And_SUC_Free_page_template
    {


        public function __construct()
        {
            add_action('init', array($this, 'template'));
            add_action('wp_ajax_contactform_action', array($this, 'ajax_contactform_action_callback'));
            add_action('wp_ajax_nopriv_contactform_action', array($this, 'ajax_contactform_action_callback'));
        }
        public function template()
        {
            $options = get_option('MM_And_SUC_Free_options');

            if ($this->UserConditional($options)) {
                return;
            }

            $this->remove_header_tage_run_hook();
            add_action('wp_enqueue_scripts', array($this, 'inc_js_file'));
            add_action('wp_enqueue_scripts', array($this, 'inc_css_file'));

            // Handle Slim Mode
            if (isset($options['MM_And_SUC_Free_timer_mode']) && $options['MM_And_SUC_Free_timer_mode'] === 'slim_mode') {
                $slim_title = $options['MM_And_SUC_Free_slim_title'] ?? __('Under Maintenance', 'wp-maintenance-mode-site-under-construction');
                $slim_text = $options['MM_And_SUC_Free_slim_text'] ?? __('This site is under maintenance, We’re working hard to improve your experience. Stay tuned as we count down to launch day!', 'wp-maintenance-mode-site-under-construction');
?>

                <!DOCTYPE HTML>
                <html lang="en">

                <head>
                    <title><?php echo esc_html(get_option('blogname')); ?></title>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <?php wp_head(); ?>
                    <style>
                        /* General Reset */
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }

                        /* Body */
                        html,
                        body {
                            background-color: #f4f4f4;
                            /* Light gray background */
                            font-family: Arial, sans-serif;
                        }

                        /* Container */
                        .maintenance-box {
                            background-color: #ffffff;
                            /* White background */
                            border: 1px solid #ddd;
                            /* Light border */
                            /* Subtle shadow */
                            max-width: 780px;
                            /* Increase width */
                            margin: 50px auto;
                            /* Top margin and horizontal centering */
                            padding: 30px;
                            text-align: left;
                        }

                        /* Header */
                        .maintenance-box h1 {
                            font-size: 1.3em;
                            /* Smaller font size */
                            font-weight: normal;
                            /* Regular weight */
                            color: #666666;
                            /* Grey color */
                            margin-bottom: 10px;
                            border-bottom: 1px solid #ddd;
                            /* Horizontal line */
                            padding-bottom: 10px;
                            padding-top: 20px;
                            font-weight: bold;
                        }

                        /* Description */
                        .maintenance-box p {
                            font-size: 0.9em;
                            /* Slightly larger text */
                            color: #333;
                            /* Lighter gray text */
                            margin-top: 10px;
                            line-height: 1.5;
                            /* Improved readability */
                            padding-top: 20px;

                        }
                    </style>
                </head>

                <body>
                    <div class="maintenance-box">
                        <h1><?php echo esc_html($slim_title ?: 'Under Maintenance'); ?></h1>
                        <p><?php echo esc_html($slim_text ?: "This site is under maintenance. We're working hard to improve your experience. Stay tuned as we count down to launch day!"); ?></p>
                    </div>
                    <?php wp_footer(); ?>
                </body>

                </html>
            <?php
                exit;
            }




            // Non-Slim Modes
            $timer_mode = $options['MM_And_SUC_Free_timer_mode'] ?? 'timer_with_contact';
            $timer_style = $options['MM_And_SUC_Free_timer_style'] ?? 'colored_background';

            if ($timer_mode !== 'slim_mode' && $timer_style === 'colored_background') {
                $background_color = $options['MM_And_SUC_Free_colored_background_color'] ?? '#ffd54f';
                $countdown_title = $options['MM_And_SUC_Free_title'] ?? 'Countdown Timer';
                $countdown_date = isset($options['MM_And_SUC_Free_date'])
                    ? (new DateTime($options['MM_And_SUC_Free_date']))->format('Y-m-d\TH:i:s')
                    : '2024-12-31T23:59:59';
            ?>
                <!DOCTYPE HTML>
                <html lang="en">

                <head>
                    <title><?php echo esc_html(get_option('blogname')); ?></title>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <?php wp_head(); ?>
                    <style>
                        /* General Reset */
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }

                        /* Body */
                        html,
                        body {

                            overflow: hidden;
                        }
                    </style>
                </head>

                <body>
                    <style>
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }

                        /* Full Page Layout */
                        html,
                        body {
                            height: 100%;
                            /* Full height */
                            overflow: hidden;
                            /* Prevent scrolling */
                            font-family: Arial, sans-serif;
                        }

                        body {
                            font-family: Arial, sans-serif;
                            margin: 0;
                        }

                        .countdown-container {
                            text-align: center;
                            color: #333;
                            margin-bottom: 20px;
                        }

                        .countdown-container h1 {
                            font-size: 2em;
                            text-transform: uppercase;
                            margin-bottom: 20px;
                        }

                        .countdown-container ul {
                            list-style: none;
                            padding: 0;
                            display: flex;
                            justify-content: center;
                            gap: 20px;
                        }

                        .countdown-container li {
                            text-align: center;
                        }

                        .countdown-container li span {
                            display: block;
                            font-size: 2em;
                            font-weight: bold;
                        }
                    </style>
                    <div class="main-area" style="overflow:hidden;">
                        <div class="container-fluid full-height position-static">
                            <?php if ($timer_mode === 'timer_with_contact') { ?>
                                <!-- Left Section (Contact Form) -->
                                <section class="left-section col full-height">
                                    <div class="display-table">
                                        <div class="main-content" style="padding: 10px;">
                                            <h3 class="title"><b><?php _e('Contact us:', 'wp-maintenance-mode-site-under-construction'); ?></b></h3>
                                            <p><?php _e('We will be back soon! For more information, feel free to contact us anytime', 'wp-maintenance-mode-site-under-construction'); ?></p>
                                            <div class="email-input-area">
                                                <form class="contact2-form validate-form" id="contactform">
                                                    <div class="wrap-input2 validate-input" data-validate="Name is required">
                                                        <label for="name">Your Name</label>
                                                        <input class="input2" id="name" type="text" name="name" placeholder="Enter your full name">
                                                    </div>
                                                    <div class="wrap-input2 validate-input" data-validate="Valid email is required: ex@abc.xyz">
                                                        <label for="email">Your Email Address</label>
                                                        <input class="input2" id="email" type="text" name="email" placeholder="Enter your email address (e.g., example@example.com)">
                                                    </div>
                                                    <div class="wrap-input2 validate-input" data-validate="Message is required">
                                                        <label for="message">Your Message</label>
                                                        <textarea class="input2" name="message" id="message" placeholder="Type your message or query here"></textarea>
                                                    </div>
                                                    <div id="contact-msg"></div>
                                                    <div class="container-contact2-form-btn">
                                                        <div class="wrap-contact2-form-btn">
                                                            <div class="contact2-form-bgbtn"></div>
                                                            <button class="contact2-form-btn" id="contactbutton">
                                                                <?php esc_html_e('Send Message', 'wp-maintenance-mode-site-under-construction'); ?>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <?php echo wp_nonce_field('contactform_action', '_acf_nonce', true, false); ?>
                                                    <input type="hidden" name="action" value="contactform_action" />
                                                </form>
                                            </div><!-- email-input-area -->
                                        </div><!-- main-content -->
                                    </div><!-- display-table -->
                                </section><!-- left-section -->

                                <!-- Right Section -->
                                <section class="right-section half_width_mode"
                                    <?php if ($timer_style === 'colored_background') { ?>

                                    style="background-color: <?php echo esc_attr($background_color); ?>; display : flex; justify-content : center; align-items : center; "
                                    <?php } elseif ($timer_style === 'background_image' && $options['MM_And_SUC_Free_option_type_of_bg'] == 2) {
                                        $filename_big = pathinfo(MM_And_SUC_Free_PLUGIN_URL . 'textures/' . $options['MM_And_SUC_Free_textures']);
                                        $file_big_name = $filename_big['dirname'] . '/' . $filename_big['filename'] . '-big.' . $filename_big['extension'];
                                    ?>
                                    style="background-image: url(<?php echo esc_attr($file_big_name); ?>);"
                                    <?php } elseif ($timer_style === 'background_image' && $options['MM_And_SUC_Free_option_type_of_bg'] == 1) { ?>
                                    style="background-image: url(<?php echo esc_attr(wp_get_attachment_image_src($options['MM_And_SUC_Free_image'], 'full')[0]); ?>);"
                                    <?php } ?>>

                                    <div class="countdown-container">
                                        <?php if ($timer_style === 'colored_background') { ?>
                                            <!-- Simple Timer -->
                                            <h1><?php echo esc_html($countdown_title); ?></h1>
                                            <ul id="countdown">
                                                <li><span id="days">--</span>Days</li>
                                                <li><span id="hours">--</span>Hours</li>
                                                <li><span id="minutes">--</span>Minutes</li>
                                                <li><span id="seconds">--</span>Seconds</li>
                                            </ul>
                                            <div class="cta-buttons">
                                                <?php
                                                $cta_options = get_option('MM_And_SUC_Free_options');
                                                if (!empty($cta_options['MM_And_SUC_Free_enable_cta'])) {
                                                    $bg_color = $cta_options['MM_And_SUC_Free_cta_bg_color'] ?? '#007BFF';
                                                    $text_color = $cta_options['MM_And_SUC_Free_cta_text_color'] ?? '#FFFFFF';
                                                    for ($i = 1; $i <= 4; $i++) {
                                                        $label = $cta_options["MM_And_SUC_Free_cta_label_$i"] ?? '';
                                                        $url = $cta_options["MM_And_SUC_Free_cta_url_$i"] ?? '';
                                                        if (!empty($label) && !empty($url)) {
                                                            echo '<a href="' . esc_url($url) . '" class="cta-button" style="background-color: ' . esc_attr($bg_color) . '; color: ' . esc_attr($text_color) . ';" target="_blank" rel="noopener noreferrer">' . esc_html($label) . '</a>';
                                                        }
                                                    }
                                                }
                                                ?>
                                            </div>

                                            <style>
                                                .cta-buttons {
                                                    display: flex;
                                                    justify-content: center;
                                                    gap: 20px;
                                                    margin-top: 30px;
                                                }

                                                .cta-button {
                                                    display: inline-block;
                                                    padding: 8px 30px;
                                                    background-color: #007BFF;
                                                    color: #FFFFFF;
                                                    text-decoration: none;
                                                    border-radius: 4px;
                                                    font-size: 14px;
                                                    transition: background-color 0.3s ease, transform 0.2s ease;
                                                    text-align: center;
                                                    min-width: 100px;
                                                }

                                                .cta-button:hover {
                                                    background-color: #0056b3;
                                                    transform: scale(1.05);
                                                }
                                            </style>
                                        <?php } elseif ($timer_style === 'background_image') { ?>
                                            <!-- Rounded Timer -->
                                            <div id="rounded-countdown">
                                                <h3 class="title responsive_title"><b><?php echo esc_html($options['MM_And_SUC_Free_title']); ?></b></h3>
                                                <?php
                                                $date_time_wp = (array)current_datetime();
                                                $date = new DateTime($date_time_wp['date']);
                                                $date2 = new DateTime($options['MM_And_SUC_Free_date']);
                                                $diff = $date2->getTimestamp() - $date->getTimestamp();
                                                ?>
                                                <div class="countdown" data-remaining-sec="<?php echo esc_attr($diff); ?>"></div>
                                                <p class="des responsive_des" style="font-size: 18px;"><?php echo esc_html($options['MM_And_SUC_Free_description']); ?></p>
                                            </div>
                                            <div class="cta-buttons">
                                                <?php
                                                $cta_options = get_option('MM_And_SUC_Free_options');
                                                if (!empty($cta_options['MM_And_SUC_Free_enable_cta'])) {
                                                    $bg_color = $cta_options['MM_And_SUC_Free_cta_bg_color'] ?? '#007BFF';
                                                    $text_color = $cta_options['MM_And_SUC_Free_cta_text_color'] ?? '#FFFFFF';
                                                    for ($i = 1; $i <= 4; $i++) {
                                                        $label = $cta_options["MM_And_SUC_Free_cta_label_$i"] ?? '';
                                                        $url = $cta_options["MM_And_SUC_Free_cta_url_$i"] ?? '';
                                                        if (!empty($label) && !empty($url)) {
                                                            echo '<a href="' . esc_url($url) . '" class="cta-button" style="background-color: ' . esc_attr($bg_color) . '; color: ' . esc_attr($text_color) . ';" target="_blank" rel="noopener noreferrer">' . esc_html($label) . '</a>';
                                                        }
                                                    }
                                                }
                                                ?>
                                            </div>


                                        <?php } ?>
                                    </div>
                                </section><!-- right-section -->
                            <?php } else { ?>
                                <!-- Full Width Section -->
                                <sec class="right-section full_screen_mode"
                                    <?php if ($timer_style === 'colored_background') { ?>
                                    style="background-color: <?php echo esc_attr($background_color); ?>; display : flex; justify-content : center; align-items : center;"
                                    <?php } elseif ($timer_style === 'background_image') { ?>
                                    style="background-image: url(<?php echo esc_attr(wp_get_attachment_image_src($options['MM_And_SUC_Free_image'], 'full')[0]); ?>);"
                                    <?php } ?>>
                                    <div class="countdown-container">
                                        <?php if ($timer_style === 'colored_background') { ?>
                                            <!-- Simple Timer -->
                                            <h1><?php echo esc_html($countdown_title); ?></h1>
                                            <ul id="countdown">
                                                <li><span id="days">--</span>Days</li>
                                                <li><span id="hours">--</span>Hours</li>
                                                <li><span id="minutes">--</span>Minutes</li>
                                                <li><span id="seconds">--</span>Seconds</li>
                                            </ul>
                                            <div class="cta-buttons">
                                                <?php
                                                $cta_options = get_option('MM_And_SUC_Free_options');
                                                if (!empty($cta_options['MM_And_SUC_Free_enable_cta'])) {
                                                    $bg_color = $cta_options['MM_And_SUC_Free_cta_bg_color'] ?? '#007BFF';
                                                    $text_color = $cta_options['MM_And_SUC_Free_cta_text_color'] ?? '#FFFFFF';
                                                    for ($i = 1; $i <= 4; $i++) {
                                                        $label = $cta_options["MM_And_SUC_Free_cta_label_$i"] ?? '';
                                                        $url = $cta_options["MM_And_SUC_Free_cta_url_$i"] ?? '';
                                                        if (!empty($label) && !empty($url)) {
                                                            echo '<a href="' . esc_url($url) . '" class="cta-button" style="background-color: ' . esc_attr($bg_color) . '; color: ' . esc_attr($text_color) . ';" target="_blank" rel="noopener noreferrer">' . esc_html($label) . '</a>';
                                                        }
                                                    }
                                                }
                                                ?>
                                            </div>

                                            <style>
                                                .body {
                                                    overflow: hidden;
                                                }

                                                .cta-buttons {
                                                    display: flex;
                                                    justify-content: center;
                                                    gap: 20px;
                                                    margin-top: 30px;
                                                }

                                                .cta-button {
                                                    display: inline-block;
                                                    padding: 8px 30px;
                                                    background-color: #007BFF;
                                                    color: #FFFFFF;
                                                    text-decoration: none;
                                                    border-radius: 4px;
                                                    font-size: 14px;
                                                    transition: background-color 0.3s ease, transform 0.2s ease;
                                                    text-align: center;
                                                    min-width: 100px;
                                                }

                                                .cta-button:hover {
                                                    background-color: #0056b3;
                                                    transform: scale(1.05);
                                                }
                                            </style>
                                        <?php } elseif ($timer_style === 'background_image') { ?>
                                            <!-- Rounded Timer -->
                                            <div id="rounded-countdown">
                                                <h3 class="title responsive_title"><b><?php echo esc_html($options['MM_And_SUC_Free_title']); ?></b></h3>
                                                <?php
                                                $date_time_wp = (array)current_datetime();
                                                $date = new DateTime($date_time_wp['date']);
                                                $date2 = new DateTime($options['MM_And_SUC_Free_date']);
                                                $diff = $date2->getTimestamp() - $date->getTimestamp();
                                                ?>
                                                <div class="countdown" data-remaining-sec="<?php echo esc_attr($diff); ?>"></div>
                                                <p class="des responsive_des" style="font-size: 18px;"><?php echo esc_html($options['MM_And_SUC_Free_description']); ?></p>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    </section><!-- right-section -->
                                <?php } ?>
                        </div><!-- container-fluid -->
                    </div><!-- main-area -->

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const countdownDate = new Date("<?php echo esc_js($countdown_date); ?>").getTime();

                            if (!isNaN(countdownDate)) {
                                const x = setInterval(function() {
                                    const now = new Date().getTime();
                                    const distance = countdownDate - now;

                                    if (distance < 0) {
                                        clearInterval(x);
                                        document.querySelector("#countdown").innerHTML = "The countdown has ended!";
                                    } else {
                                        document.getElementById("days").textContent = Math.floor(distance / (1000 * 60 * 60 * 24));
                                        document.getElementById("hours").textContent = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                        document.getElementById("minutes").textContent = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                                        document.getElementById("seconds").textContent = Math.floor((distance % (1000 * 60)) / 1000);
                                    }
                                }, 1000);
                            }
                        });
                    </script>
                </body>



                </html>

            <?php
                exit;
            } elseif ($timer_mode !== 'slim_mode' && $timer_style === 'background_image') {
                $timer_mode = $options['MM_And_SUC_Free_timer_mode'] ?? 'timer_without_contact';
            ?>
                <!DOCTYPE HTML>
                <html lang="en">

                <head>
                    <title><?php echo esc_html(get_option('blogname')); ?></title>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <?php wp_head(); ?>
                    <style>
                        /* General Reset */
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }

                        /* Body */
                        html,
                        body {
                            overflow: hidden;
                        }
                    </style>
                </head>


                <body>
                    <div class="main-area" style="overflow : hidden ;">
                        <div class="container-fluid full-height position-static">
                            <?php if ($timer_mode === 'timer_with_contact') { ?>
                                <!-- Left Section (Contact Form) -->
                                <section class="left-section col full-height">
                                    <div class="display-table">
                                        <div class="main-content" style="padding: 10px;">
                                            <h3 class="title"><b><?php _e('Contact us:', 'wp-maintenance-mode-site-under-construction'); ?></b></h3>
                                            <p><?php _e('We will be back soon! For more information, feel free to contact us anytime', 'wp-maintenance-mode-site-under-construction'); ?></p>
                                            <div class="email-input-area">
                                                <form class="contact2-form validate-form" id="contactform">
                                                    <div class="wrap-input2 validate-input" data-validate="Name is required">
                                                        <label for="name">Your Name</label>
                                                        <input class="input2" id="name" type="text" name="name" placeholder="Enter your full name">
                                                        <span class="focus-input2"></span>
                                                    </div>
                                                    <div class="wrap-input2 validate-input" data-validate="Valid email is required: ex@abc.xyz">
                                                        <label for="email">Your Email Address</label>
                                                        <input class="input2" id="email" type="text" name="email" placeholder="Enter your email address (e.g., example@example.com)">
                                                        <span class="focus-input2"></span>
                                                    </div>
                                                    <div class="wrap-input2 validate-input" data-validate="Message is required">
                                                        <label for="message">Your Message</label>
                                                        <textarea class="input2" name="message" id="message" placeholder="Type your message or query here"></textarea>
                                                        <span class="focus-input2"></span>
                                                    </div>
                                                    <div id="contact-msg"></div>
                                                    <div class="container-contact2-form-btn">
                                                        <div class="wrap-contact2-form-btn">
                                                            <div class="contact2-form-bgbtn"></div>
                                                            <button class="contact2-form-btn" id="contactbutton">
                                                                <?php esc_html_e('Send Message', 'wp-maintenance-mode-site-under-construction'); ?>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <?php echo wp_nonce_field('contactform_action', '_acf_nonce', true, false); ?>
                                                    <input type="hidden" name="action" value="contactform_action" />
                                                </form>
                                            </div><!-- email-input-area -->
                                        </div><!-- main-content -->
                                    </div><!-- display-table -->
                                </section><!-- left-section -->

                                <?php
                                $width_class = "half_width_mode";
                                ?>
                                <!-- Right Section (Background Image with Countdown Timer) -->
                                <section class="right-section <?php echo esc_attr($width_class); ?>"
                                    <?php
                                    if ($options['MM_And_SUC_Free_option_type_of_bg'] == 2) {
                                        $filename_big = pathinfo(MM_And_SUC_Free_PLUGIN_URL . 'textures/' . $options['MM_And_SUC_Free_textures']);
                                        $file_big_name = $filename_big['dirname'] . '/' . $filename_big['filename'] . '-big.' . $filename_big['extension'];
                                    ?>
                                    style="background-image: url(<?php
                                                                    if (isset($options['MM_And_SUC_Free_textures']) && $options['MM_And_SUC_Free_textures'] != "") {
                                                                        echo esc_attr($file_big_name);
                                                                    }
                                                                    ?>)"
                                    <?php } else { ?>
                                    style="background-image: url(<?php
                                                                    if (isset($options['MM_And_SUC_Free_image']) && $options['MM_And_SUC_Free_image'] != "") {
                                                                        echo esc_attr(wp_get_attachment_image_src($options['MM_And_SUC_Free_image'], 'full')[0]);
                                                                    } else {
                                                                        echo esc_attr(MM_And_SUC_Free_PLUGIN_URL . '/assets/images/countdown-1-1000x1000.jpg');
                                                                    }
                                                                    ?>)"
                                    <?php }
                                    if (isset($options['MM_And_SUC_Free_textures']) && $options['MM_And_SUC_Free_textures'] != "") { ?>>
                                <?php
                                    }

                                ?>
                                <div class="display-table center-text">
                                    <div class="display-table-cell">
                                        <div id="rounded-countdown">
                                            <h3 class="title responsive_title"><b><?php echo esc_html($options['MM_And_SUC_Free_title']); ?></b></h3>
                                            <?php
                                            $date_time_wp = (array)current_datetime();
                                            $date = new DateTime($date_time_wp['date']);
                                            $date2 = new DateTime($options['MM_And_SUC_Free_date']);
                                            $diff = $date2->getTimestamp() - $date->getTimestamp();
                                            ?>
                                            <div class="countdown" data-remaining-sec="<?php echo esc_attr($diff); ?>"></div>
                                            <p class="des responsive_des" style="font-size: 18px;"><?php echo esc_html($options['MM_And_SUC_Free_description']); ?></p>
                                        </div>
                                        <div class="cta-buttons">
                                            <?php
                                            $cta_options = get_option('MM_And_SUC_Free_options');
                                            if (!empty($cta_options['MM_And_SUC_Free_enable_cta'])) {
                                                $bg_color = $cta_options['MM_And_SUC_Free_cta_bg_color'] ?? '#007BFF';
                                                $text_color = $cta_options['MM_And_SUC_Free_cta_text_color'] ?? '#FFFFFF';
                                                for ($i = 1; $i <= 4; $i++) {
                                                    $label = $cta_options["MM_And_SUC_Free_cta_label_$i"] ?? '';
                                                    $url = $cta_options["MM_And_SUC_Free_cta_url_$i"] ?? '';
                                                    if (!empty($label) && !empty($url)) {
                                                        echo '<a href="' . esc_url($url) . '" class="cta-button" style="background-color: ' . esc_attr($bg_color) . '; color: ' . esc_attr($text_color) . ';" target="_blank" rel="noopener noreferrer">' . esc_html($label) . '</a>';
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>

                                    </div>
                                </div>

                                <style>
                                    .cta-buttons {
                                        display: flex;
                                        justify-content: center;
                                        gap: 20px;
                                        margin-top: 30px;
                                    }

                                    .cta-button {
                                        display: inline-block;
                                        padding: 8px 30px;
                                        background-color: #007BFF;
                                        color: #FFFFFF;
                                        text-decoration: none;
                                        border-radius: 4px;
                                        font-size: 14px;
                                        transition: background-color 0.3s ease, transform 0.2s ease;
                                        text-align: center;
                                        min-width: 100px;
                                    }

                                    .cta-button:hover {
                                        background-color: #0056b3;
                                        transform: scale(1.05);
                                    }
                                </style>
                                </section><!-- right-section -->
                            <?php } else { ?>
                                <!-- Full Width Background Image with Countdown Timer -->
                                <section class="right-section full_screen_mode"
                                    <?php if ($options['MM_And_SUC_Free_option_type_of_bg'] == 2) {
                                        $filename_big = pathinfo(MM_And_SUC_Free_PLUGIN_URL . 'textures/' . $options['MM_And_SUC_Free_textures']);
                                        $file_big_name = $filename_big['dirname'] . '/' . $filename_big['filename'] . '-big.' . $filename_big['extension'];
                                    ?>
                                    style="background-image: url(<?php if (isset($options['MM_And_SUC_Free_textures']) && $options['MM_And_SUC_Free_textures'] != "") {
                                                                        esc_attr_e($file_big_name);
                                                                    } ?>)"
                                    <?php } else { ?>
                                    style="background-image: url(<?php if (isset($options['MM_And_SUC_Free_image']) && $options['MM_And_SUC_Free_image'] != "") {
                                                                        echo esc_attr(wp_get_attachment_image_src($options['MM_And_SUC_Free_image'], 'full')[0]);
                                                                    } else {
                                                                        echo esc_attr(MM_And_SUC_Free_PLUGIN_URL); ?>/assets/images/countdown-1-1000x1000.jpg)<?php } ?>)">
                                    <?php }
                                    if (isset($options['MM_And_SUC_Free_textures']) && $options['MM_And_SUC_Free_textures'] != "") { ?>>
                                <?php
                                    }

                                ?>

                                <div class="display-table center-text">
                                    <div class="display-table-cell">
                                        <div id="rounded-countdown">
                                            <h3 class="title responsive_title"><b><?php echo esc_html($options['MM_And_SUC_Free_title']); ?></b></h3>
                                            <?php
                                            $date_time_wp = (array)current_datetime();
                                            $date = new DateTime($date_time_wp['date']);
                                            $date2 = new DateTime($options['MM_And_SUC_Free_date']);
                                            $diff = $date2->getTimestamp() - $date->getTimestamp();
                                            ?>
                                            <div class="countdown" data-remaining-sec="<?php echo esc_attr($diff); ?>"></div>
                                            <p class="des responsive_des" style="font-size: 18px;"><?php echo esc_html($options['MM_And_SUC_Free_description']); ?></p>
                                        </div>
                                        <div class="cta-buttons">
                                            <?php
                                            $cta_options = get_option('MM_And_SUC_Free_options');
                                            if (!empty($cta_options['MM_And_SUC_Free_enable_cta'])) {
                                                $bg_color = $cta_options['MM_And_SUC_Free_cta_bg_color'] ?? '#007BFF';
                                                $text_color = $cta_options['MM_And_SUC_Free_cta_text_color'] ?? '#FFFFFF';
                                                for ($i = 1; $i <= 4; $i++) {
                                                    $label = $cta_options["MM_And_SUC_Free_cta_label_$i"] ?? '';
                                                    $url = $cta_options["MM_And_SUC_Free_cta_url_$i"] ?? '';
                                                    if (!empty($label) && !empty($url)) {
                                                        echo '<a href="' . esc_url($url) . '" class="cta-button" style="background-color: ' . esc_attr($bg_color) . '; color: ' . esc_attr($text_color) . ';" target="_blank" rel="noopener noreferrer">' . esc_html($label) . '</a>';
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <style>
                                    .cta-buttons {
                                        display: flex;
                                        justify-content: center;
                                        gap: 20px;
                                        margin-top: 30px;
                                    }

                                    .cta-button {
                                        display: inline-block;
                                        padding: 8px 30px;
                                        background-color: #007BFF;
                                        color: #FFFFFF;
                                        text-decoration: none;
                                        border-radius: 4px;
                                        font-size: 14px;
                                        transition: background-color 0.3s ease, transform 0.2s ease;
                                        text-align: center;
                                        min-width: 100px;
                                    }

                                    .cta-button:hover {
                                        background-color: #0056b3;
                                        transform: scale(1.05);
                                    }
                                </style>

                        </div>
                        <!-- End CTA Buttons -->
                        </section><!-- right-section -->
                    <?php } ?>

                    </div>
                    </div>

                    </div>
                    <?php do_action('MM_And_SUC_Free_footer'); ?>
                    <?php wp_footer(); ?>
                    <style>
                        .cta-buttons {
                            display: flex;
                            justify-content: center;
                            gap: 20px;
                            margin-top: 30px;
                        }

                        .cta-button {
                            display: inline-block;
                            padding: 8px 30px;
                            /* Reduced height, increased width */
                            background-color: #007BFF;
                            color: #FFFFFF;
                            text-decoration: none;
                            border-radius: 4px;
                            /* Less curve */
                            font-size: 14px;
                            /* Adjust font size if needed */
                            transition: background-color 0.3s ease, transform 0.2s ease;
                            text-align: center;
                            min-width: 100px;
                            /* Ensures buttons are consistently wider */
                        }

                        .cta-button:hover {
                            background-color: #0056b3;
                            transform: scale(1.05);
                        }
                    </style>
                </body>


                </html>
<?php
            }
            exit;
        }

        public function ajax_contactform_action_callback()
        {
            $error = '';
            $status = 'error';

            if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['message'])) {
                $error = 'All fields are required.';
            } else {
                if (!wp_verify_nonce($_POST['_acf_nonce'], $_POST['action'])) {
                    $error = esc_html__('Verification error, try again.', 'wp-maintenance-mode-site-under-construction');
                } else {
                    $name = sanitize_text_field($_POST['name']);
                    $email = sanitize_email($_POST['email']);
                    $subject = 'New Contact Form Submission';
                    $message = "Name: $name\nEmail: $email\nMessage:\n" . sanitize_textarea_field($_POST['message']);
                    $message .= "\nIP Address: " . sanitize_text_field($_SERVER['REMOTE_ADDR']);

                    // admin email address
                    $to = get_option('admin_email');
                    $headers = [
                        'From: ' . get_option('blogname') . " <$to>",
                        'Reply-To: ' . $email,
                        'Content-Type: text/plain; charset=UTF-8',
                    ];

                    if (wp_mail($to, $subject, $message, $headers)) {
                        $status = 'success';
                        $error = __('Thank you for reaching out! We will get back to you within 24-48 hours.', 'wp-maintenance-mode-site-under-construction');
                    } else {
                        $error = esc_html__('An error occurred while sending your message. Please try again.', 'wp-maintenance-mode-site-under-construction');
                    }
                }
            }

            $response = ['status' => $status, 'errmessage' => $error];
            header("Content-Type: application/json");
            echo json_encode($response);
            wp_die(); // Proper WordPress AJAX termination
        }

        public function remove_header_tage_run_hook()
        {
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
        public function inc_js_file()
        {
            wp_register_script('tether-min', plugins_url('assets/js/tether.min.js', __FILE__), array('jquery'), NULL, true);
            wp_enqueue_script('tether-min');
            wp_register_script('jquery-classycountdown', plugins_url('assets/js/jquery.classycountdown.js', __FILE__), array('jquery'), NULL, true);
            wp_enqueue_script('jquery-classycountdown');
            wp_register_script('jquery-knob', plugins_url('assets/js/jquery.knob.js', __FILE__), array('jquery'), NULL, true);
            wp_enqueue_script('jquery-knob');
            wp_register_script('jquery-throttle', plugins_url('assets/js/jquery.throttle.js', __FILE__), array('jquery'), NULL, true);
            wp_enqueue_script('jquery-throttle');
            wp_register_script('scripts', plugins_url('assets/js/scripts.js', __FILE__), array('jquery'), NULL, true);
            wp_enqueue_script('scripts');

            wp_enqueue_script('contactform-script', plugins_url('assets/js/contactform.js', __FILE__), array('jquery'));
            wp_localize_script('contactform-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
        }
        public static function inc_css_file()
        {

            global $wp_styles;
            $wp_styles->registered = array();
            wp_enqueue_style('fonts-googleapis-Open-Sans', '//fonts.googleapis.com/css?family=Open+Sans:400,700%7CPoppins:400,500', false, NULL, 'all');
            wp_enqueue_style('font-awesome', plugins_url('assets/css/font-awesome-4.7.0/css/font-awesome.min.css', __FILE__), false, NULL, 'all');

            wp_enqueue_style('bootstrap-min', plugins_url('assets/css/bootstrap.min.css?r=1', __FILE__), false, NULL, 'all');

            wp_enqueue_style('jquery-classycountdown', plugins_url('assets/css/jquery.classycountdown.css', __FILE__), false, NULL, 'all');
            wp_enqueue_style('styles', plugins_url('assets/css/styles.css?x=4', __FILE__), false, NULL, 'all');
            wp_enqueue_style('responsive', plugins_url('assets/css/responsive.css?x=5', __FILE__), false, NULL, 'all');
            $options = get_option('MM_And_SUC_Free_options');
            if (isset($options['MM_And_SUC_Free_option_type_of_bg']) && $options['MM_And_SUC_Free_option_type_of_bg'] == 2) {

                $text_path = pathinfo($options['MM_And_SUC_Free_textures']);

                wp_enqueue_style('MM_And_SUC_Free_textures', plugins_url('textures/' . $text_path['dirname'] . '/' . $text_path['filename'] . '.css', __FILE__), false, NULL, 'all');
            }
        }

        public function UserConditional($options = array())
        {

            if (empty($options)) {
                return true; //Site opened
            }
            if (!isset($options['MM_And_SUC_Free_status']) || $options['MM_And_SUC_Free_status'] == 0) {
                return true; //Site opened
            }
            if (is_admin()) {
                return true; //Site opened
            }
            if ($GLOBALS['pagenow'] === 'wp-login.php') {
                return true; //Site opened
            }
            // Block guest users if the plugin is active
            if (!is_user_logged_in()) {
                return false; // Site closed for guest users
            }
            if (is_user_logged_in()) {

                //No user rule selected, but the user is logged in
                if (!isset($options['MM_And_SUC_Free_role']) || (isset($options['MM_And_SUC_Free_role']) && empty($options['MM_And_SUC_Free_role']))) {
                    return true; //Site opened
                }

                //Some rules selected
                if (isset($options['MM_And_SUC_Free_role']) && !empty($options['MM_And_SUC_Free_role'])) {
                    $user = wp_get_current_user();
                    $in_role = false;
                    foreach ($options['MM_And_SUC_Free_role'] as $name) {
                        if (in_array(strtolower($name), (array) $user->roles)) {
                            $in_role = true; //Site opened
                        }
                    }
                    if (!$in_role) {
                        return true; //Site opened
                    }
                }
            }

            return false; //Site closed
        }
    }
    new MM_And_SUC_Free_page_template();
}
