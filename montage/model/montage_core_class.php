<?php

/**
 *  handle autoloading duties    
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
  
  static function isStarted(){ return self::$is_started; }//method
  
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
    
    // now we need to use all the start classes to initialize the app...
    // the start classes follow a precedence order: Global, controller, and environment...
    // we're looking for a class named "app" that is a child of montage_start...
    // next, load the controller's "start" class...
    // next, load all the plugins...
    // finally, load the environment's "start" class...
    $start_class_list = array('app',$controller);
  
    // profile...
    if($debug){ montage_profile::start('set paths'); }//if
  
    // save the important paths...
    self::setFrameworkPath($framework_path);
    self::setAppPath($app_path);
    montage_cache::setPath(self::getCustomPath($app_path,'cache'));
    
    // load the cache...
    $cache_maps = montage_cache::get('montage_core:class_maps');
    if(!empty($cache_maps)){
      self::$core_class_map = $cache_maps['core_class_map'];
      self::$parent_class_map = $cache_maps['parent_class_map'];
      self::$class_map = $cache_maps['class_map'];
    }//if
    
    // load the default model directories...
    self::setPath(self::getCustomPath($framework_path,'model'));
    
    // include all the plugin paths, save all the start class names.
    // We include these here before the app model path because they can extend core 
    // but plugin classes should never extend app classes, but app classes can extend
    // plugin classes...
    $plugin_path_list = self::getPaths(self::getCustomPath($app_path,'plugins'),false);
    foreach($plugin_path_list as $plugin_path){
      
      $start_class_list[] = basename($plugin_path);
      
      // find all the classes in the plugin path...
      self::setPath($plugin_path);
      
    }//foreach
    
    self::setPath(self::getCustomPath($app_path,'model'));
    
    // load the controller...
    $controller_path = self::getCustomPath($app_path,'controller',$controller);
    self::setPath($controller_path);
    
    if(empty(self::$parent_class_map['MONTAGE_CONTROLLER'])){
      throw new RuntimeException(
        sprintf(
          'the controller (%s) does not have any classes that extend "montage_controller" '
          .'so no requests can be processed. Fix this by adding some classes that extend '
          .'"montage_controller" in the "%s" directory. At the very least, you should have '
          .'an index class that has a getIndex() method (eg, index::getIndex()) to fulfill '
          .'default requests.',
          $path,
          $controller_path
        )
      );
    }//if
    
    // set the main settings path...
    self::setPath(self::getCustomPath($app_path,'settings'));
    
    // profile...
    if($debug){ montage_profile::stop(); }//if
    
    // profile...
    if($debug){ montage_profile::start('initialize core classes'); }//if
    
    // officially start the framework...
    montage::start($controller,$environment,$debug,$charset,$timezone);
    
    // profile...
    if($debug){ montage_profile::stop(); }//if
    
    // load the settings directory and "start" the app...
    
    // profile...
    if($debug){ montage_profile::start('settings'); }//if
    
    // now actually start to get all the settings...
    $start_class_list[] = $environment;
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
   *  set the montage root path
   *  
   *  @param  string  $val
   */
  static private function setFrameworkPath($val){ self::setField('montage_framework_path',$val); }//method
  
  /**
   *  get the montage root path
   *  
   *  @return string
   */
  static private function getFrameworkPath(){ return self::getField('montage_framework_path',''); }//method
  
  /**
   *  set the montage app root path
   *  
   *  @param  string  $val
   */
  static private function setAppPath($val){ self::setField('montage_app_path',$val); }//method
  
  /**
   *  get the montage app root path
   *  
   *  @return string
   */
  static function getAppPath(){ return self::getField('montage_app_path',''); }//method

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
      
      // save all the class maps into cache...
      montage_cache::set(
        'montage_core:class_maps',
        array(
          'core_class_map' => self::$core_class_map,
          'parent_class_map' => self::$parent_class_map,
          'class_map' => self::$class_map
        )
      );
      
    }//if
    
    return $path;
  
  }//method

  /**
   *  given multiple path bits, build a custom path
   *  
   *  @example  self::getCustomPath('foo','bar'); // -> foo/bar
   *  
   *  @param  $args,... one or more path bits
   *  @return string
   */
  static function getCustomPath(){
    $path_bits = func_get_args();
    return join(DIRECTORY_SEPARATOR,$path_bits);
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
    self::$class_map = array_merge(self::$class_map,$class_map);
  
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
   *  recursively get all the child directories in a given directory
   *  
   *  @param  string  $path a valid directory path
   *  @param  boolean $go_deep  if true, then get all the directories   
   *  @return array an array of sub-directories, 1 level deep if $go_deep = false, otherwise
   *                all directories   
   */
  private static function getPaths($path,$go_deep = true){
  
    // canary...
    if(empty($path)){ return array(); }//if
    
    $ret_list = glob(join(DIRECTORY_SEPARATOR,array($path,'*')),GLOB_ONLYDIR);
    if($go_deep){
    
      if(!empty($ret_list)){
        
        foreach($ret_list as $path){
          $ret_list = array_merge($ret_list,$this->getPaths($path));
        }//foreach
        
      }//if
      
    }//if
      
    return $ret_list;
      
  }//method

}//class     
