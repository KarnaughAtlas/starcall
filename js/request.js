    //------------------------------------------------------------------------------
// File name: request.js
// Description: Collection of scripts used when displaying a single request
// Author: JSHayford
// Version: 1.0
//------------------------------------------------------------------------------

var thisRequest = new Object();
var comments_per_page = 5;
var comment_pages;
var current_page;

// This flag tells the scripts if the user is already editing a comment
var editing = false;


jQuery( document ).ready(function() {
    jQuery.when(getRequest()).done(function(e) {
        loadRequest();
        jQuery('#commentarea').empty();
        jQuery('#commentarea').append('<hr>Comments</h3>');
        makeComments(thisRequest.request_id);
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

function submitComment (text) {
    newComment = new Comment();

    newComment.comment_text = text;
    newComment.request_id = thisRequest.request_id;

    if (newComment.comment_text != '') {

        jQuery.when(postCommentAjax(newComment)).done(function(e){
            // Load the last page
            loadComments(comment_pages);
        });

    } else {
        alert("Comment text can not be blank");
    }
}

function editComment(commentDiv) {
    // Turn on the editing flag; we can only edit one at a time
    editing = true;

    // Get the text from the span to pre-load the editor
    var text = commentDiv.querySelector(".comment_text").innerHTML;
    commentDiv.innerHTML = "";
    markup = "<textarea id='edit_comment'>" + text +
    "</textarea><br />"
    markup += "<button class='saveEdit'>Save</button>"
    markup += "<button class='cancelEdit'>Cancel</button>"



    jQuery(commentDiv).append(markup);
    // Set the height of the text box to fit comment
    setHeight("edit_comment");
    jQuery(".saveEdit").click(function(e) {

        // Pull the number out of the string by replacing all non-number chars
        // with ''
        thisCommentIndex = commentDiv.id.replace( /^\D+/g, '');;
        editCommentObj = comments[thisCommentIndex];

        newText = commentDiv.querySelector("#edit_comment").value;

        editCommentObj.comment_text = newText;

        postCommentAjax(editCommentObj);
        makeCommentPage(current_page);

        commentDiv.querySelector("#edit_comment").innerHTML = newText;
    });

    jQuery(".cancelEdit").click(function(e) {
        // Just reload the page
        makeCommentPage(current_page);
    });

        editing = false;
}

function makeComments(id, replyId) {

    var comments = new Comment();

    if (replyId) {
        // We're getting replies to comments
        getCommentsByParentId(id, function(response){
            console.log(response);
        });

    } else {
        getCommentsByRequestId(id, function(response){
            console.log(response);
        });
    }
}

function getUrlParameter(name) {
    //Strip the ID parameter out of the URL query string. Stole this
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}


function setHeight(fieldId) {
    document.getElementById(fieldId).style.height = document.getElementById(fieldId).scrollHeight+'px';
}
