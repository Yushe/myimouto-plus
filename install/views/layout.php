<!DOCTYPE html>
<html>
<head>
  <title><?= CONFIG()->app_name ?></title>
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link href="/assets/application.css" media="screen" rel="stylesheet" type="text/css">
  <script src="/assets/application.js" type="text/javascript"></script>
  <script src="/assets/moe-legacy/application.js" type="text/javascript"></script>
</head>
<body>
  <div class="overlay-notice-container" id="notice-container" style="display: none;">
    <table cellspacing="0" cellpadding="0"> <tbody>
      <tr> <td>
        <div id="notice">
        </div>
      </td> </tr>
    </tbody> </table>
  </div>

  <div id="content">
    <h1 id="static-index-header" style="margin-bottom:50px;"><a href="/"><?= CONFIG()->app_name ?></a></h1>
    <?= $this->content() ?>
  </div>
</body>
</html>
