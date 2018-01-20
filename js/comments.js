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

function getCommentsByRequestId (id) {
    if (id) {
        var endpoint = '/wp-json/starcall/v1/comments/' + '?request_id=' + id;

        return jQuery.ajax( {
        url: endpoint,
        method: 'GET',
        beforeSend: function ( xhr ) {
         xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
        },
        success: function ( response ) {
         return(response.JSON);
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

function postCommentAjax(comment) {
    var endpoint = '/wp-json/starcall/v1/comments/';
    return jQuery.ajax( {
      url: endpoint,
      method: 'POST',
      data: JSON.stringify(comment),
      beforeSend: function ( xhr ) {
          xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
      },
      complete: function ( response ) {
          return(response);
       },
      failure: function ( response, err ) {
          alert ("Error posting comment");
      },
      cache: false,
      dataType: 'json'
    } );
}
