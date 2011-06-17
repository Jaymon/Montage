<?php
/**
 *  handles deciding which controller::method to forward to
 *  
 *  this class should be renamed to something like Finder or Matcher, though
 *  Matcher::find() sounds strange, what about Resolve? 
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

  /**
   *  this is appended to the class name
   *  
   *  so, class name "foo" would become "FooController"
   *  
   *  @see  normalizeClass
   *  @var  string
   */
  protected $class_postfix = 'Controller';
  
  /**
   *  this is the interface a class has to implement to be considered a controller
   *  
   *  @var  string
   */
  protected $class_interface = '\Montage\Controller\Controllable';
  
  /**
   *  this is the namespace that will be used for the class
   *  
   *  so, if you had class "foo" then it would be use namespace \Controller
   *  and its full name would be: \Controller\FooController         
   *
   *  @var  string   
   */
  protected $class_namespace = '\Controller';
  
  /**
   *  if a suitable class can't be autodiscovered then fallback to a class in this FIFO
   *  queue list
   *  
   *  @var  array a FIFO queue of default classes
   */
  protected $class_default_list = array(
    '\Controller\IndexController',
    '\Montage\Controller\IndexController'
  );
  
  /**
   *  this prefix will be used to decide what a vanilla method name coming in will be
   *  prefixed with
   *  
   *  so, if you have method "bar" it would become: "handleBar"
   *  
   *  @see  normalizeMethod()   
   *  @var  string      
   */
  protected $method_prefix = 'handle';
  
  /**
   *  if no method can be found then fallback to this method
   *
   *  @var  string   
   */
  protected $method_default = 'handleIndex';
  
  /**
   *  in case of exception while handling a request, the first class that exists in
   *  this list will be used   
   *  
   *  @var  array FIFO queue of exception handling classes      
   */
  protected $class_exception_list = array(
    'Controller\ExceptionController',
    'Montage\Controller\ExceptionController'
  );
  
  /**
   *  holds the information about what classes exist in the system
   *
   *  @var  Reflection   
   */
  protected $reflection = null;
  
  /**
   *  create instance of this class
   *  
   *  @param  Reflection  $reflection needed to be able to find a suitable controller class            
   */
  function __construct(Reflection $reflection){
  
    $this->reflection = $reflection;
  
  }//method
  
  /**
   *  turns the info provided by the $host, $path and $params into a controller::method
   *  
   *  @param  string  $host the host that is making the request
   *  @param  string  $path the path of the request
   *  @param  array $params currently not used
   *  @return array array($controller,$method,$method_params)
   */
  public function find($host,$path,array $params = array()){
  
    $path_list = array_filter(explode('/',$path)); // ignore empty values
    $class_name = '';
    $method_name = '';
    $method_params = array();
  
    // we check in order:
    // 1 - \Controller\$path_list[0]
    // 2 - \Controller\IndexController
    // 3 - \Montage\Controller\IndexController
    list($class_name,$path_list) = $this->findClass($path_list,$this->class_default_list);
  
    // check in order:
    // 1 - $class_name/$path_list[0]
    // 2 - $class_name/$this->method_default
    list($method_name,$method_params) = $this->findMethod($class_name,$path_list);

    return array($class_name,$method_name,$method_params);
  
  }//method
  
  /**
   *  get the controller::method that should be used for the given exception $e
   *  
   *  @param  Exception $e
   *  @return array array($controller,$method,$method_params)        
   */
  public function findException(Exception $e){
    
    $e_name = get_class($e);
    $class_name = '';
    $method_name = '';
    
    // find the controller...
    try{
    
      list($class_name,$path_list) = $this->findClass(array(),$this->class_exception_list);
      
    }catch(Exception $e){
    
      throw new \UnexpectedValueException(
        sprintf(
          'A suitable Exception Controller class could not be found to handle the exception: %s',
          $e
        ),
        $e->getCode(),
        $e
      );
    
    }//try/catch
    
    // check in order:
    // 1 - $class_name/$e_name
    // 2 - $class_name/$this->method_default
    list($method_name,$method_params) = $this->findMethod($class_name,array($e_name));
  
    return array($class_name,$method_name,$method_params);
  
  }//method
  
  /**
   *  find the controller class
   *  
   *  @since  6-16-11   
   *  @param  array $path_list  the path broken up by /
   *  @param  array $default_class_list if the class can't be found through the $path_list, use
   *                the classes found in this list      
   *  @return array array($class_name,$path_list)
   */
  protected function findClass(array $path_list,array $default_class_list = array()){
  
    $class_name = '';
    $reflection = $this->reflection;
  
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
    
      foreach($default_class_list as $class_name){
      
        if($reflection->isChildClass($class_name,$this->class_interface)){
          break;
        }else{
          $class_name = '';
        }//if/else
      
      }//foreach
      
      if(empty($class_name)){
        throw new \UnexpectedValueException(
          sprintf(
            'A suitable Controller class could not be found to handle the request [%s]',
            join('/',$path_list)
          )
        );
      }//if
    
    }//if
  
    return array($class_name,$path_list);
  
  }//method
  
  /**
   *  find the matching method for $class_name using $path_list
   *  
   *  @since  6-16-11
   *  @param  string  $class_name the controller class
   *  @param  array $path_list
   *  @return array($method,$method_params)
   */
  protected function findMethod($class_name,array $path_list){
  
    $method_name = '';
    $method_params = array();
  
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
  
    return array($method_name,$method_params);
  
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
