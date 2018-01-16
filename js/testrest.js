
jQuery( document ).ready(function() {
   getRequests();
});

jQuery('#requestdesc').on('keypress', function (e) {
   var code = e.keyCode || e.which;
   if (code==13) {
    getRequests(jQuery(this).val());
   }
} );

jQuery( '#testrestbutton' ).on( 'click', function ( e )  {
 e.preventDefault();
 getRequests(jQuery('#requestdesc').val());
} );


function getRequests (filter) {

   if (filter) {
       var endpoint = '/wp-json/starcall/v1/requests/' + '?desc=' + filter;
   } else {
       var endpoint = '/wp-json/starcall/v1/requests/'
   }

   console.log("Gonna call me some ajax stuff");

   jQuery.ajax( {
     url: endpoint,
     success: function ( response ) {
           console.log("Got into the success function");
           makeTable(response);
      },
     failure: function ( response, err ) {
       console.log("Got into the failure function");
       alert ("It didn't work?");
       document.write (err);
       document.write(response);
     },
     cache: false,
     dataType: 'json'
   } );
}

function makeTable (jsonRequests) {

    jQuery('#requesttable tbody').empty();

    for (var i = 0; i < jsonRequests.length; i++) {
        markup = "<tr><td>" + jsonRequests[i].user_id + "</td>" +
                 "<td>" + jsonRequests[i].title + "</td>" +
                 "<td>" + jsonRequests[i].description.slice(0,30) + "</td></tr>";
        jQuery('#requesttable tbody').append(markup);
    }


}
