//------------------------------------------------------------------------------
// File name: request.js
// Description: Collection of scripts used when displaying a single request
// Author: JSHayford
// Version: 1.1
//------------------------------------------------------------------------------

var thisRequest = new Object();

// This flag tells the scripts if the user is already editing a comment
var editing = false;


jQuery( document ).ready(function() {

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

    // Submit gift window stuff
    jQuery('#showNewGiftWindowButton').click(function() {
        jQuery('#submitGiftWindow').show();
    });

    jQuery('#submitGiftButton').click(function(){
        if(!jQuery('#agreeTOS').is(':checked')) {
            alert("You must agree to the rules and terms of service.");
        } else if(!jQuery('#fileToUpload').val()) {
            alert("You must select a file to upload.");
        } else {
            jQuery('#submitGiftLoading').show();
            jQuery('#submitGiftButton').hide();
        }
    })

    jQuery('#submitFixSocialMedia').click(function() {
        fixSocialMedia();
    });

    // Admin functions --------------------------------------------------

    // Deny request window
    jQuery('#adminDenyRequestButton').click(function() {
        jQuery('#adminDenyRequestWindow').show();
    });

    jQuery('#adminSelectDenyReason').change(function() {

        if (jQuery('#adminSelectDenyReason').val() == 'incomplete'){
            jQuery('#adminWindowDenyExplanation').text('The application is missing critical information necessary for an artist to fulfill the request.');
        } else if (jQuery('#adminSelectDenyReason').val() == 'socialmedia'){
            jQuery('#adminWindowDenyExplanation').text('The requester did not share the project on social media, or did not provide a direct link to their post.');
        } else if (jQuery('#adminSelectDenyReason').val() == 'inappropriate'){
            jQuery('#adminWindowDenyExplanation').text('The request contains sexually explicit material, or other material forbidden by the Starcall terms of service.');
        } else if (jQuery('#adminSelectDenyReason').val() == 'spam'){
            jQuery('#adminWindowDenyExplanation').text('This is a spam request. No message will be sent to the requester.')
        }
    });

    jQuery("#adminWindowDenyRequestButton").click(function(){
        adminDenyRequest();
    });

    jQuery("#adminApproveRequestButton").click(function(){
        adminApproveRequest();
    });

    jQuery('#adminChangeStatusButton').click(function() {
        jQuery('#adminChangeStatusWindow').show();
    });

    jQuery('#adminSelectStatus').change(function() {

        if (jQuery('#adminSelectStatus').val() == 'approved'){
            jQuery('#adminWindowStatusExplanation').text('Approved requests are visible to all users.');
        } else if (jQuery('#adminSelectStatus').val() == 'denied'){
            jQuery('#adminWindowStatusExplanation').text('Denied requests are only visible to admins and the requesting user. The user will be notified of this change.');
        } else if (jQuery('#adminSelectStatus').val() == 'deleted'){
            jQuery('#adminWindowStatusExplanation').text('Deleted requests are only visible to admins. CAUTION: deleted requests are purged weekly and are irrecoverable.');
        }
    });

    jQuery('#adminWindowChangeStatusButton').click(function(){
        adminChangeRequestStatus();
    });

    // end admin functions ------------------------------------------------

    jQuery.when(getRequest()).done(function(e) {
        if (thisRequest.status == 'approved' || thisRequest.user_authorized) {
            // Only show the request if it is approved, or if the user is authorized
            loadRequest();
            loadComments();
        } else {
            requestNotFound();
        }
    });
});

