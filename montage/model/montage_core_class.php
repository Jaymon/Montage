<?php

/**
 *  handle autoloading duties. While montage is the master class, this class does
 *  all the internal heavy lifting and can be mostly left alone unless you want to
 *  set more class paths (use {@link setPath()}) than what are used by default.
 *  
 *  this class could have easily been called montage_factory
 *  
 *  class paths checked by default:
 *    [MONTAGE DIRECTORY]/model
 *    [APP DIRECTORY]/settings
 *    [MONTAGE DIRECTORY]/plugins
 *    [APP DIRECTORY]/plugins  
 *    [APP DIRECTORY]/model
 *    [APP DIRECTORY]/controller/$controller
 *   
 *  @version 0.3
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
final class montage_core extends montage_base_static {
  
  /**
   *  the "global" settings will be a montage_start child extending class with this name
   */
  const CLASS_NAME_APP_START = 'app';
  
  /**
   *  switched to true in the start() function
   *  @var  boolean
   */
  static private $is_started = false;
  
  /**
   *  switched to true in the handle() function
   *  @var  boolean
   */
  static private $is_handled = false;
  
  /**
   *  hold all the classes that could possibly be loadable
   *  
   *  the structure is: each key is a the class_key name, with path and name key/vals for
   *  each class_key
   *  
   *  @var  array
   */
  static private $class_map = array();
  
  /**
   *  hold the mapped core classes with their best defined class_keys being the value   
   *      
   *  @var  array
   */
  static private $core_class_map = array(
    'MONTAGE_REQUEST' => '',
    'MONTAGE_SETTINGS' => '',
    'MONTAGE_RESPONSE' => '',
    'MONTAGE_URL' => '',
    'MONTAGE_ESCAPE' => '',
    'MONTAGE_TEMPLATE' => '',
    'MONTAGE_SESSION' => '',
    'MONTAGE_COOKIE' => '',
    'MONTAGE_EVENT' => '',
    'MONTAGE_FORWARD' => ''
  );
  
  /**
   *  map all the child classes to their parents
   *  
   *  this is handy for making sure a given class inherits what it should
   *  
   *  @var  array
   */
  static private $parent_class_map = array();
  
  /**
   *  these are the errors that are handled with {@link handleErrorRuntime()}.
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
   *  these errors are handled by the {@link handleErrorFatal()} function
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
   *  start the wizard
   *  
   *  @param  string  $controller the requested controller name
   *  @param  string  $environment  the env that will be used
   *  @param  boolean if debug is on or not
   *  @param  string  $charset
   *  @param  string  $timezone
   *  @param  string  $framework_path corresponds to MONTAGE_PATH constant
   *  @param  string  $app_path corresponds to MONTAGE_APP_PATH constant        
   */
  static function start($controller,$environment,$debug,$charset,$timezone,$framework_path,$app_path){
  
    // profile...
    if($debug){ montage_profile::start(__METHOD__); }//if
  
    // canary...
    if(self::$is_started){
      throw new RuntimeException('The framework core was already started, no point in starting it again');
    }//if
    if(empty($controller)){
      throw new UnexpectedValueException('$controller cannot be empty');
    }//if
    if(empty($environment)){
      throw new UnexpectedValueException('$environment cannot be empty');
    }//if
    if(empty($framework_path)){
      throw new UnexpectedValueException('$framework_path cannot be empty');
    }//if
    if(empty($app_path)){
      throw new UnexpectedValueException('$app_path cannot be empty');
    }//if
  
    // set the default autoloader...
    self::set(array(__CLASS__,'load'));
  
    // save important fields...
    self::setField('montage_core_controller',$controller); // for caching
    self::setField('montage_core_environment',$environment); // for caching
  
    // save the important paths...
    montage_path::setFramework($framework_path);
    montage_path::setApp($app_path);
    montage_path::setCache(montage_path::get($app_path,'cache'));
    montage_cache::setPath(montage_path::getCache());
    
    $loaded_from_cache = self::loadCore();
    if(!$loaded_from_cache){
    
      // profile...
      if($debug){ montage_profile::start('check paths'); }//if
      
      // load the default model directories...
      self::setPath(montage_path::get($framework_path,'model'));
      
      // set the main settings path...
      self::setPath(montage_path::get($app_path,'settings'));
      
      $start_class_list = array();
      
      // throughout building the paths, we need to compile a list of start classes.
      // start classes are classes that extend montage_start.
      // the start classes follow a precedence order: Global, environment, plugins, and controller...
      // * Global is a class named "app" it can't be named "start" because of the start() method trying to override __construct()
      // * environment controller is a class with the same name as $environment, it's before plugins to set db variables
      //    and the like so that plugins (like a db plugin) can take advantage of connection settings
      //    like db name, db username, and db password
      // * plugins are name by what folder they are in (eg, [APP PATH]/plugins/foo/ the plugin is named foo)
      // and the plugin start class is the class with the same name as the root folder
      // (eg, class foo extends montage_start)
      // * controller start class is a class with same name as $controller
      $start_class_name = self::getClassName(self::CLASS_NAME_APP_START);
      if(!empty($start_class_name)){ $start_class_list[] = $start_class_name; }//if
      
      $start_class_name = self::getClassName($environment);
      if(!empty($start_class_name)){ $start_class_list[] = $start_class_name; }//if
      
      // include all the plugin paths, save all the start class names.
      // We include these here before the app model path because they can extend core 
      // but plugin classes should never extend app classes, but app classes can extend
      // plugin classes...
      $plugin_path_list = array_merge(
        montage_path::getDirectories(montage_path::get($framework_path,'plugins'),false),
        montage_path::getDirectories(montage_path::get($app_path,'plugins'),false)
      );
      foreach($plugin_path_list as $plugin_path){
        
        $plugin_name = basename($plugin_path);
        
        // find all the classes in the plugin path...
        self::setPath($plugin_path);
        
        $start_class_name = self::getClassName($plugin_name);
        if(!empty($start_class_name)){ $start_class_list[] = $start_class_name; }//if
        
      }//foreach
      
      $start_class_name = self::getClassName($controller);
      if(!empty($start_class_name)){ $start_class_list[] = $start_class_name; }//if
      
      self::setField('montage_core_start_class_list',$start_class_list);
      
      // load the app's model directory...
      self::setPath(montage_path::get($app_path,'model'));
    
      // load the controller...
      $controller_path = montage_path::get($app_path,'controller',$controller);
      self::setPath($controller_path);
      
      if(empty(self::$parent_class_map['MONTAGE_CONTROLLER'])){
        throw new RuntimeException(
          sprintf(
            join("\r\n",array(
              'the controller (%s) does not have any classes that extend "montage_controller" '
              .'so no requests can be processed. Fix this by adding some classes that extend '
              .'"montage_controller" in the "%s" directory. At the very least, you should have '
              .'an index class to fulfill default requests:',
              '',
              'class index extends montage_controller{',
              '  function %s(){}',
              '}'
            )),
            $controller,
            $controller_path,
            montage_forward::CONTROLLER_METHOD
          )
        );
      }//if
      
      // save all the compiled core classes/paths into the cache...
      self::setCore();
      
      // profile...
      if($debug){ montage_profile::stop(); }//if
    
    }//if
    
    // profile...
    if($debug){ montage_profile::start('initialize core classes'); }//if
    
    // officially start the core global classes of the framework...
    self::startCoreClasses($controller,$environment,$debug,$charset,$timezone);
    
    // set error handlers...
    set_error_handler(array(__CLASS__,'handleErrorRuntime'));
    register_shutdown_function(array(__CLASS__,'handleErrorFatal'));
    
    // profile...
    if($debug){ montage_profile::stop(); }//if
    
    // load the settings directory and "start" the app...
    
    // profile...
    if($debug){ montage_profile::start('settings'); }//if
    
    // now actually start the settings/start classes...
    $start_class_list = self::getField('montage_core_start_class_list',array());
    $start_class_parent_key = 'montage_start';
    foreach($start_class_list as $start_class_name){
      self::getInstance($start_class_name,$start_class_parent_key);
    }//foreach
    
    // profile...
    if($debug){ montage_profile::stop(); }//if
    
    self::$is_started = true;
    
    // profile...
    if($debug){ montage_profile::stop(); }//if
    
  }//method
  
  /**
   *  this is what will actually handle the request, 
   *  
   *  called at the end of the start.php file, this is really the only thing that needs 
   *  to be called, everything else will take care of itself      
   */
  static function handle(){
  
    // canary...
    if(self::$is_handled){
      throw new RuntimeException('The framework core already handled the request, no point in handling it again');
    }//if
  
    self::$is_handled = true;
    $debug = montage::getSettings()->getDebug();
    
    if($debug){ montage_profile::start(__METHOD__); }//if
    
    // get all the filters and start them...
    ///$filter_list = array_map(array('montage_core','getInstance'),montage_core::getFilters());
    $filter_list = self::getFilterClassNames();
    $use_template = self::handleRequest($filter_list);
    
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
  static private function handleRequest($filter_list = array()){
  
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
            
            $filter_list[$key] = self::getInstance($filter_class_name);
            
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
      $use_template = self::handleRequest();
    
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
      $use_template = self::handleRequest();
    
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
  static function handleErrorRuntime($errno,$errstr,$errfile,$errline){
  
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
  static function handleErrorFatal(){
  
    if($error_map = error_get_last()){
    
      if(!isset(self::$ERRORS_RUNTIME[$error_map['type']])){
    
        $error_map['name'] = self::getErrorName($error_map['type']);
      
        // broadcast the error to anyone that is listening...
        montage::getEvent()->broadcast(montage_event::KEY_ERROR,$error_map);
        
      }//if
      
    }//if
    
  }//method
  
  /**
   *  create and return an instance of $class_name
   *  
   *  this only works for classes that don't take any arguments in their constructor
   *      
   *  @param  string  $class_name the name of the class whose instance should be returned
   *  @param  string  $parent_name  the name of the parent class, if not empty then $class_name
   *                                must be a child of $parent_name, otherwise null is returned
   *  @return object
   */
  static function getInstance($class_name,$parent_name = ''){
    $class_name = self::getClassName($class_name,$parent_name);
    return empty($class_name) ? null : new $class_name();
  }//method
  
  /**
   *  create and return the best instance of $class_name
   *  
   *  this only works for classes that don't take any arguments in their constructor
   *      
   *  @param  string  $class_name the name of the class whose instance should be returned
   *  @param  string  $parent_name  the name of the parent class, if not empty then $class_name
   *                                must be a child of $parent_name, otherwise null is returned
   *  @return object
   */
  static function getBestInstance($class_name,$parent_name = ''){
    $class_name = self::getBestClassName($class_name,$parent_name);
    return empty($class_name) ? null : new $class_name();
  }//method
  
  /**
   *  get the class name from the given $class_key
   *  
   *  this is useful because since we standardize all the class names we can find
   *  a class whether we pass in ClassName Classname className. Basically, montage
   *  classes are case-insensitive            
   *  
   *  @param  string  $class_key  
   *  @param  string  $parent_name  the name of the parent class, if not empty then $class_name
   *                                must be a child of $parent_name       
   *  @return string
   */
  static function getClassName($class_key,$parent_name = ''){
    
    // sanity, make sure the class key is in the right format...
    $class_key = self::getClassKey($class_key);
    
    $ret_str = '';
    if(isset(self::$class_map[$class_key])){
      $ret_str = self::$class_map[$class_key]['class_name'];
      
      if(!empty($parent_name)){
        if(!self::isChild($class_key,$parent_name)){
          $ret_str = '';
        }//if
      }//if
      
    }//if
    
    return $ret_str;
    
  }//method
  
  /**
   *  get the absolute most child for the given class
   *  (eg, the last class to extend any class that extends the passed in $class_key)
   *  
   *  @param  string  $class_key
   *  @param  string  $parent_name  the name of the parent class, if not empty then $class_name
   *                                must be a child of $parent_name   
   *  @return string  the child class name   
   *  @throws DomainException if the class_key is extended by more than one unrelated child   
   */
  static function getBestClassName($class_key,$parent_name = ''){
  
    $ret_str = '';
  
    $class_key = self::getClassKey($class_key);
    if(isset(self::$parent_class_map[$class_key])){
    
      $child_class_list = self::$parent_class_map[$class_key];
      foreach($child_class_list as $child_class_key){
      
        if(!isset(self::$parent_class_map[$child_class_key])){
        
          if(empty($ret_str)){
            $ret_str = $child_class_key;
          }else{
            throw new DomainException(
              sprintf(
                'the given $class_key (%s) has divergent children %s and %s (those 2 classes extend ' 
                .'%s but are not related to each other) so a best class cannot be found.',
                $class_key,
                $ret_str,
                $child_class_key,
                $class_key
              )
            );
          }//if/else
        
        }//if
      
      }//foreach
    
    }else{
    
      $ret_str = $class_key;
      
    }//if/else
  
    return self::getClassName($ret_str,$parent_name);
  
  }//method
  
  /**
   *  format the class key
   *  
   *  the class key is basically the class name standardized         
   *  
   *  @return string      
   */
  static function getClassKey($class_name){
    return empty($class_name) ? '' : mb_strtoupper($class_name);
  }//method
  
  /**
   *  get all the filter classes that the app has defined
   *  
   *  this will only return final filters (eg, nothing extends it)
   *      
   *  @return array a list of class names that extend montage_filter
   */
  static function getFilterClassNames(){
  
    // canary...
    if(empty(self::$parent_class_map['MONTAGE_FILTER'])){ return array(); }//if
  
    $ret_list = array();
  
    $filter_list = self::$parent_class_map['MONTAGE_FILTER'];
    foreach($filter_list as $class_key){
      if(!isset(self::$parent_class_map[$class_key])){
        $ret_list[] = self::$class_map[$class_key]['class_name'];
      }//if
    }//method
  
    return $ret_list;
  
  }//method
  
  /**
   *  get all the controller classes that the app has defined
   *  
   *  this will only return final controllers (eg, nothing extends it)
   *      
   *  @return array a list of class names that extend montage_controller
   */
  static function getControllerClassNames(){
  
    // canary...
    if(empty(self::$parent_class_map['MONTAGE_CONTROLLER'])){ return array(); }//if
  
    $ret_list = array();
  
    $class_key_list = self::$parent_class_map['MONTAGE_CONTROLLER'];
    foreach($class_key_list as $class_key){
      if(!isset(self::$parent_class_map[$class_key])){
        $ret_list[] = self::$class_map[$class_key]['class_name'];
      }//if
    }//method
  
    return $ret_list;
  
  }//method
  
  /**
   *  return true if the given $class_name extends the controller class
   *  
   *  @param  string  $class_name
   *  @return boolean true if $class_name is the name of a controller child
   */
  static function isController($class_name){
    
    $class_key = self::getClassKey($class_name);
    
    $ret_bool = self::isChild($class_key,'MONTAGE_CONTROLLER');
    if($ret_bool){
    
      if(isset(self::$parent_class_map[$class_key])){
      
        // since this class is also a parent, let's make sure it's not abstract or whatnot...
        $class_name = self::getClassName($class_name);
        $reflector = new ReflectionClass($class_name);
        $ret_bool = $reflector->isInstantiable();
      
      }//if
    
    }//if
    
    return $ret_bool;
    
  }//method
  
  /**
   *  return true if the given $class_name extends the form class
   *  
   *  @param  string  $class_name
   *  @return boolean true if $class_name is the name of a montage_form child
   */
  static function isForm($class_name){
    return self::isChild($class_name,'MONTAGE_FORM');
  }//method
  
  /**
   *  return true if $child_class_name is a child of $parent_class_name
   *  
   *  @param  string  $child_class_name
   *  @param  string  $parent_class_name      
   *  @return boolean
   */
  static function isChild($child_class_name,$parent_class_name){
  
    // canary...
    if(empty($child_class_name)){ return false; }//if
    if(empty($parent_class_name)){ return false; }//if
  
    $ret_bool = false;
    
    $parent_class_key = self::getClassKey($parent_class_name);
    if(!empty(self::$parent_class_map[$parent_class_key])){
      
      $child_class_key = self::getClassKey($child_class_name);
      $ret_bool = in_array($child_class_key,self::$parent_class_map[$parent_class_key],true);
      
    }//if
    
    return $ret_bool;
  
  }//method

  /**
   *  set a path that will be used to auto load classes
   *  
   *  @param  string  $path      
   *  @return string
   */
  static function setPath($path){
  
    // canary...
    if(empty($path)){
      throw new UnexpectedValueException('tried to get classes in an empty $path');
    }//if
    if(!is_dir($path)){
      throw new UnexpectedValueException(sprintf('"%s" is not a valid directory',$path));
    }//if

    // we only really need to check if the cache exists, we don't need to bother
    // with fetching it since all the class maps get saved into the main cache
    // when the directory is first seen...
    if(!montage_cache::has($path)){
      
      // cached miss, so get them the old fashioned way...
      $class_map = self::getClasses($path);
      
      self::addClasses($class_map);
      
      // now cache away...
      montage_cache::set(
        array(
          self::getField('montage_core_controller'),
          self::getField('montage_core_environment'),
          $path
        ),
        $class_map
      );
      
      self::setCore();
      
    }//if
    
    return $path;
  
  }//method

  /**
   *  register an autoload function
   *  
   *  @param  callback  $callback a valid php callback
   *  @return boolean         
   *
   */        
  static function set($callback){
    return spl_autoload_register($callback);
  }//method

  /**
   *  load a class
   *  
   *  @return boolean      
   */
  static function load($class_name){
  
    // if you just get blank pages: http://www.php.net/manual/en/function.error-reporting.php#28181
    //  http://www.php.net/manual/en/function.include-once.php#53239

    $class_key = self::getClassKey($class_name);
    if(isset(self::$class_map[$class_key])){
    
      include(self::$class_map[$class_key]['path']);
      self::$class_map[$class_key]['method'] = __METHOD__;
      return true;
    
    }else{
    
      // @tbi clear cache right here so it doesn't have to be done manually?
    
      throw new RuntimeException(
        sprintf(
          'unable to autoload $class_name (%s). Have you added a new class and not cleared cache?',
          $class_name
        )
      );
      
      $backtrace = debug_backtrace();
      $file = empty($backtrace[1]['file']) ? 'unknown' : $backtrace[1]['file'];
      $line = empty($backtrace[1]['line']) ? 'unknown' : $backtrace[1]['line']; 
      trigger_error(
        sprintf(
          '%s was not found, called from %s:%s'.
          $class_name,
          $file,
          $line
        ),
        E_USER_ERROR
      );
      
      return false;
      
    }//if/else
  
  }//method
  
  ///static function getClassMap(){ return self::$class_map; }//method
  ///static function getParentClassMap(){ return self::$parent_class_map; }//method
  
  /**
   *  add the class map to the global class map
   *  
   *  also set all the other global class bindings like the core classes and parent map
   *  
   *  @param  array $class_map
   */
  static private function addClasses($class_map){
  
    // canary...
    if(empty($class_map)){ return; }//if
  
    // first merge the $class_map into the global class map in case the autoloader is called...
    foreach($class_map as $class_key => $map){
    
      if(isset(self::$class_map[$class_key])){
      
        throw new RuntimeException(
          sprintf(
            'The class (%s) at "%s" has the same name as the class (%s) at "%s"',
            self::$class_map[$class_key]['class_name'],
            self::$class_map[$class_key]['path'],
            $class_map[$class_key]['class_name'],
            $class_map[$class_key]['class_name']
          )
        );
      
      }else{
      
        self::$class_map[$class_key] = $map;
      
      }//if/else
    
    }//foreach
  
    foreach($class_map as $class_key => $map){
  
      // @note  the class_parents call depends on the autoloader...
      $parent_list = class_parents($map['class_name'],true);
            
      // save the children mappings...
      foreach($parent_list as $parent_class_name){
        $parent_class_key = self::getClassKey($parent_class_name);
        if(!isset(self::$parent_class_map[$parent_class_key])){
          self::$parent_class_map[$parent_class_key] = array();
        }//if
      
        self::$parent_class_map[$parent_class_key][] = $class_key;
      
      }//if
      
    }//foreach
  
  }//method
  
  /**
   *  find all the classes in a given path and return information about them
   *  
   *  @param  string  $path all the files in $path and its sub-dirs will be examined
   *  @return array a bunch of maps with {@link getClassKey()} keys containing info about the class_name   
   */
  static private function getClasses($path){
  
    $ret_map = array();
    $declared_class_list = get_declared_classes();
  
    $path_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach($path_iterator as $file){
      
      if($file->isFile()){
      
        $file_path = $file->getRealPath();
        
        // I originally called get_declared_classes() before and after an include_once but
        // it would fail when a child class was included where the parent class hadn't been
        // seen yet, so I'm back to reading the file in and regexing for classes, which
        // I consider hackish
        
        $file_contents = file_get_contents($file_path);
        ///$file_contents = mb_convert_encoding($file_contents,'UTF-8');
        
        // find the class declaration lines...
        $line_matches = array();
        if(preg_match_all('#^[a-z\s]*(?:class|interface)\s+[^{]+#sim',$file_contents,$line_matches)){
        
          foreach($line_matches[0] as $line_match){
          
            // get the class...
            $class_match = array();
            if(preg_match('#(?:class|interface)\s+(\S+)#',$line_match,$class_match)){
              
              $class_name = $class_match[1];
              $class_key = self::getClassKey($class_match[1]);
              $ret_map[$class_key] = array(
                'path' => $file_path,
                'class_name' => $class_name
              );
              
            }//if
            
          }//foreach
          
        }//if
        
      }//if
      
    }//foreach
  
    return $ret_map;
  
  }//method
  
  /**
   *  try to load all the core information from cache
   *  
   *  @return boolean if core info was loaded return true      
   */
  private static function loadCore(){
  
    $ret_bool = false;
  
    // load the cache...
    $cache_maps = montage_cache::get(
      array(
        self::getField('montage_core_controller'),
        self::getField('montage_core_environment'),
        'montage_core:class_maps'
      )
    );
    if(!empty($cache_maps)){
    
      // core primary...
      self::$parent_class_map = $cache_maps['parent_class_map'];
      self::$class_map = $cache_maps['class_map'];
      
      // core secondary...
      self::setField('montage_core_start_class_list',$cache_maps['start_class_list']);
      
      $ret_bool = true;
      
    }//if
  
    return $ret_bool;
  
  }//method
  
  /**
   *  set all the compiled core information into the cache so it can be loaded
   *  with {@link loadCore()}
   */        
  private static function setCore(){
  
    // save all the class maps into cache...
    montage_cache::set(
      array(
        self::getField('montage_core_controller'),
        self::getField('montage_core_environment'),
        'montage_core:class_maps'
      ),
      array(
        'parent_class_map' => self::$parent_class_map,
        'class_map' => self::$class_map,
        'start_class_list' => self::getField('montage_core_start_class_list',array())
      )
    );
  
  }//method
  
  /**
   *  start the core classes and store them in the montage class, this will allow 
   *  access to most of the montage features
   *
   *  @param  string  $controller the requested controller name
   *  @param  string  $environment  the env that will be used
   *  @param  boolean if debug is on or not
   *  @param  string  $charset
   *  @param  string  $timezone               
   */
  private static function startCoreClasses($controller,$environment,$debug,$charset,$timezone){
  
    $class_name = self::getBestClassName('montage_event');
    montage::setField('montage::montage_event',new $class_name());
    
    $class_name = self::getBestClassName('montage_session');
    montage::setField(
      'montage::montage_session',
      new $class_name(
        montage_path::get(
          montage_path::getCache(),
          'session'
        )
      )
    );
    
    $class_name = self::getBestClassName('montage_request');
    montage::setField(
      'montage::montage_request',
      new $class_name(
        $controller,
        $environment,
        montage_path::get(
          montage_path::getApp(),
          'web'
        )
      )
    );
    
    $class_name = self::getBestClassName('montage_response');
    montage::setField(
      'montage::montage_response',
      new $class_name(
        montage_path::get(
          montage_path::getApp(),
          'view'
        )
      )
    );
    
    $class_name = self::getBestClassName('montage_settings');
    montage::setField(
      'montage::montage_settings',
      new $class_name(
        $debug,
        $charset,
        $timezone
      )
    );
    
    $class_name = self::getBestClassName('montage_url');
    montage::setField('montage::montage_url',new $class_name());
    
    $class_name = self::getBestClassName('montage_cookie');
    montage::setField(
      'montage::montage_cookie',
      new $class_name(montage::getRequest()->getHost())
    );

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
