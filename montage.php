<?php

/**
 *  this is the Montage command line interface
 */
require_once('out_class.php');

$method_name = 'setMingoConfig';

out::p('stripos');

$val = mb_stripos($method_name,'set') === 0;

out::p();

out::p('check 2');

$val = 
  (($method_name[0] === 's') || ($method_name[0] === 'S'))
  && (($method_name[1] === 'e') || ($method_name[1] === 'E'))
  && (($method_name[2] === 't') || ($method_name[2] === 'T'));

out::p();

out::p('check');

$method_name = mb_strtolower($method_name);

$val = ($method_name[0] === 's') && ($method_name[1] === 'e') && ($method_name[2] === 't');

out::p();

out::x();


require_once(__DIR__.'/src/Montage/Framework.php');

$env = 'cli';
$debug = 1;
$app_path = realpath(__DIR__);

$framework = new Montage\Framework($env,$debug,$app_path);
$framework->setField('cache_path',sys_get_temp_dir());
$framework->handle();
