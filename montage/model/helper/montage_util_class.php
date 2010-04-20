<?php

/**
 *  handy functions that don't seem to go anywhere else. A toolbox of sorts
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 3-26-10
 *  @package montage
 *  @subpackage help 
 ******************************************************************************/
class montage_util {
  
  /**
   *  function to make passing arguments into a CLI script easier
   *  
   *  an argument has to be in the form: --name=val or --name if you want name to be true
   *  
   *  if you want to do an array, then specify the name multiple times: --name=val1 --name=val2 will
   *  result in ['name'] => array(val1,val2)
   *  
   *  @param  array $argv the values passed into php from the commmand line 
   *  @return array the key/val mappings that were parsed from --name=val command line arguments
   */
  static function parseArgv($argv)
  {
    // canary...
    if(empty($argv)){ return array(); }//if
  
    $ret_map = array();
  
    foreach($argv as $arg){
    
      // canary...
      if((!isset($arg[0]) || !isset($arg[1])) || ($arg[0] != '-') || ($arg[1] != '-')){
        throw new InvalidArgumentException(
          sprintf('%s does not conform to the --name=value convention',$arg)
        );
      }//if
    
      $arg_bits = explode('=',$arg,2);
      // strip off the dashes...
      $name = mb_substr($arg_bits[0],2);
      $val = isset($arg_bits[1]) ? $arg_bits[1] : true;
      
      if(isset($ret_map[$name])){
      
        if(!is_array($ret_map[$name])){
          $ret_map[$name] = array($ret_map[$name]);
        }//if
        
        $ret_map[$name][] = $val;
        
      }else{
      
        $ret_map[$name] = $val;
        
      }//if/else
    
    }//foreach
  
    return $ret_map;
  
  }//method

}//class     
