=== MF Gig Calendar ===
Contributors: brewermfnyc
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=matthew@matthewfries.com
Tags: calendar, event, gig, musician, Matthew Fries, brewermfnyc
Plugin URI: https://matthewfries.com/mf-gig-calendar
Author URI: https://matthewfries.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 3.3
Tested up to: 6.2.2
Stable tag: 1.2.1

A simple event calendar created for musicians but useful for anyone. Supports multi-day events, styled text, links, images, and more.

== Description ==

I'm Matthew Fries (that's where the MF in MF Gig Calendar comes from) and I'm a jazz pianist and educator. I'm not able to support this plugin as enthusiastically as before, but I developed it originally because I wanted a flexible and easy to use performance calendar for [my own music website](https://matthewfries.com). In the process I tried to create something that would work for more than just musicians. I've added a few features since it started:

= Current Features =
* beginning and end dates for multiple-day events
* Wordpress's WYSIWYG editor for the event description so you can include styled text, links, images and other media in your event list
* a duplicate function to make it easier if you have a repeating event that you don't want to re-enter over and over
* an RSS feed
* a widget to list a few upcoming events in the sidebar
* an archive to view past events by year
* supports localization (waiting on translations...)
* shortcode variables to customize the output

The calendar can be placed in any **PAGE** or **POST** on your Wordpress site - even in more than one place. Just include the following short code where you want the calendar to appear: 

`[mfgigcal]`

Requires WordPress 3.3 or newer because of the changes to the Wordpress WYSIWYG editor. Personally tested up to 6.2.2.

Want to keep in touch? Here are a few options...

* [www.matthewfries.com](https://matthewfries.com)
* [facebook](http://www.facebook.com/matthewfriesmusic)
* [twitter](http://www.twitter.com/mfjazz)

As a general rule I've tried to keep this as simple and flexible as possible.


== Installation ==

1. Upload the folder 'mf-gig-calendar' to the '/wp-content/plugins/' directory,
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Insert the shortcode `[mfgigcal]` wherever you want the calendar to appear (read the instructions in the widget for advanced display options).
4. Start entering events under the 'Event Calendar' menu!

Easy peasy.


== Frequently Asked Questions ==

= How do I get a calendar to show up in a Page or Post on my site? =
Easy! Use the MF Gig Calendar shortcode: `[mfgigcal]`

Just put that code on any Page or Post in the spot where you want the calendar to appear. Wordpress does the rest and any settings are optional.

= How do I use styled text and images in my event descriptions? =
MF Gig Calendar uses the built-in Wordpress WYSIWYG editor. It's exactly the same process you use when creating Posts or Pages.

= Does MF Gig Calendar put a bunch of junk in my Wordpress database? =
One database table is installed to store your events. That's it!

= What happens if I add more than one event on a particular day? =
The output is only sorted by date, so if you enter multiple events on the same day they will be displayed on most servers in the order you created them. I chose not to sort by time because I don't require you to enter a time for your event and allow you to use whatever term you want (2pm, afternoon, all day, 20.30, etc).

= Can I change the look of the calendar on my site? =
Most of the look of the calendar is controlled by your theme. A very small stylesheet to control basic layout is included in the file '/wp-content/plugins/mf-gig-calendar/mf_gig_calendar.css'. The stylesheet contains an outline/example of the basic output to help you style the calendar as you like. Got your own interesting layout you'd like to share for me to include in a future update? Let me know!

= Why do you ask me to enter a URL for my calendar in the settings? Shouldn't that just happen automatically? =
The MF Gig Calendar widget and the RSS feed use the URL you enter in Settings to link to the calendar on your site. You can put the calendar in any Page or Post on your site - even in more than one place - so this URL is the place you want people to go to see what's going on. It's completely optional. The plugin will work fine without it.

= My calendar is blowing up the design on my page extending past the margins! The fonts don't match the fonts on my site! What is going on? =
The first thing to check is that you don't have `<pre>` tags around your shortcode. Sometimes formatting comes along when you cut and paste into the Wordpress editor. If you flip to the "Text" tab in the editor you might see something like this: `<pre>[mfgigcal]</pre>` Get those `<pre>` tags out of there!

= Why doesn't MF Gig Calendar...(insert your cool idea here)...? =
I tried to keep the basics of this plugin pretty simple so that it would be useful to a broader range of people. It's all about displaying a useful list of events on one page or post and nothing more. If you have a suggestion for a really useful feature please let me know and I'll consider adding it in a future update!

== Screenshots ==
1. Admin screen - list of events

2. Admin screen - event editor page

3. Front end - example of website display

4. Front end - example of website display

5. Front end - example of website display

== Changelog ==


**Version 1.2.1**
* Bug fixes (squish)

**Version 1.2**
* Security issue fixes!
* Now PHP 8 compatible

**Version 1.1**
* Another attempt to fix the bug causing dates to be off by one day in certain hosting environments.
* Changes to the Archives year list
* Added ability to limit the number of years listed in the Archive submenu

**Version 1.0.4**

* Fixed issue with dates being off by a day!
* Updates to be compatible with PHP 7

**Version 1.0.3**

* By popular and insistant demand "Archive" is back.
* More bug fixes

**Version 1.0.2**

* Bug fixes - sorry...

**Version 1.0.1**

* Updated French translation
* Fixed database update errors

**Version 1.0**

* UTF8 support (Note: this requires altering the mfgigcal table in your database, so please make sure you backup your data first)
* French translation by Tanguy Kerfriden
* Ability to hide details from event lists
* Fixed bug with uneditable events in admin area
* Other upgrades

**Version 0.9.9.5**

* Date selector fixes (I hope!!)
* Spanish translation by Andrew Kurtis (WebHostingHub.com)
* Ability to set the default title for individual event display

**Version 0.9.9.4**

* Layout fix! Sorry!

**Version 0.9.9.3**

* New date selection process
* Some short codes now allowed in event descriptions
* Bug fixes

**Version 0.9.9.2**

* Bug fixes - limit and offset now working correctly
* Enhanced date range options
* Improved documentation

**Version 0.9.9.1**

* Bug fixes
* Added default sort order for calendar archive
* Added the ability to turn off the default link to individual event display

**Version 0.9.9**

* Admin editor user permissions relaxed - now any user who can edit posts can access the event calendar tool
* Cleanup: removed trailing ? on empty query strings
* Swedish Translation (thanks to Marie Brunnberg)
* New shortcode attributes added to customize output
* Shortcut to grab a single event for display

**Version 0.9.8**

* The archive now (correctly) only shows past events
* Ability to choose your date format in the widget
* Ability to selectively display ranges of dates, specific dates, or a single event
* Bug squashing

**Version 0.9.7**

* Fixed links to individual events to work with some AJAX powered themes
* Localization of the Date-Picker jQuery utility
* Polish translation (thanks to Julian Battelli)

**Version 0.9.6.1**

* Added the translation tools. They did not upload in the 0.9.6 update. Subversion makes my brain hurt.

**Version 0.9.6**

* Internationalized date display is now based on Wordpress language settings
* Prepared the plugin for internationalization - waiting on translations!
* Event titles in widget now link to individual events if the calendar location is set in the plugin settings.

**Version 0.9.5**

* Security fixes! Silly me. Please update!

**Version 0.9.4.1**

* Really just a re-upload of version 0.9.4. Still trying to get my brain around subversion.

**Version 0.9.4**

* Updated the calendar layout slightly to get the event description to appear next to the date instead of below it by default in some themes.
* Updated the Settings and About pages to (hopefully) be easier to understand.
* A few other minor fixes.

**Version 0.9.3**

* Fixed a bug where the links to individual events didn't work unless permalinks was turned on.

**Version 0.9.2**

* Fixed the installer!! When I added the options page in version 0.9 it caused a problem with setting up the plugin for the first time. There was a PHP timeout you may have been experiencing. This should be fixed now! Sorry...

**Version 0.9.1**

* Fixed a typo and a couple broken links in the documentation.

**Version 0.9**

* I skipped straight to version 0.9 because I feel like I'm *almost* 100% happy with this thing and I'm ready to put it in the Wordpress repository. There were just so many enhancements in this round that I couldn't just go up one tenth. Plus I really like the number 0.9 - it kind of looks like an emoticon for a person sitting at a computer screen (use your imagination).
* Separate RSS feed.
* Options page to customize output.
* About page with installation instructions.
* Upgrades to the sidebar widget.
* Replaced table-based display with more stylable output layout.

**Version 0.4**

* Adds display of individual events from the calendar. I can share individual events on Facebook now.

**Version 0.3**

* Now requires Wordpress 3.3+ and the fully functional WYSIWYG editor works great thanks to this Wordpress upgrade!

**Version 0.2**

* Added widget displaying a few upcoming events in the sidebar. That's nice.

**Version 0.1**

* First working version. Just mucking around and learning how the heck to create a Wordpress plugin! :-P


== Upgrade Notice == 

Version 1.2 should now clear up some security issues and function correctly in a PHP8 environment.