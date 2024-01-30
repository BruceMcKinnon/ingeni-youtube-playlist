<?php

define("MIN_CACHE_MINS", 0);
define("MAX_CACHE_MINS", 10080);

add_action('admin_menu', 'register_ingeni_youtube_playlist_submenu_page');
function register_ingeni_youtube_playlist_submenu_page() {
	add_submenu_page( 'options-general.php', 'Ingeni YouTube Playlist Settings', 'Ingeni YouTube Playlist', 'manage_options', 'ingeni-youtube-playlist-video-page', 'ingeni_youtube_playlist_options_page' );
}

add_action( 'admin_enqueue_scripts', 'ingeni_youtube_playlist_admin_script' );
function ingeni_youtube_playlist_admin_script( $hook ) {
    wp_enqueue_style( 'ingeni_youtube_playlist_admin_css', plugin_dir_url( __FILE__ ) . 'ingeni-youtube-playlist-settings.css' );
}




function ingeni_youtube_playlist_options_page() {

	if ( (isset($_POST['ingeniytpl_edit_hidden'])) && ($_POST['ingeniytpl_edit_hidden'] == 'Y') ){
		try {

		
			switch ($_REQUEST['btn_ingeni_php_submit']) {
				case SAVE_IYTPL_SETTINGS :
					update_option(IYTPL_API_KEY, $_POST[IYTPL_API_KEY] );

					$mins = intval( $_POST[IYTPL_FEED_CACHE_MINS] );
					
					if ( $mins < MIN_CACHE_MINS ) {
						$mins = MIN_CACHE_MINS;
					}
					if ( $mins > MAX_CACHE_MINS ) {
						$mins = MAX_CACHE_MINS;
					}
					update_option(IYTPL_FEED_CACHE_MINS, $mins );
					
					echo('<div class="updated"><p><strong>Settings saved...</strong></p></div>');

				break;
					
				case CLEAR_IYTPL_CACHE :
					$upload_dir = wp_upload_dir();
					$cached_json = $upload_dir['basedir'] . '/' . IYTPL_FEED_CACHE;
					if ( file_exists($cached_json) ) {
						unlink( $cached_json ); // Delete the current cache
					}
					echo('<div class="updated"><p><strong>Cache cleared...</strong></p></div>');
				break;
			}





		} catch (Exception $e) {
			echo('<div class="updated"><p><strong>Error: '.$e->getMessage().'</strong></p></div>');		
		}
	}

	echo('<div class="wrap ingeni_youtube_playlist_settings">');
		echo('<h2>Ingeni YouTube Playlist Options</h2>');

		echo('<form action="'. str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'" method="post" name="ingeni_youtube_playlist_options_page">'); 
			echo('<input type="hidden" name="ingeniytpl_edit_hidden" value="Y">');
			
			echo('<table class="form-table">');
			
			echo('<tr valign="top">');
				echo('<td>Google API Key</td><td><input style="width:100%;" type="text" name="'.IYTPL_API_KEY.'" value="'.get_option(IYTPL_API_KEY,'').'"></td>');
			echo('</tr>');	

			echo('<tr valign="top">'); 
				echo('<td style="width:250px;">Cache Expiry</td><td><input type="number" name="'.IYTPL_FEED_CACHE_MINS.'" min=MAX_CACHE_MINS max=MAX_CACHE_MINS value="'.get_option(IYTPL_FEED_CACHE_MINS, IYTPL_FEED_CACHE_MINS_DEFAULT).'"> ('.MIN_CACHE_MINS.' - '.MAX_CACHE_MINS.' mins)</td>'); 
			echo('</tr>');	

			echo('</tbody></table>');

            echo('<p>Your Google API key must allow access to the YouTube Data API v3 and the YouTube Embedded Player API.</p>');
            echo('<p>This can be obtained from <a href="https://console.cloud.google.com/apis/library" target="_blank">https://console.cloud.google.com/apis/library</a>.</p>');
			
			echo('<p class="submit"><input type="submit" name="btn_ingeni_php_submit" id="btn_ingeni_php_submit" value="'.SAVE_IYTPL_SETTINGS.'" class="button button-primary" value="'.SAVE_IYTPL_SETTINGS.'"><input type="submit" name="btn_ingeni_php_submit" id="btn_ingeni_clear_cache_submit" value="'.CLEAR_IYTPL_CACHE.'" class="button button-primary" value="'.CLEAR_IYTPL_CACHE.'"></p>');

		echo('</tr>');

		echo('</form>');	
	echo('</div>');

}