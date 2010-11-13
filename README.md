Asset Helper
========================================

Author: Kenn Wilson
Author URL: http://www.corvidworks.com/
Project URL: http://www.corvidworks.com/projects/asset-helper/


Asset Helper brings Rails-style asset helper functions to PHP sites. Includes support for static asset hosts and timestamped query strings.


Description
-------------------

Asset Helper brings Rails-style asset helper functions to PHP. The provided functions are:

* `stylesheet_tag()`
* `javascript_tag()`
* `image_tag()`

At their most basic, these functions create the appropriate HTML tags for the supplied arguments. The benefit comes from the additional features of these functions: timestamping and asset hosts.


### Timestamping ###

Each resulting HTML tag will include a Unix timestamp representing the last-modified time of the file. For example:

	<img src="/path/to/image.jpg?1234567890" alt="" />

This allows you to set a far-future expires time for your static files while ensuring that if these files _do_ change, browsers will always get the updated version, while keeping them cached otherwise. 


### Asset Hosts ###

This plugin also allows for static file hosts. If you have one defined, the resulting HTML tags will include complete URLs pointing to the files on your static file server. For example:

	<img src="http://static.example.com/path/to/image.jpg?1234567890" alt="" />

To define a static asset host, place the following line somewhere in your application:

	define('ASSET_HOST', 'static.example.com');

Don't worry about including "http://" or "https://" -- The plugin will automatically use whichever one is used for the page the functions appear on.


Usage
-------------------

1. Add `asset-helper.php` to a directory of your web site or anywhere
   on the server that your code can find it.
2. Use the new helper functions in your templates


Documentation
-------------------

More information can be found at:
http://www.corvidworks.com/articles/asset-helper/

Complete documentation can be found at:
http://www.corvidworks.com/projects/asset-helper/



