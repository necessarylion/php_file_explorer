<?php

namespace App\Adaptors;

use League\Flysystem\PhpseclibV2\SftpAdapter;
use League\Flysystem\PhpseclibV2\SftpConnectionProvider;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

class SftpAdaptor {

  static public function get() {
    return new SftpAdapter(
      new SftpConnectionProvider(
        $_ENV['SFTP_HOST'], // host (required)
        $_ENV['SFTP_USERNAME'], // username (required)
        empty($_ENV['SFTP_PASSWORD']) ? null : $_ENV['SFTP_PASSWORD'] , // password (optional, default: null) set to null if privateKey is used
        empty($_ENV['SFTP_PRIVATE_KEY']) ? null : $_ENV['SFTP_PRIVATE_KEY'], // private key (optional, default: null) can be used instead of password, set to null if password is set
        empty($_ENV['SFTP_PRIVATE_KEY_PASSPHRASE']) ? null : $_ENV['SFTP_PRIVATE_KEY_PASSPHRASE'], // passphrase (optional, default: null), set to null if privateKey is not used or has no passphrase
        empty($_ENV['SFTP_PORT']) ? 22 : $_ENV['SFTP_PORT'] * 1, // port (optional, default: 22)
        false, // use agent (optional, default: false)
        30, // timeout (optional, default: 10)
        10, // max tries (optional, default: 4)
        null, // host fingerprint (optional, default: null),
        null, // connectivity checker (must be an implementation of 'League\Flysystem\PhpseclibV2\ConnectivityChecker' to check if a connection can be established (optional, omit if you don't need some special handling for setting reliable connections)
      ),
      $_ENV['STORAGE_FOLDER_NAME'], // root path (required)
    );
  }
}