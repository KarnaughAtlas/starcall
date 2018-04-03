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




// Get the footer
get_footer();
?>
