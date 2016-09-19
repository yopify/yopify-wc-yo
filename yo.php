<?php

/**
 * Plugin Name:       Yo - Display Recent Sales in Real Time
 * Plugin URI:        https://yopify.com
 * Description:       Instantly replicate the atmosphere of a busy retail store and give your visitors the motivation and social proof to BUY!
 * Version:           1.0.0
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

$yopifyYoBaseUrl = 'https://yopify.com/wc/yo';
$yopifyYoVersion = '1.0.0';
$yopifyYoPublicKey = "-----BEGIN RSA PUBLIC KEY-----
MIGJAoGBAOBmLnZ3KrnNB39wUitQ1+RykjAHmKyz/r2/21XqyV847gh1JUQbNGHj
n29kUPki1FPbaVgDfd+RE+kAGsk020lOOFwKk/u9NyKzd1iewsxVHQzMsP1jZsRE
70nQFDVEVfaM6Fz+g3kSz6neBYzL030RzzhMGH2vi5CPVN7nYy6dAgMBAAE=
-----END RSA PUBLIC KEY-----";
$yopifyYoPluginUrl = str_replace(home_url(), '', plugins_url('/', __FILE__));

require plugin_dir_path(__FILE__) . 'includes/yo-routes-and-functions.php';

/**
 * Get yo configs
 * @return array
 */
function getYoConfigs()
{
    global $yopifyYoBaseUrl, $yopifyYoVersion, $yopifyYoPublicKey, $yopifyYoPluginUrl;

    return [
        'yopifyYoBaseUrl'   => $yopifyYoBaseUrl ? $yopifyYoBaseUrl : 'https://yopify.com/wc/yo',
        'yopifyYoVersion'   => $yopifyYoVersion ? $yopifyYoVersion : '1.0.0',
        'yopifyYoPublicKey' => $yopifyYoPublicKey
            ? $yopifyYoPublicKey
            : '-----BEGIN RSA PUBLIC KEY-----
MIGJAoGBAOBmLnZ3KrnNB39wUitQ1+RykjAHmKyz/r2/21XqyV847gh1JUQbNGHj
n29kUPki1FPbaVgDfd+RE+kAGsk020lOOFwKk/u9NyKzd1iewsxVHQzMsP1jZsRE
70nQFDVEVfaM6Fz+g3kSz6neBYzL030RzzhMGH2vi5CPVN7nYy6dAgMBAAE=
-----END RSA PUBLIC KEY-----',
        'yopifyYoPluginUrl' => $yopifyYoPluginUrl ? $yopifyYoPluginUrl : str_replace(home_url(), '', plugins_url('/', __FILE__))
    ];
}

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
            'woocommerce-plugin-fomo');

        printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
    });
}