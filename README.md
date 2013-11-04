MyImouto
========

MyImouto is a port of [Moebooru](https://github.com/moebooru/moebooru) to PHP and MySQL. In order for this to be an actual port (or as close as possible), MyImouto uses a custom framework that is based on Ruby on Rails, thus the code from Moebooru is transcribed to PHP with some modifications here and there to fit the target language and framework.

MyImouto features some changes and additions. More details can be found in the Wiki.

Requirements
------------

  * Requires PHP 5.4+. It was developed under PHP 5.4.7 and MySQL v5.5.27.
  * Must have PHP libraries are GD2 (for image processing), PDO (database) and cURL (for both Image search and Search external data features).
  * Recommended libraries are Imagick and Memcached.
  * If running under Apache, the Rewrite mod must be enabled. Also, to serve gzipped assets (css and js files), the Headers mod is needed.

Installation
------------

Note: You need both Git and Composer installed in your system. How to install Composer can be found [here](http://getcomposer.org/download/) and/or [here](http://getcomposer.org/doc/00-intro.md).

Go to the location where you want MyImouto to be installed, then clone the repo, then install dependencies:

    git clone https://github.com/myimouto/myimouto
    cd myimouto
    composer install

  * Wait for Composer to download MyImouto and all its dependencies. After that, wait a little longer as the asset files will be compiled.
  * Point the document root of your web server to the /public folder.
  * Create the database for the booru.
  * Create config/config.php and config/database.yml.example by copying their respective ".example" files.
  * Set your database configuration in _config/database.yml_.
  * Set your MyImouto configuration in _config/config.php_ (read the _config/default_config.php_ file to see the available options). For the system for work correctly only the *server_host* and *url_base* options are the most important.
  * If you're not accessing the site locally, list the IP address you'll connect from in the 'safe_ips' array in _install/config.php_.
  * Go to your site to complete the installation. After installation is completed, you may delete the install folder.
  * If you have problems, read below the Troubleshooting section or report them in the issues section.

Every time you update the files with `remote update` or something, also run `composer update` to update dependencies, specially for the framework.

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