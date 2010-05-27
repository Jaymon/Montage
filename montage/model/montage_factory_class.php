<?php

/**
 *  use to get instances of other classes
 *  
 *  this allows you to easily get class objects based on stuff like the parent so 
 *  you can extend classes easily and then have the right class be used in all your
 *  application
 *  
 *  {@link getBestInstance()} is used in the core to get the best request, response, etc. 
 *  classes, allowing the developer to easily extend the base default classes and have all
 *  the code still work    
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
 *  @since 5-4-10
 *  @package montage 
 ******************************************************************************/
final class montage_factory extends montage_base_static {

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

}//class     
