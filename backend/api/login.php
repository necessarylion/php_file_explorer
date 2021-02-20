<?php

include __DIR__."/../../autoload.php";

$config = \App\Config::get();
/**
 * Login function
 */
if( !isset($_SESSION['__allowed']) && strlen($config->password) ) {
	if( !empty($_POST['auth']) ){
		sleep(1);
		if( md5(sha1($_POST['auth'])) === $config->password ) {
			$label = '<label for="auth" style="color: #383;">Successfully Logged In</label>';
			$_SESSION['__allowed'] = $_POST['auth'];
			header('Location: ../../');
			exit;
		}
		$label = '<label for="auth" style="color: #D22;">Incorrect Password</label>';
	}
	else if( !empty($_REQUEST['do']) ){
		output(false, 'Session Destroyed, you need to Login Again!');
	}
	$label = isset($label) ? $label : '<label for="auth">File Explorer v'.VERSION.'</label>';
	html_login($label);
	exit;
}