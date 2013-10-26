<div style="width: 40em; margin: 5em auto;">
  <div class="section">
    <h4>Terms of Service</h4>
    <p>By accessing the "<?= CONFIG()->app_name ?>" website ("Site") you agree to the following terms of service. If you do not agree to these terms, then please do not access the Site.</p>

    <ul>
      <li>The Site reserves the right to change these terms at any time.</li>
      <li>The Site is presented to you AS IS, without any warranty, express or implied. You will not hold the Site or its staff members liable for damages caused by the use of the site.</li>
      <li>The Site reserves the right to delete or modify your account, or any content you have posted to the site.</li>
      <li>You have read the <?= $this->linkTo("tagging guidelines", "help#tags") ?>.</li>
    </ul>

    <div class="section">
      <h6>Prohibited Content</h6>
      <p>In addition, you may not use the Site to upload any of the following:</p>
      <ul>
        <li>Watermarked: Any image where a person who is not the original copyright owner has placed a watermark on the image.</li>
        <li>Poorly compressed: Any image where compression artifacts are easily visible.</li>
      </ul>
    </div>
  </div>

  <div class="section">
    <h4>Copyright Infringement</h4>

    <p>If you believe a post infringes upon your copyright, please send an email to the <?= $this->mailTo(CONFIG()->admin_contact, "webmaster", ['encode' => "hex"]) ?> with the following pieces of information:</p>
    <p>Keep in mind we only respect requests from original artists or copyright owners, not derivative works.</p>
    <ul>
      <li>The URL of the infringing post.</li>
      <li>Proof that you own the copyright.</li>
      <li>An email address that will be provided to the person who uploaded the infringing post to facilitate communication.</li>
    </ul>
  </div>

  <div class="section">
    <h4>Privacy Policy</h4>

    <p>The Site will not disclose the IP address or email address of any user except to the staff.</p>
    <p>The Site is allowed to make public everything else, including but not limited to: uploaded posts, favorited posts, comments, forum posts, wiki edits, and note edits.</p>
  </div>

  <div>
    <h4>Agreement</h4>
    <p>By clicking on the "I Agree" link, you have read all the terms and have agreed to them.</p>
    <p><?= $this->linkTo("I Agree", $this->params()->url ?: "/", ['onclick' => "jQuery.cookie('tos', '1');"]) ?> | <?= $this->linkTo("Cancel", "/") ?></p>
  </div>
</div>

