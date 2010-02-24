<?php

/**
 *  provides safe outputting of variables
 *  
 *  checkout: http://us2.php.net/manual/en/function.class-parents.php and
 *    http://us2.php.net/manual/en/function.class-implements.php so you can check if
 *    a class is iterable and stuff when you escape it  
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage
 *  @subpackage template 
 ******************************************************************************/
class montage_escape implements ArrayAccess,Iterator,Countable {

  const TYPE_OBJECT = 1;
  const TYPE_ARRAY = 2;
  const TYPE_STRING = 3;
  const TYPE_SAFE = 4;
  
  const ACCESS_ARRAY = 1;
  const ACCESS_ITERATE = 2;
  const ACCESS_COUNT = 4;
  const ACCESS_ALL = 7;
  const ACCESS_NONE = 0;
  
  protected $access = self::ACCESS_NONE;

  protected $type = self::TYPE_SAFE;
  
  protected $val = null;
  
  protected $class_name = '';
  

  function __construct($val){
  
    if(is_array($val)){
    
      $this->type = self::TYPE_ARRAY;
      $this->access = self::ACCESS_ALL;
    
    }else if(is_object($val)){
    
      $this->type = self::TYPE_OBJECT;
      
      $this->access = self::ACCESS_NONE;
      if($val instanceof ArrayAccess){
        $this->access |= self::ACCESS_ARRAY;  
      }//if
      
      if($val instanceof Traversable){
        $this->access |= self::ACCESS_ITERATE;  
      }//if
      
      if($val instanceof Countable){
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
    
    $this->start();
  
  }//method
  
  function start(){}//method
  
  /**
   *  Required definition for Countable, allows count($this) to work
   *  @link http://www.php.net/manual/en/class.countable.php
   */
  function count(){
  
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
  function offsetSet($key,$val){
    
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val cannot be treated like an array');
    
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
  function offsetGet($key){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val cannot be treated like an array');
    $class_name = $this->class_name;
    return new $class_name($this->val[$key]);
  }//method
  /**
   *  Unset a value by it's key e.g. unset($A['title']);
   */
  function offsetUnset($key){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val cannot be treated like an array');
    unset($this->val[$key]);
  }//method
  /**
   *  Check value exists, given it's key e.g. isset($A['title'])
   */
  function offsetExists($key){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val cannot be treated like an array');
    return isset($this->val[$key]);
  }//method
  /**#@-*/
  
  /**#@+
   *  Required method definitions of Iterator interface
   *  
   *  @link http://php.net/manual/en/class.iterator.php      
   */
  function rewind(){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val is not iteratable');
    $this->val->rewind();
  }//method
  function current(){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val is not iteratable');
    $class_name = $this->class_name;
    return new $class_name($this->val->current());
  }//method
  function key(){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val is not iteratable');
    $class_name = $this->class_name;
    return new $class_name($this->val->key());
  }//method
  function next(){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val is not iteratable');
    $this->val->next();
  }//method
  function valid(){
    $this->assureAccess(self::ACCESS_ARRAY,'this escaped $val is not iteratable');
    return $this->val->valid();
  }//method
  
  function __call($method,$args){
  
    // canary...
    if(!$this->isType(self::TYPE_OBJECT)){
      throw new RuntimeException('cannot call a method since the escaped $val is not an object');
    }//if
  
    $class_name = $this->class_name;
    return new $class_name(call_user_func_array(array($this->val,$method),$args));
  
  }//method
  
  function __toString(){

    $ret_str = '';
  
    switch($this->type){
    
      case self::TYPE_STRING:
      
        $ret_str = montage_text::getSafe($this->val);
        break;
    
      case self::TYPE_OBJECT:
    
        if(method_exists($this->val,'__toString')){
          $ret_str = montage_text::getSafe($this->val->__toString());
        }else{
          throw new RuntimeException(
            sprintf(
              'the escaped object of type %s has no __toString() method',
              get_class($this->val)
            )
          );
        }//if/else
    
        break;
    
      case self::TYPE_ARRAY:
        throw new RuntimeException('Array cannot be printed out');
        break;
        
      case self::TYPE_SAFE:
      default:
        
        if(is_bool($this->val)){
          $ret_str = $this->val ? 'true' : 'false';
        }else if(is_numeric($this->val)){
          $ret_str = $this->val;
        }else{
          $ret_str = montage_text::getSafe($this->val);
        }//if/else if/else
    
    }//method
  
    return $ret_str;
  
  }//method
  
  ///function __isset(){}//method
  ///function __empty(){}//method
  
  /**
   *  assure that the internal $val has the given access
   *  
   *  @throws RuntimeException  if the given access isn't allowed
   */
  protected function assureAccess($access,$msg = 'the escaped $val cannot be used in this way'){
    if(!($this->access & $access)){ throw new RuntimeException($msg); }//if
  }//method
  
  protected function isType($type){ return $this->type === $type; }//if

}//class     
