<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Yopify Yo
 * @subpackage Yopify/Yo/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Yopify Yo
 * @subpackage Yopify/Yo/public
 * @author     Yopify
 */
class Yopify_Yo_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     *
     * @param      string $plugin_name The name of the plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Yo_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Yo_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/yo-public.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Yo_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Yo_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        // wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/yo-public.js', array('jquery'), $this->version, false);;

        $clientId = get_option('yopify_yo_client_id');
        $currentAppId = yopify_yo_get_wc_app();
        $accessToken = yopify_yo_get_access_token();

        if ($clientId && $accessToken && $currentAppId) {

            if (strtolower(get_option('yopify_yo_deferred_load')) == "true") {

                add_action('wp_footer', function (){
                    ?>
                    <script type="text/javascript">
                        (function()
                        {
                            function loadYo()
                            {
                                var url = "<?php echo YOPIFY_YO_BASE_URL . '/js/yo/' . get_option('yopify_yo_client_id') . '/bootstrap.js'; ?>";
                                if( url )
                                {
                                    var s = document.createElement( 'script' );
                                    s.type = 'text/javascript';
                                    s.async = true;
                                    s.src = url;
                                    var x = document.getElementsByTagName( 'script' )[0];
                                    x.parentNode.insertBefore( s, x );
                                }
                            };
                            if( window.attachEvent )
                            {
                                window.attachEvent( 'onload', loadYo );
                            }
                            else
                            {
                                window.addEventListener( 'load', loadYo, false );
                            }
                        })();
                    </script>
                    <?php
                });
            }else {
                wp_enqueue_script('yopify-yo-js', YOPIFY_YO_BASE_URL . '/js/yo/' . $clientId . '/bootstrap.js', false);
            }
        }
    }
}
