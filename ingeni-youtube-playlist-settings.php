<?php

/*
define("IYTPL_API_KEY", "ingeni_ytplaylist_api_key");
define("SAVE_IYTPL_SETTINGS", "Save Settings...");
*/

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
			update_option(IYTPL_API_KEY, $_POST[IYTPL_API_KEY] );

			echo('<div class="updated"><p><strong>Settings saved...</strong></p></div>');

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

			echo('</tbody></table>');

            echo('<p>Your Google API key must allow access to the YouTube Data API v3 and the YouTube Embedded Player API.</p>');
            echo('<p>This can be obtained from <a href="https://console.cloud.google.com/apis/library" target="_blank">https://console.cloud.google.com/apis/library</a>.</p>');
			
			
			echo('<p class="submit"><input type="submit" name="btn_ingeni_php_submit" id="btn_ingeni_php_submit" class="button button-primary" value="'.SAVE_IYTPL_SETTINGS.'"></p>');
		echo('</form>');	
	echo('</div>');

}