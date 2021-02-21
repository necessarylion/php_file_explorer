<?php 
function html_login($label) {?>
	<!DOCTYPE html>
	<html lang="en">
    <head>
      <title>File Explorer</title>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
      <link rel="stylesheet" href="assets/css/login.css">
    </head>
    <body>
      <main>
        <form method="POST" action="backend/api/login.php" autocomplete="off" onsubmit="document.querySelector('main').style.opacity = 0; document.querySelector('body').style.backgroundColor = '#035';">
          <?= $label; ?>
          <div>
            <input id="auth" type="password" name="auth" placeholder="Enter Password" spellcheck="false" required="true" autofocus="true" />
            <button type="submit"><svg viewBox="0 0 24 24"><path fill="#999" d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg></button>
          </div>
        </form>
      </main>
      <footer>
        <div>
          <span>File Explorer</span>
          <b> &nbsp; &bull; &nbsp;</b>
          <span>Made with &nbsp;<svg viewBox="0 0 24 24"><path fill="#D00" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>&nbsp; By &nbsp;<a target="_blank" href="https://github.com/webcdn">WebCDN</a></span>
        </div>
        <div>
          <a target="_blank" href="https://github.com/webcdn/File-Explorer/issues">Report Bugs</a>
          <b> &nbsp; &bull; &nbsp;</b>
          <a target="_blank" href="https://github.com/webcdn/File-Explorer/issues/1">Suggestions / Feedback</a>
          <b> &nbsp; &bull; &nbsp;</b>
          <a target="_blank" href="https://gg.gg/contribute">Donate</a>
        </div>
      </footer>
    </body>
  </html>
<?php
} 