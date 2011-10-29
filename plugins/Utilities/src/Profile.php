<?php

/**
 *  a simple way to take script execution measurements (ie, profile a block of code)
 *
 *  @version 0.3
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-25-10
 *  @package Utilities
 ******************************************************************************/
class Profile {

  /**
   *  holds all active (started) profiling sessions
   *  
   *  @var  SplStack
   */
  protected $stack_started = null;
  
  /**
   *  holds all the finished profiling sessions
   *  
   *  @see  get()
   *  @var  array         
   */
  protected $stopped_map = array();
  
  /**
   *  hold the total profiled time
   *
   *  @var  float   
   */
  protected $total = 0.0;
  
  public function __construct(){
  
    $this->stack_started = new SplStack();
  
  }//method
  
  /**
   *  start profiling a block of code
   *  
   *  @param  string  $title  the name you are giving to this block of profiled code
   *  @return boolean pretty much always returns true
   */
  public function start($title){
  
    $profile_map = array();
    $profile_map['start'] = microtime(true);
    $profile_map['title'] = $title;
    $this->stack_started->push($profile_map);
    return true;

  }//method
  
  /**
   *  stop a profiling session
   *  
   *  profiling sessions are saved in a stack (LIFO) so this will pop the newest session
   *  off the top and stop it
   *  
   *  @return array the information about the profiling session
   */
  public function stop(){
  
    // canary...
    if($this->stack_started->isEmpty()){ return array(); }//if
  
    $profile_map = $this->stack_started->pop();
    
    $profile_map['stop'] = microtime(true);
    
    $profile_map['time'] = $this->getTime($profile_map['start'],$profile_map['stop']);
    
    $profile_map['summary'] = sprintf('%s = %s ms',$profile_map['title'],$profile_map['time']);
    
    if($this->stack_started->isEmpty()){
    
      if(isset($this->stopped_map[$profile_map['title']])){
        
        $this->stopped_map[$profile_map['title']] = array_merge(
          $this->stopped_map[$profile_map['title']],
          $profile_map
        );
      
      }else{
      
        $this->stopped_map[$profile_map['title']] = $profile_map;
        
      }//if/else
      
      $this->total += $profile_map['time'];
    
    }else{
    
      $add_profile = true;
    
      // go through and build a path...
      
      $current_map = &$this->stopped_map;
      foreach($this->stack_started as $key => $map){
      
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

    return $profile_map;
  
  }//method
  
  /**
   *  get all the profiling sessions that were created during execution
   *  
   *  @return array a list of "finished" profiling sessions
   */
  public function get(){ return $this->stopped_map; }//method
  
  /**
   *  get the total executed time
   *  
   *  @return float
   */
  public function getTotal(){
  
    $ret_total = $this->total;
  
    // if there is still stuff on the stack, the first item is the oldest, so use that
    // to calculate the total time...
    if(!$this->stack_started->isEmpty()){
    
      $profile_map = $this->stack_started->top();
      $ret_total += $this->getTime($profile_map['start'],microtime(true));
    
    }//if
    
    return $ret_total;
    
  }//method
  
  /**
   *  get the execution time in milliseconds.
   *  
   *  @param  float $start
   *  @param  float $stop
   *  @return float the total execution time
   */
  protected function getTime($start,$stop){
  
    return round((($stop - $start) * 1000),2);
  
  }//method

}//class
