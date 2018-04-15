<?php
/**
 * Template name: Gift page
 * @package starcall
 */

// Get the header
get_header();

// Make the page

// Get the post, and ID from URL
$giftId = $_GET['gift_id'];
$giftPost = get_post($giftId);

// Image header

?>

<div class="gift-header-area">
        <h3><?php echo($giftPost->post_title);?></h3>
</div>

<?php

// Display image
echo("<img class='gift-full-size' src='" . $giftPost->guid . "'>");

// get comments from db
$comments = new \stdClass();
$params['gift_id'] = $giftId;
$comments = getGiftComments($params);
makeComments($comments);

// Do the comment reply area
echo("<div id='newcommentarea'>");
echo("<br /><span class=submit_reply><strong>Submit a comment</strong></span>");
echo("<br /><textarea id='newcomment'></textarea>");
echo("<br /><button id='submitcomment'>Submit comment</button>");

function getGiftComments($params) {

    $userIsAdmin = (in_array('starcall_moderator',wp_get_current_user()->roles) || in_array('administrator',wp_get_current_user()->roles));
    $currentUser = get_current_user_id();

    $sql = 'SELECT comment_id,request_id,author_id, u1.user_login AS author,
                   reply_id,comment_text, create_date, edit_user,
                   u2.user_login AS editing_user, edit_date, comment_status
            FROM wpsc_rq_comments AS comments
            JOIN wp_users AS u1 ON comments.author_id = u1.ID
            LEFT JOIN wp_users AS u2 ON comments.edit_user = u2.ID';

    if($params) {
        // Build the WHERE clause
        if(isset($params['gift_id'])) {
            $filters[] = 'author_id = ' . $params['user_id'];
        }

        if(isset($params['reply_id'])) {
            $filters[] = 'reply_id = ' . $params['reply_id'];
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
    $comments = $wpdb->get_results($sql);

    if ( count($comments) > 0) {

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

            $containerID = 'rq_container_' . $comment->comment_id;
            $divID = 'rq_comment_' . $comment->comment_id;

            // Put the comment on the page
            echo("<div id='" . $containerID . "' class = 'request_container'>");
            echo("<div id='" . $divID . "' class='request_comment'>");
            echo("<strong>" . $comments->author . "</strong>");
            echo(" | <span class='create_date'>".$comments->create_date ." </span> | ");

            echo("<div class='comment_controls'><span class='comment_reply'>reply</span>");

            // If user is authorized, show the edit buttons
            if ($comments->user_authorized) {
                echo("<span class='comment_edit'>edit</span><span class='comment_delete'>delete</span>");
            }

            echo("</div><br />");
            echo("<span class='comment_text'>" . escapeHtml($comment->comment_text) . "</span><br />");


            if ($comment->edit_date && $comment->edit_date != $comment->create_date) {
                echo("<br /><span class='edit_text'> Edited by " . $comment->editing_user .
                " on " . $comment->edit_date . "</span>");
            }

            // Include hidden ID span for easily snagging the comment ID

            echo("<span class = 'commentID' style='display:none'>" . $comment->comment_id . "</span>");

            // Close commment controls div
            echo("</div>");


            // Recurse to get the replies to this comment
            $replyParams['reply_id'] = $comment->comment_id;
            $comment->replies = getGiftComments($replyParams);

            // Close comment div
            echo("</div>");
        }
    } else {
        // no comments
        echo ("<h4>Be the first to leave a comment on this gift!</h4>");
    }
}





// Get the footer
get_footer();
?>
