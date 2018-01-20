//------------------------------------------------------------------------------
// File name: request.js
// Description: Collection of scripts used when displaying a single request
// Author: JSHayford
// Version: 1.0
//------------------------------------------------------------------------------

var thisRequest = new Object();

jQuery( document ).ready(function() {
    jQuery.when(getRequest()).done(function(e) {
        loadRequest();
        loadComments();
    });
});

function getRequest() {
    if (getUrlParameter("request_id") != '') {
        // Ajax
        var endpoint = '/wp-json/starcall/v1/requests/?request_id=' +
        getUrlParameter("request_id");
        return jQuery.ajax( {
          url: endpoint,
          method: 'GET',
          beforeSend: function ( xhr ) {
              xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
          },
          success: function ( response ) {
              thisRequest = response[0];

           },
          failure: function ( response, err ) {
            alert ("It didn't work");
          },
          cache: false,
          dataType: 'json'
        } );
    }
}

function loadRequest () {
    try {
        // If we did, load it
        document.title = thisRequest.title + ' by ' + thisRequest.user_login;
        // Put stuff in the document. Do more with this later

        var markup = '<h1>'+ thisRequest.title + '</h1>';

        markup += '<strong>Requested by: ' + thisRequest.user_login + '</strong><br /><br />';

        if  (thisRequest.user_authorized) {
            markup += '<button class="editbutton"> Edit request </button><br /><br />';
        }

        markup += "<strong>Description</strong><br />" + thisRequest.description +
                  "<br /><br />";

        if (thisRequest.reference_links) {
            markup += "<strong>References:</strong><br />" + thisRequest.reference_links + "<br /> <br />";
              }



        jQuery("#requestarea").append(markup);
        jQuery('.editbutton').click(function() {
            editRequest();
        });

    } catch(err) {
        // Otherwise display an error message and bounce
        console.log(err);
        requestNotFound();
        return;
    }
}

function requestNotFound() {
    // Oops
    document.title = "Request not found";
    jQuery("#requestarea").append("Request not found.");
}

function editRequest() {
    //Edit mode, allow editing and stuff
    updateRequest = Object.assign({}, thisRequest);
    jQuery('#requestarea').empty();

    markup = '<h1>'+ updateRequest.title + '</h1><br>' +
             '<strong>Requested by: ' + updateRequest.user_login + '</strong><br><br>' +
             '<strong>Description</strong><br>' +
             '<textarea class="editdescription"></textarea><br><br>' +
             '<button class="saveButton">Save</button><button class="cancelButton">Cancel</button>';

    jQuery('#requestarea').append(markup);
    jQuery('.editdescription').html(updateRequest.description);

    // Add handlers to save/cancel buttons
    jQuery('.saveButton').click(function(){
        saveRequest(updateRequest);
    });

    jQuery('.cancelButton').click(function(){
        cancelChanges();
    });
}

function saveRequest(updateRequest) {

    updateRequest.description = jQuery('.editdescription').val();

    if(JSON.stringify(updateRequest) == JSON.stringify(thisRequest)) {
        //User made no changes
        alert("There are no changes to save!")

    } else {
        // Do the update
        var endpoint = '/wp-json/starcall/v1/requests/?request_id=' +
        getUrlParameter("request_id");
        return jQuery.ajax( {
          url: endpoint,
          method: 'POST',
          data: JSON.stringify(updateRequest),
          beforeSend: function ( xhr ) {
              xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
          },
          complete: function ( response ) {
              if (response.responseJSON.success==true) {
                  // Update succeeded
                  alert("Update successful!")
                  jQuery("#requestarea").empty();
                  // Load the request again so user sees the Changes
                  getRequest();
                  loadRequest();
              } else {
                  // Update failed
                  alert(response.responseJSON.errmsg);
              }
           },
          failure: function ( response, err ) {
              alert ("It didn't work");
          },
          cache: false,
          dataType: 'json'
        } );
    }
}

function cancelChanges () {
    alert("Changes cancelled.");
    jQuery('#requestarea').empty();
    loadRequest();
}

function loadComments() {

    var markup;

    jQuery('#commentarea').empty();

    // Load the comments for this request

    console.log(thisRequest);
    jQuery.when(getCommentsByRequestId(thisRequest.request_id)).done(function(response) {
        console.log(response);
        comments = response;
        console.log(comments);


        markup = "<h3> Comments </h3><br />"

        if (comments.length == 0)  {
            markup += "Be the first to comment on this request!";
        } else {
            console.log("Doing comment loop")
            for (var i = 0; i < comments.length; i++) {
                markup += "<div class='request_comment'>";
                markup += "<strong>" + comments[i].author + "</strong><br />";
                markup += comments[i].comment_text + "<br />";
                markup += "</div>";
            }
        }

        // Add the reply area TODO make sure user is logged in
        markup += "<br><textarea id='newcomment'></textarea><br />"
        markup += "<button id='submitcomment'>Submit comment</button>";
        jQuery("#commentarea").append(markup);

        jQuery('#submitcomment').click(function() {

            submitComment(jQuery('#newcomment').val());

        });
    });
}

function submitComment (text) {
    newComment = new Comment();

    newComment.comment_text = text;
    newComment.request_id = thisRequest.request_id;

    jQuery.when(postCommentAjax(newComment)).done(function(e){
            loadComments();
    });

}

function getUrlParameter(name) {
    //Strip the ID parameter out of the URL query string. Stole this
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
};
