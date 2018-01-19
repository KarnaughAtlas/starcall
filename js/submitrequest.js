//------------------------------------------------------------------------------
// File name: submitrequest.js
// Description: Collection of scripts used when submitting a request
// Author: JSHayford
// Version: 1.0
//------------------------------------------------------------------------------

var newRequestID;

//------------------------------------------------------------------------------
// On "submit request"
//------------------------------------------------------------------------------
jQuery('#requestform').on('submit',function(e){
    e.preventDefault();
    submitRequest();
});

//------------------------------------------------------------------------------
// submitRequest
//   Does the Ajax request, advises user of success, and redirects to new
//   request page if successful
//------------------------------------------------------------------------------
function submitRequest() {

    // Take form data and load it into the new request object

    var newRequest = new Object();
    formElements = document.getElementById('requestform').elements;

    newRequest.title = formElements['submitTitle'].value;

    if(formElements['submitOrigFan'].value == 'original') {
        // Set the object fan_art flag to 0
        newRequest.fan_art = false;
    } else {
        newRequest.fan_art = true;
    }

    if (formElements['submitNSFW'].value == 'yes') {
        newRequest.nsfw = true;
    } else {
        newRequest.nsfw = false;
    }

    newRequest.social_media = formElements['submitSocial'].value;
    newRequest.reference_links = formElements['submitLinks'].value;
    newRequest.description = formElements['submitDesc'].value;
    newRequest.how_hear = formElements['submitHowHear'].value;

    submitRequestAjax(newRequest);
}

function submitRequestAjax(newRequest) {
    var endpoint = '/wp-json/starcall/v1/requests/';
    return jQuery.ajax( {
      url: endpoint,
      method: 'POST',
      data: JSON.stringify(newRequest),
      beforeSend: function ( xhr ) {
          xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
      },
      complete: function ( response ) {
          if (response.responseJSON.success==true) {
              // POST succeeded
              alert("Submission successful!")
              newRequestId = response.responseJSON.new_id;
              window.location = "https://starcall.sylessae.com/request/?request_id=" + newRequestId;
          } else {
              // Update failed
              alert(response.responseJSON.errmsg);
          }
       },
      failure: function ( response, err ) {
          alert ("Error submitting request");
      },
      cache: false,
      dataType: 'json'
    } );
}
