=== Ingeni YouTube Playlist ===

Contributors: Bruce McKinnon
Tags: video, YouTube
Requires at least: 6.0
Tested up to: 6.4
Stable tag: 2025.04

Display a grid, lightbox or mainstage of YouTube videos from a playlist or channel, or a list of video IDs



== Description ==

* - Displays a grid of videos from a users YouTube playlist or channel, or supply a list of individula video IDs.

* - Can be displayed as a simple grid with individual players, a grid of thumbnails with a single mainstage player, or a grid of thumbnails that open in a full-screen lightbox.



== Installation ==

1. Upload the 'ingeni-youtube-playlist' folder to the '/wp-content/plugins/' directory.

2. Activate the plugin through the 'Plugins' menu in WordPress.

3. Add you YouTube API key via the settings.

4. Display the videos using the shortcode



== Frequently Asked Questions ==

Q - Do I need a Google API key??

A - Yes! You must provide a Google API key that has the YouTube Data API v3 AND YouTube Embedded Player API enabled. Plus, this key must be permitted for use on your domain name.

Go to API console at: https://console.cloud.google.com/apis/library

For more help, go to: https://support.google.com/googleapi/answer/6158862?hl=en

You may store the API key in Settings > YouTube Playlist admins settings, or you can provide it as a shortcake parameter.



Q - Do I need to provide BOTH the channel ID and playlist ID?

A - No, you provide either the playlist ID or the channel ID. If you do provide both, the playlist ID will be used.


Q - Where do I find the channel ID?

A - The channel ID is normally displayed in the URL, but if your YouTube page has a name, use the following instructions to obtain the channel ID:

https://mixedanalytics.com/blog/find-a-youtube-channel-id/



Q - Where do I find the playlist ID?

A: The playlist ID is normally displayed in the URL. For example:

https://www.youtube.com/playlist?list=PLi66rcYcdNzmQmYGm2sN3RESydBvdJM5q

The playlist ID is PLi66rcYcdNzmQmYGm2sN3RESydBvdJM5q



Q - Can I specify a list of YT videos, rather than a playlist?

A: Yes you can. Use the 'video_ids' parameter and provide a list of YT video IDs separated by commas. For example:

[ingeni-youtube-playlist video_ids="AHJj25PBIhg,H8yuUzaebds,j3u6_8uNL7o,89NcfopvAdU" show_description=1 show_title=1 show_image=1]



Q - Can I use a custom template?

A: Yes you can. Use the 'template_file' parameter. This is the filename of your custom template. Templates must exist in '/plugins/ingeni-youtube-playlist/templates' or '/themes/{your_theme}/ingeni-youtube-playlist/templates'. The easiest way to create a new template is to copy one of the two standard templates from '/plugins/ingeni-youtube-playlist/templates'. Rename it and change the PHP namespace to match the filename.

The shortcode must also include the template name.

[ingeni-youtube-playlist channel_id="UCcp-HjtmTMeIJ-0RrSHSGLA" show_description=0 show_title=1 show_image=1 mode=2 template_file="iytpl_template_mainstage.php"]




= How do a display the videos? =

Use the shortcode like:

[ingeni-youtube-playlist channel_id="{your-youtube-channel-id}"]

Or:

[ingeni-youtube-playlist playlist_id="{your-youtube-playlist-id}"]



The following parameters may be included:

channel_id: The ID of the YouTube channel you wish to display.

playlist_id: The ID of the YouTube playlist you wish to display.

video_ids: List of YT video IDs, comma seperated

yt_api_key: A valid Google API key, that provides access to the YouTube Data API v3. This can be obtained from https://console.cloud.google.com/apis/library.

class: Wrapping class name for all of the videos. Defaults to 'yt_videos'.

max_results: Max. number of videos to display. Default = 6.

framework: Both Bootstrap 5 and Foundation 6 row and column classes are supported.

To use Foundation 6 classes:

[ingeni-youtube-playlist framework="foundation" playlist_id="{your-youtube-playlist-id}" yt_api_key="{your_youtube_api_key}"]

If no framework value is provide, Bootstrap 5 classes are used.


debug: Set to 1 to switch on debug logging. Default = 0 (off).

mode: Specify whether to show a grid of individual YT players, or a 'MainStage' layout with a single player and clickable thumbnails of the individual videos. 0 = grid of players, 1 = lightbox, 2 = mainstage mode
		'template_file' => '',
		'video_ids' => '', // List of YT video IDs comma-seperated


show_title: Defaults to 1 = show the video title

show_image: Defaults to 1 = show the video thumbnail

show_description: Defaults to 1 = show the video description




== Changelog ==

v2024.01 - Initial version

v2024.02 - Added options page to the Settings menu.
	 - Improved on-screen error reporting.

v2024.03 - Improved JS to handle pages with no YT videos.

v2024.04 - Added 'debug' parameter to the shortcode
	- Added caching support. 0 = no caching. 10080mins = 1 week. Default = 1440mins (1 day).

v2025.01 - Added custom templates

v2025.02 - Implemented mainstage mode

v2025.03 - Fixed path to custom templates.
	 - Now supports a thumbnails folder - saves the default thumbnails, but also allows you to replace them.
	 - Implements the video_ids parameter.

v2025.04 - Added support for a fullscreen lightbox via mode=1
