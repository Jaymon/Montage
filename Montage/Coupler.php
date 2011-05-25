<?php
/**
 *  http://en.wikipedia.org/wiki/Coupler
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 5-24-11
 *  @package montage 
 ******************************************************************************/
namespace Montage;

use Montage\Classes as MontageClass;

use out;

class Coupler {

  protected $instance_map = array();

  /**
   *  final so as not to interfere with the creation of this class automatically
   */
  final public function __construct(MontageClass $classes){
  
    $class_name = $classes->normalizeClassName(get_class($classes));
    $this->instance_map['classes'] = $classes;
  
  }//method
  
  public function setInstance($instance){
  
  
  
  }//method
  
  public function findInstance($class_name){

    $found_key = '';
    $key = $this->normalizeClassName($class_name);
  
    if(isset($this->parent_class_map[$key])){
    
      $child_class_list = $this->parent_class_map[$key];
      foreach($child_class_list as $child_key){
      
        // we're looking for the descendant most class...
        if(!isset($this->parent_class_map[$child_key])){
        
          if(empty($found_key)){
          
            $found_key = $child_key;
          
          }else{
            
            throw new LogicException(
              sprintf(
                'the given $class_name (%s) has divergent children %s and %s (those 2 classes extend ' 
                .'%s but are not related to each other) so a best class cannot be found.',
                $class_name,
                $found_key,
                $child_key,
                $key
              )
            );
            
          }//if/else
        
        }//if
      
      }//foreach

    }else{
    
      if(isset($this->class_map[$key])){
    
        $found_key = $key;
        
      }else{
      
        throw new UnexpectedValueException(sprintf('no class %s was found',$class_name));
      
      }//if/else
      
    }//if/else
    
    if(!empty($found_key)){
    
      $instance_class_name = $this->class_map[$found_key]['class'];
    
    }//if

    return $this->getNewInstance($instance_class_name);
    
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
  protected function getNewInstance($class_name,$construct_args = array()){
  
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

}//method
