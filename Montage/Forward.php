<?php

/**
 *  handles deciding which controller::method to forward to
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 4-6-10
 *  @package montage
 ******************************************************************************/       
namespace Montage;

use Montage\Dependency\Reflection;
use out;

class Forward {

  /**
   *  hold the default controller class that will be used if another class isn't found
   *  
   *  class must extend montage_controller      
   */
  protected $default_class = 'Index';
  
  protected $class_postfix = 'Controller';
  
  protected $class_interface = 'Montage\Controller\Controllable';
  
  /**
   *  in case of exception while handling a request, the class with this name will be called
   *  
   *  class must extend montage_controller      
   */
  protected $exception_class = 'Exception';
  
  /**
   *  this prefix will be used to decide what a vanilla method name coming in will be
   *  prefixed with when {@link getControllerMethodName()} is called
   */
  protected $method_prefix = 'Handle';
  
  /**
   *  hold the default controller method that will be called if no other method is found
   */
  protected $default_method = 'Index';
  
  protected $reflection = null;
  
  /**
   *            
   */
  function __construct(Reflection $reflection){
  
    $this->reflection = $reflection;
  
  }//method

  public function findCLI($path,array $args = array()){
  
    $namespace = 'cli';
    
    out::e($path);
    
    // change namespace if one was found...
    $path_bits = explode('.',$path,2);
    if(isset($path_bits[1])){
      $namespace = $path_bits[0];
      $path = $path_bits[1];
    }//if
    
    $ret = $this->find($namespace,explode('/',$path));
    out::e($ret);
    
    out::e($path,$args);
  
  
  
  }//method
  
  public function findHTTP($host,$path,array $args = array()){
  
    $namespace = 'www';
  
  }//method

  /**
   *  find a controller::method from the path parts in $path_list
   *  
   *  @param  array $path_list  the path list (eg, a string path split with DIRECTORY_SEPARATOR)
   *  @return array array($controller_class_name,$controller_method,$controller_method_args)
   */
  protected function find($namespace,array $path_list){
  
    $namespace_list = $this->getNamespaces($namespace);
    $class_name = $this->getClassName($this->default_class);
    $method_name = $this->getMethodName($this->default_method);
    $method_args = array();
    $reflection = $this->reflection;
  
    // find the controller, method, and method arguments...
    $method_args = $path_list;
    if(!empty($path_list[0])){ // we have atlead controller/
      
      $maybe_class_name = $this->findClassName($namespace_list,$path_list[0]);
      out::e($maybe_class_name);
      
      if($reflection->isChild($maybe_class_name,$this->class_interface)){ // confirmed controller/
      
        $class_name = $maybe_class_name;
        
        if(!empty($path_list[1])){ // we might have controller/method/
        
          $maybe_method = $this->getMethodName($path_list[1]);
        
          // if the controller method does not exist then use the default...
          if(method_exists($class_name,$maybe_method)){ // confirmed controller/method
          
            $method_name = $maybe_method;
            $method_args = array_slice($path_list,2);
            
          }else{ // we have controller/$arg instead
          
            $method_args = array_slice($path_list,1);
            
          }//if/else
          
        }else{ // we just had controller/ no arguments
        
          $method_args = array_slice($path_list,1);
          
        }//if/else
        
      }else{ // do we have method/ instead of controller/ ?
      
        $maybe_method = $this->getMethodName($path_list[0]);
        if(method_exists($class_name,$maybe_method)){ // confirmed method/
        
          $method_name = $maybe_method;
          $method_args = array_slice($path_list,1);
          
        }//if
         
      }//if/else
      
    }//if
  
    return array($class_name,$method_name,$method_args);
  
  }//method
  