function loadComments() {

    var markup;

    // Flag for replying to comments
    var replying;

    jQuery('#commentarea').empty();
    jQuery('#commentarea').append('<h3>Comments</h3>');
    getCommentsByRequestId(thisRequest.request_id,function(comments) {
        if(comments.length == 0) {
            jQuery('#commentarea').append('No commments found. Be the first to comment on this request!');
        } else {
            var div = jQuery('#commentarea');
            makeComments(comments,div);
        }

        // Add the reply area TODO make sure user is logged in
        jQuery('#newcommentarea').empty();

        markup =  "<br /><span class=submit_reply><strong>Submit a comment</strong></span>"
        markup += "<br /><textarea id='newcomment'></textarea>"
        markup += "<br /><button id='submitcomment'>Submit comment</button>";

        jQuery("#newcommentarea").append(markup);

        jQuery('#submitcomment').click(function() {

        submitComment(jQuery('#newcomment').val());

        });

        jQuery('.comment_edit').click(function(e) {
            // Pass the parent (the entire comment div)

            if(!editing){

                var commentDiv = e.target.parentElement.parentElement;
                thisCommentID = commentDiv.id.replace( /^\D+/g, '');
                editComment(commentDiv,thisCommentID);

            } else {
                alert("You are already editing a comment!")
            }
        });

        jQuery('.comment_delete').click(function(e) {
            // Permanently delete comment after confirm

            var commentDiv = e.target.parentElement.parentElement;
            thisCommentID = commentDiv.id.replace( /^\D+/g, '');

            if (confirm("Really delete? This can not be undone.")) {
                getCommentById(thisCommentID,function(comment){
                    var editCommentObj = comment;
                    // This doesn't actually delete them; it sets the comment_status column in the DB
                    editCommentObj[0].comment_status = 'deleted';
                    // Send the first element only (even though there's only one) otherwise the server thinks it's an array and barfs
                    postCommentAjax(editCommentObj[0] , function(e){
                        loadComments();
                    });
                });

                alert("Comment deleted.");
            }
        });

        jQuery('.comment_reply').click(function(e) {

            if (!replying) {
                replying = true;
                // Create reply area
                var commentDiv = e.target.parentElement.parentElement;
                var origHTML = jQuery(commentDiv).html();
                markup = '<div class="replyarea">' +
                         '<strong>Reply</strong><br />' +
                         '<textarea id="replyText"></textarea>' +
                         '<button class="submit_reply">Submit</button>' +
                         '<button class="cancel_reply">Cancel</button>';
                jQuery(commentDiv).append(markup);

                jQuery('.cancel_reply').click(function(){
                    if(confirm("Discard reply?")) {
                        jQuery('.replyarea').remove();
                        replying = false;
                    }

                });

                jQuery('.submit_reply').click(function(){
                    var commentDiv = e.target.parentElement.parentElement;
                    var text = jQuery('#replyText').val();
                    var id = commentDiv.getElementsByClassName('commentID')[0].innerHTML;
                    submitComment(text,id);
                    replying = false;
                });
            } else {
                alert("You are already replying to a comment!");
            }
        });
    });
}

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
        document.title = escapeHtml(thisRequest.title) + ' by ' + thisRequest.user_login;
        // Put stuff in the document. Do more with this later

        var markup = '<h1>';
        if(thisRequest.nsfw == true) {
            markup += '<span class="nsfwtag">[18+] </span>';
        }

        markup += thisRequest.title + '</h1>';
        if (thisRequest.fan_art == '1') {
            markup += '<strong>Fan art ';
        } else {
            markup += '<strong>Original ';
        }

        markup += 'request by </strong><a href="https://starcall.sylessae.com/user/' + thisRequest.user_login + '">' + thisRequest.user_login + '</a><br /><br />';

        if  (thisRequest.user_authorized) {
            markup += '<button class="editbutton"> Edit request </button><br /><br />';
        }

        markup += "<strong>Description</strong><br />" + escapeHtml(thisRequest.description) +
                  "<br /><br />";

        if (thisRequest.reference_links) {
            markup += "<strong>References:</strong><br />" + escapeHtml(thisRequest.reference_links) + "<br /> <br />";
              }

        jQuery("#requestarea").append(markup);
        jQuery('.editbutton').click(function() {
            editRequest();
        });

        //Set the hidden input requestId so we can pass it to the gift methods
        jQuery('input[name="requestId"]').val(thisRequest.request_id);

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

    markup = '<strong>Title</strong><br>' +
             '<input type="text" class="edittitle" id="edit-title"></input>' +
             '<strong>Requested by: ' + updateRequest.user_login + '</strong><br><br>' +
             '<strong>NSFW: <strong><input type="checkbox" class="editnsfw" value="NSFW"><br /><br />' +
             '<input type="radio" name="editfanart" class="editfanart" value ="fanart" id="editfanart_yes">' +
             '<label for="editfanart_yes">Fan art </label>' +
             '<input type="radio" name="editfanart" class="editfanart" value ="original" id="editfanart_no">' +
             '<label for="editfanart_no">Original</label><br /><br />' +
             '<strong>Description</strong><br>' +
             '<textarea class="editdescription" id="edit-description"></textarea><br><br>' +
             '<strong>References</strong><br>' +
             '<textarea class="editdescription" id="edit-references"></textarea><br><br>' +
             '<button class="saveButton">Save</button><button class="cancelButton">Cancel</button>' +
             '<button class="deleteRequestButton">Delete Request</button>';

    jQuery('#requestarea').append(markup);
    jQuery('#edit-description').html(escapeHtml(updateRequest.description));
    jQuery('#edit-references').html(escapeHtml(updateRequest.reference_links));
    jQuery('#edit-title').val(escapeHtml(updateRequest.title));

    if(updateRequest.nsfw == true) {
        jQuery('.editnsfw').prop('checked', true);
    } else {
        jQuery('.editnsfw').prop('checked', false);
    }

    if (updateRequest.fan_art == '1') {
        jQuery('#editfanart_yes').prop('checked',true);
    } else {
        jQuery('#editfanart_no').prop('checked',true);
    }


    // Add handlers to save/cancel/delete buttons
    jQuery('.saveButton').click(function(){
        saveRequest(updateRequest);
    });

    jQuery('.cancelButton').click(function(){
        cancelChanges();
    });

    jQuery('.deleteRequestButton').click(function(){
        deleteRequest();
    });
}

