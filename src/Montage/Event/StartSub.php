<?php
/**
 *  base class to make it easy to create startup bindings for plugins and apps
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 2-28-13
 *  @package montage
 *  @subpackage Event
 ******************************************************************************/
namespace Montage\Event;

abstract class StartSub extends SingleSub {

  /**
   * holds the event passed into handle
   *
   * @var Event
   */
  protected $event = null; 

  /**
   *  this subscribes to the preHandle event, which is the earliest event that can be
   *  subscribed to with the framework in full ready mode
   *  
   *  @return string  the event name
   */
  public function getEventName(){ return 'framework.preHandle'; }//method

  /**
   *  the handle() method calls this method
   *
   *  this is just to make it easier to quickly develop startup bindings for plugins and apps
   */
  abstract protected function start();

  /**
   *  this is the callback that will be registered to the name returned from {@link getEventName()}
   *  
   *  @param  Event $event
   */
  public function handle(Event $event){
    $this->event = $event;
    return $this->start();
  }//method

}//interface
