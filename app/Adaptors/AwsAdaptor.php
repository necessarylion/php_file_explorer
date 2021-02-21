<?php

namespace App\Adaptors;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

class AwsAdaptor {

  static public function get() {
    $client = new S3Client([
      'credentials'   => [
          'key'       => $_ENV['AWS_KEY'],
          'secret'    => $_ENV['AWS_SECRET'],
      ],
      "region"    => $_ENV['AWS_REGION'],
      "version"   => "latest",
      "endpoint"  => $_ENV['AWS_ENDPOINT'],
      "scheme"    => "https"
    ]);

    return new AwsS3V3Adapter(
        $client,
        $_ENV['BUCKET_NAME'],
        $_ENV['STORAGE_FOLDER_NAME']
    );
  }

}