function saveRequest(updateRequest) {
    updateRequest.description = jQuery('#edit-description').val();
    updateRequest.reference_links = jQuery('#edit-references').val();
    updateRequest.title = jQuery('#edit-title').val();
    if(jQuery('.editnsfw').is(':checked')) {
        updateRequest.nsfw = '1';
    } else {
        updateRequest.nsfw = '0';
    }

    if (jQuery('input[name=editfanart]:checked').val() == 'original') {
        updateRequest.fan_art = '0';
    } else {
        updateRequest.fan_art = '1';
    }

    if(JSON.stringify(updateRequest) == JSON.stringify(thisRequest)) {
        //User made no changes
        alert("There are no changes to save!");

    } else if (updateRequest.description == '') {
        alert("Description can not be blank!");
        jQuery('#edit-description').val(thisRequest.description);

    } else if (updateRequest.title == '') {
        alert("Title can not be blank!");
        jQuery('#edit-title').val(thisRequest.title);

    } else {
        jQuery('#loadingWindow').show();
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
                  jQuery("#loadingText").text('Success!')
                  jQuery("#requestarea").empty();
                  // Load the request again so user sees the Changes
                  location.reload();
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

function submitComment (text,parent) {
    newComment = new Comment();

    newComment.comment_text = text;
    if( parent ) {
        // This is a reply, so we need to blank the request ID and include the parent
        newComment.comment_type = 'reply';
        newComment.parent_id = parent;

    } else {
        // This is a top-level comment on the request
        newComment.parent_id = thisRequest.request_id;
        newComment.comment_type = 'request';
    }

    if (newComment.comment_text != '') {
        postCommentAjax(newComment , function(e){
            loadComments();
        });
    } else {
        alert("Comment text can not be blank");
    }
}

function editComment(commentDiv,commentID) {
    // Turn on the editing flag; we can only edit one at a time
    editing = true;
    //Save original HTML so we can put it back if user cancels

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

        getCommentById(commentID,function(comment){
            editCommentObj = comment;
            newText = commentDiv.querySelector("#edit_comment").value;
            editCommentObj[0].comment_text = newText;
            // Get rid of replies so the server code doesn't think it's an array
            postCommentAjax(editCommentObj[0] , function(e){
                loadComments();
            });
        });
    });

    jQuery(".cancelEdit").click(function(e) {
        // Just reload the page
        if (confirm("Discard changes?")) {
            loadComments();
        }
    });

        editing = false;
}

