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

if(!defined('MONTAGE_CONTROLLER')){
  throw new RuntimeException('MONTAGE_CONTROLLER constant has not been set. Set this in your index.php file!');
}//if
if(!defined('MONTAGE_ENVIRONMENT')){
  throw new RuntimeException('MONTAGE_ENVIRONMENT constant has not been set. Set this in your index.php file!');
}//if

if(!defined('MONTAGE_CHARSET')){ define('MONTAGE_CHARSET','UTF-8'); }//if
mb_internal_encoding(MONTAGE_CHARSET);

if(!defined('MONTAGE_TIMEZONE')){ define('MONTAGE_TIMEZONE','UTC'); }//if
date_default_timezone_set(MONTAGE_TIMEZONE);

// where the framework's core can be found...
if(!defined('MONTAGE_PATH')){
  define('MONTAGE_PATH',realpath(join(DIRECTORY_SEPARATOR,array(dirname(__FILE__),'..'))));
}//if

// @todo  get rid of this...
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','out_class.php')));

// where the applications core can be found...
// this can be set in the app's start.php for a speed boost...
if(!defined('MONTAGE_APP_PATH')){
  // auto-discover the app's root dir...
  $bt = debug_backtrace();
  $bt_map = end($bt);
  if(!empty($bt_map)){
    define('MONTAGE_APP_PATH',realpath(join(DIRECTORY_SEPARATOR,array(dirname($bt_map['file']),'..'))));
  }//if
  unset($bt);
  unset($bt_map);
}//if

// include the autoloader...
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','montage_base_static_class.php')));
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','montage_wizard_class.php')));

// start the auto-loader...
montage_wizard::start(
  MONTAGE_CONTROLLER,
  MONTAGE_PATH,
  MONTAGE_APP_PATH
);

// officially start montage...
montage::start();

// load settings...

// first load the global settings...
$settings = montage_wizard::getCustomPath(
  montage_wizard::getAppPath(),
  'settings',
  'settings.php'
);
if(file_exists($settings)){ include($env_settings); }//if

// now load the environment settings so they can override any global settings...
$settings = montage_wizard::getCustomPath(
  montage_wizard::getAppPath(),
  'settings',
  sprintf('%s.php',MONTAGE_ENVIRONMENT)
);
if(file_exists($settings)){ include($settings); }//if
unset($settings);