  /**
   *  assure the controller class name and method are valid and callable
   *  
   *  @param  string  $controller_class_name
   *  @param  string  $controller_method
   *  @return array array($controller_class_name,$controller_method)        
   */
  public function get($controller_class_name,$controller_method){
  
    $controller_class_name = $this->getControllerClassName($controller_class_name);
    $controller_method = $this->getControllerMethodName($controller_method);
  
    // canary...
    if(empty($controller_class_name)){
      throw new UnexpectedValueException('a valid $controller_class_name was not found');
    }//if
    if(empty($controller_method)){
      throw new UnexpectedValueException('$controller_method cannot be empty');
    }//if
    if(!montage_core::isController($controller_class_name)){
      throw new DomainException(
        '$controller_class_name does not extend montage_controller or can\'t be declared (eg, is abstract).'
      );
    }//if
    if(!method_exists($controller_class_name,$controller_method)){
      throw new BadMethodCallException(
        sprintf(
          '%s::%s does not exist',
          $controller_class_name,
          $controller_method
        )
      );
    }//if
  
    return array($controller_class_name,$controller_method);
  
  }//method
  
  /**
   *  get the controller::method that should be used for the given exception $e
   *  
   *  @param  Exception $e
   *  @return array array($controller_class_name,$controller_method,$controller_method_args)        
   */
  public function getError(Exception $e){
    
    $e_name = get_class($e);
      
    $controller_class_name = $this->getControllerClassName(self::CONTROLLER_ERROR_CLASS_NAME);
    $controller_method = self::CONTROLLER_METHOD;
    $controller_method_args = array($e);
    
    if(montage_core::isController($controller_class_name)){
    
      $maybe_controller_method = $this->getControllerMethodName($e_name);
      
      if(method_exists($controller_class_name,$maybe_controller_method)){
        $controller_method = $controller_method;
      }//if
      
    }else{
        
      throw new RuntimeException(
        sprintf(
          'No error controller "%s" found so the exception "%s" could not be resolved. '
          .'To remedy this, create an error class that extends montage_controller. '
          .'exception information: %s',
          self::CONTROLLER_ERROR_CLASS_NAME,
          $e_name,
          $e ///$e->getTraceAsString()
        )
      );
      
    }//if/else
  
    return array($controller_class_name,$controller_method,$controller_method_args);
  
  }//method
  
  protected function findClassName(array $namespace_list,$class_name){
  
    $class_name = $this->getClassName($class_name);
    
    foreach($namespace_list as $namespace){
    
      $ret_class_name = sprintf('%s\%s',$namespace,$class_name);
    
      out::e($ret_class_name);
    
      if($this->reflection->hasClass($ret_class_name)){
        if($this->reflection->isChild($ret_class_name,$this->class_interface)){
          break;
        }//if
      }//if
    
      $ret_class_name = '';
    
    }//foreach
    
    return $ret_class_name;
  
  }//method
  
  /**
   *  gets the "usable" controller class name, this is not the full namespaced class name
   *  
   *  @param  string  $class_name  the potential controller class name
   *  @return string
   */
  protected function getClassName($class_name){
  
    // canary...
    if(empty($class_name)){
      throw new \InvalidArgumentException('$class_name was empty');
    }//if
    if(mb_stripos($class_name,$this->class_postfix) > 0){ return $class_name; }//if
  
    return sprintf('%s%s',$class_name,$this->class_postfix);
  
  }//method
  
  /**
   *  get the controller name that should be used
   *  
   *  @param  string  $method_name  can be the full method name (eg, hanldeFoo) or a partial 
   *                                that will be made into the full name (eg, foo gets turned into handleFoo)      
   *  @return string
   */
  protected function getMethodName($method_name){
  
    // canary...
    if(empty($method_name)){
      throw new \InvalidArgumentException('$method_name was empty');
    }//if
    if(mb_stripos($method_name,$this->method_prefix) > 0){ return $method_name; }//if
    
    $method_name = sprintf(
      '%s%s',
      $this->method_prefix,
      ucfirst(mb_strtolower($method_name))
    );
  
    return $method_name;
  
  }//method
  
  protected function getNamespaces($namespace = ''){
  
    $ret_list = array();
  
    $base_namespace = 'Montage\Controller';
    if(!empty($namespace)){
      $ret_list[] = sprintf('%s\%s',$base_namespace,$namespace);
    }//if
    
    $ret_list[] = $base_namespace;
    $ret_list[] = 'Montage\Controller';
  
    return $ret_list;
  
  }//method
  
}//class
