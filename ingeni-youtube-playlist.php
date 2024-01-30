<?php
/*
Plugin Name: Ingeni YouTube Playlist
Version: 2024.04
Plugin URI: http://ingeni.net
Author: Bruce McKinnon - ingeni.net
Author URI: http://ingeni.net
Description: Displays a grid of videos from a users YouTube playlist or channel
*/

/*
Copyright (c) 2024 Ingeni Web Solutions
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt

Disclaimer: 
	Use at your own risk. No warranty expressed or implied is provided.
	This program is free software; you can redistribute it and/or modify 
	it under the terms of the GNU General Public License as published by 
	the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 	See the GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


Requires : Wordpress 6.x or newer ,PHP 7.4+

v2024.01 - Initial version
v2024.02 - Added options page to the Settings menu.
		 - Improved on-screen error reporting.
v2024.03 - Improved JS to handle pages with no YT videos.
v2024.04 - Added 'debug' parameter to the shortcode
		 - Added caching support
*/


define("IYTPL_API_KEY", "ingeni_ytplaylist_api_key");
define("IYTPL_FEED_CACHE", "ingeni_ytplaylist_feed.json");
define("IYTPL_FEED_CACHE_MINS", 'ingeni_ytplaylist_cache_mins');
define("IYTPL_FEED_CACHE_MINS_DEFAULT", 1440);

define("SAVE_IYTPL_SETTINGS", "Save Settings...");
define("CLEAR_IYTPL_CACHE", "Clear Cache");

include_once('ingeni-youtube-playlist-settings.php');


function ingeni_ytplaylist_get_feed( $googleApiUrl, $debug = 0 ) {
	$use_cache = false;
	$cache_file = null;

	$videoList = '';

	$upload_dir = wp_upload_dir();
	$cached_json = $upload_dir['basedir'] . '/' . IYTPL_FEED_CACHE;

	ingeni_ytplaylist_log('cache file:'.$cached_json, $debug);

	if ( file_exists( $cached_json ) ) {
		$cache_stats = stat($cached_json);
		ingeni_ytplaylist_log('size:'.$cache_stats['size']. '  '.$cache_stats['mtime'] , $debug);

		$current_time = time();
		$cache_timeout_secs = get_option(IYTPL_FEED_CACHE_MINS, IYTPL_FEED_CACHE_MINS_DEFAULT ) * 60;

		if ( $current_time > ($cache_stats['mtime'] + $cache_timeout_secs ) ) {
			ingeni_ytplaylist_log('cache timeout' , $debug);
			unlink( $cached_json ); // Delete the current cache
			$use_cache = false;
		} else {
			$use_cache = true;
		}

		if ( $use_cache ) {
			ingeni_ytplaylist_log('reading cache file!' , $debug);
			$cache_file = fopen($cached_json, 'r');
			$response = fread($cache_file, $cache_stats['size'] );
			fclose($cache_file);
			$cache_file = null;
		}
	}



	if ( !$use_cache ) {
		try {
			$ch = curl_init();
				
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, $googleApiUrl);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_VERBOSE, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_AUTOREFERER, true);
			curl_setopt($ch, CURLOPT_REFERER, get_bloginfo('url'));

			ingeni_ytplaylist_log('send:.'.print_r($ch,true), $debug);		
			$response = curl_exec($ch);

			ingeni_ytplaylist_log('reponse:'.print_r($response,true), $debug);
			curl_close($ch);

		} catch (Exception $ex) {
			ingeni_ytplaylist_log('ingeni_ytplaylist_get_feed: '.$ex->message, 1);
		}
	}

	// Now try and decode the JSON
	if ( $response ) {			
		$videoList = json_decode($response);
		//ingeni_ytplaylist_log('list:'.print_r($videoList,true), $debug);


		// Finally, delete the existing cache and write the new one, but only if we just fetched a fresh feed
		if ( !$use_cache ) {
			if ( file_exists( $cached_json ) ) {
				unlink( $cached_json );
			}

			$cache_file = fopen($cached_json, 'w');
			fwrite($cache_file, $response);
			fclose($cache_file);
		}
	}

	return $videoList;
}

