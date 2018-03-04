<?php

/**
 * Template name: Request page
 *
 * This page displays requests. Should only be used on the request page.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package starcall
 */
global $request;

get_header();

$request = get_request();

// Should we display admin functions?
$userIsAdmin = (current_user_can('administrator') || current_user_can('moderator'));
if ($userIsAdmin) {
    doAdminStuff($request);
} else if (get_current_user_id() == $request->user_id) {
    // This is the requester; show them status info about their request
    doRequesterStuff($request);
}
?>

  <div id="requestarea"></div>
  <div id="giftarea">
  <h3>Gifts</h3>

<?php
  	// Slap the gallery here if it's available; otherwise, show a message about how there are no gifts

  	// Does the gallery exist? Search for a post with the appropriate title
 	$galleryTitle = "gift_gallery_" . $_GET["request_id"];

        // Build the query

        $query = "SELECT *
                  FROM wpsc_posts
                  WHERE post_title = '" . $galleryTitle . "' AND
                  post_type = 'foogallery'";

        // Returns an object
        $galleryRow = $wpdb->get_row( $query );

	if($galleryRow) {
            $galleryPostId = $galleryRow->ID;
            $shortcode = '[foogallery id="' . $galleryPostId . '"]';
            echo(do_shortcode($shortcode));
        } else {
            echo("No gifts have been submitted for this request.");
        }

    if(current_user_can('read')) {
        // User must be logged in to submit a gift

?>

      	<br />
      	<button id="showNewGiftWindowButton">Submit a gift</button>
        <br />
        <br />

<?php

} else {

?>

    <br />
    <p> Log in to submit a gift. </p>
    <br />
    <br />

<?php

}

 ?>

</div>

<div id="commentarea"></div>
<div id="newcommentarea"></div>

<?php

get_footer();

function doAdminStuff ($request) {

    if ($request->status == 'submitted') {

?>

<div id="admintoolbox">
    <div id="admintools">
        <span id="admintoolslabel"><strong>Admin tools</strong></span>
        <br />
        <section id="adminbuttons">
            <button id="adminApproveRequestButton">Approve request</button>
            <button id="adminDenyRequestButton">Deny request</button>
        </section>
    </div>
    <div id="adminrequeststatus">
        <h3>---- Request awaiting approval ----</h3>
        <strong> This needs to be approved before it is visible</strong>
    </div>
</div>
<br />
<br />

<?php

    } else if ($request->status == 'approved') {

?>

        <div id="admintoolbox">
            <div id="admintools">
                <span id="admintoolslabel"><strong>Admin tools</strong></span>
                <br />
                <section id="adminbuttons">
                    <button id="adminChangeStatusButton">Change status</button>
                    <button id="adminContactButton">Contact requester</button>
                </section>
            </div>
            <div id="adminrequeststatus">
                <h3>---- Request status: approved ----</h3>
                <strong> This request is visible to all users </strong>
            </div>
        </div>
        <br />
        <br />

<?php

    } else if ($request->status == 'denied') {

?>

    <div id="admintoolbox">
        <div id="admintools">
            <span id="admintoolslabel"><strong>Admin tools</strong></span>
            <br />
            <section id="adminbuttons">
                <button id="adminChangeStatusButton">Change status</button>
                <button id="adminContactButton">Contact requester</button>
            </section>
        </div>
        <div id="adminrequeststatus">
            <h3>---- Request status: denied ----</h3>
            <strong> Only admins and the requester can see this. </strong>
        </div>
    </div>
    <br />
    <br />

<?php

    } else if ($request->status == 'deleted') {

?>

        <div id="admintools">
            <span id="admintoolslabel"><strong>Admin tools</strong></span>
            <section id="adminbuttons">
                <button id="adminChangeStatusButton">Change status</button>
                <button id="adminDeleteRequestButton">Permanently delete</button>
            </section>
            <h3>---- Request status: deleted ----</h3>
            <strong> This request has been deleted and is only visible to administrators. </strong>
        </div>
        <br />
        <br />

<?php

    }
}

