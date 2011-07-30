<?php
/**
 *  Just some handy stuff to help Cli specific Controllers do their thing
 *  
 *  @note some of these methods would most likely be better as a trait, sadly we
 *  aren't on PHP 5.4, but maybe someday    
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 7-27-11
 *  @package montage 
 ******************************************************************************/
namespace Montage\Controller;

use Montage\Controller\Controller;
use Montage\Controller\ReflectionController;

class CliController extends Controller {

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

  public function preHandle(){
  
    if(!empty($this->request)){
    
      $this->is_trace = $this->request->getField('trace',false);
      $this->is_quiet = $this->request->getField('quiet',false);
    
      if(!$this->request->isCli()){
      
        throw new \RuntimeException(
          sprintf('Only command line requests are allowed for %s',get_class($this))
        );
        
      }//if
    
    }//if
  
  }//method
  
  /**
   *  print out all the different cli commands for this namespace
   *
   *  @param  array $params does nothing
   */
  public function handleHelp(array $params = array()){
  
    $rthis = new ReflectionController($this);
    echo $rthis;
    
    return false;
  
  }//method
  
  /**
   *  similar to out, but only outputs if --trace was passed in
   *
   *  @see  out()
   *  @since  2-7-11 by Jay
   */
  protected function trace()
  {
    if(empty($this->is_trace)){ return null; }//if
    
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
  protected function out($format_msg = '')
  {
    // sanity...
    if($this->is_quiet){ return; }//if
  
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
    
    echo $msg,PHP_EOL;
    flush();
  
  }//method

}//class
