<?php

// start the application...

// these are the constants the index should set...
define('MONTAGE_CONTROLLER','frontend');
define('MONTAGE_DEBUG',true);
define('MONTAGE_ENVIRONMENT','dev');
define('MONTAGE_APP_PATH',realpath(join(DIRECTORY_SEPARATOR,array(__FILE__,'..'))));

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

/*
// clear the cache while we are developing...
montage_profile::start('kill cache');
montage_cache::kill();
montage_profile::stop();
*/

out::e(montage_profile::get());

echo 'done';