add_shortcode("ingeni-youtube-playlist", "ingeni_youtube_playlist");
function ingeni_youtube_playlist( $atts ) {

	$params = shortcode_atts( array(
		'class' => 'yt_videos',
		'channel_id' => '',
		'playlist_id' => '',
		'max_results' => 6,
		'yt_api_key' => '',
		'framework' => 'bootstrap',
		'debug' => 0,
	), $atts );

	$retHtml = "";

	ingeni_ytplaylist_log(print_r($params,true), $params['debug']);
	$googleApiUrl = '';
	$isPlaylist = false;  // True if pulling a playlist, false if pulling a channel

	// YouTube Data API v3 key
	$apikey = $params['yt_api_key'];
	// If not provided via shortcode, use the key stored in Settings > YouTube Playlist
	if ($apikey == '') {
		$apikey = get_option(IYTPL_API_KEY,'');
	}
	if ($apikey == '') {
		$retHtml = '<p>ERROR: You must provide a Google API key with the YouTube Data API v3 AND YouTube Embedded Player API enabled.</p><p>Please make sure the key is permitted for use on your domain name.</p>';
	} else {

		if ( $params['playlist_id'] ) {
			$isPlaylist = true;
			$googleApiUrl = 'https://youtube.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults='.$params['max_results'].'&playlistId='.$params['playlist_id'].'&key='.$apikey;

		} elseif ( $params['channel_id'] ) {
			$googleApiUrl = 'https://www.googleapis.com/youtube/v3/search?order=date&part=snippet&channelId='.$params['channel_id'].'&key='.$apikey.'&maxResults='.$params['max_results'];
		}
		ingeni_ytplaylist_log('url:.'.$googleApiUrl, $params['debug']);
		$videoList = null;

		if ( $googleApiUrl ) {
			$videoList = ingeni_ytplaylist_get_feed( $googleApiUrl,  $params['debug'] );

			$video_count = 0;
			if ( !empty($videoList) ) {

				if ( isset( $videoList->error ) ) {
					$retHtml = '<p>API ERROR: Code: '.$videoList->error->code.' - '.$videoList->error->message.'</p>';
					foreach($videoList->error->errors as $error) {
						$retHtml .= '<p>   '.$error->message.' Domain: '.$error->domain.' Reason: '.$error->reason.'</p>';
					}
					$retHtml .= '<p>Playlist ID: '.$params['playlist_id'].'</p>';
					$retHtml .= '<p>Channel ID: '.$params['channel_id'].'</p>';
				
				} elseif ( isset( $videoList->items ) ) {
					// Framework row divs
					if ( $params['framework'] == 'foundation' ) {
						$retHtml = '<div class="grid-container '.$params['class'].'" id="ytEmbeds"><div class="grid-x grid-margin-x">';
					} else {
						$retHtml = '<div class="row '.$params['class'].'" id="ytEmbeds">';
					}

					foreach($videoList->items as $item){
						//Embed video
						$video_id = $title = $description = '';
						if ( $isPlaylist ) {
							if (isset($item->id)) {
								$video_id = $item->snippet->resourceId->videoId;
								$title = $item->snippet->title;
								$description = $item->snippet->description;
							}
						} else {
							if (isset($item->id->videoId)) {
								$video_id = $item->id->videoId;
								$title = $item->snippet->title;
								$description = $item->snippet->description;
							}
						}


						// Limit description to 2 sentences
						// Break into sentences
						$pattern = "/[.!?]/";
						$sentences = preg_split( $pattern, $description, 3);
						if ( count($sentences) >= 2 ) {
							$description = $sentences[0] . '. ' . $sentences[1] . '. ';
						}

						if ( $video_id ) {
							$video_count += 1;
							// Framework column divs
							if ( $params['framework'] == 'foundation' ) {
								$retHtml .= '<div class="cell small-12 large-6 bottom-30">';
							} else {
								$retHtml .= '<div class="col-12 col-lg-6 bottom-30">';
							}
							
								$retHtml .=' <div class="ratio ratio-16x9 video-embed">';
									$retHtml .= '<div class="embed-iframe" data-src="'.$video_id.'" id="yt_video_'.$video_count.'"></div>';
								$retHtml .= '</div>';

								$retHtml .= '<h2>'.$title.'</h2>';
								$retHtml .= '<div class="desc">'.$description.'</div>';
								
							// Close framework column divs
							$retHtml .= '</div>';
						}
					}

					// Close framework row divs
					if ( $params['framework'] == 'foundation' ) {
						$retHtml .= '</div></div>';
					} else {
						$retHtml .= '</div>';
					}

				}
			}
		} else {
			$retHtml = '<p>ERROR: You must provide a YouTube channel ID, or playlist ID.</p>';
		}
	}

	return $retHtml;
}


if (!function_exists("ingeni_ytplaylist_log")) {
	function ingeni_ytplaylist_log($msg, $debug = 0) {
		if ( $debug > 0 ) {
			$upload_dir = wp_upload_dir();
			$logFile = $upload_dir['basedir'] . '/' . 'ingeni_ytplaylist_log.txt';
			date_default_timezone_set('Australia/Sydney');

			// Now write out to the file
			$log_handle = fopen($logFile, "a");
			if ($log_handle !== false) {
				fwrite($log_handle, date("H:i:s").": ".$msg."\r\n");
				fclose($log_handle);
			}
		}
	}
}



function ingeni_load_ytplaylist() {
	// YouTube iframe player API - needed to stop multiple videos running at the same time.
	wp_enqueue_script( 'yt_iframe', 'https://www.youtube.com/iframe_api', array(), 0, true );

	wp_register_script( 'yt_iframe_controller', plugins_url('/yt_player_controller.js',__FILE__), null, '0', true );
	wp_enqueue_script( 'yt_iframe_controller' );


	// Init auto-update from GitHub repo
	require 'plugin-update-checker/plugin-update-checker.php';
	$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
		'https://github.com/BruceMcKinnon/ingeni-youtube-playlist',
		__FILE__,
		'ingeni-youtube-playlist'
	);
}
add_action( 'init', 'ingeni_load_ytplaylist' );


// Plugin activation/deactivation hooks
function ingeni_ytplaylist_activation() {
	flush_rewrite_rules( false );
}
register_activation_hook(__FILE__, 'ingeni_ytplaylist_activation');

function ingeni_ytplaylist_deactivation() {
  flush_rewrite_rules( false );
}
register_deactivation_hook( __FILE__, 'ingeni_ytplaylist_deactivation' );

?>