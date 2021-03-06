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

use Montage\Dependency\Containable;
use Montage\Dependency\Dependable;

abstract class MultiSub implements Subscribeable, Dependable {

  /**
   *  the event dispatcher
   *
   *  @see  setDispatch(), getDispatch()
   *  @var  Dispatch      
   */
  protected $dispatch = null;

  /**
   *  the dependency injection container
   *
   *  this class gets the DIC because it is better not to have the dependencies
   *  given to subscription classes on init, because other sub classes might subscribe
   *  to object creation events, but get created after a sub class has already created
   *  the object, so instead, it's better to not have your dependencies satisfied
   *  on class creation and instead get them on-demand when the event fires
   *  
   *  @see  setContainer(), getContainer()
   *  @var  \Montage\Dependency\Containable
   */
  protected $container = null;
  
  /**
   *  get the name of the event this class is subscribing to
   *  
   *  @return string  the event names this class subscribes to, this expects event_name => callback key/val pairs
   */
  abstract public function getEventNames();

  public function setContainer(Containable $container){ $this->container = $container; }//method
  public function getContainer(){ return $this->container; }//method

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
    
    $event_name_list = $this->getEventNames();
    foreach($event_name_list as $event_name => $callback){
      $dispatch->listen($event_name,$callback);
    }//foreach
  
  }//method
  
  /**
   *  unregister this class from the event
   */
  public function unregister(){
  
    $dispatch = $this->getEventDispatch();

    $event_name_list = $this->getEventNames();
    foreach($event_name_list as $event_name => $callback){
      $dispatch->kill($event_name,$callback);
    }//foreach
  
  }//method

}//interface
