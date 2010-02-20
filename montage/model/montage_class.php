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
final class montage {

  static $class_map = array();

  /**
   *  start the main montage instance, this will allow access to most of the montage features
   *
   *  @param  string  $request_root_path  usually the app's web/ directory   
   *  @param  array $load_path_list a list of paths that can be autoloaded   
   */
  static function start($request_root_path,$load_path_list = array()){
  
    // the loader needs to be first since it's used to do autoloading...
    self::$class_map['loader'] = new montage_load();
    // add the path list...
    foreach($load_path_list as $load_path){
      self::$class_map['loader']->setPath($load_path);
    }//method
    
    self::$class_map['request'] = new montage_request($request_root_path);
    
  
  
  }//method
  
  /**
   *  this is what will actually handle the request, 
   *  
   *  called from the main index.php
   *  file, this is really the only thing that needs to be called, everything else
   *  will take care of itself      
   */
  function handle(){
  
    try{
    
      $request = self::getRequest();
    
      $class = $request->getClass();
      $method = $request->getMethod();
      
      ///out::e($class,$method); return;
      
      $controller = new $class();
      $controller->start();
      
      // @tbi if the $class doesn't exted montage_controller throw an exception
    
      if(!method_exists($controller,$method)){
        $request->killMethod();
        $method = $request->getMethod();
      }//if
    
      $result = call_user_func(array($controller,$method));
        
      $controller->stop();
      
    }catch(Exception $e){
    
      // @tbi logging?
      throw $e;
    
    }//try/catch
    
  }//method

  /**
   *  return the montage_request instance
   */
  static function getRequest(){ return self::getClass('request'); }//method

  /**
   *  
   */
  static function getResponse(){}//method
  
  /**
   *  
   */
  static function getConfig(){}//method
  
  /**
   *  get the montage_load instance
   *  @return montage_load   
   */
  static function getLoader(){ return self::getClass('loader'); }//method

  /**
   *  get the class in the $class_map specified at the $class key
   *  
   *  @param  string  $class  the key where the class can be foun in $class_map
   *  @return object|null          
   */
  static function getClass($class){
    return empty(self::$class_map[$class]) ? null : self::$class_map[$class];
  }//method

}//class     
