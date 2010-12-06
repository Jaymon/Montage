<?php

/**
 *  this is Montage's class handler
 *  
 *  Everything you want to know about where classes are kept, or what classes are loaded
 *  can be found in this class   
 *  
 *  this class is final to keep everything uniform since this class is used in the 
 *  core to start classes, a developer overriding it would technically be possible
 *  (core uses parent, developer uses their factory class that extends this?) but
 *  it just seems weird to me   
 *  
 *  @example  
 *    // class child extends parent...
 *    echo get_class(montage_factory::getBestInstance('parent')); // -> 'child'
 *    echo get_class(montage_factory::getInstance('parent')); // -> 'parent'
 *    
 *    // now, we create a third class, grandchild that extends child...   
 *    echo get_class(montage_factory::getBestInstance('child')); // -> 'grandchild'
 *    echo get_class(montage_factory::getBestInstance('parent')); // -> 'grandchild'    
 *    echo get_class(montage_factory::getInstance('child')); // -> 'child' 
 * 
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-6-10
 *  @package montage 
 ******************************************************************************/
final class montage_class {

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

  public function __construct(montage_cache $cache){}//method

  /**
   *  get the absolute most child for the given class
   *  (eg, the last class to extend any class that extends the passed in $class_key)
   *  
   *  @param  string  $class_name see {@link getPossibleClassNames()}
   *  @param  string  $parent_name  the name of the parent class, if not empty then $class_name
   *                                must be a child of $parent_name   
   *  @return string  the child class name   
   *  @throws DomainException if the class_key is extended by more than one unrelated child   
   */
  static function getName($class_name,$parent_name = ''){
  
    $ret_str = '';
    $class_name_list = $this->getPossibleClassNames($class_name);
    foreach($class_name_list as $class_name){
  
      $class_key = $this->getClassKey($class_name);
      if(isset(self::$parent_class_map[$class_key])){
      
        $child_class_list = self::$parent_class_map[$class_key];
        foreach($child_class_list as $child_class_key){
        
          // we're looking for the descendant most class...
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
        
        if(empty($parent_name) || $this->isChild($ret_str,$parent_name)){ break; }//if
        
      }else{
      
        if(empty($parent_name) || $this->isChild($class_key,$parent_name)){
          $ret_str = self::$class_map[$class_key]['class_name'];
          break;
        }//if
      
      }//if/else
      
    }//foreach
  
    return $ret_str;
  
  }//method

  /**
   *  get the class name from the given potential $class_name
   *  
   *  this is useful because since we standardize all the class names we can find
   *  a class whether we pass in ClassName Classname className. Basically, montage
   *  classes are case-insensitive            
   *  
   *  @param  string|array  $class_name see {@link getPossibleClassNames()}  
   *  @param  string  $parent_name  the name of the parent class, if not empty then $class_name
   *                                must be a child of $parent_name       
   *  @return string
   */
  /* public function xgetName($class_name,$parent_name = ''){
    
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
    
  }//method */
  
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
  protected function getPossibleNames($class_name_list){
  
    // canary....
    // class name isn't an array, so return as one...
    if(!is_array($class_name_list)){ return (array)$class_name_list; }//if
    // class name is an array with one key, the class's name...
    if(empty($class_name_list[1])){
      if(empty($class_name_list[0])){
        return array();
      }else{
        return (array)$class_name_list[0];
      }//if/else
    }//if
    
    // assure the array is of the form: array(prefix_list,name_list,postfix_list)
    $class_name_list[0] = (array)$class_name_list[0];
    $class_name_list[1] = (array)$class_name_list[1];
    
    if(empty($class_name_list[2])){
    
      if(empty($class_name_list[0])){
        $class_name_list[] = array();
      }else{
        array_unshift($class_name_list,array());
      }//if/else
      
    }else{
    
      $class_name_list[2] = (array)$class_name_list[2];
      
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




  public static function callBestMethod($class_name,$method,$args = array()){
  
    // canary...
    if(empty($class_name)){
      throw new UnexpectedValueException('$class_name was empty');
    }else{
      $class_name = montage_core::getBestClassName($class_name);
      if(empty($class_name)){ 
        throw new InvalidArgumentException(
          sprintf('No class (%s) exists',$class_name)
        );
      }//if
    }//if/else
    if(empty($method)){ throw new UnexpectedValueException('$method was empty'); }//if
    
    $rclass = new ReflectionClass($class_name);
    $rmethod = $rclass->getMethod($method);
    
    // @todo: find out what $object means
    return $rmethod->invokeArgs($object,$args);
  
    return empty($args)
      ? call_user_func($callback)
      : call_user_func_array($callback,$args);
  
  }//method

  /**
   *  create and return an instance of $class_name
   *      
   *  @param  string  $class_name the name of the class whose instance should be returned
   *  @param  array $construct_args see {@link getNewInstance()} description   
   *  @param  string  $parent_name  the name of the parent class, if not empty then $class_name
   *                                must be a child of $parent_name, otherwise null is returned  
   *  @return object
   */
  static function getInstance($class_name,$construct_args = array(),$parent_name = ''){
    $class_name = montage_core::getClassName($class_name,$parent_name);
    return self::getNewInstance($class_name,$construct_args);
  }//method
  
  /**
   *  create and return the best instance of $class_name
   *  
   *  the best instance is defined as the final child, eg, if you had three classes:
   *    1 - grandchild extends child
   *    2 - child extends parent
   *    3 - parent
   *    
   *  and you passed in $class_name = child, then a grandchild instance would be returned,
   *  if you actually wanted to get a child instance, you would use {@link getInstance()} instead.
   *  
   *  You can further restrict the returned class by passing in $parent_name, if it isn't empty
   *  then the $class_name will have to inherit from $parent_name to be returned
   *  
   *  so, getBestInstance('grandchild',array(),'grandparent') would fail since child isn't a descendant
   *  of grandparent.                    
   *      
   *  @param  string  $class_name the name of the class whose instance should be returned
   *  @param  array $construct_args see {@link getNewInstance()} description    
   *  @param  string  $parent_name  the name of the parent class, if not empty then $class_name
   *                                must be a child of $parent_name, otherwise null is returned  
   *  @return object
   */
  static function getBestInstance($class_name,$construct_args = array(),$parent_name = ''){
    $class_name = montage_core::getBestClassName($class_name,$parent_name);
    return self::getNewInstance($class_name,$construct_args);
  }//method
  
  /**
   *  create and return an instance of $class_name with the given $construct_args
   *  
   *  @param  string  $class_name the name of the class to instantiate
   *  @param  array $construct_args similar to call_user_func_array, if the $class_name's
   *                                __construct() method takes 2 arguments (eg, __construct($one,$two)
   *                                then you would pass in array(1,2) and $one = 1, $two = 2               
   *  @return object
   */
  static private function getNewInstance($class_name,$construct_args = array()){
  
    // canary...
    if(empty($class_name)){ return null; }//if
  
    $ret_instance = null;
    
    if(empty($construct_args)){
    
      $ret_instance = new $class_name();
    
    }else{
    
      // http://www.php.net/manual/en/reflectionclass.newinstanceargs.php#95137
    
      $rclass = new ReflectionClass($class_name);
      
      // canary, make sure there is a __construct() method since we are passing in arguments...
      $rconstruct = $rclass->getConstructor();
      if(empty($rconstruct)){
        throw new InvalidArgumentException(
          sprintf(
            'You tried to create an instance of %s with %s constructor arguments, but the class %s '
            .'has no __construct() method, so no constructor arguments can be used to instantiate it. '
            .'Please add %s::__construct(), or don\'t pass in any constructor arguments',
            $class_name,
            count($construct_args),
            $class_name,
            $class_name
          )
        );
      }//if
      
      $ret_instance = $rclass->newInstanceArgs($construct_args);
    
    }//if/else
  
    return $ret_instance;
  
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
   *  set a path where classes can be found
   *  
   *  @param  montage_path  $path
   *  @return string
   */
  public function setPath(montage_path $path){

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

}//class     
