<?php
require dirname(__DIR__) . '/config/boot.php';
require dirname(__DIR__) . '/install/ApplicationInstaller/Base.php';

ApplicationInstaller\Base::instance()->dispatch();
