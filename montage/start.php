<?php

/**
 *  Start the main montage controller
 *  
 *  including this from another file will start, configure, and run montage. This is
 *  literally the only thing you need to include to use montage.  
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
        "define('MONTAGE_APP_PATH','/your/application/root/directory');"
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

// some OSes might have a default umask set, so let's override that...
// if you want to keep the Os's default umask, then you can do:
// define('MONTAGE_UMASK',umsak()); which will override this setting 
if(!defined('MONTAGE_UMASK')){ define('MONTAGE_UMASK',0000); }//if
umask(MONTAGE_UMASK);

if(!defined('MONTAGE_DEBUG')){ define('MONTAGE_DEBUG',true); }//if

// set this to false if you only want montage to start and not actually handle the
// request, this is handy if you want access to all the classes but nothing else
if(!defined('MONTAGE_HANDLE')){ define('MONTAGE_HANDLE',true); }//if

if(!defined('MONTAGE_ERROR_LEVEL')){
  // by default, full error reporting should always be on...
  define('MONTAGE_ERROR_LEVEL',(E_ALL | E_STRICT | E_PARSE));
}//if
error_reporting(MONTAGE_ERROR_LEVEL);

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

// where cache is stored...
if(!defined('MONTAGE_CACHE_PATH')){
  define(
    'MONTAGE_CACHE_PATH',
    join(DIRECTORY_SEPARATOR,array(MONTAGE_APP_PATH,'cache'))
  );
}//if

// include the autoloader (and supporting files)...
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','montage_base_class.php')));
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','montage_base_static_class.php')));
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','helper','montage_path_class.php')));
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','helper','montage_cache_class.php')));
require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','montage_core_class.php')));

// include the profile class so we can see how long core takes to run...
if(MONTAGE_DEBUG){
  require(join(DIRECTORY_SEPARATOR,array(MONTAGE_PATH,'model','helper','montage_profile_class.php')));
  montage_profile::start('montage total');
}//if

try{
  
  /* @todo
  1 - create a montage_cache instance
  2 - create a montage_class instance
  3 - have montage_class load the core
  4 - create a configuration instance and populate it
  5 - pass the configuration into the core instance to start the core
    a - the core instance, after loading all the path can set up the dependancy injection container (montage_service)
        so that when something like montage::getRequest() gets called it can create an instance and
        use that instance to load the request
  6 - set montage_cache and montage_class into the montage Dependancy injection container
  */ 
  
  
  
  
  // start and configure the core (this does all the heavy lifting like find classes and
  // configure the autoloader and run the defined montage_start classes)...
  montage_core::start(
    MONTAGE_CONTROLLER,
    MONTAGE_ENVIRONMENT,
    MONTAGE_DEBUG,
    MONTAGE_CHARSET,
    MONTAGE_TIMEZONE,
    array(
      'montage_path' => MONTAGE_PATH,
      'montage_app_path' => MONTAGE_APP_PATH,
      'montage_cache_path' => MONTAGE_CACHE_PATH
    )
  );
  
}catch(Exception $e){

  // something failed in the core initialization, so get rid of cache...
  montage_cache::kill();
  throw $e;

}//try/catch

if(MONTAGE_HANDLE){

  // actually handle the request...
  montage_factory::getBestInstance('montage_handle');
  
}//if

if(MONTAGE_DEBUG){ montage_profile::stop(); }//if
