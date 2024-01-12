=== Ingeni YouTube Playlist ===

Contributors: Bruce McKinnon
Tags: video, YouTube
Requires at least: 6.0
Tested up to: 6.4
Stable tag: 2024.01

Display a grid of YouTube videos from a playlist or channel.



== Description ==

* - Displays a grid of videos from a users YouTube playlist or channel



== Installation ==

1. Upload the 'ingeni-yt-playlist' folder to the '/wp-content/plugins/' directory.

2. Activate the plugin through the 'Plugins' menu in WordPress.

3. Display the videos using the shortcode



== Frequently Asked Questions ==

Q - Do I need a Google API key??

A - Yes! You must provide a Google API key that has the YouTube Data API v3 AND YouTube Embedded Player API enabled. Plus, this key must be permitted for use on your domain name.

Go to API console at: https://console.cloud.google.com/apis/library

For more help, go to: https://support.google.com/googleapi/answer/6158862?hl=en



Q - Do I need to provide BOTH the channel ID and playlist ID?

A - No, you provide either the playlist ID or the channel ID. If you do provide both, the playlist ID will be used.


Q - Where do I find the channel ID?

A - The channel ID is normally displayed in the URL, but if your YouTube page has a name, use the following instructions to obtain the channel ID:

https://mixedanalytics.com/blog/find-a-youtube-channel-id/


Q - Where do I find the playlist ID?

A: The playlist ID is normally displayed in the URL. For example:

https://www.youtube.com/playlist?list=PLi66rcYcdNzmQmYGm2sN3RESydBvdJM5q

The playlist ID is PLi66rcYcdNzmQmYGm2sN3RESydBvdJM5q




= How do a display the videos? =

Use the shortcode like:

[ingeni-yt-grid channel_id="{your-youtube-channel-id}" yt_api_key="{your_youtube_api_key}"]

Or:

[ingeni-yt-grid playlist_id="{your-youtube-playlist-id}" yt_api_key="{your_youtube_api_key}"]


The following parameters may be included:


channel_id: The ID of the YouTube channel you wish to display.

playlist_id: The ID of the YouTube playlist you wish to display.

yt_api_key: A valid Google API key, that provides access to the YouTube Data API v3. This can be obtained from https://console.cloud.google.com/apis/library.

class: Wrapping class name for all of the videos. Defaults to 'yt_videos'.

max_results: Max. number of videos to display. Default = 6.

framework: Both Bootstrap 5 and Foundation 6 row and column classes are supported.

To use Foundation 6 classes:

[ingeni-yt-grid framework="foundation" playlist_id="{your-youtube-playlist-id}" yt_api_key="{your_youtube_api_key}"]

If no framework value is provide, Bootstrap 5 classes are used.



== Changelog ==

v2024.01 - Initial version
