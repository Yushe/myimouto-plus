<div class="center_box" style="text-align:center;">
  <div class="center_box">
    <h5 style="">Update to version <?= $this->newVersion ?></h5>
    <br />
  </div>
  <form action="" method="post" name="install_form">
    <input type="hidden" name="update" value="1" />
    <p style="margin:50px 0px;"><button style="font-size:2em;">Update</button></p>
    <p style="margin:50px 0px;">(To show the installation form, the public/data folder must not exist)</p>
  </form>
  <p style="text-align:left;"></p>
</div>
