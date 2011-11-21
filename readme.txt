=== force-frame === 
Contributors: lencinhaus
Tags: frame, iframe, force
Requires at least: 3.2.1
Tested up to: 3.2.1
Stable tag: 1.1

Force a Wordpress site inside a frame or iframe.

== Description ==

**force-frame** is a [Wordpress](http://wordpress.org/) plugin that allows you to force your Wordpress website inside a frame or iframe in another website.

= Features =

* **Redirect to parent site**: when users access your website directly, they will be redirected to the parent site where your site is shown in a frame or iframe.
* **Inject site's URL into parent site's URL**: when users navigate between pages in your site, the URL they navigate to is injected in the parent site's URL using the fragment or a GET parameter; if the parent site's URL is then shared, the iframe will load the correct page of your site

Links: [Author's Site](http://www.cubica.eu)

== Installation ==

1. Extract the downloadable archive inside *wp-content/plugins*, and activate in Wordpress administration.
2. The plugin can be configured accessing the **Force Frame** link inside the Settings menu.
3. For the plugin to work, the parent site's URL must point to a web page containing an iframe pointing to your website's homepage.

== Frequently Asked Questions == 
= Question? =
Answer 

== Screenshots == 

1. Settings management

== Changelog ==
 
= 1.0 = 
* First version 

= 1.1 =
* Corrected some bugs
* Externalized and minified JS

== Upgrade Notice == 
= 1.0 =
First version