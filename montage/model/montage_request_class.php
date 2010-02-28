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

  const FIELD_CONTROLLER = 'montage_request_controller';
  const FIELD_ENVIRONMENT = 'montage_request_environment';

  /**
   *  initialize the request class
   *  
   *  this will do all the required init stuff and then call start() so if you extend
   *  this class and want to do your own init stuff, put it in start()
   *  
   *  @param  string  $controller the requested controller name
   *  @param  string  $environment  the env that will be used   
   *  @param  string  $request_root_path  most likely the /web/ directory
   */
  final function __construct($controller,$environment,$request_root_path){
  
    $this->setField(self::FIELD_CONTROLLER,$controller);
    $this->setField(self::FIELD_ENVIRONMENT,$environment);
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
    
      // find the controller and method...
      $root_path_list = explode(DIRECTORY_SEPARATOR,$request_root_path);
      $request_path_list = preg_split('#\\/#u',$this->getServerField('REQUEST_URI'));
      $path_list = array_values(array_filter(array_diff($request_path_list,$root_path_list)));
      
      // this will contain no fake path parts like controller class and controller method...
      $base_path_list = preg_split('#\\/#u',$this->getServerField('DOCUMENT_ROOT'));
      $base_path_list = array_values(array_filter(array_diff($base_path_list,$root_path_list)));

      if(!empty($path_list[0])){
        
        $controller_class = montage_core::getClassName($path_list[0]);
        
        if(montage_core::isController($controller_class)){
          
          $this->setControllerClass($controller_class);
          
          if(!empty($path_list[1])){
          
            $controller_method = sprintf('get%s',ucfirst(mb_strtolower($path_list[1])));
          
            // if the controller method does not exist then use the default...
            if(method_exists($controller_class,$controller_method)){
            
              $this->setControllerMethod($controller_method);
              
            }//if
            
          }//if
          
        }//if
        
      }//if
      
      // make all the different vars available through the field methods...
      if(!empty($_COOKIE)){ $this->setFields($_COOKIE); }//if
      if(!empty($_SESSION)){ $this->setFields($_SESSION); }//if
      
      $this->setFields($path_list);
      
      // strip out the magic quotes if they exist...
      if(get_magic_quotes_gpc()){
      
        $this->setFields(montage_text::getSlashless($_GET));
        $this->setFields(montage_text::getSlashless($_POST));
        
      }else{
      
        $this->setFields($_GET);
        $this->setFields($_POST);
      
      }//if/else
      
      $this->setField('montage_request_path_list',$path_list);
      
      if(!$this->hasHost()){
        $host = $this->getServerField(array('HTTP_X_FORWARDED_HOST','HTTP_HOST'),'');
        $this->setHost($host);
      }//if
      
      $this->setPath(join('/',$path_list));
      
      // NOTE: REQUEST_URI is the best since it includes any query string, you can't just append
      // the query string to the others either because if you use mod_rewrite, everything will get
      // appended on again
      $this->setUrl(
        sprintf(
          '%s://%s%s',
          $this->isSecure() ? montage_url::SCHEME_SECURE : montage_url::SCHEME_NORMAL,
          $host,
          $this->getServerField('REQUEST_URI')
        )
      );
      
      $this->setBase(
        sprintf(
          '%s://%s%s',
          montage_url::SCHEME_NORMAL,
          $host,
          empty($base_path_list) ? '' : join('/',$base_path_list)
        )
      );
      
      $this->setMethod($this->getServerField('REQUEST_METHOD','GET'));
      
    }//if/else
    
    $this->start();
    
  }//method
  
  final function getController(){ return $this->getField(self::FIELD_CONTROLLER,''); }//method
  final function getEnvironment(){ return $this->getField(self::FIELD_ENVIRONMENT,''); }//method

  /**
   *  the full requested base, the base is different than url because url would have the generated
   *  path on it (eg, controller_class/controller_method/...) and this won't   
   */        
  function setBase($val){ return $this->setField('montage_request_base',$val); }//method
  function getBase(){ return $this->getField('montage_request_base',''); }//method
  function hasBase(){ return $this->hasField('montage_request_base'); }//method

  /**
   *  the full requested url
   */        
  function setUrl($val){ return $this->setField('montage_request_url',$val); }//method
  function getUrl(){ return $this->getField('montage_request_url',''); }//method
  function hasUrl(){ return $this->hasField('montage_request_url'); }//method

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
   *  @param  string|array  $key_list the name of the variable to find, this can either be a string
   *                                  (eg, 'HTTP_HOST') or a list of strings (eg, array('FOO','BAR'))   
   *  @param  mixed $default_val  if $key isn't found, return $default_val
   *  @return mixed
   */
  function getServerField($key_list,$default_val = null){
  
    // canary...
    if(empty($key_list)){ return $default_val; }//if
    if(!is_array($key_list)){ $key_list = array($key_list); }//if
  
    $ret_val = $default_val;
    
    foreach($key_list as $key){
      
      if(isset($_SERVER[$key])){
        $ret_val = $_SERVER[$key];
        break;
      }else if(isset($_ENV[$key])){
        $ret_val = $_ENV[$key];
        break;
      }//if/else if
      
    }//foreach
  
    return $ret_val;
  
  }//method
  
  /**
   *  return a header field if it is found
   *  
   *  php attaches HTTP_ to all header fields and also cahnges dash to underscore, 
   *  this accounts for stuff like that
   *  
   *  @param  string  $header_name  the header name to get
   *  @return string  the header value (the part to the right of the colon)
   */
  function getHeaderField($header_name){
  
    // canary...
    if(empty($header_name)){ return ''; }//if
    
    $header_name = sprintf('HTTP_%s',str_replace('-','_',$header_name));
    return $this->getServerField($header_name,'');
  
  }//method
  
  /**
   *  return all the GET fields
   *  
   *  @return array
   */
  function getGetFields(){
    return empty($_GET) ? array() : montage_text::getSlashless($_GET);
  }//method
  
  /**
   *  return all the POST fields
   *  
   *  @return array
   */
  function getPostFields(){
    return empty($_POST) ? array() : montage_text::getSlashless($_POST);
  }//method
  
  /**
   *  return all the COOKIE fields
   *  
   *  @return array
   */
  function getCookieFields(){
    return empty($_COOKIE) ? array() : montage_text::getSlashless($_COOKIE);
  }//method
  
  /**
   *  return all the SESSION fields
   *  
   *  @return array
   */
  function getSessionFields(){
    return empty($_SESSION) ? array() : montage_text::getSlashless($_SESSION);
  }//method
  
  /**
   *  forwards this request to another controller::method
   *  
   *  this is internally (ie, browser url will not change). If you want to send the visitor
   *  to another url then call montage_response::redirect()
   *
   *  @param  string  $controller the name of the controller child to forward to (this can be a class_key also)
   *  @param  string  $method the method of $controller that will be called   
   *  @throws montage_forward_exception if $controller and $method are valid
   */
  function forward($controller,$method){
  
    // canary...
    if(empty($controller)){
      throw new UnexpectedValueException('$controller cannot be empty');
    }//if
    if(empty($method)){
      throw new UnexpectedValueException('$method cannot be empty');
    }//if
    if(!montage_core::isController($controller)){
      throw new DomainException('$controller is not a valid montage_controller child');
    }//if
    if(!method_exists($controller,$method)){
      throw new BadMethodCallException(sprintf('%s::%s does not exist',$controller,$method));
    }//if
  
    $this->setControllerClass($controller);
    $this->setControllerMethod($method);
    throw new montage_forward_exception();
  
  }//method
  
  /**
   *  Returns true if the request is a XMLHttpRequest.
   *
   *  It works if your JavaScript library set an X-Requested-With HTTP header.
   *  Works with Prototype, Mootools, jQuery, and perhaps others.
   *
   *  @return bool true if the request is an XMLHttpRequest, false otherwise
   */
  function isAjax(){
    return (mb_stripos($this->getServerField('HTTP_X_REQUESTED_WITH'), 'XMLHttpRequest') !== false);
  }//method
  
  /**
   *  get the browser's user agent string
   *  
   *  @return string  the user agent (eg, Mozilla/5.0 (Windows; U; Windows NT 5.1;) Firefox/3.0.17)
   */
  function getUserAgent(){
    return $this->getServerField('HTTP_USER_AGENT','');
  }//method
  
  /**
   *  Returns referer.
   *
   *  @return string
   */
  function getReferer(){
    return $this->getServerField('HTTP_REFERER','');
  }//method
  
  /**
   *  Returns the requested script name
   *
   *  @return string
   */
  function getFile(){
    return $this->getServerField(array('SCRIPT_FILENAME','SCRIPT_NAME','ORIG_SCRIPT_NAME'),'');
  }//method

}//class     

