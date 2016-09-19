<?php

class YopifyYoPull
{
    /**
     * Count orders
     *
     * @param $request
     *
     * @return array
     */
    public static function countOrders($request)
    {
        global $wpdb;

        $requestBody = json_decode($request->get_body(), true);

        if (count($requestBody) == 0) {
            header('HTTP/1.0 401 Unauthorized');
            die();
        }

        if (yopifyYoVerifyRsaSignature($requestBody)) {
            $status = isset($requestBody['status']) ? $requestBody['status'] : '';
            $limit = isset($requestBody['limit']) ? $requestBody['limit'] : 250;
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

            return $orderCount = ['count' => $status ? $count : $postsCount];
        }else {
            header('HTTP/1.0 401 Unauthorized');
            die();
        }
    }

    /**
     * Get orders
     *
     * @param $request
     *
     * @return array
     */
    public static function getOrders($request)
    {
        $requestBody = json_decode($request->get_body(), true);

        if (count($requestBody) == 0) {
            header('HTTP/1.0 401 Unauthorized');
            die();
        }

        if (yopifyYoVerifyRsaSignature($requestBody)) {
            $status = isset($requestBody['status']) ? $requestBody['status'] : '';
            $limit = isset($requestBody['limit']) ? $requestBody['limit'] : 250;
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

            foreach ($orders as $orderPost) {
                $order = new WC_Order();
                $countryName = new WC_Countries();
                $order->populate($orderPost);

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
                    $results['orders'][] = $orderDetails;
                }
            }

            return $results;

        }else {
            header('HTTP/1.0 401 Unauthorized');
            die();
        }
    }
}