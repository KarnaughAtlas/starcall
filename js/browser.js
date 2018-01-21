//------------------------------------------------------------------------------
// File name: browser.js
// Description: Collection of scripts used when displaying the request browser
// Author: JSHayford
// Version: 1.0
//------------------------------------------------------------------------------

//Number of requests per page
var requests_per_page = 20;

var current_page = 1;
var total_pages;
var requests = new Object();

jQuery( document ).ready(function() {
   jQuery.when(getRequests()).done(function(e) {
       makePage(1);
   });
});

jQuery('#requestdesc').on('keypress', function (e) {
   var code = e.keyCode || e.which;
   if (code==13) {
       jQuery.when(getRequests(jQuery(this).val())).done(function(e) {
           makePage(1);
       });
   }
} );

jQuery('#searchbutton').on( 'click', function ( e )  {
 e.preventDefault();
 jQuery.when(getRequests(jQuery('#requestdesc').val())).done(function(e) {
     makePage(1);
 });
} );

function getRequests (filter) {

   if (filter) {
       var endpoint = '/wp-json/starcall/v1/requests/' + '?desc=' + filter;
   } else {
       var endpoint = '/wp-json/starcall/v1/requests/'
   }

   return jQuery.ajax( {
     url: endpoint,
     method: 'GET',
     beforeSend: function ( xhr ) {
         xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
     },
     success: function ( response ) {
           requests = response;
           total_pages = Math.ceil(response.length / requests_per_page);
      },
     failure: function ( response, err ) {
       console.log("Got into the failure function");
       alert ("It didn't work");
       document.write (err);
       document.write(response);
     },
     cache: false,
     dataType: 'json'
   } );
}

function makePage (page) {

    jQuery('#requesttable tbody').empty();

    if (requests.length == 0) {
        markup = '<tr><td colspan = "4">' +
            "No requests found. Try broadening your search.</tr></td>";
        jQuery('#requesttable tbody').append(markup);
    } else {

        for (var i = (page - 1) * requests_per_page;
             i < (page * requests_per_page) &&
             i < requests.length; i++) {

            markup = "<tr class='requestrow'><td>" +
                                       requests[i].user_login + "</td>" +
                     "<td class='title'>" + requests[i].title + "</td>" +
                     "<td class='description'>" +
                                 requests[i].description.slice(0,30) + "</td>" +
                     "<td class='create_date'>" +
                                             requests[i].create_date + "</td>" +
                     "<td class='request_id' style='display:none'>" +
                     requests[i].request_id + "</td></tr>";

            jQuery('#requesttable tbody').append(markup);
        }

        // Add function for clicking on rows
        jQuery('.requestrow').on('click', function(e) {
            // Eventually this will link us to the request page - test for now

            var url = 'https://starcall.sylessae.com/request/?request_id=' +
                                          jQuery('td.request_id', this).text();

            window.open(url);
        });
    }

    current_page = page;

    // Clear page buttons and make more if we have more than one page
    jQuery('#pagination').empty();
    if (total_pages > 1) {
        makeNavButtons();
    }
}

function makeNavButtons() {

    jQuery('#pagination').append('<button id="prevpage"> < </button>');

    for (i = 1; i <= total_pages; i++) {
        if (i == current_page) {
            jQuery('#pagination').append('<button class="thispage">' + i +
            '</button>');
        } else {
            jQuery('#pagination').append('<button class="pageselect">' + i +
            '</button>');
        }
    }

    jQuery('#pagination').append('<button id="nextpage"> > </button>');

    jQuery( '#nextpage' ).on( 'click', function ( e )  {
     e.preventDefault();
     nextPage();
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
    if (current_page < total_pages) {
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
