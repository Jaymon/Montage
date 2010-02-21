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
    $this->setCLI(strncasecmp(PHP_SAPI, 'cli', 3) === 0);
  
    if($this->isCLI()){
    
      throw new exception('in CLI, tbi');
    
    }else{
    
      $root_path_list = explode(DIRECTORY_SEPARATOR,$this->getRoot());
      $request_path_list = preg_split('#\\/#u',$this->getServerField('REQUEST_URI'));
      $uri_path_list = array_values(array_filter(array_diff($request_path_list,$root_path_list)));

      if(!empty($uri_path_list[0])){
        
        if(montage_wizard::isController($uri_path_list[0])){
          
          $this->setClass(mb_strtolower($uri_path_list[0]));
          
          if(!empty($uri_path_list[1])){
            $this->setMethod(sprintf('get%s',ucfirst(mb_strtolower($uri_path_list[1]))));
          }//if
          
        }//if
        
      }//if
      
      $this->setField('uri_path_list',$uri_path_list);
      
    }//if/else
    
  }//method

  function setRoot($val){ return $this->setField('root',$val); }//method
  function getRoot(){ return $this->getField('root',''); }//method
  function hasRoot(){ return $this->hasField('root'); }//method
  
  function setClass($val){ return $this->setField('class',$val); }//method
  function getClass(){ return $this->getField('class',$this->getDefaultClass()); }//method
  function hasClass(){ return $this->hasField('class'); }//method
  protected function getDefaultClass(){ return 'index'; }//method
  
  function setMethod($val){ return $this->setField('method',$val); }//method
  function getMethod(){ return $this->getField('method',$this->getDefaultMethod()); }//method
  function hasMethod(){ return $this->hasField('method'); }//method
  function killMethod(){ return $this->killField('method'); }//method
  protected function getDefaultMethod(){ return 'getIndex'; }//method
  
  protected function setCLI($val){ return $this->setField('cli',$val); }//method
  function isCLI(){ return $this->hasField('cli'); }//method
  
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
