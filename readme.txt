=== Simple Page Ordering ===
Contributors: jakemgold
Donate link: http://www.thinkoomph.com/plugins-modules/wordpress-page-order-plugin/
Tags: order, re-order, ordering, pages, page, manage, menu_order, hierarchical, ajax, drag-and-drop, admin
Requires at least: 3.0.1
Tested up to: 3.1
Stable tag: 0.9.1

Order your pages and other hierarchical post types with simple drag and drop. Also adds a drop down to change items per page.

== Description ==

Order your pages (and any hierarchical custom post types) with simple drag and drop on the page (or custom type) management / list screen. 

The following video is from an earlier build (0.7) that has been refined.

[youtube http://www.youtube.com/watch?v=wWEVW78VF30]

Simply drag and drop the page into your desired position! It's that simple. No new admin menus pages, no dedicated clunky user interfaces. Just drag and drop on the page list screen.

To facilitate the menu order management on sites with many pages, the plug-in also adds a new drop down filter allowing you to customize the paging (pages per page) on the page admin screen. Your last choice will even be saved whenever you return (on a user to user basis and post type by post type basis)!

The plug-in is "capabilities smart" - only users with the ability to edit others' pages (i.e. editors and administrators) will be able to reorder pages.

Integrated help is included! Just click the "help" tab toward the top right of the screen; the help is below the standard help for the screen.

Note that this plug-in only allows drag and drop resort within the same branch in the page tree / hierarchy for a given page. You can instantly change the hierarchy by using the Quick Edit feature built into WordPress and changing the "Parent" option. This may be addressed in the future, but the intention is to avoid confusion about "where" you're trying to put the page. For example, if you move a page after another page's last child, are you trying to make it a child of the other page, or position it after the other page? Ideas are welcome.

This plug-in is being released as a "beta" in the Google sense. There are no known issues, but it requires much more comprehensive testing with custom post types and environments with large number of pages before we can label it "1.0". You must have JavaScript enabled for this plug-in to work.

Please note that the plug-in is currently only minimally compatible with Internet Explorer 7 and earlier, due to limitations within those browsers.


== Installation ==

1. Install easily with the WordPress plugin control panel or manually download the plugin and upload the extracted
folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Start dragging and dropping by going to the "Page" admin menu (or custom post type equivalent)!


== Screenshots ==

1. Changing the paging (items per page)
1. Dragging the page to its new position
1. Processing indicator


== Changelog ==

= 0.9 =
* Fix page count display always showing "0" on non-hierarchical post types (Showing 1-X of X)
* Fix hidden menu order not updating after sort (causing Quick Edit to reset order when used right after sorting)
* "Move" cursor only set if JavaScript enabled
* Added further directions in the plug-in description (some users were confused about how to use it)
* Basic compatibility with 3.1 RC (prevent clashes with post list sorting)

= 0.8.4 =
* Loosened constraints on drag and drop to ease dropping into top and bottom position
* Fixed row background staying "white" after dropping into a new position
* Fixed double border on the bottom of the row while dragging
* Improved some terminology (with custom post types in mind)

= 0.8.2 =
* Simplified code - consolidated hooks
* Updated version requirements