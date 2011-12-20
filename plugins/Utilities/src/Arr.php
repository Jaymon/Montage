<?php

/**
 *  hold lots of array helper methods
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 11-7-11
 *  @package Utilities
 ******************************************************************************/
class Arr extends \ArrayObject {

  /**
   *  used for indent in functions like {@link aIter()} and {@link outInfo()}
   *  @var  string   
   */     
  protected $indent = '    ';

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
  
  /**
   *  return the array in a nicely formatted string
   *  
   *  @since  12-19-11   
   *  @return string
   */
  public function __toString(){ return $this->render($this->getArrayCopy()); }//method
  
  /**
   *  prints out a nicely formatted string representation of the array
   *
   *  @since  12-19-11   
   *  @return string  the array contents in nicely formatted string form
   */
  public  function render(){
    
    $array = $this->getArrayCopy();
    
    $ret_val = $this->aIter($array,0);
    
    return $ret_val;
    
  }//method
  
  /**
   *  this does the actual recursion on the array so you can handle arrays of any dimension
   *  
   *  @since  12-19-11   
   *  @param  array $array  the array to print out
   *  @param  integer $deep how deep you are in the array, handy for indent formatting
   *  @return string
   */
  protected function aIter(array $array,$deep = 0){
  
    $ret_str = sprintf('Array (%s)%s(%s',count($array),PHP_EOL,PHP_EOL);

    foreach($array as $key => $val){

      $ret_str .= sprintf("\t[%s] => ",$key);
      
      if(is_object($val)){
      
        $ret_str .= trim($this->renderIndent($this->indent,$this->renderObject($val)));
      
      }else if(is_array($val)){
      
        $ret_str .= trim($this->renderIndent($this->indent,$this->aIter($val,$deep + 1)));
        
      }else{
      
        $ret_str .= $this->renderDefaultVal($val);
        
      }//if/else if/else
      
      $ret_str .= PHP_EOL;
      
    }//foreach

    $prefix = str_repeat($this->indent,($deep > 1) ? 1 : $deep);
  
    return trim($this->renderIndent($prefix,sprintf('%s)',$ret_str))).PHP_EOL;
  
  }//method
  
  /**
   *  output an object
   *  
   *  @since  12-19-11   
   *  @param  object  $obj  the object to output, this is different than outArray() and outVar() because
   *                        it can be called from {@link aIter()}              
   *  @return string  the printValue of an object
   */
  protected function renderObject($obj){
  
    $ret_str = '';
  
    if(method_exists($obj,'__toString')){
      $ret_str = get_class($obj).'->__toString() - '.$obj;
    }else{
      $ret_str = get_class($obj).' instance';
    }//if/else
  
    return $ret_str;
  
  }//method
  
  /**
   *  indent all the lines of $val with $indent
   *  
   *  @since  12-19-11   
   *  @param  string  $indent something like a tab
   *  @param  string  $val  the string to indent
   *  @return string  indented string
   */
  protected function renderIndent($indent,$val){
    return preg_replace('#^(.)#mu',$indent.'\1',$val);
  }//method
  
  protected function renderDefaultVal($val){
  
    $ret_str = '';
  
    if(is_null($val)){
    
      $ret_str .= 'NULL';
      
    }else if(is_bool($val)){
      
      $ret_str .= $val ? 'TRUE' : 'FALSE';
      
    }else{
    
      $ret_str .= $val;
    
    }//if/else if/else
  
    return $ret_str;
    
  }//method

}//class     