function doRequesterStuff($request) {
    // Show the requester if their request is awaiting approval or denied
    if ($request->status == 'submitted') {

?>

    <div id="requestStatusBox">
        <div id="userRequestStatus">
            <h3>---- Your request is awaiting approval ----</h3>
            <strong> The admin team has been notified and will be reviewing your request shortly.</strong>
        </div>
    </div>
    <br />
    <br />

<?php

} else if ($request->status == 'denied') {

?>

    <div id="requestStatusBox">
        <div id="userRequestStatus">
            <h3>---- Your request has been denied. ----</h3>
<?php
    if ($request->status_reason == 'socialmedia') {
        // The user did not share on social media; let them update the link here
?>
        <p>You did not share the request on social media, or did not provide a direct link to your post. Please update your social media link below: </p>
        <input id='fixSocialMedia' type='text'><?php echo($request->social_media) ?></text>
        <br /><br />
        <button id='submitFixSocialMedia'>Submit</button>

<?php

    } else if ($request->status_reason == 'incomplete') {
        // The request isn't complete; tell the user to fix it

    } else if ($request->status_reason == 'inappropriate') {
        // The request contains material that isn't allowed; tell the user to fix it

    } else {
        // Somehow we have a denied request without a reason; tell the user to contact the mods

    }
?>
        </div>
    </div>
    <br />
    <br />

<?php

    }
}

function get_request() {

    global $wpdb;

    $requestID = $_GET['request_id'];
    $query = 'SELECT *
              FROM wpsc_rq_requests
              WHERE request_id = ' . $requestID;
    $request = $wpdb->get_row($query);
    return($request);
}

?>

<!-- Deny request window -->
<div id="adminDenyRequestWindow" class="modalWindow">
  <div class="modal-content">
    <span class="windowClose">&times;</span>
    <section id="adminWindowSelectReason">
        <p><strong>Select reason for denial:</strong></p>
        <br />
        <select id="adminSelectDenyReason" autocomplete="off">
            <option disabled selected value=""> -- Select a reason -- </option>
            <option value="incomplete">Incomplete request</option>
            <option value="socialmedia">Did not share on social media</option>
            <option value="inappropriate">Inappropriate content</option>
            <option value="spam">Spam</option>
        </select>
        <br /><br />
        <button id="adminWindowDenyRequestButton"> Deny request </button>
    </section>
    <section id="adminWindowText">
        <p id="adminWindowDenyExplanation">Select a valid reason for denial</p>
    </section>
  </div>
</div>

<!-- Change status window -->
<div id="adminChangeStatusWindow" class="modalWindow">
  <div class="modal-content">
    <span class="windowClose">&times;</span>
    <section id="adminWindowSelectReason">
        <p><strong>Change request status:</strong></p>
        <br />
        <select id="adminSelectStatus" autocomplete="off">
            <option disabled selected value=""> -- Select a status -- </option>
            <option value="approved">Approved</option>
            <option value="denied">Denied</option>
            <option value="deleted">Deleted</option>
        </select>
        <br /><br />
        <button id="adminWindowChangeStatusButton"> Update status </button>
    </section>
    <section id="adminWindowText">
        <p id="adminWindowStatusExplanation">Select a status</p>
    </section>
  </div>
</div>

<!-- Submit gift window-->
<div id="submitGiftWindow" class="modalWindow">
  <div class="modal-content">
    <span class="windowClose">&times;</span>
    <h4>Give a gift</h4>
    <form id="giftForm" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" enctype="multipart/form-data">
        <strong>Caption:</strong><br />
        <textarea name="giftCaption" id="giftCaption" form="giftForm" placeholder="This is visible to everyone!"></textarea>
        <br />
        <br />
        <strong>Gift note:</strong><br />
        <textarea name="giftNode" id="giftNote" form="giftForm" placeholder="Only the requester will receive this!"></textarea>
        <br />
        <br />
        <strong>Select image to upload:</strong>
        <input type="file" name="fileToUpload" id="fileToUpload"></input>
        <br />
        <br />
        <input type="submit" id="submitGiftButton" value="Submit gift" name="submitGift"></input>
        <input type="hidden" name="action" value="submit_gift"></input>
        <input type="hidden" name="requestId" value=""></input>
    </form>
    <div id="submitGiftLoading" style="display: none">
        <img class="loading" src="http://thinkfuture.com/wp-content/uploads/2013/10/loading_spinner.gif" width="60px" height="60px" />
        <p> Loading, please wait... </p>
    </div>
  </div>
</div>
