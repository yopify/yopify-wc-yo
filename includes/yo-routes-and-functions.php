<?php

/**
 * Push the latest order to Yo
 */
add_action('woocommerce_checkout_order_processed', function ($order_id){
    catchSingleOrder($order_id);
});

add_action('wp_ajax_yopify_yo_set_access_token', 'yopify_yo_set_access_token');
add_action('wp_ajax_yopify_yo_set_current_app', 'yopify_yo_set_current_app');
add_action('wp_ajax_yopify_yo_sync_orders', 'yopify_yo_sync_orders');
add_action('wp_ajax_yopify_yo_count_orders', 'yopify_yo_count_orders');
add_action('wp_ajax_yopify_yo_get_access_tokens', 'yopify_yo_get_access_tokens');


/**
 * Access token
 * @return string
 */
function yopify_yo_get_access_token()
{
    $yopifyYoAccessToken = get_option('yopify_yo_access_token');

    return $yopifyYoAccessToken;
}

/**
 * Current app
 * @return string
 */
function yopify_yo_get_wc_app()
{
    $yopifyYoWcAppId = (int)get_option('yopify_yo_wc_app_id');

    return $yopifyYoWcAppId;
}

/**
 * Set access token
 */
function yopify_yo_set_access_token()
{
    $response = [
        'success' => false
    ];

    if (isset($_POST['token']) && $_POST['token']) {
        if (update_option('yopify_yo_access_token', $_POST['token'])) {

        }

        $response = [
            'success' => true
        ];
    }

    echo json_encode($response);
    die;
}

/**
 * Set current app
 */
function yopify_yo_set_current_app()
{
    $response = [
        'success' => false
    ];

    if (isset($_POST['app_id']) && $_POST['app_id']) {
        update_option('yopify_yo_wc_app_id', $_POST['app_id']);
        update_option('yopify_yo_client_id', $_POST['client_id']);

        $response = [
            'success' => true
        ];
    }

    echo json_encode($response);
    die;
}

/**
 * Verify Token
 *
 * @param $yopifyYoAccessToken
 *
 * @return mixed
 */
function yopify_yo_verify_token($yopifyYoAccessToken)
{
    // Initialize Yo client
    $yoClient = new Yopify_Yo_Client();

    // Set auth token
    $yoClient->authToken = $yopifyYoAccessToken ? $yopifyYoAccessToken : yopify_yo_get_access_token();
    $yoClient->appId = yopify_yo_get_wc_app();

    return $yoClient->ping(null, true);
}

/**
 * Get UTC timestamp
 */
function getUtcTimestamp()
{
    return time() - date('Z');
}

/**
 * Count total orders
 */
function yopify_yo_count_orders()
{
    // Initialize Yo client
    $yoClient = new Yopify_Yo_Client();

    // Set auth token
    $yoClient->authToken = yopify_yo_get_access_token();
    $yoClient->appId = yopify_yo_get_wc_app();

    $response = [
        'status' => 0
    ];

    if ($yoClient->ping()) {
        $status = isset($requestBody['status']) ? $requestBody['status'] : 'wc-completed, wc-processing, publish';
        $limit = isset($requestBody['limit']) ? $requestBody['limit'] : 500;
        $page = isset($requestBody['page']) ? $requestBody['page'] : 1;
        $orderBy = isset($requestBody['order']) ? $requestBody['order'] : 'DESC';
        $results = [];

        // Fetch orders
        $postsCount = wp_count_posts('shop_order');
        $count = 0;

        if ($status) {

            $statuses = array_filter(array_map('trim', explode(',', $status)));

            foreach ($statuses as $status) {
                $count += isset($postsCount->$status) ? $postsCount->$status : 0;
            }
        }

        $response = ['status' => 1, 'count' => $status ? $count : $postsCount];
    }else {
        $response['error'] = "You are not authenticated with Yo. <a href='" . admin_url('?page=yo') . "'><b>Click here</b></a> to start authentication.";
    }

    echo json_encode($response);
    die;
}

/**
 * Sync orders with yo
 */
