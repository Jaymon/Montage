<?php

/**
 *  php >= 5.4 has this class built-in, but until 5.4 is common, this will have to do
 *  as it will allow other classes to act like they have this iterator and it is built-in
 *  
 *  @link http://us2.php.net/manual/en/class.callbackfilteriterator.php 
 *  @link http://pastebin.com/2tYQGFQF  
 *    
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 9-20-11 
 ******************************************************************************/
class CallbackFilterIterator extends FilterIterator {

  /**
   *  the callback passed into the constructor
   *  
   *  the callback should take ($current,$key,$iterator) and return boolean      
   *
   *  @var  callback   
   */
  protected $callback;
  
  /**
   *  create an instance
   *
   *  @param  Traversable $iterator
   *  @param  callback  $callback   
   */
  public function __construct($iterator,$callback){

    $this->callback = $callback;

    parent::__construct($iterator);
  
  }//method
  
  public function accept(){
   
    return call_user_func(
      $this->callback,
      $this->current(),
      $this->key(),
      $this->getInnerIterator()
    );
    
  }//method

}//class
