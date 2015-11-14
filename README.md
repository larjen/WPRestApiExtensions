# WPRestApiExtensions

Extends the WP-REST API with custom read only endpoints.

If you have a WordPress blog at http://www.example.com you can reach the read only REST API by requesting these endpoints:

    http://www.example.com/wp-json/wprestapiextensions/v1/posts?page=1&per_page=12&search=query&tags=tag1+tag2
    http://www.example.com/wp-json/wprestapiextensions/v1/tag?tag_name=tag1
    http://www.example.com/wp-json/wprestapiextensions/v1/post?post=some-post-slug
    
The above endpoints will return a 404 error if nothing was found.

## Installation

1. Download to your Wordpress plugin folder and unzip.
2. Activate plugin.

## Changelog

### 1.0.7
* Moving the cache mechanism to its own plugin at: [https://github.com/larjen/WPRestCache](https://github.com/larjen/WPRestCache)

### 1.0.6
* Refactoring plugin for better performance.
* Adding an infinite cache for faster rest-api requests.
* Deploy cache mechanism from control panel.
* Optional wipe cache functionality when posts have been altered.

### 1.0.5
* Added search capability.
* Added simple pagination.
* Added a five minute caching.

### 1.0.4
* Added new endpoint for posts
* Wrapping responses

### 1.0.3
* If you request a tag with spaces in the name, before failing it will also try searching for a tag where spaces has been replaces with '-' tags.

### 1.0.2
* Added endpoint so you can get a tag.

### 1.0.1
* Added categories to the post response.

### 1.0.0
* Uploaded plugin.

[//]: title (WPRestApiExtensions)
[//]: category (work)
[//]: start_date (20151030)
[//]: end_date (#)
[//]: excerpt (WordPress plugin that extends the WP-REST API with custom read only endpoints.)
[//]: tag (GitHub)
[//]: tag (WordPress)
[//]: tag (PHP)
[//]: tag (Work)
[//]: url_github (https://github.com/larjen/WPRestApiExtensions)
[//]: url_demo (#)
[//]: url_wordpress (https://wordpress.org/plugins/WPRestApiExtensions/)
[//]: url_download (https://github.com/larjen/WPRestApiExtensions/archive/master.zip)