<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://yopify.com
 * @since      1.0.0
 *
 * @package    Yopify Yo
 * @subpackage Yopify/Yo/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Yopify Yo
 * @subpackage Yopify/Yo/includes
 * @author     Yopify
 */
class Yo_Deactivator
{

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate()
    {
        delete_option('yopify_yo_wc_app_id');
        delete_option('yopify_yo_access_token');
        delete_option('yopify_yo_client_id');
    }

}