function yopify_yo_sync_orders()
{
    set_time_limit(0);

    $requestBody = $_REQUEST;

    $status = isset($requestBody['status']) ? $requestBody['status'] : 'wc-completed, wc-processing, publish';
    $limit = isset($requestBody['limit']) ? $requestBody['limit'] : 1;
    $page = isset($requestBody['page']) ? $requestBody['page'] : 1;
    $orderBy = isset($requestBody['order']) ? $requestBody['order'] : 'DESC';
    $dateAfter = isset($requestBody['date_after']) ? $requestBody['date_after'] : '';
    $results = [];

    $args = [
        'post_type'      => 'shop_order',
        'post_status'    => $status,
        'numberposts'    => $limit,
        'posts_per_page' => $limit,
        'offset'         => ($page - 1) * $limit,
        'order'          => $orderBy
    ];

    if ($status) {
        $statuses = array_filter(array_map('trim', explode(',', $status)));

        if ($statuses) {
            $args['post_status'] = $statuses;
        }
    }

    if ($dateAfter) {
        $args['date_query'] = [
            [
                'after' => date('c', $dateAfter)
            ]
        ];
    }

    // Fetch orders
    $orders = get_posts($args);

    $response['status'] = 1;

    foreach ($orders as $orderPost) {
        $order = new WC_Order($orderPost->ID);

        try{
            $responseData = pushOrder($order);
            $response['data'] = $responseData;
            if (isset($responseData->status_code) && $responseData->status_code != 200) {
                $response['status'] = 0;
                $response['errors'][] = 'Error: ' . (isset($responseData->message) ? $responseData->message : 'An error has occurred while syncing orders.');
                break;
            }
        }catch (\Exception $e){
            $response['errors'][] = 'Exception: ' . $e->getMessage();
        }
    }

    if (isset($response['errors'])) {
        $response['errors'] = implode('<br />', $response['errors']);
    }


    echo json_encode($response);
    die;

}

/**
 * Push order to yo
 *
 * @param $order
 *
 * @return mixed|null
 */
function pushOrder($order, $orderItems = null)
{
    set_time_limit(0);

    $yoClient = new Yopify_Yo_Client();

    $yoClient->authToken = yopify_yo_get_access_token();
    $yoClient->appId = yopify_yo_get_wc_app();

    $items = $orderItems ? $orderItems : $order->get_items();
    $yoEvent = null;
    $countryName = new WC_Countries();

    foreach ($items as $order_id => $item) {

        if (! isset($item["item_meta"]["_product_id"][0])) {
            $orderData = $order->get_data();

            $itemData = $item->get_data();
            $product_id = $itemData['product_id'];
            $order_id = $itemData['order_id'];

            $first_name = $orderData['billing']['first_name'] ? $orderData['billing']['first_name'] : $orderData['shipping']['first_name'];
            $last_name = $orderData['billing']['last_name'] ? $orderData['billing']['last_name'] : $orderData['shipping']['last_name'];
            $city = $orderData['billing']['city'] ? $orderData['billing']['city'] : $orderData['shipping']['city'];
            $state = $orderData['billing']['state'] ? $orderData['billing']['state'] : $orderData['shipping']['state'];
            $country = $orderData['billing']['country'] ? $orderData['billing']['country'] : $orderData['shipping']['country'];
        }else {
            $order_id = $order->id;
            $product_id = $item["item_meta"]["_product_id"][0];

            $first_name = $order->billing_first_name ? $order->billing_first_name : $order->shipping_first_name;
            $last_name = $order->billing_last_name ? $order->billing_last_name : $order->shipping_last_name;
            $city = $order->billing_city ? $order->billing_city : $order->shipping_city;;
            $state = $order->billing_state ? $order->billing_state : $order->shipping_state;
            $country = $order->billing_country ? $order->billing_country : $order->shipping_country;
        }

        $product = new WC_Product($product_id);

        $thumbnailImage = wp_get_attachment_image_src($product->get_image_id());
        $thumbnailImage = isset($thumbnailImage['0']) && $thumbnailImage['0'] ? $thumbnailImage['0'] : wp_get_attachment_url($product->get_image_id());

        if ($product->get_title()) {
            $event = new Yopify_Yo_Event();
            $event->unique_id1 = $order_id;
            $event->unique_id2 = $product_id;
            $event->title = $product->get_title();
            $event->first_name = $first_name;
            $event->last_name = $last_name;
            $event->city = $city;
            $event->province = $country && $state ? $countryName->get_states($country)[$state] : '';
            $event->country = $country ? $countryName->get_countries()[$country] : '';
            $event->url = $product->get_permalink();
            $event->image_url = $thumbnailImage;

            $yoEvent = $yoClient->createEvent($event);
        }
    }

    return $yoEvent;
}

/**
 * Catch latest order
 *
 * @param $order_id
 *
 * @return mixed|null
 */
function catchSingleOrder($order_id)
{
    $order = $order_id ? new WC_Order($order_id) : null;

    return pushOrder($order);
}