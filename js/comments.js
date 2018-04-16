//------------------------------------------------------------------------------
// File name: comments.js
// Description: Collection of scripts related to request comments
// Author: JSHayford
// Version: 1.0
//------------------------------------------------------------------------------

// Constructor function for Comment prototype
function Comment(id,requestId,authorId,replyId,text,createDate,editDate,
    edit_user,status) {

    // Vars
    this.comment_id = id;
    this.request_id = requestId;
    this.author_id = authorId;
    this.reply_id = replyId;
    this.comment_text = text;
    this.create_date = createDate;
    this.edit_date = editDate;

    // Methods
    this.post = function () {
        response = postCommentAjax(this);
    }

    this.delete = function() {
        // Delete the comment
        alert("This isn't done yet");
    }
}

var replying = false;
var editing = false;

function getCommentsByRequestId (id,callback) {
    if (id) {
        var endpoint = '/wp-json/starcall/v1/comments/' + '?request_id=' + id;

        jQuery.ajax( {
        url: endpoint,
        method: 'GET',
        beforeSend: function ( xhr ) {
         xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
        },
        success: function ( response ) {
         callback(response);
        },
        failure: function ( response, err ) {
         console.log("Ajax failure");
         console.log(err);
        },
        cache: false,
        dataType: 'json' });



   } else {
       // We didn't get an ID
       // Return JSON with informative error
       return (false);
   }
}

function getCommentById (id,callback) {
    if (id) {
        var endpoint = '/wp-json/starcall/v1/comments/?comment_id=' + id;

        jQuery.ajax( {
        url: endpoint,
        method: 'GET',
        beforeSend: function ( xhr ) {
         xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
        },
        success: function ( response ) {
         callback(response);
        },
        failure: function ( response, err ) {
         console.log("Ajax failure");
         console.log(err);
        },
        cache: false,
        dataType: 'json' });

   } else {
       // We didn't get an ID
       // Return JSON with informative error
       return (false);
   }
}

function getCommentsByParentId (id,callback) {
    if (id) {
        var endpoint = '/wp-json/starcall/v1/comments/' + '?reply_id=' + id;

        return jQuery.ajax( {
        url: endpoint,
        method: 'GET',
        beforeSend: function ( xhr ) {
         xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
        },
        success: function ( response ) {
         callback(response);
        },
        failure: function ( response, err ) {
         console.log("Ajax failure");
         console.log(err);
        },
        cache: false,
        dataType: 'json'
        } );
   } else {
       // We didn't get an ID
       return (false);
   }
}

function postCommentAjax(comment,callback) {
    var endpoint = '/wp-json/starcall/v1/comments/';
    return jQuery.ajax( {
      url: endpoint,
      method: 'POST',
      data: JSON.stringify(comment),
      beforeSend: function ( xhr ) {
          xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
      },
      complete: function ( response ) {
          callback(response);
       },
      failure: function ( response, err ) {
          alert ("Error posting comment");
      },
      cache: false,
      dataType: 'json'
    } );
}
function deleteCommentAjax(comment,callback) {
    var endpoint = '/wp-json/starcall/v1/comments/';
    return jQuery.ajax( {
      url: endpoint,
      method: 'DELETE',
      data: JSON.stringify(comment),
      beforeSend: function ( xhr ) {
          xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
      },
      complete: function ( response ) {
          callback(response);
       },
      failure: function ( response, err ) {
          alert ("Error posting comment");
      },
      cache: false,
      dataType: 'json'
    } );
}

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
            var postCommentObj = new Object();
            postCommentObj.comment_text = text;
            postCommentObj.comment_type = 'reply';
            postCommentObj.parent_id = id;
            postCommentAjax(postCommentObj, function(e){
                location.reload();
            });
            replying = false;
        });
    } else {
        alert("You are already replying to a comment!");
    }
});

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
                location.reload();
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

function setHeight(fieldId) {
    document.getElementById(fieldId).style.height = document.getElementById(fieldId).scrollHeight+'px';
}
