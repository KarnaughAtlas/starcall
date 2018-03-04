// Add function for clicking on rows
jQuery('.requestrow').on('click', function(e) {
    // Eventually this will link us to the request page - test for now

    var url = 'https://starcall.sylessae.com/request/?request_id=' +
                                  jQuery('td.request_id', this).text();

    window.open(url);
});
