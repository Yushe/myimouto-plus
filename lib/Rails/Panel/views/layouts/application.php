<!doctype html>
<html>
<head>
  <title><?= Rails::application()->name() ?></title>
  <link href="<?= $this->urlFor('rails_panel') . '/stylesheet.css' ?>" rel="stylesheet" type="text/css" />
  <link rel="shotcut icon" type="image/png" href="<?= $this->urlFor('root') . 'favicon.png' ?>" />
</head>
<body>
  <div id="notice-container"></div>
  <div class="navbar navbar-inverse navbar-fixed-top" role="banner">
    <div class="container">
      <div class="navbar-header">
        <?= $this->linkTo('RailsPanel', '#index', ['class' => 'navbar-brand']) ?>
      </div>
      

      <ul class="nav navbar-nav">
        <li><?= $this->linkTo('Show routes', '#show_routes') ?></li>
        <li><?= $this->linkTo('Compile assets', '#compile_assets') ?></li>
        <li><?= $this->linkTo('Create files', '#create_files') ?></li>
        <li><?= $this->linkTo('Generate database cache', '#gen_table_data') ?></li>
      </ul>
      
      <div class="pull-right navbar-text">
        <?= $this->linkTo('Back to ' . Rails::application()->name(), 'root', ['class' => 'navbar-link']) ?>
      </div>
    </div>
  </div>

  <div class="container">
    <div class="row">
      <div class="navbar-default" style="text-align:center;margin-bottom:2em;">
      </div>
    </div>
    <div class="row">
      <?= $this->content() ?>
    </div>
    <hr />
    <footer><p>&copy; 2013 <em>RailsPHP Framework</em></p></footer>
  </div>
</body>
</html>
