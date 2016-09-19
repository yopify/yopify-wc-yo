<?php

// Access via /wp-json/yo/v1/orders/count
add_action('rest_api_init', function (){

    // Get store details
    register_rest_route('yo', '/v1/me', [
        'methods'  => 'POST',
        'callback' => 'yopify_yo_get_store_details',
    ]);

    // Order count
    register_rest_route('yo', '/v1/orders/count', [
        'methods'  => 'POST',
        'callback' => function ($request){

            if ( ! class_exists('YopifyYoPull')) {
                require plugin_dir_path(__FILE__) . 'YopifyYoPull.php';
            }

            return YopifyYoPull::countOrders($request);
        },
    ]);

    // Get orders
    register_rest_route('yo', '/v1/orders', [
        'methods'  => 'POST',
        'callback' => function ($request){

            if ( ! class_exists('YopifyYoPull')) {
                require plugin_dir_path(__FILE__) . 'YopifyYoPull.php';
            }

            return YopifyYoPull::getOrders($request);
        },
    ]);
});

add_action('woocommerce_checkout_order_processed', function ($order_id){
    if ( ! class_exists('YopifyYoPush')) {
        require plugin_dir_path(__FILE__) . 'YopifyYoPush.php';
    }

    YopifyYoPush::pushNewOrderToWebhook($order_id);
});


/**
 * Get client id from Yo server
 *
 * @param $storeUrl
 * @param $yopifyYoAccessToken
 *
 * @return mixed
 */
function yopify_yo_get_client_id($storeUrl, $yopifyYoAccessToken)
{
    global $yopifyYoBaseUrl;

    $yoConfigs = getYoConfigs();
    extract($yoConfigs);

    $timestamp = getUtcTimestamp();

    $url = $yopifyYoBaseUrl . '/identify?url=' . urlencode($storeUrl) . '&t=' . $timestamp .
           '&hmac=' . hash_hmac('sha256', $storeUrl . $timestamp, $yopifyYoAccessToken);

    $response = wp_remote_get($url, [
        'sslverify' => false,
        'timeout'   => 30,
    ]);

    if (is_wp_error($response)) {

        $errorMessage = $response->get_error_message();;
        echo '<div id="message" class="error"><p>' . $errorMessage . '</p></div>';
        die;

    }else {

        if ($response != null && isset($response['body'])) {
            return json_decode($response['body'], true);
        }
    }

    return null;
}

/**
 * Encrypt data
 *
 * @param $data
 *
 * @return string
 */
function yopify_yo_encrypt_data($data)
{
    global $yopifyYoPublicKey;

    $yoConfigs = getYoConfigs();
    extract($yoConfigs);

    if ( ! class_exists('Crypt_RSA')) {
        include_once('phpseclib/Crypt/RSA.php');
    }

    $rsa = new Crypt_RSA();
    $rsa->loadKey($yopifyYoPublicKey);

    $encryptedData = base64_encode($rsa->encrypt(json_encode($data)));

    return $encryptedData;
}


/**
 * Get store details
 *
 * @param $request
 *
 * @return array
 */
function yopify_yo_get_store_details($request)
{
    global $yopifyYoPluginUrl;

    $yoConfigs = getYoConfigs();
    extract($yoConfigs);

    $siteUrl = home_url();
    $yopifyYoAccessToken = yopify_yo_get_access_token();

    $requestBody = json_decode($request->get_body(), true);

    if (count($requestBody) == 0) {
        header('HTTP/1.0 401 Unauthorized');
        exit();
    }

    if (yopifyYoVerifyRsaSignature($requestBody)) {
        return [
            't'         => isset($requestBody['t']) ? $requestBody['t'] : '',
            'signature' => isset($requestBody['signature']) ? $requestBody['signature'] : '',
            'payload'   => yopify_yo_encrypt_data([
                'url'          => $siteUrl,
                'email'        => get_option('admin_email'),
                'name'         => get_option('blogname'),
                'access_token' => $yopifyYoAccessToken,
                'plugin_url'   => $yopifyYoPluginUrl
            ])
        ];
    }else {
        header('HTTP/1.0 401 Unauthorized');
        exit();
    }
}


/**
 * Verify Signature
 *
 * @param $data
 *
 * @return bool
 */
function yopifyYoVerifyRsaSignature($data)
{
    global $yopifyYoPublicKey;

    $yoConfigs = getYoConfigs();
    extract($yoConfigs);

    $timestamp = isset($data['t']) ? $data['t'] : null;
    $signature = isset($data['signature']) ? $data['signature'] : null;

    if ( ! class_exists('Crypt_RSA')) {
        include_once('phpseclib/Crypt/RSA.php');
    }

    $rsa = new Crypt_RSA();
    $rsa->loadKey($yopifyYoPublicKey);

    return $rsa->verify($timestamp, base64_decode($signature));
}

/**
 * Access token
 * @return string
 */
function yopify_yo_get_access_token()
{
    $yopifyYoAccessToken = get_option('yopify_yo_access_token');

    if ($yopifyYoAccessToken == null || $yopifyYoAccessToken === false) {
        $yopifyYoAccessToken = hash('sha256', getUtcTimestamp() . uniqid());
        add_option('yopify_yo_access_token', $yopifyYoAccessToken);
    }

    return $yopifyYoAccessToken;
}

/**
 * Get UTC timestamp
 */
function getUtcTimestamp()
{
    return time() - date('Z');
}