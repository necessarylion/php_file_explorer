<?php

use App\Storage;

include __DIR__."/../autoload.php";

if( empty($_COOKIE['__xsrf']) ){
	setcookie('__xsrf', sha1( uniqid() ) );
}

if($_POST && @$_COOKIE['__xsrf'] != @$_POST['xsrf'] ){
	output(false, 'XSRF Failure');
}

$welcome = 'Welcome Back';

if( !empty($_REQUEST['do']) ){

  // listing api
	if( @$_POST['do'] == 'list' ) {
		clearstatcache();
    $storage = new Storage(true);
    $files = $storage->list();
	}

  // file upload
	if( @$_POST['do'] == 'upload' ){
    $storage = new Storage(true);
    $storage->upload($_FILES['file_data']);
    exit;
	}

  // file download
	if( @$_GET['do'] == 'download'){
    $storage = new Storage();
    $storage->download();
		exit;
	}

   // file download
	if( @$_GET['do'] == 'view'){
    $storage = new Storage();
    $storage->view();
		exit;
	}

  // create new folder
  if( @$_POST['do'] == 'mkdir' ){
    $storage = new Storage(true);
    $storage->createDir();
	}

  // delete file and folder
  if( @$_REQUEST['do'] == 'trash' ){
    $storage = new Storage();
    $storage->deleteFiles();
	}

  // creating new file
  if( @$_POST['do'] == 'nwfile' ){
    $storage = new Storage(true);
    $result = $storage->createFile();
	}

  // edit content fo the file
  if( @$_GET['do'] == 'edit'){
		if( @$_POST['do'] == 'save' && isset($_POST['content']) ){
      $storage = new Storage();
      $result = $storage->writeContent($_POST['content']);
		}
		html_editor($_GET['path']);
		exit;
	}

  if( @$_POST['do'] == 'rename' ){
    $storage = new Storage();
    $result = $storage->rename();
	}

  if( @$_POST['do'] == 'copy' ){
    $storage = new Storage();
    $storage->copy();
	}

	if( @$_POST['do'] == 'move' ) {
    $storage = new Storage();
    $storage->move();
	}

	if( @$_REQUEST['do'] == 'logout' ){
		logout() ? output(true, 'Logged Out Successfully') : output(false, 'Refreshing...');
	}

}