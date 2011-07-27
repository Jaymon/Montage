<?php

/**
 *  this is the Montage command line interface
 */
require_once('out_class.php');
require_once(__DIR__.'/src/Montage/Framework.php');

$env = 'cli';
$debug = 1;
$app_path = realpath(__DIR__);

$framework = new Montage\Framework($env,$debug,$app_path);
$framework->setField('cache_path',sys_get_temp_dir());
$framework->handle();
