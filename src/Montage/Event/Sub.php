<?php
/**
 *  base class to allow easy class based event subscriptions
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 8-25-11
 *  @package montage
 *  @subpackage Event
 ******************************************************************************/
namespace Montage\Event;

abstract class Sub implements Subable {

  /**
   *  the event dispatcher
   *
   *  @see  setDispatch(), getDispatch()
   *  @var  Dispatch      
   */
  protected $dispatch = null;

  /**
   *  get the event dispatcher
   *
   *  @Param  Dispatch  $dispatch   
   */
  public function setDispatch(Dispatch $dispatch){ $this->dispatch = $dispatch; }//method
  
  /**
   *  get the event dispatcher
   *
   *  @return Dispatch   
   */
  public function getDispatch(){ return $this->dispatch; }//method
  
  /**
   *  register for the event
   */
  public function register(){
  
    $dispatch = $this->getDispatch();
    $callback = array($this,'handle');
    
    $event_name_list = (array)$this->getEventName();
    foreach($event_name_list as $event_name){
      $dispatch->listen($event_name,$callback);
    }//foreach
  
  }//method
  
  /**
   *  unregister this class from the event
   */
  public function unregister(){
  
    $dispatch = $this->getDispatch();
    $callback = array($this,'handle');
    
    $event_name_list = (array)$this->getEventName();
    foreach($event_name_list as $event_name){
      $dispatch->kill($event_name,$callback);
    }//foreach
  
  }//method

}//interface
