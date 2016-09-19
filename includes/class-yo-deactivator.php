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
        global $yopifyYoBaseUrl, $yopifyYoVersion;

        $yoConfigs = getYoConfigs();
        extract($yoConfigs);

        $siteUrl = home_url();
        $yopifyYoAccessToken = yopify_yo_get_access_token();

        $signedPayload = yopify_yo_encrypt_data([
            'url'          => $siteUrl,
            'email'        => get_option('admin_email'),
            'name'         => get_option('blogname'),
            'access_token' => $yopifyYoAccessToken
        ]);

        $url = $yopifyYoBaseUrl . '/capture/webhook?topic=store.app.uninstalled&signed_payload=' . urlencode($signedPayload);

        $http_args = array(
            'body'        => json_encode([]),
            'headers'     => array(
                'Content-Type' => 'application/json'
            ),
            'httpversion' => '1.0',
            'timeout'     => 60
        );

        return $response = wp_remote_post($url, $http_args);
    }

}
