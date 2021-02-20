<?php


function getData($uri){
	$is_URL = !!preg_match('/(https?:\/\/)/i', $uri);

	if( function_exists('curl_init') && ($is_URL || is_bool($uri)) ){
		if( is_bool($uri) ){
			return true;
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
	elseif( function_exists('file_get_contents') && (!$is_URL || ini_get('allow_url_fopen')) ){
		if( is_bool($uri) ){
			return true;
		}
		return file_get_contents($uri);
	}
	return false;
}

function setData($uri, $data){
	if( function_exists('file_put_contents') ){
		return file_put_contents($uri, $data) !== false;
	}
	elseif( $fh = fopen($uri, 'wb') ){
		fwrite($fh, $data);
		fclose($fh);
		return true;
	}
	return false;
}

function scan_dir($path, $opts = null, &$list = array() ){
	$files = scandir($path, stripos($opts, 'desc') !== false ? 1 : 0);
	$files = array_diff( $files, array('.', '..') );

	foreach($files as $file){
		$fullpath = "$path/$file";
		if( is_dir($fullpath) ){
			stripos($opts, 'skipDirs' ) === false && array_push($list, $fullpath);
			stripos($opts, 'recursive') !== false && scan_dir("$path/$file", $opts, $list);
		}
		else {
			stripos($opts, 'skipFiles') === false && array_push($list, $fullpath);
		}
	}

	if( stripos($opts, 'dirFirst') !== false ){
		$dirA = $filA = array();
		foreach ($list as $l) {
			is_dir(realpath($l)) ? array_push($dirA, $l) : array_push($filA, $l);
		}
		return array_merge($dirA, $filA);
	}
	return $list;
}

function cp_rf($src, $dst, $dir, &$output = true) {
	$base = basename($dir);
	$handle = str_replace($dir, "$dst/$base", $src);
	if( is_dir($src) ) {
		$output &= mkdir($handle);
		$files = array_diff( scandir($src), array('.', '..') );
		foreach ($files as $file)
			cp_rf("$src/$file", $dst, $dir, $output);
	}
	else {
		$output &= copy($src, $handle);
	}
	return $output;
}

function rm_rf($loc, &$output = true) {
	if( is_dir($loc) ) {
		$files = array_diff( scandir($loc), array('.', '..') );
		foreach ($files as $file)
			rm_rf("$loc/$file", $output);
		$output &= rmdir($loc);
	}
	else {
		$output &= unlink($loc);
	}
	return $output;
}

function is_recursively_rdwr($d) {
	$stack = array($d);
	while( $loc = array_pop($stack) ) {
		if(!is_readable($loc) || !is_writable($loc))
			return false;
		if( is_dir($loc) ) {
			$files = array_diff( scandir($loc), array('.', '..') );
			foreach($files as $file)
				$stack[] = "$loc/$file";
		}
	}
	return true;
}

function up_paths($loc) {
	$output = array();
	$paths = is_array($loc) ? $loc : array($loc);
	foreach ($paths as $path) {
		$path = realpath($path);
		foreach(explode('/', $path) as $i) { 
			if( !in_array($path, $output) ){
				$output[] = realpath($path);
			}
			$path = dirname($path);
		}
	}
	sort($output);
	return array_filter($output);
}

function logout(){
	return session_destroy() && setcookie('__xsrf', '', time() - 3600);
}

function output($flag, $response, $xtra = array()) {
	header('Content-Type: application/json');
	$xtra = is_array($xtra) ? $xtra : array($xtra);
	$data = array('flag' => (bool) $flag, 'response' => $response);
	exit(json_encode($data + $xtra, JSON_PRETTY_PRINT));
}

function inBytes($ini_v) {
	$ini_v = trim($ini_v);
	$units = array('K' => 1<<10, 'M' => 1<<20, 'G' => 1<<30);
	return intval($ini_v) * ($units[strtoupper( substr($ini_v, -1) )] ? : 1);
}