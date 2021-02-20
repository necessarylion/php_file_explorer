<?php

include __DIR__."/../autoload.php";

$config = \App\Config::get();

$path = empty($_REQUEST['path']) ? './'.$storage_path : $_REQUEST['path'];
$real = @realpath($path);
$is_up = strlen($real) < strlen(__DIR__);
$deny_paths = up_paths( array(__FILE__, _CONFIG) );
$reqs_paths = isset( $_REQUEST['ways'] ) ? array_map(function($p){return realpath($p);}, $_REQUEST['ways']) : array(realpath($path));
$editable_files = array('asp','aspx','c','cer','cfm','class','cpp','cs','csr','css','csv','dtd','fla','h','htm','html','java','js','jsp','json','log','lua','m','md','mht','pl','php','phps','phpx','py','sh','sln','sql','svg','swift','txt','vb','vcxproj','whtml','xcodeproj','xhtml','xml');


if( empty($_COOKIE['__xsrf']) ){
	setcookie('__xsrf', sha1( uniqid() ) );
}
if($real === false) {
	output(false, 'File or Directory Not Found');
}
if($_POST && @$_COOKIE['__xsrf'] != @$_POST['xsrf'] ){
	output(false, 'XSRF Failure');
}
if( $is_up && !$config->go_up ){
	output(false, 'Forbidden Access');
}

$welcome = 'Welcome Back';

