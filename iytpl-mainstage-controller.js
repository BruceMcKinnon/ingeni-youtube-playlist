
var player = null; // The YT iframe

jQuery(document).ready(function() {
    //console.log('iytpl doc ready');
    iytplSetup();
    
    // If the iframe player is not yet initialised, force the issue...
    if (!player) {
        onYouTubeIframeAPIReady();
    }
});


function iytplSetup() {
    // Play a video if one of the thumbnails is clicked
    jQuery('.iytpl-thumbnail img').on('click', function () {
        var videoId = jQuery(this).data('id');
        playVideo(videoId);
    });

    // Play a video if the poster is clicked
    jQuery('#iytplMainstagePoster').on('click', function () {
        var videoId = jQuery(this).data('id');
        playVideo(videoId);
    });
    
    //console.log('iytpl setup..');
}

function onYouTubeIframeAPIReady() {
    //console.log('iytpl onYouTubeIframeAPIReady');
    
    // Make sure the player is setup prior to initing the YT player
    if ( !jQuery('#iytplMainstagePoster').data('id') ) {
        iytplSetup();
    }
    
    player = new YT.Player('iytplMainstage', {
        height: '390',
        width: '640',
        videoId: '',
        playerVars: { 'autoplay': 1, 'playsinline': 1 },
        events: {
            'onStateChange': onPlayerStateChange
        }
    });

    // Pre-load the first video into the mainstage poster
    var firstVideoId = jQuery(".iytpl-thumbnails > :first-child > img").data('id');
    //console.log('iytpl firstVideoId:'+firstVideoId);

    var posterImg = jQuery(".iytpl-thumbnails > :first-child > img").attr('src');
    //console.log('iytpl posterimg:'+posterImg);

    jQuery("#iytplMainstagePoster").css("background-image",'url("'+posterImg+'")');
    jQuery("#iytplMainstagePoster").attr('data-id', firstVideoId);
}



function playVideo( videoId ) {
    //console.log('iytpl playVideo start...'+videoId);

    if (player) {
        jQuery("#iytplMainstagePoster").hide();
        player.loadVideoById(videoId);
    }
}


function onPlayerStateChange(event) {
    var done = false;
    //console.log('iytpl state change: '+event.data );

    /*
    -1 (unstarted)
    0 (ended)
    1 (playing)
    2 (paused)
    3 (buffering)
    5 (video cued).
    */

    if (event.data == YT.PlayerState.PLAYING) {
        //jQuery('#iytplMainstage-close').show();
        //jQuery('#yiytplMainstageWrapper').show();
    }
}


function pauseVideo() {
    if (player) {
        player.pauseVideo();

        //jQuery('#iytpMainstage-close').hide();
        //jQuery('#iytplMainstageWrapper').hide();
    }
    
    //console.log('iytpl pause video');
}
