<?php

/*
  Plugin Name: WPRestApiExtensions
  Plugin URI: https://github.com/larjen/WPRestApiExtensions
  Description: Extends the WP-REST API with custom read only endpoints.
  Author: Lars Jensen
  Version: 1.0.5
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

    /*
     * Retrieve tagsfrom name parameter
     */

    static function filter_tag($tags) {

        $returnTags = [];

        foreach ($tags as $tag) {
            array_push($returnTags, array(
                "count" => $tag->count,
                "description" => $tag->description,
                "slug" => $tag->slug,
                "term_id" => $tag->term_id,
                "name" => $tag->name
                    )
            );
        }
        return $returnTags;
    }

    static function filter_category($category) {
        //var_dump($category);

        $returnCat["term_id"] = $category->term_id;
        $returnCat["name"] = $category->name;
        $returnCat["category_count"] = $category->category_count;
        $returnCat["slug"] = $category->slug;
        $returnCat["description"] = $category->description;

        return $returnCat;
    }

    static function filter_post($post) {
        //var_dump($post);

        $returnPost["ID"] = $post->ID;
        $returnPost["post_date"] = $post->post_date;
        $returnPost["post_content"] = $post->post_content;
        $returnPost["post_title"] = $post->post_title;
        $returnPost["post_name"] = $post->post_name;

        return $returnPost;
    }

    static function tag($WP_REST_Request_arg) {

        self::add_message($WP_REST_Request_arg["tag_name"]);

        if (empty($WP_REST_Request_arg["tag_name"])) {
            return new WP_Error('WPRestApiExtensions', 'No such tag.', array('status' => 404));
        }

        $response = [];
        $response["data"] = [];
        $response["status_code"] = 200;
        $response["uri"] = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s:" : ":") . "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];


        // add the filter
        add_filter('get_tags', 'WPRestApiExtensions::filter_tag', 10, 1);

        //search object
        $searchObj = array(
            "name" => $WP_REST_Request_arg["tag_name"],
            "number" => 1
        );

        $tag = get_tags($searchObj);

        if (empty($tag)) {

            // if there is a space in the requested name - try replacing them with '-'
            // to see if that gives a result

            $newSearchTerm = str_replace(' ', '-', $WP_REST_Request_arg["tag_name"]);

            //search object
            $searchObj = array(
                "name" => $newSearchTerm,
                "number" => 1
            );



            $tag = get_tags($searchObj);

            if (empty($tag)) {
                return new WP_Error('WPRestApiExtensions', 'No such tag.', array('status' => 404));
            }
        }

        // remove the filter
        remove_filter('get_tags', 'WPRestApiExtensions::filter_tag');
        array_push($response["data"], $tag[0]);

        return $response;
    }

    static function get_pagination($response) {

        $pagination = array();

        if ($response["page"] < 2) {
            $pagination["prev"] = false;
        } else {
            $pagination["prev"] = $response["page"] - 1;
        }

        if ($response["page"] < $response["total_pages"]) {
            $pagination["next"] = $response["page"] + 1;
        } else {
            $pagination["next"] = false;
        }

        return $pagination;
    }

    static function posts($WP_REST_Request_arg) {

        self::add_message($WP_REST_Request_arg["name"]);

        if (isset($WP_REST_Request_arg["page"])) {
            if ($WP_REST_Request_arg["page"] < 1) {
                return new WP_Error('WPRestApiExtensions', 'No posts found.', array('status' => 404));
            }
        }

        // build the WP_Query query
        $args = array();

        if (isset($WP_REST_Request_arg["per_page"])) {
            $args['posts_per_page'] = $WP_REST_Request_arg["per_page"];
        }

        if (isset($WP_REST_Request_arg["page"])) {
            $args['paged'] = $WP_REST_Request_arg["page"];
        }

        if (isset($WP_REST_Request_arg["tags"])) {
            $args['tag'] = $WP_REST_Request_arg["tags"];
        }

        if (isset($WP_REST_Request_arg["search"]) && !empty($WP_REST_Request_arg["search"])) {
            $args['s'] = $WP_REST_Request_arg["search"];
        }

        // now build the pages to return
        $the_query = new WP_Query($args);

        $response = [];

        $response["data"] = [];

        $response["uri"] = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s:" : ":") . "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        foreach ($the_query->get_posts() as $post) {

            // just add the fields we need
            $returnPost = self::filter_post($post);

            // add tags
            $returnPost["tags"] = self::filter_tag(wp_get_post_tags($post->ID));

            // add categories to the post
            $categoryIds = wp_get_post_categories($post->ID);
            $returnPost["categories"] = [];
            foreach ($categoryIds as $categoryId) {
                $cat = get_category($categoryId);
                array_push($returnPost["categories"], self::filter_category($cat));
            }

            array_push($response["data"], $returnPost);
        }

        // get some additional data back
        $response['total'] = $the_query->found_posts;
        $response['total_pages'] = $the_query->max_num_pages;
        $response['per_page'] = $WP_REST_Request_arg["per_page"];
        $response["status_code"] = 200;
        $response["page"] = $WP_REST_Request_arg["page"];


        $response["pagination"] = self::get_pagination($response);


        /* Restore original Post Data */
        wp_reset_postdata();

        if (empty($response["data"])) {
            return new WP_Error('WPRestApiExtensions', 'No posts found.', array('status' => 404));
        }

        return $response;
    }

}

// register activation and deactivation
register_activation_hook(__FILE__, 'WPRestApiExtensions::activation');
register_deactivation_hook(__FILE__, 'WPRestApiExtensions::deactivation');

// register wp hooks
add_action('admin_menu', 'WPRestApiExtensions::plugin_menu');

// add for rest api
add_action('rest_api_init', function () {
    register_rest_route('wprestapiextensions/v1', '/tag', array(
        'methods' => 'GET',
        'callback' => 'WPRestApiExtensions::tag',
    ));
});
add_action('rest_api_init', function () {
    register_rest_route('wprestapiextensions/v1', '/posts', array(
        'methods' => 'GET',
        'callback' => 'WPRestApiExtensions::posts',
    ));
});

