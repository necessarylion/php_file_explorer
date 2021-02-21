<?php 

function html_editor($file){
  $storage = New App\Storage;
	$content = $storage->getContent();
?>
	<!DOCTYPE html>
	<html lang="en">
    <head>
      <title>Edit {<?= basename($file); ?>}</title>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
      <link rel="stylesheet" href="assets/css/editor.css?version=<?php echo $_ENV['VERSION'] ?>">
    </head>
    <body>
      <div class="overlay"></div>
      <header>
        <label for="codedit"><?= basename($file); ?></label>
        <div class="action">
          <button type="submit" class="btn" form="editor">Save</button>
          <button class="btn flat" onclick="window.open('', '_self', '').close(); return false;">Close</button>
        </div>
      </header>
      <form method="POST" id="editor">
        <div class="inputs">
          <input type="hidden" id="xsrf" name="xsrf" value="<?= @$_COOKIE['__xsrf']; ?>">
          <textarea id="codedit" name="content" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"><?= htmlentities( mb_convert_encoding($content, 'UTF-8', 'auto') ); ?></textarea>
        </div>
      </form>
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
      <script src="assets/js/editor.js?version=<?php echo $_ENV['VERSION'] ?>"></script>
    </body>
  </html>
<?php } ?>