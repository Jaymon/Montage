<?php
/**
 *  For a controller to do something on the command line, it must extend this Controller
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 2013-2-4
 *  @package montage 
 ******************************************************************************/
namespace Montage\Controller;

use Montage\Reflection\ReflectionController; // used for help

abstract class Command extends Controller {

  /**
   *  @since  12-22-11  
   *  @var  \Screen
   */
  public $screen = null;

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

}//class

