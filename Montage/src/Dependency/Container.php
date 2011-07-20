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
use Montage\Field\Field;
use out;

class Container extends Field {

  /**
   *  the reflection class is kept outside the {@link $instance_map} because it
   *  is needed for lots of things   
   *
   *  @var  string  the passed in Reflection instances full class name   
   */
  protected $reflection = null;

  protected $instance_map = array();
  
  protected $preferred_map = array();
  
  /**
   *  holds the class keys with a callback that should be executed before the instance is created
   *
   *  @since  6-29-11   
   *  @var  array   
   */
  protected $on_create_map = array();
  
  /**
   *  holds the class keys with a callback that should be executed after the instance is created
   *
   *  @since  6-30-11 
   *  @var  array   
   */
  protected $on_created_map = array();

  /**
   *  create an instance of this class
   */
  public function __construct(Reflection $reflection){
  
    $this->reflection = $reflection;
    $this->setInstance($reflection);
    $this->setInstance($this); // we want to be able to inject this also
    
  }//method
  
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
  public function onCreate($class_name,$callback){
  
    // canary...
    if(!is_callable($callback)){
      throw new \InvalidArgumentException('$callback was not callable');
    }//if
  
    $class_key = $this->getKey($class_name);
  
    $this->on_create_map[$class_key] = $callback;
  
  }//method
  
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
  public function onCreated($class_name,$callback){
  
    // canary...
    if(!is_callable($callback)){
      throw new \InvalidArgumentException('$callback was not callable');
    }//if
  
    $class_key = $this->getKey($class_name);
  
    $this->on_created_map[$class_key] = $callback;
  
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
  
  public function getReflection(){ return $this->reflection; }//method
  
  public function hasInstance($class_name){
    return isset($this->instance_map[$this->getKey($class_name)]);
  }//method
  
  public function setInstance($instance){
  
    // canary...
    if(!is_object($instance)){ throw new \InvalidArgumentException('$instance was empty'); }//if
  
    $class_key = $this->getKey($instance);
    $this->instance_map[$class_key] = $instance;
  
  }//method
  
  /**
   *  when you know what class you specifically want, use this method over {@link findInstance()}
   *
   *  @param  string  $class_name the name of the class you are looking for
   *  @param  array $params any params you want to pass into the constructor of the instance      
   */
  public function getInstance($class_name,$params = array()){
  
    // canary...
    if(empty($class_name)){ throw new \InvalidArgumentException('$class_name was empty'); }//if
  
    $ret_instance = null;
    $params = (array)$params;
    $class_key = $this->getKey($class_name);
  
    if(isset($this->instance_map[$class_key])){
    
      $ret_instance = $this->instance_map[$class_key];
      
    }else{
    
      // handle on create...
      if(isset($this->on_create_map[$class_key])){
        $params = call_user_func($this->on_create_map[$class_key],$this,$params);
      }//if
    
      $ret_instance = $this->createInstance($class_name,$params);
      $this->setInstance($ret_instance);
      
      // handle on created...
      if(isset($this->on_created_map[$class_key])){
        call_user_func($this->on_created_map[$class_key],$this,$ret_instance);
      }//if
    
    }//if/else
  
    return $ret_instance;
  
  }//method
  
  /**
   *  find the absolute descendant of the class(es) you pass in
   *
   *  @param  string  $class_name the name of the class you are looking for
   *  @param  array $params any params you want to pass into the constructor of the instance      
   */
  public function findInstance($class_name,$params = array()){

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
      
        // handle on create...
        if(isset($this->on_create_map[$class_key])){
          $params = call_user_func($this->on_create_map[$class_key],$this,$params);
        }//if
      
        $ret_instance = $this->getInstance($instance_class_name,$params);
        
        // handle on created...
        if(isset($this->on_created_map[$class_key])){
          call_user_func($this->on_created_map[$class_key],$this,$ret_instance);
        }//if
        
        // also map to the passed in class key for quicker repeat lookups of the same child class...
        $this->instance_map[$class_key] = $ret_instance;
        
      }//if/else
      
    }//if
      
