<?php

/**
 *  Start the main montage controller
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 12-28-2009
 *  @package montage
 ******************************************************************************/

// define some MONTAGE constants...
define('MONTAGE_SETTINGS_DIR',dirname(__FILE__));
define('MONTAGE_MODEL_DIR',realpath(join(DIRECTORY_SEPARATOR,array(MONTAGE_SETTINGS_DIR,'..','model'))));

// define some MONTAGE APP constants...
if(!defined('MONTAGE_APP_DIR')){
  // auto-discover the app's root dir...
  $bt = debug_backtrace();
  if(!empty($bt[0])){
    define('MONTAGE_APP_DIR',realpath(join(DIRECTORY_SEPARATOR,array(dirname($bt[0]['file']),'..'))));
  }//if
}//if
define('MONTAGE_APP_MODEL_DIR',join(DIRECTORY_SEPARATOR,array(MONTAGE_APP_DIR,'model')));
define('MONTAGE_APP_CONTROLLER_DIR',join(DIRECTORY_SEPARATOR,array(MONTAGE_APP_DIR,'controller')));

/*
// set the model include path so most core stuff will work...
set_include_path(
  get_include_path()
  .PATH_SEPARATOR.
  MONTAGE_MODEL_DIR
);
*/

// load the montage_load class, that will take care of all other loading...
// number 5: http://ioreader.com/2007/08/17/11-cool-things-about-php-that-most-people-overlook/
// number 6: http://ioreader.com/2007/08/19/12-things-you-should-dislike-about-php/

// include the main montage class...
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_MODEL_DIR,'montage_class.php')));

// include the autoloader...
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_MODEL_DIR,'montage_load_class.php')));

montage::start(
  join(DIRECTORY_SEPARATOR,array(MONTAGE_APP_DIR,'web')),
  array(
    MONTAGE_MODEL_DIR,
    MONTAGE_APP_MODEL_DIR,
    MONTAGE_APP_CONTROLLER_DIR
  )
);
