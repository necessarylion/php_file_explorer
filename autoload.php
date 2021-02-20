<?php

session_start();
setlocale(LC_ALL, 'en_US.UTF-8');
ini_set('max_execution_time', 0);

include __DIR__."/backend/helper.php";
require __DIR__."/vendor/autoload.php";

define('VERSION', '2.0.5-beta');
define('_CONFIG', __DIR__.'/.htconfig');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$phpVer           = phpversion();
$theme            = 'black';
$storage_path     = 'storage';
$max_upload_size  = min( inBytes( ini_get('post_max_size') ), inBytes( ini_get('upload_max_filesize') ) );