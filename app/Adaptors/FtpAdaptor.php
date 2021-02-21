<?php

namespace App\Adaptors;

use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;

class FtpAdaptor {

  static public function get() {
    return new FtpAdapter(
      // Connection options
      FtpConnectionOptions::fromArray([
        'host' => $_ENV['FTP_HOST'], // required
        'root' => '/'.$_ENV['STORAGE_FOLDER_NAME'], // required
        'username' => $_ENV['FTP_USERNAME'], // required
        'password' => $_ENV['FTP_PASSWORD'], // required
        'port' => $_ENV['FTP_PORT'] * 1,
        'ssl' => false,
        'timeout' => 90,
        'utf8' => false,
        'passive' => true,
        'transferMode' => FTP_BINARY,
        'systemType' => null, // 'windows' or 'unix'
        'ignorePassiveAddress' => null, // true or false
        'timestampsOnUnixListingsEnabled' => false, // true or false
        'recurseManually' => true // true 
      ])
    );
  }
  
}