<?php
/**
 *  all start classes should extend this class to already have basic functionality
 *  
 *  if you want to go full custom then just implement the Startable interface 
 *  
 *  @abstract 
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-17-11
 *  @package montage
 *  @subpackage Start  
 ******************************************************************************/     
namespace Montage\Start;

use Montage\Config;

use Montage\Dependency\Container;
use Montage\Dependency\Injector;

use Montage\Field;

abstract class Start extends Field implements Startable, Injector {

  protected $config = null;

  protected $container = null;

  public function __construct(FrameworkConfig $config){
  
    $this->config = $config;
    $this->setContainer($container);
  
  }//method
  
  public function setContainer(Container $container){ $this->container = $container; }//method
  public function getContainer(){ return $this->container; }//method
  
}//class
