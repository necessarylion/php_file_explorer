<?php

use App\AwsS3;

echo "<pre>";

include 'autoload.php';

$aws = new AwsS3();
$files = $aws->list();
print_r($files);