<?php

/**
 *  this class handles errors    
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 5-26-10
 *  @package montage 
 ******************************************************************************/
final class montage_error {

  /**
   *  these are the errors that are handled with {@link getErrorRuntime()}.
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
   *  these errors are handled by the {@link getErrorFatal()} function
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
   *  handles runtime errors, basically the warnings, and the E_USER_* stuff
   *  
   *  http://us2.php.net/manual/en/function.set_error_handler      
   *
   *  @param  integer $errno  the error number, this will be a constant (eg, E_USER_NOTICE)
   *  @param  string  $errstr the actual error description
   *  @param  string  $errfile  the file path of the file that triggered the error
   *  @param  integer $errline  the line number the error occured on the $errfile         
   *  @return boolean false to pass the error through, true to block it from the normal handler
   */        
  static function handleRuntime($errno,$errstr,$errfile,$errline){
  
    $error_map = array();
    $error_map['type'] = $errno;
    $error_map['message'] = $errstr;
    $error_map['file'] = $errfile;
    $error_map['line'] = $errline;
    $error_map['name'] = self::getErrorName($error_map['type']);
    
    // broadcast the error to anyone that is listening...
    montage::getEvent()->broadcast(montage_event::KEY_ERROR,$error_map);
    
    // still pass the errors through, change to true if you want to block errors...
    return false;
    
  }//method
  
  /**
   *  this handles the fatal errors, the E_COMPILE, etc.
   *  
   *  http://us2.php.net/manual/en/function.register_shutdown_function
   *      
   *  "The following error types cannot be handled with a user defined error function: 
   *  E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, 
   *  and most of E_STRICT raised in the file where set_error_handler() is called."
   */        
  static function handleFatal(){
  
    if($error_map = error_get_last()){
    
      if(!isset(self::$ERRORS_RUNTIME[$error_map['type']])){
    
        $error_map['name'] = self::getErrorName($error_map['type']);
      
        // broadcast the error to anyone that is listening...
        montage::getEvent()->broadcast(montage_event::KEY_ERROR,$error_map);
        
      }//if
      
    }//if
    
  }//method
  
  /**
   *  return the error name that corresponds to the $errno
   *  
   *  @param  integer $errno
   *  @return string
   */
  private static function getName($errno){
  
    $ret_str = 'UNKNOWN';
    if(isset(self::$ERRORS_RUNTIME[$errno])){
      $ret_str = self::$ERRORS_RUNTIME[$errno];
    }else if(isset(self::$ERRORS_FATAL)){
      $ret_str = self::$ERRORS_FATAL[$errno];
    }//if/else if
  
    return $ret_str;
  
  }//method

}//class     
