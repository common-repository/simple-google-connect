=== Simple Google Connect ===
Contributors: Otto42
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=otto%40ottodestruct%2ecom
Tags: google, connect, simple, otto, otto42, javascript, comments, plusone, button
Requires at least: 3.3
Tested up to: 3.3
License: GPLv2
License URI: http://www.opensource.org/licenses/GPL-2.0
Stable Tag: trunk

== Description ==

Note: This plugin is not complete. Consider it a preview release. Some of it works, some may not. 

Simple Google Connect is a framework and series of sub-systems that let you add any sort of Google based functionality you like to a WordPress blog. This lets you have an integrated site without a lot of coding, and still letting you customize it exactly the way you'd like.

After activating the plugin and setting up a Google Application for your site, you can enable individual pieces of functionality to let you integrate your site in various ways.

Requires WordPress 3.3 and PHP 5. 

* Enables your site to connect to Google using Google's various APIs
* +1 button
* Sign in using Google for comments (and use Google based avatars!)
* Login using Google credentials
* Google+ Badges for pages

If you have suggestions for a new add-on, feel free to email me at otto@ottodestruct.com .

Want regular updates? Become a fan of my sites on Facebook!
http://www.facebook.com/ottopress
http://www.facebook.com/apps/application.php?id=116002660893

Or follow my sites on Twitter!
http://twitter.com/ottodestruct

== Installation ==

1. Upload the files to the `/wp-content/plugins/simple-google-connect/` directory
1. Activate the "Simple Google Connect" plugin through the 'Plugins' menu in WordPress
1. Follow the instructions that the plugin itself will give you.

== Frequently Asked Questions ==

= The comments addon isn't working! =

You have to modify your theme to use the comments plugin.

(Note: If you have WordPress 3.0 and a theme using the new comment_form() method, then this step is not necessary).

In your comments.php file (or wherever your comments form is), you need to do the following.

1. Find the three inputs for the author, email, and url information. They need to have those ID's on the inputs (author, email, url). This is what the default theme and all standardized themes use, but some may be slightly different. You'll have to alter them to have these ID's in that case.

2. Just before the first input, add this code:
[div id="comment-user-details"]
[?php do_action('alt_comment_login'); ?]

(Replace the []'s with normal html greater/less than signs).

3. Just below the last input (not the comment text area, just the name/email/url inputs, add this:
[/div]

That will add the necessary pieces to allow the script to work.

If you're using WordPress 3.0 and the new "comments_form" code (like in the Twenty Ten theme), then this is unnecessary! Check ottopress.com for info on how to upgrade your theme to use the new 3.0 features.

= Google Avatars look wrong. =

Google's avatars use slightly different code than other avatars. They should style the same, but not all themes will have this working properly, due to various theme designs and such. 

However, it is almost always possible to correct this with some simple CSS adjustments. For this reason, they are given an "google-avatar" class, for you to use to style them as you need. Just use .fbavatar in your CSS and add styling rules to correct those specific avatars.

= How do I use this with multi-site across subdomains/subdirectories? =

(This is untested. You have been warned.)

Many people want to set up a "network" of sites, and enable SGC across all of them. Furthermore, they'd like people to stay "connected" across them all, and to only use one Google Application to connect their users. This is entirely possible with a bit of setup.

First, create your Google Application. It should use the base domain field as well as the normal fields. No subdirectories or subdomains anywhere. For this example, we'll use "example.com".

Next, you can add these to your site's wp-config:
define('SGC_APP_SECRET', 'xxxxx');
define('SGC_APP_ID', 'xxxxx');

These are the exact same settings as on the normal SGC base configuration screen, and they will override those settings for the entire network of sites. In fact, when those are defined, the corresponding settings options won't even appear. This may look odd at first.

With this setup, SGC *should* work across all your subdomains and subdirectories. So it'll work on example.com or blog.example.com or otto.example.com or whatever. It should also work on example.com/blog. 

Notes: 
* If you use other domains with domain mapping, those domains will need have their oauth Redirect URIs added to the Project in the Google API Console before they will work.

= How do I use Google Avatars? =

The Comments module will automatically use Google avatars for users that leave comments using Google.

== Upgrade Notice ==

== Changelog ==

= 0.1 =
* First version.
* Comments added
* Comment avatars added
* +1 button added
* Help screens added
* Pointer added to draw attention to help screens
* Compatibility check added (WP 3.3 is required)
* Add G+ badge
* Add login support
* Beginnings of importer support