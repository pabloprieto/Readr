<?php

chdir(__DIR__);

include 'vendor/SplClassLoader.php';
include 'vendor/simplepie/autoloader.php';
include 'vendor/password_compat/password.php';

$loader = new SplClassLoader('Readr', 'readr/src');
$loader->register();

$app = new \Readr\App;
$app->run();