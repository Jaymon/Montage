<?php

/**
 *  provides safe outputting of variables
 *  
 *  checkout: http://us2.php.net/manual/en/function.class-parents.php and
 *    http://us2.php.net/manual/en/function.class-implements.php so you can check if
 *    a class is iterable and stuff when you escape it. It doesn't extend montage_base
 *    because it wraps other classes that do extend it and we don't want method name
 *    clashing  
 *  
 *  @version 0.3
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage
 *  @subpackage Field 
 ******************************************************************************/
namespace Montage\Field;

class Escape implements \ArrayAccess,\Iterator,\Countable {

  const TYPE_OBJECT = 1;
  const TYPE_ARRAY = 2;
  const TYPE_STRING = 3;
  const TYPE_SAFE = 4;
  
  const ACCESS_ARRAY = 1;
  const ACCESS_ITERATE = 2;
  const ACCESS_COUNT = 4;
  const ACCESS_ALL = 7;
  const ACCESS_NONE = 0;
  
  /**
   *  one of the ACCESS_* constants, set in {@link __construct()}
   *  
   *  @var  integer
   */
  protected $access = self::ACCESS_NONE;

  /**
   *  one of the TYPE_* constants, set in {@link __construct()}
   *  
   *  @var  integer
   */
  protected $type = self::TYPE_SAFE;
  
  /**
   *  hold the value that is going to be escaped
   *  
   *  @var  mixed
   */
  protected $val = null;
  
  /**
   *  since this class can be overloaded, we need to make sure we always create instances
   *  of the right class, so this holds the class name
   *  
   *  @var  string
   */
  protected $class_name = '';
  
  /**
   *  set to the application defaults
   *  
   *  @var  string
   */
  protected $charset = '';
  
  public function __construct($val){
  
    if(is_array($val)){
    
      $this->type = self::TYPE_ARRAY;
      $this->access = self::ACCESS_ALL;
    
    }else if(is_object($val)){
    
      // define what features the object has... 
    
      $this->type = self::TYPE_OBJECT;
      
      $this->access = self::ACCESS_NONE;
      if($val instanceof \ArrayAccess){
        $this->access |= self::ACCESS_ARRAY;  
      }//if
      
      if($val instanceof \Traversable){
        $this->access |= self::ACCESS_ITERATE;  
      }//if
      
      if($val instanceof \Countable){
        $this->access |= self::ACCESS_COUNT;  
      }//if
    
    }else if(is_string($val)){
    
      $this->type = self::TYPE_STRING;
    
    }else{
    
      $this->type = self::TYPE_SAFE;
      $this->access = self::ACCESS_NONE;
    
    }//if
    
    $this->class_name = get_class($this);
    $this->val = $val;
    $this->charset = mb_internal_encoding();
  
  }//method
  
  /**
   *  if you ever need the actual vanilla value, you can call this method
   *  
   *  @return mixed the vanilla {@link $val}
   *  @throws BadMethodCallException  if $val is an object with a method of the same name
   */
  public function getRawVal(){
  
    if($this->assureType(self::TYPE_OBJECT)){
      if(method_exists($this->val,__FUNCTION__)){
        throw new \BadMethodCallException(
          sprintf(
            '%s has a %s method call but the wrapped $val (%s instance) also has a %s method. '
            .'I would consider changing the %s::%s method name so it does not conflict with %s.',
            $this->class_name,
            __FUNCTION__,
            get_class($this->val),
            __FUNCTION__,
            get_class($this->val),
            __FUNCTION__,
            __METHOD__
          )
        );  
      }//if
    }//if
  
    return $this->val;
    
  }//method
  
  /**
   *  Required definition for Countable, allows count($this) to work
   *  @link http://www.php.net/manual/en/class.countable.php
   */
  public function count(){
  
    $this->assureAccess(self::ACCESS_COUNT,'this escaped $val cannot be counted');
    return count($this->val);
    
  }//method
  
  /**#@+
   *  Required definitions of interface ArrayAccess
   *  @link http://www.php.net/manual/en/class.arrayaccess.php   
   */
  /**
   *  Set a value given it's key e.g. $A['title'] = 'foo';
   */
  public function offsetSet($key,$val){
    
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val cannot be treated like an array');
    
    $val = $this->assureRawVal($val);
    
    if($key === null){
      // they are trying to do a $obj[] = $val so let's append the $val
      // via: http://www.php.net/manual/en/class.arrayobject.php#93100
      $this->val[] = $val;
    }else{
      // they specified the key, so this will work on the internal objects...
      $this->val[$key] = $val;
    }//if/else
  }//method
  /**
   *  Return a value given it's key e.g. echo $A['title'];
   */
  public function offsetGet($key){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val cannot be treated like an array');
    $class_name = $this->class_name;
    return new $class_name($this->val[$key]);
  }//method
  /**
   *  Unset a value by it's key e.g. unset($A['title']);
   */
  public function offsetUnset($key){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val cannot be treated like an array');
    unset($this->val[$key]);
  }//method
  /**
   *  Check value exists, given it's key e.g. isset($A['title'])
   */
  public function offsetExists($key){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val cannot be treated like an array');
    return isset($this->val[$key]);
  }//method
  /**#@-*/
  
  /**#@+
   *  Required method definitions of Iterator interface
   *  
   *  @link http://php.net/manual/en/class.iterator.php      
   */
  public function rewind(){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val is not iteratable');
    $this->val->rewind();
  }//method
  public function current(){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val is not iteratable');
    $class_name = $this->class_name;
    return new $class_name($this->val->current());
  }//method
  public function key(){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val is not iteratable');
    $class_name = $this->class_name;
    return new $class_name($this->val->key());
  }//method
  public function next(){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val is not iteratable');
    $this->val->next();
  }//method
  public function valid(){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val is not iteratable');
    return $this->val->valid();
  }//method
  
