<?php
/**
 *  iterate through a multi-dimensional array
 *  
 *  @link http://www.php.net/manual/en/class.recursivearrayiterator.php
 *  @link http://www.php.net/manual/en/class.splstack.php
 *    
 *  @version 0.3
 *  @author Jay Marcyes
 *  @since 10-1-11 
 ******************************************************************************/
class FlattenArrayIterator extends RecursiveArrayIterator {
  
  /**
   *  holds the internal iterator
   *  
   *  @var  Iterator
   */
  protected $iterator = null;

  /**
   *  holds the internal stack that is used to keep track of each iterator
   *
   *  @var  SplStack   
   */
  protected $stack = null;
  
  /**
   *  holds all the keys needed to get to the current value
   *  
   *  @since  12-22-11
   *  @see  keys()
   *  @var  array
   */
  protected $keys = array();

  /**
   *  create instance
   *  
   *  @param  array $arr
   */
  public function __construct(array $arr){

    $this->iterator = new ArrayIterator($arr);
    $this->stack = new SplStack();
    
    parent::__construct($arr);
  
  }//method
  
  /**
   *  get the current element
   *  
   *  this will push the current iterator on the stack if an array is found
   *  
   *  @return mixed
   */
  public function current(){
  
    $current = $this->iterator->current();
    
    if(is_array($current)){
    
      $this->keys[] = $this->key();
    
      // move the iterator to the next element so it will be ready when it pops...
      $this->iterator->next();
      $this->stack->push($this->iterator);
    
      $this->iterator = new ArrayIterator($current);
      $current = $this->current();
    
    }//if
  
    return $current;
  
  }//method
  
  /**
   *  get all the keys that led to current
   *  
   *  @example
   *    $arr = array('foo' => array('bar' => array('baz' => 1)));
   *    $i = new FlattenArrayIterator($arr);
   *    foreach($i as $a){
   *      echo $a; // 1
   *      print_r($i->keys()); // array('foo','bar','baz')
   *    }            
   *
   *  original notes:   
   *  have the keys return an array() of all the keys down, so if the array looked
   *  like: array(0 => 'foo',1 => array(0 => array(0 => 'bar'))); then when we got to 
   *  value 'bar' the key would be: array(1,0,0) with the last value being the correct
   *  key
   *      
   *  @since  12-22-11
   *  @return array a list of all the keys needed to get to the current value
   */
  public function keys(){
  
    $keys = $this->keys;
    $keys[] = $this->key();
    return $keys;
  
  }//method
  
  public function key(){ return $this->iterator->key(); }//method
  
  public function next(){ return $this->iterator->next(); }//method
  
  public function rewind(){ return $this->iterator->rewind(); }//method
  
  /**
   *  return whether the current element is valid
   *
   *  this will pop the previous iterator off the stack (if one is available) if the
   *  current iterator is no longer valid
   *  
   *  @return boolean            
   */
  public function valid(){
  
    $is_valid = $this->iterator->valid();
    if($is_valid === false){
    
      if(!$this->stack->isEmpty()){
      
        $this->iterator = $this->stack->pop();
        array_pop($this->keys);
        
        $is_valid = $this->valid();
      
      }//if
    
    }//if
    
    return $is_valid;
    
  }//method

}//class