    return $ret_instance;
    
  }//method
  
  /**
   *  create and return an instance of $class_name with the given $construct_args
   *  
   *  @param  string  $class_name the name of the class to instantiate
   *  @param  array $construct_args similar to call_user_func_array, if the $class_name's
   *                                __construct() method takes 2 arguments (eg, __construct($one,$two)
   *                                then you would pass in array(1,2) and $one = 1, $two = 2               
   *  @return object
   */
  public function createInstance($class_name,$params = array()){
  
    // canary...
    if(empty($class_name)){
      throw new \InvalidArgumentException('empty $class_name');
    }//if
  
    $ret_instance = null;
    $instance_params = array();
    
    $params = (array)$params;

    // get around absolute namespace reflection bug
    // absolute namespaced classes like \foo\bar cause all the autoloaders to fire where
    // foo\bar doesn't. This is a bug in php...
    $rclass_name = $class_name;
    if($rclass_name[0] === '\\'){ $rclass_name = mb_substr($rclass_name,1); }//if
    $rclass = new ReflectionClass($rclass_name);
    
    // canary, make sure there is a __construct() method since we are passing in arguments...
    $rconstructor = $rclass->getConstructor();
    
    if(empty($rconstructor)){
      
      if(!empty($params)){
        
        throw new \UnexpectedValueException(
          sprintf(
            'Normalizing "%s" constructor params will fail because "%s" '
            .'has no __construct() method, so no constructor arguments can be used to instantiate it. '
            .'Please add %s::__construct(), or don\'t pass in any constructor arguments',
            $class_name,
            $class_name,
            $class_name
          )
        );
      
      }//method
      
    }else{
    
      $instance_params = $this->normalizeParams($rconstructor,$params);
    
    }//if/else
    
    if(empty($instance_params)){
    
      $ret_instance = new $class_name();
    
    }else{
    
      // http://www.php.net/manual/en/reflectionclass.newinstanceargs.php#95137
      $ret_instance = $rclass->newInstanceArgs($instance_params);
    
    }//if/else
    
    $ret_instance = $this->injectSetters($ret_instance,$rclass);
  
    return $ret_instance;
  
  }//method
  
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
  public function callMethod($instance,$method,array $params = array()){
  
    $rmethod = new ReflectionMethod($instance,$method);
    $method_params = $this->normalizeParams($rmethod,$params);
    return $rmethod->invokeArgs($instance,$method_params);
  
  }//method
  
  /**
   *  normalize one param of a method
   *  
   *  @since  7-5-11
   *  @param  ReflectionParameter $rparam
   *  @param  array $params see {@link normalizeParams()} for description of the $params array
   *  @return mixed the normalized param
   */
  public function normalizeParam(ReflectionParameter $rparam,array $params = array()){
  
    $ret_param = null;
    $index = $rparam->getPosition();
  
    // first try and resolve numeric keys, then do string keys...
    if(array_key_exists($index,$params)){
    
      $ret_param = $params[$index];
    
    }else{

      $field_name = $rparam->getName();
    
      if(array_key_exists($field_name,$params)){
        
        $ret_param = $params[$field_name];
      
      }else{
      
        $rclass = $rparam->getClass();
          
        if($rclass === null){
        
          if($this->existsField($field_name)){
          
            $ret_param = $this->getField($field_name);
            
          }else if($rparam->isDefaultValueAvailable()){
          
            $ret_param = $rparam->getDefaultValue();
          
          }else{
          
            throw new \UnexpectedValueException(
              sprintf(
                'no suitable value could be found for %s::%s() param "%s"',
                $rparam->getDeclaringClass()->getName(),
                $rparam->getName(),
                $field_name
              )
            );
          
          }//if/else if/else
        
        }else{
        
          $class_name = $rclass->getName();
          
          try{
          
            $ret_param = $this->findInstance($class_name);
            
          }catch(\Exception $e){
          
            if($rparam->isDefaultValueAvailable()){
            
              $ret_param = $rparam->getDefaultValue();
            
            }else{
            
              $ret_param = $this->getInstance($class_name);
            
              /* $reflection = $this->getReflection();
              $found_instance = false;
            
              foreach($this->instance_map as $class_key => $instance){
              
                if($this->isRelatedClass($class_name,$class_key)){
              
                  $ret_params[] = $instance;
                  $found_instance = true;
                  break;
                  
                }//if
              
              }//foreach */
            
              ///throw $e;
              
            }//if/else
          
          }//try/catch
        
        }//if/else
      
      }//if/else
      
    }//if/else
  
    return $ret_param;
  
  }//method
  
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
  public function normalizeParams(ReflectionMethod $rmethod,array $params = array()){
  
    // canary...
    if($rmethod->getNumberOfParameters() <= 0){ return $params; }//if
  
    // canary, params are numeric, so just pass those into the constructor untouched...
    ///if(ctype_digit((string)join('',array_keys($params)))){ return $params; }//if
    
    $ret_params = array();
    
    $rparams = $rmethod->getParameters();
    foreach($rparams as $rparam){

      $ret_params[] = $this->normalizeParam($rparam,$params);
      
    }//foreach
    
    return $ret_params;
  
  }//method
  
  /**
   *  get the key the instance will use for the instance map
   *
   *  @since  6-13-11
   *  @Param  string|object $class   
   *  @return string      
   */
  protected function getKey($class){
    
    $class_name = is_object($class)
      ? get_class($class)
      : $class;
  
    return $this->getReflection()->normalizeClassName($class_name);
    
  }//method

  /**
   *  inject dependencies via setter methods
   *  
   *  by default, this class will only inject if the method is of the form:
   *  setName(ClassName $class) and nothing else. And it will only inject the class
   *  if it has already created it (it won't be created on the fly like with constructor
   *  injection). This is because if you are using setter injection then it is most
   *  likely optional that you want the object instance, if you absolutely must have
   *  the instance then use constructor injection            
   *  
   *  @example:
   *    setFoo(Foo $foo);                  
   *
   *  @since  6-14-11   
   *  @param  object  $instance the object instance to be injected
   *  @param  ReflectionClass $rclass the reflection object of the given $instance      
   *  @return object  $instance with its setters injected
   */
  protected function injectSetters($instance,ReflectionClass $rclass = null){
  
    // canary...
    if(empty($instance)){ throw new \InvalidArgumentException('$instance was empty'); }//if
    if(empty($rclass)){
      $rclass = new ReflectionObject($instance);
    }//if
  
    $ret_count = 0;
    
    $rmethod_list = $rclass->getMethods(ReflectionMethod::IS_PUBLIC);
    foreach($rmethod_list as $rmethod){
    
      $method_name = $rmethod->getName();
    
      // only check the method if it is of the form: setNNNN()...
      if(mb_stripos($method_name,'set') === 0){
      
        if($rmethod->getNumberOfParameters() === 1){
        
          $rparams = $rmethod->getParameters();
          foreach($rparams as $rparam){
          
            $prclass = $rparam->getClass();
            if($prclass !== null){
          
              // @todo  this won't account for having a child instance, so might want to "find" the class
              $class_name = $prclass->getName();
              if($this->hasInstance($class_name)){
              
                $instance->{$method_name}($this->getInstance($class_name));
                $ret_count++;
              
              }//if
              
            }//if
        
          }//foreach
        
        }//if
      
      }//if
    
    }//foreach
  
    ///return $ret_count;
    return $instance;
  
  }//method

}//class
