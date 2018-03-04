<?php
/**
 * Template name: Request browser
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

get_header(); ?>

<section class="rq_container">
<head><title>Search requests</title></head>

<div class=request_table>
<input id = "requestdesc" type="text"></input><button id = "searchbutton"> Search </button>
<input type="checkbox" id="includensfw" value="1">
<label for="includensfw">Include NSFW</label>

<table id="requesttable">
  <thead>
    <tr>
      <th>Requester</th>
      <th>Title</th>
      <th>Description</th>
      <th>Date</th>
    </tr>
  </thead>
  <tbody>

  </tbody>
</table>

<div id="pagination"></div>

</div>

</section>
<?php
get_footer();
