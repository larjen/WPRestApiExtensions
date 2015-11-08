=== WPRestApiExtensions ===
Contributors: larjen
Donate link: http://exenova.dk/
Tags: Toggle
Requires at least: 4.3.1
Tested up to: 4.3.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends the WP-REST API with custom read only endpoints.

== Description ==

Extends the WP-REST API with custom read only endpoints.

If you have a WordPress blog at http://www.example.com you can reach the read
only REST API by requesting these endpoints:

    http://www.example.com/wp-json/wprestapiextensions/v1/posts?page=1&per_page=12&search=query&tags=tag1+tag2
    http://www.example.com/wp-json/wprestapiextensions/v1/tag?tag_name=tag1
    http://www.example.com/wp-json/wprestapiextensions/v1/post
    
The above endpoints does read from database whenever they are requested, and are
thus very slow to react. 

From within the plugin, there is an option to deploy a cache in front of this
REST API. When you deploy it, be warned that the folder "rest-api" will be
created in the root of your webserver.

You can then request the REST-API like this:

    http://www.example.com/rest-api/v1/posts?page=1&per_page=12&search=query&tags=tag1+tag2
    http://www.example.com/rest-api/v1/tag/?_jsonp=angular.callbacks._1&tag_name=tag1

Since these requests are infinitely cached, you can schedule an optional wipe
of the cache which will occur 5 minutes after last post alteration.

== Installation ==

1. Download to your Wordpress plugin folder and unzip.
1. Activate plugin.

== Frequently Asked Questions ==

= Do I use this at my own risk? =

Yes.

== Screenshots ==

== Changelog ==

= 1.0.6 =
* Refactoring plugin for better performance.
* Adding an infinite cache for faster rest-api requests.
* Deploy cache mechanism from control panel.
* Optional wipe cache functionality when posts have been altered.

= 1.0.5 =

* Added search capability.
* Added simple pagination.
* Added a five minute caching.

= 1.0.4 =

* Added new endpoints for posts.
* Wrapping responses

= 1.0.3 =

* If you request a tag with spaces in the name, before failing it will also try searching for a tag where spaces has been replaces with '-' tags.

= 1.0.2 =
* Added endpoint so you can get a tag.

= 1.0.1 =
* Added categories to response for posts.

= 1.0.0 =
* Uploaded plugin.

== Upgrade Notice ==
