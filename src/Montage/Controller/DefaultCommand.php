<?php
/**
 *  default command line controller
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 2013-3-2
 *  @package montage 
 ******************************************************************************/
namespace Montage\Controller;

class DefaultCommand extends Command {

  /**
   * print out all the commands available in the app
   */
  public function handleDefault(array $params = array()){
    $this->screen->out('TODO: print out all known commands');
  }//method

  /**
   *  check config stuff for potential problems
   *  
   *  this is inspired by Symfony 2.0's web/config.php file
   *
   *  TODO: update this method, not sure it still is useful
   *
   *  @since  8-23-11
   */
  public function handleCheck(array $params = array()){
  
    $warning_list = array();
    
    $this->screen->out('Checking Framework for potential problems...');
  
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
    
      $this->screen->out();
      $this->screen->out($break);
      $this->screen->out('* WARNINGS');
      $this->screen->out($break);
      $this->screen->out();
      
      foreach($warning_list as $index => $warning){
      
        $this->screen->out('%s - %s',$index + 1,$warning);
      
      }//foreach
    
    }//if
    
    return false;
  
  }//method
  
}//class

