  <?php
  /**
   * Plugin Name: Starcall site-specific plugin
   * Plugin URI: https://github.com/iamsayed/read-me-later
   * Description: This plugin includes features unique to starcall.sylessae.com and is not intended for use anywhere else.
   * Version: 1.0.0
   * Author: Josh Hayford (KarnaughAtlas)
   * License: GPL3
   */


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

          // Request request routes

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


  			//TODO register route for submitting a fulfillment
  				// Must be special artist role?

  			//TODO register route for editing a fulfillment
  				// Must be mod or owner
      }

    // Register our REST Server
      public function hook_rest_server(){
          add_action( 'rest_api_init', array( $this, 'register_routes' ) );
      }

      public function get_requests( WP_REST_Request $request ){

      //------------------------------------------------------------------------------------------------------------------
      // Function: get_requests
      //
      // This function gets requests (no waaaaay). If called with no parameters it returns all SFW requests with
      // a status = 'approved' and nsfw = 0. Otherwise, you can give it parms in the URL and it'll filter requests.
      // If given a request_id it will ignore all other parms and return the request with that ID.
      //
      // URL: https://starcall.sylessae.com/wp-json/starcall/v1/requests/
      // Method: GET
      // Returns: Requests JSON object
      // Parms: id (int): request ID. If given this parameter, the function will return a single matching request
      //                  !! NOTE! If you give get_requests an ID it will ignore all other parms !!
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
          $sql = 'SELECT request_id,title,user_id,user_login,nsfw,fan_art,description,
                  create_date,edit_date,status
                  FROM wpsc_rq_requests
                  JOIN wp_users ON wpsc_rq_requests.user_id = wp_users.ID';

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

                  } else {
                      // Default is omit NSFW results
                      $filters[] =  'nsfw = 0';
                  }
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

          write_log($sql);

  		$requests = $wpdb->get_results($sql, ARRAY_A );

  		return($requests);
  	}

      public function post_request (WP_REST_Request $request) {

      //---------------------------------------------------------------------
      // Function: post_request
      //
      // This is the create/update function for requests. This function
      // consumes a JSON object in the body of the request.

      // !!NOTE!!

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
                     'social_media' => $requestToUpdate->social_media,
                      'description' => $requestToUpdate->description,
                         'how_hear' => $requestToUpdate->how_hear,
                           'status' => $requestToUpdate->status
                       );

                       // Build the where array
                       $where = array('request_id' => $requestToUpdate->request_id);

                       // Now we can do the update. Success should have the total
                       // rows affected.
                       $success = $wpdb->update( $table, $data, $where);

                       if ($success) {
                           // We did it, guys
                           $response->sucess = true;
                           $response->rows_updaded = $success;
                           return($response);

                       } else {
                           // Update failed
                           write_log("ERROR: wpdb->update barfed.");
                           write_log("Request ID: " . $requestToUpdate->request_id);
                           write_log($data);

                           $response->success = false;
                           $response->errmsg = 'Error updating request';

                           return($response);
                       }

                    } else {
                        // User is not authorized, send error
                        $response->success = false;
                        $response->errmsg = 'User is not authorized';
                        return($response);
                    }

                } else {
                    // Didn't find the request, send error
                    $response->success = false;
                    $response->errmsg = 'Request with ID ' . $requestToUpdate->request_id . ' not found';
                    return($response);
                }
            } else {
                // We're submitting a new request. Do we have a valid user?
                if (current_user_can('read')) {
                    // Insert request
                    // We're using the wpdb object for database access,
                    // so we need to build a few arrays for it.

                    // Set the table
                    $table = 'wpsc_rq_requests';

                    // Build data array for fields to insert
                    $data = array(
                        'title' => $requestToUpdate->title,
                      'user_id' => $requestToUpdate->user_id,
                         'nsfw' => $requestToUpdate->nsfw,
                      'fan_art' => $requestToUpdate->fan_art,
                 'social_media' => $requestToUpdate->social_media,
                  'description' => $requestToUpdate->description,
                     'how_hear' => $requestToUpdate->how_hear,
                       'status' => $requestToUpdate->status
                   );


                    // Now we can do the insert. Success here will have the
                    // total number of rows affected, which hopefully will be 1
                    $success = $wpdb->insert( $table, $data);

                    if($success) {
                        // Insert successful!
                        $reponse->success = true;
                        $response->new_id = $wpdb->insert_id;

                        return($response);

                    } else {
                        // Error inserting
                        $response->success = false;
                        $response->errmsg = 'Error occurred during insert';

                        return($response);
                    }

                } else {
                    // User is not valid or not logged in
                    $response->success = false;
                    $response->errmsg = 'Invalid user - are you logged in?';
                    return($response);
                }
            }
          } else {
            // We didn't get the post body
            $response->success = false;
            $response->errmsg = "Failed to get post body";
            return($response);
          }
      }

      public function delete_request (WP_REST_Request $request) {

      //--------------------------------------------------------------------------------------------------------------------
      // Function: delete_request
      //
      // This function deletes reques. It takes only one parm, the ID of the request to delete. Only the owner of the request
      // or a moderator may delete requests.
      //
      // URL: https://starcall.sylessae.com/wp-json/starcall/v1/requests/
      // Method: DELETE
      // Returns: JSON with true/false and an error if applicable
      // Parms: ID
      //--------------------------------------------------------------------------------------------------------------------

          global $wpdb;

          // TODO write this function :-)

          // Do we have an ID?
              // Is this user allowed to delete this request (mod or request owner) ?
                  // Delete request
                  // Return true or descriptive error
              // User can not delete
                  // Return descriptive error
          // We don't have an ID
              // Return descriptive error

          return("This isn't done yet!");

      }

      public function get_comments (WP_REST_Request $request) {

      //--------------------------------------------------------------------------------------------------------------------
      // Function: get_comments
      //
      // TODO add description and other stuff here
      //
      // URL: https://starcall.sylessae.com/wp-json/starcall/v1/comments/
      // Method: GET
      // Returns: JSON
      // Parms: JSON
      //--------------------------------------------------------------------------------------------------------------------

          global $wpdb;

          // TODO write this function :-)

          return("This isn't done yet!");

      }

      public function post_comment (WP_REST_Request $request) {

      //--------------------------------------------------------------------------------------------------------------------
      // Function: post_comment
      //
      // TODO add description and other stuff here
      //
      // URL: https://starcall.sylessae.com/wp-json/starcall/v1/comments/
      // Method: POST
      // Returns: JSON
      // Parms: JSON
      //--------------------------------------------------------------------------------------------------------------------

          global $wpdb;

          // TODO write this function :-)

          return("This isn't done yet!");

      }

      public function delete_comment (WP_REST_Request $request) {

      //--------------------------------------------------------------------------------------------------------------------
      // Function: delete_comment
      //
      // TODO add description and other stuff here
      //
      // URL: https://starcall.sylessae.com/wp-json/starcall/v1/comments/
      // Method: DELETE
      // Returns: JSON
      // Parms: JSON
      //--------------------------------------------------------------------------------------------------------------------

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

  //------------------------------------------------------------------------------
  // Enqueue scripts
  //------------------------------------------------------------------------------

  function starcall_enqueue_scripts () {

      wp_register_script('starcall_browser',
                          plugins_url('js/browser.js', __FILE__),
                          array('jquery','wp-api'),'1.0', true);

      wp_register_script('request_page',
                          plugins_url('js/request.js', __FILE__),
                          array('jquery','wp-api'),'1.0', true);

      // We only want the request script on the corresponding page
      if (is_page("request")) {
          wp_enqueue_script('request_page');
      }
      // Search request page script
      if (is_page("requests")) {
          wp_enqueue_script('starcall_browser');
      }


  }

  add_action( 'wp_enqueue_scripts', 'starcall_enqueue_scripts' );

  ?>
