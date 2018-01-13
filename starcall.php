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
        
        // Register route for read method - anyone can read, hence no permissions callback
        register_rest_route( $namespace, '/requests', array(
            array(
                'methods'         => WP_REST_Server::READABLE,
                'callback'        => array( $this, 'get_requests' ),
            )
			
			//TODO register route for create request method 
				// Must be a valid logged-in user to submit
        	
			//TODO register route for edit request method
				// Must be an admin or the request submitter to edit
        
			//TODO register route for delete request
				// Only allow admins or request submitter to delete
        
        		//TODO register route for getting comments
        			// Anyone can get comments
        
			//TODO register route for adding comment
				// Must be logged in to add a comment
			
			//TODO register route for editing coment 
				// Must be admin or comment owner
        
			//TODO register route for deleting comment
				// Must be admin or comment owner
        	
			//TODO register route for submitting a fulfillment
				// Must be special artist role?
        	
			//TODO register route for editing a fulfillment
				// Must be mod or owner
        )  );
    }
 
  // Register our REST Server
    public function hook_rest_server(){
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }
 
 
    //------------------------------------------------------------------------------------------------------------------
    // Function: get_requests
    //
    // This function gets requests (no waaaaay). If called with no parameters it returns all SFW requests with
    // a status = 'approved' and nsfw = 0. Otherwise, you can give it parms in the URL and it'll filter requests.
    // If given a request_id it will ignore all other parms and return the request with that ID.
    //
	// URL: https://starcall.sylessae.com/wp-json/starcall/v1/requests/
	// Method: GET
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
	
    public function get_requests( WP_REST_Request $request ){
       
        global $wpdb;

	$sql = 'SELECT *
        FROM wpsc_rq_requests';
        
        // Determine if we need to add WHERE clauses to our query
		$params = $request->get_params();
        
		if ($params['request_id']) { // fetching a specific request, not user-selectable. Ignore other parms.
    		$filters[] =  ' WHERE request_id = ' . $params['id'];
		
		} elseif ($params) { // we have filters
		
			// $filters is an array we'll use to build the dynamic WHERE/AND clause. Make sure we don't have junk
			unset($filters);
			
			//-------------------------------------------------------------------------
			// Fan art flag --
			//	yes = fan art only 
			// 	no = original characters only
			//	all = include all (default behavior)
			//-------------------------------------------------------------------------
				
			if ($params['fan_art']) { 
			
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
			
			if ($params['desc']) {
			
				$filters[] =  'description LIKE "%' . trim($params['desc']) . '%"';
			}
		
			//-------------------------------------------------
			// NSFW flag --
			// 	yes = include NSFW and non-NSFW results 
			//	only = only show NSFW results
			//	no = omit NSFW (default behavior)
			//-------------------------------------------------
					
			if ($params['nsfw'] == "yes") {		
				// Include NSFW and regular results, so don't add anything to the query
		
			} elseif ($params['nsfw'] == "only") {
				// Include only NSFW results
				$filters[] =  'nsfw = 1';
			
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
			
			//TODO allow filtering on artist, submitter <- search by name?
			//TODO include date filters? 
			
			if ($filters) {
				$sql .= ' WHERE ' . implode(' AND ', $filters);
			}
			
		} else { 
			// No params, but by default we still need to filter out NSFW results and include only approved requests
			$sql .=  " WHERE nsfw = 0 AND status = 'approved'"; 
		}

		write_log( "JOSHDEBUG: " . json_encode($params) );		
		write_log( "JOSHDEBUG: " . $sql);
		$requests = $wpdb->get_results($sql, ARRAY_A );
		
		return($requests);		
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

?>
