<?php

/**
 *  access class to get instances of other classes     
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 5-4-10
 *  @package montage 
 ******************************************************************************/
class montage_factory {

  /**
   *  create and return an instance of $class_name
   *  
   *  this only works for classes that don't take any arguments in their constructor
   *      
   *  @param  string  $class_name the name of the class whose instance should be returned
   *  @param  array $construct_args see {@link getNewInstance()} description   
   *  @param  string  $parent_name  the name of the parent class, if not empty then $class_name
   *                                must be a child of $parent_name, otherwise null is returned  
   *  @return object
   */
  static function getInstance($class_name,$contruct_args = array(),$parent_name = ''){
    $class_name = montage_core::getClassName($class_name,$parent_name);
    return self::getNewInstance($class_name,$construct_args);
  }//method
  
  /**
   *  create and return the best instance of $class_name
   *  
   *  this only works for classes that don't take any arguments in their constructor
   *      
   *  @param  string  $class_name the name of the class whose instance should be returned
   *  @param  array $construct_args see {@link getNewInstance()} description    
   *  @param  string  $parent_name  the name of the parent class, if not empty then $class_name
   *                                must be a child of $parent_name, otherwise null is returned  
   *  @return object
   */
  static function getBestInstance($class_name,$contruct_args = array(),$parent_name = ''){
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
    
    if(empty($contstruct_args)){
    
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
