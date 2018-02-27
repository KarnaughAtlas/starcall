<?php
/**
 * Plugin Name: Starcall site-specific plugin
 * Plugin URI: https://github.com/iamsayed/read-me-later
 * Description: This plugin includes features unique to starcall.sylessae.com and is not intended for use anywhere else.
 * Version: 1.0.0
 * Author: Josh Hayford (KarnaughAtlas)
 * License: GPL3
 */


//------------------------------------------------------------------------
// Function: make_comment_array
//
// This request gets comments. Initial support only for retrieving by
/// user ID, reply ID and request ID.
//
// URL: https://starcall.sylessae.com/wp-json/starcall/v1/comments/
// Method: GET
// Returns: JSON
// Parms: URL parms - user_id or request_id
//------------------------------------------------------------------------
function make_comment_array($params,$currentUser,$userIsAdmin) {
  global $wpdb;

 // Initialize SQL query
 $sql = 'SELECT comment_id,request_id,author_id, u1.user_login AS author,
                reply_id,comment_text, create_date, edit_user,
                u2.user_login AS editing_user, edit_date, comment_status
         FROM wpsc_rq_comments AS comments
         JOIN wp_users AS u1 ON comments.author_id = u1.ID
         LEFT JOIN wp_users AS u2 ON comments.edit_user = u2.ID';

 if($params) {
     // Build the WHERE clause
     if(isset($params['author_id'])) {
         $filters[] = 'author_id = ' . $params['user_id'];
     }

     if(isset($params['request_id'])) {
         $filters[] = 'request_id = ' . $params['request_id'];
     }

     if(isset($params['reply_id'])) {
         $filters[] = 'reply_id = ' . $params['reply_id'];
     }

     if(isset($params['comment_id'])) {
         $filters[] = 'comment_id = ' . $params['comment_id'];
     }
 }

 if  (!$userIsAdmin) {
       // User is a filthy peasant, they may not see the glory of
       // unapproved comments
       $filters[] = 'comment_status = "approved"';
   }

 // IMPLOSION
 if (isset($filters)) {
     $sql .= ' WHERE ' . implode(' AND ', $filters);
 }

 // Query the database and return the response
 write_log($sql);
 $comments = $wpdb->get_results($sql);

 foreach ($comments as $comment) {
     if ($userIsAdmin){
         // Admins and moderators can modify any request
         $comment->user_authorized = true;
     } elseif ($currentUser == $comment->author_id) {
         // Users can modify their own requests
         $comment->user_authorized = true;
     } else {
         // No touchy
         $comment->user_authorized = false;
     }

     // Recurse to get the replies to this comment
     $replyParams['reply_id'] = $comment->comment_id;
     $comment->replies = make_comment_array($replyParams,$currentUser,$userIsAdmin);
 }
   return($comments);
}

