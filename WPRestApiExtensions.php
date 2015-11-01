<?php

/*
  Plugin Name: WPRestApiExtensions
  Plugin URI: https://github.com/larjen/WPRestApiExtensions
  Description: Extends the WP-REST API to get additional fiels from responses.
  Author: Lars Jensen
  Version: 1.0.2
  Author URI: http://exenova.dk/
 */

class WPRestApiExtensions {

    // values to use internally in the plugin, do not customize

    static $debug = false;
    static $plugin_salt = "WPRestApiExtensions";
    static $plugin_name = "WPRestApiExtensions";

    static function activation() {
        update_option(self::$plugin_salt . "_MESSAGES", []);

        self::add_message('Plugin WPRestApiExtensions activated.');
    }

    static function add_message($message) {

        $messages = get_option(self::$plugin_salt . "_MESSAGES");
        array_push($messages, date("Y-m-d H:i:s") . " - " . $message);

        // keep the amount of messages below 10
        if (count($messages) > 10) {
            $temp = array_shift($messages);
        }

        update_option(self::$plugin_salt . "_MESSAGES", $messages);
    }

    static function deactivation() {

        self::add_message('Plugin WPRestApiExtensions deactivated.');
    }

    static function plugin_menu() {

        add_management_page(self::$plugin_name, self::$plugin_name, 'activate_plugins', 'WPRestApiExtensions', array('WPRestApiExtensions', 'plugin_options'));
    }

    static function plugin_options() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // debug
        if (self::$debug) {
            echo '<pre>';
            echo 'get_option("' . self::$plugin_salt . '_VALUE")=' . get_option(self::$plugin_salt . "_VALUE") . PHP_EOL;
            echo '</pre>';
        }

        // print the admin page
        echo '<div class="wrap">';
        echo '<h2>' . self::$plugin_name . '</h2>';
        echo '<p>This plugin extends normal REST API, you may monitor any plugin notifications from this page.</p>';

        $messages = get_option(self::$plugin_salt . "_MESSAGES");

        while (!empty($messages)) {
            $message = array_shift($messages);
            echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong>' . $message . '</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Afvis denne meddelelse.</span></button></div>';
        }

        // since the messages has been shown, purge them.
        update_option(self::$plugin_salt . "_MESSAGES", []);
    }

    static function extend_with_post_data() {
        register_api_field('post', 'tags', array(
            'get_callback' => 'WPRestApiExtensions::get_tags',
            'update_callback' => null,
            'schema' => null,
                )
        );
        register_api_field('post', 'categories', array(
            'get_callback' => 'WPRestApiExtensions::get_category',
            'update_callback' => null,
            'schema' => null,
                )
        );
    }

    static function get_tags($object, $field_name, $request) {
        return wp_get_post_tags($object['id']);
    }

    static function get_category($object, $field_name, $request) {

        $catIds = wp_get_post_categories($object['id']);

        $cats = array();

        foreach ($catIds as $id) {
            array_push($cats, get_category($id));
        }

        return $cats;
    }
    
    /*
     * Retrieve tagsfrom name parameter
     * 
     */
    
    static function get_filtered_tags( $WP_REST_Request_arg ) {
        
        self::add_message($WP_REST_Request_arg["name"] );
        
        //search object
        $searchObj = array(
            "name"=>$WP_REST_Request_arg["name"],
            "number"=>1
        );

        
        $tags = get_tags($searchObj);
        
        if ( empty( $tags ) ) {
            return new WP_Error( 'WPRestApiExtensions', 'No such tag.', array( 'status' => 404 ) );
        }

        return $tags;
    }


}

// register activation and deactivation
register_activation_hook(__FILE__, 'WPRestApiExtensions::activation');
register_deactivation_hook(__FILE__, 'WPRestApiExtensions::deactivation');

// register wp hooks
add_action('admin_menu', 'WPRestApiExtensions::plugin_menu');

// add for rest api
add_action('rest_api_init', 'WPRestApiExtensions::extend_with_post_data');
add_action('rest_api_init', function () {
    register_rest_route( 'wprestapiextensions/v1', '/tags', array(
        'methods' => 'GET',
        'callback' => 'WPRestApiExtensions::get_filtered_tags',
    ) );
} );

