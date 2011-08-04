<?php
/**
 *  the Dependancy Injection Container is like a service locator. 
 *  
 *  This is the base container for any custom containers. Though this could be entirely
 *  side-stepped in favor of a completely custom container that implements Containable 
 *     
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 7-22-11
 *  @package montage
 ******************************************************************************/
namespace Montage\Dependency;

use Montage\Dependency\Containable;
use ReflectionObject, ReflectionClass, ReflectionMethod, ReflectionParameter;
use Montage\Field\Field;

abstract class Container extends Field implements Containable {

  protected $instance_map = array();
  
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
    
    if(isset($this->instance_map[$class_key])){
      \out::e($this->instance_map);
    }//if
    
    /*   trigger_error(
        sprintf(
          'An object of %s has already been created, making this %s mostly useless',
          $class_name,
          __FUNCTION__
        ),
        E_USER_WARNING
      );
    }//if */
  
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
    
    /* if(isset($this->instance_map[$class_key])){
      trigger_error(
        sprintf(
          'An object of %s has already been created, making this %s mostly useless',
          $class_name,
          __FUNCTION__
        ),
        E_USER_WARNING
      );
    }//if */
  
    $this->on_created_map[$class_key] = $callback;
  
  }//method
  
  /**
   *  true if there is an existing instance with the $class_name
   *  
   *  @param  string  $class_name
   *  @return boolean
   */
  public function hasInstance($class_name){
    return isset($this->instance_map[$this->getKey($class_name)]);
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
  
    $class_key = $this->getKey($class_name);
    $this->instance_map[$class_key] = $instance;
  
  }//method
  
  /**
   *  find the absolute descendant of the class(es) you pass in
   *
   *  @param  string  $class_name the name of the class you are looking for
   *  @param  array $params any params you want to pass into the constructor of the instance      
   */
  public function getInstance($class_name,$params = array()){
  
    $class_key = $this->getKey($class_name);
    $ret_instance = null;
  
    // check to see if there is already an instance before creating one...
    if(isset($this->instance_map[$class_key])){
    
      $ret_instance = $this->instance_map[$class_key];
    
    }else{
    
      $instance_class_name = $this->getClassName($class_name);
      $instance_class_key = $this->getKey($instance_class_name);
      
      // I'm not sold on this being here, I'm thinking this might be better being just implemented
      // in a child class since this seems really ad-hoc... 
      if(isset($this->instance_map[$instance_class_key])){
    
        $ret_instance = $this->instance_map[$instance_class_key];
      
      }else{
    
        // $class_name is passed so createInstance will check the right on create and on created
        // events...
        $ret_instance = $this->createInstance($class_name,$params);
        $this->setInstance($class_name,$ret_instance);
        
      }//if/else
      
    }//if/else
    
    return $ret_instance;
  
  }//method
  
  /**
   *  create and return an instance of $class_name with the given $construct_args
   *  
   *  @param  string  $class_name the name of the class to instantiate
   *  @param  array $construct_args similar to call_user_func_array, if the $class_name's
   *                                __construct() method takes 2 arguments (eg, __construct($one,$two)
   *                                then you would pass in array(1,2) and $one would equal 1, and
   *                                $two would equal 2, likewise you could pass in array('one' => 1,'two' => 2)               
   *  @return object
   */
  public function createInstance($class_name,$params = array()){
  
    // canary...
    if(empty($class_name)){ throw new \InvalidArgumentException('empty $class_name'); }//if
  
    $class_key = $this->getKey($class_name);
  
    // handle on create...
    if(isset($this->on_create_map[$class_key])){
    
      $params = call_user_func($this->on_create_map[$class_key],$this,$params);
      
      if(!is_array($params)){
        throw new \UnexpectedValueException(
          sprintf('An array should have been returned from on create callback for %s',$class_key)
        );
      }//if
      
    }//if
  
    $instance_class_name = $this->getClassName($class_name);
    $params = (array)$params;
    $instance_params = array();

    // get around absolute namespace reflection bug
    // absolute namespaced classes like \foo\bar cause all the autoloaders to fire where
    // foo\bar doesn't. This is a bug in php...
    $rclass_name = $instance_class_name;
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
    
    // actually create the class
    // http://www.php.net/manual/en/reflectionclass.newinstanceargs.php#95137
    $ret_instance = empty($instance_params) ? new $class_name() : $rclass->newInstanceArgs($instance_params);
    $ret_instance = $this->injectSetters($ret_instance,$rclass);
    
    // handle on created...
    if(isset($this->on_created_map[$class_key])){
      call_user_func($this->on_created_map[$class_key],$this,$ret_instance);
    }//if
  
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
  public function normalizeParam(\ReflectionParameter $rparam,array $params = array()){
  
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
      
        try{
      
          $rclass = $rparam->getClass();
          
        }catch(\ReflectionException $e){
        
          throw new \ReflectionException(
            sprintf(
              '%s which is param %s of method %s::%s()',
              $e->getMessage(),
              ($index + 1),
              $rparam->getDeclaringClass()->getName(),
              $rparam->getDeclaringFunction()->getName()
            )
          );
        
        }//try/catch
          
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
                $rparam->getDeclaringFunction()->getName(),
                $field_name
              )
            );
          
          }//if/else if/else
        
        }else{
        
          $class_name = $rclass->getName();
          
          try{
          
            $ret_param = $this->getInstance($class_name);
            
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
   *    $this->normalizeParams($rmethod,array('che','cha')) // retuns array('che','cha',automatically created SomeClass Instance)
   *    $this->normalizeParams($rmethod,array('che')) // retuns array('che','',automatically created SomeClass Instance)
   *    $this->normalizeParams($rmethod,array('che' => new SomeClass(),'bar' => '')) // retuns array('','',passed in SomeClass Instance)       
   *        
   *  @param  ReflectionFunctionAbstract  $rfunc  the reflection of the method/function
   *  @param  array $params any params you want to pass to override any magically
   *                        discovered params
   *  @return array the params ready to be passed to the method using something like call_user_func_array
   */
  public function normalizeParams(\ReflectionFunctionAbstract $rfunc,array $params = array()){
  
    // canary...
    if($rfunc->getNumberOfParameters() <= 0){ return $params; }//if
  
    $ret_params = array();
    
    $rparams = $rfunc->getParameters();
    foreach($rparams as $rparam){

      $ret_params[] = $this->normalizeParam($rparam,$params);
      
    }//foreach
    
    return $ret_params;
  
  }//method
  
  /**
   *  find the class name that will be used to create the instance
   *
   *  @since  8-3-11   
   *  @param  string  $class_name the name of the class you are looking for
   *  @return string  the class name that will be used to create the instance      
   */
  abstract protected function getClassName($class_name);
  
  /**
   *  get the key the instance will use for the instance map
   *
   *  @since  6-13-11
   *  @Param  string  $class_name
   *  @return string    
   */
  protected function getKey($class_name){ return mb_strtoupper($class_name); }//method

  /**
   *  inject dependencies via setter methods
   *  
   *  by default, this class will only inject if the method is of the form:
   *  setName(ClassName $class) and nothing else. And it will only inject the class
   *  if it can be created. This is because if you are using setter injection then it is most
   *  likely optional that you want the object instance, if you absolutely must have
   *  the instance then use constructor injection (which will halt execution if the instance
   *  can't be created)       
   *  
   *  @example:
   *    setFoo(Foo $foo);                  
   *
   *  @since  6-14-11   
   *  @param  object  $instance the object instance to be injected
   *  @param  ReflectionClass $rclass the reflection object of the given $instance      
   *  @return object  $instance with its setters injected
   */
  protected function injectSetters($instance,\ReflectionClass $rclass = null){
  
    // canary...
    if(empty($instance)){ throw new \InvalidArgumentException('$instance was empty'); }//if
    if(empty($rclass)){
      $rclass = new ReflectionObject($instance);
    }//if
  
    $ret_count = 0;
    
    $rmethod_list = $rclass->getMethods(ReflectionMethod::IS_PUBLIC);
    foreach($rmethod_list as $rmethod){
    
      $method_name = $rmethod->getName();
    
      $is_setter = (($method_name[0] === 's') || ($method_name[0] === 'S'))
        && (($method_name[1] === 'e') || ($method_name[1] === 'E'))
        && (($method_name[2] === 't') || ($method_name[2] === 'T'));
    
      // only check the method if it is of the form: setNNNN()...
      if($is_setter){
      
        // the valid setter syntax is: setName(ClassName $var_name), only methods matching that are set...
        if($rmethod->getNumberOfParameters() === 1){
        
          $rparams = $rmethod->getParameters();
          foreach($rparams as $rparam){
          
            try{
            
              $prclass = $rparam->getClass();
              if($prclass !== null){
            
                $class_name = $prclass->getName();
                $instance->{$method_name}($this->getInstance($class_name));
                $ret_count++;
                
              }//if
              
            }catch(\Exception $e){
              // exceptions aren't fatal, just don't set the dependency
            }//try/catch
        
          }//foreach
        
        }//if
      
      }//if
    
    }//foreach
  
    ///return $ret_count;
    return $instance;
  
  }//method

}//class
