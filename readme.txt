=== LinkWorth Plugin ===
Contributors: linkworth
Donate link: http://linkworth.com
Tags: ads, sidebar, post, content, ad, text links, links
Requires at least: 2.3
Tested up to: 6.0
Stable tag: 3.3.5

Easily publish different types of text link products and in-content ads from linkworth.com.

== Description ==

Easily publish different types of text link products and in-content ads from [LinkWorth](http://www.linkworth.com).  This plugin does most of the work on your blog and centralizes the administration into a single settings page.  Plugin users must have a Partner account from [LinkWorth](http://www.linkworth.com).

* Widgets enabled
* CSS Style your ads
* Preset style options
* Debugging option
* List of all approved ads
* Customize on settings page
* WordPress Multisite capable: just install and go on all your blogs.

== Installation ==

LinkWorth can easily be integrated into your WordPress blog by following a few simple steps.

1. Connect to your server via a file transfer method like FTP. This step is identical to that in which you uploaded WordPress to your server or hosting account. If this was done by someone other than you, you are best to consult with that individual and find out how to complete this step.
2. Once you are connected to the server, upload the entire LinkWorth folder to wp-content/plugins/. These directories are located in the WordPress root (main) directory.
3. Login into your WordPress administration panel.
4. Click on the Plugins tab. If you uploaded the folder in Step 2 into the correct location on your server, in the list of plugins you will see "LinkWorth". Click on the corresponding "Activate" link.

== Frequently Asked Questions ==

= How do I know if my theme uses widgets? =

If in the WordPress administration panel you have an option called 'widgets' under the 'design' or 'theme' menu, click on it. If the resulting page allows you to add widgets, your current theme uses widgets. if you do not see the link, or you receive a message that your theme is not widget enabled, your theme does not use widgets.

= What is the name of the LinkWorth widget? =

The widget is called 'Links Widget' under the 'widgets' section.

= What does 'Add rotating ads after what loop' mean? =

On some themes, there are multiple WordPress "loops" where posts are displayed. By default, LinkWorth ads are displayed at the bottom of the first of these loops. On most themes, this is correct, but occasionally custom themes will use custom loops to display posts in different ways.

If your ads appear in a different place than where you would expect them, you may talk to your theme author and ask them which loop is the "main" loop (such as the first or second loop). Afterwards, you may add the loop number (1,2,3 etc.) into the box to have ads display after it.

= What is 'Disable silent running' mean? =

Normally, LinkWorth does not add anything but the deals to your blog. Selecting to Disable silent running will add LinkWorth comments where deals are published.

== Changelog ==

= 3.3.5 =
* Replaced php functions with WP functions.
* Data is now sanitized, escaped, and validated.
* Variables and options are being escaped when echo'd.

= 3.3.4 =
* Replaced curl with wp_remote_get function.
* Data is now sanitized, escaped, and validated.
* Variables and options are being escaped when echo'd.

= 3.3.3 =
* Added nonce

= 3.3 =
* Tested up to 4.1

= 3.2.8 =
* Removed LinkWords.

= 3.2.7 =
* Fixed php warning in foreach loop.

= 3.2.6 =
* Fix for anchor texts that contain special chars.

= 3.2.5 =
* Fix for LinkInTxt links

= 3.2.4 =
* Updated Loop Count Value
* Updated Tested Version

= 3.2.3 =
* Added check for 'Allow Link Description.'
* Added display of both titles and descriptions.
* Updated function for cleaning title and descriptions.
* Updated check for home page.

= 3.2.2 =
* Added support for plugins using 'Safe Mode' in PHP.

= 3.2.1 =
* Added new warning for more then one instance of 'The Loop.'
* Updated function location for identification and linkwords.
* Updated location for determining 'The Loop' count.
* Fix for 'Update Deal List' button not working properly.
* Fix for LinkWords not showing comment with silent disabled.

= 3.2.0 =
* Added 'LinkWorth' tab for settings.
* Added new version of 'debug' for LinkWorth admins.
* Added option for LinkInTxt to be displayed as 'Tags' on single pages.
* Added option to set link size; they are not random now.
* Updated additions for user identification system.
* Updated the look of settings for closer match to WordPress styles.
* Updated 'Link Styles' for easier layout and options.
* Removed LinkSura code. The product is not longer avaliable at LinkWorth.
* Removed the 'LinkWorth' link from under 'Settings' tab.
* Removed 'LinkMura in sidebar' option.
* Removed 'Do not display after loop' option.
* Removed old 'debug' option.
* Fix for 'Settings' page split into 'General' and 'Advanced' setting pages.
* Fix for LinkBB default path name changed to 'pages.'
* Fix for widget default title changed to 'Friends.'
* Fix for LinkInTxt to only show on their required page.
* Fix for identification of 'The Loop' by plugin.
* Fix for how links are passed to the browser.
* Fix for detection of 'The Loop' on pages.

= 3.1.22 =
* Fix for home page deals showing up on sub-page(s) considered the home page by WordPress.
* Easier identification of plugin users for LinkWorth's system.

= 3.1.2 =
* Fix for Linkwords being displayed on single posts when option to disable LinkWords was selected

= 3.1.19 =
* Linkwords are able to be displayed without other deals

= 3.1.18 =
* Fix WordPress multisite to update blogs Website ID and Hash correctly
* Fix updated function to register widget and register widget control [You will need to set widget back to the sidebar]
* Update admin page to show multisite Website ID and Hash and Website ID and Hash per site correctly
* Update LinkAds now have title attributes set in LinkWorth control center

= 3.1.16 =
* Update LinkMuras now have title attributes set in LinkWorth control center

== License ==

Copyright 2008 LinkWorth (support@linkworth.com)
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as
published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the
Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
