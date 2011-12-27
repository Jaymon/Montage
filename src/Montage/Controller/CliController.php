<?php
/**
 *  Just some handy stuff to help Cli specific Controllers do their thing
 *  
 *  @note some of these methods would most likely be better as a trait, sadly we
 *  aren't on PHP 5.4, but maybe someday    
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 7-27-11
 *  @package montage 
 ******************************************************************************/
namespace Montage\Controller;

use Montage\Controller\Controller;
use Montage\Reflection\ReflectionController;

class CliController extends Controller {

  /**
   *  @since  12-22-11  
   *  @var  \Screen
   */
  public $screen = null;

  public function preHandle(){
  
    if(!empty($this->request)){
    
      if(!$this->request->isCli()){
      
        throw new \RuntimeException(
          sprintf('Only command line requests are allowed for %s',get_class($this))
        );
        
      }//if
    
    }//if
  
  }//method
  
  /**
   *  check config stuff for potential problems
   *  
   *  this is inspired by Symfony 2.0's web/config.php file
   *  
   *  @since  8-23-11
   */
  public function handleCheck(array $params = array()){
  
    $warning_list = array();
    
    $this->out('Checking Framework for potential problems...');
  
    $arg_separator = ini_get('arg_separator.output');
    if($arg_separator == '&amp;'){
    
      $warning_list[] = '[INI] arg_separator.output is set to &amp; instead of &';
      
      /* test why this is bad:
      ini_set('arg_separator.output','&amp;');
      $values = array('foo[bar]' => 'adkfasjlfdkflsd','foo[che]' => 'adfkasdljfldk');
      $qs = http_build_query($values);
      parse_str($qs, $values);
      out::e($values);
      out::e(ini_get('arg_separator.output'));
      */
    
    }//if
    
    if(ini_get('magic_quotes_gpc')){
    
      $warning_list[] = '[INI] magic_quotes_gpc is ON';
      
    }//if
    
    if(ini_get('register_globals')){
    
      $warning_list[] = '[INI] register_globals is ON';
    
    }//if
    
    $break = str_repeat('*',79);
    
    if(empty($warning_list)){
    
      $this->out('NO PROBLEMS FOUND!');
    
    }else{
    
      $this->out();
      $this->out($break);
      $this->out('* WARNINGS');
      $this->out($break);
      $this->out();
      
      foreach($warning_list as $index => $warning){
      
        $this->out('%s - %s',$index + 1,$warning);
      
      }//foreach
    
    }//if
    
    return false;
  
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
   *  @deprecated 12-22-11 use \Screen instance instead
   */
  protected function trace(){
  
    $args = func_get_args();
    return call_user_func_array(array($this->screen,'trace'),$args);
  
  }//method
  
  /**
   *  @deprecated 12-22-11 use \Screen instance instead
   */
  protected function out($format_msg = ''){
  
    $args = func_get_args();
    return call_user_func_array(array($this->screen,'out'),$args);
  
  }//method

}//class
