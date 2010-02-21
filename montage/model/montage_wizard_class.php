<?php

/**
 *  handle autoloading duties    
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
final class montage_wizard extends montage_base_static {
  
  /**
   *  hold all the classes that could possibly be loadable
   *  
   *  the structure is: each key is a the class_key name, with path and name key/vals for
   *  each class_key
   *  
   *  @var  array
   */
  static $class_map = array();
  
  /**
   *  hold the core classes with their class_keys being the value
   *  
   *  @var  array
   */
  static $core_class_map = array(
    'MONTAGE_REQUEST' => '',
    'MONTAGE_SETTINGS' => '',
    'MONTAGE_RESPONSE' => ''
  );
  
  /**
   *  map all the child classes to their parents
   *  
   *  this is handy for making sure a given class inherits what it should
   *  
   *  @var  array
   */
  static $parent_class_map = array();
  
  /**
   *  start the loader class
   *  
   *  @parma  string  $controller the controller that will be used
   */
  static function start($controller){
  
    // load the model directories...
    self::setClasses(self::getCustomPath(self::getPath(),'model'));
    self::setClasses(self::getCustomPath(self::getAppPath(),'model'));
    
    // load the controller...
    self::setClasses(self::getCustomPath(self::getAppPath(),'controller',$controller));
    
    // set the defaul autoloader...
    self::set(array(__CLASS__,'get'));
  
  }//method
  
  /**
   *  get the best request class name
   *  
   *  @return string  a class name
   */
  static function getRequest(){
    return self::getClassName(self::$core_class_map['MONTAGE_REQUEST']);
  }//method
  
  /**
   *  get the best response class name
   *  
   *  @return string  a class name
   */
  static function getResponse(){
    return self::getClassName(self::$core_class_map['MONTAGE_RESPONSE']);
  }//method
  
  /**
   *  get all the filters that the app has defined
   *  
   *  @return array a list of class names that extend montage_filter
   */
  static function getFilters(){
  
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
   *  @return boolean true if $class_name is the name of a controller
   */
  static function isController($class_name){
    $class_key = self::getClassKey($class_name);
    return in_array($class_key,self::$parent_class_map['MONTAGE_CONTROLLER'],true);
  }//method
  
  /**
   *  set the montage root path
   *  
   *  @param  string  $val
   */
  static function setPath($val){ self::setField('montage_path',$val); }//method
  
  /**
   *  get the montage root path
   *  
   *  @return string
   */
  static function getPath(){ return self::getField('montage_path',''); }//method
  
  /**
   *  set the montage app root path
   *  
   *  @param  string  $val
   */
  static function setAppPath($val){ self::setField('montage_app_path',$val); }//method
  
  /**
   *  get the montage app root path
   *  
   *  @return string
   */
  static function getAppPath(){ return self::getField('montage_app_path',''); }//method

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
    return spl_autoload_register($callback,true);
  }//method

  /**
   *  load a class
   *  
   *  @return boolean      
   */
  static function get($class){
  
    // if you just get blank pages: http://www.php.net/manual/en/function.error-reporting.php#28181
    //  http://www.php.net/manual/en/function.include-once.php#53239
  
    out::e($class);
    return;
  
    $name_list = array();
    $name_list[] = sprintf('%s_class.php',$class);
    $name_list[] = sprintf('%s.class.php',$class);
    $name_list[] = sprintf('%s.php',$class);
    
    // go through each of the paths...
    foreach($this->path_map as $path_list){
    
      // now go through each path and check it agains $name_list...
      foreach($path_list as $path){
        
        foreach($name_list as $name){
          
          $filepath = join(DIRECTORY_SEPARATOR,array($path,$name));
          if(file_exists($filepath)){
            require($filepath);
            $this->class_map[$class] = $filepath;
            return true;
          }//if
          
        }//foreach
      
      }//foreach
    
    }//foreach

    /**
    $backtrace = debug_backtrace();
    $file = empty($backtrace[1]['file']) ? 'unknown' : $backtrace[1]['file'];
    $line = empty($backtrace[1]['line']) ? 'unknown' : $backtrace[1]['line']; 
    out::t();
    trigger_error($class.' was not found, called from '.$file.':'.$line,E_USER_ERROR);
    **/
    
    // can't use montage exception here because something might have failed before here...
    throw new Exception(sprintf('could not find class %s',$class));
    return false;
  
  }//method
  
  /**
   *  find all the classes in a given path and map them for later use
   *  
   *  @param  string  $path all the files in $path and its sub-dirs will be examined
   */
  static private function setClasses($path){
  
    // canary...
    if(empty($path)){
      throw new UnexpectedValueException('tried to get classes in an empty $path');
    }//if
    if(!is_dir($path)){
      throw new UnexpectedValueException(sprintf('"%s" is not a valid directory',$path));
    }//if
  
    $ret_map = array();
  
    $path_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach($path_iterator as $file){
      
      if($file->isFile()){
      
        $file_path = $file->getRealPath();
        $file_contents = file_get_contents($file_path);
        
        // find the class declaration lines...
        $line_matches = array();
        if(preg_match_all('#^[\w\s]*class\s+[^{]+#sium',$file_contents,$line_matches)){
        
          foreach($line_matches[0] as $line_match){
          
            // get the class...
            $class_match = array();
            if(preg_match('#class\s+(\S+)#',$line_match,$class_match)){
              
              $class_name = $class_match[1];
              $class_key = self::getClassKey($class_match[1]);
              
              // include the file since we need to check inheritance and stuff...
              include_once($file_path);
                
              // get all the classes this object extends...
              $parent_list = array();
              $class_reflector = new ReflectionClass($class_name);
              $parent_reflector = $class_reflector->getParentClass();
              while(!empty($parent_reflector)){
                $parent_list[] = $parent_reflector->getName();
                $parent_reflector = $parent_reflector->getParentClass();
              }//while
            
              // save the children mappings...
              foreach($parent_list as $parent_class_name){
                $parent_class_key = self::getClassKey($parent_class_name);
                if(!isset(self::$parent_class_map[$parent_class_key])){
                  self::$parent_class_map[$parent_class_key] = array();
                }//if
              
                self::$parent_class_map[$parent_class_key][] = $class_key;
              
              }//if
            
              if(isset(self::$core_class_map[$class_key])){
            
                self::$core_class_map[$class_key] = $class_key;
              
              }else{
                
                foreach($parent_list as $parent_class_name){
                
                  $parent_class_key = self::getClassKey($parent_class_name);
                  if(isset(self::$core_class_map[$parent_class_key])){
                    
                    self::$core_class_map[$parent_class_key] = $class_key;
                    break;
                    
                  }//if
                  
                }//foreach
                
              }//if/else
              
              self::$class_map[$class_key] = array(
                'path' => $file_path,
                'class_name' => $class_name
              );
            
            }//if
          
          }//foreach
        
        }//if
      
      }//if
      
    }//foreach
  
  }//method
  
  /**
   *  recursively get all the child directories in a given directory
   *  
   *  @param  string  $path a valid directory path
   *  @return array an array of $path and all its sub-directory paths
   */
  private static function getPaths($path){
  
    // canary...
    if(empty($path)){ return array(); }//if
    
    $ret_list = array($path);
    $path_list = glob(join(DIRECTORY_SEPARATOR,array($path,'*')),GLOB_ONLYDIR);
    if(!empty($path_list)){
      
      foreach($path_list as $path){
        $ret_list = array_merge($ret_list,$this->getPaths($path));
      }//foreach
      
    }//if
      
    return $ret_list;
      
  }//method
  
  /**
   *  get the class name from the given $class_key
   *  
   *  @param  string  $class_key      
   *  @return string
   */
  static private function getClassName($class_key){
    $ret_str = '';
    if(isset(self::$class_map[$class_key])){
      $ret_str = self::$class_map[$class_key]['class_name'];
    }//if
    return $ret_str;
  }//method
  
  static private function getClassKey($class_name){
    return mb_strtoupper($class_name);
  }//method

}//class     
