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
      --bg-primary: <?php echo $theme ?>;
    }
    body {
      font-family: 'Source Sans Pro', sans-serif;
    }
  </style>
  <script>
    const STORAGE_PATH = "<?php echo $storage_path ?>";
    const TOAST_SUCCESS_COLOR = '#2FCC70';
  </script>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body onload="toast('<?= @$welcome; ?>');">
  
  <?php include __DIR__."/layout/header.php" ?>

	<main class="<?= isset($_COOKIE['fe_view']) ? $_COOKIE['fe_view'] : 'gridView'; ?>"></main>

  <?php include __DIR__."/layout/footer.php" ?>

	<div class="overlay"></div>
	<div class="options" alt="Options"></div>

  <?php include __DIR__."/components/models.php" ?>

	<script src="assets/js/jquery.js"></script>
	<script src="assets/js/home.js"></script>
	
</body>
</html>