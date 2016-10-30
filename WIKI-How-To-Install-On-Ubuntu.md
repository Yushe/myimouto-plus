These steps will install MyImouto and all programs needed on a fresh install of Ubuntu 14.04 LTS. This is a rather quick guide for local use.

Programs that will be installed are: `PHP 7.0` `MySQL 5.6` `NGINX` `Git` `Composer` `Unzip`

### Most commands need root privileges, so use root user:
`sudo su`

### Add PHP and Git PPAs:
`add-apt-repository ppa:ondrej/php`
`add-apt-repository ppa:git-core/ppa`

### Update packages:
`apt-get update`

### Install packages and PHP extensions. MySQL will ask for root password, so enter the password you want:
`apt-get install -y php7.0-fpm php7.0-xml nginx git imagemagick mysql-server-5.6 php7.0-imagick php7.0-mysql unzip`

### Install Composer (extracted from its download page):
`php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"`
`php composer-setup.php`
`php -r "unlink('composer-setup.php');"`

### Move Composer to make it globally accessible:
`mv composer.phar /usr/local/bin/composer`

### Exit root user:
`exit`

### Clone MyImouto repository, then go into the folder:
`git clone https://github.com/myimouto/myimouto --depth 1`
`cd myimouto`

### Install dependencies:
`composer install`

### Configure NGINX. Open the default configuration file:
`sudo vim /etc/nginx/sites-available/default`

And paste this either at the bottom or top of the file. Make sure to change /path/to/myimouto/public to the correct path:
`server {
        listen 3000 default_server;

        root /path/to/myimouto/public;

        rewrite "^/(?:data/)?(preview|sample|jpeg|image)/([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{28})(?:/.*?)?(\.[\w]+)$" /data/$1/$2/$3/$2$3$4$5 last;

        location / {
                try_files $uri /index.php$is_args$query_string;
        }

        location ~ \.php$ {
                include fastcgi_params;
                fastcgi_pass unix:/run/php/php7.0-fpm.sock;
        }
}`

### By default the maximum file size allowed is only about 2MB. 
To increase that limit (for example to 50MB) you have to configure both NGINX and PHP, so open /etc/nginx/nginx.conf and add this inside the http block:
`client_max_body_size 55M;`

### Now to configure PHP, open /etc/php/7.0/fpm/php.ini and search for:
`post_max_size` (around line 656) and set it to 55M

`upload_max_filesize` (around line 798) and set it 50M

Let's also turn on error display, which will help to debug things if anything goes wrong, so search for `display_errors` (around line 462) and set it to On.

### Reload both NGINX and PHP so changes take effect:
`sudo service nginx reload`
`sudo service php7.0-fpm reload`

### Create a database for MyImouto:
`mysql -u root -p`
(You will be prompted to enter your password)
`CREATE DATABASE myimouto COLLATE utf8_general_ci;`
`exit`

### Make a copy of MyImouto config files:
`cp config/config.php.example config/config.php`
`cp config/database.yml.example config/database.yml`

Set your database username (root) and the password you chose in config/database.yml. If you're accessing the site from a different computer/device, open config/config.php and set $server_host and $url_base accordingly (to check for your local IP run ifconfig).

### Run the MyImouto installer:
`php install.php`

### Set permissions to folders. Note that this is NOT the correct way to do this, but for a local installation it doesn't matter:
`chmod -R 0777 log tmp`
`sudo chown -R www-data:www-data public/data/`

Go to http://yourip:3000. Your site should be working!
