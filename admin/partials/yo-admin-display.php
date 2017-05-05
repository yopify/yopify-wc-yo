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

    add_submenu_page(
        null            // -> Set to null - will hide menu link
        , 'Yo - Sync Orders'    // -> Page Title
        , 'Yo - Sync Orders'    // -> Title that would otherwise appear in the menu
        , 'manage_options' // -> Capability level
        , 'yopify_yo_sync_orders'   // _> Still accessible via admin.php?page=menu_handle
        , 'yopify_yo_sync_orders_callback' // -> To render the page
    );
}

// Add menu
add_action('admin_menu', 'yopify_yo_admin_menu');

/**
 * Add sync menu
 */
function yopify_yo_add_sync_menu()
{
    global $wp_admin_bar, $wpdb;

    if ( ! is_super_admin() || ! is_admin_bar_showing()) {
        return;
    }


    /* Add the main siteadmin menu item */
    $wp_admin_bar->add_menu(array(
        'id'    => 'yopify_yo_sync_orders_button',
        'title' => __('Yo - Sync Orders', 'woocommerce-plugin-yo'),
        'href'  => admin_url('?page=yopify_yo_sync_orders'),
        'meta'  => [
            'title' => 'Sync orders with Yo'
        ]
    ));
}

add_action('admin_bar_menu', 'yopify_yo_add_sync_menu', 1000);


/**
 * Sync orders page
 */
function yopify_yo_sync_orders_callback()
{
    ?>
    <script type="text/javascript">
        var yopifyYoSyncOrdersUrl = ajaxurl + "?action=yopify_yo_sync_orders";
        var yopifyYoDashboardUrl = '<?php echo admin_url('?page=yo'); ?>';
    </script>

    <a id="yopify_yo_start_sync" href="javascript:void(0);">Click here to sync orders. </a>
    <div class="yopify-yo-sync-orders-container" style="display: none;">
        <h2>Sync Maximum Orders: <span id="totalOrdersCount"></span></h2>
        <div class="progress-bar-holder" style="width: 500px; padding-top: 4px; padding-bottom: 4px; background: #ffffff; border: 1px solid;">
            <div class="progress" style="background: #4F800D; width: 0; padding-top: 4px; padding-bottom: 4px;"></div>
        </div>

        <ul id="yopify-yo-sync-logs">
            <li>Initializing...</li>
        </ul>
    </div>

    <?php
}

/**
 * Front end to show yo app
 */
function yopify_yo_init_page()
{
    $siteUrl = home_url();
    $yopifyYoAccessToken = yopify_yo_get_access_token();

    // Verify Store on yo server
    $clientDetails = yopify_yo_verify_token($yopifyYoAccessToken);

    if (isset($clientDetails->status) && $clientDetails->status == 1) {

        $yopifyYoAuthUrl = YOPIFY_YO_BASE_URL . '/auth/login?app_id=' . yopify_yo_get_wc_app() . '&api_token=' . $yopifyYoAccessToken;

        ?>
        <iframe id="yopifyYoBackEndIframe" src="<?php echo $yopifyYoAuthUrl; ?>" style="width:100%; min-height:500px;" frameborder="no"></iframe>
        <?php
    }else {

        $url = YOPIFY_YO_BASE_URL . '/me/token?callback=?';
        $loginUrl = YOPIFY_YO_BASE_URL . '/login';
        $createTokensUrl = YOPIFY_YO_BASE_URL . '/settings#/api';
        ?>
        <script type="text/javascript">
            var yopifyYoCheckLoginUrl = "<?php echo YOPIFY_YO_BASE_URL . '/auth/login/check?callback=?'; ?>";
            var yopifyYoMyApps = "<?php echo YOPIFY_YO_BASE_URL . '/me/apps?callback=?'; ?>";
            var yopifyYoLoginUrl = "<?php echo $loginUrl; ?>";
            var yopifyYoCreateTokensUrl = "<?php echo $createTokensUrl; ?>";
            var yopifyYoGetAccessTokenUrl = '<?php echo $url; ?>';
            var yopifyYoSetAccessTokenUrl = '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=yopify_yo_set_access_token';
            var yopifyYoSetCurrentAppUrl = '<?php echo admin_url('admin-ajax.php'); ?>' + '?action=yopify_yo_set_current_app';
            var yopifyYoSyncOrdersUrl = '<?php echo admin_url('?page=yopify_yo_sync_orders'); ?>';
            var yopifyYoDashboardUrl = '<?php echo admin_url('?page=yo'); ?>';
        </script>

        <div class="allow-access" id="yopifyYoAllowAccessHolder" style="display: none">
            <a href="https://yopify.com" target="_blank" class="logo-holder"><img src="<?php echo YOPIFY_YO_PLUGIN_URL; ?>assets/logo.png" class="app-logo"
                                                                                  alt="Yo App"/></a>

            <h3>You are not logged in.</h3>

            <p class="no-account">Please click Login button to Setup Yo. Once you're logged In, Please refresh this page.</p>

            <div class="action-btns">
                <a class="allow-btn left-button" id="switchAccount" href="<?php echo YOPIFY_YO_BASE_URL . '/logout' ?>" target="_blank">Switch account</a>
                <a class="allow-btn right-button" href="javascript:void(0)" id="yopifyYoAllowAccessButton"><?php _e('Login', 'woocommerce-plugin-yo'); ?></a>
            </div>

            <div class='yo_loading' style='display: none;'>
                <div class='cssload-box-loading'></div>
            </div>
        </div>
        <?php
    }
    ?>
    <?php
}