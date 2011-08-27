<?php
/**
 *  handles passing messages back and forth 
 *  
 *  this class is loosely based on the Symfony events from which I took inspiration
 *  http://components.symfony-project.org/event-dispatcher/
 *  http://symfony.com/doc/current/book/internals.html#event-dispatcher 
 *  
 *  I decided not to wrap Symfony's events stuff because I don't like priorities attached
 *  to events, while I get why they would seem useful, I think events shouldn't worry about
 *  order at all because what if you have a great system for setting priority using 1-10 but
 *  then add a plugin that uses 100-1000, all of a sudden that plugin's events always get
 *  fired before yours, so I think it is better to design your apps around not being able
 *  to order the event queue.
 *  
 *  also, I wanted to keep some of my features like persistent events
 *  
 *  @link http://en.wikipedia.org/wiki/Delegation_%28programming%29
 *  @link http://en.wikipedia.org/wiki/Publish/subscribe
 *  @link http://en.wikipedia.org/wiki/Observer_pattern
 *  
 *  I thought about using the SPL but it was too limiting:
 *  http://www.php.net/manual/en/class.splobserver.php       
 *  
 *  @version 0.4
 *  @author Jay Marcyes
 *  @since 4-2-10
 *  @package montage
 *  @subpackage event
 ******************************************************************************/
namespace Montage\Event;

class Dispatch {

  /**
   *  holds the event mappings. each key will have an array of callbacks to ping
   *
   *  @var  array   
   */
  protected $event_map = array();
  
  /**
   *  if $persistent = true is passed into {@link broadcast()} then the event will
   *  be kept in this map until a callback is registered with the key
   *  
   *  basically, this allows a backlog to be created
   *  
   *  @var  array
   */
  protected $persistent_map = array();
  
  /**
   *  set a listener that can be broadcast to
   *  
   *  @param  string  $key  the name of the event that will trigger the $callback
   *  @param  callable  $callback a valid php callback that will be called when $key is passed
   *                              into {@link broadcast()}. The $callback function will be passed
   *                              2 params ($key,$info_map) and if it returns true all remaining callbacks
   *                              listening to $key will not be executed
   *  @return boolean
   */
  public function listen($event_name,$callback){
  
    // canary...
    if(empty($event_name)){ throw new \InvalidArgumentException('$event_name cannot be empty'); }//if
    if(empty($callback)){ throw new \InvalidArgumentException('$callback cannot be empty'); }//if
    if(!is_callable($callback)){
      throw new \InvalidArgumentException(
        'a valid php $callback needs to be passed in. ' 
        .'See: http://us2.php.net/manual/en/language.pseudo-types.php#language.types.callback'
      );
    }//if
    
    if(!isset($this->event_map[$event_name])){ $this->event_map[$event_name] = array(); }//if
  
    $this->event_map[$event_name][] = $callback;
    
    // clear any backlog...
    if(isset($this->persistent_map[$event_name])){
      
      foreach($this->persistent_map[$event_name] as $event){ $this->broadcast($event); }//foreach
      unset($this->persistent_map[$event_name]);
      
    }//if
    
    return true;
  
  }//method
  
  /**
   *  broadcast the $key so all the listening callbacks will be pinged
   *  
   *  if one of the callbacks returns non-null then the remaining callbacks will not be called
   *  and that non-null value will be returned   
   *      
   *  @param  string  $key
   *  @param  array $info_map anything you want but usually a key/value array with information
   *                          you want to pass to each callback listening        
   *  @param  boolean $persistent true to make the message stay around until a callback is listening       
   *  @return Event returns the passed in event object
   */
  public function broadcast(Event $event){
  
    $event_name = $event->getName();
    $list = array();
    
    if(isset($this->event_map[Event::NAME_ALL])){
    
      $this->notify($this->event_map[Event::NAME_ALL],$event);
    
    }//if
  
    if(isset($this->event_map[$event_name])){
    
      $this->notify($this->event_map[$event_name],$event);
  
    }else{
    
      if($event->isPersistent()){ $this->persist($event); }//if
    
    }//if/else
    
    return $event;
  
  }//method
  
  /**
   *  notify each callback in the $callback_list with the $event
   *  
   *  @param  array $callback_list  all the callbacks to notify of $event      
   *  @param  Event $event  the event that is being broadcasted
   *  @return integer how many callbacks in the list were actually notified   
   */
  protected function notify(array $callback_list,Event $event){
  
    $ret_count = 0;
  
    foreach($callback_list as $callback){
      
      $bool = call_user_func($callback,$event);
      $event->bumpNotifyCount();
      $ret_count++;
      if($bool === true){ break; }//if
      
    }//foreach
    
    return $ret_count;
  
  }//method
  
  /**
   *  persist the event until an event handler is listening
   *  
   *  @since  8-25-11
   *  @param  Event $event
   */
  protected function persist(Event $event){
  
    $event_name = $event->getName();
  
    if(!isset($this->persistent_map[$event_name])){
      $this->persistent_map[$event_name] = array();
    }//if
    
    $this->persistent_map[$event_name][] = $event;
    
    // keep lists no bigger than 25...
    if(isset($this->persistent_map[$event_name][25])){
    
      array_shift($this->persistent_map[$event_name]);
    
    }//if
  
  }//method
  
  /**
   *  return true if listeners for $event_name events exist
   *  
   *  @since  8-26-11
   *  @param  string  $event_name   
   *  @return boolean
   */
  public function has($event_name){ return !empty($this->event_map[$event_name]); }//method
  
  /**
   *  return event information
   *  
   *  if no $event_name is passed in then return all the listening callbacks
   *      
   *  @param  string  $event_name return all the callbacks of this $key
   *  @return array if no $event_name, return all callbacks, otherwise return $event_name's callbacks
   */
  public function get($event_name = ''){
  
    $ret_list = array();
  
    if(empty($event_name)){
    
      $ret_list = $this->event_map;
    
    }else{
    
      if(isset($this->event_map[$event_name])){
        $ret_list = $this->event_map[$event_name];
      }//if
    
    }//if/else
    
    return $ret_list;
  
  }//method
  
  /**
   *  remove a listening $key or a specific $callback of a $key
   *  
   *  @param  string  $key  the listening key to remove
   *  @param  callback  $callback if you only want to remove a specific callback
   *                              of the $key pass it in
   *  @return boolean
   */
  public function kill($event_name,$callback = null){
  
    // canary...
    if(empty($event_name)){ throw new \InvalidArgumentException('$event_name cannot be empty'); }//if
    if(!isset($this->event_map[$event_name])){ return true; }//if
    
    if(empty($callback)){
    
      unset($this->event_map[$event_name]);
    
    }else{
    
      foreach($this->event_map[$event_name] as $event_index => $event_callback){
      
        if(is_array($event_callback)){
        
          if(is_array($callback)){
          
            $event_callback_class = $callback_class = '';
          
            if(is_object($event_callback[0])){
              $event_callback_class = get_class($event_callback[0]);
            }//if
            
            if(is_object($callback[0])){
              $callback_class = get_class($callback[0]);
            }//if
            
            if($callback_class == $event_callback_class){
            
              $event_callback_method = mb_strtolower($event_callback[1]); 
              $callback_method = mb_strtolower($callback[1]);
              
              if($callback_method == $event_callback_method){
                unset($this->event_map[$key][$event_index]);
              }//if
            
            }//if
            
          }//if
        
        }else{
        
          if($callback === $event_callback){
            unset($this->event_map[$event_name][$event_index]);
          }//if
        
        }//if/else if   
      
      }//foreach
    
    }//if/else

    return true;
  
  }//method
  
}//class
