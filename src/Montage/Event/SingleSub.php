<?php
/**
 *  base class to allow easy class based event subscriptions
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 2-26-13
 *  @package montage
 *  @subpackage Event
 ******************************************************************************/
namespace Montage\Event;

abstract class SingleSub extends MultiSub {

  /**
   *  get the name of the event this class is subscribing to
   *  
   *  @return string  the event name
   */
  abstract public function getEventName();

  /**
   *  this is the callback that will be registered to the name returned from {@link getEventName()}
   *  
   *  @param  Event $event
   */
  abstract public function handle(Event $event);

  /**
   * return the format that is needed for Subscribe's register method
   *
   * @return  array event_name => callback pairs
   */
  public function getEventNames(){
    return array($this->getEventName() => array($this,'handle'));
  }//method

}//interface
