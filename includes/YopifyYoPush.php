<?php

/**
 * Push orders/products
 * Class YopifyYoPush
 */
class YopifyYoPush
{
    public static function pushNewOrderToWebhook($order_id)
    {
        global $yopifyYoBaseUrl, $yopifyYoVersion;

        $yoConfigs = getYoConfigs();
        extract($yoConfigs);

        $siteUrl = home_url();
        $yopifyYoAccessToken = yopify_yo_get_access_token();

        $order = $order_id ? new WC_Order($order_id) : null;
        $countryName = new WC_Countries();

        if ($order) {
            $orderDetails = [
                'wc_order_id' => $order->id,
                'order_name'  => "#" . $order->id,
                'first_name'  => $order->billing_first_name ? $order->billing_first_name : $order->shipping_first_name,
                'last_name'   => $order->billing_last_name ? $order->billing_last_name : $order->shipping_last_name,
                'email'       => $order->billing_email ? $order->billing_email : $order->shipping_email,
                'address1'    => $order->billing_address_1 ? $order->billing_address_1 : $order->shipping_address_1,
                'address2'    => $order->billing_address_2 ? $order->billing_address_2 : $order->shipping_address_2,
                'city'        => $order->billing_city ? $order->billing_city : $order->shipping_city,
                'province'    => $countryName->get_states($order->billing_country)[$order->billing_state] ? $countryName->get_states($order->billing_country)[$order->billing_state] : $countryName->get_states($order->shipping_country)[$order->shipping_state],
                'company'     => $order->billing_company ? $order->billing_company : $order->shipping_company,
                'country'     => $countryName->get_countries()[$order->billing_country] ? $countryName->get_countries()[$order->billing_country] : $countryName->get_countries()[$order->shipping_country],
                'zip'         => $order->billing_postcode ? $order->billing_postcode : $order->shipping_postcode,
                'created_at'  => $order->order_date,
                'updated_at'  => $order->modified_date,
                'line_items'  => []
            ];

            $items = $order->get_items();
            foreach ($items as $order_id => $item) {
                $product_id = $item["item_meta"]["_product_id"][0];
                $product = new WC_Product($product_id);
                $orderDetails['line_items'][] = [
                    'title'              => $product->get_title(),
                    'wc_product_id'      => $product_id,
                    'featured_image_url' => wp_get_attachment_url($product->get_image_id()),
                    'url'                => $product->get_permalink(),
                    'status'             => get_post_status($product_id),
                    'created_at'         => get_the_date('Y-m-d H:i:s', $product_id),
                ];
            }

            if (count($orderDetails['line_items']) > 0) {

                $data['order'] = $orderDetails;

                $signedPayload = yopify_yo_encrypt_data([
                    'url'          => $siteUrl,
                    'email'        => get_option('admin_email'),
                    'name'         => get_option('blogname'),
                    'access_token' => $yopifyYoAccessToken
                ]);

                $url = $yopifyYoBaseUrl . '/capture/webhook?topic=store.order.created&signed_payload=' . urlencode($signedPayload);

                $http_args = array(
                    'body'        => json_encode($data),
                    'headers'     => array(
                        'Content-Type' => 'application/json'
                    ),
                    'httpversion' => '1.0',
                    'timeout'     => 15
                );

                $response = wp_remote_post($url, $http_args);

                if ($response != null) {
                    switch ($response['response']['code']) {
                        case 200:
                            break;
                        case 401:
                            wp_remote_get($yopifyYoBaseUrl . '/auth?url=' . urlencode(home_url()) . '&t=' . getUtcTimestamp() . "&version=" . $yopifyYoVersion . "&redirect=0");
                            break;
                        default:
                            break;
                    }
                }
            }
        }

    }

    /**
     *  Call webhook when app is uninstalled
     */
    public static function uninstalled()
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
            'timeout'     => 30
        );

        return $response = wp_remote_post($url, $http_args);
    }
}