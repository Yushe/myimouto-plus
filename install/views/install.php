<?php
if (function_exists('imagecreatetruecolor')) {
    $gd2       = "Enabled";
    $gd2_class = "good";
} else {
    $gd2       = "Not enabled";
    $gd2_class = "bad important";
}

if (class_exists('Imagick', false)) {
    $imagick       = "Enabled";
    $imagick_class = "good";
} else {
    $imagick       = "Not enabled";
    $imagick_class = "bad";
}


if (function_exists('curl_init')) {
    $curl       = "Enabled";
    $curl_class = "good";
} else {
    $curl       = "Not enabled";
    $curl_class = "bad";
}

if (class_exists('PDO', false)) {
    $pdo       = "Enabled";
    $pdo_class = "good";
} else {
    $pdo       = "Not enabled";
    $pdo_class = "bad important";
}
?>
<style type="text/css">
.center_box{
  width:550px;
  margin-left:auto;
  margin-right:auto;
  margin-bottom:10px;
}

.good{color:#0f0;}
.okay{color:orange;}
.bad{color:red;}
</style>

<div class="center_box"><h5>PHP.ini directives</h5></div>
<table class="form" style="margin-left:auto; margin-right:auto; width:550px; text-align:center;"><tbody>

  <tr>
    <th style="text-align:center; background-color:#555;">Name</th>
    <th style="text-align:center; background-color:#555;">Current value</th>
    <th style="text-align:center; background-color:#555;">Recommended min. value</th>
  </tr>
  
  <tr>
    <th>memory_limit</th>
    <td><?= ini_get('memory_limit') ?></td>
    <td>128M+</td>
  </tr>
  
  <tr>
    <th>post_max_size</th>
    <td><?= ini_get('post_max_size') ?></td>
    <td>6M</td>
  </tr>
  
  <tr>
    <th>upload_max_filesize</th>
    <td><?= ini_get('upload_max_filesize') ?></td>
    <td>5M</td>
  </tr>
  
  <tr>
    <th>GD2</th>
    <td class="<?= $gd2_class ?>"><?= $gd2 ?></td>
    <td>Must be enabled</td>
  </tr>
  
  <tr>
    <th>PDO</th>
    <td class="<?= $pdo_class ?>"><?= $pdo ?></td>
    <td>Must be enabled</td>
  </tr>
  
  
  <tr>
    <th>Imagick</th>
    <td class="<?= $imagick_class ?>"><?= $imagick ?></td>
    <td>Recommended</td>
  </tr>
  
  <tr>
    <th>cURL</th>
    <td class="<?= $curl_class ?>"><?= $curl ?></td>
    <td>Should be enabled</td>
  </tr>
</tbody></table>

<br />
<br />

<div class="center_box"><h5>Admin account</h5></div>
<form action="" method="post" name="install_form">
  <table class="form" style="margin-left:auto; margin-right:auto; width:550px;">
    <tr>
      <th>Name</th>
      <td width="60%"><input type="text" name="admin_name" id="name" style="width:65%;" /></td>
    </tr>
    
    <tr>
      <th>Password</th>
      <td><input type="password" name="admin_password" id="pw" style="width:65%;" /></td>
    </tr>
    
    <tr>
      <th>Confirm password</th>
      <td><input type="password" name="confirm_pw" id="pwc" style="width:65%;" /></td>
    </tr>
    <tr>
      <th>
        <label for="show_db_errors">Show database errors</label>
        <p>Only enable if you have problems when creating the database.</p>
      </th>
      <td style="vertical-align:middle;">
        <input type="hidden" name="show_db_errors" value="0" />
        <input type="checkbox" name="show_db_errors" id="show_db_errors" value="1" />
      </td>
    </tr>
    <tr>
      <td colspan="2"><input type="submit" value="Install" id="install-commit" /></td>
    </tr>
  </table>
</form>

<script type="text/javascript">
(function($) {
  $('#install-commit').on('click', function() {
    if ($('.bad.important').length) {
      notice('System requirements not met');
      return false;
    }
    
    var pw   = $('#pw').val();
    var pwc  = $('#pwc').val();
    var name = $('#name').val();
    
    if ( name == '' ) {
      notice("Enter a name");
      $('#name').focus();
      return false;
    } else if ( name.length < 2 ) {
      notice("Name must be at least 2 characters long");
      return false;
    } else if ( pw == '' ) {
      notice("Enter a password");
      $('#pw').focus();
      return false;
    } else if ( pw.length < 5 ) {
      notice("Password must be at least 5 characters long");
      $('#pw').focus();
      return false;
    } else if ( pw != pwc ) {
      notice("Passwords don't match");
      $('#pwc').focus();
      return false;
    }
  });

  var text = Cookie.get("notice");
  if (text) {
    notice(text, true);
    Cookie.remove("notice");
  }
})(jQuery);
</script>
