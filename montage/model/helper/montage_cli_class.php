<?php

/**
 *  handy functions for working with the CLI (command line)
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 3-26-10
 *  @package montage
 *  @subpackage help 
 ******************************************************************************/
class montage_cli {
  
  /**
   *  set to true to turn off {@link out()} messages
   *  
   *  @var  boolean
   */
  public static $muffle = false;
  
  /**
   *  get just the argument parts of the argv
   *  
   *  @since  8-24-10   
   *  @return array
   */
  public static function getArgv()
  {
    if(empty($_SERVER['argv'])){ return array(); }//if
  
    $argv = $_SERVER['argv'];
  
    // do some hackish stuff to decide if the first argv needs to be stripped...
    $bt = debug_backtrace();
    if(!empty($bt[0]['file'])){
      $file_path = $bt[0]['file'];
      $file_name = basename($bt[0]['file']);
      if(($argv[0] == $file_path) || ($argv[0] == $file_name)){
        $argv = array_slice($argv,1);
      }//if
    }//if
  
    return $argv;
  
  }//method
  
  /**
   *  function to make passing arguments into a CLI script easier
   *  
   *  an argument has to be in the form: --name=val or --name if you want name to be true
   *  
   *  if you want to do an array, then specify the name multiple times: --name=val1 --name=val2 will
   *  result in ['name'] => array(val1,val2)
   *  
   *  @param  array $argv the values passed into php from the commmand line
   *  @param  array $required_argv_map hold required args that need to be passed in to be considered valid.
   *                                  The name is the key and the required value will be the val, if the val is null
   *                                  then the name needs to be there with a value (in $argv), if the val 
   *                                  is not null then that will be used as the default value if 
   *                                  the name isn't passed in with $argv 
   *  @return array the key/val mappings that were parsed from --name=val command line arguments
   */
  public static function parseArgv($argv,$required_argv_map = array())
  {
    $ret_map = array();
  
    // build the map that will be returned...
    if(!empty($argv)){
    
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
        
        $val = true;
        if(isset($arg_bits[1])){
          
          $val = $arg_bits[1];
          
          if(!is_numeric($val)){
            
            // convert literal true or false into actual booleans...
            switch($val){
            
              case 'true':
              case 'TRUE':
                $val = true;
                
              case 'false':
              case 'FALSE':
                $val = false;
            
            }//switch
          
          }//if
          
        }//if
        
        if(isset($ret_map[$name])){
        
          if(!is_array($ret_map[$name])){
            $ret_map[$name] = array($ret_map[$name]);
          }//if
          
          $ret_map[$name][] = $val;
          
        }else{
        
          $ret_map[$name] = $val;
          
        }//if/else
      
      }//foreach
      
    }//if
  
    // make sure any required key/val pairings are there...
    if(!empty($required_argv_map)){
    
      foreach($required_argv_map as $name => $default_val){
      
        if(!isset($ret_map[$name])){
          if($default_val === null){
            throw new UnexpectedValueException(
              sprintf(
                '%s was not passed in and is required, you need to pass it in: --%s=[VALUE]',
                $name,
                $name
              )
            );
          }else{
            $ret_map[$name] = $default_val;
          }///if/else
        }//if
      
      
      }//foreach
    
    }//if
  
    return $ret_map;
  
  }//method
  
  /**
   *  print out a line of information to the user
   *  
   *  a newline is automatically added to the output echo      
   *  
   *  @example  montage_cli::out('this is the %s string with %d args','format',2);
   *      
   *  @since  8-19-10 pulled from Plancast's BaseTask class   
   *  @param  string  $format_msg the message, if $var_list is present then inferred to
   *                              be a format string suitable for a sprintf call, if no
   *                              $var_list is present then it will just be printed out 
   *                              to the user
   *  @param  mixed $arg,...  any other arguments passed in are assumed to be the format string vars for
   *                          vsprintf                           
   */
  public static function out()
  {
    // sanity...
    if(self::$muffle){ return; }//if
  
    $args = func_get_args();
    if(!empty($args))
    {
      $format_msg = $args[0];
      $var_list = array_slice($args,1);
      
      if(empty($var_list))
      {
        $msg = $format_msg;
        
      }else{
        
        foreach($var_list as $key => $var){
        
          // turn arrays into strings...
          if(is_array($var)){
            $var_list[$key] = sprintf('[%s]',join(', ',$var));
          }//if
        
        }//foreach
        
        /** $total_vars = count($var_list);
        if($total_vars == 1)
        {
          if(is_array($var_list[0]))
          {
            $var_list = $var_list[0];
          }//if
        }//if
        **/
        
        $msg = vsprintf($format_msg,$var_list);
        
      }//if
      
      echo $msg,PHP_EOL;
      flush();
      
    }//if
  
  }//method

}//class     
