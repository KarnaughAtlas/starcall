
jQuery( document ).ready(function() {
   get_requests();
});

jQuery('#requestdesc').on('keypress', function (e) {
   var code = e.keyCode || e.which;
   if (code==13) {
    get_requests(jQuery(this).val());
   }
} );

jQuery( '#testrestbutton' ).on( 'click', function ( e )  {
 e.preventDefault();
 get_requests(jQuery('#requestdesc').val());
} );


function get_requests (filter) {

   if (filter) {
       var endpoint = '/wp-json/starcall/v1/requests/' + '?desc=' + filter;
   } else {
       var endpoint = '/wp-json/starcall/v1/requests/'
   }

   console.log("Gonna call me some ajax stuff");

   jQuery("#testingstuff").text("Loading...");



   jQuery.ajax( {
     url: endpoint,
     success: function ( response ) {
           console.log("Got into the success function");
           jQuery('#testingstuff').text(response);
      },
     failure: function ( response, err ) {
       console.log("Got into the failure function");
       alert ("It didn't work?");
       document.write (err);
       document.write(response);
     },
     cache: false,
     dataType: 'text'
   } );
}
