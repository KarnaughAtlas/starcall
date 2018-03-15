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

  <div class="submitLinksDiv">
    <strong>Links to external references (optional)</strong>
    <textarea id="submitLinks" class="submitlinks" placeholder="Please credit the original artists for any submitted reference materials."></textarea>
  </div>

  <br />

  <div class="submitNSFW">
    Requests containing nudity, violent images, or other adult situations must be marked as 18+. Requests that contain or explicitly ask for this content will be removed if they are not so marked. <strong>Requests depicting graphic violence or sex are not permitted. </strong> <br />
    <br />
  <strong>Mark request as 18+?</strong><br />
  <input type="radio" id="submitNSFW" name="nsfw" value="yes" required>Yes<input type="radio" id="submitNSFW" name="nsfw" value="no" required> No <br>
 </div>

  <br />

  <div class="submitSocialDiv">
    <strong>Please share Project Starcall on the social platform of your choice. Include #ProjectStarcall, a link to the site, and a short message about what Starcall is to you. When you're done, copy the direct link from your post and paste it here.</strong><br />
    <a class="whyShare" href="">Why do I have to do this?</a>

    <textarea id="submitSocial" class="submitsocial" required></textarea>
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

  <div class="submitRequestAgree">
      <input type="checkbox" id="agreeTOS" name="agreeTOS" value="yes" required>
      By checking this box you agree to the <a href="https://starcall.sylessae.com/rules" target="_blank">rules</a> and <a href="https://starcall.sylessae.com/tos" target="_blank">Terms of Service.</a><br /><br />
  <div class="submitRequestDiv">
    <input type="submit" id="submitRequestButton" class="submitrequestbutton" value="Submit request">
  </div>
</form>

<!-- Submit gift window-->
<div id="whyShareWindow" class="modalWindow">
  <div class="modal-content">
    <span class="windowClose">&times;</span>
    <h3>Why do I have to share the project?</h3>
    <span class="explainWhyShare"> Starcall is still in its infancy, and if we want it to succeed it is critical that as many people see it as possible! By sharing the message of the project with your followers, you can help attract more talented artists and more requesters with great ideas to share with us. Thank you! </span>
    <br />
    <br />
  </div>
</div>


<?php
get_footer();
