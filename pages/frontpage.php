<?php
/**
 * Template name: Front page
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package starcall
 */

get_header();

?>
<body>
    <div id="areYouLost">
        <h4>LOST? CLICK HERE TO GET STARTED!</h4>
    </div>

    <div id="getStartedBox">
        <div  id="getStartedChecklist">
            <ul>
                <li>Have you <a href="https://sylessae.com/register">signed up</a>?</li>
                <li>Have you read our <a href="https://starcall.sylessae.com/rules/">rules</a>, <a href="https://starcall.sylessae.com/starcall-faq/">FAQ</a> and <a href="https://starcall.sylessae.com/tos/">terms of service</a>?</li>
                <li>Have you updated your <a href="http://www.sylessae.com/user">bio</a>?</li>
                <li>Have you introduced yourself on the <a href="https://starcall.sylessae.com/forums/">forums</a>?</li>
                <li>Artists: Have you found a <a href="https://starcall.sylessae.com/requests/">request</a> that interests you?</li>
                <li>Have you submitted a <a href="https://starcall.sylessae.com/submit/">request</a>?</li>
            </ul>
        </div>
    </div>

    <div class="columnContainer">
        <section id="leftContainer">
            <head><title>Project Starcall - CREATE | GIVE | INSPIRE</title></head>

            <div class="frontPageHeader">
              <div class="frontPageTitle"> LATEST GIFTS </div>
            </div>
            <div id="latestGifts">
                <?php echo do_shortcode('[foogallery id="2487"]'); ?>
            </div>

            <div class="frontPageHeader">
                <div class="frontPageTitle"> LATEST REQUESTS </div>
            </div>
            <div id="latestRequests">
                <?php get_latest_gifts(); ?>
                <a href="https://starcall.sylessae.com/requests"><button id="frontPageBrowseRequestsButton">Browse requests</button></a>
                <a href="https://starcall.sylessae.com/submit"><button id="frontPageSubmitRequestsButton">Submit a request</button></a>

            </div>
        </section>

        <section id="rightContainer">
            <h3 id="rightContainerHeader">NEWS</h3>
            <div id="postArchive">
                <?php get_front_page_posts(); ?>
            </div>
            <h3 id="rightContainerHeader">TWITTER</h3>
            <div id="twitterFeed">
                <?php
                $shortcode = '[statictweets skin="default" resource="usertimeline" user="projectStarcall" count="13" retweets="on" show="time,media"/]';
                echo(do_shortcode($shortcode));
                ?>
            </div>
        </section>
</div>
</body>
<?php

get_footer();

function get_latest_gifts() {
    global $wpdb;
    $requests = new \stdClass();

    $sql = 'SELECT request_id,title,user_id,user_login,nsfw,fan_art,
                   reference_links, description, create_date, edit_date,
                   status
            FROM wpsc_rq_requests
            LEFT JOIN wp_users ON wpsc_rq_requests.user_id = wp_users.ID
            WHERE status = "approved"
            ORDER BY create_date desc
            LIMIT 10';

    $requests = $wpdb->get_results($sql);

?>

    <table id="requesttable">
    <thead>
        <tr>
            <th class="requester">Requester</th>
            <th class="title">Title</th>
            <th class="description">Description</th>
            <th class="br_create_date">Date</th>
        </tr>
    </thead>
    <tbody>

      <?php

    echo('<tr><td colspan = "4">');
    foreach ($requests as $request) {

        echo("<tr class='requestrow'><td class='requester'>" . $request->user_login . "</td>");
        echo("<td class='title'>");
                 "<td class='title'>";
        if ($request->nsfw == true) {
            echo("<span class='nsfwtag'>[18+] </span>");
        }

        echo(substr($request->title, 0, 30) . "</td>");
        echo("<td class='description'>" . substr($request->description,0,60) . "...</td>");
        echo("<td class='br_create_date'>" . $request->create_date . "</td>");
        echo("<td class='request_id' style='display: none'>" . $request->request_id . "</td></tr>");
    }
    echo("</table>");
}

function get_front_page_posts(){
    ?>
<ul>
<?php
	$recent_posts = wp_get_recent_posts();
	foreach( $recent_posts as $recent ){
		echo '<li><a href="' . get_permalink($recent["ID"]) . '">' .   $recent["post_title"].'</a> </li> ';
	}
	wp_reset_query();
?>
</ul>
<?php

}
