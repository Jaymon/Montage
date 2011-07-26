<?php

/**
 *  this is an example of the index.php file that should go in your app's web/ folder
 */

require_once('path/to/Montage/Framework.php');

$env = 'dev'; // what environment to use
$debug = 1; // debug level you want to use
$app_path = realpath(__DIR__.'/..'); // your app's root path

$framework = new Montage\Framework($env,$debug,$app_path);
$framework->handle();
