<?php

include __DIR__."/../autoload.php";

$config = \App\Config::get();

$path = empty($_REQUEST['path']) ? './'.$storage_path : $_REQUEST['path'];

$deny_paths = up_paths( array(__FILE__, _CONFIG) );
$reqs_paths = isset( $_REQUEST['ways'] ) ? array_map(function($p){return realpath($p);}, $_REQUEST['ways']) : array(realpath($path));
$editable_files = array('asp','aspx','c','cer','cfm','class','cpp','cs','csr','css','csv','dtd','fla','h','htm','html','java','js','jsp','json','log','lua','m','md','mht','pl','php','phps','phpx','py','sh','sln','sql','svg','swift','txt','vb','vcxproj','whtml','xcodeproj','xhtml','xml');

if( empty($_COOKIE['__xsrf']) ){
	setcookie('__xsrf', sha1( uniqid() ) );
}

if($_POST && @$_COOKIE['__xsrf'] != @$_POST['xsrf'] ){
	output(false, 'XSRF Failure');
}

$welcome = 'Welcome Back';

if( !empty($_REQUEST['do']) ){

	if( in_array($_REQUEST['do'], array('edit', 'rename', 'permit', 'trash')) && !empty(array_intersect($reqs_paths, $deny_paths)) ){
		output(false, 'Oops! You\'re trying to play with source files.');
	}

  // listing api
	else if( @$_POST['do'] == 'list' ) {
    // folder path AWS
    $_ENV['AWS_FOLDER_NAME'] = empty($_REQUEST['path']) 
      ? $storage_path 
      : $storage_path.'/'.$_REQUEST['path'];
		clearstatcache();
    $aws = new App\AwsS3();
    $files = $aws->list();
		output(true, $files);
	}

  // file upload
	else if( @$_POST['do'] == 'upload' ){
    // folder path AWS
    $_ENV['AWS_FOLDER_NAME'] = empty($_REQUEST['path']) 
      ? $storage_path 
      : $storage_path.'/'.$_REQUEST['path'];
    $aws = new App\AwsS3();
    $aws->upload($_FILES['file_data']);
    exit;
	}

  // file download
	else if( @$_GET['do'] == 'download'){
    $aws = new App\AwsS3();
    $aws->download($path);
		exit;
	}

  // create new folder
  else if( @$_POST['do'] == 'mkdir' ){
		$dir = trim( preg_replace('/[\<\>\:\"\/\\\|\?\*]/', '', @$_POST['dirname']), ' .');

		if( in_array($dir, array('.', '..')) ) {
			output(false, 'Invalid Attempt');
		}
		else {
      $_ENV['AWS_FOLDER_NAME'] = empty($_REQUEST['path']) 
        ? $storage_path 
        : $storage_path.'/'.$_REQUEST['path'];
      $aws = new App\AwsS3();
      try{
        $aws->createDir($dir);
        output(true, 'Directory Created'); 
      }
      catch(Exception $e) {
        output(false, 'Unable to create directory');
      }
		}
	}

  // delete file and folder
  else if( @$_REQUEST['do'] == 'trash' ){
		$ways = array_diff($_POST['ways'], $deny_paths, array('.', '..'));
		if( is_array($ways) ){
			$ack = true;
			foreach ($ways as $way) {
        $aws = new App\AwsS3();
        $aws->deleteFile($way);
			}
			$ack ? output(true, 'Deleted Successfully') : output(false, 'Unable to delete files');
		}
		else {
			output(false, 'Deletion Failed');
		}
	}

  // creating new file
  else if( @$_POST['do'] == 'nwfile' ){
    $_ENV['AWS_FOLDER_NAME'] = empty($_REQUEST['path']) 
        ? $storage_path 
        : $storage_path.'/'.$_REQUEST['path'];

		$fl = trim( preg_replace('/[\<\>\:\"\/\\\|\?\*]/', '', @$_POST['filename']), ' .');

		if( in_array($fl, array('.', '..')) ) {
			output(false, 'Invalid Attempt');
		}
		else {
      $aws = new App\AwsS3();
      $result = $aws->createFile(@$_POST['filename']);
      $result ?  output(true, 'File Created') : output(false, 'File Already Exist');
		}
	}

  // edit content fo the file
  else if( @$_GET['do'] == 'edit'){
		if( @$_POST['do'] == 'save' && isset($_POST['content']) ){
      $aws = new App\AwsS3();
      $result = $aws->writeContent($_POST['content']);
			output(true, 'File Saved Successfully');
		}
		html_editor($_GET['path']);
		exit;
	}

  else if( @$_POST['do'] == 'rename' ){
		$new = trim( preg_replace('/[\<\>\:\"\/\\\|\?\*]/', '', @$_POST['newname']), ' .');

		if( in_array($new, array('.', '..')) ) {
			output(false, 'Invalid Attempt');
		}
		else {
      $aws = new App\AwsS3();
      $result = $aws->rename();
		}
	}

  else if( @$_POST['do'] == 'copy' ){
    
    $_ENV['AWS_FOLDER_NAME'] = $storage_path;

    $aws = new App\AwsS3();

		$ways = array_diff($_POST['ways'], $deny_paths, array('.', '..'));
		if( is_array($ways) ){
			foreach ($ways as $way) {
        $aws->copy($way, $_REQUEST['path']);
			}
			output(true, 'Copied Successfully');
		}
		else {
			output(false, 'Copying Failed');
		}
	}

  //-----#################-----//

	elseif( @$_POST['do'] == 'move' ) {

    $_ENV['AWS_FOLDER_NAME'] = $storage_path;

    $aws = new App\AwsS3();

		$ways = array_diff($_POST['ways'], $deny_paths, array('.', '..'));

		if( is_array($ways) ){
			foreach ($ways as $way) {
        foreach ($ways as $way) {
          $aws->move($way, $_REQUEST['path']);
        }
			}
      output(true, 'Moved Successfully');
		}
		else {
			output(false, 'Moving Failed');
		}
	}

	elseif( @$_REQUEST['do'] == 'logout' ){
		logout() ? output(true, 'Logged Out Successfully') : output(false, 'Refreshing...');
	}

}