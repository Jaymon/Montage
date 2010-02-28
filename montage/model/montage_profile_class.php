<?php

/**
 *  a simple way to take script execution measurements 
 *
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-25-10
 *  @package montage
 ******************************************************************************/
final class montage_profile {

  private static $stack_started = array();
  
  
  private static $stopped_list = array();
  
  
  static function start($title){
  
    $profile_map = array();
    $profile_map['start'] = microtime(true);
    $profile_map['title'] = $title;
    self::$stack_started[] = $profile_map;
    return true;

  }//method
  
  static function stop(){
  
    $profile_map = array_pop(self::$stack_started);
    if(!empty($profile_map)){
    
      $stop = microtime(true);
      
      // get the execution time in milliseconds...
      $time = round((($stop - $profile_map['start']) * 1000),2);
      
      // go through and build a path...
      $title = '';
      foreach(self::$stack_started as $key => $map){
        $title .= sprintf('%s > ',$map['title']);
      }//foreach
      $title .= $profile_map['title'];
      
      self::$stopped_list[] = sprintf('%s = %s ms',$title,$time);
      
    }//if
  
    return true;
  
  }//method
  
  static function get(){ return self::$stopped_list; }//method





}//class
