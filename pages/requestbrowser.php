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

<table id="requesttable">
  <thead>
    <tr id="requesttablefilters">
        <th class="filterrow"></th>
        <th class="filterrow"><input id = "requesttitle" type="text" placeholder="Search by Title"></input></th>
        <th class="filterrow"><input id = "requestdesc" type="text" placeholder="Search by Description"></input></th>
        <th class="filterrow"><button id = "searchbutton"> Search </button><br /><input type="checkbox" id="includensfw" value="1"><label for="includensfw">Include NSFW</label></th>
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

<!-- Loading window-->
<div id="loadingWindow" class="loadingWindow">
  <div class="modal-content" id="loadingWindowContent">
    <div class="loadingStatusMessage">
        <img class="loadingSpinner" src="<?php echo(plugins_url('starcall/assets/loading.gif')) ?>" width="40px" height="40px" />
        <span id="loadingText"> Loading, please wait... </span>
    </div>
  </div>
</div>

<?php
get_footer();
