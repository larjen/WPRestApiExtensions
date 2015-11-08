<?php

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

include_once(__DIR__ . DIRECTORY_SEPARATOR . "includes". DIRECTORY_SEPARATOR . "main.php");

class WPRestApiExtensionsUninstall extends WPRestApiExtensions {
    static function uninstall() {
        delete_option(self::$plugin_name . "_MESSAGES");
        delete_option(self::$plugin_name . "_ACTIVE");
        
        // to-do add method to delete the infinite cache mechanism
    }
}

WPRestApiExtensionsUninstall::uninstall();