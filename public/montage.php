<?php

/**
 *  this is the Montage command line interface
 */
require_once('out_class.php');
require_once(__DIR__.'/../src/Montage/Framework.php');

$env = 'cli';

// set the app path to temp directory on the system...
$app_path = sys_get_temp_dir();
if(mb_substr($app_path,-1) !== DIRECTORY_SEPARATOR){

  $app_path .= $app_path.DIRECTORY_SEPARATOR;

}//if

$app_path .= 'Montage';

$framework = new Montage\Framework($env,$app_path);
$framework->handle();
