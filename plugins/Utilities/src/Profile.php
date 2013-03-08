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
    $profile_map['time'] = $this->getTime($profile_map['start'], $profile_map['stop']);
    $profile_map['summary'] = sprintf('%s = %s ms', $profile_map['title'], $profile_map['time']);

    if($this->stack_started->isEmpty()){

      // the stack is empty, so this was a top level call, so it should be merged into an already
      // existing key with the same name (because we've maybe stopped children), or a new key if no
      // children were ever started and stopped.
    
      if(isset($this->stopped_map[$profile_map['title']])){
        
        $this->stopped_map[$profile_map['title']] = array_merge(
          $this->stopped_map[$profile_map['title']],
          $profile_map
        );
      
      }else{
      
        $this->stopped_map[$profile_map['title']] = $profile_map;
        
      }//if/else
      
      // we add to total here because otherwise it will get counted twice
      $this->total += $profile_map['time'];

    }else{

      // this is a child, so if there were 3 nested calls: Foo -> Bar -> che, and we
      // are stopping che, we should build a map that has a Foo key, with a Bar child, and
      // that Bar child will have a Che child, that's where this profile map will go
    
      // go through and build a path, by the time this is done, we'll know where $profile_map goes
      $current_map = &$this->stopped_map;
      // stack iteration is fixes as LIFO now, so we need to go through it backward
      $stack_key = count($this->stack_started) - 1;
      while(isset($this->stack_started[$stack_key])){
        $map = $this->stack_started[$stack_key--];
      
        if(!isset($current_map[$map['title']])){
          $current_map[$map['title']] = array('time' => 0, 'children' => array());
        }else{
          if(!isset($current_map[$map['title']]['children'])){
            $current_map[$map['title']]['children'] = array();
          }//if
        }//if/else
      
        $current_map = &$current_map[$map['title']]['children'];
        
      }//while
      
      if(isset($current_map[$profile_map['title']])){
        $current_map[$profile_map['title']] = array_merge(
          $current_map[$profile_map['title']],
          $profile_map
        );

      }else{
        $current_map[$profile_map['title']] = $profile_map;
      }//if/else

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
