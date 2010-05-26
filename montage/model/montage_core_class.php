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
 *  @version 0.3
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
   *  @deprecated this is no longer used but is a good snapshot of what classes can
   *  be extended by the user, when I have proper documentation this can be gotten rid
   *  of      
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
    'MONTAGE_FORWARD' => '',
    'MONTAGE_HANDLE' => ''
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
    self::appendClassLoader(array(__CLASS__,'load'));
  
    // save important fields...
    self::setField('montage_core_controller',$controller); // for caching
    self::setField('montage_core_environment',$environment); // for caching
  
    // save the important paths...
    montage_path::setFramework($framework_path);
    montage_path::setApp($app_path);
    montage_path::setCache(montage_path::get($app_path,'cache'));
    montage_cache::setPath(montage_path::getCache());
    
    $loaded_from_cache = self::loadCore();
    if($loaded_from_cache){
    
      self::$is_cached = true;
    
    }else{
    
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
      
      $controller_class_key = self::getClassKey('MONTAGE_CONTROLLER');
      if(empty(self::$parent_class_map[$controller_class_key])){
        throw new RuntimeException(
          sprintf(
            join("\r\n",array(
              'the controller (%s) does not have any classes that extend "montage_controller" '
              .'so no requests can be processed. Fix this by adding some classes that extend '
              .'"montage_controller" in the "%s" directory. At the very least, you should have '
              .'an index class to fulfill default requests:',
              '',
              'class index extends montage_controller {',
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
    set_error_handler(array('montage_error','handleRuntime'));
    register_shutdown_function(array('montage_error','handleFatal'));
    
    // profile...
    if($debug){ montage_profile::stop(); }//if
    
    // load the settings directory and "start" the app...
    
    // profile...
    if($debug){ montage_profile::start('settings'); }//if
    
    // now actually start the settings/start classes...
    $start_class_list = self::getField('montage_core_start_class_list',array());
    $start_class_parent_key = 'montage_start';
    foreach($start_class_list as $start_class_name){
      montage_factory::getInstance($start_class_name,array(),$start_class_parent_key);
    }//foreach
    
    // profile...
    if($debug){ montage_profile::stop(); }//if
    
    self::$is_started = true;
    
    // profile...
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
   *  @idea final might be a better word than best here (eg, getFinalClassName)
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
   *  the class key is basically the class name standardized, this is handy to make
   *  classes case-insensitive (because they aren't in php)           
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
   *  get all the descendant (child) classes defined for the given $parent_class_name
   *  
   *  @note another name I liked for this method was getDescendants but figured I would
   *        stay with the ClassNames syntax that all the other methods used   
   *  
   *  @since  5-26-10
   *          
   *  @param  string  $parent_class_name  the name of the parent
   *  @param  boolean $only_best_classes  if true, then only classes with no children are returned, if false
   *                                      then all classes (even ones that defined children are returned         
   *  @return array a list of class names that extend montage_controller
   */
  static function getChildClassNames($parent_class_name,$only_best_classes = true){
  
    $parent_class_key = self::getClassKey($parent_class_name);
  
    // canary...
    if(empty(self::$parent_class_map[$parent_class_key])){ return array(); }//if
  
    $ret_list = array();
  
    $class_key_list = self::$parent_class_map[$parent_class_key];
    foreach($class_key_list as $class_key){
      if($only_best_classes){
        if(!isset(self::$parent_class_map[$class_key])){
          $ret_list[] = self::$class_map[$class_key]['class_name'];
        }//if
      }else{
        $ret_list[] = self::$class_map[$class_key]['class_name'];
      }//if/else
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
   *  return true if $child_class_name is a child of $parent_class_name or actually
   *  is $parent_class_name   
   *  
   *  @param  string  $child_class_name
   *  @param  string  $parent_class_name      
   *  @return boolean
   */
  static function isRelated($child_class_name,$parent_class_name){
  
    // canary...
    if(empty($child_class_name)){ return false; }//if
    if(empty($parent_class_name)){ return false; }//if
  
    $ret_bool = false;
    
    $child_class_key = self::getClassKey($child_class_name);
    
    $parent_class_key = self::getClassKey($parent_class_name);
    if(!empty(self::$parent_class_map[$parent_class_key])){
      
      $ret_bool = in_array($child_class_key,self::$parent_class_map[$parent_class_key],true);
      
    }else{
    
      $ret_bool = ($child_class_key === $parent_class_key);
    
    }//if/else
    
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
   *  @param  callback  $callback a valid php callback that loads classes
   *  @return boolean
   */
  static function appendClassLoader($callback){
    return spl_autoload_register($callback);
  }//method

  /**
   *  load a class
   *  
   *  this should never be called by the user, the only reason it is public is so
   *  {@link appendClassLoader()} will work right   
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
    
      $backtrace = debug_backtrace();
      $file = empty($backtrace[1]['file']) ? 'unknown' : $backtrace[1]['file'];
      $line = empty($backtrace[1]['line']) ? 'unknown' : $backtrace[1]['line'];
    
      throw new InvalidArgumentException(
        sprintf(
          'unable to autoload $class_name (%s) at %s:%s. Have you ' 
          .'added a new class and not cleared cache?',
          $class_name,
          $file,
          $line
        )
      );
      
    }//if/else
  
  }//method
  
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
  
    montage::setField(
      'montage::montage_event',
      montage_factory::getBestInstance(
        'montage_event'
      )
    );
    
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
        array()
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
