<?php

/**
 *  base methods that will be common to all helpers     
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 9-4-10
 *  @package montage 
 ******************************************************************************/
abstract class montage_helper extends montage_base_static {

  /**
   *  one of the problems with static classes is you can't easily override methods,
   *  but if your helper uses this class to make internal calls, then you can selectively
   *  override methods in the static classes
   *  
   *  WARNING you have to make sure the method you override returns an expected result, otherwise
   *    you can introduce annoying bugs            
   *  
   *  @since  9-4-10   
   *  @param  string  $class_name the name of the class, can be a parent class
   *  @param  string  $method the method to be called
   *  @param  array $args the arguments to pass to the class                  
   *  @return mixed whatever the called method returns
   */
  final protected static function call($class_name,$method,$args = array()){
  
    // canary...
    if(empty($class_name)){ throw new UnexpectedValueException('$class_name was empty'); }//if
    if(empty($method)){ throw new UnexpectedValueException('$method was empty'); }//if
  
    // get the best callback to make the static call...
    $callback = array(
      montage_core::getBestClassName($class_name),
      $method
    );
  
    return empty($args)
      ? call_user_func($callback)
      : call_user_func_array($callback,$args);
  
  }//method

}//class     
