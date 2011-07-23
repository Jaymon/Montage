<?php
/**
 *  in order to substitute your DI container for the default container, your class
 *  will need to implement this interface   
 * 
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 7-22-11
 *  @package montage
 ******************************************************************************/
namespace Montage\Dependency;

interface Containable {
  
  /**
   *  the callback will be triggered when the instance is about to be created
   *
   *  the callback should take an instance of container and the method params:
   *  and return params. Eg, callback(Container $container,array $params){ return $params; }      
   *
   *  @since  6-29-11   
   *  @param  string  $class_name the class this will be active for
   *  @param  callback $callback a valid php callback         
   */
  public function onCreate($class_name,$callback);
  
  /**
   *  the callback will be triggered when the instance was just created
   *
   *  the callback should take an instance of container and the just created instance:
   *  Eg, callback(Container $container,$instance){}      
   *
   *  @since  6-30-11   
   *  @param  string  $class_name the class this will be active for
   *  @param  callback $callback a valid php callback         
   */
  public function onCreated($class_name,$callback);
  
  /**
   *  true if there is an existing instance with the $class_name
   *  
   *  @param  string  $class_name
   *  @return boolean
   */
  public function hasInstance($class_name);
  
  /**
   *  set the given instance using key $class_name
   *  
   *  @param  string  $class_name
   *  @param  object  $instance the instance to set at class_name
   */
  public function setInstance($class_name,$instance);
  
  /**
   *  when you know what class you specifically want, use this method over {@link findInstance()}
   *
   *  @param  string  $class_name the name of the class you are looking for
   *  @param  array $params any params you want to pass into the constructor of the instance      
   */
  public function getInstance($class_name,$params = array());
  
  /**
   *  create and return an instance of $class_name with the given $construct_args
   *  
   *  @param  string  $class_name the name of the class to instantiate
   *  @param  array $construct_args similar to call_user_func_array, if the $class_name's
   *                                __construct() method takes 2 arguments (eg, __construct($one,$two)
   *                                then you would pass in array(1,2) and $one = 1, $two = 2               
   *  @return object
   */
  public function createInstance($class_name,$params = array());
  
  /**
   *  call the $method of the object $instance using $params normalized with {@link normalizeParams()}
   *  
   *  basically, this will magically satisfy any object params if they exist handling the 
   *  dependencies of the method call         
   *
   *  @since  6-23-11   
   *  @param  object  $instance the object that will call the method
   *  @param  string  $method the method name
   *  @param  array $params see {@link normalizeParams()} for how these are resolved
   *  @return mixed whatever the method returns
   */
  public function callMethod($instance,$method,array $params = array());
  
  /**
   *  normalize one param of a method
   *  
   *  @since  7-5-11
   *  @param  ReflectionParameter $rparam
   *  @param  array $params see {@link normalizeParams()} for description of the $params array
   *  @return mixed the normalized param
   */
  public function normalizeParam(\ReflectionParameter $rparam,array $params = array());
  
  /**
   *  normalize the params of the $rmethod to allow a valid call
   *
   *  @example
   *    // method signature: foo($bar = '',$baz = '',SomeClass $che);
   *    $rmethod = new ReflectionMethod($instance,'foo');
   *    $this->normalizeParams($rmethod,array('che','cha') // retuns array('che','cha',automatically created SomeClass Instance)
   *    $this->normalizeParams($rmethod,array('che') // retuns array('che','',automatically created SomeClass Instance)
   *    $this->normalizeParams($rmethod,array('che' => new SomeClass(),'bar' => '') // retuns array('','',passed in SomeClass Instance)       
   *        
   *  @param  ReflectionMethod  $rmethod  the reflection of the method
   *  @param  array $params any params you want to pass to override any magically
   *                        discovered params
   *  @return array the params ready to be passed to the method using something like call_user_func_array
   */
  public function normalizeParams(\ReflectionMethod $rmethod,array $params = array());

}//class
