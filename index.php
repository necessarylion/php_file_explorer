<?php
require __DIR__."/login.php";
require __DIR__."/editor.php";
require __DIR__."/backend/main.php";

// middleware
if( !isset($_SESSION['__allowed']) ) {
	$label = isset($label) ? $label : '<label for="auth">Welcome Back</label>';
	html_login($label);
	exit;
}

list($r, $g, $b) = sscanf($theme, "#%02x%02x%02x");
define('COLOR_RGB', "rgb($r, $g, $b)" );
define('COLOR_RGB_CODE', "$r, $g, $b" );
define('COLOR_HEX', $theme );

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>File Explorer</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
  <link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:ital,wght@0,400;0,600;0,700;1,400;1,600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-primary: <?php echo COLOR_RGB ?>;
      --bg-transparent: rgb(<?php echo COLOR_RGB_CODE?>, 0.1);
    }
    body {
      font-family: 'Source Sans Pro', sans-serif;
    }
  </style>
  <script>
    const STORAGE_PATH = "<?php echo $storage_path ?>";
    const TOAST_SUCCESS_COLOR = '#2FCC70';
    const DRIVE_ID = "<?php echo $driveId ?>";
  </script>
  <link rel="stylesheet" href="assets/css/style.css?version=<?php echo $_ENV['VERSION'] ?>">
</head>
<body onload="toast('<?= @$welcome; ?>');">
  <section class="sidebar">
    <div class="logo">
      <h1>
        <?php include(__DIR__."/assets/logo-icon.php") ?>
      <span class="header-text">EZY Explorer</span></h1>
    </div>
    <div class="list">
      <div class="add-new-connection">
        <button class="btn">
          Add New Connection <?php include(__DIR__."/assets/server-icon.php") ?>
        </button>
      </div>

      <?php foreach($drives as $key => $drive) { ?>
      <a href="?driveId=<?php echo $key ?>" class="menu-list <?php echo ($driveId == $key) ? 'active': ''; ?>">
        <?php include(__DIR__."/assets/server-icon.php") ?> <span> <?php echo $drive['name'] ?> </span>
        <span class="color" style="background: <?php echo $drive['color'] ?>"></span>
      </a>
      <?php } ?>
    </div>
    <div class="sidebar-footer">
      Version: 1.0.1
      <p>Powered By&nbsp;<a href="https://www.programmingdude.dev/">programmingdude.dev</a></p>
    </div>
  </section>
  <section class="main-content">
    <?php include __DIR__."/layout/header.php" ?>

    <main class="<?= isset($_COOKIE['fe_view']) ? $_COOKIE['fe_view'] : 'gridView'; ?>"></main>
    <div class="overlay"></div>
    <div class="options" alt="Options"></div>

    <?php include __DIR__."/components/models.php" ?>
  </section>
	<script src="assets/js/jquery.js"></script>
	<script src="assets/js/home.js?version=<?php echo $_ENV['VERSION'] ?>"></script>
</body>
</html>