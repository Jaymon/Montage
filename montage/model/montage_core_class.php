<?php

/**
 *  handle autoloading duties. While montage is the master class, this class does
 *  all the internal heavy lifting and can be mostly left alone unless you want to
 *  set more class paths (use {@link setPath()}) than what are used by default.   
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
final class montage_core extends montage_base_static {
  
  /**
   *  switched to true in the start() function
   *  @var  boolean
   */
  static private $is_started = false;
  
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
    'MONTAGE_LOG' => '',
    'MONTAGE_SESSION' => ''
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
    if(self::isStarted()){
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
  
    // save the important paths...
    montage_path::setFramework($framework_path);
    montage_path::setApp($app_path);
    montage_path::setCache(montage_path::get($app_path,'cache'));
    montage_cache::setPath(montage_path::getCache());
    
    $loaded_from_cache = self::loadCore();
    if(!$loaded_from_cache){
    
      // profile...
      if($debug){ montage_profile::start('build paths'); }//if
    
      // throughout building the paths, we need to compile a list of start classes.
      // start classes are classes that extend montage_start.
      // the start classes follow a precedence order: Global, plugins, controller, and environment...
      // * Global is a class named "app"
      // * plugins are name by what folder they are in (eg, [APP PATH]/plugins/foo/ the plugin is named foo)
      // and the plugin start class is the class with the same name as the root folder
      // (eg, class foo extends montage_start)
      // * controller start class is a class with same name as $controller
      // * environment controller is a class with the same name as $environment
      $start_class_list = array('app');
      
      // load the default model directories...
      self::setPath(montage_path::get($framework_path,'model'));
      
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
      
      // load the app model directory...
      self::setPath(montage_path::get($app_path,'model'));
    
      // load the controller...
      $controller_path = montage_path::get($app_path,'controller',$controller);
      self::setPath($controller_path);
      
      if(empty(self::$parent_class_map['MONTAGE_CONTROLLER'])){
        throw new RuntimeException(
          sprintf(
            'the controller (%s) does not have any classes that extend "montage_controller" '
            .'so no requests can be processed. Fix this by adding some classes that extend '
            .'"montage_controller" in the "%s" directory. At the very least, you should have '
            .'an index class that has a getIndex() method (eg, index::getIndex()) to fulfill '
            .'default requests.',
            $controller,
            $controller_path
          )
        );
      }//if
      
      // set the main settings path...
      self::setPath(montage_path::get($app_path,'settings'));
      
      $start_class_name = self::getClassName($controller);
      if(!empty($start_class_name)){ $start_class_list[] = $start_class_name; }//if
      
      $start_class_name = self::getClassName($environment);
      if(!empty($start_class_name)){ $start_class_list[] = $start_class_name; }//if
      
      self::setField('montage_core_start_class_list',$start_class_list);
      
      // save all the compiled core classes/paths into the cache...
      self::setCore();
      
      // profile...
      if($debug){ montage_profile::stop(); }//if
    
    }//if
    
    // profile...
    if($debug){ montage_profile::start('initialize core classes'); }//if
    
    // officially start the framework...
    self::startCoreClasses($controller,$environment,$debug,$charset,$timezone);
    
    // profile...
    if($debug){ montage_profile::stop(); }//if
    
    // load the settings directory and "start" the app...
    
    // profile...
    if($debug){ montage_profile::start('settings'); }//if
    
    // now actually start the settings/start classes...
    $start_class_list = self::getField('montage_core_start_class_list',array());
    $start_class_parent_key = 'MONTAGE_START';
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
   *  if {@link start()} has been called then this will be true, it provides a public
   *  facing way to see if the core has been started previously
   *  
   *  @return boolean
   */
  static function isStarted(){ return self::$is_started; }//method
  
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
    
    $class_name = self::getClassName($class_name);
  
    if(!empty($parent_name)){
    
      if(!self::isChild($class_name,$parent_name)){
        $class_name = '';
      }//if
    
    }//if
    
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
   *  @return string
   */
  static function getClassName($class_key){
    
    // sanity, make sure the class key is in the right format...
    $class_key = self::getClassKey($class_key);
    
    $ret_str = '';
    if(isset(self::$class_map[$class_key])){
      $ret_str = self::$class_map[$class_key]['class_name'];
    }//if
    return $ret_str;
    
  }//method
  
  /**
   *  get the absolute most child for the given parent 
   *  (eg, the last class to extend any class that extends the passed in $parent_class_key)
   *  
   *  @param  string  $parent_class_key
   *  @return string  the child class name
   */
  static function getChildClassName($parent_class_key){
  
    $ret_str = '';
  
    $parent_class_key = self::getClassKey($parent_class_key);
    if(isset(self::$parent_class_map[$parent_class_key])){
    
      $child_class_list = self::$parent_class_map[$parent_class_key];
      foreach($child_class_list as $child_class_key){
      
        if(!isset(self::$parent_class_map[$child_class_key])){
        
          if(empty($ret_str)){
            $ret_str = $child_class_key;
          }else{
            throw new DomainException(
              sprintf(
                'the given $parent_class_key (%s) has divergent children (eg, two child classes that are not related)',
                $parent_class_key
              )
            );
          }//if/else
        
        }//if
      
      }//foreach
    
    }else{
    
      throw new RuntimeException(
        sprintf('no class extends $parent_class_key (%s)',$parent_class_key)
      );
      
    }//if/else
  
    return self::getClassName($ret_str);
  
  }//method
  
  /**
   *  get the class name for a key core class
   *  
   *  the reason why this method exists is because you can extend certain core classes
   *  (the ones found in the {@link $core_class_map}) and there needs to be a way for
   *  the framework to get the core class that is going to be used
   *  
   *  @param  string  $core_class_key the core class you want to get the best matching class
   *                                  for
   *  @return string  the class name that is going to be used as the core class
   */
  static function getCoreClassName($core_class_key){
    // sanity, make sure the class key is in the right format...
    $core_class_key = self::getClassKey($core_class_key);
    
    $ret_str = '';
    
    if(isset(self::$core_class_map[$core_class_key])){
    
      $ret_str = self::getClassName(self::$core_class_map[$core_class_key]);
    
    }//if
    
    return $ret_str;
    
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
   *  get all the filters that the app has defined
   *  
   *  @return array a list of class names that extend montage_filter
   */
  static function getFilters(){
  
    // canary...
    if(empty(self::$parent_class_map['MONTAGE_FILTER'])){ return array(); }//if
  
    $ret_list = array();
  
    $filter_list = self::$parent_class_map['MONTAGE_FILTER'];
    foreach($filter_list as $class_key){
      $ret_list[] = self::$class_map[$class_key]['class_name'];
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
    // canary...
    if(empty(self::$parent_class_map['MONTAGE_CONTROLLER'])){ return false; }//if
    return self::isChild($class_name,'MONTAGE_CONTROLLER');
  }//method
  
  /**
   *  return true if the given $class_name extends the form class
   *  
   *  @param  string  $class_name
   *  @return boolean true if $class_name is the name of a montage_form child
   */
  static function isForm($class_name){
    // canary...
    if(empty(self::$parent_class_map['MONTAGE_FORM'])){ return false; }//if
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
    $child_class_key = self::getClassKey($child_class_name);
    $parent_class_key = self::getClassKey($parent_class_name);
    if(isset(self::$parent_class_map[$parent_class_key])){
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
      montage_cache::set($path,$class_map);
      
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
    ///self::$class_map = array_merge(self::$class_map,$class_map);
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
  
      // @note  this depends on the autoloader...
      $parent_list = class_parents($map['class_name'],true);
            
      // save the children mappings...
      foreach($parent_list as $parent_class_name){
        $parent_class_key = self::getClassKey($parent_class_name);
        if(!isset(self::$parent_class_map[$parent_class_key])){
          self::$parent_class_map[$parent_class_key] = array();
        }//if
      
        self::$parent_class_map[$parent_class_key][] = $class_key;
      
      }//if
    
      // check if we have a core class override...
      if(isset(self::$core_class_map[$class_key])){
    
        self::$core_class_map[$class_key] = $class_key;
      
      }else{
        
        foreach($parent_list as $parent_class_name){
        
          $parent_class_key = self::getClassKey($parent_class_name);
          if(isset(self::$core_class_map[$parent_class_key])){
            
            /*
            $text = sprintf('is %s a child of %s is %s',
              $class_key,
              self::$core_class_map[$parent_class_key],
              self::isChild($class_key,self::$core_class_map[$parent_class_key]) ? 'TRUE' : 'FALSE'
            );
            out::e($text);
            */
            
            if(empty(self::$core_class_map[$parent_class_key])){
            
              // we don't have a class defined for this core yet, so set it...
              self::$core_class_map[$parent_class_key] = $class_key;
              break;
              
            }else{
              
              // see if the current core set class is a child of the current $class
              if(self::isChild($class_key,self::$core_class_map[$parent_class_key])){
              
                self::$core_class_map[$parent_class_key] = $class_key;
                break;
              
              }else{
              
                // check to make sure the current core class is a child of the one being checked.
                // If it isn't then we have 2 classes that extend the same core but aren't 
                // related (eg, parent/child) so error out...
                if(!self::isChild(self::$core_class_map[$parent_class_key],$class_key)){
                
                  throw new RuntimeException(
                    sprintf(
                      'There are 2 classes that are extending the same core: "%s" and "%s". Please fix this.',
                      $map['class_name'],
                      self::$class_map[self::$core_class_map[$parent_class_key]]['class_name']
                    )
                  );
                  
                }//if
              
              
              }//if/else
              
            }//if/else
            
          }//if
          
        }//foreach
        
      }//if/else
      
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
        if(preg_match_all('#^[\w\s]*(?:class|interface)\s+[^{]+#sim',$file_contents,$line_matches)){
        
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
    $cache_maps = montage_cache::get('montage_core:class_maps');
    if(!empty($cache_maps)){
    
      // core primary...
      self::$core_class_map = $cache_maps['core_class_map'];
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
      'montage_core:class_maps',
      array(
        'core_class_map' => self::$core_class_map,
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
  
    // log starts first so startup problems can be logged...
    $class_name = self::getCoreClassName('montage_log');
    montage::setField('montage_log',new $class_name());
    
    // this will start the session...
    $si_instance = null;
    try{
    
      $si_class_name = self::getChildClassName('montage_session_interface');
      ///$si_instance = new $si_class_name();
      
    }catch(RuntimeException $e){}//try/catch
    
    $class_name = self::getCoreClassName('montage_session');
    montage::setField(
      'montage_session',
      new $class_name(
        montage_path::get(
          montage_path::getApp(),
          'cache',
          'session'
        )
      )
    );
    
    
    $class_name = self::getCoreClassName('montage_request');
    montage::setField(
      'montage_request',
      new $class_name(
        $controller,
        $environment,
        montage_path::get(
          montage_path::getApp(),
          'web'
        )
      )
    );
    
    $class_name = self::getCoreClassName('montage_response');
    montage::setField(
      'montage_response',
      new $class_name(
        montage_path::get(
          montage_path::getApp(),
          'view'
        )
      )
    );
    
    $class_name = self::getCoreClassName('montage_settings');
    montage::setField(
      'montage_settings',
      new $class_name(
        $debug,
        $charset,
        $timezone
      )
    );
    
    $class_name = self::getCoreClassName('montage_url');
    montage::setField('montage_url',new $class_name());

  }//method

}//class     