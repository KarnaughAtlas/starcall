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

function postCommentAjax(id,callback) {
    var endpoint = '/wp-json/starcall/v1/comments/?comment_id='+id;
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
