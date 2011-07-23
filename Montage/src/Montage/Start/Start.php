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

use Montage\Config\FrameworkConfig;

use Montage\Dependency\Containable;
use Montage\Dependency\Dependable;

use Montage\Field\Field;

abstract class Start extends Field implements Startable, Dependable {

  protected $framework_config = null;

  protected $container = null;

  public function __construct(FrameworkConfig $framework_config = null){
  
    $this->framework_config = $framework_config;
  
  }//method
  
  public function setContainer(Containable $container){ $this->container = $container; }//method
  public function getContainer(){ return $this->container; }//method
  
}//class
