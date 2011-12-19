<?php
/**
 *  interface to allow easy class based event subscriptions
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
