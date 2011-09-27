<?php
/**
 *  all start classes should extend this class to inherit basic functionality
 *  
 *  if you want to go full custom then just implement the Startable interface 
 *  
 *  @abstract 
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-17-11
 *  @package montage
 *  @subpackage Start  
 ******************************************************************************/     
namespace Montage\Start;

use Montage\Config\FrameworkConfig;

use Montage\Dependency\Containable;
use Montage\Dependency\Dependable;

use Montage\Field\Field;

use Montage\Event\Eventable;
use Montage\Event\Event;
use Montage\Event\Dispatch;

abstract class Start extends Field implements Startable, Dependable, Eventable {

  /**
   *  the framework configuration object
   *  
   *  @var  Montage\Config\FrameworkConfig
   */
  protected $framework_config = null;

  /**
   *  the dependency injection container
   *  
   *  @var  Montage\Dependency\Containable
   */
  protected $container = null;
  
  /**
   *  the event dispatcher
   *
   *  @see  setDispatch(), getDispatch()
   *  @var  Montage\Event\Dispatch      
   */
  protected $dispatch = null;

  public function __construct(FrameworkConfig $framework_config = null){
  
    $this->framework_config = $framework_config;
  
  }//method
  
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
   *  just to make it a little easier to broadcast the event, and to also be able to 
   *  easily override event broadcast for this entire class
   *  
   *  @since  8-25-11            
   *  @return Event
   */
  public function broadcastEvent(Event $event){
  
    $dispatch = $this->getEventDispatch();
    return empty($dispatch) ? $event : $dispatch->broadcast($event);
  
  }//method
  
}//class
