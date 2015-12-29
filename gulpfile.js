var elixir = require('laravel-elixir');
var assets = require('/home/blackmesa/.global_assets/elixir')(__dirname);

elixir(function(mix) {
  mix
  .sass(
    'app.scss',
    '../assets',
    {
      includePaths: [
        assets.bower.baseDir(),
        assets.loose.baseDir(),
      ]
    }
  )
  .scripts(
    assets.bower.pathTo([
      'angular/angular.min.js',
      'angular-ui-router/release/angular-ui-router.min.js',
      'angular-resource/angular-resource.min.js',
      'jquery/dist/jquery.min.js'
    ])
    .concat(assets.loose.pathTo([
      'parziphal/angular-file-model.js',
    ]))
    .concat([
      'post.js'
    ]),
    '../assets/app.js',
    'resources/assets/js'
  )
  ;
  assets.copyRobotoFonts(mix);
});
