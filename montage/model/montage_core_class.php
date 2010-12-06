<?php

/**
 *  this class handles all the discovering and auto-loading of classes, it also has
 *  methods to let the developer easily get class information and class relationships  
 *  
 *  this class can be mostly left alone unless you want to set more class paths 
 *  (use {@link setPath()}) than what are used by default, or if you want to add
 *  a custom autoloader (use {@link appendClassLoader()}) 
 *
 *  class paths checked by default:
 *    [MONTAGE_PATH]/model
 *    [MONTAGE_APP_PATH]/settings
 *    [MONTAGE_PATH]/plugins
 *    [MONTAGE_APP_PATH]/plugins  
 *    [MONTAGE_APP_PATH]/model
 *    [MONTAGE_APP_PATH]/controller/$controller
 *   
 *  @version 0.6
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
final class montage_core extends montage_base_static {
  
  /**
   *  the current montage version
   */
  const VERSION = '0.6';
  
  /**
   *  the "global" settings will be a montage_start child extending class with this name
   */
  const CLASS_NAME_APP_START = 'app';
  
  /**
   *  the key where all the start classes are kept
   */
  const KEY_START = 'montage_core::start_class_list';
  
  /**
   *  the key where all the class paths are kept
   */
  const KEY_PATH = 'montage_core::path_list';
  
  /**
   *  the keys in this list will be cached with the core
   *  
   *  @see  setCore(), loadCore(), resetCore()      
   *  @var  array
   */
  static private $key_cache_list = array(
    self::KEY_START,
    self::KEY_PATH
  );
  
  /**
   *  switched to true in the start() function
   *  @var  boolean
   */
  static private $is_started = false;
  
  /**
   *  will be set to true if the core information was loaded from cache instead of
   *  generated, you can access this boolean by using {@link isCached()}
   *  
   *  @var  boolean
   */
  static private $is_cached = false;
  
  /**
   *  start montage's core, basically, everything that makes montage go
   *  
   *  @param  string  $controller the requested controller name
   *  @param  string  $environment  the env that will be used
   *  @param  boolean if debug is on or not
   *  @param  string  $charset
   *  @param  string  $timezone
   *  @param  array $path_map contains key/val pairs of all the paths the core needs
   */
  static function start($controller,$environment,$debug,$charset,$timezone,$path_map){
  
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
    if(empty($path_map['montage_path'])){
      throw new UnexpectedValueException('montage_path cannot be empty');
    }//if
    if(empty($path_map['montage_app_path'])){
      throw new UnexpectedValueException('montage_app_path cannot be empty');
    }//if
    if(empty($path_map['montage_cache_path'])){
      throw new UnexpectedValueException('montage_cache_path cannot be empty');
    }//if

    self::$is_started = true;
    $event_warning_list = array();
  
    // so I don't have to go change everything...
    $framework_path = $path_map['montage_path'];
    $app_path = $path_map['montage_app_path'];
  
    // set the default autoloader...
    self::appendClassLoader(array(__CLASS__,'load'));
  
    // save important fields...
    self::setField('montage_core_controller',$controller); // for caching
    self::setField('montage_core_environment',$environment); // for caching
  
    // save the important paths...
    montage_path::setFramework($framework_path);
    montage_path::setApp($app_path);
    montage_path::setCache($path_map['montage_cache_path']);
    montage_cache::setPath(montage_path::getCache());
    
    $loaded_from_cache = self::loadCore();
    if($loaded_from_cache){
    
      self::$is_cached = true;
    
    }else{
    
      // profile...
      if($debug){ montage_profile::start('check paths'); }//if
      
      // reset the core...
      self::resetCore();
      
      // load the default model directories...
      self::setPath(montage_path::get($framework_path,'model'));
      
      // set the main settings path, ingore it if it doesn't exist...
      try{
        self::setPath(montage_path::get($app_path,'settings'));
      }catch(InvalidArgumentException $e){
        $event_warning_list[] = array('msg' => $e->getMessage());
      }//try/catch
      
      $start_class_list = array();
      $start_class_parent_name = 'montage_start';
      $start_class_postfix_list = array('_start','Start');
      
      // throughout building the paths, we need to compile a list of start classes.
      // start classes are classes that extend montage_start.
      // the start classes follow a precedence order: Global, environment, plugins, and controller...
      // * Global is a class named "app" it can't be named "start" because start_start sounds funny
      // * environment is a class with the same name as $environment, it's before plugins to set db variables
      //    and the like so that plugins (like a db plugin) can take advantage of connection settings
      //    like db name, db username, and db password
      // * plugins are name by what folder they are in (eg, [APP PATH]/plugins/foo/ the plugin is named foo)
      // and the plugin start class is the class with the same name as the root folder
      // (eg, class foo extends montage_start)
      // * controller start class is a class with same name as $controller
      
      $start_class_name = self::getClassName(
        array(self::CLASS_NAME_APP_START,$start_class_postfix_list),
        $start_class_parent_name
      );
      if(!empty($start_class_name)){ $start_class_list[] = $start_class_name; }//if
      
      $start_class_name = self::getClassName(
        array($environment,$start_class_postfix_list),
        $start_class_parent_name
      );
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
        
        $start_class_name = self::getClassName(
          array($plugin_name,$start_class_postfix_list),
          $start_class_parent_name
        );
        if(!empty($start_class_name)){ $start_class_list[] = $start_class_name; }//if
        
      }//foreach
      
      $start_class_name = self::getClassName(
        array($controller,$start_class_postfix_list),
        $start_class_parent_name
      );
      if(!empty($start_class_name)){ $start_class_list[] = $start_class_name; }//if
      
      self::setField(self::KEY_START,$start_class_list);
      
      // load the app's model directory, ignore it if it doesn't exist...
      try{
        self::setPath(montage_path::get($app_path,'model'));
      }catch(InvalidArgumentException $e){
        $event_warning_list[] = array('msg' => $e->getMessage());
      }//try/catch
    
      // load the controller, ignore it if it doesn't exist...
      try{
        $controller_path = montage_path::get($app_path,'controller',$controller);
        self::setPath($controller_path);
        
        $controller_class_key = self::getClassKey('MONTAGE_CONTROLLER');
        if(empty(self::$parent_class_map[$controller_class_key])){
          throw new RuntimeException(
            sprintf(
              join("\r\n",array(
                'the controller (%s) does not have any classes that extend "montage_controller" '
                .'so no requests can be processed. Fix this by adding some classes that extend '
                .'"montage_controller" in the "%s" directory. At the very least, you should have '
                .'an index class to fulfill default requests'
              )),
              $controller,
              $controller_path,
              montage_forward::CONTROLLER_METHOD
            )
          );
        }//if
      }catch(InvalidArgumentException $e){
        $event_warning_list[] = array('msg' => $e->getMessage());
      }//try/catch
      
      // save all the compiled core classes/paths into the cache...
      self::setCore();
      
      // profile...
      if($debug){ montage_profile::stop(); }//if
    
    }//if
    
    // profile...
    if($debug){ montage_profile::start('initialize core classes'); }//if
    
    // officially start the core global classes of the framework...
    self::startCoreClasses($controller,$environment,$debug,$charset,$timezone);
    
    // profile, finish init of core...
    if($debug){ montage_profile::stop(); }//if
    
    // set error handlers...
    set_error_handler(array('montage_error','handleRuntime'));
    register_shutdown_function(array('montage_error','handleFatal'));
    
    // broadcast any encountered warnings...
    if(!empty($event_warning_list)){
    
      $event = montage::getEvent();
      
      foreach($event_warning_list as $info_map){
        $event->broadcast(montage_event::KEY_WARNING,$info_map,true);
      }//foreach
    
    }//if
    
    // profile, finish method run...
    if($debug){ montage_profile::stop(); }//if
    
  }//method
  
  /**
   *  returns true if the montage core class information was loaded from cache instead
   *  of generated by the requested
   *  
   *  @return boolean
   */
  static function isCached(){ return self::$is_cached; }//method
  
  /**
   *  get all the filter classes that the app has defined
   *  
   *  this will only return final filters (eg, nothing extends it)
   *      
   *  @return array a list of class names that extend montage_filter
   */
  static function getFilterClassNames(){
    return self::getChildClassNames('montage_filter');
  }//method
  
  /**
   *  get all the controller classes that the app has defined
   *  
   *  this will only return final controllers (eg, nothing extends it)
   *      
   *  @return array a list of class names that extend montage_controller
   */
  static function getControllerClassNames(){
    return self::getChildClassNames('montage_controller');
  }//method
  
  /**
   *  get all the start classes that the app has defined
   *  
   *  @since  5-31-10   
   *  @return array a list of class names that extend montage_start
   */
  static function getStartClassNames(){
    return self::getField(self::KEY_START,array());
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
   *  try to load all the core information from cache
   *  
   *  @return boolean if core info was loaded return true      
   */
  private static function loadCore(){
  
    $ret_bool = false;
  
    // load the cache...
    $core_map = montage_cache::get(
      array(
        self::getField('montage_core_controller'),
        self::getField('montage_core_environment'),
        'montage_core::core_map'
      )
    );
    
    if(!empty($core_map)){
    
      // core primary...
      self::$parent_class_map = $core_map['parent_class_map'];
      self::$class_map = $core_map['class_map'];
      
      // load the secondary core...
      foreach(self::$key_cache_list as $key){
        if(isset($core_map[$key])){
          self::setField($key,$core_map[$key]);
        }//if
      }//foreach
      
      $ret_bool = true;
      
    }//if
  
    return $ret_bool;
  
  }//method
  
  /**
   *  set all the compiled core information into the cache so it can be loaded
   *  with {@link loadCore()}
   */        
  private static function setCore(){
  
    $core_map = array();
    $core_map['parent_class_map'] = self::$parent_class_map;
    $core_map['class_map'] = self::$class_map;
  
    foreach(self::$key_cache_list as $key){
      $core_map[$key] = self::getField($key,array());
    }//foreach
  
    // save all the class maps into cache...
    montage_cache::set(
      array(
        self::getField('montage_core_controller'),
        self::getField('montage_core_environment'),
        'montage_core::core_map'
      ),
      $core_map
    );
  
  }//method
  
  /**
   *  reset all the compiled core information to its initial state
   *  
   *  the reason why this method exists is so static arrays can be reset when the 
   *  cache is    
   *      
   *  @see  setCore(), loadCore()   
   */
  private static function resetCore(){
    foreach(self::$key_cache_list as $key){ self::setField($key,array()); }//foreach
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
  
    // we start this first so other classes can broadcast events...
    montage::setField(
      'montage::montage_event',
      montage_factory::getBestInstance(
        'montage_event'
      )
    );
    
    // we start session as soon as we can to minimize the chances of headers being sent...
    montage::setField(
      'montage::montage_session',
      montage_factory::getBestInstance(
        'montage_session',
        array(
          montage_path::get(montage_path::getCache(),'session')
        )
      )
    );  
    
    montage::setField(
      'montage::montage_request',
      montage_factory::getBestInstance(
        'montage_request',
        array(
          $controller,
          $environment,
          montage_path::get(montage_path::getApp(),'web')
        )
      )
    );
    
    montage::setField(
      'montage::montage_response',
      montage_factory::getBestInstance(
        'montage_response',
        array(
          montage_path::get(montage_path::getApp(),'view')
        )
      )
    );
    
    montage::setField(
      'montage::montage_settings',
      montage_factory::getBestInstance(
        'montage_settings',
        array(
          $debug,
          $charset,
          $timezone
        )
      )
    );
    
    montage::setField(
      'montage::montage_url',
      montage_factory::getBestInstance(
        'montage_url',
        array(
          montage::getRequest()->getUrl(),
          montage::getRequest()->getBase()
        )
      )
    );
    
    montage::setField(
      'montage::montage_cookie',
      montage_factory::getBestInstance(
        'montage_cookie',
        array(
          montage::getRequest()->getHost()
        )
      )
    );

  }//method

}//class     
