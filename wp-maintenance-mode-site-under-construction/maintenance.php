<?php
/*
 * Plugin Name: WP Maintenance Mode & Site Under Construction

 * Plugin URI: https://wordpress.org/plugins/wp-maintenance-mode-site-under-construction

 * Description:  Lets tell your visitors about the maintenance time you want, also let them know when you are online again by showing a very beautiful counter

 * Version: 3.1

 * Author: wp-buy

 * Text Domain: wp-maintenance-mode-site-under-construction

 * Domain Path: /languages

 * Author URI: https://wordpress.org/plugins/wp-maintenance-mode-site-under-construction

 * License: GPL2

 */

//---------------------------------------------------------------------------------------------
//Load plugin textdomain to load translations
//---------------------------------------------------------------------------------------------
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if(!function_exists('MM_And_SUC_Free_load_textdomain')) {
    function MM_And_SUC_Free_load_textdomain()
    {
        load_plugin_textdomain('wp-maintenance-mode-site-under-construction', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    add_action('init', 'MM_And_SUC_Free_load_textdomain');
}
//--------------------------------------------------------------------------------------------

define( 'MM_And_SUC_Free_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MM_And_SUC_Free_PLUGIN_URL', plugin_dir_url(__FILE__) );

require_once( MM_And_SUC_Free_PLUGIN_DIR . '/admin/settings.php' );
require_once( MM_And_SUC_Free_PLUGIN_DIR . '/template.php' );

if(!function_exists('MM_And_SUC_Free_filter_action_links')) {
    function MM_And_SUC_Free_filter_action_links($links)
    {
        $links['settings'] = sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=MM_And_SUC_Free_Settings'), __('Settings', 'wp-maintenance-mode-site-under-construction'));
        return $links;
    }
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'MM_And_SUC_Free_filter_action_links', 10, 1);
}