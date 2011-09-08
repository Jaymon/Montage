<?php
/**
 *  the Dependancy Injection Container is like a service locator  
 * 
 *  @todo injectPublicParams() - inject instances via a public param, the problem
 *  is you would have to docblock the param with a @var field telling what object
 *  you want, and that would have to use the full namespaced class name   
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-1-11
 *  @package montage
 ******************************************************************************/
namespace Montage\Dependency;

use ReflectionObject, ReflectionClass, ReflectionMethod, ReflectionParameter;
use Montage\Dependency\Container;

class ReflectionContainer extends Container {

  /**
   *  the reflection class is kept outside the {@link $instance_map} because it
   *  is needed for lots of things   
   *
   *  @var  string  the passed in Reflection instances full class name   
   */
  protected $reflection = null;

  /**
   *  create an instance of this class
   */
  public function __construct(Reflection $reflection){
  
    $this->reflection = $reflection;
    $this->setInstance('reflection',$reflection);
    
    parent::__construct();
    
  }//method
  
  /**
   *  set the given instance using key $class_name
   *  
   *  @param  string  $class_name
   *  @param  object  $instance the instance to set at class_name
   */
  public function setInstance($class_name,$instance){
  
    // canary...
    if(!is_object($instance)){ throw new \InvalidArgumentException('$instance was empty'); }//if
  
    $reflection = $this->getReflection();
    $instance_name = get_class($instance);
    
    $class_list = array();
    
    if($reflection->hasClass($instance_name)){
    
      $class_map = $reflection->getClass($instance_name);
      $class_list = $class_map['dependencies'];
      $class_list[] = $instance_name;
    
    }else{
    
      // since the internal reflection object doesn't know about this instance, we'll have to build
      // the dependency list the old fashioned way
    
      $class = $instance_name;
      
      // add all parent classes...
      for($class_list[] = $class; $class = get_parent_class($class); $class_list[] = $class);
      
      // add all interfaces...
      if($interface_list = class_implements($instance_name)){
        $class_list = array_merge($class_list,$interface_list);
      }//if
      
    }//if/else
    
    // add the class name key...
    if(!empty($class_name)){ $class_list[] = $class_name; }//if
    
    // save the instance in every found key...
    foreach($class_list as $cn){
      
      $class_key = $this->getKey($cn);
      $this->instance_map[$class_key] = $instance;
      
    }//foreach
    
  }//method
  
  /**
   *  find the class name that will be used to create the instance
   *
   *  @param  string  $class_name the name of the class you are looking for
   *  @return string  the class name that will be used to create the instance      
   */
  public function getClassName($class_name){

    $ret_class_name = '';
    $reflection = $this->getReflection();
    
    if($reflection->hasClass($class_name)){

      $ret_class_name = $reflection->findClassName($class_name);
      
    }else if(class_exists($class_name)){
      
      $ret_class_name = $class_name;
      
    }//if/else if
  
    if(empty($ret_class_name)){
    
      throw new \UnexpectedValueException(
        sprintf('Unable to find suitable class name using key "%s"',$class_name)
      );
      
    }//if

    return $ret_class_name;
    
  }//method
  
  /**
   *  get the key the instance will use for the instance map
   *
   *  @since  6-13-11
   *  @Param  string  $class_name
   *  @return string    
   */
  protected function getKey($class_name){
  
    return $this->getReflection()->normalizeClassName($class_name);
    
  }//method
  
  /**
   *  get the internal reflection instance this class uses
   *  
   *  @return Montage\Dependency\Reflection
   */
  protected function getReflection(){ return $this->reflection; }//method
  
  /**
   *  handle actually running the onCreate callback
   *  
   *  @since  8-25-11
   *  @param  string  $class_key
   *  @param  array $params            
   *  @return array the same $params filtered through the callback
   */
  protected function handleOnCreate($class_key,array $params){
  
    $reflection = $this->getReflection();
    $class_map = $reflection->getClass($class_key);
    $cb_class_list = $class_map['dependencies'];
    $cb_class_list[] = $class_key;
    
    foreach($cb_class_list as $cb_class_name){
    
      $cb_class_key = $this->getKey($cb_class_name);
      $params = parent::handleOnCreate($cb_class_key,$params);
    
    }//foreach
  
    return $params;
  
  }//method
  
