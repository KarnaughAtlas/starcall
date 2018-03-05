// Add function for clicking on rows
jQuery('.requestrow').on('click', function(e) {


    var url = 'https://starcall.sylessae.com/request/?request_id=' +
                                  jQuery('td.request_id', this).text();
    window.open(url);
});

jQuery('#areYouLost').click(function(e){
    jQuery('#getStartedBox').toggle();
});
