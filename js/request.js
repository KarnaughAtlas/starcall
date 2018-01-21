    //------------------------------------------------------------------------------
// File name: request.js
// Description: Collection of scripts used when displaying a single request
// Author: JSHayford
// Version: 1.0
//------------------------------------------------------------------------------

var thisRequest = new Object();
var comments_per_page = 5;
var comments = new Object();

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

    jQuery('#commentarea').empty();

    // Load the comments for this request

    jQuery.when(getCommentsByRequestId(thisRequest.request_id)).done(function(response) {
        comments = response;
        // Display the first page of comments
        makeCommentPage(1);
    });
}

function makeCommentPage(page) {

        var markup;

        if (comments.length == 0)  {
            markup = "Be the first to comment on this request!";
        } else { // We've got comments, display them

            // Figure out how many pages we'll need. TODO let the user choose how
            // many comments to display per page.

            comment_pages = Math.ceil(comments.length / comments_per_page);

            for (var i = (page - 1) * comments_per_page;
                 i < (page * comments_per_page) &&
                 i < comments.length; i++) {

                markup += "<div id='rq_comment_'"+i+" class='request_comment'>";
                markup += "<strong>" + comments[i].author + "</strong><br />";
                markup += comments[i].comment_text + "<br />";
                markup += "</div>";
            }

            // Clear the nav buttons and make new ones if necessary
            jQuery('#comment_pagination').empty();
            if (comment_pages > 1) {
                makeCommentNavButtons();
            }
        }

        // Add the reply area TODO make sure user is logged in
        markup += "<br><textarea id='newcomment'></textarea><br />"
        markup += "<button id='submitcomment'>Submit comment</button>";
        jQuery("#commentarea").append(markup);

        jQuery('#submitcomment').click(function() {

            submitComment(jQuery('#newcomment').val());

        });
}

function makeCommentNavButtons() {

    jQuery('#comment_pagination').append('<button id="prevpage"> < </button>');

    for (i = 1; i <= comment_pages; i++) {
        if (i == current_page) {
            jQuery('#comment_pagination').append('<button class="thispage">' + i +
            '</button>');
        } else {
            jQuery('#comment_pagination').append('<button class="pageselect">' + i +
            '</button>');
        }
    }

    jQuery('#comment_pagination').append('<button id="nextpage"> > </button>');

    jQuery( '#nextpage' ).on( 'click', function ( e )  {
     e.preventDefault();
     nextCommentPage();
    } );

    jQuery( '#prevpage' ).on( 'click', function ( e )  {
     e.preventDefault();
     prevPage();
    } );

    jQuery( '.pageselect' ).on( 'click', function ( e )  {
     e.preventDefault();
     makePage(jQuery(this).text());
    } );
}

function nextPage () {
    if (current_page < comment_pages) {
        current_page ++;
        makePage(current_page);
    }
}

function prevPage () {
    if (current_page > 1) {
        current_page --;
        makePage(current_page);
    }
}

function submitComment (text) {
    newComment = new Comment();

    newComment.comment_text = text;
    newComment.request_id = thisRequest.request_id;

    if (newComment.comment_text != '') {

        jQuery.when(postCommentAjax(newComment)).done(function(e){
                loadComments();
        });

    } else {
        alert("Comment text is blank");
    }
}

function getUrlParameter(name) {
    //Strip the ID parameter out of the URL query string. Stole this
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
};
