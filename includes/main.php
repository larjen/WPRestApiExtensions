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
        self::add_message('Plugin WPRestApiExtensions activated.');
    }

    /*
     * Deactivation
     */

    static function deactivation() {
        delete_option(self::$plugin_name . "_MESSAGES");
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

    static function filter_posts($post) {

        //foreach($post as $key => $value){
        //    error_log($key."=".$value);
        //}


        $returnPost["ID"] = $post->ID;
        $returnPost["post_date"] = $post->post_date;
        $returnPost["post_author"] = $post->post_author;
        //$returnPost["post_content"] = $post->post_content;
        $returnPost["post_title"] = $post->post_title;
        $returnPost["post_name"] = $post->post_name;
        $returnPost["post_excerpt"] = $post->post_excerpt;

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

        // return the result
        return self::return_code($response);
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


        // now build the pages to return
        $the_query = new WP_Query($args);

        $response = [];

        $response["data"] = [];

        $response["uri"] = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s:" : ":") . "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        foreach ($the_query->get_posts() as $post) {

            // just add the fields we need
            $returnPost = self::filter_posts($post);

            // add tags
            $returnPost["tags"] = self::filter_tag(wp_get_post_tags($post->ID));

            // add categories to the post
            $categoryIds = wp_get_post_categories($post->ID);
            $returnPost["categories"] = [];
            foreach ($categoryIds as $categoryId) {
                $cat = get_category($categoryId);
                array_push($returnPost["categories"], self::filter_category($cat));
            }

            // add images to the response
            $returnPost["images"] = self::get_images($post->ID);

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

        // if there was an error return an error
        return self::return_code($response);
    }

    /*
     * gets images for a specific post
     */


    static function get_images($post_id){
        include_once( ABSPATH . 'wp-admin/includes/image.php' );

        // now get any images attached to the post
        $args = array(
            'post_parent' => $post_id,
            'post_type'   => 'any',
            'numberposts' => -1,
            'post_status' => 'any'
        );

        $images = get_children ( $args );
        $images_meta = [];

        foreach ($images as $image){

            $new_image_temp = get_post_meta($image->ID);
            
            // unset the data we dont need
            if (isset($new_image_temp["type"][0])){
                $new_image["type"] = $new_image_temp["type"][0];
            }
            
            if (isset($new_image_temp["img_path"][0])){
                $new_image["img_path"] = $new_image_temp["img_path"][0];
            }

            $sizes_temp = wp_get_attachment_metadata($image->ID,false);
            
            if (isset($sizes_temp["sizes"])){
                $new_image["sizes"] = $sizes_temp["sizes"];
            }
            
            array_push($images_meta, $new_image);
        }

        return $images_meta;
    }

    /*
     * Return one post from post_name (slug)
     */

    static function post($WP_REST_Request_arg) {

        if (isset($WP_REST_Request_arg["post_name"])) {
            if (empty($WP_REST_Request_arg["post_name"])) {
                return new WP_Error('WPRestApiExtensions', 'No posts found.', array('status' => 404));
            }
        } else {
            return new WP_Error('WPRestApiExtensions', 'No posts found.', array('status' => 404));
        }

        // get the post from the slug
        $args = array(
            'name' => $WP_REST_Request_arg["post_name"],
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 1
        );

        $post = get_posts($args);
        if (empty($post)) {
            return new WP_Error('WPRestApiExtensions', 'No posts found.', array('status' => 404));
        }

        $post = $post[0];

        $response = [];

        $response["data"] = [];

        $response["uri"] = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s:" : ":") . "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // just add the fields we need
        //$returnPost = self::filter_posts($post);

        // add tags
        $post->tags = self::filter_tag(wp_get_post_tags($post->ID));

        // add categories to the post
        $categoryIds = wp_get_post_categories($post->ID);
        $post->categories = [];
        foreach ($categoryIds as $categoryId) {
            $cat = get_category($categoryId);
            array_push($post->categories, self::filter_category($cat));
        }

        // add images to the response
        $post->images = self::get_images($post->ID);

        array_push($response["data"], $post);

        $response["status_code"] = 200;


        // if there was an error return an error
        return self::return_code($response);
    }

}

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
add_action('rest_api_init', function () {
    register_rest_route('wprestapiextensions/v1', '/post', array(
        'methods' => 'GET',
        'callback' => 'WPRestApiExtensions::post',
    ));
});
