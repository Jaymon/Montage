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

  /**
   *  initialize the request class
   *  
   *  this will do all the required init stuff and then call start() so if you extend
   *  this class and want to do your own init stuff, put it in start()
   *  
   *  @param  string  $request_root_path  most likely the /web/ directory
   */
  final function __construct($request_root_path){
  
    $this->setRequestRoot($request_root_path);
    
    // are we in CLI?
    if(strncasecmp(PHP_SAPI, 'cli', 3) === 0){
    
      $this->setMethod('CLI');
      throw new exception('in CLI, tbi');
      
      // make all the command line arguments available through the field methods...
      if(!empty($_SERVER['argv'])){
        $this->setFields($_SERVER['argv']);
      }//if
      
    }else{
    
      $root_path_list = explode(DIRECTORY_SEPARATOR,$request_root_path);
      $request_path_list = preg_split('#\\/#u',$this->getServerField('REQUEST_URI'));
      $path_list = array_values(array_filter(array_diff($request_path_list,$root_path_list)));

      if(!empty($path_list[0])){
        
        if(montage_core::isController($path_list[0])){
          
          $this->setControllerClass(mb_strtolower($path_list[0]));
          
          if(!empty($path_list[1])){
            $this->setControllerMethod(sprintf('get%s',ucfirst(mb_strtolower($path_list[1]))));
          }//if
          
        }//if
        
      }//if
      
      // make all the different vars available through the field methods...
      if(!empty($_COOKIE)){ $this->setFields($_COOKIE); }//if
      if(!empty($_SESSION)){ $this->setFields($_SESSION); }//if
      
      $this->setFields($path_list);
      
      // strip out the magic quotes if they exist...
      if(get_magic_quotes_gpc()){
      
        $this->setFields($this->stripSlashes($_GET));
        $this->setFields($this->stripSlashes($_POST));
        
      }else{
      
        $this->setFields($_GET);
        $this->setFields($_POST);
      
      }//if/else
      
      $this->setField('montage_request_path_list',$path_list);
      
      $host = $this->getServerField('HTTP_HOST','');
      $this->setHost($host);
      $this->setPath(join('/',$path_list));
      
      $this->setMethod($this->getServerField('REQUEST_METHOD','GET'));
      
    }//if/else
    
    $this->start();
    
  }//method

  /**
   *  usually something like: example.com or subdomain.example.com
   */        
  function setHost($val){ return $this->setField('montage_request_host',$val); }//method
  function getHost(){ return $this->getField('montage_request_host',''); }//method
  function hasHost(){ return $this->hasField('montage_request_host'); }//method

  /**
   *  if the path of the montage request isn't just the root directory (eg, /) then
   *  that path will be here
   */
  function setPath($val){ return $this->setField('montage_request_path',$val); }//method
  function getPath(){ return $this->getField('montage_request_path',''); }//method
  function hasPath(){ return $this->hasField('montage_request_path'); }//method

  /**
   *  corresponds to the [APP PATH]/web/ directory
   */        
  function setRequestRoot($val){ return $this->setField('montage_request_request_root',$val); }//method
  function getRequestRoot(){ return $this->getField('montage_request_request_root',''); }//method
  function hasRequestRoot(){ return $this->hasField('montage_request_request_root'); }//method
  
  /**
   *  the requested controller class that will be used to answer this request
   */
  function setControllerClass($val){ return $this->setField('montage_request_controller_class',$val); }//method
  function getControllerClass(){ return $this->getField('montage_request_controller_class',$this->getDefaultControllerClass()); }//method
  function hasControllerClass(){ return $this->hasField('montage_request_controller_class'); }//method
  protected function getDefaultControllerClass(){ return 'index'; }//method
  
  /**
   *  the requested controller method that will be used to answer this request
   */
  function setControllerMethod($val){ return $this->setField('montage_request_controller_method',$val); }//method
  function getControllerMethod(){ return $this->getField('montage_request_controller_method',$this->getDefaultControllerMethod()); }//method
  function hasControllerMethod(){ return $this->hasField('montage_request_controller_method'); }//method
  function killControllerMethod(){ return $this->killField('montage_request_controller_method'); }//method
  protected function getDefaultControllerMethod(){ return 'getIndex'; }//method
  
  /**
   *  the method used for this request (eg, GET, POST, CLI)
   */        
  function setMethod($val){ return $this->setField('montage_request_method',mb_strtoupper($val)); }//method
  function getMethod(){ return $this->getField('montage_request_method',''); }//method
  function hasMethod(){ return $this->hasField('montage_request_method'); }//method
  function isMethod($val){ return $this->isField('montage_request_method',mb_strtoupper($val)); }//method
  
  /**
   *  shortcut method for you to know if this is a command line request
   *  
   *  @return boolean
   */
  function isCli(){ return $this->isMethod('CLI'); }//method
  
  /**
   *  shortcut method to know if this is a POST request
   *  
   *  @return boolean
   */
  function isPost(){ return $this->isMethod('POST'); }//method
  
  /**
   *  Returns true if the current request is secure (HTTPS protocol).
   *
   *  I figured out how to do this from Symfony
   *      
   *  return boolean
   */
  function isSecure(){
  
    $ret_bool = false;
    $val = $this->getServerField('HTTPS',$this->getServerField('HTTP_SSL_HTTPS'));
    if(!empty($val)){
      $ret_bool = (mb_strtolower($val) == 'on') || ($val == 1);
    }//if
    
    if(empty($ret_bool)){
      $val = $this->getServerField('HTTP_X_FORWARDED_PROTO');
      if(!empty($val)){
        $ret_bool = mb_strtolower($val) == 'https';
      }//if
    }//if
  
    return $ret_bool;
  
  }//method
  
  /**
   *  get the visitor's ip address
   *  
   *  this function is from {@link http://wiki.jumba.com.au/wiki/PHP_Get_user_IP_Address}
   *  with help from {@link http://www.php.net/manual/en/language.variables.predefined.php#31724}   
   *   
   *  @return the ip address if found
   */        
  function getIp(){
   
    $ret_str = '';
   
    $ret_str = $this->getServerField('HTTP_X_FORWARDED_FOR','');
    if(empty($ret_str)){
      $ret_str = $this->getServerField('HTTP_CLIENT_IP','');
      if(empty($ret_str)){
        $ret_str = $this->getServerField('REMOTE_ADDR','');
      }//if
    }//if
    
    return trim($ret_str);
    
  }//method
  
  /**
   *  check if a server field exists
   *  
   *  @param  string  $key  the name of the variable to find
   *  @return boolean
   */
  function hasServerField($key){
    $ret_bool = false;
    if(!empty($key)){
      $ret_bool = isset($_SERVER[$key]);
      if(empty($ret_bool)){
        $ret_bool = isset($_ENV[$key]);
      }//if
    }//if
    return $ret_bool;
  }//method
  
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
  
  /**
   *  recursively strip all the slashes from a $val
   *  
   *  @param  mixed $val
   *  @return $val with all slashes stripped
   */
  private function stripSlashes($val)
  {
    // canary...
    if(is_array($val)){ return $this->stripSlashes($val); }//if
    if(is_object($val)){ return $val; }//if
    
    return stripslashes($val);
    
  }//method

}//class     

