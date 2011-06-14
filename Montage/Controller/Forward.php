<?php
/**
 *  handles deciding which controller::method to forward to
 *  
 *  this class should be renamed to something like Finder or Matcher, though
 *  Matcher::find() sounds strange   
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 4-6-10
 *  @package montage
 ******************************************************************************/       
namespace Montage\Controller;

use Montage\Dependency\Reflection;
use out;

class Forward {

  protected $class_postfix = 'Controller';
  
  protected $class_interface = 'Montage\Controller\Controllable';
  
  protected $class_namespace = 'Controller';
  
  protected $class_default_list = array(
    'Controller\IndexController',
    'Montage\Controller\IndexController'
  );
  
  /**
   *  this prefix will be used to decide what a vanilla method name coming in will be
   *  prefixed with when {@link getControllerMethodName()} is called
   */
  protected $method_prefix = 'handle';
  
  protected $method_default = 'handleIndex';
  
  /**
   *  in case of exception while handling a request, the class with this name will be called
   *  
   *  class must extend montage_controller      
   */
  protected $class_exception_list = array(
    'Controller\ExceptionController',
    'Montage\Controller\ExceptionController'
  );
  
  protected $reflection = null;
  
  /**
   *            
   */
  function __construct(Reflection $reflection){
  
    $this->reflection = $reflection;
  
  }//method

  public function findCLI($path,array $args = array()){
  
    $namespace = 'cli';
    
    list($namespace,$path) = $this->findNamespace('cli',$path,$args);
    out::e($path);
    
    $ret = $this->find($namespace,explode('/',$path));
    out::e($ret);
    
    out::e($path,$args);
  
  
  
  }//method
  
  public function find($host,$path,array $args = array()){
  
    $path_list = array_filter(explode('/',$path)); // ignore empty values
    $class_name = '';
    $method_name = '';
    $method_params = array();
    $reflection = $this->reflection;
  
    // we check in order:
    // 1 - \Controller\$path_list[0]
    // 2 - \Controller\IndexController
    // 3 - \Montage\Controller\IndexController
  
    // see if the controller was passed in from the request string...
    if(!empty($path_list[0])){
    
      $class_name = $this->normalizeClass($this->class_namespace,$path_list[0]);
    
      if($reflection->hasClass($class_name,$this->class_interface)){
      
        $path_list = array_slice($path_list,1);
      
      }else{
      
        $class_name = '';
      
      }//if
    
    }//if
  
    if(empty($class_name)){
    
      ///out::i($reflection);
    
      foreach($this->class_default_list as $class_name){
      
        if($reflection->isChildClass($class_name,$this->class_interface)){
          break;
        }else{
          $class_name = '';
        }//if/else
      
      }//foreach
      
      if(empty($class_name)){
        throw new \UnexpectedValueException(
          sprintf(
            'A suitable Controller class could not be found to handle the request host: %s, path: %s',
            $host,
            $path
          )
        );
      }//if
    
    }//if
  
    // check in order:
    // 1 - $class_name/$path_list[0]
    // 2 - $class_name/$this->method_default
  
    // find the method...
    if(!empty($path_list[0])){
    
      $method_name = $this->normalizeMethod($path_list[0]);
        
      // if the controller method does not exist then use the default...
      if(method_exists($class_name,$method_name)){ // confirmed controller/method
      
        $method_params = array_slice($path_list,1);
        
      }else{
        $method_name = '';
      }//if/else
    
    }//if
    
    if(empty($method_name)){
    
      // check for controller/$arg using the default method...
      $method_name = $this->method_default;
      if(method_exists($class_name,$method_name)){
      
        $method_params = $path_list;
      
      }else{
      
        throw new \UnexpectedValueException(
          sprintf(
            'Could not find a suitable method in %s to handle the request',
            $class_name
          )
        );
      
      }//if/else
    
    }//if

    return array($class_name,$method_name,$method_params);
  
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
  public function findException(Exception $e){
    
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
  
  /**
   *  gets the "usable" controller class name
   *  
   *  basically, if you pass in something like "foo" then this will return "FooController"
   *  which is the non-resolved classname before the namespace is added    
   *      
   *  @param  string  $namespace  the namespace you want to prepend to the $class_name   
   *  @param  string  $class_name  the potential controller class name
   *  @return string
   */
  protected function normalizeClass($namespace,$class_name){
  
    // canary...
    if(empty($class_name)){
      throw new \InvalidArgumentException('$class_name was empty');
    }//if
    if(mb_stripos($class_name,$this->class_postfix) === false){
      $class_name = sprintf('%s%s',ucfirst($class_name),$this->class_postfix);
    }//if
  
    return sprintf('%s\%s',$namespace,$class_name);
  
  }//method
  
  /**
   *  get the controller name that should be used
   *  
   *  @param  string  $method_name  can be the full method name (eg, hanldeFoo) or a partial 
   *                                that will be made into the full name (eg, foo gets turned into handleFoo)      
   *  @return string
   */
  protected function normalizeMethod($method_name){
  
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
  
}//class