  /**
   *  handle actually running the onCreated callback
   *  
   *  @since  8-25-11
   *  @param  string  $class_key
   *  @param  object  $instance the newly created instance   
   */
  protected function handleOnCreated($class_key,$instance){
    
    $reflection = $this->getReflection();
    $class_map = $reflection->getClass($class_key);
    $cb_class_list = $class_map['dependencies'];
    $cb_class_list[] = $class_key;
    
    foreach($cb_class_list as $cb_class_name){
    
      $cb_class_key = $this->getKey($cb_class_name);
      $params = parent::handleOnCreated($cb_class_key,$instance);
    
    }//foreach
    
  }//method
  
  /**
   *  override parent to cache method names and params into the {@link $inject_map}
   * 
   *  @since  9-7-11    
   *  @see  parent::injectInstance()
   */
  protected function injectInstance($instance,\ReflectionFunctionAbstract $rmethod){
  
    $ret_bool = parent::injectInstance($instance,$rmethod);
  
    if($ret_bool){
    
      $method_name = $rmethod->getName();
      $class_name = $this->getInjectClassName($rmethod);
      $reflection = $this->getReflection();
      $inject_map = array();
      
      if($this->isInjectMethod($rmethod)){
      
        $inject_map['inject'] = array();
        $inject_map['inject'][$method_name] = $class_name;
      
      }else if($this->isSetMethod($rmethod)){
      
        $inject_map['set'] = array();
        $inject_map['set'][$method_name] = $class_name;
      
      }//if/else if
      
      $class_map = $reflection->getClass(get_class($instance));
      $info_map = array();
      
      // update the class info with the new info...
      if(isset($class_map['info'])){ $info_map = $class_map['info']; }//if
      if(!isset($info_map['inject_map'])){ $info_map['inject_map'] = array(); }//if
      
      foreach($inject_map as $type => $list){
        
        foreach($list as $mn => $mc){
        
          if(!isset($info_map['inject_map'][$type])){ $info_map['inject_map'][$type] = array(); }//if
        
          $info_map['inject_map'][$type][$mn] = $mc;
          
        }//foreach
      
      }//foreach
    
      $reflection->addClassInfo(get_class($instance),$info_map);
    
    }//if
  
    return $ret_bool;
    
  }//method
  
  /**
   *  override parent to check cache before manually injecting dependencies
   * 
   *  @since  9-7-11
   *  @see  parent::injectInstance()
   */
  protected function methodInjection($instance,\ReflectionClass $rclass = null){
  
    $reflection = $this->getReflection();
    $class_map = $reflection->getClass(get_class($instance));
    if(isset($class_map['info']['inject_map'])){
    
      $inject_map = $class_map['info']['inject_map'];
    
      try{
        
        if(isset($inject_map['inject'])){
          
          foreach($inject_map['inject'] as $method_name => $class_name){
          
            $instance->{$method_name}($this->getInstance($class_name));
          
          }//foreach
          
        }//if
        
        if(isset($inject_map['set'])){
          
          foreach($inject_map['set'] as $method_name => $class_name){
          
            if($this->hasInstance($class_name)){
              
              $instance->{$method_name}($this->getInstance($class_name));
              
            }//if
          
          }//foreach
          
        }//if
        
      }catch(\Exception $e){
        // exceptions aren't fatal, just don't set the dependency
      }//try/catch
    
      $ret = $instance;
    
    }else{
    
      $ret = parent::methodInjection($instance,$rclass);
    
    }//if/else
  
    return $ret;
  
  }//method
  
  /**
   *  reset the container to its virgin state
   *     
   *  @since  8-22-11         
   */
  public function reset(){
  
    parent::reset();
    $this->setInstance('reflection',$this->reflection);
  
  }//method

}//class
