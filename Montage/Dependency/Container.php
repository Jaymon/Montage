<?php
/**
 *  the Dependancy Injection Container is like a service locator  
 * 
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-1-11
 *  @package montage
 ******************************************************************************/
namespace Montage\Dependency;

use ReflectionClass, ReflectionMethod;
use Montage\Field;
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

  /**
   *  final so as not to interfere with the creation of this class automatically
   */
  final public function __construct(Reflection $reflection){
  
    $this->reflection = $reflection;
    $this->setInstance($reflection);
    
  }//method
  
  public function getReflection(){ return $this->reflection; }//method
  
  public function hasInstance($class_name){
    return isset($this->instance_map[$this->getKey($class_name)]);
  }//method
  
  public function setInstance($instance){
  
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
    
      $ret_instance = $this->getNewInstance($class_name,$params);
      $this->setInstance($ret_instance);
    
    }//if/else
  
    return $ret_instance;
  
  }//method
  
  /**
   *  find the absolute descendant of the class(es) you pass in
   *
   *  @param  string|array  $class_name the name(s) of the class(es) you are looking for
   *  @param  array $params any params you want to pass into the constructor of the instance      
   */
  public function findInstance($class_name,$params = array()){

    $ret_instance = null;
    $class_name = (array)$class_name;
    $params = (array)$params;
    $reflection = $this->getReflection();
    $instance_class_name = '';
    
    foreach($class_name as $cn){
    
      try{
    
        $instance_class_name = $reflection->findClassName($cn);
        break;
        
      }catch(Exception $e){}//try/catch
      
    }//foreach
  
    if(empty($instance_class_name)){
    
      throw new \UnexpectedValueException(
        sprintf('Unable to find suitable class using [%s]',join(',',$class_name))
      );
    
    }else{
      
      $class_key = $this->getKey($instance_class_name);
  
      if(isset($this->instance_map[$class_key])){
      
        $ret_instance = $this->instance_map[$class_key];
        
      }else{
    
        $ret_instance = $this->getNewInstance($instance_class_name,$params);
        $this->setInstance($ret_instance);
      
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
   *                                then you would pass in array(1,2) and $one = 1, $two = 2               
   *  @return object
   */
  public function getNewInstance($class_name,$params = array()){
  
    // canary...
    if(empty($class_name)){
      throw new InvalidArgumentException('empty $class_name');
    }//if
  
    $ret_instance = null;
    $instance_params = array();
    
    $params = (array)$params;
    $rclass = new ReflectionClass($class_name);
    
    // canary, make sure there is a __construct() method since we are passing in arguments...
    $rconstructor = $rclass->getConstructor();
    if(empty($rconstructor)){
      
      if(!empty($params)){
        
        throw new UnexpectedValueException(
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
  
    return $ret_instance;
  
  }//method
  
  public function normalizeParams(ReflectionMethod $rmethod,array $params){
  
    // canary...
    if($rmethod->getNumberOfParameters() <= 0){ return $params; }//if
  
    // canary, params are numeric, so just pass those into the constructor untouched...
    ///if(ctype_digit((string)join('',array_keys($params)))){ return $params; }//if
    
    $ret_params = array();
    $rparams = $rmethod->getParameters();
    
    foreach($rparams as $index => $rparam){

      // first try and resolve numeric keys, then do string keys...
      if(array_key_exists($index,$params)){
      
        $ret_params[] = $params[$index];
      
      }else{
  
        $field_name = $rparam->getName();
      
        if(array_key_exists($field_name,$params)){
          
          $ret_params[] = $params[$field_name];
        
        }else{
        
          $rclass = $rparam->getClass();
          if($rclass === null){
          
            if($this->existsField($field_name)){
            
              $ret_params[] = $this->getField($field_name);
              
            }else if($rparam->isDefaultValueAvailable()){
            
              $ret_params[] = $rparam->getDefaultValue();
            
            }else{
            
              throw new UnexpectedValueException(
                sprintf(
                  'no suitable value could be found for %s\'s __construct() param "%s"',
                  $rclass->getName(),
                  $field_name
                )
              );
            
            }//if/else if/else
          
          }else{
          
            $class_name = $rclass->getName();
            
            try{
            
              $ret_params[] = $this->findInstance($class_name);
              
            }catch(Exception $e){
            
              if($rparam->isDefaultValueAvailable()){
              
                $ret_params[] = $rparam->getDefaultValue();
              
              }else{
                throw $e;
              }//if/else
            
            }//try/catch
          
          }//if/else
        
        }//if/else
        
      }//if/else
      
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

}//class
