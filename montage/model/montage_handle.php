<?php

/**
 *  this class actually handles the request    
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 5-26-10
 *  @package montage 
 ******************************************************************************/
final class montage_handle extends montage_base_static {

  /**
   *  switched to true in the start() function
   *  @var  boolean
   */
  static private $is_started = false;

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
   *  this is what will actually handle the request, 
   *  
   *  called at the end of the start.php file, this is really the only thing that needs 
   *  to be called, everything else will take care of itself      
   */
  static function start(){
  
    // canary...
    if(self::$is_started){
      throw new RuntimeException('this method has alread started, no point in starting again');
    }//if
  
    self::$is_started = true;
    $debug = montage::getSettings()->getDebug();
    
    if($debug){ montage_profile::start(__METHOD__); }//if
    
    // get all the filters and start them...
    $filter_list = montage_core::getFilterClassNames();
    $use_template = self::get($filter_list);
    
    // profile, response...
    if($debug){ montage_profile::start('Response'); }//if
    
    if(!is_bool($use_template)){
      
      throw new UnexpectedValueException(
        sprintf(
          'the controller method (%s::%s) returned a non-boolean value, it was a %s',
          $request->getControllerClass(),
          $request->getControllerMethod(),
          gettype($use_template)
        )
      );
      
    }//if
    
    $response = montage::getResponse();
    $response->handle($use_template);
    
    // profile, response...
    if($debug){ montage_profile::stop(); }//if
    
    // profile, method...
    if($debug){ montage_profile::stop(); }//if
    
  }//method
  
  /**
   *  handle a request, warts and all
   *  
   *  the reason this is separate from {@link handle()} so that it can call it again
   *  to try and handle (in case of error or the like)
   *  
   *  @param  array $filter_list  a list of string names of classes that extend montage_filter
   *  @return boolean $use_template to pass into the response handler
   */
  static private function get($filter_list = array()){
  
    // canary, avoid infinite internal redirects...
    $ir_field = 'montage_handle::infinite_recursion_count'; 
    $ir_count = self::getField($ir_field,0);
    $ir_max_count = 15; // is there a reason to go more than 15?
    if($ir_count > $ir_max_count){
      throw new RuntimeException(
        sprintf(
          'The application has internally redirected more than %s times, something seems to '
          .'be wrong and the app is bailing to avoid infinite recursion!',
          $ir_max_count
        )
      );
    }else{
      self::bumpField($ir_field,1);
    }//if/else
  
    $debug = montage::getSettings()->getDebug();
    
    // profile...
    if($debug){ montage_profile::start(__METHOD__); }//if

    $use_template = false;
    $request = montage::getRequest();
    $response = montage::getResponse();
    $event = montage::getEvent();
    
    try{
      
      if(!empty($filter_list)){
        
        // profile, filters start...
        if($debug){ montage_profile::start('filters start'); }//if

        foreach($filter_list as $key => $filter_class_name){
          
          if(is_string($filter_class_name)){
            
            $event->broadcast(
              montage_event::KEY_INFO,
              array('msg' => sprintf('starting filter %s',$filter_class_name))
            );
            
            $filter_list[$key] = montage_factory::getInstance($filter_class_name);
            
          }//if
            
        }//foreach
        
      }//if
      
      // profile, filters start...
      if($debug){ montage_profile::stop(); }//if
      
      // profile...
      if($debug){ montage_profile::start('controller'); }//if
      
      $use_template = $request->handle();
      
      // profile, stop controller...
      if($debug){ montage_profile::stop(); }//if
      
      // profile...
      if($debug){ montage_profile::start('filters stop'); }//if
      
      if(!empty($filter_list)){
        
        // run all the filters again to stop them...
        foreach($filter_list as $filter_instance){
        
          $event->broadcast(
            montage_event::KEY_INFO,
            array('msg' => sprintf('stopping filter %s',get_class($filter_instance)))
          );
          
          $filter_instance->stop();
          
        }//foreach
        
      }//if
      
      // profile...
      if($debug){ montage_profile::stop(); }//if
    
    }catch(montage_forward_exception $e){
    
      $event->broadcast(
        montage_event::KEY_INFO,
        array('msg' => 
          sprintf(
            'forwarding to controller %s::%s via forward exception at %s:%s',
            $request->getControllerClass(),
            $request->getControllerMethod(),
            $e->getFile(),
            $e->getLine()
          )
        )
      );
    
      // we forwarded to another controller so we're going another round...
      $use_template = self::get();
    
    }catch(montage_redirect_exception $e){
    
      // we don't really need to do anything since the redirect header should have been called
      $use_template = false;
      $response->set('');
      
      $event->broadcast(
        montage_event::KEY_INFO,
        array('msg' => 
          sprintf(
            'redirect to %s',
            $e->getMessage()
          )
        )
      );
    
    }catch(montage_stop_exception $e){
      
      $use_template = false; // since a stop signal was caught we'll want to use $response->get()
      
      // do nothing, we've stopped execution so we'll go ahead and let the response take over
      $event->broadcast(
        montage_event::KEY_INFO,
        array('msg' => 
          sprintf(
            'execution stopped via stop exception at %s:%s',
            $e->getFile(),
            $e->getLine()
          )
        )
      );
      
    }catch(Exception $e){
      
      $request->setErrorHandler($e);
      
      $event->broadcast(
        montage_event::KEY_INFO,
        array('msg' => 
          sprintf(
            'forwarding to controller %s::%s to handle exception at %s:%s',
            $request->getControllerClass(),
            $request->getControllerMethod(),
            $e->getFile(),
            $e->getLine()
          )
        )
      );
      
      // send it back through for another round...
      $use_template = self::get();
      
    }//try/catch
    
    // profile...
    if($debug){ montage_profile::stop(); }//if
  
    return $use_template;
  
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
  static function getErrorRuntime($errno,$errstr,$errfile,$errline){
  
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
  static function getErrorFatal(){
  
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
  private static function getErrorName($errno){
  
    $ret_str = 'UNKNOWN';
    if(isset(self::$ERRORS_RUNTIME[$errno])){
      $ret_str = self::$ERRORS_RUNTIME[$errno];
    }else if(isset(self::$ERRORS_FATAL)){
      $ret_str = self::$ERRORS_FATAL[$errno];
    }//if/else if
  
    return $ret_str;
  
  }//method

}//class     
