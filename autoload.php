<?php

session_start();
ini_set('max_execution_time', 0);

include __DIR__."/backend/helper.php";
require __DIR__."/vendor/autoload.php";

define('VERSION', '2.0.5-beta');
define('_CONFIG', __DIR__.'/.htconfig');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$max_upload_size  = min( inBytes( ini_get('post_max_size') ), inBytes( ini_get('upload_max_filesize') ) );
$phpVer           = phpversion();

// drive
$drives  = json_decode(file_get_contents('.drives'), true);
$driveId = 0;
if(isset($_GET['driveId'])) {
  $getDriveId = $_GET['driveId'];
  $driveId    = isset($drives[$getDriveId]) ? $getDriveId : 0;
}
$drive = $drives[$driveId];

$theme                        = $drive['color'];
$_ENV['FOLDER_NAME']          = $drive['folder_name'];
$storage_path                 = $_ENV['FOLDER_NAME'];
$_ENV['STORAGE_FOLDER_NAME']  = $storage_path;
$_ENV['STORAGE_TYPE']         = $drive['type'];

// for aws
$_ENV['AWS_KEY']              = $drive['AWS_KEY']       ?? null;
$_ENV['AWS_SECRET']           = $drive['AWS_SECRET']    ?? null;
$_ENV['AWS_ENDPOINT']         = $drive['AWS_ENDPOINT']  ?? null;
$_ENV['AWS_REGION']           = $drive['AWS_REGION']    ?? null;
$_ENV['BUCKET_NAME']          = $drive['BUCKET_NAME']   ?? null;


// FTP
$_ENV['FTP_BASE_PATH']        = $drive['FTP_BASE_PATH']  ?? null;
$_ENV['FTP_HOST']             = $drive['FTP_HOST']       ?? null;
$_ENV['FTP_USERNAME']         = $drive['FTP_USERNAME']   ?? null;
$_ENV['FTP_PASSWORD']         = $drive['FTP_PASSWORD']   ?? null;
$_ENV['FTP_PORT']             = $drive['FTP_PORT']       ?? null;


// SFTP
$_ENV['SFTP_HOST']            = $drive['SFTP_HOST']      ?? null;
$_ENV['SFTP_USERNAME']        = $drive['SFTP_USERNAME']  ?? null;
$_ENV['SFTP_PASSWORD']        = $drive['SFTP_PASSWORD']  ?? null;
$_ENV['SFTP_PRIVATE_KEY']     = $drive['SFTP_PRIVATE_KEY']   ?? null;
$_ENV['SFTP_PRIVATE_KEY_PASSPHRASE'] = $drive['SFTP_PRIVATE_KEY_PASSPHRASE'] ?? null;
$_ENV['SFTP_PORT']            = $drive['SFTP_PORT']      ?? null;
$_ENV['SFTP_BASE_PATH']       = $drive['SFTP_BASE_PATH'] ?? null;