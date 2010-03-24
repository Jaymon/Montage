<?php

/**
 *  this is the master class, it is static and available from everywhere in your app,
 *  it provides access to the most commonly used montage objects so they can be easily
 *  retrieved from anywhere     
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
final class montage extends montage_base_static {
  
  /**
   *  this is what will actually handle the request, 
   *  
   *  called at the end of the start.php file, this is really the only thing that needs 
   *  to be called, everything else will take care of itself      
   */
  static function handle(){
  
    $debug = self::getSettings()->getDebug();
    
    // profile...
    if($debug){ montage_profile::start(__METHOD__); }//if
  
    $request = self::getRequest();
    $controller_class_name = $controller_method = '';
  
    try{
      
      ///out::e($class,$method); return;
      
      // profile...
      if($debug){ montage_profile::start('filters start'); }//if
      
      // get all the filters and start them...
      ///$filter_list = array_map(array('montage_core','getInstance'),montage_core::getFilters());
      $filter_list = montage_core::getFilterClassNames();
      foreach($filter_list as $key => $filter_class_name){
        
        try{
          
          $filter_list = montage_core::getInstance($filter_class_name);
        
        }catch(montage_forward_exception $e){
          // we ignore the forward because the controller hasn't been called yet, but people
          // might want to do the forward instead of all the montage_request::setController* methods
        }//try/catch
        
      }//foreach
      
      // profile...
      if($debug){ montage_profile::stop(); }//if
      
      // profile...
      if($debug){ montage_profile::start('controller'); }//if
      
      while(true){
        
        try{
          
          $result = $request->handle();
          break;
          
        }catch(montage_forward_exception $e){
    
          // we want to go another round in the while loop since the original
          // controller is being forwarded to another controller
    
        }//try/catch
        
      }//while
      
      // profile...
      if($debug){ montage_profile::stop(); }//if
      
      // profile...
      if($debug){ montage_profile::start('filters stop'); }//if
      
      // run all the filters again to stop them...
      foreach($filter_list as $filter_instance){
        $filter_instance->stop();
      }//foreach
      
      // profile...
      if($debug){ montage_profile::stop(); }//if
      
    }catch(montage_redirect_exception $e){
    
      // we don't really need to do anything since the redirect header should have been called
      $result = false;
    
    }catch(Exception $e){
    
      ///self::getLog()->setException($e);
      
      $controller_class = montage_core::getClassName('error');
      if(montage_core::isController($controller_class)){
      
        $request->setControllerClass($controller_class);
        $controller_method = $request->getControllerMethodName(get_class($e));
        if(method_exists($controller_class,$controller_method)){
          $request->setControllerMethod($controller_method);
        }//if
        
        $request->setControllerMethodArgs(array($e));
        $result = $request->handle();
        
      }else{
        
        throw new RuntimeException(
          sprintf(
            'No error controller so the exception %e (code: %s, message: %s) could not be resolved: %s',
            get_class($e),
            $e->getCode(),
            $e->getMessage(),
            $e
          )
        );
      
      }//if/else
    
    }//try/catch
    
    // profile...
    if($debug){ montage_profile::start('Response'); }//if
    
    $response = montage::getResponse();
     
    if(!headers_sent()){
      
      // send the content type header... 
      header(
        sprintf(
          'Content-Type: %s; charset=%s',
          $response->getContentType(),
          MONTAGE_CHARSET
        )
      );
      
      // send the status code header...
      header(
        sprintf(
          '%s %s',
          $request->getServerField('SERVER_PROTOCOL','HTTP/1.0'),
          $response->getStatus()
        )
      );
      
      if($response->isStatusCode(401)){
        header('WWW-Authenticate: Basic realm="Please Log In"');
      }//if
      
    }//if
    
    if(is_bool($result)){
    
      if($result === true){
    
        // actually render the view using the template info...
        $template = $response->getTemplateInstance();
        $template->out(montage_template::OPTION_OUT_STD);
        
      }else{
        // no output is sent if controller returns false
      }//if/else
    
    }else if(is_string($result)){
    
      // it's a string, so just echo it to the screen and be done...
      echo $result;
    
    }else{
    
      throw new UnexpectedValueException(
        sprintf(
          'the controller method (%s::%s) returned a value that was neither a boolean or a string, it was a %s',
          $controller_class_name,
          $controller_method,
          gettype($result)
        )
      );
    
    }//if/else if/else
    
    // profile...
    if($debug){ montage_profile::stop(); }//if
    
    // profile...
    if($debug){ montage_profile::stop(); }//if
    
  }//method

  /**
   *  return the montage_request instance
   *  
   *  @return montage_request      
   */
  static function getRequest(){ return self::getField('montage_request'); }//method

  /**
   *  return the montage_response instance
   *  
   *  @return montage_response      
   */
  static function getResponse(){ return self::getField('montage_response'); }//method
  
  /**
   *  return the montage_settings instance
   *  
   *  @return montage_settings      
   */
  static function getSettings(){ return self::getField('montage_settings'); }//method
  
  /**
   *  return the montage_url instance
   *  
   *  @return montage_url      
   */
  static function getUrl(){ return self::getField('montage_url'); }//method
  
  /**
   *  return the montage_log instance
   *  
   *  @return montage_log      
   */
  static function getLog(){ return self::getField('montage_log'); }//method
  
  /**
   *  return the montage_session instance
   *  
   *  @return montage_session      
   */
  static function getSession(){ return self::getField('montage_session'); }//method

}//class     
