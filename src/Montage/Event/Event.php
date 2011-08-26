<?php
/**
 *  this is what is passed to the {@link \Montage\Event\Dispatch::broadcast()} method
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 8-25-11
 *  @package montage
 *  @subpackage event
 ******************************************************************************/
namespace Montage\Event;

use Montage\Field\Field;

class Event extends Field {

  /**
   *  any errors encountered will broadcast on this key
   */
  const NAME_ERROR = 'framework.error';
  
  /**
   *  any warnings encountered will broadcast on this key
   */
  const NAME_WARNING = 'framework.warning';
  
  /**
   *  general info about how the request is being handled will broadcast on this key
   */
  const NAME_INFO = 'framework.info';
  
  /**
   *  this is a special event in that a callback bounded to this event will be called
   *  with every broadcast event. Great for analytics and/or logging
   *  
   *  return values don't matter for this event, the callback will also receive the
   *  original calling key instead of the value of this key (ie, if you bind method "foo"
   *  to this key and event "bar" was broadcast, the callback would be: foo('bar',$info_map))            
   */
  const NAME_ALL = 'framework.all';

  /**
   *  the name of the event
   *
   *  @var  string   
   */
  protected $event_name = '';
  
  /**
   *  whether this event should persist until something is listening to it
   *
   *  @var  boolean   
   */
  protected $persist = false;
  
  /**
   *  create a new Event
   *  
   *  @param  string  $event_name the event name      
   *  @param  array $field_map  the values you want this event to contain when it is handled by the callbacks
   *  @param  boolean $persist  set to true to have this event stick around until something is listening to
   *                            $event_name, if something is already listening, then this is ignored
   */
  public function __construct($event_name,array $field_map = array(),$persist = false){
  
    $this->event_name = $event_name;
    $this->persist = $persist;
    $this->setFields($field_map);
  
  }//method
  
  public function getName(){ return $this->event_name; }//method
  
  public function isPersistent(){ return $this->persist; }//method

}//class
