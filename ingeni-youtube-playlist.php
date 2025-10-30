<?php
/*
Plugin Name: Ingeni YouTube Playlist
Version: 2025.03
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

v2025.01 - Added custom templates
v2025.02 - Implemented mainstage mode
v2025.03 - Fixed path to custom templates.
		 - Now supports a thumbnails folder - saves the default thumbnails, but also allows you to replace them.
		 - Implement the video_ids parameter.
*/


define("IYTPL_API_KEY", "ingeni_ytplaylist_api_key");
define("IYTPL_FEED_CACHE", "iytpl_");
define("IYTPL_FEED_CACHE_MINS", 'ingeni_ytplaylist_cache_mins');
define("IYTPL_FEED_CACHE_MINS_DEFAULT", 1440);

define("SAVE_IYTPL_SETTINGS", "Save Settings...");
define("CLEAR_IYTPL_CACHE", "Clear Cache");

include_once('ingeni-youtube-playlist-settings.php');


function ingeni_ytplaylist_get_feed( $googleApiUrl, $debug = 0, $cache_id = '' ) {
	$use_cache = false;
	$cache_file = null;

	$videoList = '';

	$upload_dir = wp_upload_dir();
	$cached_json = $upload_dir['basedir'] . '/' . IYTPL_FEED_CACHE . $cache_id . '.json';

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
		'class' => 'iytpl_videos',
		'channel_id' => '',
		'playlist_id' => '',
		'max_results' => 6,
		'yt_api_key' => '',
		'framework' => 'grid',
		'debug' => 0,
		'show_title' => 1,
		'show_image' => 1,
		'show_description' => 1,
		'mode' => 0,  // Mode. 0 = grid of players, 1 - lightbox mode, 2 - mainstage mode
		'template_file' => '',
		'video_ids' => '', // List of YT video IDs comma-seperated
	), $atts );

	$retHtml = "";

	ingeni_ytplaylist_log(print_r($params,true), $params['debug']);
	$googleApiUrl = '';
	$isPlaylist = false;  // True if pulling a playlist, false if pulling a channel
	$isVideoList = false;

	// Get the plugin version number
	$plugin_data = get_plugin_data( __FILE__ );
	$plugin_version = $plugin_data['Version'];

	// If using standard grid layout mode, enqueue specific JS and CSS
	if ($params['mode'] == 0) {
		wp_register_script( 'yt_iframe_controller', plugins_url('/yt_player_controller.js',__FILE__), null, '0', true );
		wp_enqueue_script( 'yt_iframe_controller' );
		wp_enqueue_style( 'yt_std_template_style', plugins_url('/ingeni-youtube-playlist.css',__FILE__), $plugin_version );
	}
	// If using Mainstage mode, enqueue specific JS and CSS
	if ($params['mode'] == 2) {
		wp_register_script( 'yt_mainstage_controller', plugins_url('/iytpl-mainstage-controller.js',__FILE__), null, '0', true );
		wp_enqueue_script( 'yt_mainstage_controller' );
		wp_enqueue_style( 'yt_mainstage_style', plugins_url('/iytpl-mainstage-controller.css',__FILE__), $plugin_version );
	}


	// YouTube Data API v3 key
	$apikey = $params['yt_api_key'];
	// If not provided via shortcode, use the key stored in Settings > YouTube Playlist
	if ($apikey == '') {
		$apikey = get_option(IYTPL_API_KEY,'');
	}
	if ($apikey == '') {
		$retHtml = '<p>ERROR: You must provide a Google API key with the YouTube Data API v3 AND YouTube Embedded Player API enabled.</p><p>Please make sure the key is permitted for use on your domain name.</p>';
	} else {

		$cache_id = '';
		if ( $params['video_ids'] ) {
			//$videoList = explode(',', $params['video_ids'] );
			$isVideoList = true;
			$isPlaylist = false;

			$googleApiUrl = 'https://www.googleapis.com/youtube/v3/videos?part=snippet&key='.$apikey.'&id='.$params['video_ids'];
			$cache_id = str_replace(',','_',$params['video_ids']);

			//$googleApiUrl = 'https://www.googleapis.com/youtube/v3/videos?part=snippet&key='.$apikey.'&id=AHJj25PBIhg';

		} else {
			$isVideoList = false;
			if ( $params['playlist_id'] ) {
				$isPlaylist = true;
				$googleApiUrl = 'https://youtube.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults='.$params['max_results'].'&playlistId='.$params['playlist_id'].'&key='.$apikey;
				$cache_id = $params['playlist_id'];

			} elseif ( $params['channel_id'] ) {
				$googleApiUrl = 'https://www.googleapis.com/youtube/v3/search?order=date&part=snippet&channelId='.$params['channel_id'].'&key='.$apikey.'&maxResults='.$params['max_results'];
				$cache_id = $params['channel_id'];
			}
		}

		ingeni_ytplaylist_log('url:.'.$googleApiUrl, $params['debug']);
		$videoList = null;

		if ( $googleApiUrl ) {
			$videoList = ingeni_ytplaylist_get_feed( $googleApiUrl,  $params['debug'], $cache_id );
		}

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

				$attributes = array();
				$attributes['debug'] = $params['debug'];
				$attributes['framework'] = $params['framework'];
				$attributes['wrapperClass'] = $params['class'];
				$attributes['showTitle'] = $params['show_title'];
				$attributes['showImage'] = $params['show_image'];
				$attributes['showDescription'] = $params['show_description'];
				$attributes['mode'] = $params['mode'];
				$attributes['debugMode'] = $params['debug'];

				$template_file = $params['template_file'];

				//
				// Use a custom template?
				//
				if ( $template_file != '' ) {

					if ( file_exists( plugin_dir_path( __FILE__ ) . 'templates/'.$template_file ) ) {
						$template_file = plugin_dir_path( __FILE__ ) . 'templates/'.$template_file;
					}

					if ( file_exists( get_template_directory() .'/ingeni-youtube-playlist/'.$template_file ) ) {
						$template_file = get_template_directory() .'/ingeni-youtube-playlist/'.$template_file;
					}

					if ( file_exists( get_stylesheet_directory() .'/ingeni-youtube-playlist/'.$template_file ) ) {
						$template_file = get_stylesheet_directory() .'/ingeni-youtube-playlist/'.$template_file;
					}
				}
				ingeni_ytplaylist_log('custom template_file:'.$template_file);
		
				$has_template = false;
				if ( file_exists( $template_file ) ) {
					// Custom template exists
					$has_template = true;
				} else {
					// Use the standard template
					$template_file = plugin_dir_path( __FILE__ ) . 'templates/iytpl_template_std.php';
					if ( file_exists( $template_file ) ) {
						$has_template = true;
					}
				}

				$templateRenderer = null;
				if ( !$has_template ) {
					$retHtml = '<p>Sorry, there is no template available to display the latest posts!</p>';

				} else {
					// Include the template file
					include_once($template_file);
					//fb_log('using template: '.$template_file);

					// Instantiate the renderer class
					if ( class_exists("iytpl_template") ) {
						$templateRenderer = new ilp_template( $attributes );

					} else {
						$name_space = basename($template_file, '.php');
						$class_name = $name_space."\iytpl_template";
						if ( class_exists($class_name) ) {
							$templateRenderer = new $class_name( $attributes );
						} else {
							$retHtml = '<p>Sorry, the latest posts template '.$template_file. ' does not support the mandatory functions.</p>';
						}
					}
				}

				if ( $templateRenderer ) {

					// Open the wrapper divs
					$retHtml .= $templateRenderer->iytpl_get_block_wrapper_open();


					// Mainstage mode
					if ( $params['mode'] == 2 ) {
						$retHtml .= '<div id="iytplMainstageWrapper"><div id="iytplMainstagePoster"></div><div id="iytplMainstage" class="iytplMainstage" style="margin-bottom: 20px;"></div></div>
						<div class="iytpl-thumbnails">';
					}

					foreach($videoList->items as $item){
						//Embed video
						$video_id = $title = $description = $thumbnail_url = $fallback_thumb = '';
						if ( $isPlaylist ) {
							if (isset($item->id)) {
								$video_id = $item->snippet->resourceId->videoId;
								$title = $item->snippet->title;
								$description = $item->snippet->description;
							}
						} elseif ( $isVideoList ) {
							if (isset($item->id)) {
								$video_id = $item->id;
								$title = $item->snippet->title;
								$description = $item->snippet->description;
								$fallback_thumb = $item->snippet->thumbnails->standard->url;
							}
						} else {
							if (isset($item->id->videoId)) {
								$video_id = $item->id->videoId;
								$title = $item->snippet->title;
								$description = $item->snippet->description;
							}
						}

						if ( $params['mode'] > 0 ) {
							$thumbnail_url = iytpl_get_thumbnail($video_id, $fallback_thumb);
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

							$retHtml .= $templateRenderer->iytpl_render_one_post( $video_id, $video_count, $title, $description, $thumbnail_url );
						}
					}

					if ( $params['mode'] == 1 ) {
						$retHtml .= '</div>';  // close .iytpl-thumbnails
					}

					// Close the wrapper divs
					$retHtml .= $templateRenderer->iytpl_get_block_wrapper_close();



					$templateRenderer = null;
				}

			} else {
				$retHtml = '<p>ERROR: You must provide a YouTube channel ID, or playlist ID.</p>';
			}
		}
	}

	return $retHtml;
}


