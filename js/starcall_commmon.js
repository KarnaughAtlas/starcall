// Common javascript used throughout starcall.sylessae.com

// Close windows if the user clicks outside of them
window.onclick = function(event) {
    if (jQuery(event.target).hasClass('modalWindow')) {
        jQuery('.modalWindow').hide();
    }
}

// Modal window close button
jQuery('.windowClose').click(function() {
    jQuery('.modalWindow').hide();
});
