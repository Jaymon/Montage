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
   *  start the main montage instance, this will allow access to most of the montage features
   *
   *  @param  string  $request_root_path  usually the app's web/ directory   
   *  @param  array $load_path_list a list of paths that can be autoloaded   
   */
  static function start(){
  
    $class_name = montage_wizard::getCoreClassName('montage_request');
    self::setField(
      'montage_request',
      new $class_name(
        montage_wizard::getCustomPath(
          montage_wizard::getAppPath(),
          'web'
        )
      )
    );
    
    $class_name = montage_wizard::getCoreClassName('montage_response');
    self::setField(
      'montage_response',
      new $class_name(
        montage_wizard::getCustomPath(
          montage_wizard::getAppPath(),
          'view'
        )
      )
    );
    
    $class_name = montage_wizard::getCoreClassName('montage_settings');
    self::setField('montage_settings',new $class_name());
    
    $class_name = montage_wizard::getCoreClassName('montage_url');
    self::setField('montage_url',new $class_name());
    self::getUrl()->setRoot(
      self::getRequest()->getHost(),
      self::getRequest()->getPath()
    );
    
  }//method
  
  /**
   *  this is what will actually handle the request, 
   *  
   *  called from the main index.php
   *  file, this is really the only thing that needs to be called, everything else
   *  will take care of itself      
   */
  static function handle(){
  
    try{
    
      $request = self::getRequest();
    
      $class = $request->getControllerClass();
      $method = $request->getControllerMethod();
      
      ///out::e($class,$method); return;
      
      // get all the filters and start them...
      $filter_list = montage_wizard::getFilters();
      foreach($filter_list as $key => $filter_class_name){
        $filter_list[$key] = new $filter_class_name();
        $filter_list[$key]->start();
      }//foreach
      
      $controller = new $class();
      $controller->start();
    
      if(!method_exists($controller,$method)){
        $request->killControllerMethod();
        $method = $request->getControllerMethod();
      }//if
    
      $result = call_user_func(array($controller,$method));
      $controller->stop();
      
      // run all the filters again...
      foreach($filter_list as $filter_instance){
        $filter_instance->stop();
      }//foreach
      
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
      
        if($result === true){
      
          // actually render the view...
          
          ///if(!headers_sent()){ header("Content-Type: text/html"); }//if
          
          $template = $response->getTemplateInstance();
          $template->out(montage_template::OPTION_OUT_STD);
          
        }else{
          // @tbi do something if controller returned false, not sure what to do
        }//if/else
      
      }else if(is_string($result)){
      
        // it's a string, so just echo it to the screen and be done...
        echo $result;
      
      }else{
      
        throw new UnexpectedValueException(
          sprintf(
            'the controller method (%s:%s) returned a value that was neither a boolean or a string, it was a %s',
            $class,
            $method,
            gettype($result)
          )
        );
      
      }//if/else if/else
      
    }catch(Exception $e){
    
      // @tbi logging?
      throw $e;
    
    }//try/catch
    
  }//method

  /**
   *  return the montage_request instance
   */
  static function getRequest(){ return self::getField('montage_request'); }//method

  /**
   *  return the montage_response instance
   */
  static function getResponse(){ return self::getField('montage_response'); }//method
  
  /**
   *  return the montage_settings instance
   */
  static function getSettings(){ return self::getField('montage_settings'); }//method
  
  /**
   *  return the montage_url instance
   */
  static function getUrl(){ return self::getField('montage_url'); }//method

}//class     
