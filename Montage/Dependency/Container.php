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

use ReflectionClass;
use Montage\Field;
use out;

class Container extends Field {

  /**
   *  the reflection class is kept outside the {@link $instance_map} because it
   *  is needed for lost of things   
   *
   *  @var  Reflection   
   */
  protected $reflection = null;

  protected $instance_map = array();

  /**
   *  final so as not to interfere with the creation of this class automatically
   */
  final public function __construct(Reflection $reflection){
  
    $this->reflection = $reflection;
    // put it in the instance map also so it is easy for classes to grab it as a dependency...
    $this->instance_map[get_class($this->reflection)] = $this->reflection;
  
  }//method
  
  public function getReflection(){ return $this->reflection; }//method
  
  public function hasInstance($class_name){ return isset($this->instance_map[$class_name]); }//method
  
  public function setInstance($instance){
  
    $class_name = get_class($instance);
    $this->instance_map[$class_name] = $instance;
  
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
  
    if(!$this->hasInstance($class_name)){
    
      $this->instance_map[$class_name] = $this->getNewInstance($class_name,$params);
    
    }//if/else
  
    return $this->instance_map[$class_name];
  
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
        
      }catch(Exception $e){}//try/catch
      
    }//foreach
  
    if(empty($instance_class_name)){
    
      throw new \UnexpectedValueException(
        sprintf('Unable to find suitable class using [%s]',join(',',$class_name))
      );
    
    }else{
    
      if(!$this->hasInstance($instance_class_name)){
      
        $this->instance_map[$instance_class_name] = $this->getNewInstance($instance_class_name,$params);
      
      }//if/else
    
      $ret_instance = $this->instance_map[$instance_class_name];
      
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
    $rclass = new ReflectionClass($class_name);
    $instance_params = $this->normalizeParams($rclass,$params);
    
    if(empty($instance_params)){
    
      $ret_instance = new $class_name();
    
    }else{
    
      // http://www.php.net/manual/en/reflectionclass.newinstanceargs.php#95137
      $ret_instance = $rclass->newInstanceArgs($instance_params);
    
    }//if/else
  
    return $ret_instance;
  
  }//method
  
  protected function normalizeParams(ReflectionClass $rclass,array $params){
  
    // canary, make sure there is a __construct() method since we are passing in arguments...
    $rconstructor = $rclass->getConstructor();
    if(empty($rconstructor)){
      
      $class_name = $rclass->getName();
      
      throw new UnexpectedValueException(
        sprintf(
          'Normalizing "%s" constructor params failed because "%s" '
          .'has no __construct() method, so no constructor arguments can be used to instantiate it. '
          .'Please add %s::__construct(), or don\'t pass in any constructor arguments',
          $class_name,
          $class_name,
          $class_name
        )
      );
      
    }//if
    // canary, params are numeric, so just pass those into the constructor untouched...
    if(ctype_digit((string)join('',array_keys($params)))){ return $params; }//if
    
    $ret_params = array();
    $rparams = $rconstructor->getParameters();
    
    foreach($rparams as $rparam){

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
      
    }//foreach
    
    return $ret_params;
  
  }//method

}//class
