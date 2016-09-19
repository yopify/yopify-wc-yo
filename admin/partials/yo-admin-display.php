<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Yopify Yo
 * @subpackage Yopify/Yo/admin/partials
 */

/**
 * Add menu page for admin
 */
function yopify_yo_admin_menu()
{
    add_menu_page('Yo', 'Yo', 'manage_options', 'yo', 'yopify_yo_init_page');
}

// Add menu
add_action('admin_menu', 'yopify_yo_admin_menu');


/**
 * Front end to show yo app
 */
function yopify_yo_init_page()
{
    global $yopifyYoBaseUrl, $yopifyYoPluginUrl;

    $yoConfigs = getYoConfigs();
    extract($yoConfigs);

    $siteUrl = home_url();
    $yopifyYoAccessToken = yopify_yo_get_access_token();

    // Verify Store on yo server
    $clientDetails = yopify_yo_get_client_id($siteUrl, $yopifyYoAccessToken);

    if (isset($clientDetails['success']) && $clientDetails['success']) {

        // Get store id
        $clientId = isset($clientDetails['client_id']) && $clientDetails['client_id'] ? $clientDetails['client_id'] : null;

        if ($clientId) {
            update_option('yopify_yo_client_id', $clientId, true);
        }

        $time = getUtcTimestamp();
        $yopifyYoAuthUrl = $yopifyYoBaseUrl . '/auth?url=' . urlencode($siteUrl) . '&t=' . $time . '&hmac=' . hash_hmac('sha256', $siteUrl . $time,
                $yopifyYoAccessToken);

        ?>
        <iframe id="yopifyYoBackEndIframe" src="<?php echo $yopifyYoAuthUrl; ?>" style="width:100%; min-height:500px;" frameborder="no"></iframe>
        <?php
    }else {

        $payload = [
            'url'          => $siteUrl,
            'email'        => get_option('admin_email'),
            'name'         => get_option('blogname'),
            'access_token' => $yopifyYoAccessToken,
            'plugin_url'   => $yopifyYoPluginUrl
        ];

        $signedPayload = yopify_yo_encrypt_data($payload);

        $yoUrl = $yopifyYoBaseUrl . '/auth?signed_payload=' . urlencode($signedPayload);

        ?>
        <div class="allow-access">
            <a href="https://yopify.com" target="_blank"><img src="https://yopify.com/images/yo/logo.png" class="app-logo" alt="Yo App"/></a>

            <h3>Yopify would like to access the following information from your account:</h3>
            <ol>
                <li>URL</li>
                <li>Email</li>
                <li>Name</li>
            </ol>
            <p class="no-account">By clicking Allow, you allow this app to use your information in accordance with terms of service and privacy policies. You
                can change this and other Account Permissions at any time.</p>

            <div class="action-btns">
                <button class="allow-btn" onclick="window.open('<?php echo $yoUrl; ?>');"><?php _e('Allow', 'woocommerce-plugin-yo'); ?></button>
            </div>
        </div>
        <?php
    }
    ?>
    <?php
}
