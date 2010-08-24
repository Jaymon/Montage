<?php

/**
 *  all the request information
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-19-10
 *  @package montage 
 ******************************************************************************/
class montage_request extends montage_base {

  const FIELD_CONTROLLER = 'montage_request::controller';
  const FIELD_ENVIRONMENT = 'montage_request::environment';

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
    $path_list = array();
    
    // are we in CLI?
    // http://www.php.net/manual/en/features.commandline.php#93248
    if(strncasecmp(PHP_SAPI, 'cli', 3) === 0){
    
      $this->setMethod('CLI');
      
      // make all the command line arguments available through the field methods...
      if(!empty($_SERVER['argv'])){
        
        $arg_map = array_slice($_SERVER['argv'],1); // get rid of the script that was called
        if(!empty($arg_map)){
        
          if($arg_map[0][0] == '-'){
          
            throw UnexpectedValueException(
              'You are missing class/method (class should extend montage_controller). '
              .'A cli request must be made the following way: '
              .' php path/to/script.php class/method --key=val --key2=val2 ...'
            ); 
          
          }else{
            $path_list = array_values(array_filter(preg_split('#\\/#u',$arg_map[0])));
            $arg_map = array_slice($arg_map,1); // strip off the controller path
          }//if
        
          $arg_map = montage_cli::parseArgv($arg_map);
          $this->setFields($arg_map);

        }//if
        
      }//if

    }else{
      
      $this->setMethod($this->getServerField('REQUEST_METHOD','GET'));
      
      // strip out the magic quotes if they exist...
      if(get_magic_quotes_gpc()){
      
        $this->setFields(montage_text::killSlashes(array_merge($_GET,$_POST)));
      
      }else{
      
        $this->setFields(array_merge($_GET,$_POST));
      
      }//if/else
      
      // get the root of the request...
      $root_path_list = explode(DIRECTORY_SEPARATOR,$request_root_path);
      
      // we don't care about the GET vars, so ignore them...
      $request_uri = $this->getServerField('REQUEST_URI');
      $request_uri = explode('?',$request_uri);
      $request_path_list = preg_split('#\\/#u',$request_uri[0]);
      
      // find the difference between ROOT and REQUEST and that will decide the controller::method to use...
      $path_list = montage_path::getIntersection($request_path_list,$root_path_list);
      
      if(!$this->hasHost()){
        $host = $this->getServerField(array('HTTP_X_FORWARDED_HOST','HTTP_HOST'),'');
        $this->setHost($host);
      }//if
      
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
      
      // see if we have any base path on top of the host...
      $base_path = $this->getServerField(array('SCRIPT_NAME','PHP_SELF'),'');
      if(!empty($base_path)){
        $base_path = join('/',array_slice(preg_split('#\\/#u',$base_path),0,-1));
      }//if
      
      $this->setBase(
        sprintf(
          '%s://%s%s',
          montage_url::SCHEME_NORMAL,
          $host,
          $base_path
        )
      );
      
    }//if/else
    
    $this->setField('montage_request::path_list',$path_list);
    $this->setPath(join('/',$path_list));
    
    // set the controller...
    $forward = montage_factory::getBestInstance('montage_forward');
    $controller_map = $forward->find($path_list); 
    $this->setControllerClass($controller_map[0]);
    $this->setControllerMethod($controller_map[1]);
    $this->setControllerMethodArgs($controller_map[2]);
    
    $this->setFields($path_list);
    
