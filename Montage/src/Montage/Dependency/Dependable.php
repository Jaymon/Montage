<?php
/**
 *  this interface allows you to make any object dependancy injector aware. 
 *  
 *  This should be done sparingly as it basically makes what dependancies your class
 *  truly is dependant on opaque 
 *  
 *  @todo rename to Dependable?  
 *  
 *  
 *  http://en.wikipedia.org/wiki/Coupling_%28computer_science%29
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 5-24-11
 *  @package montage
 *  @subpackage interface  
 ******************************************************************************/
namespace Montage\Dependency;

interface Dependable {

  public function setContainer(\Montage\Dependency\Containable $container);
  
  public function getContainer();

}//interface
