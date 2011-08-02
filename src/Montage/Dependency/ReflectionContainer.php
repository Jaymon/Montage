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
    $this->setInstance('',$reflection);
    $this->setInstance('',$this); // we want to be able to inject this also
    
  }//method
  
  /**
   *  when "finding" a class, sometimes that class will have multiple children, this
   *  lets you set which child class you would want returned
   *  
   *  @since  6-18-11
   *  @param  string  $class_name the class that might be passed into {@link findInstance()}
   *  @param  string  $preferred_class_name the class that will be searched for instead of $class_name
   */
  public function setPreferred($class_name,$preferred_class_name){
  
    $class_key = $this->getKey($class_name);
    ///$preferred_class_key = $this->getKey($preferred_class_name);
    $this->preferred_map[$class_key] = $preferred_class_name;
  
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
   *  find the absolute descendant of the class(es) you pass in
   *
   *  @param  string  $class_name the name of the class you are looking for
   *  @param  array $params any params you want to pass into the constructor of the instance      
   */
  public function getInstance($class_name,$params = array()){

    $ret_instance = null;
    $class_name = $class_name;
    $class_key = $this->getKey($class_name);
    $cn_key = $class_key;
    $params = (array)$params;
    $reflection = $this->getReflection();
    $instance_class_name = '';
    
    if(isset($this->instance_map[$cn_key])){ // check to see if there is already an instance
    
      $ret_instance = $this->instance_map[$cn_key];
    
    }else if(isset($this->preferred_map[$cn_key])){ // check to see if there has been a preferred class set
  
      $cn_key = $this->preferred_map[$cn_key];
    
    }//if
      
    if(empty($ret_instance)){
    
      if($reflection->hasClass($cn_key)){

        $instance_class_name = $reflection->findClassName($cn_key);
        
      }else if(class_exists($cn_key)){
        
        $instance_class_name = $cn_key;
        
      }//if/else if
    
      if(empty($instance_class_name)){
      
        throw new \UnexpectedValueException(
          sprintf('Unable to find suitable child class using "%s"',$class_name)
        );
        
      }else{

        if(isset($this->instance_map[$class_key])){
        
          $ret_instance = $this->instance_map[$class_key];
          
        }else{
        
          // handle on create...
          // @todo  this should probably be moved to Container::createInstance()
          // I haven't done that because notice we check $class_key but then use $instance_class_name
          // to create the instance
          if(isset($this->on_create_map[$class_key])){
          
            $params = call_user_func($this->on_create_map[$class_key],$this,$params);
            
            if(!is_array($params)){
              throw new \UnexpectedValueException(
                sprintf('An array should have been returned from on create callback for %s',$class_key)
              );
            }//if
            
          }//if
        
          $ret_instance = $this->createInstance($instance_class_name,$params);
          
          // handle on created...
          // @todo  this should probably be moved to Container::createInstance()
          if(isset($this->on_created_map[$class_key])){
            call_user_func($this->on_created_map[$class_key],$this,$ret_instance);
          }//if
          
          $this->setInstance($instance_class_name,$ret_instance);
        
        }//if/else
        
      }//if/else
      
    }//if
      
    return $ret_instance;
    
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

}//class
