<?php

/**
 *  hold lots of array helper methods
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 11-7-11
 *  @package Utilities
 ******************************************************************************/
class Arr extends \ArrayObject {

  /**
   *  create class instance
   *
   *  @param  string|array  $str  if array it will join it by a space
   */
  public function __construct($arr){
  
    // canary...
    if(empty($arr)){ throw new \InvalidArgumentException('$arr was empty'); }//if
  
    $args = func_get_args();
    
    if(!isset($args[1])){
    
      $args = (array)$args[0];
    
    }//if
    
    parent::__construct($args);
  
  }//method
  
  /**
   *  return the internal raw string
   *  
   *  @return string      
   */
  ///public function __toString(){ return $this->str; }//method
  
  /**
   *  since you can't do things like [0,-4] on an array this is the next best thing
   *  
   *  this allows you to get as close as possible to Python's list handling, and it
   *  is a little different than array_slice because if just an offset is given it will just
   *  return one index and not the entire array from that offset forward      
   *
   *  @example     
   *    $this(0,-5); // cut off last 5 indexes
   *    $this(-3); // get third to last index
   *    $this(-3,null) // get from index 3 to the end of the array
   *
   *  @param  integer $offset where to start on the array
   *  @param  integer $length how big you want the array to be
   *  @return self
   */
  public function __invoke($offset,$length = 1){
  
    $ret_arr = array();
    
    if(empty($length)){
    
      $ret_arr = array_slice($this->getArrayCopy(),$offset);
    
    }else{
    
      $ret_arr = array_slice($this->getArrayCopy(),$offset,$length);
    
    }//if/else
  
    return $this->getInstance($ret_arr);
  
  }//method
  
  /**
   *  output the array as html/xml attributes in a nicely formatted string
   *     
   *  @return string
   */
  protected function attributify(array $attr_map){
    
    // canary...
    $attr_map = $this->getArrayCopy();
    if(empty($attr_map)){ return ''; }//if
  
    $ret_str = '';
    
    foreach($attr_map as $attr_name => $attr_val){
      
      if(is_array($attr_val) || is_object($attr_val)){
      
        $ret_str .= sprintf('%s="%s" ',$attr_name,json_encode($attr_val));
        
      }else{
      
        if(is_bool($attr_val)){
        
          $attr_val = $attr_val ? 'true' : 'false';
        
        }//if
      
        $ret_str .= sprintf('%s="%s" ',$attr_name,$attr_val);
        
      }//if/else
      
    }//foreach
    
    return trim($ret_str);
    
  }//method
  
  /**
   *  wrap a new instance around $str
   *
   *  @since  11-3-11
   *  @param  string  $str   
   *  @return self
   */
  protected function getInstance($arr){
  
    // canary...
    if($arr instanceof self){ return $arr; }//if
  
    $class_name = get_class($this);
    return new $class_name($arr);
  
  }//method

}//class     
