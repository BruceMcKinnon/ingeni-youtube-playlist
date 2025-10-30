// JS to load and control playing of videos
//

// Global iframe setup
var ytPlayerReady = false;


// Store the player that is currently playing.
var currentPlayingPlayer = null;

jQuery(document).ready(function() {
    // If the iframe player is not yet initialised, force the issue...
    if (!ytPlayerReady) {
        onYouTubeIframeAPIReady();
    }
});

// The API is ready to go, so load up the players.
function onYouTubeIframeAPIReady() {
    console.log('onYouTubeIframeAPIReady');
    loadYtPlayers("ytEmbeds", "embed-iframe");
    ytPlayerReady = true;
}


// Find all of the target divs and add players to them
function loadYtPlayers(targetId, embedClass) {
    const targetDiv = document.getElementById(targetId);
    var originUrl = document.location.origin;

    if ( targetDiv ) {
//console.log('targetDiv: '+targetDiv.id+'  embedclass='+embedClass);
        const playerDivs = targetDiv.getElementsByClassName(embedClass);
        if ( playerDivs ) {
            //console.log('playerDivs: '+playerDivs.length);
            for (let i = 0; i < playerDivs.length; i++) {

                var playerDivId = playerDivs[i].id;
                const thisVideoDiv = document.getElementById(playerDivId);
                let thisVideoId = thisVideoDiv.getAttribute("data-src");

                var player = null;
                player = new YT.Player(playerDivId, {
                    height: '100%',
                    width: '100%',
                    videoId: thisVideoId,
                    playerVars: {
                        'playsinline': 1
                    },
                    events: {
                        'onReady': onPlayerReady,
                        'onStateChange': onPlayerStateChange
                    }
                });
            }
        }
    }
}


// The player is ready!
function onPlayerReady(event) {
    //console.log('onPlayerReady');
}


// Playing state changed. If there is already a video playing, we MUST pause it. You cannot have two
// videos playing at the same time.
function onPlayerStateChange(event) {
    if (event.data == YT.PlayerState.PLAYING) {
        if ( currentPlayingPlayer ) {
            currentPlayingPlayer.pauseVideo();
        }
        currentPlayingPlayer = event.target;
    }
    //console.log('currentPlayingPlayer: '+JSON.stringify(currentPlayingPlayer));
}
