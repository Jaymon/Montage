<?php

/**
 *  handle recording significant events in the life of a montage framework call
 *  
 *  by default, this class does absolutely nothing and is designed to be extended
 *  by the developer to allow logging of errors.
 *  
 *  Basically, a developer should extend this class and then implement the set() method
 *  to log how they want it to (eg, save to a db, or send an email). You can use the
 *  supporting methods getBacktrace() and getInfo() to get detailed information about
 *  the request and what went wrong  
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-24-10
 *  @package montage
 *  @subpackage logging
 ******************************************************************************/       
/*
// easy code to get an "out of memory" fatal error from php...
$str = str_repeat('abcdefghijklmnopqrstuvwxyz0123456789',80000);
while(true){
  $str .= $str;
}//while
exit();
*/
class montage_log extends montage_base {

  /**
   *  the date format for date() call that will be used
   *  
   *  @var  string       
   */
  const DATE_STAMP = 'D M j o G:i:s e';

  /**
   *  these are the errors that are handled with handleRuntime().
   */        
  private static $ERRORS_RUNTIME = array(
    E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
    E_WARNING => 'E_WARNING',
    E_NOTICE => 'E_NOTICE',
    E_STRICT => 'E_STRICT',
    E_USER_NOTICE => 'E_USER_NOTICE',
    E_USER_WARNING => 'E_USER_WARNING',
    E_USER_ERROR => 'E_USER_ERROR'
    ///E_DEPRECATED => 'E_DEPRECATED', // >=5.3.0 
    ///E_USER_DEPRECATED => 'E_USER_DEPRECATED' // >=5.3.0
  );
  
  /**
   *  these errors are handled by the handleFatal() function
   *  
   *  use get_defined_constants() to see their values.
   */        
  private static $ERRORS_FATAL = array(
    E_ERROR => 'E_ERROR',
    E_PARSE => 'E_PARSE',
    E_CORE_ERROR => 'E_CORE_ERROR',
    E_CORE_WARNING => 'E_CORE_WARNING',
    E_COMPILE_ERROR => 'E_COMPILE_ERROR',
    E_COMPILE_WARNING => '_COMPILE_WARNING'
  );

  /**
   *  sets this class to handle errors, basically this is the on switch            
   */
  final function __construct(){
  
    // for runtime errors...
    set_error_handler(array($this,'setRuntime'));
    
    // for uncaught exceptions, we don't bother with this one since it will suppress uncaught
    // exceptions (not print them to the screen or anything, and can't bubble like set_error_handler)
    // and setFatal() will catch any uncaught exception anyway...
    ///set_exception_handler(array($this,'setException'));
    
    // for fatal errors...
    register_shutdown_function(array($this,'setFatal'));
  
    // for inheritence, let child classes do any init they need...
    $this->start();
  
  }//method

  /**
   *  handles runtime errors, basically the warnings, and the E_USER_* stuff
   *
   *  @param  integer $errno  the error number, this will be a constant (eg, E_USER_NOTICE)
   *  @param  string  $errstr the actual error description
   *  @param  string  $errfile  the file path of the file that triggered the error
   *  @param  integer $errline  the line number the error occured on the $errfile         
   *  @return boolean false to pass the error through, true to block it from the normal handler
   */        
  function setRuntime($errno,$errstr,$errfile,$errline){
  
    $this->set(
      $errno,
      $errstr,
      $errfile,
      $errline,
      $this->getName($errno)
    );
    
    // still pass the errors through, change to true if you want to block errors...
    return false;
    
  }//method
  
  /**
   *  handles runtime errors, basically the warnings, and the E_USER_* stuff
   *
   *  @link http://us2.php.net/set_exception_handler
   *  @link http://stackoverflow.com/questions/1082276/logging-caught-and-uncaught-exceptions
   *  @link http://stackoverflow.com/questions/557052/exceptions-in-php-try-catch-or-setexceptionhandler      
   *      
   *  @param  Exception $e
   */
  function setException($e){
  
    $this->set(
      $e->getCode(),
      $e->getMessage(),
      $e->getFile(),
      $e->getLine(),
      get_class($e)
    );
    
  }//method
  
  /**
   *  this handles the fatal errors, the E_COMPILE, etc.
   *  
   *  "The following error types cannot be handled with a user defined error function: 
   *  E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, 
   *  and most of E_STRICT raised in the file where set_error_handler() is called."
   */        
  function setFatal(){
  
    if($error_map = error_get_last()){
    
      // only save the last error if it wasn't handled by the runtime error catcher...
      if(!isset(self::$ERRORS_RUNTIME[$error_map['type']])){
        $this->set(
          $error_map['type'],
          $error_map['message'],
          $error_map['file'],
          $error_map['line'],
          $this->getName($error_map['type'])
        );
      }//if
      
    }//if
    
  }//method
  
  /**
   *  allows arbitrary info to be saved with each error logged
   *  
   *  this is different than {@link setMsg()} in that this will only be sent if
   *  one of the set methods is called, basically this is meta info you want to 
   *  attach to other messages or errors logged.            
   *            
   *  @param  string  $info the info to be tied to the key 
   *  @param  string  $key  if you want to update a specific message, include the key                    
   */
  function appendInfo($info,$key = ''){
  
    // canary...
    if(empty($info)){ return; }//if
    
    if(empty($key)){
      
      $datestamp = date(self::DATE_STAMP);
      if($this->hasField($datestamp)){
      
        $field = $this->getField($datestamp,array());
        $field[] = $info;
        $this->setField($datestamp,$field);
      
      }//if
      
    }else{
    
      $this->setField($key,$field);
    
    }//if/else
    
  }//method
  
