<?php
/**
 *  handles deciding which controller::method to forward to
 *  
 *  this class should be renamed to something like Finder or Matcher, though
 *  Matcher::find() sounds strange, what about Resolve?  6-17-11 - I went with
 *  Select 
 *  
 *  @version 0.4
 *  @author Jay Marcyes
 *  @since 4-6-10
 *  @package montage
 ******************************************************************************/       
namespace Montage\Controller;

use Montage\Reflection\ReflectionFramework;

class Select {

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
   *  a list of all the namespaces to try
   *
   *  @see  addNamespace()   
   *  @var  array   
   */
  protected $class_namespace_list = array(
    'Controller',
    'Montage\Controller'
  );
  
  /**
   *  if no controller can be found using passed in params, use this controller name
   *
   *  @var  string   
   */        
  protected $class_default = 'IndexController';
  
  /**
   *  when an exception is encountered, use this class to handle it
   *
   *  @var  string   
   */
  protected $class_exception = 'ExceptionController';
  
  /**
   *  this is the interface a class has to implement to be considered a controller
   *  
   *  @var  string
   */
  protected $class_interface = 'Montage\Controller\Controllable';
  
  
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
   *  holds the information about what classes exist in the system
   *
   *  @var  \Montage\Reflection\ReflectionFramework
   */
  protected $reflection = null;
  
  /**
   *  create instance of this class
   *  
   *  @param  \Montage\Reflection\ReflectionFramework $reflection needed to be able to find a suitable controller class            
   */
  public function __construct(ReflectionFramework $reflection){
  
    $this->reflection = $reflection;
  
  }//method
  
  /**
   *  add a namespace to the list of usable controller namespaces
   *
   *  @deprecated I think this is no longer needed since {@link getClassName()} will now fallback
   *  to checking all controller classes looking for a match if the defined namespace list doesn't find
   *  a match      
   *      
   *  this is handy for plugins to add a controller that can be easily overridden by
   *  the application (which would be harder to do if the plugin had controller \Controller\Foo and
   *  the app wanted to change Foo a bit it couldn't easily extend \Controller\Foo since the 
   *  plugin already defined it).         
   *      
   *  @since  6-29-11
   *  @param  string  $namespace  the namespace to add   
   */        
  public function addNamespace($namespace){
    
    // canary...
    if(empty($namespace)){ throw new \InvalidArgumentException('$namespace was empty'); }//if
    
    // always keep montage as the last controller so plugins can override it...
    $montage_namespace = end($this->class_namespace_list);
    $key = key($this->class_namespace_list);
    
    $this->class_namespace_list[$key] = $namespace;
    $this->class_namespace_list[] = $montage_namespace;
    
    return $this->class_namespace_list;
  
  }//method
  
  /**
   *  get the full default class name
   *
   *  @since  6-21-11
   *  @return string  a full namespaced class name      
   */
  public function getDefaultClassName(){
  
    return $this->getClassName($this->class_default);
  
  }//method
  
  /**
   *  get the full exception class name
   *
   *  @since  6-21-11
   *  @return string  a full namespaced class name      
   */
  public function getExceptionClassName(){
  
    return $this->getClassName($this->class_exception);
  
  }//method
  
  /**
   *  returns a full class name if it is a child of {@link $class_interface}
   *  
   *  @since  6-20-11
   *  @param  string  $class_name a partial class name that will be turned into a full class name, this
   *                              value would be equivalent to {@link ReflectionClass::getShortName()} and
   *                              is the name of the class without the namespace         
   *  @return string
   */
  public function getClassName($class_name){
  
    // canary...
    if(empty($class_name)){
      throw new \InvalidArgumentException('$class_name was empty');
    }//if
  
    $ret_str = '';
    $reflection = $this->reflection;
    $tried_class_list = array();

    foreach($this->class_namespace_list as $class_namespace){
      
      $full_class_name = $this->normalizeClass($class_namespace,$class_name);
      
      if($reflection->isChildClass($full_class_name,$this->class_interface)){
      
        $rclass = new \ReflectionClass($full_class_name);
        if($rclass->isInstantiable()){
        
          $ret_str = $full_class_name;
          break;
          
        }//if
        
      }//if
      
      $tried_class_list[] = $full_class_name;
      
    }//foreach
  
    // fallback to any controller that matches the name...
    if(empty($ret_str)){
    
      $regex = sprintf('#%s$#i',preg_quote($this->normalizeClass('',$class_name)));
    
      $class_list = $reflection->findClassNames($this->class_interface,$tried_class_list);
      foreach($class_list as $full_class_name){
      
        if(preg_match($regex,$full_class_name)){
        
          $rclass = new \ReflectionClass($full_class_name);
          if($rclass->isInstantiable()){
          
            $ret_str = $full_class_name;
            break;
            
          }//if
        
        }//if
      
      }//foreach

    }//if
  
    return $ret_str;
  
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
  
    $path_list = array_values(array_filter(explode('/',$path))); // ignore empty values
    $class_name = '';
    $method_name = '';
    $method_params = array();
  
    // we check in this order:
    // 1 - \Controller\$path_list[0]
    // 2 - \Montage\Controller\$path_list[0]
    // 3 - \Controller\IndexController
    // 4 - \Montage\Controller\IndexController
    list($class_name,$path_list) = $this->findClass($path_list,$this->class_default);
  
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
  public function findException(\Exception $e){
    
    $e_name = get_class($e);
    $class_name = '';
    $method_name = '';
    
    // find the controller...
    try{
    
      list($class_name,$path_list) = $this->findClass(array(),$this->class_exception);
      
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
  
    // override params to just be the exception...
    $method_params = array($e);
  
    return array($class_name,$method_name,$method_params);
  
  }//method
  
  /**
   *  find the controller class
   *  
   *  @since  6-16-11   
   *  @param  array $path_list  the path broken up by /
   *  @param  string  $fallback_class_name  if the class can't be found through the $path_list, use
   *                                        this class
   *  @return array array($class_name,$path_list)
   */
  protected function findClass(array $path_list,$fallback_class_name = ''){

    $class_name = '';
    $path_bit = reset($path_list);

    // see if the controller was passed in from the request string...
    if(!empty($path_bit)){
      
      $class_name = $this->getClassName($path_bit);
  
    }//if
  
    // check for the default class name...
    if(empty($class_name)){
      
      $class_name = $this->getClassName($fallback_class_name);
      
    }else{
    
      $path_list = array_slice($path_list,1);
    
    }//if/else
  
    if(empty($class_name)){
      throw new \UnexpectedValueException(
        sprintf(
          'A suitable Controller class could not be found to handle the request [%s]',
          join('/',$path_list)
        )
      );
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
    $path_bit = reset($path_list);
    
    // check in order:
    // 1 - $class_name/$path_list[0]
    // 2 - $class_name/$this->method_default
  
    // find the method...
    if(!empty($path_bit)){
    
      $method_name = $this->normalizeMethod($path_bit);
      
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
  
    return array($method_name,array_values($method_params));
  
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
