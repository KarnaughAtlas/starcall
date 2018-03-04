<?php
/**
 * Template name: Submit request
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
<form id="requestform">
  <h1>Submit a Request</h1>

  <br />
  <div class="submitTitleDiv">
    <strong>Title: </strong>
    <textarea id="submitTitle" required placeholder="The name of your character or title of the piece"></textarea>
  </div>

  <br />

  <div class="submitOrigFanDiv">
    <strong>Is this an original piece or fan art?</strong><br>
    <input type="radio" id="submitOrigFan" name="origfan" value="original" required> Original   <input type="radio" id="submitOrigFan" name="origfan" value="fanart" required> Fan art<br>
  </div>

  <br />

  <div class="submitDescDiv">
    <strong>Description</strong>
    <textarea id="submitDesc" class="submitdescription" placeholder="Describe your character. Be as detailed as possible!" required></textarea>
  </div>

  <br />

  <div class="submitSocialDiv">
    <strong>To help Starcall grow, we ask that all requesters share the project on social media. Please provide a DIRECT LINK to your social media post sharing Starcall</strong>
    <textarea id="submitSocial" class="submitsocial" required></textarea>
  </div>

  <br />

  <div class="submitLinksDiv">
    <strong>Links to external references (optional)</strong>
    <textarea id="submitLinks" class="submitlinks" placeholder="Please credit the original artists for any submitted reference materials."></textarea>
  </div>

  <br />

  <div class="submitHowHear">
    <strong>How did you hear about Starcall?</strong>
    <select id="submitHowHear" class="submithowhear" required>
      <option disabled selected value> -- select an option -- </option>
      <option value="deviantart">Deviantart</option>
      <option value="twitter">Twitter</option>
      <option value="instagram">Instagram</option>
      <option value="facebook">Facebook</option>
      <option value="patreon">Patreon</option>
      <option value="tumblr">Tumblr</option>
      <option value="other">Other</option>
    </select>
  </div>

  <br />

  <div class="submitNSFW">
    Requests containing nudity, violent images, or other adult situations must be marked as 18+. Requests that contain or explicitly ask for this content will be removed if they are not so marked.

  <strong>Requests depicting graphic violence or sex are not permitted.</strong> <br />

  Mark request as 18+?
  <input type="radio" id="submitNSFW" name="nsfw" value="no" required> No <input type="radio" id="submitNSFW" name="nsfw" value="yes" required>Yes<br>
 </div>

  <br />

  <div class="submitRequestDiv">
    <input type="submit" id="submitRequestButton" class="submitrequestbutton" value="Submit request">
  </div>
</form>

<?php
get_footer();
