<?php
/**
 *  interface to allow easy class based event subscriptions
 *
 *  any class that implements this interface will automatically subscribe to the
 *  events when the framework is started up, you should be careful to avoid dependency
 *  injection on startup because all the subscribe events might not be set so you
 *  could inadvertly create an object that would be created differently after all
 *  the events are registered (because a later Subscribeable child registers a 
 *  creation event)
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 8-25-11
 *  @package montage
 *  @subpackage Event
 ******************************************************************************/
namespace Montage\Event;

interface Subscribeable {
  
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
