<?php

/**
 *  all the request information
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-19-10
 *  @package montage 
 ******************************************************************************/
class montage_request extends montage_base {

  final function __construct($request_root_path){
  
    $this->setRoot($request_root_path);
    $this->start();
  }//method

  function start(){
  
    // are we in CLI?
    if(strncasecmp(PHP_SAPI, 'cli', 3) === 0){
    
      $this->setMethod('CLI');
      throw new exception('in CLI, tbi');
      
      // make all the command line arguments available through the field methods...
      if(!empty($_SERVER['argv'])){
        $this->setFields($_SERVER['argv']);
      }//if
      
    }else{
    
      $root_path_list = explode(DIRECTORY_SEPARATOR,$this->getRoot());
      $request_path_list = preg_split('#\\/#u',$this->getServerField('REQUEST_URI'));
      $uri_path_list = array_values(array_filter(array_diff($request_path_list,$root_path_list)));

      if(!empty($uri_path_list[0])){
        
        if(montage_wizard::isController($uri_path_list[0])){
          
          $this->setControllerClass(mb_strtolower($uri_path_list[0]));
          
          if(!empty($uri_path_list[1])){
            $this->setControllerMethod(sprintf('get%s',ucfirst(mb_strtolower($uri_path_list[1]))));
          }//if
          
        }//if
        
      }//if
      
      // make all the different vars available through the field methods...
      if(!empty($_COOKIE)){ $this->setFields($_COOKIE); }//if
      if(!empty($_SESSION)){ $this->setFields($_SESSION); }//if
      
      $this->setFields($uri_path_list);
      $this->setFields($_GET);
      $this->setFields($_POST);
      
      $this->setField('montage_request_uri_path_list',$uri_path_list);
      
      $this->setMethod($this->getServerField('REQUEST_METHOD','GET'));
      
    }//if/else
    
  }//method

  function setRoot($val){ return $this->setField('montage_request_root',$val); }//method
  function getRoot(){ return $this->getField('montage_request_root',''); }//method
  function hasRoot(){ return $this->hasField('montage_request_root'); }//method
  
  function setControllerClass($val){ return $this->setField('montage_request_controller_class',$val); }//method
  function getControllerClass(){ return $this->getField('montage_request_controller_class',$this->getDefaultControllerClass()); }//method
  function hasControllerClass(){ return $this->hasField('montage_request_controller_class'); }//method
  protected function getDefaultControllerClass(){ return 'montage_request_controller_index'; }//method
  
  function setControllerMethod($val){ return $this->setField('montage_request_controller_method',$val); }//method
  function getControllerMethod(){ return $this->getField('montage_request_controller_method',$this->getDefaultControllerMethod()); }//method
  function hasControllerMethod(){ return $this->hasField('montage_request_controller_method'); }//method
  function killControllerMethod(){ return $this->killField('montage_request_controller_method'); }//method
  protected function getDefaultControllerMethod(){ return 'getIndex'; }//method
  
  function setMethod($val){ return $this->setField('montage_request_method',mb_strtoupper($val)); }//method
  function getMethod(){ return $this->getField('montage_request_method',''); }//method
  function hasMethod(){ return $this->hasField('montage_request_method'); }//method
  function isMethod($val){ return $this->isField('montage_request_method',mb_strtoupper($val)); }//method
  
  function isCli(){ return $this->isMethod('CLI'); }//method
  function isPost(){ return $this->isMethod('POST'); }//method
  
  /**
   *  checks both $_SERVER and $_ENV for a value
   *  
   *  @param  string  $key  the name of the variable to find
   *  @param  mixed $default_val  if $key isn't found, return $default_val
   *  @return mixed
   */
  function getServerField($key,$default_val = null){
  
    // canary...
    if(empty($key)){ return $default_val; }//if
  
    $ret_val = $default_val;
    
    if(isset($_SERVER[$key])){
      $ret_val = $_SERVER[$key];
    }else if(isset($_ENV[$key])){
      $ret_val = $_ENV[$key];
    }//if/else if
  
    return $ret_val;
  
  }//method

}//class     

