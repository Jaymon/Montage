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
   *  hold all the classes that could possibly be loadable
   *  
   *  the structure is: each key is a the class_key name, with path and name key/vals for
   *  each class_key
   *  
   *  @var  array
   */
  static private $class_map = array();
  
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

    self::$is_started = true;
    $event_warning_list = array();
  
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
   *  get the class name from the given potential $class_name
   *  
   *  this is useful because since we standardize all the class names we can find
   *  a class whether we pass in ClassName Classname className. Basically, montage
   *  classes are case-insensitive            
   *  
   *  @param  string  $class_name see {@link getPossibleClassNames()}  
   *  @param  string  $parent_name  the name of the parent class, if not empty then $class_name
   *                                must be a child of $parent_name       
   *  @return string
   */
  static function getClassName($class_name,$parent_name = ''){
    
    $ret_str = '';
    $class_name_list = self::getPossibleClassNames($class_name);
    foreach($class_name_list as $class_name){
      
      // sanity, make sure the class key is in the right format...
      $class_key = self::getClassKey($class_name);
      
      if(isset(self::$class_map[$class_key])){
      
        $ret_str = self::$class_map[$class_key]['class_name'];
        
        if(empty($parent_name)){
        
          break;
          
        }else{
        
          if(!self::isChild($class_key,$parent_name)){
            $ret_str = '';
          }//if
        
          break;
        
        }//if/else
        
      }//if
      
    }//foreach
    
    return $ret_str;
    
  }//method
  
  /**
   *  get possible class names from an array of some structure like: array(prefix_list,name_list,postfix_list)
   *
   *  this is handy for getting the class name of a class that has to have a specific pre or postfix
   *  like the _controller or _start postfixes         
   *        
   *  @param  string|array  $class_name_list  the possible parts of a class name to be combined      
   *                                          Possible combinations:
   *                                            1 - class_name
   *                                            2 - array(prefix_list,class_name
   *                                            3 - array(class_name,postfix_list)
   *                                            4 - array(prefix_list,class_name,postfix_list)            
   *  @return array a list of possible combinations assembled from the bits of $class_name_list
   */
  private static function getPossibleClassNames($class_name_list){
  
    // canary....
    // class name isn't an array, so return as one...
    if(!is_array($class_name_list)){ return array($class_name_list); }//if
    // class name is an array with one key, the class's name...
    if(empty($class_name_list[1])){
      if(empty($class_name_list[0])){
        return array();
      }else{
        return is_array($class_name_list[0]) ? $class_name_list[0] : array($class_name_list[0]);
      }//if/else
    }//if
    
    // assure the array is of the form: array(prefix_list,name_list,postfix_list)
    
    if(!is_array($class_name_list[0])){ $class_name_list[0] = array($class_name_list[0]); }//if
    if(!is_array($class_name_list[1])){ $class_name_list[1] = array($class_name_list[1]); }//if
    if(empty($class_name_list[2])){
    
      if(empty($class_name_list[0])){
        $class_name_list[] = array();
      }else{
        array_unshift($class_name_list,array());
      }//if/else
      
    }else{
    
      if(!is_array($class_name_list[2])){ $class_name_list[2] = array($class_name_list[2]); }//if
      
    }//if/else

    $ret_list = array();

    // go through each class name...
    foreach($class_name_list[1] as $class_name){
    
      $class_name_partial_list = array();
    
      if(empty($class_name_list[0])){
      
        $class_name_partial_list[] = $class_name;
      
      }else{
      
        // append the prefixes...
        foreach($class_name_list[0] as $class_name_prefix){
        
          $class_name_partial_list[] = sprintf('%s%s',$class_name_prefix,$class_name);
        
        }//foreach
        
      }//if/else
      
      if(empty($class_name_list[2])){
      
        $ret_list = $class_name_partial_list;
      
      }else{
      
        // now append the postfix
        foreach($class_name_partial_list as $class_name_partial){
        
          // append the postfixes to each of the partials...
          foreach($class_name_list[2] as $class_name_postfix){
        
            $ret_list[] = sprintf('%s%s',$class_name_partial,$class_name_postfix);
            
          }//foreach
        
        }//foreach
        
      }//if/else
    
    }//foreach
    
    return $ret_list;
    
  }//method
  
  /**
   *  get the absolute most child for the given class
   *  (eg, the last class to extend any class that extends the passed in $class_key)
   *  
   *  @idea final might be a better word than best here (eg, getFinalClassName)
   *      
   *  @param  string  $class_name see {@link getPossibleClassNames()}
   *  @param  string  $parent_name  the name of the parent class, if not empty then $class_name
   *                                must be a child of $parent_name   
   *  @return string  the child class name   
   *  @throws DomainException if the class_key is extended by more than one unrelated child   
   */
  static function getBestClassName($class_name,$parent_name = ''){
  
    $ret_str = '';
    $class_name_list = self::getPossibleClassNames($class_name);
    foreach($class_name_list as $class_name){
  
      $class_key = self::getClassKey($class_name);
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
        
        $ret_str = self::getClassName($ret_str,$parent_name);
        break;
      
      }else{
      
        $ret_str = self::getClassName($class_key,$parent_name);
        if(!empty($ret_str)){ break; }//if
        
      }//if/else
      
    }//foreach
  
    return $ret_str;
  
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
   *  get all the start classes that the app has defined
   *  
   *  @since  5-31-10   
   *  @return array a list of class names that extend montage_start
   */
  static function getStartClassNames(){
    return self::getField(self::KEY_START,array());
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
      throw new InvalidArgumentException(sprintf('"%s" is not a valid directory',$path));
    }//if

    $path_list = self::getField(self::KEY_PATH,array());

    // we only really need to check if the cache exists, we don't need to bother
    // with fetching it since all the class maps get saved into the main cache
    // when the directory is first seen...
    ///if(!montage_cache::has($path)){
    if(!in_array($path,$path_list,true)){
      
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
      
      // add the path to the path list and save the new core...
      $path_list[] = $path;
      self::setField(self::KEY_PATH,$path_list);
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
   *  @return boolean true if the class was found, false if not (so other autoloaders can have a chance)
   */
  static function load($class_name){
  
    // if you just get blank pages: http://www.php.net/manual/en/function.error-reporting.php#28181
    //  http://www.php.net/manual/en/function.include-once.php#53239

    $ret_bool = false;

    $class_key = self::getClassKey($class_name);
    if(isset(self::$class_map[$class_key])){
    
      include(self::$class_map[$class_key]['path']);
      $ret_bool = true;
    
    }else{
    
      // we used to throw an exception here, but that didn't account for user appended
      // autoloaders (ie, if this autoloader failed, then it failed even if the user
      // had appended another autoloader...
      $ret_bool = false;
      
    }//if/else
    
    return $ret_bool;
  
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
            'SAME CLASS NAME ERROR! The class (%s) at "%s" has the same name as the class (%s) at "%s"',
            self::$class_map[$class_key]['class_name'],
            self::$class_map[$class_key]['path'],
            $class_map[$class_key]['class_name'],
            $class_map[$class_key]['path']
          )
        );
      
      }else{
      
        self::$class_map[$class_key] = $map;
      
      }//if/else
    
    }//foreach
  
    // now map the child classes to their parents...
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
      
        // make sure the extension is a php one...
        $ext = pathinfo($file->getFilename(),PATHINFO_EXTENSION);
        if(!empty($ext) && preg_match('#(?:php(?:\d+)?|inc|phtml)$#',$ext)){
      
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
