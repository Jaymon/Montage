<?php
/**
 *  interface to allow easy class based event subscriptions
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 8-25-11
 *  @package montage
 *  @subpackage Event
 ******************************************************************************/
namespace Montage\Event;

interface Subscribeable {

  /**
   *  get the name(s) of the event(s) this class is subscribing to
   *  
   *  @return string|array      
   */
  public function getEventName();

  /**
   *  this is the callback that will be registered to the name(s) returned from {@link getEventName()}
   *  
   *  @param  Event $event
   */
  public function handle(Event $event);
  
  /**
   *  get the event dispatcher
   *
   *  @Param  Dispatch  $dispatch   
   */
  public function setEventDispatch(Dispatch $dispatch);
  
  /**
   *  get the event dispatcher
   *
   *  @return Dispatch   
   */
  public function getEventDispatch();
  
  /**
   *  register for the event
   */
  public function register();
  
  /**
   *  unregister this class from the event
   */
  public function unregister();

}//interface
