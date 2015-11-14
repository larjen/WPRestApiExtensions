<?php

class WPRestApiExtensionsAdmin extends WPRestApiExtensions {

    static function plugin_menu() {
        add_management_page(self::$plugin_name, self::$plugin_name, 'activate_plugins', 'WPRestApiExtensionsAdmin', array('WPRestApiExtensionsAdmin', 'plugin_options'));
    }
    
    static function plugin_options() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // debug
        if (self::$debug) {
            echo '<pre>';
            echo 'ABSPATH=' .ABSPATH;
            echo 'get_option("' . self::$plugin_name . '_MESSAGES")=' . var_dump(get_option(self::$plugin_name . "_MESSAGES") ). PHP_EOL;
            echo '</pre>';
        }

        // print the admin page
        echo '<div class="wrap">';
        echo '<h2>' . self::$plugin_name . '</h2>';
        echo '<p>This plugin extends the REST API, with additional custom endpoints.</p>';
        $messages = get_option(self::$plugin_name . "_MESSAGES");

        while (!empty($messages)) {
            $message = array_shift($messages);
            echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong>' . $message . '</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Afvis denne meddelelse.</span></button></div>';
        }
        
        echo '</div>';

        // since the messages has been shown, purge them.
        update_option(self::$plugin_name . "_MESSAGES", []);
    }
}

// register wp hooks
add_action('admin_menu', 'WPRestApiExtensionsAdmin::plugin_menu');