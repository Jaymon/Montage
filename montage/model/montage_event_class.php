<?php

/**
 *  handles passing messages back and forth 
 *  
 *  this class is loosely based on the Symfony events from which I took inspiration...
 *  http://components.symfony-project.org/event-dispatcher/ 
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 4-2-10
 *  @package montage
 *  @subpackage messaging
 ******************************************************************************/       
class montage_event {

  /**
   *  any errors encountered will broadcast on this key
   */
  const KEY_ERROR = 'montage.error';
  
  /**
   *  general info about how the request is being handled will broadcast on this key
   */
  const KEY_INFO = 'montage.info';

  /**
   *  holds the event mappings. each key will have an array of callbacks to ping
   *
   *  @var  array   
   */
  protected $event_map = array();

  /**
   *  sets this class to handle errors, basically this is the on switch            
   */
  final function __construct(){
  
    // for inheritence, let child classes do any init they need...
    $this->start();
  
  }//method

  /**
   *  placeholder in case a user extended class is used and needs to do init stuff
   */
  protected function start(){}//method
  
  /**
   *  set a listener that can be broadcast to
   *  
   *  @param  string  $key  the name of the event that will trigger the $callback
   *  @param  callback  $callback a valid php callback that will be called when $key is passed
   *                              into {@link broadcast()}. The $callback function will be passed
   *                              2 params ($key,$info_map) and if it returns true all remaining callbacks
   *                              listening to $key will not be executed
   *  @return boolean
   */
  public function listen($key,$callback){
  
    // canary...
    if(empty($key)){ throw new UnexpectedValueException('$key cannot be empty'); }//if
    if(empty($callback)){ throw new UnexpectedValueException('$callback cannot be empty'); }//if
    if(!is_callable($callback)){
      throw new UnexpectedValueException(
        'a valid php $callback needs to be passed in. ' 
        .'See: http://us2.php.net/manual/en/language.pseudo-types.php#language.types.callback'
      );
    }//if
    
    if(!isset($this->event_map[$key])){ $this->event_map[$key] = array(); }//if
  
    $this->event_map[$key][] = $callback;
    
    return true;
  
  }//method
  
  /**
   *  broadcast the $key so all the listening callbacks will be pinged
   *  
   *  if one of the callbacks returns true then the remaining callbacks will not be called
   *      
   *  @param  string  $key
   *  @param  array $info_map anything you want but usually a key/value array with information
   *                          you want to pass to each callback listening            
   *  @return boolean
   */
  public function broadcast($key,$info_map = array()){
  
    // canary...
    if(empty($key)){ throw new UnexpectedValueException('$key cannot be empty'); }//if
  
    if(isset($this->event_map[$key])){
    
      foreach($this->event_map[$key] as $event_callback){
      
        $ret_bool = call_user_func($event_callback,$key,$info_map);
        if($ret_bool === true){ break; }//if
      
      }//foreach
  
    }//if
    
    return true;
  
  }//method
  
  /**
   *  return event information
   *  
   *  if no $key is passed in then return all the listening callbacks
   *      
   *  @param  string  $key  return all the callbacks of this $key
   *  @return array if no $key, return all callbacks, otherwise return $key's callbacks
   */
  public function get($key = ''){
  
    $ret_arr = array();
  
    if(empty($key)){
    
      $ret_arr = $this->event_map;
    
    }else{
    
      if(isset($this->event_map[$key])){
        $ret_arr = $this->event_map[$key];
      }//if
    
    }//if/else
    
    return $ret_arr;
  
  }//method
  
  /**
   *  remove a listening $key or a specific $callback of a $key
   *  
   *  @param  string  $key  the listening key to remove
   *  @param  callback  $callback if you only want to remove a specific callback
   *                              of the $key pass it in
   *  @return boolean
   */
  public function kill($key,$callback = null){
  
    // canary...
    if(empty($key)){ throw new UnexpectedValueException('$key cannot be empty'); }//if
    
    if(isset($this->event_map[$key])){
    
      if(empty($callback)){
      
        unset($this->event_map[$key]);
      
      }else{
      
        foreach($this->event_map[$key] as $event_index => $event_callback){
        
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
          
          }else if(is_string($event_callback)){
          
            if($callback == $event_callback){
              unset($this->event_map[$key][$event_index]);
            }//if
          
          }//if/else if   
        
        }//foreach
      
      }//if/else
      
    }//if
    
    return true;
  
  }//method
  
}//class
