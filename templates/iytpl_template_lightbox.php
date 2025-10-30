<?php
/*

Lightbox template for ingeni-youtube-playlist

Templates need to implement a iytpl_template() class that support the following public methods:

iytpl_get_block_wrapper_open()

    iytpl_render_one_post()

iytpl_get_block_wrapper_close()

*/

namespace iytpl_template_lightbox;

class iytpl_template {
	public $html;
	public $attributes;
    public $wrapperClass, $show_image, $show_description, $show_title, $mode, $debugMode, $framework ;

	function __construct( $attributes ) {

		$this->html = '';

        // Grab attributes that relate to the template
        $this->attributes = $attributes;

        $this->wrapperClass = "ingeni_yt_playlist";
        if ( $attributes['wrapperClass'] != '' ) {
            $this->wrapperClass  = $attributes['wrapperClass'];
        }

		$this->framework = $attributes['framework'];

        $this->show_image = (int) $attributes['showImage'];
		$this->show_description = (int) $attributes['showDescription'];
		$this->show_title = (int) $attributes['showTitle'];
		$this->mode = (int) $attributes['mode'];
		$this->debugMode = (int) $attributes['debugMode'];

    }

    function iytpl_render_one_post( $video_id, $video_count, $title, $description, $thumbnail_url ) {

        $this->html = '';

		// Framework column divs
		if ( $this->framework == 'foundation' ) {
			$this->html = '<div class="cell small-12 large-6 bottom-30">';
		} elseif ( $this->framework == 'bootstrap' ) {
			$this->html = '<div class="col-12 col-md-6 col-lg-4 bottom-30">';
		} else {
			$this->html = '<div class="iytpl-thumbnail">';
		}

		// Poster image
		if ( $this->show_image && $thumbnail_url ) {
			$hero_img = '<img src="'.$thumbnail_url.'" loading="lazy" title="'.$title.'" alt="'.$title.'" data-id="'.$video_id.'" />';
			$this->html .= $hero_img;
		}
		// Video title
		if ($this->show_title) {
			$this->html .= '<h3 class="item_title">' . $title . '</h3>';
		}
		// Video description
		if ($this->show_description) {
			if ( function_exists("short_excerpt") ) {
				$excerpt = short_excerpt( $description, 80 );
			} else {
				$excerpt = $description;
			}
			$this->html .= '<p class="item_excerpt">' . wp_strip_all_tags( $excerpt ) . '</p>';
		}

		// Close framework column divs
		$this->html .= '</div>';

		return $this->html;
	}


    function iytpl_get_block_wrapper_open() {
		$retHtml = '';

		// Framework row divs
		if ( $this->framework == 'foundation' ) {
			$retHtml = '<div class="grid-container '.$this->wrapperClass.'" id="ytEmbeds"><div class="grid-x grid-margin-x">';
		} else {
			$retHtml = '<div class="row '.$this->wrapperClass.'" id="ytEmbeds">';
		}

		return ( $retHtml );
    }

	
    function iytpl_get_block_wrapper_close() {
		$retHtml = '';
		// Close framework row divs
		if ( $this->framework == 'foundation' ) {
			$retHtml = '</div></div>';
		} else {
			$retHtml = '</div>';
		}
        return ( $retHtml );
    }


    //
    // Add additional supporting function here....
    //

	private function get_first_sentence($content, $min_character_count = 0, $max_character_count = 150, $num_sentances = 1) {
		$retVal = $content;

		// Remove H4s
		$clean = preg_replace('#<h4>(.*?)</h4>#', '', $content);
		$clean = wp_strip_all_tags($clean);
		// Replace all curly quotes.
		$clean = str_replace(array('“','”'), '"', $clean);

		$locs = get_sentance_endings($clean, $min_character_count);
		$loc = $locs[0];

	
		$retVal = substr($clean,0, ($loc+1) );

		if ($num_sentances == 2) {
			$clean = substr( $clean, ($loc+1), (strlen($clean)-($loc+1)) );

			$locs = get_sentance_endings($clean, $min_character_count);
			$loc = $locs[0];
			$retVal .= substr($clean,0, ($loc+1) );
		}

		if (strlen($retVal) > $max_character_count) {
			$retVal = substr($retVal,0,$max_character_count+10);
			$last_word = strripos($retVal,' ');
			if ($last_word !== false) {
				$retVal = substr($retVal,0,$last_word) . '...';
			}
		}

		return $retVal;
	}

	private function get_sentance_endings( $clean, $min_character_count ) {
		$exclaim = strpos($clean, "!",$min_character_count);
		if ($exclaim === false) {
			$exclaim = strlen($clean)-1;
		}
		$question = strpos($clean, "?",$min_character_count);
		if ($question === false) {
			$question = strlen($clean)-1;
		}
		$endquote = strpos($clean, '".',$min_character_count);
		if ($endquote === false) {
			$endquote = strlen($clean)-1;
		}
		$period = strpos($clean, '.',$min_character_count);
		if ($period === false) {
			$period = strlen($clean)-1;
		}

		$locs = array($exclaim,$question,$endquote,$period);
		sort( $locs );

		return $locs;
	}

	private function short_excerpt( $content, $limit_chars = 100 ) {
		if ( function_exists("get_first_sentence") ) {
			$excerpt = get_first_sentence($content);
		} else {
			$excerpt = trim(substr($content,$limit_chars));

			if ( substr($excerpt,strlen($excerpt)-1,1) != '.' ) {
				$excerpt += '...';
			}
		}
		return $excerpt;
	}

}
