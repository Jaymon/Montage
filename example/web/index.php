<?php

// start the application...
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
  out::i(montage::getLoader());
  
}catch(Exception $e){

  out::e($e);

}//try/catch

echo 'done';