if( !empty($_REQUEST['do']) ){
	if( in_array($_REQUEST['do'], array('edit', 'rename', 'permit', 'trash')) && !empty(array_intersect($reqs_paths, $deny_paths)) ){
		output(false, 'Oops! You\'re trying to play with source files.');
	}

	else if( @$_POST['do'] == 'list' ){
		clearstatcache();
		if( !is_executable($path) ) {
			output(false, 'Not Enough Permissions');
		}
		else if( is_dir($path) ) {
			$result = array();
			$fps = scan_dir($path, 'dirFirst');

			empty($fps) && output(false, 'Directory Empty');

			foreach($fps as $i => $fp) {
				if( !$config->show_hidden && substr(basename($fp), 0, 1) == '.' ){
					continue;
				}
				$ext = strtolower(pathinfo($fp, PATHINFO_EXTENSION));
				$stat = @stat($fp);
				$danger = in_array(realpath($fp), $deny_paths);
				$result[] = array(
					'sort' => $i,
					'name' => basename($fp),
					'path' => preg_replace('@^\./@', '', $fp),
					'real_path' => realpath($fp),
					'type' => function_exists('mime_content_type') ? @mime_content_type($fp) : $ext,
					'ext' => is_dir($fp) ? '---' : $ext,
					'size' => is_dir($fp) ? 0 : $stat['size'],
					'perm' => '0' . decoct( @fileperms($fp) & 0777 ),
					'ownr' => $stat['uid'],
					'ownr_ok' => function_exists('posix_getpwuid') ? posix_getpwuid($stat['uid'])['name'] : $stat['uid'],
					'atime' => $stat['atime'],
					'ctime' => $stat['ctime'],
					'mtime' => $stat['mtime'],
					'is_dir'=> is_dir($fp),
					'is_deletable' => is_writable($path) && !$danger && is_recursively_rdwr($fp),
					'is_editable' => !is_dir($fp) && is_writable($fp) && !$danger && in_array($ext, $editable_files),
					'is_writable' => is_writable($fp) && !$danger,
					'is_readable' => is_readable($fp),
					'is_executable' => is_executable($fp),
					'is_recursable' => is_recursively_rdwr($fp) && !$danger,
					'is_zipable' => is_dir($fp) && class_exists('ZipArchive') && is_recursively_rdwr($fp),
					'is_zip' => $ext == 'zip' && class_exists('ZipArchive'),
				);
			}
			output(true, $result);
		}
		else {
			output(false, 'Not a Directory');
		}
	}

	elseif( @$_GET['do'] == 'download' && !is_dir($real) ){
		$filename = basename($path);
		header('Content-Type: ' . mime_content_type($path));
		header('Content-Length: '. filesize($path));
		header(sprintf('Content-Disposition: attachment; filename=%s', strpos('MSIE', $_SERVER['HTTP_REFERER']) ? rawurlencode($filename) : $filename ));
		ob_flush();
		readfile($path);
		exit;
	}

	elseif( @$_GET['do'] == 'edit' && !is_dir($real) && file_exists($real) ){
		if( @$_POST['do'] == 'save' && isset($_POST['content']) ){
			setData($path, $_POST['content']) ? output(true, 'File Saved Successfully') : output(false, 'Damn! saving error');
		}
		html_editor($real);
		exit;
	}

	elseif( @$_POST['do'] == 'upload' ){
		chdir($path);
		move_uploaded_file($_FILES['file_data']['tmp_name'], $_FILES['file_data']['name']);
		exit;
	}

	elseif( @$_POST['do'] == 'mkdir' ){
		chdir($path);
		$dir = trim( preg_replace('/[\<\>\:\"\/\\\|\?\*]/', '', @$_POST['dirname']), ' .');

		if( in_array($dir, array('.', '..')) ) {
			output(false, 'Invalid Attempt');
		}
		else if( is_dir($dir) ){
			output(false, 'Directory Already Exist');
		}
		else {
			mkdir($dir, 0755) ? output(true, 'Directory Created') : output(false, 'Unable to create directory');
		}
	}

	elseif( @$_POST['do'] == 'nwfile' ){
		chdir($path);
		$fl = trim( preg_replace('/[\<\>\:\"\/\\\|\?\*]/', '', @$_POST['filename']), ' .');

		if( in_array($fl, array('.', '..')) ) {
			output(false, 'Invalid Attempt');
		}
		else if( file_exists($fl) ) {
			output(false, 'File Already Exist');
		}
		else {
			touch($fl) ? output(true, 'File Created') : output(false, 'Unable to create file');
		}
	}

	elseif( @$_POST['do'] == 'rename' ){
		$new = trim( preg_replace('/[\<\>\:\"\/\\\|\?\*]/', '', @$_POST['newname']), ' .');

		if( in_array($new, array('.', '..')) ) {
			output(false, 'Invalid Attempt');
		}
		else {
			rename($real, dirname($real).'/'.$new) ? output(true, 'Renamed Successfully') : output(false, 'Wrong Params');
		}
	}

	elseif( @$_POST['do'] == 'copy' ){
		$ways = array_diff($_POST['ways'], $deny_paths, array('.', '..'));

		if( is_array($ways) ){
			$ack = true;
			foreach ($ways as $way) {
				$ack &= cp_rf($way, $path, $way);
			}
			@$ack ? output(true, 'Copied Successfully') : output(false, 'Wrong Params');
		}
		else {
			output(false, 'Copying Failed');
		}
	}

	elseif( @$_POST['do'] == 'move' ){
		$ways = array_diff($_POST['ways'], $deny_paths, array('.', '..'));

		if( is_array($ways) ){
			$ack = true;
			foreach ($ways as $way) {
				$ack &= rename($way, $path . '/' . basename($way));
			}
			$ack ? output(true, 'Moved Successfully') : output(false, 'Wrong Params');
		}
		else {
			output(false, 'Moving Failed');
		}
	}

	elseif( @$_REQUEST['do'] == 'trash' ){
		$ways = array_diff($_POST['ways'], $deny_paths, array('.', '..'));

		if( is_array($ways) ){
			$ack = true;
			foreach ($ways as $way) {
				$ack &= rm_rf($way);
			}
			$ack ? output(true, 'Deleted Successfully') : output(false, 'Unable to delete files');
		}
		else {
			output(false, 'Deletion Failed');
		}
	}

	elseif( @$_POST['do'] == 'compress' ){
		if (is_dir($real)){
			$zip = new ZipArchive();
			if( $zip->open($path.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) === true ){
				foreach(scan_dir($path, 'recursive,skipDirs') as $loc ){
					$zip->addFile($loc, str_replace($path, '', $loc));
				}
				$zip->close();
				output(true, '`'.basename($path).'.zip` created successfully');
			}
			else {
				output(false, 'Oops! Unable to compress');
			}
		}
		else {
			output(false, 'Oops! Directory is corrupted');
		}
	}

	elseif( @$_POST['do'] == 'extract' ){
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		$pathTo = pathinfo($real, PATHINFO_DIRNAME);
		if( strtolower($ext) == 'zip' ){
			$zip = new ZipArchive;
			if( $zip->open($path) === true ){
				$zip->extractTo($pathTo);
				$zip->close();
				output(true, 'Archive Extracted Successfully');
			}
			else {
				output(false, 'Oops! Archive is corrupted');
			}
		}
		else {
			output(false, "Oops!, Error while extracting `.$ext` file");
		}
	}

	elseif( @$_POST['do'] == 'permit' ){
		$perm = octdec($_POST['perm']);
		$rcrs = @$_POST['recurse'];
		
		if( empty($rcrs) ){
			$ack = chmod($real, $perm);
		}
		else if( is_dir($real) ){
			$ack = true;
			foreach( scan_dir($real, 'recursive') as $list ){
				if( (is_dir($list) && stripos($rcrs, 'd') !== false) || (!is_dir($list) && stripos($rcrs, 'f') !== false) ){
					$ack &= chmod($list, $perm);
				}
			}
		}
		$ack ? output(true, 'Permission Modified') : output(false, 'Error to permit');
	}

	elseif( @$_REQUEST['do'] == 'logout' ){
		logout() ? output(true, 'Logged Out Successfully') : output(false, 'Refreshing...');
	}

}