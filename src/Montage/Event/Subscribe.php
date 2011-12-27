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

abstract class Subscribe implements Subscribeable {

  /**
   *  the event dispatcher
   *
   *  @see  setDispatch(), getDispatch()
   *  @var  Dispatch      
   */
  protected $dispatch = null;

  /**
   *  get the name(s) of the event(s) this class is subscribing to      
   *  
   *  @return string|array  if a string, then the it is the event name, if an array, then it is
   *                        a map of event_name/callback pairs 
   */
  abstract public function getEventName();

  /**
   *  this is the callback that will be registered to the name(s) returned from {@link getEventName()}
   *  
   *  @param  Event $event
   */
  abstract public function handle(Event $event);

  /**
   *  get the event dispatcher
   *
   *  @Param  Dispatch  $dispatch   
   */
  public function setEventDispatch(Dispatch $dispatch){ $this->dispatch = $dispatch; }//method
  
  /**
   *  get the event dispatcher
   *
   *  @return Dispatch   
   */
  public function getEventDispatch(){ return $this->dispatch; }//method
  
  /**
   *  register for the event
   */
  public function register(){
  
    $dispatch = $this->getEventDispatch();
    
    $event_name_list = $this->getEventName();
    if(is_array($event_name_list)){
      
      foreach($event_name_list as $event_name => $callback){
        $dispatch->listen($event_name,$callback);
      }//foreach
      
    }else{
    
      $callback = array($this,'handle');
      $dispatch->listen($event_name_list,$callback);
    
    }//if/else
  
  }//method
  
  /**
   *  unregister this class from the event
   */
  public function unregister(){
  
    $dispatch = $this->getEventDispatch();

    $event_name_list = $this->getEventName();
    if(is_array($event_name_list)){
      
      foreach($event_name_list as $event_name => $callback){
        $dispatch->kill($event_name,$callback);
      }//foreach
      
    }else{
    
      $callback = array($this,'handle');
      $dispatch->kill($event_name_list,$callback);
    
    }//if/else
  
  }//method

}//interface
