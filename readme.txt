=== WP-Cat2Calendar ===
Contributors: andddd
Donate link: http://codeispoetry.ru/
Tags: calendar, post, category, future post, organize, agenda
Requires at least: 2.8.4
Tested up to: 3.0.3
Stable tag: 1.0.8

WP-Cat2Calendar is a plugin which can organize posts into a calendar by category(/ies).

== Description ==

WP-Cat2Calendar is a plugin which can organize posts into a calendar by category(/ies). It supports a shortcode so you can create a lot of calendars with different settings for different posts or pages on your blog. Every day in the calendar will list the posts published on that day for the selected categories.

= Usage =

Use `WP-Cat2Calendar` shortcode in your post/page to add a calendar.

You can add a calendar using the php lines:

`$options = array(...);
global $wpCat2Calendar;
echo $wpCat2Calendar->display($options);`


= Options =

* <tt>cat_id</tt> – a comma separated list of category ID's.<br/>1.0 – 1.0.1 – uncategorized by default.<br/>1.0.2 – all categories by default.<br/>You also can use a special keyword post_author which will be replaced with a post author ID where shortcode is placed.<br/>WordPress bug (still in 2.8.5) at wp-includes/query.php line 1979 in exclusion so you can exclude only one author, but you can include multiple.
* <tt>author_id</tt> – a comma separated list of author ID's. (all authors by default)<br/>since: 1.0.2<br/>You also can use a special keyword post_author which will be replaced with a post author ID where shortcode is placed.<br/>WordPress bug (still in 2.8.5) at wp-includes/query.php line 1979 in exclusion so you can exclude only one author, but you can include multiple.
* <tt>year</tt> – year you want to display in calendar (current year by default)
* <tt>month</tt> – month you want to display in calendar (current month by default)
* <tt>show_nav</tt> – show/hide month/year navigation, 0 or 1 (0 by default)
* <tt>show_date</tt> – show/hide selected month/year title, 0 or 1 (0 by default). Have no affect if navigation is shown.
* <tt>allow_change_date</tt> – allow user to navigate through a calendar even if navigation is hidden and user has direct link. Has no affect if navigation is shown.


= Examples =

`[WP-Cat2Calendar cat_id="3,4" show_nav="1" year="2009" month="10"]`

It will show a calendar of posts for WordPress categories with ID 3 and 4 with navigation and the start date for a calendar will be October, 2009.

`[WP-Cat2Calendar cat_id="1" show_nav="1"]`

It will show a calendar of posts for WordPress category ID 1 with navigation and the start date for a calendar will be current date.

`[WP-Cat2Calendar author_id="1, 2, 3" cat_id="-4,-5"]`

It will show a calendar of posts posted by users with ID 1, 2, 3 for all WordPress categories excluding categories with ID 4 and 5.

`[WP-Cat2Calendar author_id="-post_author"]`

Show a calendar of posts posted by any user except a posts which belongs to the author of post where shortcode is placed.

`[WP-Cat2Calendar author_id="post_author"]`

Show a calendar of posts posted by the author of post where shortcode is placed.


See the [WP-Cat2Calendar homepage](http://www.codeispoetry.ru/wp-cat2calendar "WP-Cat2Calendar homepage") for further information.

== Installation ==

WP-Cat2Calendar requires WordPress 2.8.4 or higher.

* Download and extract the plugin onto your computer
* Fill in the plugin extraction directory/folder to your blog's plugins directory (usually wp-content/plugins)
* Activate the plugin

== Frequently Asked Questions ==

N/A

== Screenshots ==

1. How it looks
2. Widgets support
3. Widget settings
4. Settings page

== Changelog ==

= Upcoming 1.0.9 =
 * Plugin context help update
 * 'No future post' support

= 1.0.8 =
 * Navigation URL hot fix for perma-structs without trailing slash

= 1.0.7 =
 * The most URL Rewrite issues fixed
 * 'No Future Posts' support removed till next release

= 1.0.6 =
 * Date format setting (date_format shortcode parameter)
 * Show prev/next month/year feature
 * New widget options: category select, author select, cell height
 * "Growing" URL bug fix
 * Minor CSS fixes

= 1.0.5 =
 * Widgets support
 * URL Rewrite issues, but some issues are still unfixed
 * Major JS fixes
 * cell_height shortcode parameter
 * Clickable dates following to category archive
 * Timezone fix
 * Minor visual changes
 * Minor bug fixes

= 1.0.4 =
* URL Rewrite conflict with other plugins is solved
* Design and behaviour were reworked. Now it shows posts list with excerpts when cursor is over.

= 1.0.3 =
* Localizable navigation date format
* Months localization through WordPress
* Title attribute added to the post links
* Current date fix (wrong timezone)
* URL Rewrite fixes, now it correctly works and even outside WordPress loop
* Default theme changes
* Contextual help update
* Plugin's direct call protection

= 1.0.2 =
* author_id option added
* include/exclude author/category support
* insignificant code improvements and IE8 fixes in default.css

= 1.0.1 =
* WP 2.6 related fix for default css

= 1.0 =
* Initial release
