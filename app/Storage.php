<?php

namespace App;

use Exception;
use Aws\S3\S3Client;
use League\Flysystem\Filesystem;
use League\Flysystem\Visibility;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;

class Storage {

  protected $filesystem;

  public function __construct($setFolderPath = false) {
    if($setFolderPath) {
      $this->_setFolderPath();
    }
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

  protected function _setFolderPath() {
    $_ENV['AWS_FOLDER_NAME'] = empty($_REQUEST['path']) 
      ? $_ENV['FOLDER_NAME'] 
      : $_ENV['FOLDER_NAME'].'/'.$_REQUEST['path'];
  }

  /**
   * file listing
   */
  public function list() {
    $editable_files = ['asp','aspx','c','cer','cfm','class','cpp','cs','csr','css','csv','dtd','fla','h','htm','html','java','js','jsp','json','log','lua','m','md','mht','pl','php','phps','phpx','py','sh','sln','sql','svg','swift','txt','vb','vcxproj','whtml','xcodeproj','xhtml','xml'];

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
          'real_path' => $_ENV['AWS_FOLDER_NAME']. '/'. $fp,
          'name' => basename($fp),
          'type' => function_exists('mime_content_type') ? @mime_content_type($fp) : $ext,
          'ext'  => ($file->isDir()) ? '---' : $ext,
          'atime' => ($file->isFile()) ? $file->lastModified() : null,
          'ctime' => ($file->isFile()) ? $file->lastModified() : null,
          'mtime' => ($file->isFile()) ? $file->lastModified() : null,
          'is_dir' => $file->isDir(),
          'is_deletable' => true,
          'is_editable'  => in_array($ext, $editable_files),
          'is_writable'  => true,
          'is_readable'  => true,
          'is_executable' => false,
          'is_recursable' => true,
          'is_zipable'    => false,
          'is_zip'        => false,
        ];
        $index++;
      }
    }
    output(true, $files);
  }

  public function upload($file, $isPrivate = false) {
    try {
      $stream = fopen($file['tmp_name'], 'r+');
      $this->filesystem->writeStream(
        $file['name'],
        $stream,
        ['visibility' => ($isPrivate) ? Visibility::PRIVATE: Visibility::PUBLIC]
      );
    }
    catch(Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  protected function getPath($path = false, $method = 'get') {
    return str_replace($_ENV['FOLDER_NAME'].'/', '', $path ? $path : $_GET['path'] );
  }
  
  protected function getSize() {
    return $_GET['size'] * 1;
  }

  public function download() {
    $path   = $this->getPath();
    $stream = $this->filesystem->readStream($path);
    $file_extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $file_name      = basename($path);
    header("Pragma: public");
    header("Expires: -1");
    header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
    header("Content-Transfer-Encoding: binary");
    header("Content-Type:application/" . $file_extension);
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . $this->getSize());
    fpassthru($stream);
  }

  public function createDir() {
    $dir = trim( preg_replace('/[\<\>\:\"\/\\\|\?\*]/', '', @$_POST['dirname']), ' .');
		if( in_array($dir, array('.', '..')) ) {
			output(false, 'Invalid Attempt');
		}
		else {
      $response = $this->filesystem->createDirectory($dir);
      output(true, 'Directory Created'); 
    }
  }

  public function deleteFiles() {
    $files = $_POST['ways'];
		if( is_array($files) ){
			foreach ($files as $file) {
        $file = $this->getPath($file);
        $file_extension = pathinfo($file, PATHINFO_EXTENSION);
        if(!empty($file_extension)) {
          $this->filesystem->delete($file);
        }
        else {
          $this->filesystem->deleteDirectory($file);
        }
			}
			output(true, 'Deleted Successfully');
		}
		else {
			output(false, 'Deletion Failed');
		}
  }

  public function createFile($isPrivate = false) {
    $fl = trim( preg_replace('/[\<\>\:\"\/\\\|\?\*]/', '', @$_POST['filename']), ' .');
		if( in_array($fl, array('.', '..')) ) {
			output(false, 'Invalid Attempt');
		}
		else {
      $file = @$_POST['filename'];
      $fileExists = $this->filesystem->fileExists($file);
      if(!$fileExists) {
        $this->filesystem->write($file, '', 
          ['visibility' => ($isPrivate) ? Visibility::PRIVATE: Visibility::PUBLIC]);
          output(true, 'File Created');
      }
      else {
        output(false, 'File Already Exist');
      }
		}
  }

  public function getContent() {
    $path = $this->getPath();
    $response = $this->filesystem->read($path);
    return $response;
  }

  public function writeContent($contents) {
    $path = $this->getPath();
    $this->filesystem->write($path, $contents);
    output(true, 'File Saved Successfully');
  }

  public function rename($isPrivate = false) {
    $new = trim( preg_replace('/[\<\>\:\"\/\\\|\?\*]/', '', @$_POST['newname']), ' .');
		if( in_array($new, array('.', '..')) ) {
			output(false, 'Invalid Attempt');
		}
		else {
      $path = $this->getPath($_POST['path']);
      $newFileName = $_POST['newname'];

      $fileName = basename($path);
      $folder   = str_replace($fileName, '', $path);
      $newFileName = $folder.$newFileName;

      $file_extension = pathinfo($fileName, PATHINFO_EXTENSION);
      if(empty($file_extension)) {
        output(false, 'Folder Cannot Rename');
      }

      $fileExists = $this->filesystem->fileExists($newFileName);
      if(!$fileExists) {
        $this->filesystem->move($path, $newFileName, 
          ['visibility' => ($isPrivate) ? Visibility::PRIVATE: Visibility::PUBLIC]);
        output(true, 'Renamed Successfully');
      }
      else {
        output(false, 'File Already Exist');
      }
    }
  }

  public function copy($isPrivate = false) {
    $_ENV['AWS_FOLDER_NAME'] = $_ENV['FOLDER_NAME'];
		$ways = $_POST['ways'];
		if( is_array($ways) ){
			foreach ($ways as $way) {
        $originalFile = $way;
        $newPath = $_REQUEST['path'];
        $baseName = basename($originalFile);
        $file_extension = pathinfo($baseName, PATHINFO_EXTENSION);
        if(!empty($file_extension) ) {
        $newFile = $newPath.'/'.$baseName;
        $this->filesystem->copy($this->getPath($originalFile), $newFile, 
          ['visibility' => ($isPrivate) ? Visibility::PRIVATE: Visibility::PUBLIC]);
        }
        else {
          output(false, 'Folder Cannot Copy');
        }
			}
			output(true, 'Copied Successfully');
		}
		else {
			output(false, 'Copying Failed');
		}
  }

  public function move($isPrivate = false) {
    $_ENV['AWS_FOLDER_NAME'] = $_ENV['FOLDER_NAME'];
		$ways = $_POST['ways'];

		if( is_array($ways) ){
      foreach ($ways as $way) {
        $originalFile = $way;
        $newPath = $_REQUEST['path'];
        $baseName = basename($originalFile);
        $file_extension = pathinfo($baseName, PATHINFO_EXTENSION);
        if(!empty($file_extension) ) {
          $newFile = $newPath.'/'.basename($originalFile);
          $fileExists = $this->filesystem->fileExists($this->getPath($originalFile));
          if($fileExists) {
          $this->filesystem->move($this->getPath($originalFile), $newFile, 
            ['visibility' => ($isPrivate) ? Visibility::PRIVATE: Visibility::PUBLIC]);
          }
        }
        else { output(false, 'Folder Cannot Move'); }
      }
      output(true, 'Moved Successfully');
		}
		else { output(false, 'Moving Failed'); }
  }

}