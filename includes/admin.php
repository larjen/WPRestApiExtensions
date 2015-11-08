<?php

class WPRestApiExtensionsAdmin extends WPRestApiExtensions {

    static function plugin_menu() {
        add_management_page(self::$plugin_name, self::$plugin_name, 'activate_plugins', 'WPRestApiExtensionsAdmin', array('WPRestApiExtensionsAdmin', 'plugin_options'));
    }
    
    static function plugin_options() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        if (isset($_POST[self::$plugin_name."_DEPLOY_CACHE"])) {
            self::deploy_cache();
        }

        if (isset($_POST[self::$plugin_name."_WIPE_CACHE"])) {
            self::wipe_cache();
        }

        if (isset($_POST["ACTIVE"])) {
            if ($_POST["ACTIVE"] == 'activated') {
                update_option(self::$plugin_name . "_ACTIVE", true);
                
            }

            if ($_POST["ACTIVE"] == 'deactivated') {
                update_option(self::$plugin_name . "_ACTIVE", false);
            }
        }


        
        // debug
        if (self::$debug) {
            echo '<pre>';
            echo 'ABSPATH=' .ABSPATH;
            echo 'get_option("' . self::$plugin_name . '_MESSAGES")=' . var_dump(get_option(self::$plugin_name . "_MESSAGES") ). PHP_EOL;
            echo 'get_option("' . self::$plugin_name . '_ACTIVE")=' . var_dump(get_option(self::$plugin_name . "_ACTIVE") ). PHP_EOL;

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
        
        echo '<h3 class="title">Settings</h3>';
        echo '';
        echo '<form method="post" action="">';
        echo '<table class="form-table"><tbody>';
        
        echo '<tr valign="top"><th scope="row">Deploy cache</th><td><fieldset><legend class="screen-reader-text"><span>Deploy cache</span></legend><label for="DEPLOY_CACHE"><input id="DEPLOY_CACHE" name="'.self::$plugin_name.'_DEPLOY_CACHE" type="checkbox"></label>';
        echo '<p class="description">Provides infinetely cached endpoints to the REST API.</p>';
        echo '</fieldset></td></tr>';

        echo '<tr valign="top"><th scope="row">Wipe cache</th><td><fieldset><legend class="screen-reader-text"><span>Wipe cache</span></legend><label for="WIPE_CACHE"><input id="WIPE_CACHE" name="'.self::$plugin_name.'_WIPE_CACHE" type="checkbox"></label>';
        echo '<p class="description">If your posts have changed, the infinite cache will serve stale responses.</p>';
        echo '</fieldset></td></tr>';

        echo '<tr valign="top"><th scope="row">Schedule cache wipe</th><td><fieldset><legend class="screen-reader-text"><span>Activate</span></legend>';
        if (get_option(self::$plugin_name . "_ACTIVE") == true) {
            echo '<label for="ACTIVE"><input checked="checked" id="ACTIVE" name="ACTIVE" type="radio" value="activated"> Wipe cache when posts are altered.</label><br /><legend class="screen-reader-text"><span>Dectivate</span></legend><label for="DEACTIVE"><input id="DEACTIVE" name="ACTIVE" type="radio" value="deactivated"> Cache is never auto wiped.</label>';
        } else {
            echo '<label for="ACTIVE"><input id="ACTIVE" name="ACTIVE" type="radio" value="activated"> Wipe cache when posts are altered.</label><br /><legend class="screen-reader-text"><span>Dectivate</span></legend><label for="DEACTIVE"><input checked="checked" id="DEACTIVE" name="ACTIVE" type="radio" value="deactivated"> Cache is never auto wiped.</label>';
        }
        echo '<p class="description">When activated the entire cache will be scheduled for a wipe five minutes after last post alteration.</p>';
        echo '</fieldset></td></tr>';
        
        echo '</tbody></table>';


        echo '<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>';
        echo '</form></div>';

        // since the messages has been shown, purge them.
        update_option(self::$plugin_name . "_MESSAGES", []);
    }
}

// register wp hooks
add_action('admin_menu', 'WPRestApiExtensionsAdmin::plugin_menu');