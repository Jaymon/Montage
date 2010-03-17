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
   *  called from the main index.php
   *  file, this is really the only thing that needs to be called, everything else
   *  will take care of itself      
   */
  static function handle(){
  
    $debug = self::getSettings()->getDebug();
    
    // profile...
    if($debug){ montage_profile::start(__METHOD__); }//if
  
    try{
      
      ///out::e($class,$method); return;
      
      // profile...
      if($debug){ montage_profile::start('filters start'); }//if
      
      $filter_list = montage_core::getFilterClassNames();
      
      // get all the filters and start them...
      ///$filter_list = array_map(array('montage_core','getInstance'),montage_core::getFilters());
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
      
      $controller_class_name = $controller_method = '';
      
      // profile...
      if($debug){ montage_profile::start('controller'); }//if
      
      while(true){
        
        try{
          
          $request = self::getRequest();
          
          // create the controller and call its requested method...
          $controller_class_name = $request->getControllerClass();
          $controller_method = $request->getControllerMethod();
          $controller_method_args = $request->getControllerMethodArgs();
          $controller = new $controller_class_name();
          $result = call_user_func_array(array($controller,$controller_method),$controller_method_args);
          $controller->stop();
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
      
      // run all the filters again...
      foreach($filter_list as $filter_instance){
        $filter_instance->stop();
      }//foreach
      
      // profile...
      if($debug){ montage_profile::stop(); }//if
      
      $response = montage::getResponse();
      
      // send the content type header...  
      if(!headers_sent()){
        header(
          sprintf(
            'Content-Type: %s; charset=%s',
            $response->getContentType(),
            MONTAGE_CHARSET
          )
        );
      }//if
      
      // @tbi status code (eg, 404) header needs to be sent
      
      if(is_bool($result)){
      
        // profile...
        if($debug){ montage_profile::start('render view'); }//if
      
        if($result === true){
      
          // actually render the view...
          
          ///if(!headers_sent()){ header("Content-Type: text/html"); }//if
          
          $template = $response->getTemplateInstance();
          $template->out(montage_template::OPTION_OUT_STD);
          
        }else{
          // @tbi do something if controller returned false, not sure what to do
        }//if/else
        
        // profile...
        if($debug){ montage_profile::stop(); }//if
      
      }else if(is_string($result)){
      
        // profile...
        if($debug){ montage_profile::start('echo response'); }//if
      
        // it's a string, so just echo it to the screen and be done...
        echo $result;
        
        // profile...
        if($debug){ montage_profile::stop(); }//if
      
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
      
    }catch(montage_redirect_exception $e){
    
      // we don't really need to do anything since the redirect header should have been called
    
    }catch(Exception $e){
    
      self::getLog()->setException($e);
      
      // @tbi so we really want to re-throw or should we check for an error controller?
      throw $e;
    
    }//try/catch
    
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
