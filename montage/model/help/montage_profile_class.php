<?php

/**
 *  a simple way to take script execution measurements (ie, profile a block of code)
 *
 *  @version 0.3
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-25-10
 *  @package montage
 ******************************************************************************/
final class montage_profile {

  /**
   *  holds all active (started) profiling sessions
   *  
   *  @var  array
   */        
  private static $stack_started = array();
  
  /**
   *  holds all the finished profiling sessions
   *  
   *  @see  get()
   *  @var  array         
   */
  private static $stopped_map = array();
  
  /**
   *  start profiling a block of code
   *  
   *  @param  string  $title  the name you are giving to this block of profiled code
   *  @return boolean pretty much always returns true
   */
  static function start($title){
  
    $profile_map = array();
    $profile_map['start'] = microtime(true);
    $profile_map['title'] = $title;
    self::$stack_started[] = $profile_map;
    return true;

  }//method
  
  /**
   *  stop a profiling session
   *  
   *  profiling sessions are saved in a stack (FIFO) so this will pop the newest session
   *  off the top and stop it
   *  
   *  @return array the information about the profiling session
   */
  static function stop(){
  
    $profile_map = array_pop(self::$stack_started);
    if(!empty($profile_map)){
    
      $profile_map['stop'] = microtime(true);
      
      // get the execution time in milliseconds...
      $profile_map['time'] = round((($profile_map['stop'] - $profile_map['start']) * 1000),2);
      $profile_map['summary'] = sprintf('%s = %s ms',$profile_map['title'],$profile_map['time']);
      
      if(empty(self::$stack_started)){
      
        if(isset(self::$stopped_map[$profile_map['title']])){
          
          self::$stopped_map[$profile_map['title']] = array_merge(
            self::$stopped_map[$profile_map['title']],
            $profile_map
          );
        
        }else{
        
          self::$stopped_map[$profile_map['title']] = $profile_map;
          
        }//if/else
      
      }else{
      
        $add_profile = true;
      
        // go through and build a path...
        
        $current_map = &self::$stopped_map;
        foreach(self::$stack_started as $key => $map){
        
          if(!isset($current_map[$map['title']])){
            $current_map[$map['title']] = array('time' => 0,'children' => array());
          }//if
        
          $current_map = &$current_map[$map['title']]['children'];
          
          if(isset($current_map[$profile_map['title']])){
          
            $current_map[$profile_map['title']] = array_merge(
              $current_map[$profile_map['title']],
              $profile_map
            );
            
            $add_profile = false;
          
          }//if
        
        }//foreach
        
        if($add_profile){
        
          $current_map[$profile_map['title']] = $profile_map;
          
        }//if
        
      }//if/else
      
    }//if
  
    return $profile_map;
  
  }//method
  
  /**
   *  get all the profiling sessions that were created during execution
   *  
   *  @return array a list of "finished" profiling sessions
   */
  static function get(){ return self::$stopped_map; }//method

}//class
