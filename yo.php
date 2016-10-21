<?php

/**
 * Plugin Name:       Yo - Display Recent Sales in Real Time
 * Plugin URI:        https://yopify.com
 * Description:       Instantly replicate the atmosphere of a busy retail store and give your visitors the motivation and social proof to BUY!
 * Version:           2.0
 * Author:            Yopify
 * Author URI:        http://yopify.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       yopify_wc_yo
 * Domain Path:       /languages
 * @package           Yopify Yo
 */

// If this file is called directly, abort.
if ( ! defined('WPINC')) {
    die;
}

define('YOPIFY_YO_BASE_URL', 'https://yopify.com/api/yo');
define('YOPIFY_YO_VERSION', '1.0');
define('YOPIFY_YO_PLUGIN_URL', plugin_dir_url(__FILE__));

if ( ! class_exists('YopifyYo_Client')) {
    require_once plugin_dir_path(__FILE__) . 'includes/sdk/Client.php';
    require_once plugin_dir_path(__FILE__) . 'includes/sdk/Event.php';
}

require plugin_dir_path(__FILE__) . 'includes/yo-routes-and-functions.php';


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-yo-activator.php
 */
function on_yo_activate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-yo-activator.php';
    Yo_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-yo-deactivator.php
 */
function on_yo_deactivate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-yo-deactivator.php';
    Yo_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'on_yo_activate');
register_deactivation_hook(__FILE__, 'on_yo_deactivate');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-yo.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function yopify_yo_bootstrap()
{
    $plugin = new Yo();
    $plugin->run();
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    yopify_yo_bootstrap();
}else {
    add_action('admin_notices', function (){
        $class = 'notice notice-error';
        $message = __('<strong>Yo - Display Recent Sales in Real Time</strong> plugin requires to have <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a> plugin installed.',
            'woocommerce-plugin-yo');

        printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
    });
}