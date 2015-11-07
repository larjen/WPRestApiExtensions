<?php

/*
  Plugin Name: WPRestApiExtensions
  Plugin URI: https://github.com/larjen/WPRestApiExtensions
  Description: Extends the WP-REST API with custom read only endpoints.
  Author: Lars Jensen
  Version: 1.0.6
  Author URI: http://exenova.dk/
 */

include_once(__DIR__ . DIRECTORY_SEPARATOR . "includes". DIRECTORY_SEPARATOR . "main.php");

if (is_admin()) {
    
    // include admin ui
    include_once(__DIR__ . DIRECTORY_SEPARATOR . "includes". DIRECTORY_SEPARATOR . "admin.php");

    // register activation and deactivation
    register_activation_hook(__FILE__, 'WPRestApiExtensions::activation');
    register_deactivation_hook(__FILE__, 'WPRestApiExtensions::deactivation');
    
}