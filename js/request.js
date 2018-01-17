jQuery( document ).ready(function() {
   jQuery.when(getRequest()).done(function(e) {
       try {
           document.title = requestJSON[0].title + ' by ' + requestJSON[0].user_login;
           loadRequest();
       } catch(err) {
           document.title = "Request not found";
           jQuery("#requestarea").append("Request not found.");
       }
   });
});

function getRequest() {
    if (getUrlParameter("request_id") != '') {

        var endpoint = '/wp-json/starcall/v1/requests/?request_id=' +
        getUrlParameter("request_id");
        // Ajax call
        return jQuery.ajax( {
          url: endpoint,
          success: function ( response ) {
                requestJSON = response;
           },
          failure: function ( response, err ) {
            alert ("It didn't work");
          },
          cache: false,
          dataType: 'json'
        } );
    } else {
        // We didn't get a valid ID, so do nothing
    }
}

function loadRequest () {
    console.log("I am in loadRequest");
    var markup = '<h1>'+ requestJSON[0].title + '</h1><br>' +
                 '<h3>Requested by: ' + requestJSON[0].user_login + '</h3><br>' +
                 requestJSON[0].description;

    jQuery("#requestarea").append(markup);
}

function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
};
