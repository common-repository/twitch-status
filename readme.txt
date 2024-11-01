=== Twitch Status ===
Contributors: nicolas.bernier
Tags: Twitch.tv, tag, AJAX, status, widget, thumbnail, embed, player, video, chat, chatbox, easy, shortcode
Requires at least: 4.6
Tested up to: 5.4.1
Stable tag: 1.5.1
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Inserts Twitch.tv stream player and chatbox in your posts, stream widget and online status tags in your menus. Supports multiple channels.

== Description ==

Inserts Twitch.tv stream status tags in your blog. The tags just indicates if the stream is live with a blinking red cirle or offline.

Supports multiple channels.

Also implements a simple widget showing the stream status (including the thumbnail, title, game name and number of viewers) + CSS classes to show and hide some elements of the markup accordingly to the channel status.

The tags and the widget are updated every 30 seconds.

== Installation ==

= Install the plugin =

1. Download and unzip twitch-status archive contents to the `/wp-content/plugins/twitch-status` directory or add it using WordPress' plugin manager.
2. Activate the plugin through the 'Plugins' menu in WordPress.

= Configure your channels =

1. Go to *Settings* / *Twitch status*
2. Enter the name of all the channels you want to use with the plugin, including in widgets.
3. (optional) Set the jQuery selector(s) for each channel matching the places you want to insert a status tag. Check the F.A.Q. for more information about how to use them (it's easy). Leave this blank if you just want to use the plugin.

= Add widgets =

1. Go to *Appearance* / *Widgets*
2. Drag and drop the *Twitch status widget* wherever you want to use it.
3. Choose the Twitch channel you want to show up among the ones you have previously entered in the settings.

= Insert Twitch video player in a post =

To insert a video player without the chatbox, use shortcode `[twitch-player channel="CHANNEL_NAME"]`. With the chatbox, use shortcode `[twitch-player channel="CHANNEL_NAME" chat="true"]`.
Replace CHANNEL_NAME with the name of a channel you have previously configured in the settings.

= Insert Twitch chatbox in a post =

Use shortcode `[twitch-chat channel="CHANNEL_NAME"]` where CHANNEL_NAME is the name of a channel you have previously configured in the settings.

= Insert "Live" tag when my channel is online in a post =

Use shortcode `[twitch-status channel="CHANNEL_NAME"]` where CHANNEL_NAME is the name of a channel you have previously configured in the settings.

== Frequently Asked Questions ==

= I want to add a widget for my channel but there is no way to set my channel name =

You must first add your channel in the plugin's settings page. Then, you will be able to select your channel in the widget's settings.

= I want to add a stream status tag on my "Twitch" tab. How do I find the matching jQuery selector? =

You can find the jQuery selector by using the browser developers tools (right click / inspect on element) to get the id and/or classes of the element. If you have an id, just prepend the # symbol to it and you have it. For example, if your element has ID `menu-item-582`, the jQuery selector would be `#menu-item-582`. If the menu element has a link inside it (`a` element), add the a element in the selector `#menu-item-582 a`.

If your element doesn't have an id but a class, use the class instead. The matching selector would have a `.` instead of a `#` (ie `.menu-item-582 a`).

Fore more information about jQuery selectors, check out http://api.jquery.com/category/selectors/

= I added the right shortcode to embed my channel's player but all I see is a grey block =

Check if you have correctly added your channel name in the settings page before.

= Can I show and hide some parts of my page accordingly to my channel status? =

Yes! There are 3 CSS classes `twitch-is-online`, `twitch-is-offline` and `twitch-is-loading` to be used in conjunction with the `data-twitch-channel` HTML attribute with your channel name `data-twitch-channel="CHANNEL_NAME"` (where `CHANNEL_NAME` is the name of your channel) to achieve this.

Please be aware that the channel must be added on the settings page.

For example, if your channel name is "nolife":

`Nolife is <span data-twitch-channel="nolife"><span class="twitch-is-online">online to kick ass and chew bubble gum!</span><span class="twitch-is-offline">offline. Eighters gonna 8.</span><span class="twitch-is-loading">...</span></span>`

== Screenshots ==

1. The stream status tag when online and offline.
2. The stream status widget when online.
3. The stream status widget when offline.
4. Embedded video player with chat, with French localization.

== Changelog ==

= 1.5.1 =
* Fixed metadata

= 1.5.0 =
* Updated for the new Twitch Helix API
* Minor bugfixes and improvements

= 1.4.2 =
* Chat now fits screen size when viewing embedded player + chat combo on a mobile screen.
* Fixed options page bugs

= 1.4.0. =
* Now using HTML5 data to update elements (CSS classes are still supported for backward compatibility)
* Added shortcode [twitch-status] for status tag
* Added shortcode [twitch-player] for embedded player
* Added shortcode [twitch-chat] for embedded chatbox
* Properly rewrote some parts of code

= 1.3.0 =
* Optimized loading time of the tags and the widgets.
* Widget links are now active all the time, even when the channel is offline.
* Fixed widget links to Twitch channels.
* Improved documentation.

= 1.2.2 =
* Fixed calls to Twitch.tv API.

= 1.2.1 =
* Added CSS classes `twitch-is-online`, `twitch-is-offline` and `twitch-is-loading` to be used in conjunction with `twitch-status-channel-CHANNEL_NAME` to enable/disable some elements of the page accordingly to the channel status.
* Fixed widget size issues

= 1.2 =
* Added multiple channel support

= 1.1 =
* Added stream status widget

= 1.0 =
* First release

== Upgrade Notice ==

= 1.1.x to 1.2.x and up =

If you added some custom Twitch status tags, you have to add the CSS class `twitch-status-channel-CHANNEL_NAME` (where `CHANNEL_NAME` is the name of your channel) to the markup of each of them.