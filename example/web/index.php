<?php

// start the application...

// these are the constants the index should set...
define('MONTAGE_CONTROLLER','frontend');
define('MONTAGE_DEBUG',true);
define('MONTAGE_ENVIRONMENT','dev');

$start_path = join(
  DIRECTORY_SEPARATOR,
  array(
    dirname(__FILE__),
    '..',
    'settings',
    'start.php'
  )
);
require($start_path);

try{

  montage::handle();
  
}catch(Exception $e){

  out::e($e);

}//try/catch

echo 'done';