//------------------------------------------------------------------------
// Function: make_comment_array
//
// This request gets comments. Initial support only for retrieving by
/// user ID, reply ID and request ID.
//
//------------------------------------------------------------------------
function make_gift_array($params,$currentUser,$userIsAdmin) {
  global $wpdb;

 // Initialize SQL query
 $sql = 'SELECT id,request_id,user, u1.user_login AS user_name,
                path, caption, create_date, edit_date, edit_user,
                u2.user_login AS editing_user, status
         FROM wpsc_rq_gifts AS gifts
         JOIN wp_users AS u1 ON gifts.user = u1.ID
         LEFT JOIN wp_users AS u2 ON gifts.edit_user = u2.ID';

 if($params) {
     // Build the WHERE clause
     if(isset($params['user'])) {
         $filters[] = 'user = ' . $params['user_id'];
     }

     if(isset($params['request_id'])) {
         $filters[] = 'request_id = ' . $params['request_id'];
     }

 }

 if  (!$userIsAdmin) {
       // User is a filthy peasant, they may not see the glory of
       // unapproved gifts
       $filters[] = 'status = "approved"';
   }

 // IMPLOSION
 if (isset($filters)) {
     $sql .= ' WHERE ' . implode(' AND ', $filters);
 }

 // Query the database and return the response
 write_log($sql);
 $gifts = $wpdb->get_results($sql);

 foreach ($gifts as $gift) {
     if ($userIsAdmin){
         // Admins and moderators can modify any gift
         $gift->user_authorized = true;
     } elseif ($currentUser == $gift->user) {
         // Users can modify their own requests
         $gift->user_authorized = true;
     } else {
         // No touchy
         $gift->user_authorized = false;
     }

  }

 return($gifts);
}

 //-----------------------------------------------------
 // REST API server for async database requests
 // 	See callback functions for usage
 //-----------------------------------------------------

  class starcall_rest extends WP_REST_Controller {

    //The namespace and version for the REST server
    var $my_namespace = 'starcall/v';
    var $my_version   = '1';

    public function register_routes() {
        $namespace = $this->my_namespace . $this->my_version;

        // Register request routes

        register_rest_route( $namespace, '/requests', array(
            array(
                'methods'         => WP_REST_Server::READABLE,
                'callback'        => array( $this, 'get_requests' ) ),

            array(
                'methods'         => WP_REST_Server::EDITABLE,
                'callback'        => array( $this, 'post_request' ) ),

            array(
                'methods'         => WP_REST_Server::DELETABLE,
                'callback'        => array( $this, 'delete_request' ) ),
        ) );

        // Register comment routes

        register_rest_route( $namespace, '/comments', array(
            array(
                'methods'         => WP_REST_Server::READABLE,
                'callback'        => array( $this, 'get_comments' ) ),

            array(
                'methods'         => WP_REST_Server::EDITABLE,
                'callback'        => array( $this, 'post_comment' ) ),

            array(
                'methods'         => WP_REST_Server::DELETABLE,
                'callback'        => array( $this, 'delete_comment' ) ),
        ) );

        register_rest_route( $namespace, '/gifts', array(
            array(
                'methods'         => WP_REST_Server::READABLE,
                'callback'        => array( $this, 'get_gifts' ) ),

            array(
                'methods'         => WP_REST_Server::EDITABLE,
                'callback'        => array( $this, 'post_gift' ) ),

            array(
                'methods'         => WP_REST_Server::DELETABLE,
                'callback'        => array( $this, 'delete_gift' ) ),
        ) );


          //TODO register gift routes
    }

  // Register our REST Server
    public function hook_rest_server(){
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function get_requests( WP_REST_Request $request ){

    //------------------------------------------------------------------------
    // Function: get_requests
    //
    // This function gets requests (no waaaaay). If called with no parameters
    // it returns all SFW requests with a status = 'approved' and nsfw = 0.
    // Otherwise, you can give it parms in the URL and it'll filter requests.
    // If given a request_id it will ignore all other parms and return the
    // request with that ID.
    //
    // URL: https://starcall.sylessae.com/wp-json/starcall/v1/requests/
    // Method: GET
    // Returns: Requests JSON object
    // Parms: id (int): request ID. If given this parameter, the function will
    // return a single matching request.
    // NOTE: If you give get_requests an ID it will ignore all other parms!
    //
    //        fan_art (yes | no ):
    //        	yes = only fan art
    //          no = only original
    //          anything else (or blank) is both
    //
    //        desc (text): text filter for description
    //
    //        nsfw (yes | no | both):
    //        	yes = include NSFW only
    //          both: include everything
    //			no (or anything else) (default) =  omit NSFW results
    //
    //		  status (submitted | approved | pending | trash ):
    //		  	submitted: returns requests awaiting approval
    //          approved (default):  requests visible to public
    //------------------------------------------------------------------------------------------------------------------

        global $wpdb;
        $sql = 'SELECT request_id,title,user_id,user_login,nsfw,fan_art,
                       reference_links, description, create_date, edit_date,
                       status
                FROM wpsc_rq_requests
                JOIN wp_users ON wpsc_rq_requests.user_id = wp_users.ID';
        $requests = new \stdClass();

        // Determine if we need to add WHERE clauses to our query
      $params = $request->get_params();

      if (isset($params['request_id'])) { // fetching a specific request, not user-selectable. Ignore other parms.
          $filters[] =  'request_id = ' . $params['request_id'];

      } elseif ($params) { // we have filters

          // $filters is an array we'll use to build the dynamic WHERE/AND clause. Make sure we don't have junk
          unset($filters);

          //-------------------------------------------------------------------------
          // Fan art flag --
          //	yes = fan art only
          // 	no = original characters only
          //	all = include all (default behavior)
          //-------------------------------------------------------------------------

          if (isset($params['fan_art'])) {

              if($params['fan_art'] == "yes") { // Fan art characters only
                  $filters[] =  'fan_art = 1';

              } elseif ($params['fan_art'] == "no") { // Original characters only
                  $filters[] =  'fan_art = 0';

              } else { // Anything else, send them everything - I.E. do nothing

              }
          }

          //-------------------------------------------------------------------------
          // Description filter --
          //	Text-based filter
          //-------------------------------------------------------------------------

          if (isset($params['desc'])) {

              $filters[] =  'description LIKE "%' . trim($params['desc']) . '%"';
          }

          //-------------------------------------------------
          // NSFW flag --
          // 	yes = include NSFW and non-NSFW results
          //	only = only show NSFW results
          //	no = omit NSFW (default behavior)
          //-------------------------------------------------

          if (isset($params['nsfw'])) {


                if ($params['nsfw'] == 'yes') {
                   // Include NSFW and regular results, so don't add anything to the query

                } elseif ($params['nsfw'] == "only") {
                    // Include only NSFW results
                    $filters[] =  'nsfw = 1';
                }

            } else {
                    // Default is omit NSFW results
                    $filters[] =  'nsfw = 0';
            }


          //-------------------------------------------------------------------------
          // Status flag --
          //	submitted: submitted but awaiting moderator approval (mod only)
          //	  pending: awaiting changes before approval (mod only)
          //	 approved: visible to public (default behavior)
          //	  deleted: in the trash (mod only)
          //        all: show all (mod only)
          //-------------------------------------------------------------------------

          // TODO: Only allow moderators and above to filter on status, otherwise user will only see approved requests

            if(isset($params['status'])) {
                if ($params['status'] == 'submitted') {

                    $filters[] =  "status = 'submitted'";

                } elseif ($params['status'] == 'deleted') {

                    $filters[] =  "status = 'deleted'";

                } elseif ($params['status'] == 'pending') {

                    $filters[] =  "status = 'pending'";

                } elseif ($params['status'] == 'all') {
                     // Include all so don't add anything

                } else {
                     // Default is include only 'approved' requests
                    $filters[] =  "status = 'approved'";

                }
            }

          //TODO allow filtering on artist, submitter <- search by name?
          //TODO include date filters?

      } else {
          // No params, but by default we still need to filter out NSFW results and include only approved requests
          $sql .=  " WHERE nsfw = 0 AND status = 'approved'";
      }

        if (isset($filters)) {
            $sql .= ' WHERE ' . implode(' AND ', $filters);
        }

        //Add the SQL order bys here
        $sql .= " ORDER BY RAND()";

      $requests = $wpdb->get_results($sql);

      // Now we need to figure out if the user has auth rights to modify the requests

      $userIsAdmin = (current_user_can('administrator') || current_user_can('moderator'));
      $currentUser = get_current_user_id();

      foreach ($requests as $request) {
          if ($userIsAdmin){
              // Admins and moderators can modify any request
              $request->user_authorized = true;
          } elseif ($currentUser == $request->user_id) {
              // Users can modify their own requests
              $request->user_authorized = true;
          } else {
              // No touchy
              $request->user_authorized = false;
          }
      }

      return($requests);
  }

    public function post_request (WP_REST_Request $request) {

    //---------------------------------------------------------------------
    // Function: post_request
    //
    // This is the create/update function for requests. This function
    // consumes a JSON object in the body of the request.

    // !!NOTE!!

    // Be careful!

    // If post_requests is given an id parm it assumes we're modifying an
    // existing request. You can not create a request with a specific ID,
    // it is always generated by the database. If the ID passed in the
    // JSON does not exist, post_requests returns false. A successful
    // update will return true, otherwise false.

    // !!NOTE!!

    //
    // If post_requests is not given an ID it assumes we're creating a new
    // one. Success will return true, failure false.
    //
    // Any logged in user may create requests. Only moderators and the
    // owner of the request in question may modify.
    //
    // URL: https://starcall.sylessae.com/wp-json/starcall/v1/requests/
    // Method: POST
    // Returns: JSON with various things in it
    // Parms: JSON object
    //---------------------------------------------------------------------

        global $wpdb;
        $response = new \stdClass();

        // Get our JSON from the HTTP request body
        $requestToUpdate = json_decode($request -> get_body());

        if($requestToUpdate !== null) {
            // Successfully got the post body
          if (isset($requestToUpdate->request_id)) {
              // We're updating an existing request
              // First, let's get the request we're changing
              $sql = "SELECT * FROM wpsc_rq_requests " .
                     "WHERE request_id = " . $requestToUpdate->request_id;

              $existingRequest = $wpdb->get_row($sql);

              if ($existingRequest) {
                  // Found it. Make sure the user is allowed to do this
                  // Must be mod, admin, or the owner of the request
                  $currentUser = get_current_user_id();

                  if (current_user_can('moderator') ||
                      current_user_can('administrator') ||
                      $currentUser == $existingRequest->user_id) {
                      // Do the update

                      // We're using the wpdb object for database access,
                      // so we need to build a few arrays for it.

                      // Set the table we're going to update

                      $table = 'wpsc_rq_requests';

                      // Build data array for fields to update

                      $data = array(
                          'title' => $requestToUpdate->title,
                        'user_id' => $requestToUpdate->user_id,
                           'nsfw' => $requestToUpdate->nsfw,
                        'fan_art' => $requestToUpdate->fan_art,
                'reference_links' => $requestToUpdate->reference_links,
                    'description' => $requestToUpdate->description,
                         'status' => $requestToUpdate->status,
                      'edit_user' => $currentUser
                     );

                     // Build the where array
                     $where = array('request_id' => $requestToUpdate->request_id);

                     // Now we can do the update. Success should have the total
                     // rows affected.
                     $success = $wpdb->update( $table, $data, $where);

                     if ($success) {
                         // We did it, guys
                         $response->success = true;
                         $response->rows_updaded = $success;

                     } else {
                         // Update failed
                         write_log("ERROR: wpdb->update barfed.");
                         write_log("Request ID: " . $requestToUpdate->request_id);
                         write_log($data);

                         $response->success = false;
                         $response->errmsg = 'Error updating request';
                     }

                  } else {
                      // User is not authorized, send error
                      $response->success = false;
                      $response->errmsg = 'User is not authorized';
                  }

              } else {
                  // Didn't find the request, send error
                  $response->success = false;
                  $response->errmsg = 'Request with ID ' . $requestToUpdate->request_id . ' not found';
              }
          } else {
              // We're submitting a new request. Do we have a valid user?
              if (current_user_can('read')) {
                  // Insert request
                  // We're using the wpdb object for database access,
                  // so we need to build a few arrays for it.

                  // Set the table
                  $table = 'wpsc_rq_requests';
                  $requestStatus = 'submitted';

                  $currentUser = get_current_user_id();

                  // Build data array for fields to insert
                  $data = array(
                      'title' => $requestToUpdate->title,
                    'user_id' => $currentUser,
                       'nsfw' => $requestToUpdate->nsfw,
                    'fan_art' => $requestToUpdate->fan_art,
               'social_media' => $requestToUpdate->social_media,
            'reference_links' => $requestToUpdate->reference_links,
                'description' => $requestToUpdate->description,
                   'how_hear' => $requestToUpdate->how_hear,
                     'status' => $requestStatus
                 );


                  // Now we can do the insert. Success here will have the
                  // total number of rows affected, which hopefully will be 1
                  $success = $wpdb->insert( $table, $data);

                  if($success) {
                      // Insert successful!
                      $response->success = true;
                      $response->new_id = $wpdb->insert_id;

                  } else {
                      // Error inserting
                      $response->success = false;
                      $response->errmsg = 'Error occurred during insert';
                  }

              } else {
                  // User is not valid or not logged in
                  $response->success = false;
                  $response->errmsg = 'Invalid user - are you logged in?';
              }
          }
        } else {
          // We didn't get the post body
          $response->success = false;
          $response->errmsg = "Failed to get post body";
        }

        return($response);
    }

    public function delete_request (WP_REST_Request $request) {

        //--------------------------------------------------------------------
        // Function: delete_request
        //
        // This function deletes reques. It takes only one parm, the ID
        // of the request to delete. Note - only moderators and administrators
        // can actually delete requests. Request owners can only set the status
        // to 'trash' which is done via post_request.
        //
        // URL: https://starcall.sylessae.com/wp-json/starcall/v1/requests/
        // Method: DELETE
        // Returns: JSON with true/false and an error if applicable
        // Parms: ID
        //--------------------------------------------------------------------


        global $wpdb;
        $response = new \stdClass();

        $params = $request->get_params();

        if (isset($params['request_id'])) {

            if (current_user_can('administrator') || current_user_can('moderator')) {
                // User is authorized
                $deletedRows = $wpdb->delete( 'wpsc_rq_requests', array( 'request_id' => $parms['request_id'] ) );
                if ($deletedRows != 1) {
                    // We should only have affected one row
                    $response->success = true;
                } else {
                    // Something went wrong
                    write_log('Attempting to delete ID: ' . $parms['request_id']);
                    write_log('Error occurred, affected '.$deletedRows.' rows');
                    $response->success = false;
                    $response->errmsg = 'Error occurred during delete - see debug.log';
                }

            } else {
                //user is not authorized
                $response->success = false;
                $response->errmsg = 'User is not authorized';
            }

        } else {
            // No ID, send an error
            $response->success = false;
            $response->errmsg = 'No ID passed for delete';
        }

        return($response);
    }

    public function get_comments (WP_REST_Request $request) {
    //------------------------------------------------------------------------
    // Function: get_comments
    //
    // This request gets comments. Initial support only for retrieving by
    /// user ID, reply ID and request ID.
    //
    // URL: https://starcall.sylessae.com/wp-json/starcall/v1/comments/
    // Method: GET
    // Returns: JSON
    // Parms: URL parms - user_id or request_id
    //------------------------------------------------------------------------

        // Globals
        global $wpdb;

        // Objects
        $comments = new \stdClass();
        $userIsAdmin = (current_user_can('administrator') || current_user_can('moderator'));
        $currentUser = get_current_user_id();

        $params = $request->get_params();

        $comments = make_comment_array($params,$currentUser,$userIsAdmin);

        return($comments);
    }

    public function post_comment (WP_REST_Request $request) {
    //------------------------------------------------------------------------
    // Function: post_comment
    //
    // TODO add description and other stuff here
    //
    // URL: https://starcall.sylessae.com/wp-json/starcall/v1/comments/
    // Method: POST
    // Returns: JSON
    // Parms: JSON
    //------------------------------------------------------------------------

    global $wpdb;

    $response = new \stdClass();

    // Get our JSON from the HTTP comment body
    $commentToUpdate = json_decode($request -> get_body());

    if($commentToUpdate !== null) {
        write_log($commentToUpdate);
        // Successfully got the post body
      if (isset($commentToUpdate->comment_id)) {
          // We're updating an existing comment
          // First, let's get the comment we're changing
          $sql = "SELECT * FROM wpsc_rq_comments " .
                 "WHERE comment_id = " . $commentToUpdate->comment_id;

          $existingComment = $wpdb->get_row($sql);

          if ($existingComment) {
              // Found it. Make sure the user is allowed to do this
              // Must be mod, admin, or the owner of the comment
              $currentUser = get_current_user_id();

              if (current_user_can('moderator') ||
                  current_user_can('administrator') ||
                  $currentUser == $existingComment->author_id) {
                  // Do the update

                  // We're using the wpdb object for database access,
                  // so we need to build a few arrays for it.

                  // Set the table we're going to update

                  $table = 'wpsc_rq_comments';

                  // Build data array for fields to update

                  $data = array(
                    'request_id' => $commentToUpdate->request_id,
                     'author_id' => $commentToUpdate->author_id,
                      'reply_id' => $commentToUpdate->reply_id,
                  'comment_text' => $commentToUpdate->comment_text,
                'comment_status' => $commentToUpdate->comment_status,
                     'edit_user' => $currentUser
                 );

                 // Build the where array
                 $where = array('comment_id' => $commentToUpdate->comment_id);

                 // Now we can do the update. Success should have the total
                 // rows affected.
                 $success = $wpdb->update( $table, $data, $where);

                 if ($success) {
                     // We did it, guys
                     $response->success = true;
                     $response->rows_updaded = $success;

                 } else {
                     // Update failed
                     write_log("ERROR: wpdb->update barfed.");
                     write_log("Comment ID: " . $commentToUpdate->comment_id);
                     write_log($data);

                     $response->success = false;
                     $response->errmsg = 'Error updating comment';
                 }

              } else {
                  // User is not authorized, send error
                  $response->success = false;
                  $response->errmsg = 'User is not authorized';
              }

          } else {
              // Didn't find the comment, send error
              $response->success = false;
              $response->errmsg = 'Comment with ID ' . $commentToUpdate->comment_id . ' not found';
          }
      } else {
          // We're submitting a new comment. Do we have a valid user?
          write_log("We're in the new comment block");
          if (current_user_can('read')) {
              // Insert comment
              // We're using the wpdb object for database access,
              // so we need to build a few arrays for it.

              // Set the table
              $table = 'wpsc_rq_comments';
              $commentStatus = 'approved';

              $currentUser = get_current_user_id();
              if (isset($commentToUpdate->reply_id)) {
                  $replyID = $commentToUpdate->reply_id;
              } else {
                  $replyID = 0;
              }

              // Build data array for fields to insert
              $data = array(
                  'request_id' => $commentToUpdate->request_id,
                   'author_id' => $currentUser,
                    'reply_id' => $replyID,
                'comment_text' => $commentToUpdate->comment_text,
              'comment_status' => $commentStatus,
             );

              // Now we can do the insert. Success here will have the
              // total number of rows affected, which hopefully will be 1
              $success = $wpdb->insert( $table, $data);

              if($success) {
                  // Insert successful!
                  $response->success = true;
                  $response->new_id = $wpdb->insert_id;

              } else {
                  // Error inserting
                  $response->success = false;
                  $response->errmsg = 'Error occurred during insert';
              }

          } else {
              // User is not valid or not logged in
              $response->success = false;
              $response->errmsg = 'Invalid user - are you logged in?';
          }
      }
    } else {
      // We didn't get the post body
      $response->success = false;
      $response->errmsg = "Failed to get post body";
    }

    return($response);
}

    public function delete_comment (WP_REST_Request $request) {

    //------------------------------------------------------------------------
    // Function: delete_comment
    //
    // Deletes comments. Admin/moderator only function, users can 'trash' comments
    // using the update_comment method.
    //
    // URL: https://starcall.sylessae.com/wp-json/starcall/v1/comments/
    // Method: DELETE
    // Returns: JSON
    // Parms: JSON
    //------------------------------------------------------------------------

    global $wpdb;
    $response = new \stdClass();

    $params = $request->get_params();

    if (isset($params['request_id'])) {

        if (current_user_can('administrator') || current_user_can('moderator')) {
            // User is authorized
            $deletedRows = $wpdb->delete( 'wpsc_rq_comments', array( 'comment_id' => $parms['comment_id'] ) );
            if ($deletedRows != 1) {
                // We should only have affected one row
                $response->success = true;
            } else {
                // Something went wrong
                write_log('Attempting to delete ID: ' . $parms['request_id']);
                write_log('Error occurred, affected '.$deletedRows.' rows');
                $response->success = false;
                $response->errmsg = 'Error occurred during delete - see debug.log';
            }

        } else {
            //user is not authorized
            $response->success = false;
            $response->errmsg = 'User is not authorized';
        }

    } else {
        // No ID, send an error
        $response->success = false;
        $response->errmsg = 'No ID passed for delete';
    }

    return($response);
}

    public function get_gifts (WP_REST_Request $request) {

    //------------------------------------------------------------------------
    // Function: get_gifts
    //
    // TODO add description and other stuff here
    //
    // URL: https://starcall.sylessae.com/wp-json/starcall/v1/gifts/
    // Method: GET
    // Returns: JSON
    // Parms: JSON
    //------------------------------------------------------------------------

        // Globals
        global $wpdb;

        // Objects
        $gifts = new \stdClass();
        $userIsAdmin = (current_user_can('administrator') || current_user_can('moderator'));
        $currentUser = get_current_user_id();

        $params = $request->get_params();

        $gifts = make_gift_array($params,$currentUser,$userIsAdmin);

        return($gifts);
    }

    public function post_gift (WP_REST_Request $request) {

    //------------------------------------------------------------------------
    // Function: post_gift
    //
    // TODO add description and other stuff here
    //
    // URL: https://starcall.sylessae.com/wp-json/starcall/v1/gifts/
    // Method: POST
    // Returns: JSON
    // Parms: JSON
    //------------------------------------------------------------------------
        global $wpdb;

        $response = new \stdClass();

        // Get our JSON from the HTTP gift body
        $giftToUpdate = json_decode($request -> get_body());

        if($giftToUpdate !== null) {
            // Successfully got the post body
          if (isset($giftToUpdate->id)) {
              // We're updating an existing gift
              // First, let's get the gift we're changing
              $sql = "SELECT * FROM wpsc_rq_gifts " .
                     "WHERE id = " . $giftToUpdate->id;

              $existingGift = $wpdb->get_row($sql);

              if ($existingGift) {
                  // Found it. Make sure the user is allowed to do this
                  // Must be mod, admin, or the owner of the gift
                  $currentUser = get_current_user_id();

                  if (current_user_can('moderator') ||
                      current_user_can('administrator') ||
                      $currentUser == $existingGift->user) {
                      // Do the update

                      // We're using the wpdb object for database access,
                      // so we need to build a few arrays for it.

                      // Set the table we're going to update

                      $table = 'wpsc_rq_gifts';

                      // Build data array for fields to update

                      $data = array(
                        'request_id' => $giftToUpdate->request_id,
                              'user' => $giftToUpdate->user,
                              'path' => $giftToUpdate->path,
                           'caption' => $giftToUpdate->caption,
                            'status' => $giftToUpdate->status,
                         'edit_user' => $currentUser
                     );

                     // Build the where array
                     $where = array('id' => $giftToUpdate->id);

                     // Now we can do the update. Success should have the total
                     // rows affected.
                     $success = $wpdb->update( $table, $data, $where);

                     if ($success) {
                         // We did it, guys
                         $response->success = true;
                         $response->rows_updaded = $success;

                     } else {
                         // Update failed
                         write_log("ERROR: wpdb->update barfed.");
                         write_log("gift ID: " . $giftToUpdate->id);
                         write_log($data);

                         $response->success = false;
                         $response->errmsg = 'Error updating gift';
                     }

                  } else {
                      // User is not authorized, send error
                      $response->success = false;
                      $response->errmsg = 'User is not authorized';
                  }

              } else {
                  // Didn't find the gift, send error
                  $response->success = false;
                  $response->errmsg = 'gift with ID ' . $giftToUpdate->gift_id . ' not found';
              }
          } else {
              // We're submitting a new gift. Do we have a valid user?
              if (current_user_can('read')) {
                  // Insert gift
                  // We're using the wpdb object for database access,
                  // so we need to build a few arrays for it.

                  // Set the table
                  $table = 'wpsc_rq_gifts';
                  // Initial status
                  $giftStatus = 'submitted';

                  $currentUser = get_current_user_id();
                  if (isset($giftToUpdate->reply_id)) {
                      $replyID = $giftToUpdate->reply_id;
                  } else {
                      $replyID = 0;
                  }

                  // Build data array for fields to insert
                  $data = array(
                      'request_id' => $giftToUpdate->request_id,
                       'author_id' => $currentUser,
                        'reply_id' => $replyID,
                      'gift_text' => $giftToUpdate->gift_text,
                    'gift_status' => $giftStatus,
                 );

                  // Now we can do the insert. Success here will have the
                  // total number of rows affected, which hopefully will be 1
                  $success = $wpdb->insert( $table, $data);

                  if($success) {
                      // Insert successful!
                      $response->success = true;
                      $response->new_id = $wpdb->insert_id;

                  } else {
                      // Error inserting
                      $response->success = false;
                      $response->errmsg = 'Error occurred during insert';
                  }

              } else {
                  // User is not valid or not logged in
                  $response->success = false;
                  $response->errmsg = 'Invalid user - are you logged in?';
              }
          }
        } else {
          // We didn't get the post body
          $response->success = false;
          $response->errmsg = "Failed to get post body";
        }

        return($response);

    }

    public function delete_gift (WP_REST_Request $request) {

    //------------------------------------------------------------------------
    // Function: delete_gift
    //
    // TODO add description and other stuff here
    //
    // URL: https://starcall.sylessae.com/wp-json/starcall/v1/gifts/
    // Method: DELETE
    // Returns: JSON
    // Parms: JSON
    //------------------------------------------------------------------------

        global $wpdb;

        // TODO write this function :-)

        return("This isn't done yet!");
    }
}

$starcall_rest = new starcall_rest();
$starcall_rest->hook_rest_server();

//--------------------------------------------------------
// Custom roles for Starcall
//--------------------------------------------------------

function starcall_custom_roles () {

  add_role(
      'starcall_moderator',
      __( 'Moderator' ),
      array(
          'read' => true,
      )
  );
}

register_activation_hook( __FILE__, 'starcall_custom_roles' );

//----------------------------------------------------------------------------
// Enqueue scripts
//----------------------------------------------------------------------------

function starcall_enqueue_scripts () {

    wp_register_script('starcall_browser',
                        plugins_url('js/browser.js', __FILE__),
                        array('jquery','wp-api'),'1.0', true);

    wp_register_script('request_page',
                        plugins_url('js/request.js', __FILE__),
                        array('jquery','wp-api'),'1.0', true);

    wp_register_script('submit_request',
                        plugins_url('js/submitrequest.js', __FILE__),
                        array('jquery','wp-api'),'1.0', true);

    wp_register_script('starcall_comments',
                        plugins_url('js/comments.js', __FILE__),
                        array('jquery','wp-api'),'1.0', true);

    //Enqueue common scripts for all pages
    wp_enqueue_script('starcall_comments');

    // We only want the request script on the corresponding page
    if (is_page("request")) {
        wp_enqueue_script('request_page');
    }
    // Search request page script
    if (is_page("requests")) {
        wp_enqueue_script('starcall_browser');
    }
    // Submit request page
    if (is_page("submit")) {
        wp_enqueue_script('submit_request');
    }
}

add_action( 'wp_enqueue_scripts', 'starcall_enqueue_scripts' );

//----------------------------------------------------------------------------
// Handlers for form actions
//----------------------------------------------------------------------------

add_action( 'admin_post_submit_gift', 'submit_gift');

function submit_gift() {

    global $wpdb;

    // This is the ID for the Gallery Template post. Janky way of doing this,
    // but it allows us to change the settings without changing every single gallery.
    // TODO write a function to update all gallery post meta from this Template

    $templateGalleryId = 2365;

    // Note that this is all highly dependant on the FooGallery plugin

    // Does the gallery exist? Search for a post with the appropriate title
    $galleryTitle = "gift_gallery_" . $_POST['requestId'];

    // Build the query

    $query = "SELECT *
              FROM wpsc_posts
              WHERE post_title = '" . $galleryTitle . "' AND
              post_type = 'foogallery'";

    // Returns an array of objects
    $galleryRow = $wpdb->get_row( $query );

    if($galleryRow) {
        $galleryPostId = $galleryRow->ID;

    } else {
        // We need to create the post to make a new gallery. First make the post
        $postArr = array(
            'post_author' => 1,
             'post_title' => $galleryTitle,
           'post_content' => '',
            'post_status' => 'publish',
              'post_type' => 'foogallery',
         'comment_status' => 'closed',
            'ping_status' => 'closed'
        );

        $galleryPostId = wp_insert_post($postArr);
      //TODO add error handling if $galleryPostId == 0

      // Get the meta from the FooGallery template

      $galleryAttachments = get_post_meta($templateGalleryId, 'foogallery_attachments', true);
      $galleryTemplate = get_post_meta($templateGalleryId, 'foogallery_template', true);
      $gallerySettings = get_post_meta($templateGalleryId, '_foogallery_settings', true);
      $gallerySort = get_post_meta($templateGalleryId, 'foogallery_sort', true);

      // Do the meta

      add_post_meta($galleryPostId, 'foogallery_attachments', $galleryAttachments);
      add_post_meta($galleryPostId, 'foogallery_template', $galleryTemplate);
      add_post_meta($galleryPostId, '_foogallery_settings', $gallerySettings);
      add_post_meta($gallerypostId, 'foogallery_sort', $gallerySort);
    }

    // Take the file and attach it to the gallery post with wp_insert_attachment()
    // Also need to do the foogallery_attachments meta

  // These files need to be included as dependencies when on the front end.
  require_once( ABSPATH . 'wp-admin/includes/image.php' );
  require_once( ABSPATH . 'wp-admin/includes/file.php' );
  require_once( ABSPATH . 'wp-admin/includes/media.php' );

  // Let WordPress handle the upload.
  // Build the attachment post data - we need to have the title and author in the caption

  // Load user data into current_user
  $currentUser = wp_get_current_user();

  $giftTitle = 'Gift by ' . $currentUser->user_login;
  $giftCaption = $_POST['giftCaption'];

  $postArr = array(
       'post_title' => $giftTitle,
       'post_excerpt' => $giftCaption
  );

  $attachment_id = media_handle_upload( 'fileToUpload', $galleryPostId, $postArr );


  if ( is_wp_error( $attachment_id ) ) {
      echo 'It broke';
  } else {
      echo 'Success';
      // Now do the foogallery_attachments meta
      $galleryAttachments = get_post_meta($galleryPostId, 'foogallery_attachments', true);
      $galleryAttachments[] = $attachment_id;
      update_post_meta($galleryPostId, 'foogallery_attachments', $galleryAttachments);
  }

    $url = 'https://starcall.sylessae.com/request/?request_id=' . $_POST['requestId'];
    wp_redirect( $url );
    exit;

    return;
}

?>
