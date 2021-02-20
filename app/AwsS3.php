<?php

namespace App;

use Exception;
use Aws\S3\S3Client;
use League\Flysystem\Filesystem;
use League\Flysystem\Visibility;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;

class AwsS3 {

  protected $filesystem;

  public function __construct() {
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

    $adapter = new AwsS3V3Adapter(
        $client,
        $_ENV['BUCKET_NAME'],
        $_ENV['AWS_FOLDER_NAME']
    );
    $this->filesystem = new Filesystem($adapter);

    $this->baseLink = $_ENV['AWS_ENDPOINT'].'/'.$_ENV['BUCKET_NAME'].'/'.$_ENV['AWS_FOLDER_NAME'].'/';
  }

  /**
   * file listing
   */
  public function list() {
    $allFiles = $this->filesystem->listContents('/')->toArray();
    $files = [];
    $index = 0;
    foreach($allFiles as $file) {
      $fp = $file->path();
      if(!empty($fp)) {
        
        $ext = strtolower(pathinfo($fp, PATHINFO_EXTENSION));

        $files[] = [
          'sort' => $index,
          'size' => ($file->isFile()) ? $file->fileSize() : 0,
          // 'path' => $fp,
          'path' => ($file->isFile()) ? $this->baseLink.$fp : $fp,
          'realpath' => $fp,
          'name' => basename($fp),
          'type' => function_exists('mime_content_type') ? @mime_content_type($fp) : $ext,
          'ext'  => ($file->isDir()) ? '---' : $ext,
          'atime' => ($file->isFile()) ? $file->lastModified() : null,
          'ctime' => ($file->isFile()) ? $file->lastModified() : null,
          'mtime' => ($file->isFile()) ? $file->lastModified() : null,
          'is_dir' => $file->isDir(),
          'is_deletable' => true,
          'is_editable'  => false,
          'is_writable'  => false,
          'is_readable'  => true,
          'is_executable' => false,
          'is_recursable' => false,
          'is_zipable'    => false,
          'is_zip'        => false,
        ];
        $index++;
      }
    }
    return $files;
  }

  public function upload($path, $filename, $permission = 'public-read') {
    try {
      $result = $this->s3->putObject([
        'Bucket' => $_ENV['BUCKET_NAME'],
        'Key'    => $_ENV['AWS_FOLDER_NAME'].'/'.$filename,
        'Body'   => fopen($path, 'r'),
        'ACL'    => $permission,
      ]);
      return $result;
    }
    catch(Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

}