  /**
   *  http://www.php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.methods
   */     
  public function __call($method,$args){
  
    // canary...
    if(!$this->assureType(self::TYPE_OBJECT)){
      throw new \LogicException('cannot call a method since the escaped $val is not an object');
    }//if
    
    // go through and get the raw values of each of the arguments to pass to the method...
    $args = array_map(array($this,'assureRawVal'),$args);
    ///foreach($args as $key => $arg){ $args[$key] = $this->assureRawVal($arg); }//method
    
    $class_name = $this->class_name;
    return new $class_name(call_user_func_array(array($this->val,$method),$args));
  
  }//method
  
  /**
   *  http://www.php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
   */        
  public function __set($name,$val){
  
    // canary...
    if(!$this->assureType(self::TYPE_OBJECT)){
      throw new \LogicException('cannot do things like $instance->foo since escaped $val is not an object');
    }//if
    if(!method_exists($this->val,'__set')){
      throw new \BadMethodCallException(
        'cannot do things like $instance->foo since escaped $val does not support __set() magic method'
      );
    }//if
    
    return $this->val->__set($key,$this->assureRawVal($val));
  
  }//method
  
  public function __get($name){
  
    // canary...
    if(!$this->assureType(self::TYPE_OBJECT)){
      throw new \LogicException('cannot do things like $instance->foo since escaped $val is not an object');
    }//if
    if(!method_exists($this->val,'__set')){
      throw new \BadMethodCallException(
        sprintf(
          'Cannot do $instance->foo since escaped $val of type %s does not support __get() magic method',
          gettype($this->val)
        )
      );
    }//if
    
    $class_name = $this->class_name;
    return new $class_name($this->val->__get($key));
  
  }//method
  
  public function __isset($name){
  
    // canary...
    if(!$this->assureType(self::TYPE_OBJECT)){
      throw new \LogicException('cannot do things like isset($instance->foo) since escaped $val is not an object');
    }//if
    if(!method_exists($this->val,'__isset')){
      throw new \BadMethodCallException(
        sprintf(
          'Cannot do isset($instance->foo) since escaped $val of type %s does not support __isset() magic method',
          gettype($this->val)
        )
      );
    }//if
    
    return $this->val->__isset($name);
    
  }//method
  
  public function __unset($name){
  
    // canary...
    if(!$this->assureType(self::TYPE_OBJECT)){
      throw new \LogicException('cannot do things like unset($instance->foo) since escaped $val is not an object');
    }//if
    if(!method_exists($this->val,'__isset')){
      throw new \BadMethodCallException(
        sprintf(
          'Cannot do unset($instance->foo) since escaped $val of type does not support __unset() magic method',
          gettype($this->val)
        )
      );
    }//if
    
    $this->val->__unset($name);
    
  }//method
  
  /**
   *  output the $val as a string
   *     
   *  http://www.php.net/manual/en/language.oop5.magic.php#language.oop5.magic.tostring
   *  
   *  @return string      
   */
  public function __toString(){

    $ret_str = '';
  
    switch($this->type){
    
      case self::TYPE_STRING:
      
        $ret_str = $this->getSafe($this->val);
        break;
    
      case self::TYPE_OBJECT:
    
        if(method_exists($this->val,'__toString')){
          $ret_str = $this->getSafe($this->val->__toString());
        }else{
          throw new \BadMethodCallException(
            sprintf(
              'the escaped object of type %s has no __toString() method',
              get_class($this->val)
            )
          );
        }//if/else
    
        break;
    
      case self::TYPE_ARRAY:
        throw new \RuntimeException('Array cannot be printed out');
        break;
        
      case self::TYPE_SAFE:
      default:
        
        if(is_bool($this->val)){
          $ret_str = $this->val ? 'true' : 'false';
        }else if(is_numeric($this->val)){
          $ret_str = $this->val;
        }else{
          $ret_str = $this->getSafe($this->val);
        }//if/else if/else
    
    }//method
  
    return $ret_str;
  
  }//method
  
  /**
   *  assure that the internal $val has the given access
   *  
   *  @throws RuntimeException  if the given access isn't allowed
   */
  protected function assureAccess($access,$msg = 'the escaped $val cannot be used in this way'){
    if(!($this->access & $access)){ throw new \RuntimeException($msg); }//if
  }//method
  
  /**
   *  return true if the internal type is the same as $type
   *  
   *  @param  integer $type one of the TYPE_* constants
   */
  protected function assureType($type){ return $this->type === $type; }//if
  
  /**
   *  if the passed in $val is an instance of this class, then return the raw value it wraps 
   *   
   *  this assures that methods that have type checks will still work without problems.
   *  Also, this makes sure this class doesn't wrap another instance
   *  
   *  @param  mixed $val
   *  @return mixed the absolute raw $val
   */
  protected function assureRawVal($val){
  
    $base_class_name = __CLASS__; // should always be the name of this class
    return ($val instanceof $base_class_name) ? $val->getRawValue() : $val;
    
  }//method
  
  /**
   *  return a safe value for $val that is suitable for display in stuff like the value attribute 
   *  
   *  @param  string  $val  the value to be "cleansed"
   *  @return string      
   */
  protected function getSafe($val){
    return htmlspecialchars($val,ENT_COMPAT,$this->charset,false); 
  }//method

}//class     
