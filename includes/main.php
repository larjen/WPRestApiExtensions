<?php

class WPRestApiExtensions {

    // values to use internally in the plugin, do not customize

    static $debug = false;
    static $plugin_name = "WPRestApiExtensions";

    /*
     * Activation
     */

    static function activation() {
        update_option(self::$plugin_name . "_MESSAGES", []);
        update_option(self::$plugin_name . "_ACTIVE", false);
        self::add_message('Plugin WPRestApiExtensions activated.');
    }

    /*
     * Deactivation
     */

    static function deactivation() {
        
        self::clear_schedule();
        self::add_message('Plugin WPRestApiExtensions deactivated.');
    }

    /*
     * Schedules a wipe of the cache to occur in 5 minutes
     */
    static function schedule_wipe_of_cache() {
        // unschedule previous schedule
        self::clear_schedule();

        //gives the unix timestamp for today's date + 1 minute
        $start = time() + (5 * 60);

        // schedule wipe of cache in 5 minutes, when cache has been wiped
        // the scheduler will be cleared so this does not repeat hourly
        wp_schedule_event($start, 'hourly', 'WPRestApiExtensionsWipeCache');
    }
    
    /*
     * Wipe the cache, then clear the schedule
     */
    static function do_scheduled_cache_wipe() {
    
        self::clear_schedule();
        
        // only wipe the cache if the cache wiping is activated
        if (get_option(self::$plugin_name . "_ACTIVE") == true){
            self::wipe_cache();
        }
    }   
    

    static function clear_schedule() {
        // unschedule previous schedule
        wp_clear_scheduled_hook('WPRestApiExtensionsWipeCache');
    }   
    
    /*
     * Copies one directory to another
     */

    static function recurse_copy($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ( $file = readdir($dir))) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if (is_dir($src . '/' . $file)) {
                    self::recurse_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /*
     * Deploy cache
     */

    static function deploy_cache() {

        $source_dir = __DIR__ . DIRECTORY_SEPARATOR . "rest-api";
        $destination_dir = ABSPATH . "rest-api";

        self::recurse_copy($source_dir, $destination_dir);
        self::wipe_cache();
                
        self::add_message('Plugin WPRestApiExtensions deploying cache mechanism<br /> from: ' . $source_dir . '<br /> to: ' . $destination_dir);
    }

    /*
     * Wipe cache
     */

    static function wipe_cache() {

        $cache_dir = ABSPATH . "rest-api" . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR;

        @mkdir($cache_dir);

        $files = glob($cache_dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        self::add_message('Plugin WPRestApiExtensions wiped cache at : ' . $cache_dir . '.');
    }

    /*
     * Adds messages
     */

    static function add_message($message) {
        $messages = get_option(self::$plugin_name . "_MESSAGES");
        array_push($messages, date("Y-m-d H:i:s") . " - " . $message);

        // keep the amount of messages below 10
        if (count($messages) > 10) {
            $temp = array_shift($messages);
        }

        update_option(self::$plugin_name . "_MESSAGES", $messages);
    }

    /*
     * Filter tags
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

    /*
     * Filter categories
     */

    static function filter_category($category) {
        //var_dump($category);

        $returnCat["term_id"] = $category->term_id;
        $returnCat["name"] = $category->name;
        $returnCat["category_count"] = $category->category_count;
        $returnCat["slug"] = $category->slug;
        $returnCat["description"] = $category->description;

        return $returnCat;
    }

    /*
     * Filter posts
     */

    static function filter_post($post) {
        //var_dump($post);

        $returnPost["ID"] = $post->ID;
        $returnPost["post_date"] = $post->post_date;
        $returnPost["post_content"] = $post->post_content;
        $returnPost["post_title"] = $post->post_title;
        $returnPost["post_name"] = $post->post_name;

        return $returnPost;
    }

    /*
     * At the end of each reply either return the reply or return an error.
     */

    static function return_code($response) {
        // if there was an error return an error
        if ($response["status_code"] !== 200) {
            //return $response;

            return new WP_Error('WPRestApiExtensions', $response["status_message"], array('status' => $response["status_code"]));
        } else {
            return $response;
        }
    }

    /*
     * Lookup one tag 
     */

    static function tag($WP_REST_Request_arg) {

        if (empty($WP_REST_Request_arg["tag_name"])) {
            return new WP_Error('WPRestApiExtensions', 'No such tag.', array('status' => 404));
        }

        $cache_key = md5("WPRestApiExtensions::tag" . $WP_REST_Request_arg["tag_name"]);

        if (false === ( $value = get_transient($cache_key) )) {

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
                    $response["status_code"] = 404;
                    $response["status_message"] = "No such tag.";
                }
            }

            // remove the filter
            remove_filter('get_tags', 'WPRestApiExtensions::filter_tag');

            if (!empty($tag)) {
                array_push($response["data"], $tag[0]);
            }

            // store in cache for 5 minutes
            set_transient($cache_key, $response, 60 * 5);

            // return the result
            return self::return_code($response);
        } else {

            // return the result
            return self::return_code($value);
        }
    }

    /*
     * From a response get a pagination object 
     */

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

    /*
     * Return an array of posts
     */

    static function posts($WP_REST_Request_arg) {

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

        $cache_key = md5("WPRestApiExtensions::posts" . serialize($args));

        if (false === ( $value = get_transient($cache_key) )) {

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
                $response["status_code"] = 404;
                $response["status_message"] = "No posts found.";
            }

            // store in cache for 5 minutes
            set_transient($cache_key, $response, 60 * 5);

            // if there was an error return an error
            return self::return_code($response);
        } else {

            // return the result
            return self::return_code($value);
        }
    }

}

// register wp hooks
add_action('save_post', 'WPRestApiExtensions::schedule_wipe_of_cache');

// add action to wipe cache
add_action('WPRestApiExtensionsWipeCache', 'WPRestApiExtensions::do_scheduled_cache_wipe');

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