  /**
   *  log an arbitrary message
   *  
   *  @param  string  $msg  the message to save into the log
   *  @param  string  $type the error name
   *  @param  integer $num  the errno                    
   */
  function setMsg($msg,$type = 'MESSAGE',$num = 0){
  
    // sanity...
    if(empty($msg)){ return; }//if
    $this->set($num,$msg,'','',$type);
      
  }//method
  
  /**
   *  do something with the error
   *  
   *  this is the method that should be implemented by any child class that extends
   *  this class         
   *  
   *  @param  string  $errno
   *  @param  string  $errstr the message
   *  @param  string  $errfile  the file where the message originated
   *  @param  integer $errline  the line number of $errfile where the error occured
   *  @param  string  $type usually the name of the error (eg, E_USER_ERROR or MESSAGE)   
   */
  protected function set($errno,$errstr,$errfile,$errline,$type){}//method
  
  /**
   *  generate a backtrace list
   *  
   *  @return array a list of method calls from first to latest (default, debug_backtrace gives latest to first)
   */
  protected function getBacktrace(){
  
    // set the backtrace...
    $backtrace = debug_backtrace();
    $backtrace = array_reverse(array_slice($backtrace,2));
    $ret_list = array();
    
    foreach($backtrace as $key => $bt_map){
    
      $class = empty($bt_map['class']) ? '' : $bt_map['class'];
      $method = empty($bt_map['function']) ? '' : $bt_map['function'];
      $file = empty($bt_map['file']) ? 'unknown' : $bt_map['file'];
      $line = empty($bt_map['line']) ? 'unknown' : $bt_map['line'];
      
      $method_call = '';
      if(empty($class)){
        if(!empty($method)){ $method_call = $method; }//if
      }else{
        if(empty($method)){
          $method_call = $class;
        }else{
          $method_call = sprintf('%s::%s',$class,$method);
        }//if/else
      }//if/else
      
      $arg_list = array();
      if(!empty($bt_map['args'])){
      
        foreach($bt_map['args'] as $arg){
        
          if(is_object($arg)){
            $arg_list[] = get_class($arg);
          }else{
            if(is_array($arg)){
              $arg_list[] = sprintf('Array(%s)',count($arg));
            }else if(is_bool($arg)){
              $arg_list[] = $arg ? 'TRUE' : 'FALSE';
            }else if(is_null($arg)){
              $arg_list[] = 'NULL';
            }else if(is_string($arg)){
              $arg_list[] = sprintf('"%s"',$arg);
            }else{
              $arg_list[] = $arg;
            }//if/else
          }//if/else
        
        }//foreach
      
      }//if
      
      $ret_list[] = sprintf('%s(%s) - %s:%s',$method_call,join(', ',$arg_list),$file,$line);
      
    }//foreach
  
    return $ret_list;
  
  }//method
  
  /**
   *  this function aggregates some information about the errored request
   *  
   *  the emails didn't have much info, so this function adds info like url requested
   *  and what request vars there were, that way we can work better to solve the error         
   *
   *  @return string  information about the request
   */           
  protected function getInfo(){
  
    $ret_map = array();
    
    // save some general info about the request...
    $ret_map['request'] = array();
    
    $request = montage::getRequest();
    $ret_map['request']['url'] = $request->getUrl();
    $ret_map['request']['referrer'] = $request->getReferer();
    $ret_map['request']['controller_class'] = $request->getControllerClass();
    $ret_map['request']['controller_method'] = $request->getControllerMethod();
    $ret_map['request']['host'] = $request->getHost();
    $ret_map['request']['ajax'] = $request->isAjax() ? 'TRUE' : 'FALSE';
    $ret_map['request']['script'] = $request->getFile();
    $ret_map['request']['cli'] = $request->isCli() ? 'TRUE' : 'FALSE';
    $ret_map['request']['User-Agent'] = $request->getUserAgent();
    
    if($request->hasServerField('REQUEST_URI')){
      $ret_map['request']['REQUEST_URI'] = $request->getServerField('REQUEST_URI','');
    }//if
    
    if($request->isCli()){
    
      if(!empty($_SERVER['argv'])){
        $ret_map['argv'] = $_SERVER['argv'];
      }//if
      
    }else{
    
      $ret_map['request']['ip_address'] = $request->getIp();
    
    }//if/else
    
    // save the application saved info...
    $ret_map['general'] = $this->getFields();
  
    // add the variables...
  
    if(!empty($_GET)){
      $ret_map['_GET'] = $request->getGetFields();
    }//if
    
    if(!empty($_POST)){
      $ret_map['_POST'] = $request->getPostFields();
    }//if
    
    if(!empty($_SESSION)){
      $ret_map['_SESSION'] = $request->getSessionFields();
    }//if
    
    if(!empty($_COOKIE)){
      $ret_map['_COOKIE'] = $request->getCookieFields();
    }//if
      
    return $ret_map;
  
  }//method */
  
  /**
   *  return the error name that corresponds to the $errno
   *  
   *  @param  integer $errno
   *  @return string
   */
  protected function getName($errno){
  
    $ret_str = 'UNKNOWN';
    if(isset(self::$ERRORS_RUNTIME[$errno])){
      $ret_str = self::$ERRORS_RUNTIME[$errno];
    }else if(isset(self::$ERRORS_FATAL)){
      $ret_str = self::$ERRORS_FATAL[$errno];
    }//if/else if
  
    return $ret_str;
  
  }//method

}//class

?>