// Get the thumbnail for the video - stored as the videoid.ext
// Support webp and jpg files only
function iytpl_get_thumbnail($video_id, $fallbackThumb = '') {
	$thumbFile = '';

	if ( $video_id ) {
		$matching_files = null;

		// Check for a thumbnails folder in the current theme folder
		if ( file_exists( get_template_directory() .'/ingeni-youtube-playlist/thumbnails' ) ) {
			$matching_files = glob(  get_template_directory() .'/ingeni-youtube-playlist/thumbnails/'.$video_id.'.*' );

			if ( is_array($matching_files) && ( count($matching_files) > 0 ) ) {
				if ( in_array(  get_template_directory() .'/ingeni-youtube-playlist/thumbnails/' . $video_id.'.webp', $matching_files ) ) {
					$thumbFile = get_stylesheet_directory_uri() . '/ingeni-youtube-playlist/thumbnails/' . $video_id.'.webp';
				} elseif ( in_array(  get_template_directory() .'/ingeni-youtube-playlist/thumbnails/' . $video_id.'.jpg', $matching_files ) ) {
					$thumbFile = get_stylesheet_directory_uri() . '/ingeni-youtube-playlist/thumbnails/' . $video_id.'.jpg';
				}
			}
		}

		if ( ! $thumbFile ) {
			// Create the default thumbnails folder
			if ( ! file_exists( plugin_dir_path( __FILE__ ) . 'thumbnails' ) ) {
				mkdir( plugin_dir_path( __FILE__ ) . 'thumbnails', 0755 );
			}

			// Check for a thumbnails folder in the plugin folder
			$matching_files = glob( plugin_dir_path( __FILE__ ) . 'thumbnails/'.$video_id.'.*');

			if ( is_array($matching_files) && ( count($matching_files) > 0 ) ) {
				if ( in_array(  get_template_directory() .'/ingeni-youtube-playlist/thumbnails/' . $video_id.'.webp', $matching_files ) ) {
					$thumbFile = plugin_dir_url( __FILE__ ) . 'thumbnails/' . $video_id . '.webp';
				} elseif ( in_array(  get_template_directory() .'/ingeni-youtube-playlist/thumbnails/' . $video_id.'.jpg', $matching_files ) ) {
					$thumbFile = plugin_dir_url( __FILE__ ) . 'thumbnails/' . $video_id . '.jpg';
				}
			}
		}

		if ( ! $thumbFile ) {
			if ( !$fallbackThumb) {
				$thumbUrl = 'https://img.youtube.com/vi/'.$video_id.'/maxresdefault.jpg';
			} else {
				$thumbUrl = $fallbackThumb;
			}

			$imageContent = file_get_contents( $thumbUrl );

			if ($imageContent !== false) {
				// Save the image content to the local file
				$localFilePath = plugin_dir_path( __FILE__ ) . 'thumbnails/'.$video_id.'.jpg';
				if ( file_put_contents( $localFilePath, $imageContent ) !== false) {
					$thumbFile = plugin_dir_url( __FILE__ ) . 'thumbnails/' . $video_id.'.jpg';
			
				} else {
					ingeni_ytplaylist_log( "Error saving image to: " . $localFilePath, true );
				}
			} else {
				ingeni_ytplaylist_log ( "Error retrieving image from URL: " . $imageUrl, true );
			}

		}

	}

	return $thumbFile;
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
	wp_enqueue_script( 'yt_iframe', 'https://www.youtube.com/iframe_api', array(), null, true );

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