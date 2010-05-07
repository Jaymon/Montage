<?php

/**
 *  handles deciding which controller::method to forward to
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 4-6-10
 *  @package montage
 ******************************************************************************/       
class montage_forward {

  /**
   *  hold the default controller class that will be used if another class isn't found
   *  
   *  class must extend montage_controller      
   */
  const CONTROLLER_CLASS_NAME = 'index';
  
  /**
   *  in case of exception while handling a request, the class with this name will be called
   *  
   *  class must extend montage_controller      
   */
  const CONTROLLER_ERROR_CLASS_NAME = 'error';
  
  /**
   *  hold the default controller method that will be called if no other method is found
   */
  const CONTROLLER_METHOD = 'handleIndex';

  /**
   *  this prefix will be used to decide what a vanilla method name coming in will be
   *  prefixed with when {@link getControllerMethodName()} is called
   */
  const CONTROLLER_METHOD_PREFIX = 'handle';

  /**
   *  set main contructor that can't be over-written            
   */
  final function __construct(){
  
    // for inheritence, let child classes do any init they need...
    $this->start();
  
  }//method

  /**
   *  placeholder in case a user extended class is used and needs to do init stuff
   */
  protected function start(){}//method
  
  /**
   *  find a controller::method from the path parts in $path_list
   *  
   *  @param  array $path_list  the path list (eg, a string path split with DIRECTORY_SEPARATOR)
   *  @return array array($controller_class_name,$controller_method,$controller_method_args)
   */
  public function find($path_list){
  
    $controller_class_name = self::CONTROLLER_CLASS_NAME;
    $controller_method = self::CONTROLLER_METHOD;
    $controller_method_args = array();
  
    // find the controller, method, and method arguments...
    $controller_method_args = $path_list;
    if(!empty($path_list[0])){
      
      $maybe_controller_class_name = montage_core::getClassName($path_list[0]);
      
      if(montage_core::isController($maybe_controller_class_name)){
        
        $controller_class_name = $maybe_controller_class_name;
        
        if(!empty($path_list[1])){
        
          $maybe_controller_method = $this->getControllerMethodName($path_list[1]);
        
          // if the controller method does not exist then use the default...
          if(method_exists($controller_class_name,$maybe_controller_method)){
          
            $controller_method = $maybe_controller_method;
            $controller_method_args = array_slice($path_list,2);
            
          }else{
          
            $controller_method_args = array_slice($path_list,1);
            
          }//if/else
          
        }else{
          $controller_method_args = array_slice($path_list,1);
        }//if/else
        
      }//if/else
      
    }//if
  
    return array($controller_class_name,$controller_method,$controller_method_args);
  
  }//method
  
  /**
   *  assure the controller class name and method are valid and callable
   *  
   *  @param  string  $controller_class_name
   *  @param  string  $controller_method
   *  @return array array($controller_class_name,$controller_method)        
   */
  public function get($controller_class_name,$controller_method){
  
    $controller_method = $this->getControllerMethodName($controller_method);
  
    // canary...
    if(empty($controller_class_name)){
      throw new UnexpectedValueException('$controller_class_name cannot be empty');
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
      
    $controller_class_name = montage_core::getClassName(self::CONTROLLER_ERROR_CLASS_NAME);
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
          'No error controller %s found so the exception %s could not be resolved. '
          .' To remedy this, create an error class that extends montage_controller (eg, "class %s '
          .' extends montage_controller { ... }"). '
          .' exception information: %s',
          self::CONTROLLER_ERROR_CLASS_NAME,
          $e_name,
          $e->getCode(),
          $e->getMessage(),
          self::CONTROLLER_ERROR_CLASS_NAME,
          $e ///$e->getTraceAsString()
        )
      );
      
    }//if/else
  
    return array($controller_class_name,$controller_method,$controller_method_args);
  
  }//method
  
  /**
   *  get the controller name that should be used
   *  
   *  @param  string  $method_name  can be the full method name (eg, hanldeFoo) or a partial 
   *                                that will be made into the full name (eg, foo gets turned into handleFoo)      
   *  @return string
   */
  final public function getControllerMethodName($method_name){
  
    // canary...
    if(empty($method_name)){
      throw new UnexpectedValueException('$method_name cannot be empty');
    }//if
    
    // see if the method name already has the prefix...
    if(mb_stripos($method_name,self::CONTROLLER_METHOD_PREFIX) !== 0){
      $method_name = sprintf(
        '%s%s',
        self::CONTROLLER_METHOD_PREFIX,
        ucfirst(mb_strtolower($method_name))
      );
    }//if/else
  
    return $method_name;
  
  }//method
  
}//class
