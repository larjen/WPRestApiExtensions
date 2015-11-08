<?php

/*
 * This file was autogenerated from the WordPress plugin WPRestApiExtensions.
 */

define("CACHE_DIRECTORY", __DIR__ . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR);
define("PATH_TO_WP", "");

/*
 * Add headers
 */

function add_headers($status_code) {
    header($_SERVER["SERVER_PROTOCOL"] . " " . $status_code);
    header("Content-Type: application/json; charset=UTF-8");
}

/*
 * Wipes the cache clean
 */

function wipe_cache() {
    $files = glob(CACHE_DIRECTORY . '*');
    foreach ($files as $file) {
        if (is_file($file))
            unlink($file);
    }
}

/*
 * Prints the request from the server and adds it to the cache
 */

function get_fresh($cache_key) {

    // construct a new request but strip the _jsonp from the request
    $request_parameters = "?";
    foreach ($_GET as $key => $value) {
        if ($key != "_jsonp") {
            $request_parameters = $request_parameters . "&" . $key . "=" . $value;
        }
    }

    $URI = "http://" . $_SERVER["HTTP_HOST"] . PATH_TO_WP . "/wp-json/wprestapiextensions/v1/" . ENDPOINT . "/" . $request_parameters;

    error_log($URI);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $URI);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $output = curl_exec($ch);

    // get status code
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // print the result
    print_result($output, $status_code);

    // now add file to cache
    $fh = fopen(CACHE_DIRECTORY . $cache_key, 'w') or die();
    fwrite($fh, $status_code . ":");
    fwrite($fh, $output);
    fclose($fh);
}

/*
 * Gets the cachekey for the request
 */

function get_cache_key() {
    $cache_key = "";
    foreach ($_GET as $key => $value) {
        if ($key != "_jsonp") {
            $cache_key = $cache_key . $key . $value;
        }
    }
    return ENDPOINT . $cache_key;
}

/*
 * Prints the result, and decide if it should be wrapped in
 * a jsonp callback
 */

function print_result($output, $status_code) {

    // add headers
    add_headers($status_code);

    // now print it
    if (isset($_GET["_jsonp"])) {

        // wrap in jsonp format
        print("/**/" . $_GET["_jsonp"] . "(");
        print($output);
        print(")");
    } else {
        print($output);
    }
}

/*
 * Returns boolean false if not in cache, otherwise it prints the cached
 * version.
 */

function get_cached($cache_key) {

    // if the file is not in the cache, return false
    if (!file_exists(CACHE_DIRECTORY . $cache_key)) {
        return false;
    }

    $output = file_get_contents(CACHE_DIRECTORY . $cache_key);
    $output_array = explode(":", $output, 2);

    // print the result
    print_result($output_array[1], $output_array[0]);
}

// if purge set - wipe cache
if (isset($_GET["PURGE_CACHE"]) && !defined('ENDPOINT')) {
    wipe_cache();
} else {
    // check if response is in cache
    if (defined('ENDPOINT')) {
        $cache_key = get_cache_key();

        if (get_cached($cache_key) === false) {
            get_fresh($cache_key);
        }
    }
}