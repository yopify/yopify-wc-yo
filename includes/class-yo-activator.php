<?php

/**
 * Fired during plugin activation
 *
 * @link       https://yopify.com
 * @since      1.0.0
 *
 * @package    Yopify Yo
 * @subpackage Yopify/Yo/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Yopify Yo
 * @subpackage Yopify/Yo/includes
 * @author     Yopify
 */
class Yo_Activator
{


    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate()
    {
        global $yopifyYoBaseUrl;

        $yoConfigs = getYoConfigs();
        extract($yoConfigs);

        $yopifyYoAccessToken = yopify_yo_get_access_token();

        $siteUrl = home_url();

        $payload = [
            'url'          => $siteUrl,
            'email'        => get_option('admin_email'),
            'name'         => get_option('blogname'),
            'access_token' => $yopifyYoAccessToken,
            'activate'     => true
        ];

        $signedPayload = yopify_yo_encrypt_data($payload);

        $yoUrl = $yopifyYoBaseUrl . '/auth?redirect=0&signed_payload=' . urlencode($signedPayload);

        $response = wp_remote_get($yoUrl, [
            'sslverify' => false,
            'timeout'   => 120,
        ]);

        if ( ! is_wp_error($response) && isset($response['body']) && $response['body']) {

            $responseBody = json_decode($response['body']);
            if (isset($responseBody->client_id) && $responseBody->client_id) {

                // Get store id
                $clientId = $responseBody->client_id;

                if ($clientId) {
                    update_option('yopify_yo_client_id', $clientId, true);
                }
            }
        }

        return $response;
    }

}
