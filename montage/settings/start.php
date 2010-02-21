<?php

/**
 *  Start the main montage controller
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 12-28-2009
 *  @package montage
 ******************************************************************************/

// set some constants...
if(!defined('MONTAGE_DEBUG')){ define('MONTAGE_DEBUG',true); }//if
error_reporting((E_ALL | E_STRICT | E_PARSE)); // error reporting should always be on
if(MONTAGE_DEBUG){
  ini_set('display_errors','on');
}else{
  // since debug isn't on let's not display the errors to the user and rely on logging...
  ini_set('display_errors','off');
}//if/else

if(!defined('MONTAGE_CHARSET')){ define('MONTAGE_CHARSET','UTF-8'); }//if
mb_internal_encoding(MONTAGE_CHARSET);

if(!defined('MONTAGE_TIMEZONE')){ define('MONTAGE_TIMEZONE','UTC'); }//if
date_default_timezone_set(MONTAGE_TIMEZONE);

// where the framework's core can be found...
define('MONTAGE_PATH',realpath(join(DIRECTORY_SEPARATOR,array(dirname(__FILE__),'..'))));

// where the applications core can be found...
// this can be set in the app's start.php for a speed boost...
if(!defined('MONTAGE_APP_PATH')){
  // auto-discover the app's root dir...
  $bt = debug_backtrace();
  if(!empty($bt[0])){
    define('MONTAGE_APP_PATH',realpath(join(DIRECTORY_SEPARATOR,array(dirname($bt[0]['file']),'..'))));
  }//if
}//if

if(!defined('MONTAGE_CONTROLLER')){
  throw new exception('MONTAGE_CONTROLLER constant has not been set. Set this in your index.php file!');
}//if

require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','out_class.php')));

require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','montage_base_static_class.php')));
// include the autoloader...
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','montage_wizard_class.php')));

// start the auto-loader...
montage_wizard::setPath(MONTAGE_PATH);
montage_wizard::setAppPath(MONTAGE_APP_PATH);
montage_wizard::start(MONTAGE_CONTROLLER);

// officially start montage...
montage::start();
/*montage::start(
  join(DIRECTORY_SEPARATOR,array(MONTAGE_APP_DIR,'web')),
  array(
    MONTAGE_MODEL_DIR,
    MONTAGE_APP_MODEL_DIR,
    MONTAGE_APP_CONTROLLER_DIR
  )
);*/
