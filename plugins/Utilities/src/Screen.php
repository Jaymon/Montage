<?php
/**
 *  provides methods for printing to the screen
 *  
 *  these methods were originally in the CliController, but I moved them into here
 *  because I had a situation where a non CliController derived Controller needed to
 *  print to the screen, man I can't wait for trait support in php    
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 12-22-11
 ******************************************************************************/
class Screen {
  
  /**
   *  this will be set to true if the --quiet option is passed in, it will silence
   *  all {@link out()} output   
   *
   *  @pram boolean
   */
  protected $is_quiet = false;
  
  /**
   *  this will be set to true if --trace option is passed in, it will turn on output
   *  for any {@link trace()} call   
   *
   *  @param  boolean
   */
  protected $is_trace = false;
  
  /**
   *  create instance
   *  
   *  @param  array $argv
   *  @param  array $req_argv_map arguments that have to be present, see {@link assure()}   
   */
  public function __construct($is_quiet = false,$is_trace = false){

    $this->is_quiet = $is_quiet;
    $this->is_trace = $is_trace;

  }//method
  
  public function render($format_msg = ''){

    $msg = '';
    $args = func_get_args();
    
    if(!empty($args))
    {
      $format_msg = $args[0];
      $var_list = array_slice($args,1);
      
      if(empty($var_list))
      {
        $msg = (string)$format_msg;
        
      }else{
        
        if(!isset($var_list[1]))
        {
          // an array of values was passed in instead of multiple values, ie...
          // ->out('format string',array($one,$two...)) instead of ->out('format string',$one,$two...)
        
          if(is_array($var_list[0]))
          {
            $var_list = $var_list[0];
          }//if
          
        }//if
        
        $msg = vsprintf($format_msg,$var_list);
        
      }//if
      
    }//if
    
    return $msg;
  
  }//method
  
  /**
   *  similar to out, but only outputs if --trace was passed in
   *
   *  @see  out()
   *  @since  2-7-11 by Jay
   */
  public function trace($format_msg = ''){
  
    if(empty($this->is_trace)){ return; }//if
    
    $args = func_get_args();
    return call_user_func_array(array($this,'out'),$args);
  
  }//method
  
  /**
   *  print out information to the user
   *  
   *  @example  $this->out('this is the %s string with %d args','format',2);
   *      
   *  @since  11-5-09   
   *  @param  string  $format_msg the message, if $var_list is present then inferred to
   *                              be a format string suitable for a sprintf call, if no
   *                              $var_list is present then it will just be printed out 
   *                              to the user
   *  @param  mixed $arg,...  any other arguments passed in are assumed to be the format string vars for
   *                          vsprintf                           
   */
  public function out($format_msg = ''){
  
    // sanity...
    if($this->is_quiet){ return; }//if
  
    $args = func_get_args();
    $msg = call_user_func_array(array($this,'render'),$args);
    echo $msg,PHP_EOL;
    flush();
  
  }//method

}//class
