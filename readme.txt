=== SimpleSitemap ===
Author:             Anthony Ferguson
Author URI:         https://www.ferguson.codes
Contributors:		ajferg
Requires at least:	5.0
Tested up to:		6.6
Stable tag:			1.4.4
Requires PHP:       7.4
License:            GPLv3 or later
License URI:        https://www.gnu.org/licenses/gpl-3.0.html
Tags:				sitemap, posts, pages


Generates a simple sitemap for your site.

== Description ==

Generates a simple sitemap, listing the page and posts on your site.  You can set which page will become your sitemap, and the sitemap will be automatically inserted with your regular page content.

This plugin is useful in helping your visitors find their way around the site, and is beneficial for search engines crawling your site.

July 2024 - Plugin now comes with a Gutenberg compatible block, allowing you to choose which post-types to show sitemap links for.  The earlier functionality & shortcodes continue to work unchanged.

== Installation ==

1. Upload plugin to your wordpress installation plugin directory
1. Activate plugin through the `Plugins` menu in Wordpress
1. Look at the configuration screen (found under `Settings` in the Wordpress menu)
1. Choose which page to show the sitemap on, and tick which pages or post-categories you wish to exclude from the sitemap.

== Frequently Asked Questions ==

= Can I change the order in which Pages are listed? =
Pages are listed according to 'menu order'. When you edit pages, you can set the Order.  There are plugins available which let you drag/drop pages to set your preferred order.

= Are pages displayed hierarchically? =
Yes - if you have pages & subpages, they will be listed as an indented list.

= Can I change the order in which Posts are listed? =
No.  Posts are listed from newest to oldest, and are not grouped by category.

= Can I display my blog posts grouped by category? =
No, I haven't build that feature yet.

= How do I use the shortcode to show my custom post-type? =
<code>[simplesitemap view="POST_TYPE"]</code>


== Changelog ==
= 1.4.4 =
* Adding a Gutenberg block, and the build tools that go along with it

= 1.4.3 =
* Adding /assets/ folder for use on plugin repository
* Fix an error in my build/deploy script

= 1.4.1 =
* Release using github build tools

= 1.4.0 =
* Refactor plugin, and set up a git repo with SVN deployment

= 1.3.1 =
* Code formatting changes to bring inline with WordPress PHP Code Standards
* Convert class to singleton model

= 1.3 = 
* Update code to suit WordPress 4.4 standards
* Better handling of post type outputs
* Now supports Pages, Posts, and Products out of the box
* Shortcode can be used to draw additional types
* Action hooks included, might be useful to developers
* Remove the Custom Titles function - haven't used it in a long time.

= 1.1 =
* Now supports Custom Titles through Thesis Theme and All In One SEO Plugin
* Added ShortCode Support: [simplesitemap] or [simplesitemap view=pages] or [simplesitemap view=posts]
* Auto-Add to Page now allows you to insert before OR after original page content

= 1.0 =
* Initial release.
