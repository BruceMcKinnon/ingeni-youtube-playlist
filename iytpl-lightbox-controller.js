/* IYTPL Lightbox controller */

jQuery(document).ready(function() {
    //console.log('lightbox ready');
    // Play a video if one of the thumbnails is clicked
    jQuery('.iytpl-thumbnail img').on('click', function () {
        var videoId = jQuery(this).data('id');
        iytpl_DisplayLightbox(videoId);
    });

});


function iytpl_DisplayLightbox( videoId ) {
    //console.log('show lightbox id='+videoId);
    if (videoId) {
        var lightboxHtml = '<div id="iytpl_lightboxWrapper"><div class="iytpl_lightbox_overlay">';
        lightboxHtml += '<div id="iytplLightboxClose">&times;</div><iframe src="https://www.youtube.com/embed/' + videoId + '?autoplay=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
        lightboxHtml += '</div></div>';

        jQuery('body').append( lightboxHtml );

        // Remove the div when the X is clicked
        jQuery('#iytplLightboxClose').on('click', function () {
            jQuery('#iytpl_lightboxWrapper').remove();
        });
    }

}