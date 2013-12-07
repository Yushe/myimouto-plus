MyImouto
========

MyImouto is a port of [Moebooru](https://github.com/moebooru/moebooru) to PHP and MySQL. In order for this to be an actual port (or as close as possible), MyImouto uses a custom framework that is based on Ruby on Rails, thus the code from Moebooru is transcribed to PHP with some modifications here and there to fit the target language and framework.

MyImouto features some changes and additions. More details can be found in the Wiki.

Requirements
------------

  * Requires PHP 5.4+. It was developed under PHP 5.4.7 and MySQL v5.5.27.
  * Must have PHP libraries are GD2 (for image processing), PDO (database) and cURL (for both Image search and Search external data features).
  * Recommended libraries are Imagick and Memcached.
  * Both Git and Composer are needed for installation/update. How to install Composer can be found [here](http://getcomposer.org/download/) and/or [here](http://getcomposer.org/doc/00-intro.md).
  * If running under Apache, the Rewrite mod must be enabled. Also, to serve gzipped assets (css and js files), the Headers mod is needed.

Installation
------------

  * Install system dependencies: `composer install`.
  * Create a database for the booru.
  * Create `config/config.php` and `config/database.yml` by copying their respective _.example_ files.
  * Set your database configuration in `config/database.yml`.
  * Configure the booru by editing `config/config.php`. For a minimum configuration, both *server_host* and *url_base* options must be correctly configured.
  * Run the installer: `php install.php`. Enter a name and password for the admin account when asked, then wait for the installation to finish.
  * Finally, point the document root of your web server to the `/public` folder. That's where the index.php file is.

Updating
--------

Every time you update the files with `git remote update` or something, also run `composer update` to update dependencies, specially for the framework, and also run `php config/boot.php db:migrate` to run database migrations (if any).

Troubleshooting
---------------

### Access denied for xxx.xxx.xxx.xxx

If this is all you see when you go to your booru to complete the installation, the problem is that the IP address you're connecting from isn't listed under the allowed IP addresses.

You simply need to allow the IP address you see in the notice. Go to _install/config.php_ and look for:


    'safe_ips' = [
      '127.0.0.1',
      '::1',
    ]

Enter the IP address you got in the notice in a new line, like this:

    'safe_ips' = [
      '127.0.0.1',
      '::1',
      'xxx.xxx.xxx.xxx'
    ]

Now refresh the page.

### Parse error: syntax error, unexpected '[' in ... 

If you see this error, it means you're running PHP 5.3 or lower. You have to upgrade to PHP 5.4 or higher.