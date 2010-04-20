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

// canary, these constants must be set...
if(!defined('MONTAGE_APP_PATH')){
  $bt = debug_backtrace();
  $bt_map = end($bt);
  throw new RuntimeException(
    sprintf(
      join("\r\n",array(
        'MONTAGE_APP_PATH constant has not been set. You need to define this constant in %s. '
        .' Something like:',
        '',
        "define('MONTAGE_APP_PATH','/application/root/directory');"
      )),
      $bt_map['file']
    )
  );
}//if

if(!defined('MONTAGE_CONTROLLER')){
  $bt = debug_backtrace();
  $bt_map = end($bt);
  throw new RuntimeException(
    sprintf(
      join("\r\n",array(
        'MONTAGE_CONTROLLER constant has not been set. (Usually something like "frontend" or "backend"). '
        .' You need to define this constant in %s. Something like:',
        '',
        "define('MONTAGE_CONTROLLER','controller_name');"
      )),
      $bt_map['file']
    )
  );
}//if

if(!defined('MONTAGE_ENVIRONMENT')){
  $bt = debug_backtrace();
  $bt_map = end($bt);
  throw new RuntimeException(
    sprintf(
      join("\r\n",array(
        'MONTAGE_ENVIRONMENT constant has not been set (usually something like "dev" or "prod". '
        .'You need to define this constant in %s. Something like:',
        '',
        "define('MONTAGE_ENVIRONMENT','environment_name');"
      )),
      $bt_map['file']
    )
  );
}//if

// set some constants to default values if not previously set...
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
if(!defined('MONTAGE_PATH')){ define('MONTAGE_PATH',realpath(dirname(__FILE__))); }//if

// include the autoloader (and supporting files)...
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','montage_base_static_class.php')));
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','helper','montage_path_class.php')));
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','montage_cache_class.php')));
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','montage_core_class.php')));

// include the profile class so we can see how long core takes to run...
if(MONTAGE_DEBUG){
  require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','helper','montage_profile_class.php')));
  montage_profile::start('montage total');
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

if(MONTAGE_DEBUG){ montage_profile::stop(); }//if
