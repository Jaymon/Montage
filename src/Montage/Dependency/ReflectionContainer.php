<?php
/**
 *  this Container uses reflection to decide what class to create
 *  
 *  usually, when using this class you will pass in the wanted class's full namespaced
 *  name and this class will then use reflection to get the absolute child and create that
 *  instead of the parent class you passed in.        
 *  
 *  @version 0.3
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-1-11
 *  @package montage
 ******************************************************************************/
namespace Montage\Dependency;

use ReflectionObject, ReflectionClass, ReflectionMethod, ReflectionParameter, ReflectionProperty;
use Montage\Dependency\Container;
use Montage\Reflection\ReflectionFramework;
use Montage\Annotation\ParamAnnotation;

class ReflectionContainer extends Container {

  /**
   *  the reflection class is kept outside the {@link $instance_map} because it
   *  is needed for lots of things   
   *
   *  @var  ReflectionFramework   
   */
  protected $reflection = null;

  /**
   *  create an instance of this class
   *  
   *  @param  Montage\Reflection\ReflectionFramework  $reflection
   */
  public function __construct(ReflectionFramework $reflection){
  
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
    $class_list = $reflection->getRelated(get_class($instance));
    
    // add the class name key (it might not be a namespaced\classname, that's why we add it)..
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
   *  @param  string  $class_name
   *  @param  array $params            
   *  @return array the same $params filtered through the callback
   */
  protected function handleOnCreate($class_name,array $params){
  
    $reflection = $this->getReflection();
    $cb_class_list = $reflection->getParents($class_name);
    if(!in_array($class_name,$cb_class_list)){ $cb_class_list[] = $class_name; }//if
    
    $this->handleCreateDependencies($class_name);
    
    foreach($cb_class_list as $cb_class_name){
    
      $params = $this->_handleOnCreate($cb_class_name,$params);
    
    }//foreach
  
    return $params;
  
  }//method
  
  /**
   *  because this handleOnCreate loops through all the classes, we use this to
   *  call {@link parent::handleOnCreate()}   
   *
   *  @since  12-13-11   
   *  @see  handleOnCreate()
   */
  protected function _handleOnCreate($class_name,array $params){
    return parent::handleOnCreate($class_name,$params);
  }//method
  
  /**
   *  handle actually running the onCreated callback
   *  
   *  @since  8-25-11
   *  @param  string  $class_name
   *  @param  object  $instance the newly created instance   
   */
  protected function handleOnCreated($class_name,$instance){
    
    $reflection = $this->getReflection();
    $cb_class_list = $reflection->getParents($class_name);
    if(!in_array($class_name,$cb_class_list)){ $cb_class_list[] = $class_name; }//if
    
    $this->handleCreatedDependencies($instance);
    
    foreach($cb_class_list as $cb_class_name){
    
      $this->_handleOnCreated($cb_class_name,$instance);
    
    }//foreach
    
  }//method
  
  /**
   *  because this handleOnCreated loops through all the classes, we use this to
   *  call {@link parent::handleOnCreated()}   
   *
   *  @since  12-13-11
   *  @see  handleOnCreated()
   */
  protected function _handleOnCreated($class_name,$instance){
    return parent::handleOnCreated($class_name,$instance);
  }//method
  
  /**
   *  check static methods and params and set those values before creating the class
   *      
   *  this is handy when you want to use a static value in the constructor of the class
   *  (as I just recently wanted to do)
   *  
   *  @since  1-12-12
   *  @param  string  $class_name same class name as all the other methods
   */
  protected function handleCreateDependencies($class_name){
  
    $dependency_list = array();
    $instance_class_name = $this->getClassName($class_name);
    $reflection = $this->getReflection();
    $rclass = new ReflectionClass($instance_class_name);
    
    // first check cache, if it doesn't exist, build it and save it
    $class_map = $reflection->getClass($instance_class_name);
    if(!isset($class_map['info']['create'])){
      
      // find the dependency methods
      $filter = ReflectionMethod::IS_PUBLIC;
      $rmethod_list = $rclass->getMethods($filter);
      foreach($rmethod_list as $rmethod){
  
        if($rmethod->isStatic()){
  
          if($map = $this->getMethodDependencyMap($rmethod)){ $dependency_list[] = $map; }//if
          
        }//if
  
      }//foreach
      
      $filter = ReflectionProperty::IS_PUBLIC;
      $rparam_list = $rclass->getProperties($filter);
      foreach($rparam_list as $rparam){
  
        if($rparam->isStatic()){
  
          if($map = $this->getParamDependencyMap($rparam)){ $dependency_list[] = $map; }//if
          
        }//if
      
      }//foreach
      
      $reflection->addClassInfo($instance_class_name,array('create' => $dependency_list));
      
    }else{
    
      $dependency_list = $class_map['info']['create'];
    
    }//if/else

    foreach($dependency_list as $map){ $this->injectInstance($rclass,$map); }//foreach
    
  }//method
  
  /**
   *  automatically inject dependencies in the recently created instance
   *  
   *  currently, there are three ways to do dependencies:
   *    injectClassName(\ClassName $instance){} // instance must exist
   *    setClassName(\ClassName $instance){} // instance is optional
   *    / **
   *     *  @var  \Namespace\ClassName
   *     * /        
   *    public $instance = null; // this public property will be set with the instance
   *
   *  @param  object  $instance
   *  @return object  the same instance with dependencies injected
   */
  protected function handleCreatedDependencies($instance){
  
    // canary...
    if(empty($instance)){ throw new \InvalidArgumentException('$instance was empty'); }//if
    
    $dependency_list = array();
    $robj = new ReflectionObject($instance);
    $reflection = $this->getReflection();
    
    // first check cache, if it doesn't exist, build it and save it
    $class_map = $reflection->getClass($robj->getName());
    if(!isset($class_map['info']['created'])){
      
      // find the dependency methods
      $rmethod_list = $robj->getMethods(ReflectionMethod::IS_PUBLIC);
      foreach($rmethod_list as $rmethod){
  
        if(!$rmethod->isStatic()){
    
          if($map = $this->getMethodDependencyMap($rmethod,$instance)){
        
            $dependency_list[] = $map;  
          
          }//if
          
        }//if
  
      }//foreach
      
      $rparam_list = $robj->getProperties(ReflectionProperty::IS_PUBLIC);
      foreach($rparam_list as $rparam){

        if(!$rparam->isStatic()){
    
          if($map = $this->getParamDependencyMap($rparam,$instance)){
        
            $dependency_list[] = $map;  
          
          }//if
          
        }//if
      
      }//foreach
      
      $reflection->addClassInfo($robj->getName(),array('created' => $dependency_list));
      
    }else{
    
      $dependency_list = $class_map['info']['created'];
    
    }//if/else

    foreach($dependency_list as $map){
    
      $this->injectInstance($robj,$map,$instance);
    
    }//foreach
    
    return $instance;
  
  }//method
  
  /**
   *  inject a dependency using: $instance::$rmethod({@link getInjectClassName()})
   *
   *  @since  9-7-11
   *     
   *  @param  \ReflectionClass  $rclass info about the class being injected into
   *  @param  array $map  information about the param
   *  @param  object|null $instance   
   *  @return object|null the $instance, with dependency in $map injected into it
   */
  protected function injectInstance(\ReflectionClass $rclass,array $map,$instance = null){
  
    $class_name = $map['class_name'];
    $name = $map['name'];
  
    if(empty($map['method'])){
    
      $rparam = $rclass->getProperty($name);
      $param_instance = $rparam->isStatic() ? null : $instance;
    
      // before trying to inject the instance, make sure it isn't already set
      if($rparam->getValue($param_instance) === null){
      
        $rparam->setValue($param_instance,$this->getInstance($class_name));
        
      }//if
    
    }else{
    
      $callback = array();
    
      // build callback
      if(empty($map['static'])){
      
        $callback = array($instance,$name);
      
      }else{
      
        $callback = array($rclass->getName(),$name);
      
      }//if/else
    
      if(empty($map['optional'])){
        
        // fail if the injected instance can't be successfully created
        
        call_user_func($callback,$this->getInstance($class_name));
          
      }else{
      
        // if we don't already have an active instance, let's not set the dependency
      
        try{
        
          if($this->hasInstance($class_name)){
            
            call_user_func($callback,$this->getInstance($class_name));
            
          }//if
          
        }catch(\Exception $e){
          // exceptions aren't fatal, just don't set the dependency
        }//try/catch
          
      }//if/else
      
    }//if/else
  
    return $instance;
  
  }//method
  
  /**
   *  returns a dependency map if the method is a valid dependency inject method
   *  
   *  @since  9-7-11   
   *  @param  ReflectionMethod  $method  the reflection of the method/function
   *  @param  object|null $instance the object instance, or null if it hasn't been created yet             
   *  @return array
   */
  protected function getParamDependencyMap(\ReflectionProperty $rparam,$instance = null){
  
    $val = empty($instance)
      ? $rparam->getValue()
      : $rparam->getValue($instance);
  
    $docblock = $rparam->getDocComment();
  
    // canary
    if($val !== null){ return array(); }//if
    if(empty($docblock)){ return array(); }//if
  
    $ret_map = array();
    $param_name = $rparam->getName();
    $class_name = '';

    $annotation = new ParamAnnotation($rparam);
    $class_name = $annotation->getClassName();
      
    if(!empty($class_name)){
      
      // fix namespace...
      if($class_name[0] !== '\\'){
      
        $rclass = $rparam->getDeclaringClass();
        $class_name = sprintf('%s\\%s',$rclass->getNamespaceName(),$class_name);
      
      }//if
      
      $ret_map['name'] = $param_name;
      $ret_map['optional'] = false;
      $ret_map['method'] = false;
      $ret_map['static'] = $rparam->isStatic();
      $ret_map['class_name'] = $class_name;
      
    }//if
    
    return $ret_map;
  
  }//method
  
  /**
   *  returns a dependency map if the method is a valid dependency inject method
   *  
   *  @since  9-7-11   
   *  @param  ReflectionMethod  $method  the reflection of the method/function           
   *  @return array
   */
  protected function getMethodDependencyMap(\ReflectionMethod $rmethod,$instance = null){
  
    // canary
    if($rmethod->getNumberOfParameters() !== 1){ return array(); }//if
  
    $ret_map = array();
    $method_name = $rmethod->getName();
    
    if(preg_match('#^inject#i',$method_name)){
    
      $ret_map['name'] = $method_name;
      $ret_map['optional'] = false;
    
    }else if(preg_match('#^set#i',$method_name)){
    
      $ret_map['name'] = $method_name;
      $ret_map['optional'] = true;
    
    }//if/else if
    
    if(!empty($ret_map)){
      
      $rparams = $rmethod->getParameters();
      $rparam = current($rparams);
      $prclass = $rparam->getClass();
      
      if($prclass === null){
    
        $ret_map = array();
    
      }else{
    
        $ret_map['method'] = true;
        $ret_map['static'] = $rmethod->isStatic();
        $ret_map['class_name'] = $prclass->getName();
        
      }//if
    
    }//if
    
    return $ret_map;
  
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
