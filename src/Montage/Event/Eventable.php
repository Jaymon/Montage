<?php
/**
 *  interface to allow a class to become an event broadcaster
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 8-29-11
 *  @package montage
 *  @subpackage Event
 ******************************************************************************/
namespace Montage\Event;

interface Eventable {

  /**
   *  get the event dispatcher
   *
   *  @Param  Dispatch  $dispatch   
   */
  public function setEventDispatch(\Montage\Event\Dispatch $dispatch);
  
  /**
   *  get the event dispatcher
   *
   *  @return Dispatch   
   */
  public function getEventDispatch();
  
  /**
   *  broadcast the $event
   *
   *  honestly, I put this in the interface so there would be an easy method to check if
   *  the event dispatcher was actually set and broadcast the message, the only reason this
   *  is public is because you have to make interface methods public, otherwise I would make
   *  this protected         
   *      
   *  @param  Event $event   
   */
  public function broadcastEvent(\Montage\Event\Event $event);
  
}//interface