    $this->start();
    
  }//method
  
  /**
   *  handle the request
   *  
   *  this will use the set controller, method, and args to instantiate the controller
   *  and handle the request. Basically calling: 
   *  getControllerClass()::getControllerMethodName()(getControllerMethodArgs) or
   *  $controller::$method($args)
   *  
   *  @return boolean
   */
  function handle(){
  
    // create the controller and call its requested method...
    $controller_class_name = $this->getControllerClass();
    $controller_method = $this->getControllerMethod();
    $controller_method_args = $this->getControllerMethodArgs();
    
    // canary...
    if(empty($controller_class_name)){
      throw new UnexpectedValueException('no controller class to handle the request was found');
    }//if
    if(empty($controller_method)){
      throw new UnexpectedValueException(
        sprintf('no method found in controller class %s to handle the request',$controller_class_name)
      );
    }//if
    
    // make sure there are enough required method_args...
    $arg_count = empty($controller_method_args) ? 0 : count($controller_method_args);
    $rm = new ReflectionMethod($controller_class_name,$controller_method);
    $arg_required_count = $rm->getNumberOfRequiredParameters();
    if($arg_required_count > $arg_count){
      throw new LengthException(
        sprintf(
          '%s::%s expects %s arguments to be passed to it, but only got %s args',
          $controller_class_name,
          $controller_method,
          $arg_required_count,
          $arg_count
        )
      );
    }//if
    
    $event = montage::getEvent();
    $event->broadcast(
      montage_event::KEY_INFO,
      array('msg' => 
        sprintf(
          'controller %s::%s called',
          $controller_class_name,
          $controller_method
        )
      )
    );
    
    $controller = new $controller_class_name();
    $ret_mixed = call_user_func_array(array($controller,$controller_method),$controller_method_args);
    $controller->stop();
    
    return $ret_mixed;
  
  }//method
  
  /**
   *  get a $form_name instance
   *  
   *  @param  string|object $form the name of the form class instance that should be returned.
   *                              or an actual instance of the form_class that will be populated
   *                              with the values. A form class is any class that extends montage_form         
   *  @return montage_form  a child of montage_form, null if none was found
   */
  function getForm($form){
    
    if($form instanceof montage_form){
    
      $form->set($this->getField($form->getName(),array()));
    
    }else{
      
      $form_name = (string)$form;
      $form = null;
      
      // make sure the namespace is a valid form class...
      if(montage_core::isForm($form_name)){
        
        // create the form isntance to wrap the values...
        $field_map = $this->getField($form_name,array());
        $form_class_name = montage_core::getClassName($form_name);
        $form = new $form_class_name($field_map);
        
      }//if
      
    }//if/else
    
    return $form;
    
  }//method
  function hasForm($form_name){ return $this->hasField($form_name); }//method
  
  /**
   *  returns the controller that will be used in this request
   *  
   *  the controller is usually the folder in the /controller folder where all the
   *  controller classes are found
   *  
   *  @return string  the controller name
   */
  final function getController(){ return $this->getField(self::FIELD_CONTROLLER,''); }//method
  
  /**
   *  the environment is usually something like: prod, dev, debug, etc.
   *  
   *  @return string  the name of the environment being used
   */
  final function getEnvironment(){ return $this->getField(self::FIELD_ENVIRONMENT,''); }//method

  /**
   *  the full requested base, the base is different than url because url would have the generated
   *  path on it (eg, controller_class/controller_method/...) and this won't   
   */        
  function setBase($val){ return $this->setField('montage_request::base',$val); }//method
  function getBase(){ return $this->getField('montage_request::base',''); }//method
  function hasBase(){ return $this->hasField('montage_request::base'); }//method

  /**
   *  the full requested url
   */        
  function setUrl($val){ return $this->setField('montage_request::url',$val); }//method
  function getUrl(){ return $this->getField('montage_request::url',''); }//method
  function hasUrl(){ return $this->hasField('montage_request::url'); }//method

  /**
   *  usually something like: example.com or subdomain.example.com
   */        
  function setHost($val){ return $this->setField('montage_request::host',$val); }//method
  function getHost(){ return $this->getField('montage_request::host',''); }//method
  function hasHost(){ return $this->hasField('montage_request::host'); }//method

  /**
   *  if the path of the montage request isn't just the root directory (eg, /) then
   *  that path will be here
   */
  function setPath($val){ return $this->setField('montage_request::path',$val); }//method
  function getPath(){ return $this->getField('montage_request::path',''); }//method
  function hasPath(){ return $this->hasField('montage_request::path'); }//method

  /**
   *  corresponds to the [APP PATH]/web/ directory
   */        
  function setRequestRoot($val){ return $this->setField('montage_request::request_root',$val); }//method
  function getRequestRoot(){ return $this->getField('montage_request::request_root',''); }//method
  
  /**
   *  the requested controller class that will be used to answer this request
   */
  protected function setControllerClass($val){ return $this->setField('montage_request::controller_class',$val); }//method
  function getControllerClass(){ return $this->getField('montage_request::controller_class',''); }//method
  
  /**
   *  the requested controller method that will be used to answer this request
   */
  protected function setControllerMethod($val){ return $this->setField('montage_request::controller_method',$val); }//method
  function getControllerMethod(){ return $this->getField('montage_request::controller_method',''); }//method
  
  /**
   *  the arguments that will be passed into the controller::method
   */
  function setControllerMethodArgs($val){ return $this->setField('montage_request::controller_method_args',$val); }//method
  function getControllerMethodArgs(){ return $this->getField('montage_request::controller_method_args',array()); }//method
  function hasControllerMethodArgs(){ return $this->hasField('montage_request::controller_method_args'); }//method
  
  /**
   *  the method used for this request (eg, GET, POST, CLI)
   */        
  function setMethod($val){ return $this->setField('montage_request::method',mb_strtoupper($val)); }//method
  function getMethod(){ return $this->getField('montage_request::method',''); }//method
  function hasMethod(){ return $this->hasField('montage_request::method'); }//method
  function isMethod($val){ return $this->isField('montage_request::method',mb_strtoupper($val)); }//method
  
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
    return empty($_GET) ? array() : montage_text::killSlashes($_GET);
  }//method
  
  /**
   *  return all the POST fields
   *  
   *  @return array
   */
  function getPostFields(){
    return empty($_POST) ? array() : montage_text::killSlashes($_POST);
  }//method
  
  /**
   *  return all the COOKIE fields
   *  
   *  @return array
   */
  function getCookieFields(){
    return empty($_COOKIE) ? array() : montage_text::killSlashes($_COOKIE);
  }//method
  
  /**
   *  return all the SESSION fields
   *  
   *  @return array
   */
  function getSessionFields(){
    return empty($_SESSION) ? array() : montage_text::killSlashes($_SESSION);
  }//method
  
  /**
   *  forwards this request to another $controller::$method
   *  
   *  this is internally (ie, browser url will not change). If you want to send the visitor
   *  to another url then call {@link montage_response::redirect()}
   *
   *  @see  setHandler()   
   *  @param  string  $controller the name of the controller child to forward to (this can be a class_key also)
   *  @param  string  $method the method of $controller that will be called
   *  @param  array $args a list of values to pass into the $controller::$method     
   *  @throws montage_forward_exception if $controller and $method are valid
   */
  function forward($controller_class_name,$method,$args = array()){
  
    if($this->setHandler($controller_class_name,$method,$args)){
    
      throw montage_factory::getBestInstance('montage_forward_exception');
      
    }//if
  
  }//method
  
  /**
   *  forwards this request to the error handler for $e
   *  
   *  @see  setErrorHandler()
   *  @param  Exception $e  the exception whose controller::method needs to be forwarded to     
   *  @throws montage_forward_exception if $controller and $method are valid
   */
  function forwardError(Exception $e){
  
    if($this->setErrorHandler($e)){
    
      throw montage_factory::getBestInstance('montage_forward_exception');
      
    }//if
  
  }//method
  
  /**
   *  sets the $controller::$method
   *  
   *  @param  string  $controller_class_name the name of the controller child (this can be a class_key also)
   *  @param  string  $method the method of $controller that will be called
   *  @param  array $args a list of values to pass into the $controller::$method
   */
  function setHandler($controller_class_name,$method,$args = array()){
  
    $forward = montage_factory::getBestInstance('montage_forward');
    $controller_map = $forward->get($controller_class_name,$method);
  
    $this->setControllerClass($controller_map[0]);
    $this->setControllerMethod($controller_map[1]);
    $this->setControllerMethodArgs($args);
    return true;
  
  }//method
  
  /**
   *  sets the $controller::$method that will handle the error
   *  
   *  @param  Exception $e  the exception that needs to be handled
   */
  function setErrorHandler(Exception $e){
  
    $forward = montage_factory::getBestInstance('montage_forward');
    $controller_map = $forward->getError($e);
  
    $this->setControllerClass($controller_map[0]);
    $this->setControllerMethod($controller_map[1]);
    $this->setControllerMethodArgs(array($e));
    return true;
  
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
  
  /**
   *  this function aggregates some information about the current request, handy for
   *  getting detailed info in case of error or something           
   *
   *  @return string  information about the request
   */
  function getInfo(){
  
    $ret_map = array();
    
    // save some general info about the request...
    $ret_map['request'] = array();
    
    $ret_map['request']['url'] = $this->getUrl();
    $ret_map['request']['referrer'] = $this->getReferer();
    $ret_map['request']['controller_class'] = $this->getControllerClass();
    $ret_map['request']['controller_method'] = $this->getControllerMethod();
    $ret_map['request']['host'] = $this->getHost();
    $ret_map['request']['ajax'] = $this->isAjax() ? 'TRUE' : 'FALSE';
    $ret_map['request']['script'] = $this->getFile();
    $ret_map['request']['cli'] = $this->isCli() ? 'TRUE' : 'FALSE';
    $ret_map['request']['User-Agent'] = $this->getUserAgent();
    
    if($this->hasServerField('REQUEST_URI')){
      $ret_map['request']['REQUEST_URI'] = $this->getServerField('REQUEST_URI','');
    }//if
    
    if($this->isCli()){
    
      if(!empty($_SERVER['argv'])){
        $ret_map['argv'] = $_SERVER['argv'];
      }//if
      
    }else{
    
      $ret_map['request']['ip_address'] = $this->getIp();
    
    }//if/else
    
    // add the variables...
  
    if(!empty($_GET)){
      $ret_map['_GET'] = $this->getGetFields();
    }//if
    
    if(!empty($_POST)){
      $ret_map['_POST'] = $this->getPostFields();
    }//if
    
    if(!empty($_SESSION)){
      $ret_map['_SESSION'] = $this->getSessionFields();
    }//if
    
    if(!empty($_COOKIE)){
      $ret_map['_COOKIE'] = $this->getCookieFields();
    }//if
      
    return $ret_map;
  
  }//method */

}//class     