function makeComments(comments,div) {

    var markup;

    // This is a recursive routine that is called at the request level, and then
    // again for each commentto get replies. It accepts an array of comment objects.

    for (var i=0; i < comments.length; i++) {

        var containerID = 'rq_container_' + comments[i].comment_id;
        var divID = 'rq_comment_' + comments[i].comment_id;

        markup = "<div id='" + containerID + "' class = 'request_container'>"
        markup += "<div id='" + divID + "' class='request_comment'>";
        markup += "<strong>" + comments[i].author + "</strong>";
        markup += " | <span class='create_date'>"+comments[i].create_date +" </span> | ";

        markup+="<div class='comment_controls'><span class='comment_reply'>reply</span>"

        // If user is authorized, show the edit buttons
        if (comments[i].user_authorized) {
            markup += "<span class='comment_edit'>edit</span><span class='comment_delete'>delete</span>";
        }

        markup += "</div><br />";
        markup += "<span class='comment_text'>" + escapeHtml(comments[i].comment_text) + "</span><br />";


        if (comments[i].edit_date && comments[i].edit_date != comments[i].create_date) {
            markup += "<br /><span class='edit_text'> Edited by " + comments[i].editing_user +
            " on " + comments[i].edit_date + "</span>";
        }

        // Include hidden ID span for easily snagging the comment ID

        markup += "<span class = 'commentID' style='display:none'>" + comments[i].comment_id + "</span>";

        markup += "</div></div>";

        jQuery(div).append(markup);

        if (comments[i].replies.length > 0) {
            // Recurse to load replies
            replyDiv = jQuery('#'+containerID);
            makeComments(comments[i].replies, replyDiv);
        }
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


// Admin functions

function adminDenyRequest() {
    jQuery("#loadingWindow").show();
    updateRequest = Object.assign({}, thisRequest);
    updateRequest.status = 'denied';
    updateRequest.status_reason = jQuery('#adminSelectDenyReason').val();
    jQuery.when(ajaxUpdateRequest(updateRequest)).done(function(e) {
        jQuery("#loadingText").text("Success!");
        location.reload();
    });
}

function adminApproveRequest() {
    jQuery("#loadingWindow").show();
    updateRequest = Object.assign({}, thisRequest);
    updateRequest.status = 'approved';
    jQuery.when(ajaxUpdateRequest(updateRequest)).done(function(e) {
        jQuery("#loadingText").text("Success!");
        location.reload();
    });

}

function adminChangeRequestStatus() {
    jQuery("#loadingWindow").show();
    updateRequest = Object.assign({}, thisRequest);

    if (jQuery('#adminSelectStatus').val() != '') {
        updateRequest.status = jQuery('#adminSelectStatus').val();
        jQuery.when(ajaxUpdateRequest(updateRequest)).done(function(e) {
            jQuery("#loadingText").text("Success!");
            location.reload();
        });
    } else {
        // Invalid status
        alert("Select a valid status");
    }
}

function fixSocialMedia() {
    jQuery("#loadingWindow").show();
    updateRequest = Object.assign({}, thisRequest);
    if (jQuery('#fixSocialMedia') == '') {
        alert("Please enter a social media link.");
    } else if (jQuery('#fixSocialMedia').val() == thisRequest.social_media) {
        alert("Please update your social media link.");
    } else {
        updateRequest.social_media = jQuery('fixSocialMedia').val();
        jQuery.when(ajaxUpdateRequest(updateRequest)).done(function(e) {
            jQuery("#loadingText").text("Success!");
            location.reload();
        });
    }
}

function deleteRequest () {
    jQuery("#loadingWindow").show();
    updateRequest = Object.assign({}, thisRequest);
    if (confirm("Really delete? This can not be undone!")) {
        updateRequest.status = 'deleted';
        jQuery.when(ajaxUpdateRequest(updateRequest)).done(function(e) {
            jQuery("#loadingText").text("Success!");
            location.reload();
        });
    }
}

function ajaxUpdateRequest(updateRequest) {
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
              jQuery("#requestarea").empty();
              // Load the request again so user sees the Changes
              jQuery.when(getRequest()).done(function(e) {
                  loadRequest();
              });
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

function escapeHtml(text) {
  if(text) {
    return text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
      } else {
        return '';
      }
}
