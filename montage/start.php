<?php

/**
 *  Start the main montage controller
 *  
 *  including this from another file will start, configure, and run montage
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
if(!defined('MONTAGE_PATH')){ define('MONTAGE_PATH',realpath(dirname(__FILE__))); }//if

// where the applications core can be found...
// this can be set in the app before calling this file for a very slight speed boost...
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

// include the autoloader (and supporting files)...
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','montage_base_static_class.php')));
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','help','montage_path_class.php')));
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','montage_cache_class.php')));
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','montage_core_class.php')));

// include the profile cache so we can see how long core takes to run...
if(MONTAGE_DEBUG){
  require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','montage_profile_class.php')));
}//if

try{
  
  // start the auto-loader...
  montage_core::start(
    MONTAGE_CONTROLLER,
    MONTAGE_ENVIRONMENT,
    MONTAGE_DEBUG,
    MONTAGE_CHARSET,
    MONTAGE_TIMEZONE,
    MONTAGE_PATH,
    MONTAGE_APP_PATH
  );
  
}catch(Exception $e){

  // something failed in the core initialization, so get rid of cache...
  montage_cache::kill();
  throw $e;

}//try/catch

// actually handle the request...
montage_core::handle();
