# MyImouto

MyImouto is a port of [Moebooru](https://github.com/moebooru/moebooru) to PHP and MySQL. In order for this to be an actual port (or as close as possible), MyImouto uses a custom framework that is based on Ruby on Rails, thus the code from Moebooru is transcribed to PHP with some modifications here and there to fit the target language and framework.

MyImouto is still under development. For more information about its features, changes and additions, please refer to the [About MyImouto](https://github.com/myimouto/myimouto/wiki/About-MyImouto) wiki.


## Requirements

  * PHP 5.4 or higher.
  * MySQL v5.5 or higher.
  * PHP libraries:
    * GD2
    * PDO
    * cURL
    * Imagick (recommended)
    * Memcached (recommended)
  * Composer (Dependency Management for PHP).
  * If running under Apache, the Rewrite mod must be enabled. Also, to serve gzipped CSS and JS files, the Headers mod is needed.


## Installation

For an explained, step-by-step guide, please check the [How to Install](https://github.com/myimouto/myimouto/wiki/How-to-install) guide. Otherwise, here's the quick guide for advanced users:

  * Install system dependencies: `composer install`.
  * Create a database for the booru.
  * Create `config/config.php` and `config/database.yml` by copying their respective _.example_ files.
  * Set your database configuration in `config/database.yml`.
  * Configure the booru by editing `config/config.php`. For a minimum configuration, both `server_host` and `url_base` options must be correctly configured.
  * Run the installer: `php install.php`. Enter a name and password for the admin account when asked, then wait for the installation to finish.
  * Finally, point the document root of your web server to the `public` folder. That's where the index.php file is.


## Updating

Every time you update the files, don't forget to run `composer update` to update dependencies, specially for the framework, and also run `php config/boot.php db:migrate` to run database migrations (if any).
