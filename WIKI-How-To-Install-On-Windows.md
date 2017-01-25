

This is an explained, step-by-step guide on how to install MyImouto booru in your local computer. This guide is focused on Windows users. If you'd like to install MyImouto under Linux, please check the How to install on Ubuntu guide. Before continuing and if you haven't yet, please check the system requirements that are in the project's main page or in the README.md file.

In order to run MyImouto, you need to setup a web server. To make things easier, we will use XAMPP throughout this guide. MyImouto also needs Git and Composer to be installed in your system. You also need a good text editor like Notepad++ to edit files.
## XAMPP

XAMPP is a pack of web-server-related programs. It makes everyone's life easier because it automatically installs and configures everything you need to set up a web server, so it's really good if you need something fast or if you're not sure how to set up a server manually. Among other programs, it will install for us Apache (the web sever), MySQL (database server) and of course, PHP.
### How to install

1. Download the installer: http://www.apachefriends.org/en/xampp-windows.html
2. Install it in your desired directory.
3. Run the XAMPP control panel and make sure Apache and MySQL are running.
4. Go to http://127.0.0.1/ in your web browser.

If the page loads, XAMPP was installed successfuly.
If you'd like XAMPP to start automatically on Windows startup, check the Svc box for both Apache and MySQL in the panel.

## Git

Git is a distributed revision control and source code management system. It's an excellent tool to develop and maintain projects for share or also for yourself, making developers' and the users' life nicer and easier. You need to have it installed in order to download the source code of MyImouto.
### How to install
1. Download installer: http://git-scm.com/download/win
2. Run the installer. 

The important options are in the "Adjusting your PATH environment" section, where you need to select the second option (Run Git from the Windows Command Prompt).

## Composer

Composer is a dependency manager for PHP that (also) makes developers' and users' life easier and nicer. It will download for you all the third-party libraries that MyImouto is dependent on. Composer is actually a single file called "composer.phar" that is executed with PHP.
### How to install
1. Download installer: https://getcomposer.org/Composer-Setup.exe
2. Run the installer. When prompted for php.exe, go to the location where you installed XAMPP to locate it. By default it is in C:\xampp\php\php.exe.

## Downloading MyImouto
You will need to open a command prompt in the location where you want MyImouto to be installed. On Windows 7 and up, go to the destination folder, right click while holding Shift, then choose "Open command window here", or hit Windows Key + R, then type cmd, hit Enter then go to the destination folder.

2. Enter the following command to download the files: `git clone https://github.com/myimouto/myimouto`
3. Go to the new folder (run cd myimouto in the console) 
4. Install the dependencies with Composer by running `composer install`.

Before finally installing MyImouto, some configurations are needed so it can work correctly, but don't close the console as it will be needed later.
## Configurations
### PHP.ini

We need to make some edits to PHP's configuration file, located in xampp/php/php.ini.

These are the recommended minimum values for some PHP directives. So open php.ini, search for these directives and edit them:

`upload_max_filesize = 5M` - Maximum size of files that PHP will accept (the value is actually up to you).

`post_max_size = 6M` - This value should be a little bigger than upload_max_filesize.

`memory_limit = 128M` - The bigger images you'll accept, the more memory they will need to be processed.

`date.timezone` - Make sure this directive has a value. You can go here to check for your timezone.

Make sure the following PHP extensions are enabled by removing the leading semicolon:
`extension=php_pdo_mysql.dll`
`extension=php_curl.dll`

## Server configuration (Apache2)

MyImouto's document root isn't just the myimouto folder, but it's the myimouto/public folder, so when you go to http://yourbooru.com/ your web server will load the index.php file that is inside the myimouto/public folder. This also means that you aren't supposed to access your booru by going to http://yourbooru.com/public.

There are many ways to achieve this. A rather simple one is to run the site under a port.

Let's enable port 3000. To enable it on Apache go to xampp/apache/conf and open the httpd.conf file. Look for the line that says "Listen 80", and below it enter "Listen 3000".

We have to let Apache know about the directory where we put MyImouto. In the same file, around line 220, you will see this:

This should be changed to whatever you set DocumentRoot to.

    <Directory "X:/xampp/htdocs">
      ...
    </Directory>

Where "X" is the drive where you installed Xampp. Below </Directory> enter this:

    <Directory "drive:/path/to/myimouto">
      AllowOverride All
      Require all granted
    </Directory>

Change the Directory path according to your settings.

Now we will point the port we opened before to the myimouto/public folder. Go to xampp/apache/conf/extra and open the httpd-vhosts.conf file and enter these lines:

    <VirtualHost *:3000>
      DocumentRoot "drive:/path/to/myimouto/public"
    </VirtualHost>

DocumentRoot is the same path as above except that you add the /public part.

We also need to make sure that both the Rewrite module and Virtual Hosts are enabled. They are enabled by default with XAMPP and when installing Apache2 in Ubuntu with apt-get install apache2, but if you happened to have installed Apache using a different method, like with WAMP, you have to make sure both requirements are enabled. In WAMP, for example, in your http.conf file, search for the following lines and make sure they are uncommented:

    LoadModule rewrite_module modules/mod_rewrite.so
    Include conf/extra/httpd-vhosts.conf

Now restart Apache so these and PHP changes take effect (you can do this in XAMPP's control panel).
##Database

Create a database for your booru (the default expected name is myimouto). You can do so through PHPMyAdmin:

1. Go to http://127.0.0.1/phpmyadmin in your web browser.
2. In the top, horizontal menu, click on Databases. You will see a small text input under "Create database". Enter "myimouto" there, then in the Collation input choose "utf8_general_ci", and then click on Create.

## Configuring MyImouto
1. Create a copy of (or just rename) both config/config.php.example and config/database.yml.example files, without the ".example" part.
2. Open config/database.yml and set the username and password for the database. By default, XAMPP sets "root" as username and nothing as password. If required set host to localhost to make the database connection thru a UNIX domain socket.

The configuration regarding the booru is in the config/default_config.php file. However, for convenience, you are requested to customize it in the config/config.php file, instead of directly modifying the default_config.php file. For a minimum configuration, you only need to correctly configure both server_host and url_base options.

That's all the configuration needed. We can finally install MyImouto.
## Installing MyImouto
1. In the console run the installer file with the command: php install.php.
2. Enter a name and password for the admin account when asked, then wait for the installation to finish.

And that's it. You can finally go to http://127.0.0.1:3000 (or your chosen URL) to use your booru.
