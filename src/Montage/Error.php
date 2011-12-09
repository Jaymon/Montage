<?php

/**
 *  this class handles errors    
 *   
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 5-26-10
 *  @package montage 
 ******************************************************************************/
namespace Montage;

use Montage\Event\Dispatch;
use Montage\Event\Eventable;
use Montage\Event\Event;

class Error implements Eventable {

  /**
   *  the event dispatcher
   *
   *  @see  setDispatch(), getDispatch()
   *  @var  Dispatch      
   */
  protected $dispatch = null;

  /**
   *  these are the errors that are handled with {@link handleRuntime()}.
   */        
  protected $ERRORS_RUNTIME = array(
    E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
    E_WARNING => 'E_WARNING',
    E_NOTICE => 'E_NOTICE',
    E_STRICT => 'E_STRICT',
    E_USER_NOTICE => 'E_USER_NOTICE',
    E_USER_WARNING => 'E_USER_WARNING',
    E_USER_ERROR => 'E_USER_ERROR',
    E_DEPRECATED => 'E_DEPRECATED', 
    E_USER_DEPRECATED => 'E_USER_DEPRECATED'
  );
  
  /**
   *  these errors are handled by the {@link handleFatal()} function
   *  
   *  use get_defined_constants() to see their values.
   */
  protected $ERRORS_FATAL = array(
    E_ERROR => 'E_ERROR',
    E_PARSE => 'E_PARSE',
    E_CORE_ERROR => 'E_CORE_ERROR',
    E_CORE_WARNING => 'E_CORE_WARNING',
    E_COMPILE_ERROR => 'E_COMPILE_ERROR',
    E_COMPILE_WARNING => '_COMPILE_WARNING'
  );
  
  public function __construct(){
  
    // set error handlers...
    // http://us2.php.net/set_error_handler
    set_error_handler(array($this,'handleRuntime'));
    register_shutdown_function(array($this,'handleFatal'));
    
    // http://us2.php.net/manual/en/function.set-exception-handler.php
    ///set_exception_handler(array($this,'handleException'));
  
  }//method
  
  public function __destruct(){
  
    restore_error_handler();
  
  }//method
  
  /**
   *  get the event dispatcher
   *
   *  @Param  Dispatch  $dispatch   
   */
  public function setEventDispatch(\Montage\Event\Dispatch $dispatch){ $this->dispatch = $dispatch; }//method
  
  /**
   *  get the event dispatcher
   *
   *  @return Dispatch   
   */
  public function getEventDispatch(){ return $this->dispatch; }//method
  
  /**
   *  @see  Eventable interface
   */
  public function broadcastEvent(\Montage\Event\Event $event){
  
    if($dispatch = $this->getEventDispatch()){
    
      $dispatch->broadcast($event);
    
    }//if
    
    return $event;
  
  }//method
  
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
  public function handleRuntime($errno,$errstr,$errfile,$errline){

    // canary...
    if($errno === E_RECOVERABLE_ERROR){
      throw new \InvalidArgumentException($errstr,$errno);
    }//if
    // respect error reporting, ignore supressed errors...
    // http://us2.php.net/manual/en/language.operators.errorcontrol.php
    // this can be done with either the @ symbol before an expression, or with: error_reporting(0) at
    // the top of the script
    if(error_reporting() === 0){ return false; }//if
  
    $error_map = array();
    $error_map['group'] = 'RUNTIME';
    $error_map['type'] = $errno;
    $error_map['message'] = $errstr;
    $error_map['file'] = $errfile;
    $error_map['line'] = $errline;
    $error_map['name'] = $this->getName($error_map['type']);
    
    $this->handleError($error_map);
    
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
  public function handleFatal(){
  
    if($error_map = error_get_last()){
    
      if(!isset($this->ERRORS_RUNTIME[$error_map['type']])){
    
        $error_map['group'] = 'FATAL';
        $error_map['name'] = $this->getName($error_map['type']);
        $this->handleError($error_map);
      
      }//if
      
    }//if
    
  }//method
  
  /**
   *  handles exceptions
   *  
   *  this is just a quick way to have an exception be broadcast to any error listeners
   *  for logging and so forth         
   * 
   *  @since  6-1-10    
   *  @param  Exception $e  the exception to handle         
   *  @return boolean
   */
  public function handleException($e){

    $error_map = array();
    $error_map['group'] = 'EXCEPTION';
    $error_map['type'] = $e->getCode();
    $error_map['message'] = $e->getMessage();
    $error_map['file'] = $e->getFile();
    $error_map['line'] = $e->getLine();
    $error_map['name'] = get_class($e);
    $error_map['instance'] = $e;
    $this->handleError($error_map);
    
    return true;
    
  }//method
  
  /**
   *  do whatever with the $error_map
   *  
   *  @since  9-16-11
   *  @param  array $error_map  all the gathered info about the error
   */
  protected function handleError(array $error_map){

    // broadcast the error to anyone that is listening...
    $event = new Event('framework.error',$error_map);
    $this->broadcastEvent($event);
  
  }//method
  
  /**
   *  return the error name that corresponds to the $errno
   *  
   *  @param  integer $errno
   *  @return string
   */
  protected function getName($errno){
  
    $ret_str = 'UNKNOWN';
    if(isset($this->ERRORS_RUNTIME[$errno])){
      $ret_str = $this->ERRORS_RUNTIME[$errno];
    }else if(isset($this->ERRORS_FATAL)){
      $ret_str = $this->ERRORS_FATAL[$errno];
    }//if/else if
  
    return $ret_str;
  
  }//method

}//class     
